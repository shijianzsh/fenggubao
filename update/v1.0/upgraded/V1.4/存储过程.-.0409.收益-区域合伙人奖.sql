-- -------------------------------
-- 收益 - 区域合伙人奖
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income_countyService`;
DELIMITER ;;
CREATE PROCEDURE `Income_countyService`(IN userId INT(11),
                                        IN performanceAmount DECIMAL(14, 4),
                                        IN orderId INT(11),
                                        OUT error INT(11))
BEGIN

  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
  SET error = 0;

  out_label:
  BEGIN

    SET @province = '', @city = '', @country = '';
    SELECT `province`,
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
    SELECT p.id INTO @userId
    FROM `zc_member` AS p
           LEFT JOIN zc_member AS c ON find_in_set(p.id, c.repath)
    WHERE p.`role` = 3
      AND p.`province` = @province
      AND p.`city` = @city
      AND p.`country` = @country
      AND c.id = userId
    LIMIT 1;
    IF @userId <= 0
    THEN
      LEAVE out_label;
    END IF;

    -- 验证特殊体系（禁止分发特殊体系外的代理费）
    SET @specialDisableRoleIncome = GetSetting(concat('special_disable_role_income'));
    IF @specialDisableRoleIncome
    THEN
      SELECT count(0) INTO @hasDisable
      FROM zc_member AS pm
             LEFT JOIN zc_member AS m ON find_in_set(pm.id, m.repath)
      WHERE m.id = @userId
        AND find_in_set(pm.loginname, @specialDisableRoleIncome);
      IF @hasDisable > 0 THEN

        SELECT count(0) INTO @isChild
        FROM zc_member AS pm
               LEFT JOIN zc_member AS m ON find_in_set(pm.id, m.repath)
        WHERE m.id = userId
          AND find_in_set(pm.loginname, @specialDisableRoleIncome);

        IF @isChild = 0 THEN
          LEAVE out_label;
        END IF;
      END IF;
    END IF;

    SET @incomeBai = GetSetting(CONCAT('prize_county_service_bai'));
    IF @incomeBai <= 0
    THEN
      LEAVE out_label;
    END IF;

    -- 获取公让宝最新价格
    SET @goldcoinPrice = 1;
    SELECT amount INTO @goldcoinPrice
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
      IF error THEN
        LEAVE out_label;
      END IF;
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