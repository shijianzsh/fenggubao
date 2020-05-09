
-- ------------------- 存储过程.-.1112.事件-申请店长.sql start ------------------------ 

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

-- ------------------- 存储过程.-.1112.事件-申请店长.sql end ------------------------ 


-- ------------------- 存储过程.-.1113.大礼包区消费返奖.sql start ------------------------ 

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

-- ------------------- 存储过程.-.1113.大礼包区消费返奖.sql end ------------------------ 


-- ------------------- 存储过程.-.1113.奖-VIP销售奖.sql start ------------------------ 

-- -------------------------------
-- VIP会员销售奖
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income_recommend`;
DELIMITER ;;
CREATE PROCEDURE `Income_recommend`(IN userId INT(11), IN amount DECIMAL(14, 4), OUT error INT(11))
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      # 直推上线必须是VIP会员，才能获取销售奖
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
        and p.`level` in (2, 5);

      # 销售奖比例
      SET @recommendBai = GetSetting(CONCAT('prize_direct_bai_', @parentLevel));
      if @recommendBai <= 0
      then
        leave out_label;
      end if;

      #添加明细
      CALL AddAccountRecord(@parentId, 'cash', 309, amount * @recommendBai * 0.01, UNIX_TIMESTAMP(),
                            concat('{"from_uid":"', userId, '"}'), 'VIP销售奖', @recommendBai, error);


    END out_label;
  END
;;
DELIMITER ;

-- ------------------- 存储过程.-.1113.奖-VIP销售奖.sql end ------------------------ 


-- ------------------- 存储过程.-.1113.奖-店长销售奖.sql start ------------------------ 

-- -------------------------------
-- 店长销售奖
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income_recommendService`;
DELIMITER ;;
CREATE PROCEDURE `Income_recommendService`(IN userId INT(11), OUT error INT(11))
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      # 直推上线
      select count(0)
      into @hasParent
      from
        zc_member as m
        left join zc_member as p on m.reid = p.id
      where
        m.id = userId
        and p.is_lock = 0;

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
      limit 1;

      # 销售奖比例
      SET @recommendAmount = GetSetting(CONCAT('prize_direct_service'));
      if @recommendAmount <= 0
      then
        leave out_label;
      end if;

      #添加明细
      CALL AddAccountRecord(@parentId, 'cash', 317, @recommendAmount, UNIX_TIMESTAMP(),
                            concat('{"from_uid":"', userId, '"}'), '店长销售奖', 0, error);


    END out_label;
  END
;;
DELIMITER ;

-- ------------------- 存储过程.-.1113.奖-店长销售奖.sql end ------------------------ 


-- ------------------- 存储过程.-.1113.激活VIP会员.sql start ------------------------ 

-- -------------------------------
-- 激活爱心会员事件
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

      # 检查会员是否存在
      select count(0)
      into @hasUser
      from zc_member
      where `level` in (1, 2) and id = userId and is_lock = 0;

      if @hasUser = 0
      then
        leave out_label;
      end if;

      # 分发销售奖
      CALL Income_recommend(userId, amount, error);
      if error
      then
        leave out_label;
      end if;

      select `level`
      into @memberLevel
      from zc_member
      where id = userId;

      # 验证激活爱心会员金额
      SET @memberLevelAmount = GetSetting(CONCAT('buy_gift_amount_2'));
      if (amount = @memberLevelAmount and @memberLevelAmount > 0)
      then
        set activateLevel = 2;
      end if;

      # 已激活会员不需要再次激活，只能向上升级，不能降级
      if (activateLevel <= 0 or @memberLevel >= activateLevel)
      then
        leave out_label;
      end if;

      # 激活爱心会员
      update zc_member
      set `level` = activateLevel, open_time = unix_timestamp()
      where `level` in (1) and id = userId;


    END out_label;
  END
;;
DELIMITER ;

# update zc_member as m, zc_orders as o
# set m.open_time = o.pay_time
# where m.id = o.uid and o.order_status in (1, 3, 4) and m.open_time = 0 and o.pay_time > 0;

-- ------------------- 存储过程.-.1113.激活VIP会员.sql end ------------------------ 


-- ------------------- 存储过程.-.1114.事件-订单完成.sql start ------------------------ 

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




-- ------------------- 存储过程.-.1114.事件-订单完成.sql end ------------------------ 


-- ------------------- 存储过程.-.1114.店长星级评定.sql start ------------------------ 

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

-- ------------------- 存储过程.-.1114.店长星级评定.sql end ------------------------ 


-- ------------------- 存储过程.-.1114.店长消费奖.sql start ------------------------ 

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
            CALL AddAccountRecord(c_user_id, 'cash', 310, performanceAmount * @consumeBai * 0.01, UNIX_TIMESTAMP(),
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

-- ------------------- 存储过程.-.1114.店长消费奖.sql end ------------------------ 


-- ------------------- 存储过程.-.1114.店长补贴.sql start ------------------------ 

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

    declare done int default 0;
    declare c_user_id int default 0;
    declare c_role_star int default 0;

    # 获取所有上级星级店长
    declare c_user cursor for
      select
        p.id,
        p.role_star
      from
        zc_member as m
        left join zc_member as p on find_in_set(p.id, m.repath)
      where
        m.id = userId
        and p.role = 3
        and p.role_star > 0
        and p.is_lock = 0
      order by p.relevel desc;

    declare continue handler for not found set done = 1;
    declare continue handler for sqlexception set error = 1; #异常错误
    set error = 0;

    set @parentLevel = 0;

    out_label: BEGIN

      set @maxSubsidyBai = GetSetting(CONCAT('service_star_subsidy_8'));
      if @maxSubsidyBai <= 0
      then
        leave out_label;
      end if;

      set @alreadySubsidyBai = 0;

      open c_user;
      repeat
        fetch c_user
        into c_user_id, c_role_star;
        if not done
        then
          begin

            if @alreadySubsidyBai >= @maxSubsidyBai
            then
              leave out_label;
            end if;

            set @subsidyBai = GetSetting(CONCAT('service_star_subsidy_', c_role_star));

            set @subsidyBai = @subsidyBai - @alreadySubsidyBai;

            if @subsidyBai > @maxSubsidyBai - @alreadySubsidyBai
            then
              set @subsidyBai = @maxSubsidyBai - @alreadySubsidyBai;
            end if;

            if @subsidyBai <= 0
            then
              leave out_label;
            end if;

            set @alreadySubsidyBai = @alreadySubsidyBai + @subsidyBai;

            #添加明细
            CALL AddAccountRecord(c_user_id, 'cash', 311, performanceAmount * @subsidyBai * 0.01, UNIX_TIMESTAMP(),
                                  concat('{"from_uid":"', userId, '","order_id":"', orderId, '"}'), '星级店长消费补贴', @subsidyBai, error);

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

-- ------------------- 存储过程.-.1114.店长补贴.sql end ------------------------ 


-- ------------------- 存储过程.-.1114.星级店长定时业务补贴.sql start ------------------------ 

-- ----------------------------
-- 星级店长业务补贴（每天0时执行）
-- ----------------------------
DROP EVENT IF EXISTS `everyday_subsidy`;
DELIMITER ;;
CREATE EVENT `everyday_subsidy`
  ON SCHEDULE EVERY 1 DAY
    STARTS '2018-09-07 00:00:00'
  ON COMPLETION PRESERVE
  ENABLE DO BEGIN

  CALL Income_subsidyStarService(@error);

END
;;
DELIMITER ;

-- -------------------------------
-- 星级店长业务补贴
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income_subsidyStarService`;
DELIMITER ;;
CREATE PROCEDURE `Income_subsidyStarService`(
  OUT error INT(11)
)
  BEGIN

    declare done int default 0;
    declare c_user_id int default 0;
    declare c_role_star int default 0;

    # 获取所有级星级店长
    declare c_user cursor for
      select
        id,
        role_star
      from
        zc_member
      where
        role = 3
        and role_star > 0
        and is_lock = 0;

    declare continue handler for not found set done = 1;
    declare continue handler for sqlexception set error = 1; #异常错误
    set error = 0;

    out_label: BEGIN

      set @subsidyAmount = GetSetting(CONCAT('service_subsidy_amount_everyday'));
      if @subsidyAmount <= 0
      then
        leave out_label;
      end if;

      set @maxAmount = GetSetting(CONCAT('service_subsidy_amount_max'));
      if @maxAmount <= 0
      then
        leave out_label;
      end if;

      set @subsidyTag = from_unixtime(unix_timestamp(), '%Y%m%d');

      open c_user;
      repeat
        fetch c_user
        into c_user_id, c_role_star;
        if not done
        then
          begin

            select count(0)
            into @hasSubsidy
            from zc_subsidy_record
            where user_id = c_user_id and subsidy_tag = @subsidyTag;

            if @hasSubsidy
            then
              leave out_label;
            end if;

            select sum(subsidy_amount)
            into @alreadySubsidyAmount
            from zc_subsidy_record
            where user_id = c_user_id;

            if @alreadySubsidyAmount >= @maxAmount
            then
              leave out_label;
            end if;

            insert into zc_subsidy_record values (null, c_user_id, @subsidyAmount, @subsidyTag, unix_timestamp());

            #添加明细
            CALL AddAccountRecord(c_user_id, 'cash', 314, @subsidyAmount, UNIX_TIMESTAMP(), '', '星级店长每日业务补贴', 0, error);

          end;
        end if;
      until done end repeat;
      close c_user;
    END out_label;
  END
;;
DELIMITER ;

-- ------------------- 存储过程.-.1114.星级店长定时业务补贴.sql end ------------------------ 


-- ------------------- 存储过程.-.1115.收益.sql start ------------------------ 

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

-- ------------------- 存储过程.-.1115.收益.sql end ------------------------ 


-- ------------------- 存储过程.-.1116-添加明细(个人所得税).sql start ------------------------ 

DROP PROCEDURE IF EXISTS `AddAccountRecord`;
DELIMITER ;;
CREATE PROCEDURE `AddAccountRecord`(
  IN userId   INT(11),
  IN currency VARCHAR(50),
  IN action   INT(11),
  IN amount   FLOAT,
  IN addtime  INT(11),
  IN attach   VARCHAR(1000),
  IN remark   VARCHAR(1000),
  IN exchange FLOAT, OUT error INT(11))
  SQL SECURITY INVOKER
  BEGIN
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN
      # 金额=0, 不计入明细
      IF TRUNCATE(amount, 4) = 0
      THEN
        LEAVE out_label;
      END IF;

      # 动态生成表名
      SET @to_table = CONCAT('zc_account_', currency, '_', from_unixtime(addtime, '%Y%m'));

      # 检查并创建对应交易记录表
      SET @v_sql = CONCAT('CREATE TABLE IF NOT EXISTS `', @to_table, '` LIKE `zc_account_record`;');
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @attach = attach;
      IF @attach = ''
      THEN
        SET @attach = '{}';
      END IF;

      -- 初始化用户实时收支数据
      INSERT IGNORE INTO zc_account (user_id, account_tag, account_uptime) VALUES (userId, 0, addtime);
      -- 初始化用户日收支数据
      INSERT IGNORE INTO zc_account (
        user_id,
        account_cash_balance,
        account_tag,
        account_uptime)
        SELECT
          user_id,
          account_cash_balance,
          @account_tag AS account_tag,
          addtime      AS account_uptime
        FROM zc_account
        WHERE user_id = userId AND account_tag = 0;

      SET @account_tag = from_unixtime(addtime, '%Y%m%d');
      SET @field_balance = CONCAT('account_', currency, '_balance');
      SET @field_expenditure = CONCAT('account_', currency, '_expenditure');
      SET @field_income = CONCAT('account_', currency, '_income');

      SET @old_balance = 0;
      # 获取帐户余额
      SET @v_sql = CONCAT('SELECT ', @field_balance, ' INTO @old_balance FROM zc_account WHERE
                          account_tag = 0 AND user_id = ', userId, ' LIMIT 1;');
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      # 插入明细
      SET @v_sql = CONCAT(' INSERT INTO ', @to_table, ' VALUES(
      NULL,
      ', userId, ',
      \'', currency, '\',
      ', action, ',
      ', amount, ',
      ', (@old_balance + amount), ',
      ', addtime, ',
      \'', @attach, '\',
      \'', remark, '\',
      \'', exchange, '\')');
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @record_id = LAST_INSERT_ID(); # 获取明细ID
      IF @record_id = 0
      THEN
        SET error = 1;
        LEAVE out_label;
      END IF;

      # 更新用户实时收支总计和余额
      SET @v_sql = CONCAT('UPDATE zc_account SET
        ', @field_balance, ' = ', @field_balance, ' + ', amount, ',
        ', @field_expenditure, ' = ', @field_expenditure, ' + ', IF(amount < 0, amount, 0), ',
        ', @field_income, ' = ', @field_income, ' + ', IF(amount > 0, amount, 0), ',
        account_uptime = ', addtime, '
        WHERE user_id = ', userId, ' AND account_tag = 0');
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      # 更新用户当日收支总计和余额
      SET @v_sql = CONCAT('UPDATE zc_account AS za, zc_account AS za0 SET
        za.', @field_balance, ' = za0.', @field_balance, ',
        za.', @field_expenditure, ' = za.', @field_expenditure, ' + ', IF(amount < 0, amount, 0), ',
        za.', @field_income, ' = za.', @field_income, ' + ', IF(amount > 0, amount, 0), ',
        za.account_uptime = ', addtime, '
        WHERE za.user_id = za0.user_id
        AND za.user_id = ', userId, '
        AND za0.user_id = ', userId, '
        AND za.account_tag = ', @account_tag, '
        AND za0.account_tag = 0;');
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      # 306 商家收益, 309 直推奖, 310 服务网点/营运中心消费奖, 311 服务网点补贴奖， 312 营运中心补贴奖, 313 业绩分成, 314 加权分红， 315 合伙人补贴奖, 316 广告收益
      SET @has_tax = 0; # 是否征收个人所得税
      IF action IN (309, 310, 311, 312, 313, 314, 315, 316, 317)
      THEN
        SET @has_tax = 1;
      END IF;

      IF @has_tax = 1
      THEN
        # 征收个人所得税
        CALL PersonalTax(userId, currency, amount, @record_id, error);
        IF error
        THEN
          LEAVE out_label;
        END IF;

        # 扣除平台管理费
        CALL SystemManageFee(userId, currency, amount, @record_id, error);
        IF error
        THEN
          LEAVE out_label;
        END IF;
      END IF;

    END out_label;
  END
;;
DELIMITER ;

-- ------------------- 存储过程.-.1116-添加明细(个人所得税).sql end ------------------------ 


-- ------------------- 存储过程.-.1116.用户收益统计.sql start ------------------------ 

-- ----------------------------
-- 定时统计-用户收益数据
-- ----------------------------
DROP PROCEDURE IF EXISTS `TimerTask_AccountIncome`;
DELIMITER ;;
CREATE PROCEDURE `TimerTask_AccountIncome`(IN nowtime INT(11), OUT error INT(11))
  BEGIN

    SET @now = nowtime;
    # 今日tag
    SET @day_tag = FROM_UNIXTIME(@now, '%Y%m%d');
    # 当月tag
    SET @moth_tag = FROM_UNIXTIME(@now, '%Y%m');
    # 当年tag
    SET @year_tag = FROM_UNIXTIME(@now, '%Y');
    # 昨日tag
    SET @yesterday_tag = DATE_FORMAT(DATE_ADD(@day_tag, INTERVAL -1 DAY), '%Y%m%d');
    # 上月tag
    SET @lastmoth_tag = DATE_FORMAT(DATE_ADD(@day_tag, INTERVAL -1 MONTH), '%Y%m');
    # 去年tag
    SET @lastyear_tag = @year_tag - 1;

    #统计用户昨日收益数据
    SET @task_id = TimerTask_add('Statistics_AccountIncomeDay', CONCAT(@yesterday_tag), '统计用户昨日收益数据', @yesterday_tag);
    CALL Statistics_AccountIncomeDay(@yesterday_tag, @error);
    IF @error
    THEN
      SET @affected = TimerTask_update(@task_id, 1);
    ELSE
      SET @affected = TimerTask_update(@task_id, 2);
    END IF;

    # 统计用户上月收益数据
    IF NOT @error AND IF(FROM_UNIXTIME(@now, '%e') = 1, 1, 0)
    THEN
      SET @task_id = TimerTask_add('Statistics_AccountIncome', CONCAT(@lastmoth_tag), '统计用户上月收益数据', @lastmoth_tag);
      CALL Statistics_AccountIncome(@lastmoth_tag, @error);
      IF @error
      THEN
        SET @affected = TimerTask_update(@task_id, 1);
      ELSE
        SET @affected = TimerTask_update(@task_id, 2);
      END IF;
    END IF;

    #
    IF NOT @error AND IF(FROM_UNIXTIME(@now, '%c%e') = 11, 1, 0)
    THEN
      # 统计用户去年收益数据 （数据结止日期为昨天）
      SET @task_id = TimerTask_add('Statistics_AccountIncome', CONCAT(@lastyear_tag), '统计用户去年收益数据', @lastyear_tag);
      CALL Statistics_AccountIncome(@lastyear_tag, @error);
      IF @error
      THEN
        SET @affected = TimerTask_update(@task_id, 1);
      ELSE
        SET @affected = TimerTask_update(@task_id, 2);
      END IF;
    END IF;

    # 统计用户当月收益数据 （数据结止日期为昨天）
    IF NOT @error
    THEN
      SET @task_id = TimerTask_add('Statistics_AccountIncome', CONCAT(@moth_tag), '统计用户当月收益数据 （数据结止日期为昨天）', @moth_tag);
      CALL Statistics_AccountIncome(@moth_tag, @error);
      IF @error
      THEN
        SET @affected = TimerTask_update(@task_id, 1);
      ELSE
        SET @affected = TimerTask_update(@task_id, 2);
      END IF;
    END IF;

    # 统计用户当年收益数据 （数据结止日期为昨天）
    IF NOT @error
    THEN
      SET @task_id = TimerTask_add('Statistics_AccountIncome', CONCAT(@year_tag), '统计用户当年收益数据 （数据结止日期为昨天）', @year_tag);
      CALL Statistics_AccountIncome(@year_tag, @error);
      IF @error
      THEN
        SET @affected = TimerTask_update(@task_id, 1);
      ELSE
        SET @affected = TimerTask_update(@task_id, 2);
      END IF;
    END IF;

    # 统计用户总收益数据 （数据结止日期为昨天）
    IF NOT @error
    THEN
      SET @task_id = TimerTask_add('Statistics_AccountIncome', CONCAT(0), '统计用户总收益数据 （数据结止日期为昨天）', 0);
      CALL Statistics_AccountIncome(0, @error);
      IF @error
      THEN
        SET @affected = TimerTask_update(@task_id, 1);
      ELSE
        SET @affected = TimerTask_update(@task_id, 2);
      END IF;
    END IF;

    SET error = @error;
  END
;;
DELIMITER ;




-- ----------------------------
-- 统计用户日收益数据
-- ----------------------------
DROP PROCEDURE IF EXISTS `Statistics_AccountIncomeDay`;
DELIMITER ;;
CREATE PROCEDURE `Statistics_AccountIncomeDay`(IN tag int(11), OUT error int(11))
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;


    out_label: BEGIN

      # 商家收益
      CALL Statistics_AccountIncomeDay_action(tag, 'cash', '306', 'income_cash_merchant', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # VIP销售奖（直推奖）
      CALL Statistics_AccountIncomeDay_action(tag, 'cash', '309', 'income_cash_recommend', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 店长消费奖
      CALL Statistics_AccountIncomeDay_action(tag, 'cash', '310', 'income_cash_consume', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 合伙人补贴奖
      CALL Statistics_AccountIncomeDay_action(tag, 'cash', '315', 'income_cash_partner_subsidy', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 服务网点补贴奖
      CALL Statistics_AccountIncomeDay_action(tag, 'cash', '311', 'income_cash_service_subsidy', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 营运中心补贴奖
      CALL Statistics_AccountIncomeDay_action(tag, 'cash', '312', 'income_cash_company_subsidy', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 业绩结算
      CALL Statistics_AccountIncomeDay_action(tag, 'cash', '313', 'income_cash_performance', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 星级店长业务补贴
      CALL Statistics_AccountIncomeDay_action(tag, 'cash', '314', 'income_cash_bonus', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 广告收益
      CALL Statistics_AccountIncomeDay_action(tag, 'cash', '316', 'income_cash_adview', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 店长直推奖
      CALL Statistics_AccountIncomeDay_action(tag, 'cash', '317', 'income_cash_recommend_service', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 注册赠送
      CALL Statistics_AccountIncomeDay_action(tag, 'goldcoin', '100', 'income_goldcoin_register_give', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 消费赠送
      CALL Statistics_AccountIncomeDay_action(tag, 'goldcoin', '103', 'income_goldcoin_consume_give', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 签到赠送
      CALL Statistics_AccountIncomeDay_action(tag, 'goldcoin', '104', 'income_goldcoin_checkin', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 广告收益
      CALL Statistics_AccountIncomeDay_action(tag, 'goldcoin', '105', 'income_goldcoin_adview', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      CALL Statistics_AccountIncomeDay_action(tag, 'goldcoin', '106', 'income_goldcoin_service_give', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 注册赠送
      CALL Statistics_AccountIncomeDay_action(tag, 'points', '400', 'income_points_register_give', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 消费赠送
      CALL Statistics_AccountIncomeDay_action(tag, 'points', '403', 'income_points_consume_give', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 合计统计
      UPDATE `zc_account_income`
      SET
        `income_cash_total`     = (
          `income_cash_merchant` +
          `income_cash_recommend` +
          `income_cash_consume` +
          `income_cash_partner_subsidy` +
          `income_cash_service_subsidy` +
          `income_cash_company_subsidy` +
          `income_cash_performance` +
          `income_cash_bonus` +
          `income_cash_adview` +
          `income_cash_recommend_service`
        ),
        `income_goldcoin_total` = (
          `income_goldcoin_register_give` +
          `income_goldcoin_consume_give` +
          `income_goldcoin_checkin` +
          `income_goldcoin_adview` +
          `income_goldcoin_service_give`
        ),
        `income_points_total`   = (
          `income_points_register_give` +
          `income_points_consume_give`
        ),
        income_total            = (
          `income_cash_merchant` +
          `income_cash_recommend` +
          `income_cash_consume` +
          `income_cash_partner_subsidy` +
          `income_cash_service_subsidy` +
          `income_cash_company_subsidy` +
          `income_cash_performance` +
          `income_cash_bonus` +
          `income_cash_adview` +
          `income_cash_recommend_service` +
          `income_goldcoin_register_give` +
          `income_goldcoin_consume_give` +
          `income_goldcoin_checkin` +
          `income_goldcoin_adview` +
          `income_goldcoin_service_give` +
          `income_points_register_give` +
          `income_points_consume_give` +
          `income_goldcoin_adview`
        )
      WHERE `income_tag` = tag;

      # 总统计
      CALL Statistics_AccountIncome_total(tag, error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

    END out_label;
  END
;;
DELIMITER ;


-- ----------------------------
-- 统计用户日单项收益数据
-- ----------------------------
DROP PROCEDURE IF EXISTS `Statistics_AccountIncomeDay_action`;
DELIMITER ;;
CREATE PROCEDURE `Statistics_AccountIncomeDay_action`(
  IN  tag      int(11),
  IN  currency varchar(50),
  IN  action   varchar(50),
  IN  field    varchar(50),
  OUT error    int(11)
)
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      SET @start_time = UNIX_TIMESTAMP(tag);
      SET @end_time = UNIX_TIMESTAMP(date_add(tag, INTERVAL 1 DAY));
      SET @temp_table = CONCAT('temp_account_income_', tag, '_', field);
      SET @target_table = 'zc_account_income';
      SET @source_table = CONCAT('zc_account_', currency, '_', substring(tag, 1, 6));

      SET @v_sql = CONCAT('DROP TABLE IF EXISTS ', @temp_table);
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @v_sql = CONCAT('CREATE TABLE ', @temp_table, ' AS SELECT
      user_id,
      IFNULL(SUM(record_amount), 0) AS ', field, '
      FROM ', @source_table, '
      WHERE record_action IN (', action, ')
      AND record_addtime >= ', @start_time, '
      AND record_addtime < ', @end_time, '
      GROUP BY user_id');
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @v_sql = CONCAT('UPDATE ', @target_table, ' as ta, ', @temp_table, ' as te SET
      ta.', field, ' = te.', field, ',
      ta.`income_uptime` = ', UNIX_TIMESTAMP(), '
      WHERE ta.user_id = te.user_id AND ta.income_tag = ', tag);
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @v_sql = CONCAT('INSERT IGNORE INTO ', @target_table, '(
      user_id,
      ', field, ',
      income_tag,
      income_uptime)
      SELECT
      user_id,
      ', field, ',
      ', tag, ',
      ', UNIX_TIMESTAMP(), '
      FROM ', @temp_table, ';');
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @v_sql = CONCAT('DROP TABLE IF EXISTS ', @temp_table);
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END out_label;
  END
;;
DELIMITER ;


-- ----------------------------
-- 统计用户日单项收益数据
-- ----------------------------
DROP PROCEDURE IF EXISTS `Statistics_AccountIncomeDay_action`;
DELIMITER ;;
CREATE PROCEDURE `Statistics_AccountIncomeDay_action`(
  IN  tag      int(11),
  IN  currency varchar(50),
  IN  action   varchar(50),
  IN  field    varchar(50),
  OUT error    int(11)
)
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      SET @start_time = UNIX_TIMESTAMP(tag);
      SET @end_time = UNIX_TIMESTAMP(date_add(tag, INTERVAL 1 DAY));
      SET @temp_table = CONCAT('temp_account_income_', tag, '_', field);
      SET @target_table = 'zc_account_income';
      SET @source_table = CONCAT('zc_account_', currency, '_', substring(tag, 1, 6));

      SET @v_sql = CONCAT('DROP TABLE IF EXISTS ', @temp_table);
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @v_sql = CONCAT('CREATE TABLE ', @temp_table, ' AS SELECT
      user_id,
      IFNULL(SUM(record_amount), 0) AS ', field, '
      FROM ', @source_table, '
      WHERE record_action IN (', action, ')
      AND record_addtime >= ', @start_time, '
      AND record_addtime < ', @end_time, '
      GROUP BY user_id');
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @v_sql = CONCAT('UPDATE ', @target_table, ' as ta, ', @temp_table, ' as te SET
      ta.', field, ' = te.', field, ',
      ta.`income_uptime` = ', UNIX_TIMESTAMP(), '
      WHERE ta.user_id = te.user_id AND ta.income_tag = ', tag);
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @v_sql = CONCAT('INSERT IGNORE INTO ', @target_table, '(
      user_id,
      ', field, ',
      income_tag,
      income_uptime)
      SELECT
      user_id,
      ', field, ',
      ', tag, ',
      ', UNIX_TIMESTAMP(), '
      FROM ', @temp_table, ';');
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @v_sql = CONCAT('DROP TABLE IF EXISTS ', @temp_table);
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END out_label;
  END
;;
DELIMITER ;


-- ----------------------------
-- 统计用总收益数据
-- ----------------------------
DROP PROCEDURE IF EXISTS `Statistics_AccountIncome_total`;
DELIMITER ;;
CREATE PROCEDURE `Statistics_AccountIncome_total`(
  IN  tag   int(11),
  OUT error int(11)
)
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN
      SET @temp_table = CONCAT('temp_account_income_', tag);
      SET @target_table = 'zc_account_income';
      SET @v_sql = CONCAT('DROP TABLE IF EXISTS ', @temp_table);
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
      SET @v_sql = CONCAT('CREATE TABLE ', @temp_table, ' AS SELECT
    SUM(`income_cash_merchant`) AS `income_cash_merchant`,
    SUM(`income_cash_recommend`) AS `income_cash_recommend`,
    SUM(`income_cash_consume`) AS `income_cash_consume`,
    SUM(`income_cash_partner_subsidy`) AS `income_cash_partner_subsidy`,
    SUM(`income_cash_service_subsidy`) AS `income_cash_service_subsidy`,
    SUM(`income_cash_company_subsidy`) AS `income_cash_company_subsidy`,
    SUM(`income_cash_performance`) AS `income_cash_performance`,
    SUM(`income_cash_bonus`) AS `income_cash_bonus`,
    SUM(`income_cash_adview`) AS `income_cash_adview`,
    SUM(`income_cash_recommend_service`) AS `income_cash_recommend_service`,
    SUM(`income_cash_total`) AS `income_cash_total`,

    SUM(`income_goldcoin_register_give`) AS `income_goldcoin_register_give`,
    SUM(`income_goldcoin_consume_give`) AS `income_goldcoin_consume_give`,
    SUM(`income_goldcoin_checkin`) AS `income_goldcoin_checkin`,
    SUM(`income_goldcoin_adview`) AS `income_goldcoin_adview`,
    SUM(`income_goldcoin_service_give`) AS `income_goldcoin_service_give`,
    SUM(`income_goldcoin_total`) AS `income_goldcoin_total`,

    SUM(`income_points_register_give`) AS `income_points_register_give`,
    SUM(`income_points_consume_give`) AS `income_points_consume_give`,
    SUM(`income_points_total`) AS `income_points_total`,

    SUM(`income_total`) AS `income_total`
    FROM ', @target_table, '
    WHERE user_id > 0 AND income_tag = ', tag, '
    ');
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @v_sql = CONCAT('UPDATE ', @target_table, ' as ta, ', @temp_table, ' as te SET
    ta.`income_cash_merchant` = te.`income_cash_merchant`,
    ta.`income_cash_recommend` = te.`income_cash_recommend`,
    ta.`income_cash_consume` = te.`income_cash_consume`,
    ta.`income_cash_partner_subsidy` = te.`income_cash_partner_subsidy`,
    ta.`income_cash_service_subsidy` = te.`income_cash_service_subsidy`,
    ta.`income_cash_company_subsidy` = te.`income_cash_company_subsidy`,
    ta.`income_cash_performance` = te.`income_cash_performance`,
    ta.`income_cash_bonus` = te.`income_cash_bonus`,
    ta.`income_cash_adview` = te.`income_cash_adview`,
    ta.`income_cash_recommend_service` = te.`income_cash_recommend_service`,
    ta.`income_cash_total` = te.`income_cash_total`,

    ta.`income_goldcoin_register_give` = te.`income_goldcoin_register_give`,
    ta.`income_goldcoin_consume_give` = te.`income_goldcoin_consume_give`,
    ta.`income_goldcoin_checkin` = te.`income_goldcoin_checkin`,
    ta.`income_goldcoin_adview` = te.`income_goldcoin_adview`,
    ta.`income_goldcoin_service_give` = te.`income_goldcoin_service_give`,
    ta.`income_goldcoin_total` = te.`income_goldcoin_total`,

    ta.`income_points_register_give` = te.`income_points_register_give`,
    ta.`income_points_consume_give` = te.`income_points_consume_give`,
    ta.`income_points_total` = te.`income_points_total`,

    ta.`income_total` = te.`income_total`,
    ta.`income_uptime` = ', UNIX_TIMESTAMP(), '
    WHERE ta.user_id = 0 AND ta.income_tag = ', tag);
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
      SET @v_sql = CONCAT('INSERT IGNORE INTO ', @target_table, '(
      user_id,
      income_cash_merchant,
      income_cash_recommend,
      income_cash_consume,
      income_cash_partner_subsidy,
      income_cash_service_subsidy,
      income_cash_company_subsidy,
      income_cash_performance,
      income_cash_bonus,
      income_cash_adview,
      income_cash_recommend_service,
      income_cash_total,

      income_goldcoin_register_give,
      income_goldcoin_consume_give,
      income_goldcoin_checkin,
      income_goldcoin_adview,
      income_goldcoin_service_give,
      income_goldcoin_total,

      income_points_register_give,
      income_points_consume_give,
      income_points_total,

      income_total,
      income_tag,
      income_uptime)
      SELECT
      0,
      income_cash_merchant,
      income_cash_recommend,
      income_cash_consume,
      income_cash_partner_subsidy,
      income_cash_service_subsidy,
      income_cash_company_subsidy,
      income_cash_performance,
      income_cash_bonus,
      income_cash_adview,
      income_cash_recommend_service,
      income_cash_total,

      income_goldcoin_register_give,
      income_goldcoin_consume_give,
      income_goldcoin_checkin,
      income_goldcoin_adview,
      income_goldcoin_service_give,
      income_goldcoin_total,

      income_points_register_give,
      income_points_consume_give,
      income_points_total,

      income_total,
      ', tag, ',
      ', UNIX_TIMESTAMP(), '
      FROM ', @temp_table, ';');
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
      SET @v_sql = CONCAT('DROP TABLE IF EXISTS ', @temp_table);
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END out_label;
  END
;;
DELIMITER ;

-- ----------------------------
-- 统计用户月年总收益数据
-- ----------------------------
DROP PROCEDURE IF EXISTS `Statistics_AccountIncome`;
DELIMITER ;;
CREATE PROCEDURE `Statistics_AccountIncome`(
  IN  tag   int(11),
  OUT error int(11)
)
  BEGIN
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN
      SET @temp_table = CONCAT('temp_account_income_', tag);
      SET @source_table = 'zc_account_income';
      IF tag = 0
      THEN
        SET @start_tag = 2016;
        SET @end_tag = 2020;
      ELSE
        SET @start_tag = tag * 100;
        SET @end_tag = (tag + 1) * 100;
      END IF;
      SET @v_sql = CONCAT('DROP TABLE IF EXISTS ', @temp_table);
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
      SET @v_sql = CONCAT('CREATE TABLE ', @temp_table, ' AS SELECT
      NULL AS income_id,
      `user_id` AS `user_id`,
      SUM(`income_cash_merchant`) AS income_cash_merchant,
      SUM(`income_cash_recommend`) AS income_cash_recommend,
      SUM(`income_cash_consume`) AS income_cash_consume,
      SUM(`income_cash_partner_subsidy`) AS income_cash_partner_subsidy,
      SUM(`income_cash_service_subsidy`) AS income_cash_service_subsidy,
      SUM(`income_cash_company_subsidy`) AS income_cash_company_subsidy,
      SUM(`income_cash_performance`) AS income_cash_performance,
      SUM(`income_cash_bonus`) AS income_cash_bonus,
      SUM(`income_cash_adview`) AS income_cash_adview,
      SUM(`income_cash_recommend_service`) AS income_cash_recommend_service,
      SUM(`income_cash_total`) as income_cash_total,

      SUM(`income_goldcoin_register_give`) AS income_goldcoin_register_give,
      SUM(`income_goldcoin_consume_give`) AS income_goldcoin_consume_give,
      SUM(`income_goldcoin_checkin`) AS income_goldcoin_checkin,
      SUM(`income_goldcoin_adview`) AS income_goldcoin_adview,
      SUM(`income_goldcoin_service_give`) AS income_goldcoin_service_give,
      SUM(`income_goldcoin_total`) as income_goldcoin_total,

      SUM(`income_points_register_give`) AS income_points_register_give,
      SUM(`income_points_consume_give`) AS income_points_consume_give,
      SUM(`income_points_total`) as income_points_total,

      SUM(`income_total`) as income_total,
      ', tag, ' AS income_tag,
      ', UNIX_TIMESTAMP(), ' AS income_uptime
      FROM ', @source_table, '
      WHERE income_tag >= ', @start_tag, ' AND income_tag < ', @end_tag, '
      GROUP BY `user_id` ORDER BY `user_id` ASC;');
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @v_sql = CONCAT('UPDATE ', @source_table, ' AS so, ', @temp_table, ' AS te SET
      so.income_cash_merchant = te.income_cash_merchant,
      so.income_cash_recommend = te.income_cash_recommend,
      so.income_cash_consume = te.income_cash_consume,
      so.income_cash_partner_subsidy = te.income_cash_partner_subsidy,
      so.income_cash_service_subsidy = te.income_cash_service_subsidy,
      so.income_cash_company_subsidy = te.income_cash_company_subsidy,
      so.income_cash_performance = te.income_cash_performance,
      so.income_cash_bonus = te.income_cash_bonus,
      so.income_cash_adview = te.income_cash_adview,
      so.income_cash_recommend_service = te.income_cash_recommend_service,
      so.income_cash_total = te.income_cash_total,

      so.income_goldcoin_register_give = te.income_goldcoin_register_give,
      so.income_goldcoin_consume_give = te.income_goldcoin_consume_give,
      so.income_goldcoin_checkin = te.income_goldcoin_checkin,
      so.income_goldcoin_adview = te.income_goldcoin_adview,
      so.income_goldcoin_service_give = te.income_goldcoin_service_give,
      so.income_goldcoin_total = te.income_goldcoin_total,

      so.income_points_register_give = te.income_points_register_give,
      so.income_points_consume_give = te.income_points_consume_give,
      so.income_points_total = te.income_points_total,

      so.income_total = te.income_total,
      so.income_uptime = ', UNIX_TIMESTAMP(), '
      WHERE so.user_id = te.user_id AND so.income_tag = ', tag, ';');
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @v_sql = CONCAT('INSERT IGNORE INTO `', @source_table, '`(
      `income_id`,
      `user_id`,
      income_cash_merchant,
      income_cash_recommend,
      income_cash_consume,
      income_cash_partner_subsidy,
      income_cash_service_subsidy,
      income_cash_company_subsidy,
      income_cash_performance,
      income_cash_bonus,
      income_cash_adview,
      income_cash_recommend_service,
      income_cash_total,

      income_goldcoin_register_give,
      income_goldcoin_consume_give,
      income_goldcoin_checkin,
      income_goldcoin_adview,
      income_goldcoin_service_give,
      income_goldcoin_total,

      income_points_register_give,
      income_points_consume_give,
      income_points_total,

      `income_total`,
      `income_tag`,
      `income_uptime`
      ) SELECT * FROM ', @temp_table, ';');
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
      SET @v_sql = CONCAT('DROP TABLE IF EXISTS ', @temp_table);
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      # 总统计
      CALL Statistics_AccountIncome_total(tag, error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

    END out_label;
  END
;;
DELIMITER ;




-- ------------------- 存储过程.-.1116.用户收益统计.sql end ------------------------ 

