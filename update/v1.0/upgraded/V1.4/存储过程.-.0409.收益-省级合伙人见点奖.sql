-- -------------------------------
-- 收益 - 省级合伙人见点奖
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income_provinceServiceSee`;
DELIMITER ;;
CREATE PROCEDURE `Income_provinceServiceSee`(IN userId INT(11),
                                             IN performanceAmount DECIMAL(14, 4),
                                             IN orderId INT(11),
                                             OUT error INT(11))
BEGIN

  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
  SET error = 0;

  out_label:
  BEGIN

    SET @province = '';
    SELECT `province` INTO @province
    FROM zc_certification
    WHERE user_id = userId
    LIMIT 1;

    IF @province = ''
    THEN
      LEAVE out_label;
    END IF;

    SET @userId = 0;
    SELECT p.id INTO @userId
    FROM `zc_member` AS p
           LEFT JOIN zc_member AS c ON find_in_set(p.id, c.repath)
    WHERE p.`role` = 4
      AND p.`province` = @province
      AND c.id = userId
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
    SELECT amount INTO @goldcoinPrice
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
      IF error THEN
        LEAVE out_label;
      END IF;
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