
-- ------------------- 存储过程.-.0109.定时创建明细表.sql start ------------------------ 

-- ----------------------------
-- 定时创建明细表
-- ----------------------------
DROP PROCEDURE IF EXISTS `TimerTask_recordtable`;
DELIMITER ;;
CREATE PROCEDURE `TimerTask_recordtable`(OUT error INT(11))
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    SET @month_tag = FROM_UNIXTIME(unix_timestamp(), '%Y%m');
    SET @nextmonth_tag = DATE_FORMAT(DATE_ADD(@month_tag * 100 + 1, INTERVAL 1 MONTH), '%Y%m');

    # 创建当月流通资产交易记录表
    SET @v_sql = CONCAT('CREATE TABLE IF NOT EXISTS `zc_account_goldcoin_', @month_tag, '` LIKE `zc_account_record`;');
    PREPARE statement FROM @v_sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    # 创建下月流通资产交易记录表
    SET @v_sql = CONCAT('CREATE TABLE IF NOT EXISTS `zc_account_goldcoin_', @nextmonth_tag, '` LIKE `zc_account_record`;');
    PREPARE statement FROM @v_sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    # 创建当月锁定资产交易记录表
    SET @v_sql = CONCAT('CREATE TABLE IF NOT EXISTS `zc_account_bonus_', @month_tag, '` LIKE `zc_account_record`;');
    PREPARE statement FROM @v_sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    # 创建下月锁定资产交易记录表
    SET @v_sql = CONCAT('CREATE TABLE IF NOT EXISTS `zc_account_bonus_', @nextmonth_tag, '` LIKE `zc_account_record`;');
    PREPARE statement FROM @v_sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    # 创建当月现金币交易记录表
    SET @v_sql = CONCAT('CREATE TABLE IF NOT EXISTS `zc_account_cash_', @month_tag, '` LIKE `zc_account_record`;');
    PREPARE statement FROM @v_sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    # 创建下月现金币交易记录表
    SET @v_sql = CONCAT('CREATE TABLE IF NOT EXISTS `zc_account_cash_', @nextmonth_tag, '` LIKE `zc_account_record`;');
    PREPARE statement FROM @v_sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    # 创建当月提货券交易记录表
    SET @v_sql = CONCAT('CREATE TABLE IF NOT EXISTS `zc_account_colorcoin_', @month_tag, '` LIKE `zc_account_record`;');
    PREPARE statement FROM @v_sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    # 创建下月提货券交易记录表
    SET @v_sql = CONCAT('CREATE TABLE IF NOT EXISTS `zc_account_colorcoin_', @nextmonth_tag, '` LIKE `zc_account_record`;');
    PREPARE statement FROM @v_sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    # 创建当月矿池交易记录表
    SET @v_sql = CONCAT('CREATE TABLE IF NOT EXISTS `zc_account_points_', @month_tag, '` LIKE `zc_account_record`;');
    PREPARE statement FROM @v_sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    # 创建下月矿池交易记录表
    SET @v_sql = CONCAT('CREATE TABLE IF NOT EXISTS `zc_account_points_', @nextmonth_tag, '` LIKE `zc_account_record`;');
    PREPARE statement FROM @v_sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    # 创建当月业绩表
    SET @v_sql = CONCAT('CREATE TABLE IF NOT EXISTS `zc_performance_', @month_tag,
                        '` LIKE `zc_performance`;');
    PREPARE statement FROM @v_sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    # 创建下月业绩表
    SET @v_sql = CONCAT('CREATE TABLE IF NOT EXISTS `zc_performance_', @nextmonth_tag,
                        '` LIKE `zc_performance`;');
    PREPARE statement FROM @v_sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;
  END
;;
DELIMITER ;

CALL TimerTask_recordtable(@error);
select @error;

-- ------------------- 存储过程.-.0109.定时创建明细表.sql end ------------------------ 


-- ------------------- 存储过程.-.0110.挖矿.sql start ------------------------ 



-- ------------------- 存储过程.-.0110.挖矿.sql end ------------------------ 


-- ------------------- 存储过程.-.0110.收益.sql start ------------------------ 

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

      # 分发销售奖
      CALL Income_consume(userId, performanceAmount, orderId, error);
      IF error
      THEN
        LEAVE out_label;
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

      # 流入矿池
      CALL Mine_add(performanceAmount, orderId, error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

    END out_label;
  END
;;
DELIMITER ;

-- ------------------- 存储过程.-.0110.收益.sql end ------------------------ 


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
-- 收益 - 累计所有收益
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

--       SET @tag = FROM_UNIXTIME(UNIX_TIMESTAMP(), '%Y%m%d');

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
DELIMITER ;;
CREATE EVENT `everyday_Release`
  ON SCHEDULE EVERY 1 DAY
    STARTS '2019-01-01 00:00:00'
  ON COMPLETION PRESERVE
  ENABLE DO BEGIN

  SET @switch = GetSetting(CONCAT('goldcoin_release_switch'));
  IF @switch = '开启'
  THEN
    START TRANSACTION;
    CALL Release_lock(@error);
    IF @error THEN
      ROLLBACK ;
    ELSE
      COMMIT ;
    END IF;
  END IF;

END
;;
DELIMITER ;

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

-- ------------------- 存储过程.-.0117.计算业绩.sql end ------------------------ 

