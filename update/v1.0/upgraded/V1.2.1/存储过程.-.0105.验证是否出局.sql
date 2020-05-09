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

    -- 验证是否出局
    SET @totalConsumeAmount = 0, @level = 0, @totalIncomeAmount = 0, @isOut = 0, @outBei = 0;
    SELECT c.amount,
           c.level,
           c.income_amount,
           c.is_out,
           cr.out_bei
           INTO @totalConsumeAmount, @level, @totalIncomeAmount, @isOut, @outBei
    FROM `zc_consume` AS c
           LEFT JOIN
         zc_consume_rule AS cr ON c.level = cr.level
    WHERE c.user_id = userId
      AND cr.id IS NOT NULL;

    IF @totalConsumeAmount = 0 AND @level = 0 AND @totalIncomeAmount = 0 AND @isOut = 0 AND @outBei = 0
    THEN
      LEAVE out_label;
    END IF;

    SET @goldcoinPrice = 1;
    SELECT amount INTO @goldcoinPrice
    FROM zc_goldcoin_prices
    ORDER BY id DESC
    LIMIT 1;

    IF @totalIncomeAmount * @goldcoinPrice >= @totalConsumeAmount * @outBei
    THEN
      SET @isOut = 1;
    ELSE
      SET @isOut = 0;
    END IF;

    IF @isOut = 1
    THEN
      SELECT COUNT(0) INTO @coutLevel5
      FROM zc_consume AS c LEFT JOIN zc_member AS m ON c.user_id = m.id
      WHERE m.reid = userId
        AND c.amount >= @totalConsumeAmount * 2;
      IF @coutLevel5 > 1
      THEN
        SET @isOut = 0;
      END IF;
    END IF;

    UPDATE `zc_consume`
    SET `is_out` = @isOut,
        uptime   = unix_timestamp()
    WHERE user_id = userId;

  END out_label;
END
;;
DELIMITER ;