-- -------------------------------
-- 收益 - 累计所有收益并验证是否出局
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income_add`;
DELIMITER ;;
CREATE PROCEDURE `Income_add`(IN userId INT(11),
                              IN incomeAmount DECIMAL(14, 4),
                              OUT error INT(11))
BEGIN

  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
  SET error = 0;

  out_label:
  BEGIN

    -- 累计收益
    INSERT IGNORE INTO `zc_consume` (`user_id`) VALUE (userId);
    IF incomeAmount > 0
    THEN
      UPDATE `zc_consume`
      SET `income_amount` = `income_amount` + incomeAmount
      WHERE user_id = userId;
    END IF;

    -- 验证
    SELECT count(0) INTO @hasConsume
    FROM `zc_consume` AS c
           LEFT JOIN zc_consume_rule AS cr ON c.level = cr.level
    WHERE c.user_id = userId
      AND cr.id IS NOT NULL;
    IF @hasConsume = 0 THEN
      LEAVE out_label;
    END IF;

    -- 验证是否出局
    SET @totalConsumeAmount = 0, @level = 0, @totalIncomeAmount = 0, @isOut = 0, @miningIncomeAmount = 0, @dynamicOut = 0, @outBei = 0;
    SELECT c.amount,
           c.level,
           c.income_amount,
           c.is_out,
           ifnull(m.amount, 0),
           c.dynamic_out,
           cr.out_bei
           INTO @totalConsumeAmount, @level, @totalIncomeAmount, @isOut, @miningIncomeAmount, @dynamicOut, @outBei
    FROM `zc_consume` AS c
           LEFT JOIN zc_consume_rule AS cr ON c.level = cr.level
           LEFT JOIN zc_mining AS m ON c.user_id = m.user_id
    WHERE c.user_id = userId
      AND cr.id IS NOT NULL
      AND m.id IS NOT NULL
      AND m.tag = 0;

    IF @totalConsumeAmount = 0 AND @level = 0 AND @totalIncomeAmount = 0 AND @isOut = 0 AND @outBei = 0
    THEN
      LEAVE out_label;
    END IF;

    SET @goldcoinPrice = 1;
    SELECT amount INTO @goldcoinPrice
    FROM zc_goldcoin_prices
    ORDER BY id DESC
    LIMIT 1;

    # 验证静态收益是否出局
    IF (@totalIncomeAmount - @miningIncomeAmount) * @goldcoinPrice >= @totalConsumeAmount * @outBei
    THEN
      SET @isOut = 1;
      SELECT COUNT(0) INTO @coutLevel
      FROM zc_member AS m
      WHERE m.reid = userId
        AND m.level = 2;
      IF @coutLevel > 1
      THEN
        SET @isOut = 0;
      END IF;
    ELSE
      SET @isOut = 0;
    END IF;

    # 验证动态收益是否出局
    IF @miningIncomeAmount * @goldcoinPrice >= @totalConsumeAmount * @outBei THEN
      SET @dynamicOut = 1;
    ELSE
      SET @dynamicOut = 0;
    END IF;

    # 更新出局状态
    UPDATE `zc_consume`
    SET `is_out`    = @isOut,
        dynamic_out = @dynamicOut,
        uptime      = unix_timestamp()
    WHERE user_id = userId;

  END out_label;
END
;;
DELIMITER ;