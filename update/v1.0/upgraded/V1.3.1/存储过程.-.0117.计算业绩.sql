-- -------------------------------
-- 计算业绩
-- -------------------------------
DROP PROCEDURE IF EXISTS `Performance_calculation`;
DELIMITER ;;
CREATE PROCEDURE `Performance_calculation`(IN orderId INT(11), OUT error INT(11))
BEGIN

  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
  SET error = 0;

  out_label:
  BEGIN

    SET @performanceAmount = 0;

    SELECT o.uid,
           sum(op.price_cash * op.product_quantity * op.performance_bai_cash * 0.01),
           m.reid
           INTO
             @userId,
             @performanceAmount,
             @parentId
    FROM zc_orders AS o
           LEFT JOIN zc_order_product AS op ON o.id = op.order_id
           LEFT JOIN zc_member AS m ON o.uid = m.id
    WHERE o.id = orderId;

    IF @performanceAmount <= 0
    THEN
      LEAVE out_label;
    END IF;

    -- 计算消费者自己的业绩
    INSERT IGNORE INTO `zc_consume` (`user_id`) VALUE (@userId);
    SELECT `amount`,
           `level`
           INTO @userPerformanceAmount, @userLevel
    FROM `zc_consume`
    WHERE `user_id` = @userId;
    SET @userPerformanceAmount = @userPerformanceAmount + @performanceAmount;

    SET @newLevel = 0;
    SELECT `level` INTO @newLevel
    FROM zc_consume_rule
    WHERE amount <= @userPerformanceAmount
    ORDER BY `level` DESC
    LIMIT 1;

    IF @newLevel > @userLevel
    THEN
      SET @userLevel = @newLevel;
    END IF;

    UPDATE `zc_consume`
    SET `amount` = @userPerformanceAmount,
        `level`  = @userLevel,
        `uptime` = UNIX_TIMESTAMP()
    WHERE `user_id` = @userId;

    CALL Income_add(@userId, 0, error);
    IF error
    THEN
      LEAVE out_label;
    END IF;

    CALL Income_add(@parentID, 0, error);
    IF error
    THEN
      LEAVE out_label;
    END IF;

    -- 计算上级业绩
    INSERT IGNORE INTO zc_performance_queue (user_id, performance_amount, order_id, queue_status, queue_addtime, queue_starttime, queue_endtime)
    VALUES (@userId, @performanceAmount, orderId, 0, unix_timestamp(), unix_timestamp(), unix_timestamp());

    -- 分发收益
    CALL Income(@userId, @performanceAmount, orderId, error);
    IF error
    THEN
      LEAVE out_label;
    END IF;

  END out_label;
END
;;
DELIMITER ;


-- -------------------------------
-- 执行业绩结算队列
-- -------------------------------
DROP PROCEDURE IF EXISTS `Performance_queue`;
DELIMITER ;;
CREATE PROCEDURE `Performance_queue`(OUT error INT(11))
BEGIN

  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
  SET error = 0;

  out_label:
  BEGIN

    # 检查是否有队列正在执行
    SELECT count(0) INTO @hasLock
    FROM zc_performance_queue
    WHERE queue_status = 1;
    IF @hasLock
    THEN
      LEAVE out_label;
    END IF;

    SET @queueId = 0, @userId = 0, @performanceAmount = 0, @orderId = 0;
    SELECT queue_id,
           user_id,
           performance_amount,
           order_id
           INTO @queueId, @userId, @performanceAmount, @orderId
    FROM zc_performance_queue
    WHERE queue_status = 0
    ORDER BY queue_id ASC
    LIMIT 1;
    IF @queueId = 0
    THEN
      LEAVE out_label;
    END IF;

    UPDATE zc_performance_queue
    SET queue_status    = 1,
        queue_starttime = unix_timestamp()
    WHERE queue_id = @queueId;

    # 开启事务
    START TRANSACTION
      ;

      # 统计总业绩
      CALL Performance_add(0, @performanceAmount, error);

      IF error <> 1
      THEN
        # 统计自己的业绩
        CALL Performance_add(@userId, @performanceAmount, error);
      END IF;
      IF error <> 1 THEN
        # 计算星级
        CALL Performance_star(@userId, error);
      END IF;

      IF error <> 1
      THEN
        # 向上统计业绩
        CALL Performance_batch(@userId, @performanceAmount, error);
      END IF;

      IF error <> 1
      THEN
        CALL Performance_starBatch(@userId, error);
      END IF;

      IF error
      THEN
        ROLLBACK; # 回滚
        UPDATE zc_performance_queue
        SET queue_status  = 2,
            queue_endtime = unix_timestamp()
        WHERE queue_id = @queueId;
      ELSE
        COMMIT; # 提交
        UPDATE zc_performance_queue
        SET queue_status  = 3,
            queue_endtime = unix_timestamp()
        WHERE queue_id = @queueId;
      END IF
    ;

  END out_label;
END
;;
DELIMITER ;