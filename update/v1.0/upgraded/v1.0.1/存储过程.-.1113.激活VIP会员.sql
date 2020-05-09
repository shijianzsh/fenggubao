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