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
  DECLARE c_consume_machine_amount DECIMAL(14, 4) DEFAULT 0;

  # 获取当日所有挖矿队列
  DECLARE c_queue CURSOR FOR
    SELECT m.id,
           mq.id,
           m.open_time,
           mq.created_time,
           mq.updated_time,
           c.amount,
           c.amount_old,
           c.machine_amount
    FROM zc_mining_queue AS mq
           LEFT JOIN zc_member AS m ON mq.user_id = m.id
           LEFT JOIN zc_consume AS c ON m.id = c.user_id
    WHERE m.level = 2
      AND c.dynamic_out = 0 # 动态收益是否出局
      AND m.is_lock = 0
      AND mq.is_expired = 0
      AND mq.created_time > UNIX_TIMESTAMP(FROM_UNIXTIME(unix_timestamp(), '%Y-%m-%d'))
      AND m.id IS NOT NULL;

  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; -- 异常错误
  SET error = 0;

  out_label:
  BEGIN
    # 一个矿机所需业绩金额
    SET @performancePortionBase = GetSetting(concat('performance_portion_base'));
    IF @performancePortionBase <= 0
    THEN
      LEAVE out_label;
    END IF;

    # 老矿机算力比例
    SET @mineOldMachineBai = GetSetting(concat('mine_old_machine_bai'));

    # 矿池金额
    SELECT IFNULL(SUM(account_points_balance), 0) INTO @mineTotalAmount
    FROM zc_account
    WHERE user_id = 1
      AND account_tag = 0;

    # 今日标识
    SET @todayTag = from_unixtime(unix_timestamp(), '%Y%m%d');

    # 今日产出总金额
    SELECT IFNULL(SUM(`amount`), 0) INTO @todayTotalMiningAmount
    FROM zc_mining
    WHERE `tag` = @todayTag
      AND user_id = 0;

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
    REPEAT
      FETCH c_queue
        INTO c_user_id, c_queue_id, c_open_time, c_created_time, c_updated_time, c_consume_amount, c_consume_amount_old, c_consume_machine_amount;
      IF NOT done
      THEN
        BEGIN
          out_repeat:
          BEGIN
            SET @portion = floor((c_consume_amount - c_consume_amount_old) / (@performancePortionBase / 2)) / 2;
            SET @portion = @portion + floor(c_consume_amount_old / @performancePortionBase) * @mineOldMachineBai * 0.01;
            SET @portion = @portion + c_consume_machine_amount;
            IF @portion <= 0
            THEN
              LEAVE out_repeat;
            END IF;

            # 今日用户产出最大金额
            SET @todayMaxAmount = @todayMachineMaxAmount * @portion;

            # 今日产出金额
            SELECT IFNULL(SUM(`amount`), 0) INTO @todayMiningAmount
            FROM zc_mining
            WHERE `tag` = @todayTag
              AND user_id = c_user_id;

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
              SET `amount`     = `amount` + @miningAmount,
                  updated_time = unix_timestamp()
              WHERE user_id IN (0, c_user_id)
                AND `tag` IN (0, @todayTag);

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
            SET is_expired = @isExpired,
                exec_time  = unix_timestamp()
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
