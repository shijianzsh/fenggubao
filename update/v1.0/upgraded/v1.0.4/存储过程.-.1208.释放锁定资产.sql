-- -------------------------------
-- 收益 - 累计所有收益
-- -------------------------------
DROP PROCEDURE IF EXISTS `Release_lock`;
DELIMITER ;;
CREATE PROCEDURE `Release_lock`(
  OUT error             INT(11)
)
  BEGIN

    declare done int default 0;
    declare c_queue_id int default 0;
    declare c_user_id int default 0;
    declare c_release_amount int default 0;

    # 获取所有释放队列
    declare c_user cursor for
    select lq.id, lq.user_id, lq.total_amount * lq.release_rate * 0.01
    from
      zc_lock_queue as lq
        left join zc_member as m on lq.user_id = m.id
    where
      lq.total_amount > lq.release_amount
      AND lq.tag <> FROM_UNIXTIME(UNIX_TIMESTAMP(), '%Y%m%d')
      AND m.is_lock = 0;

    declare continue handler for not found set done = 1;
    declare continue handler for sqlexception set error = 1; -- 异常错误
    set error = 0;

    out_label: BEGIN


      SET @tag = FROM_UNIXTIME(UNIX_TIMESTAMP(), '%Y%m%d');

      open c_user;
      repeat fetch c_user into c_queue_id, c_user_id,  c_release_amount;
      if not done
      then
        begin
          out_repeat:BEGIN

            UPDATE zc_lock_queue SET
              release_amount = release_amount + c_release_amount,
              `tag` = @tag,
              `uptime` = UNIX_TIMESTAMP()
            WHERE id = c_queue_id;

            UPDATE zc_lock SET
              release_amount = release_amount + c_release_amount,
              lock_amount = lock_amount - c_release_amount,
              `uptime` = UNIX_TIMESTAMP()
            WHERE user_id = c_user_id;

            # 添加明细
            CALL AddAccountRecord(c_user_id, 'goldcoin', 113, c_release_amount , UNIX_TIMESTAMP(), '', 'sdjcsf', 0, error);

            if error
            then
              leave out_label;
            end if;

          END out_repeat;
        end;
      end if;
      until done end repeat;
      close c_user;
    END out_label;
  END
;;
DELIMITER ;



