-- -------------------------------
-- 星级店长补贴
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

            CALL Income_subsidy();

          end;
        end if;
      until done end repeat;
      close c_user;
    END out_label;
  END
;;
DELIMITER ;