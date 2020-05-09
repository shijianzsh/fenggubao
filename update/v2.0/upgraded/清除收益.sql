DROP PROCEDURE IF EXISTS `Clear_consume`;
DELIMITER ;;
CREATE PROCEDURE `Clear_consume`(OUT error INT(11))
BEGIN

    DECLARE done INT DEFAULT 0;
    DECLARE c_id INT DEFAULT 0;

    # 获取当日所有挖矿队列
    DECLARE c_queue CURSOR FOR
        SELECT id FROM zc_consume WHERE amount > 0 OR income_amount > 0 OR income_amount > 0 OR machine_amount_4 > 0;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; -- 异常错误
    SET error = 0;

    out_label:
    BEGIN

        OPEN c_queue;
        REPEAT
            FETCH c_queue INTO c_id;

            IF NOT done
            THEN
                BEGIN
                    out_repeat:
                    BEGIN
                        INSERT IGNORE INTO `zc_consume_bak`
                        SELECT NULL,
                               id,
                               user_id,
                               `level`,
                               amount,
                               amount_old,
                               income_amount,
                               is_out,
                               dynamic_out,
                               uptime,
                               machine_amount,
                               machine_amount_1,
                               machine_amount_2,
                               machine_amount_3,
                               machine_amount_4,
                               machine_amount_5,
                               machine_amount_uptime,
                               dynamic_worth,
                               static_worth
                        FROM zc_consume
                        WHERE id = c_id
                        LIMIT 1;
                        UPDATE `zc_consume`
                        SET `level`               = 0,
                            `amount`              = 0,
                            `amount_old`          = 0,
                            `income_amount`       = 0,
                            `uptime`              = unix_timestamp(),
                            machine_amount        = 0,
                            machine_amount_1      = 0,
                            machine_amount_2      = 0,
                            machine_amount_3      = 0,
                            machine_amount_4      = 0,
                            machine_amount_5      = 0,
                            machine_amount_uptime = unix_timestamp(),
                            dynamic_worth         = 1,
                            static_worth          = 1
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

CALL Clear_consume(@error);

SELECT @error;

UPDATE zc_member SET `is_tt` = 0;