
-- -------------------------------
-- 计算指定用户最新时间矿机数
-- -------------------------------
DROP PROCEDURE IF EXISTS `CalculationMachine_latest`;
DELIMITER ;;
CREATE PROCEDURE `CalculationMachine_latest`(IN userId INT(11), OUT error INT(11))
BEGIN

  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; -- 异常错误
  SET error = 0;

  out_label:
  BEGIN
    SET @times = 5;
    IF unix_timestamp(GetSetting(concat('mine_machine_end_', 5))) <= 0 THEN
      SET @times = 4;
    END IF;

    IF unix_timestamp(GetSetting(concat('mine_machine_end_', 4))) <= 0 THEN
      SET @times = 3;
    END IF;

    IF unix_timestamp(GetSetting(concat('mine_machine_end_', 3))) <= 0 THEN
      SET @times = 2;
    END IF;

    IF unix_timestamp(GetSetting(concat('mine_machine_end_', 2))) <= 0 THEN
      SET @times = 1;
    END IF;

    IF unix_timestamp(GetSetting(concat('mine_machine_end_', 1))) <= 0 THEN
      SET @times = 0;
    END IF;

    CALL CalculationMachine_stage(userId, @times, @error);
    IF @error THEN
      LEAVE out_label;
    END IF;
  END out_label;
END
;;
DELIMITER ;
