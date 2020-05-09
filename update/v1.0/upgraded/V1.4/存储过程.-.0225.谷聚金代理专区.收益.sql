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