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