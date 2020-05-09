DROP PROCEDURE IF EXISTS `GoldcoinToSupply`;
DELIMITER ;;
CREATE PROCEDURE `GoldcoinToSupply`(OUT error INT(11))
BEGIN

    DECLARE done INT DEFAULT 0;
    DECLARE c_user_id INT DEFAULT 0;
    DECLARE c_goldcoin_balance DECIMAL(14, 4) DEFAULT 0;

    # 获取当日所有挖矿队列
    DECLARE c_queue CURSOR FOR
        SELECT user_id, account_goldcoin_balance FROM zc_account WHERE account_goldcoin_balance > 0 AND account_tag = 0;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; -- 异常错误
    SET error = 0;

    out_label:
    BEGIN

        OPEN c_queue;
        REPEAT
            FETCH c_queue INTO c_user_id, c_goldcoin_balance;

            IF NOT done
            THEN
                BEGIN
                    out_repeat:
                    BEGIN

                        -- 添加明细
                        CALL AddAccountRecord(c_user_id, 'goldcoin', 162, -c_goldcoin_balance, unix_timestamp(), '', '锁仓', 0, error);
                        IF error THEN
                            LEAVE out_label;
                        END IF;

                        -- 添加明细
                        CALL AddAccountRecord(c_user_id, 'supply', 700, c_goldcoin_balance, unix_timestamp(), '', '锁仓', 0, error);
                        IF error THEN
                            LEAVE out_label;
                        END IF;


                    END out_repeat;
                END;
            END IF;
        UNTIL done END REPEAT;
        CLOSE c_queue;

    END out_label;
END
;;
DELIMITER ;


CALL GoldcoinToSupply(@error);
select @error;

DROP PROCEDURE IF EXISTS `GoldcoinToSupply`;