-- -------------------------------
-- 获取有效矿机最大挖矿数
-- -------------------------------
DROP FUNCTION IF EXISTS `GetUserTodayMachineMaxAmount`;
DELIMITER ;;
CREATE FUNCTION `GetUserTodayMachineMaxAmount`(userId INT, portion DECIMAL(14, 4))
    RETURNS DECIMAL(14, 4)
BEGIN
    SET @maxAmount = 0;

    SELECT count(0) INTO @hasConsume FROM zc_consume WHERE user_id = userId LIMIT 1;
    IF @hasConsume = 0 THEN
        RETURN @maxAmount;
    END IF;

    SET @maxPortion = portion;

    SELECT machine_amount,
           machine_amount_1,
           machine_amount_2,
           machine_amount_3,
           machine_amount_4,
           machine_amount_5
           INTO @machineAmount_0, @machineAmount_1, @machineAmount_2, @machineAmount_3, @machineAmount_4, @machineAmount_5
    FROM zc_consume
    WHERE user_id = userId
    LIMIT 1;

    # 计算用户第5阶段矿机今日最大产出金额
    IF @machineAmount_5 > 0 AND @machineAmount_5 > @maxPortion THEN
        SET @machineAmount_5 = @maxPortion;
    END IF;
    IF @machineAmount_5 > 0 AND @maxPortion > 0 THEN
        SET @maxAmount = @maxAmount + GetSetting('mine_machine_day_max_amount_5') * @machineAmount_5;
        SET @maxPortion = @maxPortion - @machineAmount_5;
    END IF;

    # 计算用户第4阶段矿机今日最大产出金额
    IF @machineAmount_4 > 0 AND @machineAmount_4 > @maxPortion THEN
        SET @machineAmount_4 = @maxPortion;
    END IF;
    IF @machineAmount_4 > 0 AND @maxPortion > 0 THEN
        SET @maxAmount = @maxAmount + GetSetting('mine_machine_day_max_amount_4') * @machineAmount_4;
        SET @maxPortion = @maxPortion - @machineAmount_4;
    END IF;

    # 计算用户第3阶段矿机今日最大产出金额
    IF @machineAmount_3 > 0 AND @machineAmount_3 > @maxPortion THEN
        SET @machineAmount_3 = @maxPortion;
    END IF;
    IF @machineAmount_3 > 0 AND @maxPortion > 0 THEN
        SET @maxAmount = @maxAmount + GetSetting('mine_machine_day_max_amount_3') * @machineAmount_3;
        SET @maxPortion = @maxPortion - @machineAmount_3;
    END IF;

    # 计算用户第2阶段矿机今日最大产出金额
    IF @machineAmount_2 > 0 AND @machineAmount_2 > @maxPortion THEN
        SET @machineAmount_2 = @maxPortion;
    END IF;
    IF @machineAmount_2 > 0 AND @maxPortion > 0 THEN
        SET @maxAmount = @maxAmount + GetSetting('mine_machine_day_max_amount_2') * @machineAmount_2;
        SET @maxPortion = @maxPortion - @machineAmount_2;
    END IF;

    # 计算用户第1阶段矿机今日最大产出金额
    IF @machineAmount_1 > 0 AND @machineAmount_1 > @maxPortion THEN
        SET @machineAmount_1 = @maxPortion;
    END IF;
    IF @machineAmount_1 > 0 AND portion > 0 THEN
        SET @maxAmount = @maxAmount + GetSetting('mine_machine_day_max_amount_1') * @machineAmount_1;
        SET @maxPortion = @maxPortion - @machineAmount_1;
    END IF;

    # 计算用户后台充值矿机今日最大产出金额
    IF @machineAmount_0 > 0 AND @machineAmount_0 > @maxPortion THEN
        SET @machineAmount_0 = @maxPortion;
    END IF;
    IF @machineAmount_0 > 0 AND portion > 0 THEN
        SET @maxAmount = @maxAmount + GetSetting('mine_machine_day_max_amount_0') * @machineAmount_0;
        SET @maxPortion = @maxPortion - @machineAmount_0;
    END IF;

    RETURN @maxAmount;
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
    DECLARE c_user_loginname BIGINT DEFAULT 0;
    DECLARE c_queue_id INT DEFAULT 0;
    DECLARE c_open_time INT DEFAULT 0;
    DECLARE c_created_time INT DEFAULT 0;
    DECLARE c_updated_time INT DEFAULT 0;
    DECLARE c_consume_amount DECIMAL(14, 4) DEFAULT 0;
    DECLARE c_consume_amount_old DECIMAL(14, 4) DEFAULT 0;
    DECLARE c_consume_machine_amount DECIMAL(14, 4) DEFAULT 0;
    DECLARE c_mining_amount DECIMAL(14, 4) DEFAULT 0;
    DECLARE c_mining_out_bei INT DEFAULT 0;
    DECLARE c_static_worth INT DEFAULT 0;


    # 获取当日所有挖矿队列
    DECLARE c_queue CURSOR FOR
        SELECT m.id,
               m.loginname,
               mq.id,
               m.open_time,
               mq.created_time,
               mq.updated_time,
               c.amount,
               c.amount_old,
               c.machine_amount,
               ifnull(mi.amount, 0),
               ifnull(2, 0),
               c.static_worth
        FROM zc_mining_queue AS mq
                 LEFT JOIN zc_member AS m ON mq.user_id = m.id
                 LEFT JOIN zc_consume AS c ON m.id = c.user_id
                 LEFT JOIN zc_mining AS mi ON m.id = mi.user_id
                 LEFT JOIN zc_consume_rule AS cr ON c.level = cr.level
        WHERE m.level = 2
          AND c.dynamic_out = 0 # 动态收益是否出局
          AND m.is_lock = 0
          AND mq.is_expired = 0
          AND mq.created_time > UNIX_TIMESTAMP(FROM_UNIXTIME(unix_timestamp(), '%Y-%m-%d'))
          AND m.id IS NOT NULL
          AND (
                (mi.id IS NOT NULL AND mi.tag = 0)
                OR mi.id IS NULL
            );

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

        # 特殊挖矿帐号每天单台矿机最大限额
        SET @specialUsers = GetSetting(concat('mine_special_users'));

        # 特殊挖矿帐号每天单台矿机最大限额
        SET @todayMachineMaxAmount_special = GetSetting(concat('mine_special_machine_day_max_amount'));
        IF @todayMachineMaxAmount_special <= 0 THEN
            SET @todayMachineMaxAmount_special = @todayMachineMaxAmount;
        END IF;

        # 每次单个矿机最大产出金额
        SET @onceMachineMaxAmount = GetSetting(concat('mine_machine_one_max_amount'));
        IF @onceMachineMaxAmount <= 0
        THEN
            LEAVE out_label;
        END IF;

        SET @goldcoinPrice = 1;
        SELECT amount INTO @goldcoinPrice
        FROM zc_goldcoin_prices
        ORDER BY id DESC
        LIMIT 1;

        OPEN c_queue;
        REPEAT
            FETCH c_queue
                INTO c_user_id, c_user_loginname, c_queue_id, c_open_time, c_created_time, c_updated_time, c_consume_amount, c_consume_amount_old, c_consume_machine_amount, c_mining_amount, c_mining_out_bei, c_static_worth;
            IF NOT done
            THEN
                BEGIN
                    out_repeat:
                    BEGIN
                        # 总矿机 += 正式矿机（最小单位：0.5/500 ）
                        SET @portion = floor((c_consume_amount - c_consume_amount_old) / (@performancePortionBase / 2)) / 2;
                        # 总矿机 += 内排矿机（最小单位：0.1/1000 ）
                        SET @portion = @portion + floor(c_consume_amount_old / @performancePortionBase) * @mineOldMachineBai * 0.01;
                        # 总矿机 += 后台充值矿机
                        SET @portion = @portion + c_consume_machine_amount;
                        # 有效矿机 = 总矿机 - 报废矿机( 挖矿收益价值 / 单台矿机报废价值  )
                        SET @portion = @portion - floor(c_static_worth / (@performancePortionBase * c_mining_out_bei));

                        IF @portion <= 0
                        THEN
                            LEAVE out_repeat;
                        END IF;

                        # 今日用户产出最大金额
                        IF FIND_IN_SET(c_user_loginname, CONCAT(@specialUsers)) THEN
                            SET @todayMaxAmount = @todayMachineMaxAmount_special * @portion;
                        ELSE
                            SET @todayMaxAmount = GetUserTodayMachineMaxAmount(c_user_id, @portion);
#               SET @todayMaxAmount = @todayMachineMaxAmount * @portion;
                        END IF;

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
                            CALL Income_add_static(c_user_id, @miningAmount, error);
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
