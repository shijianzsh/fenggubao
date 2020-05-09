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