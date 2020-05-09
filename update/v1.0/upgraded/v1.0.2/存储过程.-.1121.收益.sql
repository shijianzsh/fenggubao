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

      # 分发星级VIP业绩补贴（级差）
      CALL Income_subsidyPerformance(userId, performanceAmount, orderId, error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 分发店长消费奖（上三级店长）
      CALL Income_consume(userId, performanceAmount, orderId, error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 分发星级店长补贴（级差）
      CALL Income_subsidyService(userId, performanceAmount, orderId, error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

    END out_label;
  END
;;
DELIMITER ;