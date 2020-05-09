
-- ------------------- 存储过程.-.1024.事件.sql start ------------------------ 

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

-- ------------------- 存储过程.-.1024.事件.sql end ------------------------ 


-- ------------------- 存储过程.-.1024.大礼包区消费返奖.sql start ------------------------ 

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
      if @giveGoldCoinAmount <= 0
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

-- ------------------- 存储过程.-.1024.大礼包区消费返奖.sql end ------------------------ 


-- ------------------- 存储过程.-.1024.店长星级评定.sql start ------------------------ 

# -- -------------------------------
# -- 上级定星
# -- -------------------------------
DROP PROCEDURE IF EXISTS `Service_parentStar`;
DELIMITER ;;
CREATE PROCEDURE `Service_parentStar`(
  IN  userId INT(11),
  OUT error  INT(11)
)
  BEGIN

    declare done int default 0;
    declare c_user_id int default 0;

    declare c_user cursor for
      select p.id
      from
        zc_member as m
        left join zc_member as p on find_in_set(p.id, m.repath)
      where
        m.id = userId
        and p.is_lock = 0
      order by p.relevel desc;

    declare continue handler for not found set done = 1;
    declare continue handler for sqlexception set error = 1; # 异常错误
    set error = 0;

    out_label: BEGIN
      open c_user;
      repeat
        fetch c_user
        into c_user_id;
        if not done
        then
          begin

            call Service_star(c_user_id, error);

            if error
            then
              leave out_label;
            end if;

          end;
        end if;
      until done end repeat;
      close c_user;
    END out_label;
  END
;;
DELIMITER ;


# -- -------------------------------
# -- 业绩定星
# -- -------------------------------
DROP PROCEDURE IF EXISTS `Service_star`;
DELIMITER ;;
CREATE PROCEDURE `Service_star`(
  IN  userId INT(11),
  OUT error  INT(11)
)
  BEGIN
    declare continue handler for sqlexception set error = 1; # 异常错误
    set error = 0;

    out_label: BEGIN

      select count(0)
      into @hasUser
      from zc_member
      where id = userId and is_lock = 0;

      if @hasUser = 0
      then
        leave out_label;
      end if;

      select count(0)
      into @childrenCount
      from
        zc_member
      where
        find_in_set(userId, repath);
      set @i = 0;
      set @roleStar = 0;
      starloop: loop
        set @i = @i + 1;
        if @i <> 4
        then
          set @condition = GetSetting(concat('service_star_condition_', @i));
          if @childrenCount >= @condition
          then
            set @roleStar = @i;
          else
            leave starloop;
          end if;
        end if;

        if @i >= 5
        then
          leave starloop;
        end if;
      end loop starloop;

      update zc_member
      set role_star = @roleStar
      where id = userId;

    END out_label;
  END
;;
DELIMITER ;

-- ------------------- 存储过程.-.1024.店长星级评定.sql end ------------------------ 


-- ------------------- 存储过程.-.1024.店长消费奖.sql start ------------------------ 

-- -------------------------------
-- 店长消费奖（见点奖）
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income_consume`;
DELIMITER ;;
CREATE PROCEDURE `Income_consume`(
  IN  userId            INT(11),
  IN  performanceAmount decimal(14, 4),
  IN  orderId           INT(11),
  OUT error             INT(11)
)
  BEGIN

    declare done int default 0;
    declare c_user_id int default 0;

    # 获取上三级店长
    declare c_user cursor for
      select p.id
      from
        zc_member as m
        left join zc_member as p on find_in_set(p.id, m.repath)
      where
        m.id = userId
        and p.role = 3
        and p.is_lock = 0
      order by p.relevel desc
      limit 3;

    declare continue handler for not found set done = 1;
    declare continue handler for sqlexception set error = 1; #异常错误
    set error = 0;

    set @parentLevel = 0;

    out_label: BEGIN

      set @consumeBai = GetSetting(CONCAT('prize_service_consume_bai'));
      if @consumeBai <= 0
      then
        leave out_label;
      end if;

      set @amount = performanceAmount * @consumeBai * 0.01;

      open c_user;
      repeat
        fetch c_user
        into c_user_id;
        if not done
        then
          begin
            set @parentLevel = @parentLevel + 1;

            set @consumeBai = GetSetting(CONCAT('prize_service_consume_bai_', @parentLevel));
            if @consumeBai <= 0
            then
              leave out_label;
            end if;

            #添加明细
            CALL AddAccountRecord(c_user_id, 'cash', 310, @amount * @consumeBai * 0.01, UNIX_TIMESTAMP(),
                                  concat('{"order_id":"', orderId, '"}'), '店长消费奖', @consumeBai, error);

            if error
            then
              leave out_label;
            end if;

          end;
        end if;
      until done end repeat;
      close c_user;
    END out_label;
  END
;;
DELIMITER ;

-- ------------------- 存储过程.-.1024.店长消费奖.sql end ------------------------ 


-- ------------------- 存储过程.-.1024.店长补贴.sql start ------------------------ 

-- -------------------------------
-- 店长补贴
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income_subsidyService`;
DELIMITER ;;
CREATE PROCEDURE `Income_subsidyService`(
  IN  userId            INT(11),
  IN  performanceAmount decimal(14, 4),
  IN  orderId           INT(11),
  OUT error             INT(11)
)
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      select count(0)
      into @hasParent
      from
        zc_member as m
        left join zc_member as p on find_in_set(p.id, m.repath)
      where
        m.id = userId
        and p.role = 3
        and p.is_lock = 0;

      if @hasParent = 0
      then
        leave out_label;
      end if;

      select
        p.id,
        p.role_star
      into @parentId, @roleStar
      from
        zc_member as m
        left join zc_member as p on find_in_set(p.id, m.repath)
      where
        m.id = userId
        and p.role = 3
        and p.is_lock = 0
      order by p.relevel desc
      limit 1;

      # 补贴比例
      SET @subsidyBai = GetSetting(CONCAT('service_star_subsidy_', @roleStar));
      if @subsidyBai <= 0
      then
        leave out_label;
      end if;

      #添加明细
      CALL AddAccountRecord(@parentId, 'cash', 311, performanceAmount * @subsidyBai * 0.01, UNIX_TIMESTAMP(),
                            concat('{"from_uid":"', userId, '","order_id":"', orderId, '"}'), '店长补贴', @subsidyBai,
                            error);
    END out_label;
  END
;;
DELIMITER ;

-- ------------------- 存储过程.-.1024.店长补贴.sql end ------------------------ 


-- ------------------- 存储过程.-.1024.收益.sql start ------------------------ 

-- -------------------------------
-- 收益
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income`;
DELIMITER ;;
CREATE PROCEDURE `Income`(
  IN  userId            INT(11),
  IN  performanceAmount DECIMAL(14, 4),
  IN  orderId           INT(11),
  OUT error             INT(11)
)
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      # 分发店长消费奖
      call Income_consume(userId, performanceAmount, orderId, error);
      if error
      then
        leave out_label;
      end if;

      # 分发星级店长补贴
      call Income_subsidyService(userId, performanceAmount, orderId, error);
      if error
      then
        leave out_label;
      end if;

    END out_label;
  END
;;
DELIMITER ;

-- ------------------- 存储过程.-.1024.收益.sql end ------------------------ 


-- ------------------- 存储过程.-.1024.消费返.sql start ------------------------ 

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

-- ------------------- 存储过程.-.1024.消费返.sql end ------------------------ 


-- ------------------- 存储过程.-.1024.直推奖.sql start ------------------------ 

-- -------------------------------
-- 直推奖
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income_recommend`;
DELIMITER ;;
CREATE PROCEDURE `Income_recommend`(IN userId INT(11), IN amount DECIMAL(14, 4), OUT error INT(11))
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      # 直推上线必须是VIP，才能获取直推奖
      select count(0)
      into @hasParent
      from
        zc_member as m
        left join zc_member as p on m.reid = p.id
      where
        m.id = userId
        and p.is_lock = 0
        and p.`level` in (2);

      if @hasParent = 0
      then
        leave out_label;
      end if;

      select
        p.id,
        p.`level`,
        m.`level`
      into @parentId, @parentLevel, @userLevel
      from
        zc_member as m
        left join zc_member as p on m.reid = p.id
      where
        m.id = userId
        and p.is_lock = 0
        and p.`level` in (2);

      # 直推奖比例
      SET @recommendBai = GetSetting(CONCAT('prize_direct_bai_', @parentLevel));
      if @recommendBai <= 0
      then
        leave out_label;
      end if;

      #添加明细
      CALL AddAccountRecord(@parentId, 'cash', 309, amount * @recommendBai * 0.01, UNIX_TIMESTAMP(),
                            concat('{"from_uid":"', userId, '"}'), '直推奖', @recommendBai, error);


    END out_label;
  END
;;
DELIMITER ;

-- ------------------- 存储过程.-.1024.直推奖.sql end ------------------------ 

