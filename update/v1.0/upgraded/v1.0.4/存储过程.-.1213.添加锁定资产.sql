DROP PROCEDURE IF EXISTS `AddLockGoldcoin`;
DELIMITER ;;
CREATE PROCEDURE `AddLockGoldcoin`(
  IN userId   INT(11),
  IN lockAmount FLOAT,
  OUT error INT(11)
)
  BEGIN
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      INSERT IGNORE INTO `zc_lock`(`user_id`, `tag`) VALUE (userId, '0');
      UPDATE `zc_lock`
      SET
        `total_amount` = `total_amount` + lockAmount,
        `lock_amount` = `lock_amount` + lockAmount,
        `uptime` = UNIX_TIMESTAMP()
      WHERE `user_id` = userId AND `tag` = 0;

      SET @releaseBai = GetSetting('goldcoin_release_bai');

      INSERT INTO `zc_lock_queue` VALUE(NULL, userId, lockAmount, 0, @releaseBai, 0, '', UNIX_TIMESTAMP(), 0);

    END out_label;
  END
;;
DELIMITER ;



