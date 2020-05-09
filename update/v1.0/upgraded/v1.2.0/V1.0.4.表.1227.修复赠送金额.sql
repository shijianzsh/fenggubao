-- -------------------------------
-- 补发赠送
-- -------------------------------
DROP PROCEDURE IF EXISTS `Fix_give`;
DELIMITER ;;
CREATE PROCEDURE `Fix_give`(OUT error INT(11))
BEGIN

  DECLARE done INT DEFAULT 0;
  DECLARE c_user_id INT DEFAULT 0;
  DECLARE c_user_level INT DEFAULT 0;
  DECLARE c_user_loginnamne VARCHAR(11) DEFAULT '';
  DECLARE c_user_truename VARCHAR(50) DEFAULT '';

  # 所有用户
  DECLARE c_user CURSOR FOR
    SELECT m.id, m.`level`, m.loginname, m.truename
    FROM zc_member AS m
           LEFT JOIN zc_consume AS c ON m.id = c.user_id
    WHERE c.amount > 0
      AND c.id IS NOT NULL;

  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; -- 异常错误
  SET error = 0;
  SET @totalAmount = 0;
  out_label:
  BEGIN

    OPEN c_user;
    REPEAT
      FETCH c_user
        INTO c_user_id, c_user_level, c_user_loginnamne, c_user_truename;
      IF NOT done
      THEN
        BEGIN

          SELECT sum(amount) INTO @consumeAmount
          FROM zc_orders
          WHERE uid = c_user_id
            AND order_status NOT IN (0, 2)
            AND pay_time > 0
            AND pay_time <= UNIX_TIMESTAMP('2018-12-27 14:00:00');

          SELECT sum(record_amount) / 0.2 INTO @giveAmount
          FROM zc_account_goldcoin_201812
          WHERE record_action = 103
            AND user_id = c_user_id;

          SET @giveIncomeAmount = 0;
          IF @consumeAmount * 2 > @giveAmount THEN

            SET @giveIncomeAmount = @consumeAmount * 2 - @giveAmount;

            -- 赠送流通资产
            CALL AddAccountRecord(c_user_id, 'goldcoin', 103, @giveIncomeAmount * 20 * 0.01, UNIX_TIMESTAMP(),
                                  concat('{"order_id":"', 0, '"}'), 'xfzs', 2, error);
            IF error
            THEN
              LEAVE out_label;
            END IF;

            -- 赠送锁定资产
            CALL AddLockGoldcoin(c_user_id, @giveIncomeAmount * 80 * 0.01, error);
            IF error
            THEN
              LEAVE out_label;
            END IF;

          END IF;

          IF @consumeAmount > 100 AND c_user_level = 1 THEN
            UPDATE zc_member
            SET `level`   = 2,
                open_time = unix_timestamp()
            WHERE id = c_user_id;
          END IF;
          SET @totalAmount = @totalAmount + @giveIncomeAmount;
        END;
      END IF;
    UNTIL done END REPEAT;
    CLOSE c_user;
  END out_label;
  SELECT @totalAmount;
END
;;
DELIMITER ;


CALL Fix_give(@error);

SELECT @error;

