-- -------------------------------
-- 补发奖金
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income_again`;
DELIMITER ;;
CREATE PROCEDURE `Income_again`(IN orderId INT(11),
                                OUT error TINYINT(1))
BEGIN

  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; -- 异常错误
  SET error = 0;

  out_label:
  BEGIN


    SELECT o.uid,
           sum(op.price_cash * op.product_quantity * op.performance_bai_cash * 0.01 * if(ifnull(o.`discount`, 0) = 0, 10, o.`discount`) * 0.1)
           INTO
             @userId,
             @performanceAmount
    FROM zc_orders AS o
           LEFT JOIN zc_order_product AS op ON o.id = op.order_id
    WHERE o.id = orderId;

    # 分发销售奖
    CALL Income_consume(@userId, @performanceAmount, orderId, error);
    IF error
    THEN
      LEAVE out_label;
    END IF;

    # 分发钻石经销商补贴
    CALL Income_subsidy_5(@userId, @performanceAmount, orderId, error);
    IF error
    THEN
      LEAVE out_label;
    END IF;

    # 特殊身份补贴
    CALL Income_special(@userId, @performanceAmount, orderId, error);
    IF error
    THEN
      LEAVE out_label;
    END IF;

    # 分发区域合伙人奖
    CALL Income_countyService(@userId, @performanceAmount, orderId, error);
    IF error
    THEN
      LEAVE out_label;
    END IF;

    # 分发省级合伙人奖
    CALL Income_provinceService(@userId, @performanceAmount, orderId, error);
    IF error
    THEN
      LEAVE out_label;
    END IF;

  END out_label;
END
;;
DELIMITER ;
