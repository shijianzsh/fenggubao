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