-- -------------------------------
-- 广告点击事件
-- -------------------------------
DROP PROCEDURE IF EXISTS `Event_adViewed`;
DELIMITER ;;
CREATE PROCEDURE `Event_adViewed`(
  IN  userId  INT(11),
  IN  adId    int(11),
  OUT status  tinyint(1),
  OUT message varchar(255),
  OUT error   INT(11)
)
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;
    SET status = 0;

    out_label: BEGIN


      call AdViewed_checkTime(message, error);
      if error or message <> ''
      then
        leave out_label;
      end if;

      select count(0)
      into @hasViewed
      from zc_ad_view
      where
        ad_id = adId
        and user_id = userId
        and from_unixtime(view_addtime, '%Y%m%d') = from_unixtime(unix_timestamp(), '%Y%m%d');

      if @hasViewed > 0
      then
        set message = '你今日已经点过此广告了';
        leave out_label;
      end if;

      select count(0)
      into @hasUser
      from zc_member
      where
        id = userId
        and is_lock = 0
        and (`level` in (1, 2) or `role` in (3));

      if @hasUser = 0
      then
        set message = '你不在广告系统计划范围内';
        leave out_label;
      end if;

      select
        ifnull(ad_amount, 0) * 1,
        ifnull(ad_amount_credits, 0) * 1,
        ifnull(ad_amount_max, 0) - ifnull(sum(view_cash), 0),
        ifnull(ad_amount_credits, 0) - ifnull(sum(view_goldcoin), 0),
        ifnull(ad_amount_max, 0) - ifnull(sum(view_cash), 0),
        ifnull(ad_amount_credits, 0) - ifnull(sum(view_goldcoin), 0)
      into
        @cashPrice,
        @goldcoinPrice,
        @memberCashBalance,
        @memberGoldcoinBalance,
        @roleCashBalance,
        @roleGoldcoinBalance
      from
        zc_ad as a
        left join zc_ad_view as av on a.ad_id = av.ad_id
      where
        a.ad_id = adId;

      if @cashPrice <= 0 and @goldcoinPrice <= 0
      then
        set message = '此广告不参与广告系统';
        leave out_label;
      end if;

      set @userTag = AdViewed_getUserTag(userId);
      set @priceBai = GetSetting(concat('ad_price_bai_', @userTag));
      if @priceBai <= 0
      then
        set message = '你的身份不在广告收益系统计划范围内';
        leave out_label;
      end if;

      set @cashBalance = 0, @goldcoinBalance = 0;
      if @userTag in ('company')
      then
        set @cashBalance = @roleCashBalance, @goldcoinBalance = @roleGoldcoinBalance;
      elseif @userTag in (1, 2)
        then
          set @cashBalance = @memberCashBalance, @goldcoinBalance = @memberGoldcoinBalance;
      end if;

      if @cashBalance <= 0 and @goldcoinBalance <= 0
      then
        set message = '此广告已封顶';
        leave out_label;
      end if;

      select
        ifnull(sum(view_cash), 0),
        ifnull(sum(view_goldcoin), 0)
      into
        @userTodayCashViewed,
        @userTodayGoldcoinViewed
      from zc_ad_view
      where
        user_id = userId
        and from_unixtime(view_addtime, '%Y%m%d') = from_unixtime(unix_timestamp(), '%Y%m%d');

      set @userTodayCashMax = GetSetting(concat('ad_cash_max_', @userTag));
      set @userTodayGoldcoinMax = GetSetting(concat('ad_goldcoin_max_', @userTag));
      set @userTodayCashBalance = @userTodayCashMax - @userTodayCashViewed;
      set @userTodayGoldcoinBalance = @userTodayGoldcoinMax - @userTodayGoldcoinViewed;

      if @userTodayCashBalance <= 0 and @userTodayGoldcoinBalance <= 0
      then
        set message = concat('你今日广告收益已封顶');
        leave out_label;
      end if;

      set @adCurrency = '';
      if (@userTodayCashBalance > 0 and @cashBalance > 0) and (@userTodayGoldcoinBalance <= 0 or @goldcoinBalance <= 0)
      then
        set @adCurrency = 'cash';
      end if;

      if (@userTodayCashBalance <= 0 or @cashBalance <= 0) and (@userTodayGoldcoinBalance > 0 and @goldcoinBalance > 0)
      then
        set @adCurrency = 'goldcoin';
      end if;

      if @adCurrency = ''
      then
        set @cashBai = GetSetting(concat('ad_cash_bai_', @userTag));
        set @goldcoinBai = GetSetting(concat('ad_goldcoin_bai_', @userTag));
        if @cashBai > 0 and @goldcoinBai > 0
        then
          set @rand = FLOOR(0 + RAND() * (@cashBai + @goldcoinBai + 1));
          if @rand <= @cashBai
          then
            set @adCurrency = 'cash';
          else
            set @adCurrency = 'goldcoin';
          end if;
        elseif @cashBai > 0
          then
            set @adCurrency = 'cash';
        elseif @goldcoinBai > 0
          then
            set @adCurrency = 'goldcoin';
        end if;
      end if;

      if @adCurrency = ''
      then
        set message = '你的身份不在广告系统计划范围内';
      end if;

      set @adAmount = 0;
      set @adCurrencyName = '';

      if @adCurrency = 'cash'
      then
        set @adAmount = @cashPrice * @priceBai * 0.01;
        if @userTodayCashBalance > @cashBalance
        then
          set @userTodayCashBalance = @cashBalance;
        end if;
        if @adAmount > @userTodayCashBalance
        then
          set @adAmount = @userTodayCashBalance;
        end if;
        set @adCurrencyName = '米宝';
      elseif @adCurrency = 'goldcoin'
        then
          set @adAmount = @goldcoinPrice * @priceBai * 0.01;
          if @userTodayGoldcoinBalance > @goldcoinBalance
          then
            set @userTodayGoldcoinBalance = @goldcoinBalance;
          end if;
          if @adAmount > @userTodayGoldcoinBalance
          then
            set @adAmount = @userTodayGoldcoinBalance;
          end if;
          set @adCurrencyName = '兑换券';
      end if;

      set @adAmount = truncate(@adAmount, 4);

      if @adAmount <= 0
      then
        set message = concat('未获得广告收益');
        leave out_label;
      end if;


      set @adAction = '';
      if @adCurrency = 'cash'
      then
        insert into zc_ad_view (ad_id, user_id, view_cash, view_addtime)
        values (adId, userId, @adAmount, unix_timestamp());
        set @adAction = '316';
      elseif @adCurrency = 'goldcoin'
        then
          set @adAction = '105';
          insert into zc_ad_view (ad_id, user_id, view_goldcoin, view_addtime)
          values (adId, userId, @adAmount, unix_timestamp());
      end if;

      if @adCurrency in ('cash', 'goldcoin')
      then
        #添加明细
        CALL AddAccountRecord(userId, @adCurrency, @adAction, @adAmount, UNIX_TIMESTAMP(),
                              concat('{"from_uid":"', 1, '", "ad_id":"', adId, '"}'), '广告收益', 0, error);
        if error = 0
        then
          set message = concat('观看成功, 收益', @adAmount);
          if @adCurrency = 'cash'
          then
            set message = concat(message, '现金');
          end if;
          if @adCurrency = 'goldcoin'
          then
            set message = concat(message, '兑换券');
          end if;
          set status = 1;
          leave out_label;
        end if;
      end if;
      set message = '操作失败';
      leave out_label;

    END out_label;
  END
;;
DELIMITER ;

drop function if exists `AdViewed_getUserTag`;
delimiter ;;
create function `AdViewed_getUserTag`(userId int(11))
  returns varchar(50)
  begin
    select
      `level`,
      `role`,
      `is_partner`
    into @userLevel, @userRole, @userPartner
    from zc_member
    where
      id = userid
      and is_lock = 0
      and (`level` in (1, 2) or `role` in (3));

    if @userRole = 4
    then
      set @result = 'company';
    elseif @userRole = 3
      then
        set @result = 'service';
    elseif @userPartner = 1
      then
        set @result = 'partner';
    else
      set @result = @userLevel;
    end if;
    return @result;
  end
;;
delimiter ;



-- -------------------------------
-- 广告系统时间验证
-- -------------------------------
DROP PROCEDURE IF EXISTS `AdViewed_checkTime`;
DELIMITER ;;
CREATE PROCEDURE `AdViewed_checkTime`(
  OUT message varchar(255),
  OUT error   INT(11)
)
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;
    set message = '';

    out_label: BEGIN

      if GetSetting('ad_switch') != '开启'
      then
        set message = GetSetting('ad_close_tip');
        if message = ''
        then
          set message = '广告系统关闭';
        end if;
        leave out_label;
      end if;

      set @adDays = GetSetting('ad_days');
      if @adDays not in ('工作日', '周末', '每天')
      then
        set message = '广告系统未开启';
        leave out_label;
      end if;

      set @nowWeek = from_unixtime(unix_timestamp(), '%w');
      if @adDays = '工作日' and @nowWeek in (0, 6)
      then
        set message = '广告系统只在工作日开启';
        leave out_label;
      end if;
      if @adDays = '周末' and @nowWeek not in (0, 6)
      then
        set message = '广告系统只在周末开启';
        leave out_label;
      end if;

      set @nowHour = from_unixtime(unix_timestamp(), '%H');
      set @startHour = GetSetting('ad_start') * 1;
      if @nowHour < @startHour
      then
        set message = concat('今日广告系统开始时间:', @startHour, '点');
        leave out_label;
      end if;

      set @endHour = GetSetting('ad_end') * 1;
      if @nowHour >= @endHour
      then
        set message = concat('今日广告系统结束时间:', @endHour, '点');
        leave out_label;
      end if;


    END out_label;
  END
;;
DELIMITER ;
