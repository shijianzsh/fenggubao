DROP PROCEDURE IF EXISTS `Release`;

-- -------------------------------
-- 收益 - 释放原始锁定资产
-- -------------------------------
DROP PROCEDURE IF EXISTS `Release_oldLock`;
DELIMITER ;;
CREATE PROCEDURE `Release_oldLock`(
  OUT error             INT(11)
)
  BEGIN

    declare done int default 0;
    declare c_user_id int default 0;
    declare c_id int default 0;
    declare c_total_amount DECIMAL(14,4) default 0;
    declare c_release_amount DECIMAL(14,4) default 0;
    declare c_lock_amount DECIMAL(14,4) default 0;

    # 获取所有释放队列
    declare c_user cursor for
    select
      m.id,
      l.id,
      l.total_amount,
      l.lock_amount
    from
      zc_lock as l
        left join zc_member as m on l.user_id = m.id
    where
      l.tag <> FROM_UNIXTIME(UNIX_TIMESTAMP(), '%Y%m%d')
      AND l.lock_amount > 0
      AND m.is_lock = 0;

    declare continue handler for not found set done = 1;
    declare continue handler for sqlexception set error = 1; -- 异常错误
    set error = 0;

    out_label: BEGIN

      open c_user;
      repeat fetch c_user into c_user_id, c_id, c_total_amount, c_lock_amount;
      if not done
      then
        begin
          out_repeat:BEGIN

            SET  @releaseBai = 0.5;

            SET @releaseAmount = c_total_amount * @releaseBai * 0.01;
            if @releaseAmount > c_lock_amount THEN
              set @releaseAmount = c_lock_amount;
            end if;

            # 添加明细
            CALL AddAccountRecord(c_user_id, 'goldcoin', 113, @releaseAmount, UNIX_TIMESTAMP(), '', 'sdjcsf', @releaseBai, error);

            if error
            then
              leave out_label;
            end if;

            UPDATE zc_lock
            SET
              release_amount = release_amount + @releaseAmount,
              lock_amount = lock_amount - @releaseAmount,
              tag = FROM_UNIXTIME(UNIX_TIMESTAMP(), '%Y%m%d'),
              uptime = UNIX_TIMESTAMP()
            WHERE id = c_id;

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
DROP EVENT IF EXISTS `everyday_ReleaseOld`;
-- DELIMITER ;;
-- CREATE EVENT `everyday_ReleaseOld`
--   ON SCHEDULE EVERY 1 DAY
--     STARTS '2019-01-01 00:00:00'
--   ON COMPLETION PRESERVE
--   ENABLE DO BEGIN
--
--   SET @switch = GetSetting(CONCAT('goldcoin_release_switch'));
--   IF @switch = '开启'
--   THEN
--     START TRANSACTION;
--     CALL Release_oldLock(@error);
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