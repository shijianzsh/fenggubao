-- -------------------------------
-- 订单完成事件
-- -------------------------------
DROP PROCEDURE IF EXISTS `Event_register`;
DELIMITER ;;
CREATE PROCEDURE `Event_register`(IN userId int(11), OUT error int(11))
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      # 生成推荐关系
      CALL Register_recommand(userId, error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      INSERT IGNORE INTO `zc_consume` (`user_id`) VALUE (userId);

    END out_label;
  END
;;
DELIMITER ;