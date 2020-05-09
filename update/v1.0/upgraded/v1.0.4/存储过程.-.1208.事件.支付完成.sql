-- -------------------------------
-- 支付完成事件
-- -------------------------------
DROP PROCEDURE IF EXISTS `Event_paid`;
DELIMITER ;;
CREATE PROCEDURE `Event_paid`(IN orderId INT(11), OUT error INT(11))
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      SET @orderStatus = 0;
      SELECT order_status INTO @orderStatus FROM zc_orders WHERE id = orderId;
      IF @orderStatus <> 1 THEN
        LEAVE out_label;
      END IF;

      -- 激活个人代理
      CALL Event_activated(orderId, error);
      IF error THEN
        LEAVE out_label;
      END IF;

      -- 累计业绩
      CALL Performance_calculation(orderId, error);
      IF error THEN
        LEAVE out_label;
      END IF;

    END out_label;
  END
;;
DELIMITER ;