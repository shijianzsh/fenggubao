-- -------------------------------
-- 补发管理津贴
-- -------------------------------
DROP PROCEDURE IF EXISTS `Fix_subsidy`;
DELIMITER ;;
CREATE PROCEDURE `Fix_subsidy`(OUT error INT(11))
BEGIN

  DECLARE done INT DEFAULT 0;
  DECLARE c_user_id INT DEFAULT 0;
  DECLARE c_amount INT DEFAULT 0;

  # 所有用户
  DECLARE c_user CURSOR FOR
    SELECT user_id,
           record_amount
    FROM zc_account_goldcoin_201812
    WHERE record_addtime < 1546138192
      AND record_addtime >= 1545897217
      AND record_action IN (104);

  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; -- 异常错误
  SET error = 0;
  SET @count = 0;
  out_label:
  BEGIN

    OPEN c_user;
    REPEAT
      FETCH c_user
        INTO c_user_id, c_amount;
      IF NOT done
      THEN
        BEGIN
          CALL Income_subsidy(c_user_id, c_amount, 0, error);
          IF error THEN
            LEAVE out_label;
          END IF;
          SET @count = @count + 1;
        END;
      END IF;
    UNTIL done END REPEAT;
    CLOSE c_user;
  END out_label;
  SELECT @count;
END
;;
DELIMITER ;

CALL Fix_subsidy(@error);
SELECT @error;

DROP PROCEDURE IF EXISTS `Fix_subsidy`;

