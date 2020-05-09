-- -------------------------------
-- 大礼包区消费返奖
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income_consumeBack_gift`;
DELIMITER ;;
CREATE PROCEDURE `Income_consumeBack_gift`(
  IN  userId    INT(11),
  IN  userLevel TINYINT(1),
  IN  amount    DECIMAL(14, 4),
  IN  orderId   INT(11),
  OUT error     INT(11)
)
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      # 获取激活赠送兑换券金额
      SET @giveGoldCoinBai = GetSetting(CONCAT('buy_gift_give_goldcoin_bai_', userLevel));
      if @giveGoldCoinBai <= 0
      then
        leave out_label;
      end if;

      #添加明细
      CALL AddAccountRecord(userId, 'goldcoin', 103, amount * @giveGoldCoinBai * 0.01, UNIX_TIMESTAMP(),
                            concat('{"order_id":"', orderId, '"}'), '消费赠送', 0, error);

    END out_label;
  END
;;
DELIMITER ;