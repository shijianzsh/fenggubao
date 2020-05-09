-- ----------------------------
-- 迁移锁定资产数据
-- ----------------------------
DROP PROCEDURE IF EXISTS `Move_lock`;
DELIMITER ;;
CREATE PROCEDURE `Move_lock`(OUT error INT(11))
  BEGIN

    declare done int default 0;
    declare c_user_id int default 0;
    declare c_amount DECIMAL(14,4) default 0;

    # 获取所有释放队列
    declare c_user cursor for
    select user_id, account_bonus_balance
    from
      zc_account as lq
    where
      account_tag = 0 and account_bonus_balance > 0;

    declare continue handler for not found set done = 1;
    declare continue handler for sqlexception set error = 1; -- 异常错误
    set error = 0;

    out_label: BEGIN

      open c_user;
      repeat fetch c_user into c_user_id,  c_amount;
      if not done
      then
        begin
          out_repeat:BEGIN
            insert IGNORE into zc_lock(user_id, total_amount, lock_amount) VALUE (c_user_id, c_amount, c_amount);
          END out_repeat;
        end;
      end if;
      until done end repeat;
      close c_user;
    END out_label;
  END
;;
DELIMITER ;


TRUNCATE TABLE zc_lock;
TRUNCATE TABLE zc_lock_queue;
CALL Move_lock(@error);
select @error;

update zc_account set account_bonus_expenditure=0, account_bonus_income = 0, account_bonus_balance = 0;
TRUNCATE TABLE zc_account_bonus_201812;
TRUNCATE TABLE zc_account_bonus_201901;

DROP PROCEDURE IF EXISTS `Move_lock`;