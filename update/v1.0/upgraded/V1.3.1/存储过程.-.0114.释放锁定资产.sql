DROP PROCEDURE IF EXISTS `Release`;

-- -------------------------------
-- 收益 - 释放挖矿锁定金额
-- -------------------------------
DROP PROCEDURE IF EXISTS `Release_lock`;
DELIMITER ;;
CREATE PROCEDURE `Release_lock`(
  OUT error             INT(11)
)
  BEGIN

    declare done int default 0;
    declare c_user_id int default 0;
    declare c_lock_amount int default 0;

    # 获取所有释放队列
    declare c_user cursor for
    select
      m.id,
      a.account_bonus_balance
    from
      zc_account as a
        left join zc_member as m on a.user_id = m.id
    where
      a.account_tag = 0
      AND a.account_bonus_balance > 0
      AND m.is_lock = 0;

    declare continue handler for not found set done = 1;
    declare continue handler for sqlexception set error = 1; -- 异常错误
    set error = 0;

    out_label: BEGIN

      SET @releaseBai = GetSetting('goldcoin_release_bai');
      IF @releaseBai <= 0 THEN
        leave out_label;
      END IF;

      open c_user;
      repeat fetch c_user into c_user_id,  c_lock_amount;
      if not done
      then
        begin
          out_repeat:BEGIN

            SET @releaseAmount = c_lock_amount * @releaseBai * 0.01;

            # 添加明细
            CALL AddAccountRecord(c_user_id, 'bonus', 254, -@releaseAmount, UNIX_TIMESTAMP(), '', 'sdjcsf', @releaseBai, error);
            if error
            then
              leave out_label;
            end if;

            # 添加明细
            CALL AddAccountRecord(c_user_id, 'goldcoin', 113, @releaseAmount, UNIX_TIMESTAMP(), '', 'sdjcsf', @releaseBai, error);

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



-- ----------------------------
-- Event structure for everyday_Release
-- ----------------------------
DROP EVENT IF EXISTS `everyday_Release`;
-- DELIMITER ;;
-- CREATE EVENT `everyday_Release`
--   ON SCHEDULE EVERY 1 DAY
--     STARTS '2019-01-01 00:00:00'
--   ON COMPLETION PRESERVE
--   ENABLE DO BEGIN
--
--   SET @switch = GetSetting(CONCAT('goldcoin_release_switch'));
--   IF @switch = '开启'
--   THEN
--     START TRANSACTION;
--     CALL Release_lock(@error);
--     IF @error THEN
--       ROLLBACK ;
--     ELSE
--       COMMIT ;
--     END IF;
--   END IF;
--
-- END
-- ;;
-- DELIMITER ;