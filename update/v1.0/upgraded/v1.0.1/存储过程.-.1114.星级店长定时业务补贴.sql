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