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

-- -------------------------------
-- 执行业绩结算队列
-- -------------------------------
DROP PROCEDURE IF EXISTS `Performance_queue`;
DELIMITER ;;
CREATE PROCEDURE `Performance_queue`(OUT error INT(11))
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      # 检查是否有队列正在执行
      SELECT count(0)
      INTO @hasLock
      FROM zc_performance_queue
      WHERE queue_status = 1;
      IF @hasLock
      THEN
        LEAVE out_label;
      END IF;

      SET @queueId = 0, @userId = 0, @performanceAmount = 0, @orderId = 0;
      SELECT
        queue_id,
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
      SET queue_status = 1, queue_starttime = unix_timestamp()
      WHERE queue_id = @queueId;

      # 开启事务
      START TRANSACTION;

      CALL Performance_add(0, @performanceAmount, error);

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
        SET queue_status = 2, queue_endtime = unix_timestamp()
        WHERE queue_id = @queueId;
      ELSE
        COMMIT; # 提交
        UPDATE zc_performance_queue
        SET queue_status = 3, queue_endtime = unix_timestamp()
        WHERE queue_id = @queueId;
      END IF;

    END out_label;
  END
;;
DELIMITER ;


-- -------------------------------
-- 批量结算用户上线业绩
-- -------------------------------
DROP PROCEDURE IF EXISTS `Performance_batch`;
DELIMITER ;;
CREATE PROCEDURE `Performance_batch`(
  IN  queueUserId INT(11),
  IN  amount      DECIMAL(14, 4),
  OUT error       INT(11)
)
  BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE c_user_id INT DEFAULT 0;

    # 用户所有上级推荐人
    DECLARE c_user CURSOR FOR
      SELECT p.id
      FROM
        zc_member AS m
        LEFT JOIN zc_member AS p ON find_in_set(p.id, m.repath)
      WHERE
        m.id = queueUserId
        AND p.`level` IN (2)  # 个人代理
        AND p.is_lock = 0;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; #异常错误
    SET error = 0;
    out_label: BEGIN

      OPEN c_user;
      REPEAT
        FETCH c_user
        INTO c_user_id;
        IF NOT done
        THEN
          BEGIN
            CALL Performance_add(c_user_id, amount, error);
            IF error
            THEN
              LEAVE out_label;
            END IF;
          END;
        END IF;
      UNTIL done END REPEAT;
      CLOSE c_user;
    END out_label;
  END
;;
DELIMITER ;


# -- -------------------------------
# -- 累计业绩
# -- -------------------------------
DROP PROCEDURE IF EXISTS `Performance_add`;
DELIMITER ;;
CREATE PROCEDURE `Performance_add`(IN userId INT(11), IN amount DECIMAL(14, 4), OUT error INT(11))
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      SET @now = UNIX_TIMESTAMP();

      # 初始化总业绩
      INSERT IGNORE INTO zc_performance (user_id, performance_amount, performance_tag, performance_uptime)
      VALUES (userId, 0, 0, @now);

      # 初始化当月业绩
      INSERT IGNORE INTO zc_performance (user_id, performance_amount, performance_tag, performance_uptime)
      VALUES (userId, 0, from_unixtime(@now, '%Y%m'), @now);

      # 累计业绩
      UPDATE zc_performance
      SET performance_amount = performance_amount + amount, performance_uptime = @now
      WHERE
        user_id = userId
        AND performance_tag IN (0, from_unixtime(@now, '%Y%m'));

      # 创建当月业绩表
      SET @v_sql = CONCAT('CREATE TABLE IF NOT EXISTS `zc_performance_', from_unixtime(@now, '%Y%m'),
                          '` LIKE `zc_performance`;');
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      # 初始化当日业绩
      SET @v_sql = CONCAT(
          'INSERT IGNORE INTO zc_performance_', from_unixtime(@now, '%Y%m'),
          ' (user_id, performance_amount, performance_tag, performance_uptime) VALUES (',
          userId, ', 0, ', from_unixtime(@now, '%Y%m%d'), ', ', @now, ');');
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      # 累计当日业绩
      SET @v_sql = CONCAT('update zc_performance_', from_unixtime(@now, '%Y%m'),
                          ' set performance_amount = performance_amount + ', amount, ', performance_uptime = ', @now,
                          ' where user_id in (', userId, ') and performance_tag = ', from_unixtime(@now, '%Y%m%d'),
                          ';');
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

    END out_label;
  END
;;
DELIMITER ;

-- -------------------------------
-- 批量结算用户上线业绩
-- -------------------------------
DROP PROCEDURE IF EXISTS `Performance_starBatch`;
DELIMITER ;;
CREATE PROCEDURE `Performance_starBatch`(
  IN  queueUserId INT(11),
  OUT error       INT(11)
)
  BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE c_user_id INT DEFAULT 0;

    # 用户所有上级推荐人
    DECLARE c_user CURSOR FOR
      SELECT p.id
      FROM
        zc_member AS m
        LEFT JOIN zc_member AS p ON find_in_set(p.id, m.repath)
      WHERE
        m.id = queueUserId
        AND p.`level` IN (2)  # 个人代理
        AND p.is_lock = 0;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; #异常错误
    SET error = 0;
    out_label: BEGIN

      OPEN c_user;
      REPEAT
        FETCH c_user
        INTO c_user_id;
        IF NOT done
        THEN
          BEGIN
            CALL Performance_star(c_user_id, error);
            IF error
            THEN
              LEAVE out_label;
            END IF;
          END;
        END IF;
      UNTIL done END REPEAT;
      CLOSE c_user;
    END out_label;
  END
;;
DELIMITER ;

# -- -------------------------------
# -- 业绩定星
# -- -------------------------------
DROP PROCEDURE IF EXISTS `Performance_star`;
DELIMITER ;;
CREATE PROCEDURE `Performance_star`(
  IN  userId INT(11),
  OUT error  INT(11)
)
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      SELECT count(0)
      INTO @hasUser
      FROM zc_member
      WHERE id = userId AND is_lock = 0;

      IF @hasUser = 0
      THEN
        LEAVE out_label;
      END IF;

      SELECT
        ifnull(m.`star`, 0),
        p.performance_amount
      INTO @userStar, @userPerformanceAmount
      FROM zc_member AS m
        LEFT JOIN zc_performance AS p ON m.id = p.user_id
      WHERE
        m.id = userId
        AND p.performance_tag = 0;

      SET @performanceStar = 0, @conditionCount = 0, @conditionLevel = 0;
      SELECT
        rule_id,
        rule_condition_count,
        rule_condition_level
      INTO @performanceStar, @conditionCount, @conditionLevel
      FROM zc_performance_rule
      WHERE rule_amount * 10000 <= @userPerformanceAmount
      ORDER BY rule_id DESC
      LIMIT 1;

      IF @performanceStar <= @userStar
      THEN
        LEAVE out_label;
      END IF;

      IF @conditionCount > 0
      THEN
        SELECT count(0)
        INTO @childrenCount
        FROM zc_member
        WHERE find_in_set(userId, repath) AND star >= @conditionLevel;
        IF @childrenCount < @conditionCount
        THEN
          LEAVE out_label;
        END IF;
      END IF;

      UPDATE zc_member
      SET star = @performanceStar
      WHERE id = userId;

    END out_label;
  END
;;
DELIMITER ;
