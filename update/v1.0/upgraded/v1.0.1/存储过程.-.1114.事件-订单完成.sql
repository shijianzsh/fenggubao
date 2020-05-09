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

      #       set @performanceBai = GetSetting(CONCAT('performance_bai_order_goldcoin'));
      #       if @performanceBai <= 0
      #       then
      #         leave out_label;
      #       end if;

      select
        o.uid,
        s.uid,
        sum(op.price_cash * op.product_quantity),
        sum(op.price_goldcoin * op.product_quantity),
        sum(op.price_cash * op.product_quantity * op.performance_bai_cash * 0.01)
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



-- -------------------------------
-- 自动完成订单
-- -------------------------------
DROP PROCEDURE IF EXISTS `TimerTask_autoCompleteOrder`;
DELIMITER ;;
CREATE PROCEDURE `TimerTask_autoCompleteOrder`(OUT error INT(11))
  BEGIN

    DECLARE done INT DEFAULT 0;
    DECLARE c_order_id INT DEFAULT 0;

    DECLARE orders CURSOR FOR
      SELECT o.`id` AS order_id
      FROM
        `zc_orders` AS o
        LEFT JOIN `zc_order_affiliate` AS oa ON o.`id` = oa.order_id
      WHERE
        o.`order_status` = 3  # 已发货订单
        AND oa.`affiliate_sendtime` < UNIX_TIMESTAMP() - 3600 * 24 * 7; # 发货时间超过7天
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;
    # 获取当前时间
    SET @now = UNIX_TIMESTAMP();
    out_label: BEGIN
      OPEN orders;
      REPEAT
        FETCH orders
        INTO c_order_id;
        IF NOT done
        THEN
          BEGIN
            # 自动完成订单
            UPDATE `zc_orders` AS o, `zc_order_affiliate` AS of
            SET
              o.`order_status`            = 4, # 更订单改为已完成
              of.`affiliate_completetime` = @now
            WHERE
              o.`id` = of.`order_id`
              AND o.`id` = c_order_id;

            # 调起消费事件
            CALL Event_consume(c_order_id, error);
            IF error
            THEN
              LEAVE out_label;
            END IF;
          END;
        END IF;
      UNTIL done END REPEAT;
      CLOSE orders;
    END out_label;
  END
;;
DELIMITER ;



-- ----------------------------
-- 星级店长业务补贴（每天0时执行）
-- ----------------------------
DROP EVENT IF EXISTS `everyday_autoCompleteOrder`;
DELIMITER ;;
CREATE EVENT `everyday_autoCompleteOrder`
  ON SCHEDULE EVERY 1 DAY
    STARTS '2018-09-07 00:00:00'
  ON COMPLETION PRESERVE
  ENABLE DO BEGIN

  CALL TimerTask_autoCompleteOrder(@error);

END
;;
DELIMITER ;


