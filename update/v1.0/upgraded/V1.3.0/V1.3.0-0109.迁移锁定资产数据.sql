-- ----------------------------
-- 迁移锁定资产数据
-- ----------------------------
DROP PROCEDURE IF EXISTS `Move_lock`;
DELIMITER ;;
CREATE PROCEDURE `Move_lock`(OUT error INT(11))
  BEGIN

    declare done int default 0;
    declare c_queue_id int default 0;
    declare c_user_id int default 0;
    declare c_amount DECIMAL(14,4) default 0;
    declare c_addtime int default 0;

    # 获取所有释放队列
    declare c_user cursor for
    select lq.id, lq.user_id, lq.total_amount,lq.addtime
    from
      zc_lock_queue as lq
    where
      lq.tag <> FROM_UNIXTIME(UNIX_TIMESTAMP(), '%Y%m00')
      order by lq.addtime asc;

    declare continue handler for not found set done = 1;
    declare continue handler for sqlexception set error = 1; -- 异常错误
    set error = 0;

    out_label: BEGIN

      SET @tag = FROM_UNIXTIME(UNIX_TIMESTAMP(), '%Y%m00');
      open c_user;
      repeat fetch c_user into c_queue_id, c_user_id,  c_amount, c_addtime;
      if not done
      then
        begin
          out_repeat:BEGIN

            UPDATE zc_lock_queue SET
              `tag` = @tag,
              `uptime` = UNIX_TIMESTAMP()
            WHERE id = c_queue_id;

            # 添加明细
            CALL AddAccountRecord(c_user_id, 'bonus', 203, c_amount , c_addtime, '', 'xfzs', 0, error);

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

update zc_account set account_bonus_expenditure=0, account_bonus_income = 0, account_bonus_balance = 0;
update zc_lock_queue set tag = 0;
TRUNCATE TABLE zc_account_bonus_201812;
TRUNCATE TABLE zc_account_bonus_201901;

CALL Move_lock(@error);
select @error;

DROP PROCEDURE IF EXISTS `Move_lock`;