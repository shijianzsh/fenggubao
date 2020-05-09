-- -------------------------------
-- 谷聚金收益 - 激活合伙人赠送
-- -------------------------------
DROP PROCEDURE IF EXISTS `Gjj_Income_give`;
DELIMITER ;;
CREATE PROCEDURE `Gjj_Income_give`(IN userId INT(11), IN role TINYINT(1), OUT error INT(11))
BEGIN

  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
  SET error = 0;

  out_label:
  BEGIN

    -- 获取赠送数量
    SET @giveAmount = GetSetting(concat('gjj_agent_give_', role));
    IF @giveAmount <= 0 THEN
      LEAVE out_label;
    END IF;

    -- 添加明细
    CALL AddAccountRecord(userId, 'colorcoin', 407, @giveAmount, UNIX_TIMESTAMP(), concat('{"role":"', role, '"}'), '谷聚金-激活合伙人赠送', 0, error);
    IF error THEN
      LEAVE out_label;
    END IF;

  END out_label;
END
;;
DELIMITER ;
