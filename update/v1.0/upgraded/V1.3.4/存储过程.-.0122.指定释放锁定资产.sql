-- ----------------------------
-- 强行释放原始锁定资产
-- ----------------------------
DROP PROCEDURE IF EXISTS `Release_force`;
DELIMITER ;;
CREATE PROCEDURE `Release_force`(IN userId INT(11), IN releaseAmount DECIMAL(14,4), OUT error INT(11))
  BEGIN
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      if releaseAmount <= 0 then
        leave out_label;
      end if;

      select count(0) into  @hasLock from zc_lock where user_id = userId;
      if @hasLock = 0 then
        leave out_label;
      end if;

      select lock_amount into  @lockAmount from zc_lock where user_id = userId;
      if  @lockAmount < releaseAmount then
        leave out_label;
      end if;

      # 添加明细
      CALL AddAccountRecord(userId, 'goldcoin', 113, releaseAmount, UNIX_TIMESTAMP(), '', 'sdjcsf', 0, error);
      if error then
        leave out_label;
      end if;

      UPDATE zc_lock
      SET
        release_amount = release_amount + releaseAmount,
        lock_amount = lock_amount - releaseAmount,
        tag = FROM_UNIXTIME(UNIX_TIMESTAMP(), '%Y%m%d'),
        uptime = UNIX_TIMESTAMP()
      WHERE user_id = userId;

    END out_label;
  END
;;
DELIMITER ;