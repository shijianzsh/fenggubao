-- -------------------------------
-- 收益
-- -------------------------------
DROP PROCEDURE IF EXISTS `Event_applyService`;
DELIMITER ;;
CREATE PROCEDURE `Event_applyService`(
  IN  userId INT(11),
  OUT error  INT(11)
)
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      # 获取店长赠送兑换券金额
      SET @giveGoldCoinAmount = GetSetting(CONCAT('apply_service_give_goldcoin_amount'));
      if @giveGoldCoinAmount <= 0
      then
        leave out_label;
      end if;

      #添加明细
      CALL AddAccountRecord(userId, 'goldcoin', 106, @giveGoldCoinAmount, UNIX_TIMESTAMP(), '', '申请店长赠送', 0, error);
      if error
      then
        leave out_label;
      end if;

      CALL Income_recommendService(userId, error);
      if error
      then
        leave out_label;
      end if;

      CALL Service_star(userId, error);
      if error
      then
        leave out_label;
      end if;

      CALL Service_parentStar(userId, error);
      if error
      then
        leave out_label;
      end if;

      if error
      then
        leave out_label;
      end if;

    END out_label;
  END
;;
DELIMITER ;