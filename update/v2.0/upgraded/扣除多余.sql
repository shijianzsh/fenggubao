DROP PROCEDURE IF EXISTS `Income_back`;
DELIMITER ;;
CREATE PROCEDURE `Income_back`(OUT error INT(11), IN incomeBei DECIMAL(3, 1), IN incomeMinAmount DECIMAL(14, 4), IN incomeMaxAmount DECIMAL(14, 4))
BEGIN

    DECLARE done INT DEFAULT 0;
    DECLARE c_id INT DEFAULT 0;
    DECLARE c_user_id INT DEFAULT 0;
    DECLARE c_amount DECIMAL(14, 4) DEFAULT 0;

    # 获取当日所有挖矿队列
    DECLARE c_queue CURSOR FOR
        SELECT id, user_id, amount * incomeBei - income_amount
        FROM zc_consume_bak
        WHERE amount >= incomeMinAmount
          AND amount < incomeMaxAmount
          AND amount * incomeBei < income_amount;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; -- 异常错误
    SET error = 0;

    out_label:
    BEGIN

        OPEN c_queue;
        REPEAT
            FETCH c_queue INTO c_id, c_user_id, c_amount;
            IF NOT done
            THEN
                BEGIN
                    out_repeat:
                    BEGIN

                        -- 添加明细
                        CALL AddAccountRecord(c_user_id, 'goldcoin', 151, c_amount, unix_timestamp(), '', '退回多余收益', 0, error);
                        IF error THEN
                            LEAVE out_label;
                        END IF;

                        UPDATE zc_consume_bak
                        SET income_amount = income_amount + c_amount,
                            is_out        = 1,
                            dynamic_out   = 1
                        WHERE id = c_id;

                    END out_repeat;
                END;
            END IF;
        UNTIL done END REPEAT;
        CLOSE c_queue;

    END out_label;
END
;;
DELIMITER ;

CALL Income_back(@error, 2, 0, 10000);
SELECT @error;

CALL Income_back(@error, 2.5, 10000, 30000);
SELECT @error;

CALL Income_back(@error, 3, 30000, 50000);
SELECT @error;

CALL Income_back(@error, 3.5, 50000, 100000);
SELECT @error;

CALL Income_back(@error, 4, 100000, 10000000);
SELECT @error;
