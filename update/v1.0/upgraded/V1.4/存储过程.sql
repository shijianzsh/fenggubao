
-- ------------------- 存储过程.-.0225.谷聚金代理专区.业绩累计.sql start ------------------------ 

-- -------------------------------
-- 谷聚金代理专区 - 业绩累计
-- -------------------------------
DROP PROCEDURE IF EXISTS `Gjj_Performance_calculation`;
DELIMITER ;;
CREATE PROCEDURE `Gjj_Performance_calculation`(IN userId INT(11), IN role TINYINT(1), OUT error INT(11))
BEGIN

  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
  SET error = 0;

  out_label:
  BEGIN
    -- 获取代理费
    SET @agentFee = GetSetting(concat('gjj_agent_fee_', role));
    IF @agentFee <= 0 THEN
      LEAVE out_label;
    END IF;

    -- 获取PV值比例
    SET @pvBai = GetSetting('gjj_recommend_pv_bai');
    IF @pvBai <= 0 THEN
      LEAVE out_label;
    END IF;

    SELECT count(0) INTO @hasRecommend
    FROM zc_member AS m
           LEFT JOIN zc_member AS p ON p.id = m.reid
    WHERE m.id = userId
      AND m.is_lock = 0
      AND p.level = 2
      AND p.is_lock = 0
    LIMIT 1;
    IF @hasRecommend = 0 THEN
      LEAVE out_label;
    END IF;

    SELECT p.id INTO @userId
    FROM zc_member AS m
           LEFT JOIN zc_member AS p ON p.id = m.reid
    WHERE m.id = userId
      AND m.is_lock = 0
      AND p.level = 2
      AND p.is_lock = 0
    LIMIT 1;

    SET @performanceAmount = @agentFee * @pvBai * 0.01;

    -- 加入业绩统计队列
    INSERT IGNORE INTO zc_performance_queue (user_id, performance_amount, order_id, queue_status, queue_addtime, queue_starttime, queue_endtime)
    VALUES (@userId, @performanceAmount, 0, 0, unix_timestamp(), unix_timestamp(), unix_timestamp());

  END out_label;
END
;;
DELIMITER ;

-- ------------------- 存储过程.-.0225.谷聚金代理专区.业绩累计.sql end ------------------------ 


-- ------------------- 存储过程.-.0225.谷聚金代理专区.事件-支付完成事件.sql start ------------------------ 

-- -------------------------------
-- 支付完成事件
-- -------------------------------
DROP PROCEDURE IF EXISTS `Event_paid`;
DELIMITER ;;
CREATE PROCEDURE `Event_paid`(IN orderId INT(11), OUT error INT(11))
BEGIN

  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
  SET error = 0;

  out_label:
  BEGIN

    SET @orderStatus = 0;
    SELECT order_status INTO @orderStatus FROM zc_orders WHERE id = orderId;
    IF @orderStatus <> 1 THEN
      LEAVE out_label;
    END IF;

    SELECT count(0) INTO @isGjj
    FROM zc_order_product AS op
           LEFT JOIN zc_product_affiliate AS pa ON op.product_id = pa.product_id
    WHERE op.order_id = orderId
      AND pa.block_id = 7
    LIMIT 1;

    IF @isGjj > 0 THEN
      -- 分发谷聚金代理专区收益
      CALL Gjj_Income(orderId, error);
      IF error THEN
        LEAVE out_label;
      END IF;
    ELSE
      -- 激活个人代理
      CALL Event_activated(orderId, error);
      IF error THEN
        LEAVE out_label;
      END IF;
      -- 累计业绩
      CALL Performance_calculation(orderId, error);
      IF error THEN
        LEAVE out_label;
      END IF;
    END IF;

  END out_label;
END
;;
DELIMITER ;

-- ------------------- 存储过程.-.0225.谷聚金代理专区.事件-支付完成事件.sql end ------------------------ 


-- ------------------- 存储过程.-.0225.谷聚金代理专区.事件-激活合伙人.sql start ------------------------ 

-- -------------------------------
-- 谷聚金代理专区 - 事件 - 激活合伙人身份
-- -------------------------------
DROP PROCEDURE IF EXISTS `Gjj_Event_activated`;
DELIMITER ;;
CREATE PROCEDURE `Gjj_Event_activated`(IN userId INT(11), IN role TINYINT(1), OUT error INT(11))
BEGIN

  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
  SET error = 0;

  out_label:
  BEGIN

    -- 赠送谷聚金
    CALL Gjj_Income_give(userId, role, error);
    IF error THEN
      LEAVE out_label;
    END IF;

    -- 分发招商补贴
    CALL Gjj_Income_subsidy(userId, role, error);
    IF error THEN
      LEAVE out_label;
    END IF;

    -- 累计业绩
    CALL Gjj_Performance_calculation(userId, role, error);
    IF error THEN
      LEAVE out_label;
    END IF;

  END out_label;
END
;;
DELIMITER ;




-- ------------------- 存储过程.-.0225.谷聚金代理专区.事件-激活合伙人.sql end ------------------------ 


-- ------------------- 存储过程.-.0225.谷聚金代理专区.收益.sql start ------------------------ 

-- -------------------------------
-- 获取公让宝最新价格
-- -------------------------------
DROP FUNCTION IF EXISTS `GetGoldcoinLatestPrice`;
DELIMITER ;;
CREATE FUNCTION `GetGoldcoinLatestPrice`()
  RETURNS DECIMAL(14, 4)
BEGIN

  SET @goldcoinPrice = 0;

  SELECT count(0) INTO @hasPrice
  FROM zc_goldcoin_prices
  LIMIT 1;

  IF @hasPrice > 0 THEN
    SELECT amount INTO @goldcoinPrice
    FROM zc_goldcoin_prices
    ORDER BY id DESC
    LIMIT 1;
  END IF;

  RETURN @goldcoinPrice;
END
;;
DELIMITER ;


-- -------------------------------
-- 谷聚金收益 - 收益
-- -------------------------------
DROP PROCEDURE IF EXISTS `Gjj_Income`;
DELIMITER ;;
CREATE PROCEDURE `Gjj_Income`(IN orderId INT(11), OUT error INT(11))
BEGIN

  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
  SET error = 0;

  out_label:
  BEGIN

    -- 统计谷聚金代理区产品数量
    SELECT IFNULL(sum(op.product_quantity), 0) INTO
      @quantity
    FROM zc_orders AS o
           LEFT JOIN zc_order_product AS op ON o.id = op.order_id
           LEFT JOIN zc_product_affiliate AS pa ON op.product_id = pa.product_id
    WHERE o.order_status IN (1, 3, 4)
      AND o.amount_type IN (9)
      AND o.id = orderId
      AND pa.block_id = 7
    LIMIT 1;

    IF @quantity = 0 THEN
      LEAVE out_label;
    END IF;

    SELECT uid INTO @userId
    FROM zc_orders AS o
    WHERE o.id = orderId
    LIMIT 1;

    SELECT count(0) INTO @hasCounty
    FROM `zc_gjj_roles` AS ro
           LEFT JOIN zc_member AS m ON ro.user_id = m.id
    WHERE ro.audit_status = 1
      AND ro.enabled = 1
      AND ro.role IN (2)
      AND m.is_lock = 0
      AND ro.user_id = @userId;
    IF @hasCounty = 0 THEN
      LEAVE out_label;
    END IF;

    SELECT ro.region,
           ro.province,
           ro.city,
           ro.country
           INTO @region,@province,@city,@county
    FROM `zc_gjj_roles` AS ro
    WHERE ro.role IN (2)
      AND ro.user_id = @userId
    ORDER BY id ASC
    LIMIT 1;

    -- 分发大中华区重复消费奖
    CALL Gjj_Income_Consume_china(@province, @quantity, orderId, error);
    IF error THEN
      LEAVE out_label;
    END IF;

    -- 分发省营运中心重复消费奖
    CALL Gjj_Income_Consume_province(@province, @quantity, orderId, error);
    IF error THEN
      LEAVE out_label;
    END IF;

    -- 分发直推人重复消费奖
    CALL Gjj_Income_Consume_recommend(@userId, @quantity, orderId, error);
    IF error THEN
      LEAVE out_label;
    END IF;

  END out_label;
END
;;
DELIMITER ;


-- -------------------------------
-- 谷聚金收益 - 分发大中华区重复消费奖
-- -------------------------------
DROP PROCEDURE IF EXISTS `Gjj_Income_Consume_china`;
DELIMITER ;;
CREATE PROCEDURE `Gjj_Income_Consume_china`(IN province VARCHAR(50), IN quantity INT(11), IN orderId INT(11), OUT error INT(11))
BEGIN

  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
  SET error = 0;

  out_label:
  BEGIN

    -- 检查是否有大中华区合伙人
    SELECT count(0) INTO @hasChina
    FROM `zc_gjj_roles` AS ro
           LEFT JOIN `zc_gjj_regions` AS re ON ro.region = re.name
           LEFT JOIN zc_member AS m ON ro.user_id = m.id
    WHERE ro.audit_status = 1
      AND ro.enabled = 1
      AND ro.role IN (5)
      AND re.province = province
      AND m.is_lock = 0
    LIMIT 1;
    IF @hasChina = 0 THEN
      LEAVE out_label;
    END IF;

    SET @chinaConsume = GetSetting('gjj_agent_consume_5');
    SET @goldcoinPrice = GetGoldcoinLatestPrice();
    SET @chinaConsumeIncomeAmount = @chinaConsume * quantity / @goldcoinPrice;
    IF @chinaConsumeIncomeAmount <= 0 THEN
      LEAVE out_label;
    END IF;

    SELECT ro.user_id INTO @userId
    FROM `zc_gjj_roles` AS ro
           LEFT JOIN `zc_gjj_regions` AS re ON ro.region = re.name
    WHERE ro.audit_status = 1
      AND ro.enabled = 1
      AND ro.role IN (5)
      AND re.province = province
    LIMIT 1;

    -- 添加明细
    CALL AddAccountRecord(@userId, 'goldcoin', 131, @chinaConsumeIncomeAmount, UNIX_TIMESTAMP(), concat('{"order_id":"', orderId, '"}'), '谷聚金-重复消费奖（大中华区合伙人）', @chinaConsume,
                          error);
    IF error THEN
      LEAVE out_label;
    END IF;

  END out_label;
END
;;
DELIMITER ;

-- -------------------------------
-- 谷聚金收益 - 分发省营运中心重复消费奖
-- -------------------------------
DROP PROCEDURE IF EXISTS `Gjj_Income_Consume_province`;
DELIMITER ;;
CREATE PROCEDURE `Gjj_Income_Consume_province`(IN province VARCHAR(50), IN quantity INT(11), IN orderId INT(11), OUT error INT(11))
BEGIN

  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
  SET error = 0;

  out_label:
  BEGIN

    -- 检查是否有省营运中心合伙人
    SELECT count(0) INTO @hasProvince
    FROM `zc_gjj_roles` AS ro
           LEFT JOIN zc_member AS m ON ro.user_id = m.id
    WHERE ro.audit_status = 1
      AND ro.enabled = 1
      AND ro.role IN (4)
      AND ro.province = province
      AND m.is_lock = 0
    LIMIT 1;
    IF @hasProvince = 0 THEN
      LEAVE out_label;
    END IF;

    SET @provinceConsume = GetSetting('gjj_agent_consume_4');
    SET @goldcoinPrice = GetGoldcoinLatestPrice();
    SET @provinceConsumeIncomeAmount = @provinceConsume * quantity / @goldcoinPrice;
    IF @provinceConsumeIncomeAmount <= 0 THEN
      LEAVE out_label;
    END IF;

    SELECT ro.user_id INTO @userId
    FROM `zc_gjj_roles` AS ro
    WHERE ro.audit_status = 1
      AND ro.enabled = 1
      AND ro.role IN (4)
      AND ro.province = province
    LIMIT 1;

    -- 添加明细
    CALL AddAccountRecord(@userId, 'goldcoin', 131, @provinceConsumeIncomeAmount, UNIX_TIMESTAMP(), concat('{"order_id":"', orderId, '"}'), '谷聚金-重复消费奖（省营运中心合伙人）', @provinceConsume,
                          error);
    IF error THEN
      LEAVE out_label;
    END IF;

  END out_label;
END
;;
DELIMITER ;


-- -------------------------------
-- 谷聚金收益 - 直推人合伙人重复消费奖
-- -------------------------------
DROP PROCEDURE IF EXISTS `Gjj_Income_Consume_recommend`;
DELIMITER ;;
CREATE PROCEDURE `Gjj_Income_Consume_recommend`(IN userId INT(11), IN quantity INT(11), IN orderId INT(11), OUT error INT(11))
BEGIN

  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
  SET error = 0;

  out_label:
  BEGIN

    SELECT count(0) INTO @hasRecommend
    FROM `zc_gjj_roles` AS ro
           LEFT JOIN zc_member AS m ON ro.user_id = m.id
           LEFT JOIN zc_member AS p ON p.id = m.reid
    WHERE ro.audit_status = 1
      AND ro.enabled = 1
      AND ro.role IN (2)
      AND m.id = userId
      AND m.is_lock = 0
      AND p.level = 2
      AND p.is_lock = 0;
    IF @hasRecommend = 0 THEN
      LEAVE out_label;
    END IF;

    SET @recommendConsume = GetSetting('gjj_recommend_county_consume');
    SET @goldcoinPrice = GetGoldcoinLatestPrice();
    SET @recommendConsumeIncomeAmount = @recommendConsume * quantity / @goldcoinPrice;
    IF @recommendConsumeIncomeAmount <= 0 THEN
      LEAVE out_label;
    END IF;

    SELECT user_id INTO @userId
    FROM `zc_gjj_roles` AS ro
           LEFT JOIN zc_member AS m ON ro.user_id = m.id
           LEFT JOIN zc_member AS p ON p.id = m.reid
    WHERE ro.audit_status = 1
      AND ro.enabled = 1
      AND ro.role IN (2)
      AND m.id = userId
      AND m.is_lock = 0
      AND p.level = 2
      AND p.is_lock = 0
    ORDER BY ro.id ASC
    LIMIT 1;

    -- 添加明细
    CALL AddAccountRecord(@userId, 'goldcoin', 131, @recommendConsumeIncomeAmount, UNIX_TIMESTAMP(), concat('{"order_id":"', orderId, '"}'), '谷聚金-重复消费奖（直推人）', @recommendConsume,
                          error);
    IF error THEN
      LEAVE out_label;
    END IF;

  END out_label;
END
;;
DELIMITER ;

-- ------------------- 存储过程.-.0225.谷聚金代理专区.收益.sql end ------------------------ 


-- ------------------- 存储过程.-.0225.谷聚金代理专区.收益.招商补贴.sql start ------------------------ 

-- -------------------------------
-- 谷聚金收益 - 分发招商补贴
-- -------------------------------
DROP PROCEDURE IF EXISTS `Gjj_Income_subsidy`;
DELIMITER ;;
CREATE PROCEDURE `Gjj_Income_subsidy`(IN userId INT(11), IN role TINYINT(1), OUT error INT(11))
BEGIN

  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
  SET error = 0;

  out_label:
  BEGIN
    -- 只有开通区县合伙人，才分发招商补贴
    IF role <> 2 THEN
      LEAVE out_label;
    END IF;

    -- 验证是否开通了区县合伙人
    SELECT count(0) INTO @hasCounty
    FROM `zc_gjj_roles` AS ro
           LEFT JOIN zc_member AS m ON ro.user_id = m.id
    WHERE ro.audit_status = 1
      AND ro.enabled = 1
      AND ro.role IN (2)
      AND m.is_lock = 0
      AND ro.user_id = userId;
    IF @hasCounty = 0 THEN
      LEAVE out_label;
    END IF;


    -- 获取所属区域
    SELECT ro.region,
           ro.province,
           ro.city,
           ro.country
           INTO @region,@province,@city,@county
    FROM `zc_gjj_roles` AS ro
    WHERE ro.role IN (role)
      AND ro.user_id = userId
    ORDER BY id ASC
    LIMIT 1;

    -- 分发大中华区招商补贴
    CALL Gjj_Income_subsidy_china(@province, role, userId, error);
    IF error THEN
      LEAVE out_label;
    END IF;

    -- 分发省营运中心招商补贴
    CALL Gjj_Income_subsidy_province(@province, role, userId, error);
    IF error THEN
      LEAVE out_label;
    END IF;


  END out_label;
END
;;
DELIMITER ;


-- -------------------------------
-- 谷聚金收益 - 分发大中华区招商补贴
-- -------------------------------
DROP PROCEDURE IF EXISTS `Gjj_Income_subsidy_china`;
DELIMITER ;;
CREATE PROCEDURE `Gjj_Income_subsidy_china`(IN province VARCHAR(50), IN role INT(11), IN userId INT(11), OUT error INT(11))
BEGIN

  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
  SET error = 0;

  out_label:
  BEGIN

    -- 检查是否有大中华区合伙人
    SELECT count(0) INTO @hasChina
    FROM `zc_gjj_roles` AS ro
           LEFT JOIN `zc_gjj_regions` AS re ON ro.region = re.name
           LEFT JOIN zc_member AS m ON ro.user_id = m.id
    WHERE ro.audit_status = 1
      AND ro.enabled = 1
      AND ro.role IN (5)
      AND re.province = province
      AND m.is_lock = 0;
    IF @hasChina = 0 THEN
      LEAVE out_label;
    END IF;

    -- 获取代理费
    SET @agentFee = GetSetting(concat('gjj_agent_fee_', role));
    IF @agentFee <= 0 THEN
      LEAVE out_label;
    END IF;

    SET @subsidyBai = GetSetting('gjj_agent_subsidy_bai_5');
    IF @subsidyBai <= 0 THEN
      LEAVE out_label;
    END IF;

    SET @goldcoinPrice = GetGoldcoinLatestPrice();
    SET @chinaSubsidyIncomeAmount = @agentFee * @subsidyBai * 0.01 / @goldcoinPrice;
    IF @chinaSubsidyIncomeAmount <= 0 THEN
      LEAVE out_label;
    END IF;

    SELECT ro.user_id INTO @userId
    FROM `zc_gjj_roles` AS ro
           LEFT JOIN `zc_gjj_regions` AS re ON ro.region = re.name
    WHERE ro.audit_status = 1
      AND ro.enabled = 1
      AND ro.role IN (5)
      AND re.province = province
    LIMIT 1;

    -- 添加明细
    CALL AddAccountRecord(@userId, 'goldcoin', 130, @chinaSubsidyIncomeAmount, UNIX_TIMESTAMP(), concat('{"user_id":"', userId, '"}'), '谷聚金-大中华区合伙人招商补贴', @subsidyBai,
                          error);
    IF error THEN
      LEAVE out_label;
    END IF;

  END out_label;
END
;;
DELIMITER ;


-- -------------------------------
-- 谷聚金收益 - 省营运中心合伙人招商补贴
-- -------------------------------
DROP PROCEDURE IF EXISTS `Gjj_Income_subsidy_province`;
DELIMITER ;;
CREATE PROCEDURE `Gjj_Income_subsidy_province`(IN province VARCHAR(50), IN role INT(11), IN userId INT(11), OUT error INT(11))
BEGIN

  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
  SET error = 0;

  out_label:
  BEGIN

    -- 检查是否有省营运中心合伙人
    SELECT count(0) INTO @hasProvince
    FROM `zc_gjj_roles` AS ro
           LEFT JOIN zc_member AS m ON ro.user_id = m.id
    WHERE ro.audit_status = 1
      AND ro.enabled = 1
      AND ro.role IN (4)
      AND ro.province = province
      AND m.is_lock = 0;
    IF @hasProvince = 0 THEN
      LEAVE out_label;
    END IF;

    -- 获取代理费
    SET @agentFee = GetSetting(concat('gjj_agent_fee_', role));
    IF @agentFee <= 0 THEN
      LEAVE out_label;
    END IF;

    SET @subsidyBai = GetSetting('gjj_agent_subsidy_bai_4');
    IF @subsidyBai <= 0 THEN
      LEAVE out_label;
    END IF;

    SET @goldcoinPrice = GetGoldcoinLatestPrice();
    SET @provinceSubsidyIncomeAmount = @agentFee * @subsidyBai * 0.01 / @goldcoinPrice;
    IF @provinceSubsidyIncomeAmount <= 0 THEN
      LEAVE out_label;
    END IF;

    -- 获取是否有省营运中心合伙人ID
    SELECT ro.user_id INTO @userId
    FROM `zc_gjj_roles` AS ro
    WHERE ro.audit_status = 1
      AND ro.enabled = 1
      AND ro.role IN (4)
      AND ro.province = province
    LIMIT 1;

    -- 添加明细
    CALL AddAccountRecord(@userId, 'goldcoin', 130, @provinceSubsidyIncomeAmount, UNIX_TIMESTAMP(), concat('{"user_id":"', userId, '"}'), '谷聚金-省营运中心招商补贴', @subsidyBai,
                          error);
    IF error THEN
      LEAVE out_label;
    END IF;

  END out_label;
END
;;
DELIMITER ;

-- ------------------- 存储过程.-.0225.谷聚金代理专区.收益.招商补贴.sql end ------------------------ 


-- ------------------- 存储过程.-.0225.谷聚金代理专区.收益.激活赠送谷聚金.sql start ------------------------ 

-- -------------------------------
-- 谷聚金收益 - 激活合伙人赠送
-- -------------------------------
DROP PROCEDURE IF EXISTS `Gjj_Income_give`;
DELIMITER ;;
CREATE PROCEDURE `Gjj_Income_give`(IN userId INT(11), IN role TINYINT(1), OUT error INT(11))
BEGIN

  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
  SET error = 0;

  out_label:
  BEGIN

    -- 获取赠送数量
    SET @giveAmount = GetSetting(concat('gjj_agent_give_', role));
    IF @giveAmount <= 0 THEN
      LEAVE out_label;
    END IF;

    -- 添加明细
    CALL AddAccountRecord(userId, 'colorcoin', 407, @giveAmount, UNIX_TIMESTAMP(), concat('{"role":"', role, '"}'), '谷聚金-激活合伙人赠送', 0, error);
    IF error THEN
      LEAVE out_label;
    END IF;

  END out_label;
END
;;
DELIMITER ;


-- ------------------- 存储过程.-.0225.谷聚金代理专区.收益.激活赠送谷聚金.sql end ------------------------ 


-- ------------------- 存储过程.-.0226.定时创建明细表.sql start ------------------------ 

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

    # 创建当月兑换券交易记录表
    SET @v_sql = CONCAT('CREATE TABLE IF NOT EXISTS `zc_account_enroll_', @month_tag, '` LIKE `zc_account_record`;');
    PREPARE statement FROM @v_sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    # 创建下月兑换券交易记录表
    SET @v_sql = CONCAT('CREATE TABLE IF NOT EXISTS `zc_account_enroll_', @nextmonth_tag, '` LIKE `zc_account_record`;');
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

-- ------------------- 存储过程.-.0226.定时创建明细表.sql end ------------------------ 


-- ------------------- 存储过程.-.0308.事件.激活.sql start ------------------------ 

-- -------------------------------
-- 个人代理激活
-- -------------------------------
DROP PROCEDURE IF EXISTS `Event_activated`;
DELIMITER ;;
CREATE PROCEDURE `Event_activated`(IN orderId INT(11), OUT error INT(11))
BEGIN

  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
  SET error = 0;

  out_label:
  BEGIN

    SET @performanceAmount = 0;
    SELECT o.uid,
           sum(op.price_cash * op.product_quantity * op.performance_bai_cash * 0.01 * if(ifnull(o.`discount`, 0) = 0, 10, o.`discount`) * 0.1)
           INTO
             @userId,
             @performanceAmount
    FROM zc_orders AS o
           LEFT JOIN zc_order_product AS op ON o.id = op.order_id
           LEFT JOIN zc_member AS m ON o.uid = m.id
    WHERE o.id = orderId
      AND order_status IN (1, 3, 4);

    IF @performanceAmount <= 0
    THEN
      LEAVE out_label;
    END IF;

    SET @performancePortionBase = GetSetting(concat('performance_portion_base'));
    SET @userLevel = 0;
    SELECT `level` INTO @userLevel
    FROM zc_member
    WHERE id = @userId
      AND is_lock = 0;
    IF @userLevel = 0
    THEN
      LEAVE out_label;
    END IF;

    SET @ordersPerformanceAmount = 0;
    IF @userLevel = 1
    THEN
      SELECT IFNULL(sum(op.price_cash * op.product_quantity * op.performance_bai_cash * 0.01 * if(ifnull(o.`discount`, 0) = 0, 10, o.`discount`) * 0.1), 0) INTO
        @ordersPerformanceAmount
      FROM zc_orders AS o
             LEFT JOIN zc_order_product AS op ON o.id = op.order_id
             LEFT JOIN zc_member AS m ON o.uid = m.id
      WHERE o.order_status IN (1, 3, 4)
        AND o.uid = @userId;

      IF (@ordersPerformanceAmount >= @performancePortionBase)
      THEN
        CALL Income_agentGive(@userId, @ordersPerformanceAmount, orderId, error);
        IF error
        THEN
          LEAVE out_label;
        END IF;
      END IF;
    ELSEIF @userLevel = 2
    THEN
      CALL Income_agentGive(@userId, @performanceAmount, orderId, error);
      IF error
      THEN
        LEAVE out_label;
      END IF;
    END IF;

    IF @performanceAmount < @performancePortionBase AND @ordersPerformanceAmount < @performancePortionBase
    THEN
      LEAVE out_label;
    END IF;

    IF @userLevel <= 1
    THEN
      -- 激活个人代理
      UPDATE zc_member
      SET `level`   = 2,
          open_time = unix_timestamp()
      WHERE id = @userId;
    END IF;

  END out_label;
END
;;
DELIMITER ;



-- ------------------- 存储过程.-.0308.事件.激活.sql end ------------------------ 


-- ------------------- 存储过程.-.0308.收益-钻石经销商补贴.sql start ------------------------ 

-- -------------------------------
-- 收益 - 钻石经销商补贴
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income_subsidy_5`;
DELIMITER ;;
CREATE PROCEDURE `Income_subsidy_5`(IN userId INT(11),
                                    IN performanceAmount DECIMAL(14, 4),
                                    IN orderId INT(11),
                                    OUT error INT(11))
BEGIN
  DECLARE done INT DEFAULT 0;
  DECLARE c_user_id INT DEFAULT 0;

  # 获取上二级个人代理
  DECLARE c_user CURSOR FOR
    SELECT p.id
    FROM zc_member AS m
           LEFT JOIN zc_member AS p ON find_in_set(p.id, m.repath)
           LEFT JOIN zc_consume AS c ON p.id = c.user_id
    WHERE m.id = userId
      AND p.level = 2
      AND p.is_lock = 0
      AND c.is_out = 0
      AND c.level = 5
    ORDER BY p.relevel DESC
    LIMIT 2;

  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; -- 异常错误
  SET error = 0;

  SET @userLevel = 0;

  out_label:
  BEGIN

    OPEN c_user;
    REPEAT
      FETCH c_user
        INTO c_user_id;
      IF NOT done
      THEN
        BEGIN
          SET @userLevel = @userLevel + 1;

          SET @subsidyCashBai = GetSetting(CONCAT('subsidy_level_5_cash_bai_', @userLevel));
          IF @subsidyCashBai <= 0
          THEN
            LEAVE out_label;
          END IF;

          -- 添加明细
          CALL AddAccountRecord(c_user_id, 'cash', 315, performanceAmount * @subsidyCashBai * 0.01, UNIX_TIMESTAMP(),
                                concat('{"order_id":"', orderId, '"}'), '钻石经销商补贴', @subsidyCashBai, error);
          IF error THEN
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

-- ------------------- 存储过程.-.0308.收益-钻石经销商补贴.sql end ------------------------ 


-- ------------------- 存储过程.-.0308.收益-销售奖.sql start ------------------------ 

-- -------------------------------
-- 收益 - 销售奖
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income_consume`;
DELIMITER ;;
CREATE PROCEDURE `Income_consume`(IN userId INT(11),
                                  IN performanceAmount DECIMAL(14, 4),
                                  IN orderId INT(11),
                                  OUT error INT(11))
BEGIN
  DECLARE done INT DEFAULT 0;
  DECLARE c_user_id INT DEFAULT 0;

  # 获取上二级个人代理
  DECLARE c_user CURSOR FOR
    SELECT p.id
    FROM zc_member AS m
           LEFT JOIN zc_member AS p ON find_in_set(p.id, m.repath)
           LEFT JOIN zc_consume AS c ON p.id = c.user_id
    WHERE m.id = userId
      AND p.level = 2
      AND p.is_lock = 0
      AND c.is_out = 0
    ORDER BY p.relevel DESC
    LIMIT 2;

  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; -- 异常错误
  SET error = 0;

  SET @userLevel = 0;

  out_label:
  BEGIN

    OPEN c_user;
    REPEAT
      FETCH c_user
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
          SELECT amount INTO @goldcoinPrice
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
            IF error THEN
              LEAVE out_label;
            END IF;
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

          SET @consumeCashBai = GetSetting(CONCAT('prize_agent_consume_cash_bai_', @userLevel));
          IF @consumeCashBai > 0
          THEN
            -- 添加明细
            CALL AddAccountRecord(c_user_id, 'cash', 314, performanceAmount * @consumeCashBai * 0.01, UNIX_TIMESTAMP(),
                                  concat('{"order_id":"', orderId, '"}'), '代理商销售奖', @consumeCashBai, error);
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

-- ------------------- 存储过程.-.0308.收益-销售奖.sql end ------------------------ 


-- ------------------- 存储过程.-.0308.计算业绩.sql start ------------------------ 

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
           sum(op.price_cash * op.product_quantity * op.performance_bai_cash * 0.01 * if(ifnull(o.`discount`, 0) = 0, 10, o.`discount`) * 0.1),
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


-- ------------------- 存储过程.-.0308.计算业绩.sql end ------------------------ 


-- ------------------- 存储过程.-.0309.收益.sql start ------------------------ 

-- -------------------------------
-- 收益
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income`;
DELIMITER ;;
CREATE PROCEDURE `Income`(IN userId INT(11),
                          IN performanceAmount DECIMAL(14, 4),
                          IN orderId INT(11),
                          OUT error INT(11))
BEGIN

  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
  SET error = 0;

  out_label:
  BEGIN

    # 流入矿池
    CALL Mine_add(performanceAmount, orderId, error);
    IF error
    THEN
      LEAVE out_label;
    END IF;

    # 分发销售奖
    CALL Income_consume(userId, performanceAmount, orderId, error);
    IF error
    THEN
      LEAVE out_label;
    END IF;

    # 分发钻石经销商补贴
    CALL Income_subsidy_5(userId, performanceAmount, orderId, error);
    IF error
    THEN
      LEAVE out_label;
    END IF;

    # 特殊身份补贴
    CALL Income_special(userId, performanceAmount, orderId, error);
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

  END out_label;
END
;;
DELIMITER ;

-- ------------------- 存储过程.-.0309.收益.sql end ------------------------ 


-- ------------------- 存储过程.-.0315.挖矿.sql start ------------------------ 

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


-- ------------------- 存储过程.-.0315.挖矿.sql end ------------------------ 

