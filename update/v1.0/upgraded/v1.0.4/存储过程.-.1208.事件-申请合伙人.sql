-- -------------------------------
-- 申请合伙人
-- -------------------------------
DROP PROCEDURE IF EXISTS `Event_applyService`;
DELIMITER ;;
CREATE PROCEDURE `Event_applyService`(
  IN  userId INT(11),
  OUT error  INT(11)
)
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN


    END out_label;
  END
;;
DELIMITER ;