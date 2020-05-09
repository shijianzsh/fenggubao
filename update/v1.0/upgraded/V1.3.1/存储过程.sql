
-- ------------------- 存储过程.-.0110.挖矿.sql start ------------------------ 

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
              SET @portion = floor((c_consume_amount - c_consume_amount_old) / @performancePortionBase + (c_consume_amount_old * @mineOldMachineBai * 0.01) / @performancePortionBase);
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



-- ------------------- 存储过程.-.0110.挖矿.sql end ------------------------ 


-- ------------------- 存储过程.-.0112.收益-销售奖.sql start ------------------------ 

-- -------------------------------
-- 收益 - 销售奖
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income_consume`;
DELIMITER ;;
CREATE PROCEDURE `Income_consume`(
  IN  userId            INT(11),
  IN  performanceAmount DECIMAL(14, 4),
  IN  orderId           INT(11),
  OUT error             INT(11)
)
  BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE c_user_id INT DEFAULT 0;

    # 获取上二级个人代理
    DECLARE c_user CURSOR FOR
      SELECT p.id
      FROM
        zc_member AS m
        LEFT JOIN zc_member AS p ON find_in_set(p.id, m.repath)
        LEFT JOIN zc_consume AS c ON p.id = c.user_id
      WHERE
        m.id = userId
        AND p.level = 2
        AND p.is_lock = 0
        AND c.is_out = 0
      ORDER BY p.relevel DESC
      LIMIT 2;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; -- 异常错误
    SET error = 0;

    SET @userLevel = 0;

    out_label: BEGIN

      OPEN c_user;
      REPEAT FETCH c_user
      INTO c_user_id;
        IF NOT done
        THEN
          BEGIN
            SET @userLevel = @userLevel + 1;

            SET @consumeBai = GetSetting(CONCAT('prize_agent_consume_bai_', @userLevel));
            IF @consumeBai <= 0
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

            SET @consumeIncomeAmount = performanceAmount * @consumeBai * 0.01 / @goldcoinPrice;

            -- 分发流通资产
            SET @circulateBai = GetSetting(CONCAT('prize_agent_consume_circulate_bai_', @userLevel));
            IF @circulateBai > 0 THEN
              -- 添加明细
              CALL AddAccountRecord(c_user_id, 'goldcoin', 104, @consumeIncomeAmount * @circulateBai * 0.01, UNIX_TIMESTAMP(),
                                  concat('{"order_id":"', orderId, '"}'), 'xsj', @consumeBai, error);
              if error then
                leave out_label;
              end if;
            END IF;

            -- 分发锁定资产
            SET @lockBai = GetSetting(CONCAT('prize_agent_consume_lock_bai_', @userLevel));
            IF @lockBai > 0 THEN
              -- 添加明细
              CALL AddAccountRecord(c_user_id, 'bonus', 204, @consumeIncomeAmount * @lockBai * 0.01, UNIX_TIMESTAMP(),
                                  concat('{"order_id":"', orderId, '"}'), 'xsj', @lockBai, error);
              IF error THEN
                LEAVE out_label;
              END IF;
            END IF;

            -- 累计收益
            CALL Income_add(c_user_id, @consumeIncomeAmount, error);
            IF error
            THEN
              LEAVE out_label;
            END IF;

            -- 分发管理津贴
            CALL Income_subsidy(c_user_id, @consumeIncomeAmount, orderId, error);
            IF error
            THEN
              LEAVE out_label;
            END IF;

            -- 分发关爱奖
            CALL Income_care(c_user_id, @consumeIncomeAmount, orderId, error);

          END;
        END IF;
      UNTIL done END REPEAT;
      CLOSE c_user;
    END out_label;
  END
;;
DELIMITER ;

UPDATE zc_settings SET settings_value = 0 WHERE settings_code = 'prize_agent_consume_bai_2';

-- ------------------- 存储过程.-.0112.收益-销售奖.sql end ------------------------ 


-- ------------------- 存储过程.-.0114.收益-关爱奖.sql start ------------------------ 

-- -------------------------------
-- 收益 - 关爱奖
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income_care`;
DELIMITER ;;
CREATE PROCEDURE `Income_care`(
  IN  userId       INT(11),
  IN  incomeAmount DECIMAL(14, 4),
  IN  orderId      INT(11),
  OUT error        INT(11)
)
BEGIN

  DECLARE done INT DEFAULT 0;
  DECLARE c_user_id INT DEFAULT 0;
  DECLARE c_consume_amount DECIMAL(14, 4) DEFAULT 0;

  # 获取所有直接下线
  DECLARE c_user CURSOR FOR
    SELECT m.id,
           c.amount
    FROM zc_member AS m
           LEFT JOIN zc_consume AS c ON m.id = c.user_id
    WHERE m.level = 2
      AND m.is_lock = 0
      AND c.is_out = 0
      AND m.reid = userId
      AND c.id IS NOT NULL;

  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; -- 异常错误
  SET error = 0;

  out_label:
  BEGIN
    IF incomeAmount < 1
    THEN
      LEAVE out_label;
    END IF;

    SET @incomeBai = GetSetting(CONCAT('prize_care_agent_bai'));
    IF @incomeBai <= 0
    THEN
      LEAVE out_label;
    END IF;

    IF incomeAmount * @incomeBai < 0.5 THEN
      LEAVE out_label;
    END IF;

    SET @performancePortionBase = GetSetting(concat('performance_portion_base'));
    IF @performancePortionBase <= 0
    THEN
      LEAVE out_label;
    END IF;

    SET @count = 0;

    SELECT floor(sum(c.amount) / @performancePortionBase) INTO @count
    FROM zc_member AS m
           LEFT JOIN zc_consume AS c ON m.id = c.user_id
    WHERE m.level = 2
      AND m.is_lock = 0
      AND c.is_out = 0
      AND m.reid = userId
      AND c.id IS NOT NULL
    LIMIT 1;
    IF @count = 0
    THEN
      LEAVE out_label;
    END IF;

    SET @oneIncomeAmount = incomeAmount * @incomeBai * 0.01 / @count;
    IF @oneIncomeAmount < 0.0001
    THEN
      LEAVE out_label;
    END IF;

    OPEN c_user;
    REPEAT
      FETCH c_user
        INTO c_user_id, c_consume_amount;
      IF NOT done
      THEN
        BEGIN

          out_repeat:
          BEGIN

            SET @portion = floor(c_consume_amount / @performancePortionBase);
            IF @portion < 1
            THEN
              LEAVE out_repeat;
            END IF;

            SET @careIncomeAmount = @oneIncomeAmount * @portion;

            -- 分发流通资产
            SET @circulateBai = GetSetting('prize_care_agent_circulate_bai');
            IF @circulateBai > 0 THEN
              -- 添加明细
              CALL AddAccountRecord(c_user_id, 'goldcoin', 108, @careIncomeAmount * @circulateBai * 0.01, UNIX_TIMESTAMP(),
                                    concat('{"order_id":"', orderId, '"}'), 'gaj', @incomeBai, error);
              IF error THEN
                LEAVE out_label;
              END IF;
            END IF;

            -- 分发锁定资产
            SET @lockBai = GetSetting('prize_care_agent_lock_bai');
            IF @lockBai > 0 THEN
              -- 添加明细
              CALL AddAccountRecord(c_user_id, 'bonus', 208, @careIncomeAmount * @lockBai * 0.01, UNIX_TIMESTAMP(),
                                    concat('{"order_id":"', orderId, '"}'), 'gaj', @lockBai, error);
              IF error THEN
                LEAVE out_label;
              END IF;
            END IF;

            -- 累计收益
            CALL Income_add(c_user_id, @careIncomeAmount, error);
            IF error
            THEN
              LEAVE out_label;
            END IF;

            -- 添加关爱奖队列
            INSERT INTO `zc_care_queue` VALUES (NULL, c_user_id, @careIncomeAmount, orderId, 0, UNIX_TIMESTAMP(), 0, 0);

          END out_repeat;
        END;
      END IF;
    UNTIL done END REPEAT;
    CLOSE c_user;
  END out_label;
END
;;
DELIMITER ;

-- -------------------------------
-- 执行关爱奖队列
-- -------------------------------
DROP PROCEDURE IF EXISTS `Care_queue`;
DELIMITER ;;
CREATE PROCEDURE `Care_queue`(OUT error INT(11))
BEGIN

  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
  SET error = 0;

  out_label:
  BEGIN

    # 检查是否有队列正在执行
    SELECT count(0) INTO @hasLock
    FROM zc_care_queue
    WHERE queue_status = 1;
    IF @hasLock
    THEN
      LEAVE out_label;
    END IF;

    # 检查是否有队列需要执行
    SELECT count(0) INTO @hasQueue
    FROM zc_care_queue
    WHERE queue_status = 0;
    IF @hasQueue = 0
    THEN
      LEAVE out_label;
    END IF;

    SELECT id,
           user_id,
           income_amount,
           order_id
           INTO @queueId, @userId, @incomeAmount,@orderId
    FROM zc_care_queue
    WHERE queue_status = 0
    ORDER BY id ASC
    LIMIT 1;

    UPDATE zc_care_queue
    SET queue_status    = 1,
        queue_starttime = unix_timestamp()
    WHERE id = @queueId;

    # 开启事务
    START TRANSACTION;

      # 分发关爱奖
      CALL Income_care(@userId, @incomeAmount, @orderId, error);

      IF error
      THEN
        ROLLBACK; # 回滚
        UPDATE zc_care_queue
        SET queue_status  = 2,
            queue_endtime = unix_timestamp()
        WHERE id = @queueId;
      ELSE
        COMMIT; # 提交
        UPDATE zc_care_queue
        SET queue_status  = 3,
            queue_endtime = unix_timestamp()
        WHERE id = @queueId;
      END IF
    ;

  END out_label;
END
;;
DELIMITER ;

-- ----------------------------
-- 分发关爱奖收益（每天0时执行）
-- ----------------------------
DROP EVENT IF EXISTS `everysecond_Care`;
DELIMITER ;;
CREATE EVENT `everysecond_Care`
  ON SCHEDULE EVERY 5 SECOND
    STARTS '2018-12-01 00:00:00'
  ON COMPLETION PRESERVE
  ENABLE DO BEGIN

  CALL Care_queue(@error);

END
;;
DELIMITER ;

-- ------------------- 存储过程.-.0114.收益-关爱奖.sql end ------------------------ 


-- ------------------- 存储过程.-.0114.收益-区域合伙人奖.sql start ------------------------ 

-- -------------------------------
-- 收益 - 区域合伙人奖
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income_countyService`;
DELIMITER ;;
CREATE PROCEDURE `Income_countyService`(
  IN  userId            INT(11),
  IN  performanceAmount DECIMAL(14, 4),
  IN  orderId           INT(11),
  OUT error             INT(11)
)
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      SET @province = '', @city = '', @country = '';
      SELECT
        `province`,
        `city`,
        `country`
      INTO @province, @city, @country
      FROM zc_certification
      WHERE user_id = userId
      LIMIT 1;

      IF @province = '' OR @city = '' OR @country = ''
      THEN
        LEAVE out_label;
      END IF;

      SET @userId = 0;
      SELECT id
      INTO @userId
      FROM `zc_member`
      WHERE `role` = 3 AND `province` = @province AND `city` = @city AND `country` = @country
      LIMIT 1;
      IF @userId <= 0
      THEN
        LEAVE out_label;
      END IF;

      SET @incomeBai = GetSetting(CONCAT('prize_county_service_bai'));
      IF @incomeBai <= 0
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

      SET @countyIncomeAmount = performanceAmount * @incomeBai * 0.01 / @goldcoinPrice;

      -- 分发流通资产
      SET @circulateBai = GetSetting('prize_county_service_circulate_bai');
      IF @circulateBai > 0 THEN
        -- 添加明细
        CALL AddAccountRecord(@userId, 'goldcoin', 110, @countyIncomeAmount * @circulateBai * 0.01, UNIX_TIMESTAMP(),
                              concat('{"order_id":"', orderId, '"}'), 'qyhhrj', @incomeBai, error);
        if error then
          leave out_label;
        end if;
      END IF;

      -- 分发锁定资产
      SET @lockBai = GetSetting('prize_county_service_lock_bai');
      IF @lockBai > 0 THEN
        -- 添加明细
        CALL AddAccountRecord(@userId, 'bonus', 210, @countyIncomeAmount * @lockBai * 0.01, UNIX_TIMESTAMP(),
                              concat('{"order_id":"', orderId, '"}'), 'qyhhrj', @incomeBai, error);
        IF error THEN
          LEAVE out_label;
        END IF;
      END IF;

      -- 省级合伙人见点奖
      CALL Income_provinceServiceSee(userId, performanceAmount, orderId, error);

    END out_label;
  END
;;
DELIMITER ;

-- ------------------- 存储过程.-.0114.收益-区域合伙人奖.sql end ------------------------ 


-- ------------------- 存储过程.-.0114.收益-手动分红.sql start ------------------------ 

-- -------------------------------
-- 收益 - 手动分红
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income_bonus`;
DELIMITER ;;
CREATE PROCEDURE `Income_bonus`(
  IN  userStar          INT(11),
  IN  performanceAmount DECIMAL(14, 4),
  IN  totalAmount       DECIMAL(14, 4),
  OUT error             INT(11)
)
  BEGIN

    DECLARE done INT DEFAULT 0;
    DECLARE bonusId INT DEFAULT 0;
    DECLARE c_user_id INT DEFAULT 0;
    DECLARE c_consume_amount DECIMAL(14, 4) DEFAULT 0;

    # 获取指定等级的个代
    DECLARE c_user CURSOR FOR
      SELECT
        m.id,
        c.amount
      FROM zc_member AS m
        LEFT JOIN zc_consume AS c ON m.id = c.user_id
      WHERE
        m.level = 2
        AND m.star = userStar
        AND m.is_lock = 0
        AND c.is_out = 0
        AND c.id IS NOT NULL;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; -- 异常错误
    SET error = 0;

    out_label: BEGIN

      SET @tag = FROM_UNIXTIME(UNIX_TIMESTAMP(), '%Y%m%d');

      SELECT COUNT(0)
      INTO @hasBouns
      FROM zc_performance_bonus
      WHERE agent_star = userStar AND tag = @tag
      LIMIT 1;

      IF @hasBouns
      THEN
        LEAVE out_label;
      END IF;

      SET @performancePortionBase = GetSetting(concat('performance_portion_base'));
      IF @performancePortionBase <= 0
      THEN
        LEAVE out_label;
      END IF;

      SELECT floor(sum(c.amount) / @performancePortionBase)
      INTO @count
      FROM zc_member AS m
        LEFT JOIN zc_consume AS c ON m.id = c.user_id
      WHERE
        m.level = 2
        AND m.star = userStar
        AND m.is_lock = 0
        AND c.is_out = 0
        AND c.id IS NOT NULL
      LIMIT 1;
      IF @count = 0
      THEN
        LEAVE out_label;
      END IF;

      -- 添加分红记录
      INSERT INTO zc_performance_bonus
        VALUE (NULL, performanceAmount, totalAmount, userStar, @count, 0, @tag, UNIX_TIMESTAMP());

      SET bonusId = LAST_INSERT_ID(); # 获取明细ID
      SET @bonusOneAmount = totalAmount / @count;
      IF @bonusOneAmount < 0.01
      THEN
        LEAVE out_label;
      END IF;

      OPEN c_user;
      REPEAT FETCH c_user
      INTO c_user_id, c_consume_amount;
        IF NOT done
        THEN
          BEGIN

            out_repeat: BEGIN
              SET @portion = floor(c_consume_amount / @performancePortionBase);
              IF @portion < 1
              THEN
                LEAVE out_repeat;
              END IF;

              SET @bonusIncomeAmount = @bonusOneAmount * @portion;

              -- 分发流通资产
              SET @circulateBai = GetSetting('bonus_circulate_bai');
              IF @circulateBai > 0 THEN
                -- 添加明细
                CALL AddAccountRecord(c_user_id, 'goldcoin', 107, @bonusIncomeAmount * @circulateBai * 0.01, UNIX_TIMESTAMP(), '', 'jqfh', 0, error);
                if error then
                  leave out_label;
                end if;
              END IF;

              -- 分发锁定资产
              SET @lockBai = GetSetting('bonus_lock_bai');
              IF @lockBai > 0 THEN
                -- 添加明细
                CALL AddAccountRecord(c_user_id, 'bonus', 207, @bonusIncomeAmount * @lockBai * 0.01, UNIX_TIMESTAMP(), '', 'jqfh', 0, error);
                if error then
                  leave out_label;
                end if;
                IF error THEN
                  LEAVE out_label;
                END IF;
              END IF;

              # 累计实际分红金额
              UPDATE zc_performance_bonus
              SET bonus_amount = bonus_amount + @bonusIncomeAmount, updated_at = UNIX_TIMESTAMP()
              WHERE id = bonusId;

            END out_repeat;
          END;
        END IF;
      UNTIL done END REPEAT;
      CLOSE c_user;
    END out_label;
  END
;;
DELIMITER ;

-- ------------------- 存储过程.-.0114.收益-手动分红.sql end ------------------------ 


-- ------------------- 存储过程.-.0114.收益-省级合伙人奖.sql start ------------------------ 

-- -------------------------------
-- 收益 - 省级合伙人奖
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income_provinceService`;
DELIMITER ;;
CREATE PROCEDURE `Income_provinceService`(
  IN  userId            INT(11),
  IN  performanceAmount DECIMAL(14, 4),
  IN  orderId           INT(11),
  OUT error             INT(11)
)
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      SET @province = '';
      SELECT `province`
      INTO @province
      FROM zc_certification
      WHERE user_id = userId
      LIMIT 1;

      IF @province = ''
      THEN
        LEAVE out_label;
      END IF;

      SET @userId = 0;
      SELECT id
      INTO @userId
      FROM `zc_member`
      WHERE `role` = 4 AND `province` = @province
      LIMIT 1;

      IF @userId <= 0
      THEN
        LEAVE out_label;
      END IF;

      SET @incomeBai = GetSetting(CONCAT('prize_province_service_bai'));
      IF @incomeBai <= 0
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

      SET @provinceIncomeAmount = performanceAmount * @incomeBai * 0.01 / @goldcoinPrice;

      -- 分发流通资产
      SET @circulateBai = GetSetting('prize_province_service_circulate_bai');
      IF @circulateBai > 0 THEN
        -- 添加明细
        CALL AddAccountRecord(@userId, 'goldcoin', 111, @provinceIncomeAmount * @circulateBai * 0.01, UNIX_TIMESTAMP(),
                            concat('{"order_id":"', orderId, '"}'), 'sjhhrj', @incomeBai, error);
        if error then
          leave out_label;
        end if;
      END IF;

      -- 分发锁定资产
      SET @lockBai = GetSetting('prize_province_service_lock_bai');
      IF @lockBai > 0 THEN
        CALL AddAccountRecord(@userId, 'bonus', 211, @provinceIncomeAmount * @lockBai * 0.01, UNIX_TIMESTAMP(),
                            concat('{"order_id":"', orderId, '"}'), 'sjhhrj', @incomeBai, error);
        IF error THEN
          LEAVE out_label;
        END IF;
      END IF;

    END out_label;
  END
;;
DELIMITER ;

-- ------------------- 存储过程.-.0114.收益-省级合伙人奖.sql end ------------------------ 


-- ------------------- 存储过程.-.0114.收益-省级合伙人见点奖.sql start ------------------------ 

-- -------------------------------
-- 收益 - 省级合伙人见点奖
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income_provinceServiceSee`;
DELIMITER ;;
CREATE PROCEDURE `Income_provinceServiceSee`(
  IN  userId            INT(11),
  IN  performanceAmount DECIMAL(14, 4),
  IN  orderId           INT(11),
  OUT error             INT(11)
)
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      SET @province = '';
      SELECT `province`
      INTO @province
      FROM zc_certification
      WHERE user_id = userId
      LIMIT 1;

      IF @province = ''
      THEN
        LEAVE out_label;
      END IF;

      SET @userId = 0;
      SELECT id
      INTO @userId
      FROM `zc_member`
      WHERE `role` = 4 AND `province` = @province
      LIMIT 1;

      IF @userId <= 0
      THEN
        LEAVE out_label;
      END IF;

      SET @incomeBai = GetSetting(CONCAT('prize_province_service_see_bai'));
      IF @incomeBai <= 0
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

      SET @provinceSeeIncomeAmount = performanceAmount * @incomeBai * 0.01 / @goldcoinPrice;

      -- 分发流通资产
      SET @circulateBai = GetSetting('prize_province_service_see_circulate_bai');
      IF @circulateBai > 0 THEN
        -- 添加明细
        CALL AddAccountRecord(@userId, 'goldcoin', 112, @provinceSeeIncomeAmount * @circulateBai * 0.01, UNIX_TIMESTAMP(),
                            concat('{"order_id":"', orderId, '"}'), 'sjhhrjdj', @incomeBai, error);
        if error then
          leave out_label;
        end if;
      END IF;

      -- 分发锁定资产
      SET @lockBai = GetSetting('prize_province_service_see_lock_bai');
      IF @lockBai > 0 THEN
        CALL AddAccountRecord(@userId, 'bonus', 212, @provinceSeeIncomeAmount * @lockBai * 0.01, UNIX_TIMESTAMP(),
                            concat('{"order_id":"', orderId, '"}'), 'sjhhrjdj', @incomeBai, error);
        IF error THEN
          LEAVE out_label;
        END IF;
      END IF;

    END out_label;
  END
;;
DELIMITER ;

-- ------------------- 存储过程.-.0114.收益-省级合伙人见点奖.sql end ------------------------ 


-- ------------------- 存储过程.-.0114.收益-管理津贴.sql start ------------------------ 

-- -------------------------------
-- 收益 - 管理津贴
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income_subsidy`;
DELIMITER ;;
CREATE PROCEDURE `Income_subsidy`(IN userId INT(11),
                                  IN incomeAmount DECIMAL(14, 4),
                                  IN orderId INT(11),
                                  OUT error INT(11))
BEGIN

  DECLARE done INT DEFAULT 0;
  DECLARE c_user_id INT DEFAULT 0;
  DECLARE c_consume_level INT DEFAULT 0;

  # 获取直推个人代理
  DECLARE c_user CURSOR FOR
    SELECT p.id,
           c.`level`
    FROM zc_member AS m
           LEFT JOIN zc_member AS p ON find_in_set(p.id, m.repath)
           LEFT JOIN zc_consume AS c ON p.id = c.user_id
    WHERE m.id = userId
      AND p.level = 2
      AND p.is_lock = 0
      AND c.user_id IS NOT NULL
      AND c.level > 0
      AND c.is_out = 0
    ORDER BY p.relevel DESC
    LIMIT 21;

  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; -- 异常错误
  SET error = 0;

  out_label:
  BEGIN

    SET @subsidyIncomeAmount = incomeAmount;

    OPEN c_user;
    REPEAT
      FETCH c_user
        INTO c_user_id, c_consume_level;
      IF NOT done
      THEN
        BEGIN

          out_repeat:
          BEGIN

            SET @subsidy_bai = 0;
            SELECT subsidy_bai INTO @subsidy_bai
            FROM zc_consume_rule
            WHERE `level` <= c_consume_level
            ORDER BY `level` DESC
            LIMIT 1;

            IF @subsidy_bai <= 0
            THEN
              LEAVE out_repeat;
            END IF;

            SET @subsidyIncomeAmount = @subsidyIncomeAmount * @subsidy_bai * 0.01;
            IF @subsidyIncomeAmount < 1
            THEN
              LEAVE out_label;
            END IF;

            -- 分发流通资产
            SET @circulateBai = GetSetting('subsidy_agent_circulate_bai');
            IF @circulateBai > 0 THEN
              -- 添加明细
              CALL AddAccountRecord(c_user_id, 'goldcoin', 105, @subsidyIncomeAmount * @circulateBai * 0.01, UNIX_TIMESTAMP(),
                                    concat('{"order_id":"', orderId, '"}'), 'gljt', @subsidy_bai, error);
              IF error THEN
                LEAVE out_label;
              END IF;
            END IF;

            -- 分发锁定资产
            SET @lockBai = GetSetting('subsidy_agent_lock_bai');
            IF @lockBai > 0 THEN
               -- 添加明细
              CALL AddAccountRecord(c_user_id, 'bonus', 205, @subsidyIncomeAmount * @lockBai * 0.01, UNIX_TIMESTAMP(),
                                    concat('{"order_id":"', orderId, '"}'), 'gljt', @subsidy_bai, error);
              IF error THEN
                LEAVE out_label;
              END IF;
            END IF;

            -- 累计收益
            CALL Income_add(c_user_id, @subsidyIncomeAmount, error);
            IF error
            THEN
              LEAVE out_label;
            END IF;

            -- 分发关爱奖
            CALL Income_care(c_user_id, @subsidyIncomeAmount, orderId, error);
            IF error
            THEN
              LEAVE out_label;
            END IF;

          END out_repeat;

        END;
      END IF;
    UNTIL done END REPEAT;
    CLOSE c_user;
  END out_label;
END
;;
DELIMITER ;

-- ------------------- 存储过程.-.0114.收益-管理津贴.sql end ------------------------ 


-- ------------------- 存储过程.-.0114.添加锁定资产.sql start ------------------------ 

DROP PROCEDURE IF EXISTS `AddLockGoldcoin`;


-- ------------------- 存储过程.-.0114.添加锁定资产.sql end ------------------------ 


-- ------------------- 存储过程.-.0114.释放锁定资产.sql start ------------------------ 

DROP PROCEDURE IF EXISTS `Release`;

-- -------------------------------
-- 收益 - 释放挖矿锁定金额
-- -------------------------------
DROP PROCEDURE IF EXISTS `Release_lock`;
DELIMITER ;;
CREATE PROCEDURE `Release_lock`(
  OUT error             INT(11)
)
  BEGIN

    declare done int default 0;
    declare c_user_id int default 0;
    declare c_lock_amount int default 0;

    # 获取所有释放队列
    declare c_user cursor for
    select
      m.id,
      a.account_bonus_balance
    from
      zc_account as a
        left join zc_member as m on a.user_id = m.id
    where
      a.account_tag = 0
      AND a.account_bonus_balance > 0
      AND m.is_lock = 0;

    declare continue handler for not found set done = 1;
    declare continue handler for sqlexception set error = 1; -- 异常错误
    set error = 0;

    out_label: BEGIN

      SET @releaseBai = GetSetting('goldcoin_release_bai');
      IF @releaseBai <= 0 THEN
        leave out_label;
      END IF;

      open c_user;
      repeat fetch c_user into c_user_id,  c_lock_amount;
      if not done
      then
        begin
          out_repeat:BEGIN

            SET @releaseAmount = c_lock_amount * @releaseBai * 0.01;

            # 添加明细
            CALL AddAccountRecord(c_user_id, 'bonus', 254, -@releaseAmount, UNIX_TIMESTAMP(), '', 'sdjcsf', @releaseBai, error);
            if error
            then
              leave out_label;
            end if;

            # 添加明细
            CALL AddAccountRecord(c_user_id, 'goldcoin', 113, @releaseAmount, UNIX_TIMESTAMP(), '', 'sdjcsf', @releaseBai, error);

            if error
            then
              leave out_label;
            end if;

          END out_repeat;
        end;
      end if;
      until done end repeat;
      close c_user;
    END out_label;
  END
;;
DELIMITER ;



-- ----------------------------
-- Event structure for everyday_Release
-- ----------------------------
DROP EVENT IF EXISTS `everyday_Release`;
-- DELIMITER ;;
-- CREATE EVENT `everyday_Release`
--   ON SCHEDULE EVERY 1 DAY
--     STARTS '2019-01-01 00:00:00'
--   ON COMPLETION PRESERVE
--   ENABLE DO BEGIN
--
--   SET @switch = GetSetting(CONCAT('goldcoin_release_switch'));
--   IF @switch = '开启'
--   THEN
--     START TRANSACTION;
--     CALL Release_lock(@error);
--     IF @error THEN
--       ROLLBACK ;
--     ELSE
--       COMMIT ;
--     END IF;
--   END IF;
--
-- END
-- ;;
-- DELIMITER ;

-- ------------------- 存储过程.-.0114.释放锁定资产.sql end ------------------------ 


-- ------------------- 存储过程.-.0117.计算业绩.sql start ------------------------ 

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

-- ------------------- 存储过程.-.0117.计算业绩.sql end ------------------------ 


-- ------------------- 存储过程.-.0118.收益.sql start ------------------------ 

-- -------------------------------
-- 收益
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income`;
DELIMITER ;;
CREATE PROCEDURE `Income`(
  IN  userId            INT(11),
  IN  performanceAmount DECIMAL(14, 4),
  IN  orderId           INT(11),
  OUT error             INT(11)
)
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

--       # 流入矿池
--       CALL Mine_add(performanceAmount, orderId, error);
--       IF error
--       THEN
--         LEAVE out_label;
--       END IF;

      # 分发销售奖
      CALL Income_consume(userId, performanceAmount, orderId, error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      SET @specialDisableRoleIncome = GetSetting(concat('special_disable_role_income'));
      IF @specialDisableRoleIncome
      THEN
        SELECT count(0)
        INTO @hasDisable
        FROM
          zc_member AS pm
          LEFT JOIN zc_member AS m ON FIND_IN_SET(pm.id, m.repath)
        WHERE
          m.id = userId
          AND find_in_set(
              pm.loginname,
              @specialDisableRoleIncome
          );
        IF @hasDisable > 0
        THEN
          LEAVE out_label;
        END IF;
      END IF;

      # 分发区域合伙人奖
      CALL Income_countyService(userId, performanceAmount, orderId, error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 分发省级合伙人奖
      CALL Income_provinceService(userId, performanceAmount, orderId, error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

    END out_label;
  END
;;
DELIMITER ;

-- ------------------- 存储过程.-.0118.收益.sql end ------------------------ 


-- ------------------- 存储过程.-.0118.释放原始锁定资产.sql start ------------------------ 

DROP PROCEDURE IF EXISTS `Release`;

-- -------------------------------
-- 收益 - 释放原始锁定资产
-- -------------------------------
DROP PROCEDURE IF EXISTS `Release_oldLock`;
DELIMITER ;;
CREATE PROCEDURE `Release_oldLock`(
  OUT error             INT(11)
)
  BEGIN

    declare done int default 0;
    declare c_user_id int default 0;
    declare c_id int default 0;
    declare c_total_amount DECIMAL(14,4) default 0;
    declare c_release_amount DECIMAL(14,4) default 0;
    declare c_lock_amount DECIMAL(14,4) default 0;

    # 获取所有释放队列
    declare c_user cursor for
    select
      m.id,
      l.id,
      l.total_amount,
      l.lock_amount
    from
      zc_lock as l
        left join zc_member as m on l.user_id = m.id
    where
      l.tag <> FROM_UNIXTIME(UNIX_TIMESTAMP(), '%Y%m%d')
      AND l.lock_amount > 0
      AND m.is_lock = 0;

    declare continue handler for not found set done = 1;
    declare continue handler for sqlexception set error = 1; -- 异常错误
    set error = 0;

    out_label: BEGIN

      open c_user;
      repeat fetch c_user into c_user_id, c_id, c_total_amount, c_lock_amount;
      if not done
      then
        begin
          out_repeat:BEGIN

            SET  @releaseBai = 0.5;

            SET @releaseAmount = c_total_amount * @releaseBai * 0.01;
            if @releaseAmount > c_lock_amount THEN
              set @releaseAmount = c_lock_amount;
            end if;

            # 添加明细
            CALL AddAccountRecord(c_user_id, 'goldcoin', 113, @releaseAmount, UNIX_TIMESTAMP(), '', 'sdjcsf', @releaseBai, error);

            if error
            then
              leave out_label;
            end if;

            UPDATE zc_lock
            SET
              release_amount = release_amount + @releaseAmount,
              lock_amount = lock_amount - @releaseAmount,
              tag = FROM_UNIXTIME(UNIX_TIMESTAMP(), '%Y%m%d'),
              uptime = UNIX_TIMESTAMP()
            WHERE id = c_id;

          END out_repeat;
        end;
      end if;
      until done end repeat;
      close c_user;
    END out_label;
  END
;;
DELIMITER ;



-- ----------------------------
-- Event structure for everyday_Release
-- ----------------------------
DROP EVENT IF EXISTS `everyday_ReleaseOld`;
-- DELIMITER ;;
-- CREATE EVENT `everyday_ReleaseOld`
--   ON SCHEDULE EVERY 1 DAY
--     STARTS '2019-01-01 00:00:00'
--   ON COMPLETION PRESERVE
--   ENABLE DO BEGIN
--
--   SET @switch = GetSetting(CONCAT('goldcoin_release_switch'));
--   IF @switch = '开启'
--   THEN
--     START TRANSACTION;
--     CALL Release_oldLock(@error);
--     IF @error THEN
--       ROLLBACK ;
--     ELSE
--       COMMIT ;
--     END IF;
--   END IF;
--
-- END
-- ;;
-- DELIMITER ;

-- ------------------- 存储过程.-.0118.释放原始锁定资产.sql end ------------------------ 

