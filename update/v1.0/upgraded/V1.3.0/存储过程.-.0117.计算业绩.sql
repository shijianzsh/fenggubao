-- -------------------------------
-- 计算业绩
-- -------------------------------
DROP PROCEDURE IF EXISTS `Performance_calculation`;
DELIMITER ;;
CREATE PROCEDURE `Performance_calculation`(IN orderId INT(11), OUT error INT(11))
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      SET @performanceAmount = 0;

      SELECT
        o.uid,
        sum(op.price_cash * op.product_quantity * op.performance_bai_cash * 0.01),
        m.reid
      INTO
        @userId,
        @performanceAmount,
        @parentId
      FROM
        zc_orders AS o
        LEFT JOIN zc_order_product AS op ON o.id = op.order_id
        LEFT JOIN zc_member AS m ON o.uid = m.id
      WHERE
        o.id = orderId;

      IF @performanceAmount <= 0
      THEN
        LEAVE out_label;
      END IF;

      -- 计算消费者自己的业绩
      INSERT IGNORE INTO `zc_consume` (`user_id`) VALUE (@userId);
      SELECT
        `amount`,
        `level`
      INTO @userPerformanceAmount, @userLevel
      FROM `zc_consume`
      WHERE `user_id` = @userId;
      SET @userPerformanceAmount = @userPerformanceAmount + @performanceAmount;

      SET @newLevel = 0;
      SELECT `level`
      INTO @newLevel
      FROM zc_consume_rule
      WHERE amount <= @userPerformanceAmount
      ORDER BY `level` DESC
      LIMIT 1;

      IF @newLevel > @userLevel
      THEN
        SET @userLevel = @newLevel;
      END IF;

      UPDATE `zc_consume`
      SET `amount` = @userPerformanceAmount, `level` = @userLevel, `uptime` = UNIX_TIMESTAMP()
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