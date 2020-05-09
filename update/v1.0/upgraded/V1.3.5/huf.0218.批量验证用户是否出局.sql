-- -------------------------------
-- 批量验证用户是否出局
-- -------------------------------
DROP PROCEDURE IF EXISTS `CheckIsOut_batch`;
DELIMITER ;;
CREATE PROCEDURE `CheckIsOut_batch`(
  OUT error INT(11)
)
BEGIN

  DECLARE done INT DEFAULT 0;
  DECLARE c_user_id INT DEFAULT 0;

  # 获取当日所有挖矿队列
  DECLARE c_user CURSOR FOR
    SELECT user_id
    FROM zc_consume;

  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; -- 异常错误
  SET error = 0;

  out_label:
  BEGIN
    OPEN c_user;
    REPEAT
      FETCH c_user INTO c_user_id;
      IF NOT done
      THEN
        BEGIN
          CALL Income_add(c_user_id, 0, error);
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


CALL CheckIsOut_batch(@error);
SELECT  @error;
DROP PROCEDURE IF EXISTS `CheckIsOut_batch`;