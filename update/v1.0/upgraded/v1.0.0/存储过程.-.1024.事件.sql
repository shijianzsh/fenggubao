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

      # 只有购买大礼区，价格为380元的商品，才能激活会员
      select count(0)
      into @hasOrder
      from
        zc_orders as o
        left join zc_order_product as op on o.id = op.order_id
        left join zc_product_affiliate as pa on op.product_id = pa.product_id
        left join zc_member as m on o.uid = m.id
      where
        o.id = orderId
        and o.order_status = 1
        and pa.block_id = 4;

      IF @hasOrder <> 1
      THEN
        LEAVE out_label;
      END IF;

      select
        o.uid,
        m.level,
        sum(op.price_cash * op.product_quantity)
      into @userId, @memberLevel, @orderAmount
      from
        zc_orders as o
        left join zc_order_product as op on o.id = op.order_id
        left join zc_product_affiliate as pa on op.product_id = pa.product_id
        left join zc_member as m on o.uid = m.id
      where
        o.id = orderId
        and o.order_status = 1
        and pa.block_id = 4;

      if @orderAmount = 0
      then
        leave out_label;
      end if;

      CALL Event_activated(@userId, @orderAmount, @activateLevel, error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      if @activateLevel > 0
      then
        set @memberLevel = @activateLevel;
      end if;

      if @memberLevel in (2)
      then
        # 大礼包区消费返奖
        call Income_consumeBack_gift(@userId, @memberLevel, @orderAmount, orderId, error);
      end if;
      if error
      then
        leave out_label;
      end if;

      set @performanceAmount = 0;
      # 只有激活订单才分发收益
      if @activateLevel > 0
      then
        set @performanceAmount = @orderAmount;
        call Income(@userId, @performanceAmount, orderId, error);
      #       else
      #         set @performanceBai = GetSetting(CONCAT('yeji_no_365_bai'));
      #         if @performanceBai > 0
      #         then
      #           set @performanceAmount = @orderAmount * @performanceBai * 0.01;
      #         end if;
      end if;

      if error
      then
        leave out_label;
      end if;

      # 添加业绩结算对列
      if @performanceAmount > 0
      then
        insert ignore into zc_performance_queue (user_id, performance_amount, order_id, queue_status, queue_addtime, queue_starttime, queue_endtime)
        values (@userId, @performanceAmount, orderId, 0, unix_timestamp(), unix_timestamp(), unix_timestamp());
      end if;

    END out_label;
  END
;;
DELIMITER ;

-- -------------------------------
-- 激活VIP会员事件
-- -------------------------------
DROP PROCEDURE IF EXISTS `Event_activated`;
DELIMITER ;;
CREATE PROCEDURE `Event_activated`(
  IN  userId        INT(11),
  IN  amount        DECIMAL(14, 4),
  OUT activateLevel tinyint(1),
  OUT error         INT(11)
)
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      # 检查会员是名存在
      select count(0)
      into @hasUser
      from zc_member
      where `level` in (1) and id = userId and is_lock = 0;

      if @hasUser = 0
      then
        leave out_label;
      end if;

      # 分发直推奖
      CALL Income_recommend(userId, amount, error);
      if error
      then
        leave out_label;
      end if;

      select `level`
      into @memberLevel
      from zc_member
      where id = userId;

      set activateLevel = 0;

      # 验证激活VIP会员金额
      SET @memberLevelAmount = GetSetting(CONCAT('buy_gift_amount_2'));
      if (amount = @memberLevelAmount and @memberLevelAmount > 0)
      then
        set activateLevel = 2;
      end if;

      # 已激活VIP会员不需要再次激活，只能向上升级，不能降级
      if (activateLevel <= 0 or @memberLevel >= activateLevel)
      then
        leave out_label;
      end if;

      # 激活VIP会员
      update zc_member
      set `level` = activateLevel, open_time = unix_timestamp()
      where `level` in (1) and id = userId;


    END out_label;
  END
;;
DELIMITER ;


-- -------------------------------
-- 订单完成事件
-- -------------------------------
DROP PROCEDURE IF EXISTS `Event_consume`;
DELIMITER ;;
CREATE PROCEDURE `Event_consume`(IN orderId INT(11), OUT error INT(11))
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

      IF @hasOrder = 0
      THEN
        LEAVE out_label;
      END IF;

      # 分发消费返
      #       call Income_consumeBack(orderId, error);
      #       if error
      #       then
      #         leave out_label;
      #       end if;

      set @performanceBai = GetSetting(CONCAT('performance_bai_order_goldcoin'));
      if @performanceBai <= 0
      then
        leave out_label;
      end if;

      select
        o.uid,
        s.uid,
        sum(op.price_cash * op.product_quantity),
        sum(op.price_goldcoin * op.product_quantity),
        sum(op.price_goldcoin * op.product_quantity * @performanceBai * 0.01)
      into
        @userId,
        @merchantUserId,
        @cashAmount,
        @goldcoinAmount,
        @performanceAmount
      from
        zc_orders as o
        left join zc_order_product as op on o.id = op.order_id
        left join zc_store as s on o.storeid = s.id
      where o.id = orderId;

      SET @merchantAmount = @cashAmount - @performanceAmount;

      if @merchantAmount > 0
      then
        # 分发商家收益
        call Income_merchant(@merchantUserId, @merchantAmount, orderId, error);
        if error
        then
          leave out_label;
        end if;
      end if;

      if @performanceAmount > 0
      then
        call Income(@userId, @performanceAmount, orderId, error);
        if error
        then
          leave out_label;
        end if;

        insert ignore into zc_performance_queue (user_id, performance_amount, order_id, queue_status, queue_addtime, queue_starttime, queue_endtime)
        values (@userId, @performanceAmount, orderId, 0, unix_timestamp(), unix_timestamp(), unix_timestamp());
      end if;

    END out_label;
  END
;;
DELIMITER ;