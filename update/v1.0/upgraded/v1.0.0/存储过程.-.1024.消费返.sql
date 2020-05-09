-- -------------------------------
-- 消费返
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income_consumeBack`;
DELIMITER ;;
CREATE PROCEDURE `Income_consumeBack`(IN orderId INT(11), OUT error INT(11))
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      # 检测订单（非大礼包区产品，需订单完成后才能下发业绩和收益）
      select count(0)
      into @hasOrder
      from
        zc_orders as o
        left join zc_order_product as op on o.id = op.order_id
        left join zc_product_affiliate as pa on op.product_id = pa.product_id
        left join zc_member as m on o.uid = m.id
      where
        o.id = orderId
        and pa.block_id <> 4
        and o.order_status = 4;

      if @hasOrder = 0
      then
        leave out_label;
      end if;

      select
        o.uid,
        sum(op.give_goldcoin * op.product_quantity)
      into
        @userId,
        @giveGoldCoinAmount
      from
        zc_orders as o
        left join zc_order_product as op on o.id = op.order_id
      where o.id = orderId;

      if @giveGoldCoinAmount > 0
      then
        #添加明细
        CALL AddAccountRecord(@userId, 'goldcoin', 103, @giveGoldCoinAmount, UNIX_TIMESTAMP(),
                              concat('{"order_id":"', orderId, '"}'), '消费赠送', 0, error);
        if error
        then
          leave out_label;
        end if;
      end if;

    END out_label;
  END
;;
DELIMITER ;