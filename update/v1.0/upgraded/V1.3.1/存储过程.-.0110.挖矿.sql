-- -------------------------------
-- 流入矿池
-- -------------------------------
DROP PROCEDURE IF EXISTS `Mine_add`;
DELIMITER ;;
CREATE PROCEDURE `Mine_add`(
  IN  performanceAmount DECIMAL(14, 4),
  IN  orderId           INT(11),
  OUT error             INT(11)
)
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      SET @mineBai = GetSetting(CONCAT('mine_order_bai'));
      IF @mineBai <= 0
      THEN
        LEAVE out_label;
      END IF;

      -- 获取公让宝最新价格
      SET @goldcoinPrice = 1;
      SELECT amount
      INTO @goldcoinPrice
      FROM zc_goldcoin_prices
      ORDER BY id DESC
      LIMIT 1;

      SET @mineAmount = performanceAmount * @mineBai * 0.01 / @goldcoinPrice;

      CALL AddAccountRecord(1, 'points', 502, @mineAmount, UNIX_TIMESTAMP(),
                            concat('{"order_id":"', orderId, '"}'), 'xfbb', @mineBai, error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

    END out_label;
  END
;;
DELIMITER ;

-- -------------------------------
-- 挖矿
-- -------------------------------
DROP PROCEDURE IF EXISTS `Mining`;
DELIMITER ;;
CREATE PROCEDURE `Mining`(
  OUT error INT(11)
)
  BEGIN

    DECLARE done INT DEFAULT 0;
    DECLARE c_user_id INT DEFAULT 0;
    DECLARE c_queue_id INT DEFAULT 0;
    DECLARE c_open_time INT DEFAULT 0;
    DECLARE c_created_time INT DEFAULT 0;
    DECLARE c_updated_time INT DEFAULT 0;
    DECLARE c_consume_amount DECIMAL(14, 4) DEFAULT 0;
    DECLARE c_consume_amount_old DECIMAL(14, 4) DEFAULT 0;

    # 获取当日所有挖矿队列
    DECLARE c_queue CURSOR FOR
      SELECT
        m.id,
        mq.id,
        m.open_time,
        mq.created_time,
        mq.updated_time,
        c.amount,
        c.amount_old
      FROM zc_mining_queue AS mq
        LEFT JOIN zc_member AS m ON mq.user_id = m.id
        LEFT JOIN zc_consume AS c ON m.id = c.user_id
      WHERE
        m.level = 2
        AND c.is_out = 0
        AND m.is_lock = 0
        AND mq.is_expired = 0
        AND mq.created_time > UNIX_TIMESTAMP(FROM_UNIXTIME(unix_timestamp(), '%Y-%m-%d'))
        AND m.id IS NOT NULL;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; -- 异常错误
    SET error = 0;

    out_label: BEGIN
      # 一个矿机所需业绩金额
      SET @performancePortionBase = GetSetting(concat('performance_portion_base'));
      IF @performancePortionBase <= 0
      THEN
        LEAVE out_label;
      END IF;

      # 老矿机算力比例
      SET @mineOldMachineBai = GetSetting(concat('mine_old_machine_bai'));

      # 矿池金额
      SELECT IFNULL(SUM(account_points_balance), 0)
      INTO @mineTotalAmount
      FROM zc_account
      WHERE user_id = 1 AND account_tag = 0;

      # 今日标识
      SET @todayTag = from_unixtime(unix_timestamp(), '%Y%m%d');

      # 今日产出总金额
      SELECT IFNULL(SUM(`amount`), 0)
      INTO @todayTotalMiningAmount
      FROM zc_mining
      WHERE `tag` = @todayTag AND user_id = 0;

      # 验证是否有矿
      IF @mineTotalAmount - @todayTotalMiningAmount <= 0
      THEN
        LEAVE out_label;
      END IF;

      # 验证今日产出是否达到上限
      SET @todayPoolMaxAmount = GetSetting(concat('mine_pool_max_amount'));
      IF @todayTotalMiningAmount >= @todayPoolMaxAmount
      THEN
        LEAVE out_label;
      END IF;

      # 每日单个矿机最大产出金额
      SET @todayMachineMaxAmount = GetSetting(concat('mine_machine_day_max_amount'));
      IF @todayMachineMaxAmount <= 0 THEN
        LEAVE out_label;
      END IF;

      # 每次单个矿机最大产出金额
      SET @onceMachineMaxAmount = GetSetting(concat('mine_machine_one_max_amount'));
      IF @onceMachineMaxAmount <= 0
      THEN
        LEAVE out_label;
      END IF;

      OPEN c_queue;
      REPEAT FETCH c_queue
      INTO c_user_id, c_queue_id, c_open_time, c_created_time, c_updated_time, c_consume_amount, c_consume_amount_old;
        IF NOT done
        THEN
          BEGIN
            out_repeat: BEGIN
              SET @portion = floor((c_consume_amount - c_consume_amount_old) / @performancePortionBase) + floor(c_consume_amount_old / @performancePortionBase ) * @mineOldMachineBai * 0.01 );
              IF @portion < 1
              THEN
                LEAVE out_repeat;
              END IF;

              # 今日用户产出最大金额
              SET @todayMaxAmount = @todayMachineMaxAmount * @portion;

              # 今日产出金额
              SELECT IFNULL(SUM(`amount`), 0)
              INTO @todayMiningAmount
              FROM zc_mining
              WHERE `tag` = @todayTag AND user_id = c_user_id;

              # 验证今日用户挖矿是否达到上限
              IF @todayMiningAmount >= @todayMaxAmount
              THEN
                LEAVE out_repeat;
              END IF;

              SET @maxBai = FLOOR((@todayMaxAmount - @todayMiningAmount) / @todayMaxAmount * 100);
              SET @minBai = FLOOR(@maxBai / 2);
              SET @randBai = FLOOR(@minBai + (RAND() * (@maxBai - @minBai + 1)));
              SET @miningAmount = @onceMachineMaxAmount * @randBai * 0.01 * @portion;
              IF @miningAmount > 0.0001
              THEN
                -- 初始化
                INSERT IGNORE INTO `zc_mining` (`user_id`, `tag`, `created_time`) VALUE (0, 0, unix_timestamp());
                INSERT IGNORE INTO `zc_mining` (`user_id`, `tag`, `created_time`) VALUE (0, @todayTag, unix_timestamp());
                INSERT IGNORE INTO `zc_mining` (`user_id`, `tag`, `created_time`) VALUE (c_user_id, 0, unix_timestamp());
                INSERT IGNORE INTO `zc_mining` (`user_id`, `tag`, `created_time`) VALUE (c_user_id, @todayTag, unix_timestamp());

                -- 累计挖矿
                UPDATE `zc_mining`
                SET `amount` = `amount` + @miningAmount, updated_time = unix_timestamp()
                WHERE user_id in (0, c_user_id) AND `tag` IN (0, @todayTag);

                -- 累计收益
                CALL Income_add(c_user_id, @miningAmount, error);
                IF error
                THEN
                  LEAVE out_label;
                END IF;

              END IF;

              -- 验证生命周期是否终止
              SET @isExpired = 0;
--               SET @lifecycle = GetSetting('mine_queue_lifecycle');
--               IF c_updated_time < unix_timestamp() - @lifecycle * 60
--               THEN
--                 SET @isExpired = 1;
--               END IF;

              -- 更新队列信息
              UPDATE zc_mining_queue
              SET is_expired = @isExpired, exec_time = unix_timestamp()
              WHERE id = c_queue_id;

            END out_repeat;
          END;
        END IF;
      UNTIL done END REPEAT;
      CLOSE c_queue;
    END out_label;
  END
;;
DELIMITER ;

-- -------------------------------
-- 挖矿结算
-- -------------------------------
DROP PROCEDURE IF EXISTS `Mining_settle`;
DELIMITER ;;
CREATE PROCEDURE `Mining_settle`(
  IN  miningTag INT(8),
  OUT error     INT(11)
)
  BEGIN

    DECLARE done INT DEFAULT 0;
    DECLARE c_user_id INT DEFAULT 0;
    DECLARE c_amount DECIMAL(14, 4) DEFAULT 0;

    # 获取所有挖矿数据
    DECLARE c_mining CURSOR FOR
      SELECT
        m.id,
        mi.amount
      FROM zc_mining AS mi
        LEFT JOIN zc_member AS m ON mi.user_id = m.id
      WHERE
        mi.tag = miningTag
        AND m.level = 2
        AND m.is_lock = 0
        AND m.id IS NOT NULL
        AND mi.user_id > 1;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; -- 异常错误
    SET error = 0;

    out_label: BEGIN

      OPEN c_mining;
      REPEAT FETCH c_mining
      INTO c_user_id, c_amount;
        IF NOT done
        THEN
          BEGIN
            out_repeat: BEGIN

              -- 分发流通资产
              SET @circulateBai = GetSetting('mine_circulate_bai');
              IF @circulateBai > 0 THEN
                -- 结算给用户
                CALL AddAccountRecord(c_user_id, 'goldcoin', 115, c_amount * @circulateBai * 0.01, UNIX_TIMESTAMP(),
                                    concat('{"miningTag":"', miningTag, '"}'), 'wk', 0, error);
                if error then
                  leave out_label;
                end if;
              END IF;

              -- 分发锁定资产
              SET @lockBai = GetSetting('mine_lock_bai');
              IF @lockBai > 0 THEN
                -- 结算给用户
                CALL AddAccountRecord(c_user_id, 'bonus', 215, c_amount * @lockBai * 0.01, UNIX_TIMESTAMP(),
                                    concat('{"miningTag":"', miningTag, '"}'), 'wk', 0, error);
                if error then
                  leave out_label;
                end if;
              END IF;

              -- 从矿池中扣除
              CALL AddAccountRecord(1, 'points', 550, -c_amount, UNIX_TIMESTAMP(),
                                    concat('{"user_id":"', c_user_id, '","miningTag":"', miningTag, '"}'), 'wk', 0,
                                    error);
              IF error
              THEN
                LEAVE out_label;
              END IF;

            END out_repeat;
          END;
        END IF;
      UNTIL done END REPEAT;
      CLOSE c_mining;
    END out_label;
  END
;;
DELIMITER ;

-- ----------------------------
-- 定时挖矿（每分钟执行一次）
-- ----------------------------
DROP EVENT IF EXISTS `everyminute_Mining`;
DELIMITER ;;
CREATE EVENT `everyminute_Mining`
  ON SCHEDULE EVERY 1 MINUTE
    STARTS '2019-01-01 00:00:00'
  ON COMPLETION PRESERVE
  ENABLE DO BEGIN

  START TRANSACTION;
  SET @switch = GetSetting(CONCAT('mine_switch'));
  IF @switch = '开启'
  THEN
    CALL Mining(@error);
    IF @error THEN
      ROLLBACK ;
    ELSE
      COMMIT ;
    END IF;
  END IF;

END
;;
DELIMITER ;

-- ----------------------------
-- 每天定时结算昨日挖矿数据
-- ----------------------------
DROP EVENT IF EXISTS `everyday_MiningSettle`;
DELIMITER ;;
CREATE EVENT `everyday_MiningSettle`
  ON SCHEDULE EVERY 1 DAY
    STARTS '2019-01-01 00:00:00'
  ON COMPLETION PRESERVE
  ENABLE DO BEGIN

  START TRANSACTION;
  # 今日tag
  SET @todayTag = FROM_UNIXTIME(unix_timestamp(), '%Y%m%d');
  # 昨日tag
  SET @yesterdayTag = DATE_FORMAT(DATE_ADD(@todayTag, INTERVAL -1 DAY), '%Y%m%d');

  CALL Mining_settle(@yesterdayTag, @error);
  IF @error THEN
    ROLLBACK ;
  ELSE
    COMMIT ;
  END IF;

END
;;
DELIMITER ;

