-- -------------------------------
-- 谷聚金代理专区 - 事件 - 激活合伙人身份
-- -------------------------------
DROP PROCEDURE IF EXISTS `Gjj_Event_activated`;
DELIMITER ;;
CREATE PROCEDURE `Gjj_Event_activated`(IN userId INT(11), IN role TINYINT(1), OUT error INT(11))
BEGIN

  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
  SET error = 0;

  out_label:
  BEGIN

    -- 赠送谷聚金
    CALL Gjj_Income_give(userId, role, error);
    IF error THEN
      LEAVE out_label;
    END IF;

    -- 分发招商补贴
    CALL Gjj_Income_subsidy(userId, role, error);
    IF error THEN
      LEAVE out_label;
    END IF;

    -- 累计业绩
    CALL Gjj_Performance_calculation(userId, role, error);
    IF error THEN
      LEAVE out_label;
    END IF;

  END out_label;
END
;;
DELIMITER ;


