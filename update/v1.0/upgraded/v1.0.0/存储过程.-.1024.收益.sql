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

      # 分发店长消费奖
      call Income_consume(userId, performanceAmount, orderId, error);
      if error
      then
        leave out_label;
      end if;

      # 分发星级店长补贴
      call Income_subsidyService(userId, performanceAmount, orderId, error);
      if error
      then
        leave out_label;
      end if;

    END out_label;
  END
;;
DELIMITER ;