-- -------------------------------
-- 收益
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income`;
DELIMITER ;;
CREATE PROCEDURE `Income`(
  IN  userId            INT(11),
  IN  performanceAmount DECIMAL(14, 4),
  IN  orderId           INT(11),
  OUT error             INT(11)
)
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      CALL Income_consume(userId, performanceAmount, orderId, error);
      if error
      then
        leave out_label;
      end if;

      CALL Income_countyService(userId, performanceAmount, orderId, error);
      if error
      then
        leave out_label;
      end if;

      CALL Income_provinceService(userId, performanceAmount, orderId, error);
      if error
      then
        leave out_label;
      end if;

    END out_label;
  END
;;
DELIMITER ;