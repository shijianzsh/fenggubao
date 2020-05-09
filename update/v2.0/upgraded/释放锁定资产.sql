DROP PROCEDURE IF EXISTS `Release_lock`;
DELIMITER ;;
CREATE PROCEDURE `Release_lock`(OUT error INT(11))
BEGIN

    DECLARE done INT DEFAULT 0;
    DECLARE c_user_id INT DEFAULT 0;
    DECLARE c_lock_amount INT DEFAULT 0;

    # 获取所有释放队列
    DECLARE c_user CURSOR FOR
        SELECT m.id,
               a.account_bonus_balance
        FROM zc_account AS a
                 LEFT JOIN zc_member AS m ON a.user_id = m.id
        WHERE a.account_tag = 0
          AND a.account_bonus_balance > 0
          AND m.is_lock = 0;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; -- 异常错误
    SET error = 0;

    out_label:
    BEGIN

        SET @releaseBai = GetSetting('goldcoin_release_bai');
        IF @releaseBai <= 0 THEN
            LEAVE out_label;
        END IF;

        OPEN c_user;
        REPEAT
            FETCH c_user INTO c_user_id, c_lock_amount;
            IF NOT done
            THEN
                BEGIN
                    out_repeat:
                    BEGIN

                        SET @releaseAmount = c_lock_amount * @releaseBai * 0.01;

                        # 添加明细
                        CALL AddAccountRecord(c_user_id, 'bonus', 254, -@releaseAmount, UNIX_TIMESTAMP(), '', 'sdjcsf', @releaseBai, error);
                        IF error
                        THEN
                            LEAVE out_label;
                        END IF;

                        # 添加明细
                        CALL AddAccountRecord(c_user_id, 'goldcoin', 113, @releaseAmount, UNIX_TIMESTAMP(), '', 'sdjcsf', @releaseBai, error);

                        IF error
                        THEN
                            LEAVE out_label;
                        END IF;

                    END out_repeat;
                END;
            END IF;
        UNTIL done END REPEAT;
        CLOSE c_user;
    END out_label;
END
;;
DELIMITER ;