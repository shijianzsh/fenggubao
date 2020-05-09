-- -------------------------------
-- 收益
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income`;
DELIMITER ;;
CREATE PROCEDURE `Income`(IN userId INT(11),
                          IN performanceAmount DECIMAL(14, 4),
                          IN orderId INT(11),
                          OUT error INT(11))
BEGIN

  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
  SET error = 0;

  out_label:
  BEGIN

    # 流入矿池
    CALL Mine_add(performanceAmount, orderId, error);
    IF error
    THEN
      LEAVE out_label;
    END IF;

    # 分发销售奖
    CALL Income_consume(userId, performanceAmount, orderId, error);
    IF error
    THEN
      LEAVE out_label;
    END IF;

    # 特殊身份补贴
    CALL Income_special(userId, performanceAmount, orderId, error);
    IF error
    THEN
      LEAVE out_label;
    END IF;

    # 分发区域合伙人奖
    CALL Income_countyService(userId, performanceAmount, orderId, error);
    IF error
    THEN
      LEAVE out_label;
    END IF;

    # 分发省级合伙人奖
    CALL Income_provinceService(userId, performanceAmount, orderId, error);
    IF error
    THEN
      LEAVE out_label;
    END IF;

  END out_label;
END
;;
DELIMITER ;