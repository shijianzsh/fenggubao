-- -------------------------------
--
-- -------------------------------
DROP PROCEDURE IF EXISTS `Event_adViewed`;
DELIMITER ;;
CREATE PROCEDURE `Event_adViewed`(IN  userId  INT(11),
  IN  adId    int(11),
  OUT status  tinyint(1),
  OUT message varchar(255),
  OUT error   INT(11)
  )
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

    END out_label;
  END
;;
DELIMITER ;