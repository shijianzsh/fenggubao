-- -------------------------------
-- 修复挖矿
-- -------------------------------
DROP PROCEDURE IF EXISTS `Fix_wk`;
DELIMITER ;;
CREATE PROCEDURE `Fix_wk`(IN miningTag INT(10),
                          OUT error INT(11))
BEGIN

    DECLARE done INT DEFAULT 0;
    DECLARE c_user_id INT DEFAULT 0;
    DECLARE c_amount DECIMAL(14, 4) DEFAULT 0;

    # 获取所有挖矿数据
    DECLARE c_mining CURSOR FOR
        SELECT m.id,
               mi.amount
        FROM zc_mining AS mi
                 LEFT JOIN zc_member AS m ON mi.user_id = m.id
        WHERE mi.tag = miningTag
          AND m.level = 2
          AND m.is_lock = 0
          AND m.id IS NOT NULL
          AND mi.user_id > 1;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; -- 异常错误
    SET error = 0;
    SET @count = 0;
    out_label:
    BEGIN


        OPEN c_mining;
        REPEAT
            FETCH c_mining
                INTO c_user_id, c_amount;
            IF NOT done
            THEN
                BEGIN
                    out_repeat:
                    BEGIN

                        SELECT ifnull(sum(record_amount), 0) INTO @recordAmount
                        FROM zc_account_goldcoin_201906
                        WHERE user_id = c_user_id
                          AND record_attach LIKE CONCAT('%"miningTag":"', 20190610, '"%');

                        IF @recordAmount > 0 THEN
                            LEAVE out_repeat;
                        END IF;


                        -- 分发流通资产
                        SET @circulateBai = GetSetting('mine_circulate_bai');
                        IF @circulateBai > 0 THEN
                            -- 结算给用户
                            CALL AddAccountRecord(c_user_id, 'goldcoin', 115, c_amount * @circulateBai * 0.01, UNIX_TIMESTAMP(),
                                                  concat('{"miningTag":"', miningTag, '"}'), 'wk', 0, error);
                            IF error THEN
                                LEAVE out_label;
                            END IF;
                        END IF;

                        -- 从矿池中扣除
                        CALL AddAccountRecord(1, 'points', 550, -c_amount, UNIX_TIMESTAMP(),
                                              concat('{"user_id":"', c_user_id, '","miningTag":"', miningTag, '"}'), 'wk', 0,
                                              error);
                        IF error
                        THEN
                            LEAVE out_label;
                        END IF;

                        # 0 表示挖矿所得
                        SET @orderId = 0;

                        # 分发感恩奖
                        CALL Income_thanksgiving(c_user_id, c_amount, @orderId, error);
                        IF error
                        THEN
                            LEAVE out_label;
                        END IF;

                        SET @count = @count + 1;

                    END out_repeat;
                END;
            END IF;
        UNTIL done END REPEAT;
        CLOSE c_mining;
    END out_label;
    SELECT @count;
END
;;
DELIMITER ;


CALL Fix_wk(20190610, @error);
SELECT @error;

DROP PROCEDURE IF EXISTS `Fix_wk`;


-- -------------------------------
-- 修复农场
-- -------------------------------
DROP PROCEDURE IF EXISTS `Fix_consume`;
DELIMITER ;;
CREATE PROCEDURE `Fix_consume`(
    OUT error INT(11)
)
BEGIN

    DECLARE done INT DEFAULT 0;
    DECLARE c_user_id INT DEFAULT 0;
    DECLARE c_amount INT DEFAULT 0;
    DECLARE c_performance INT DEFAULT 0;

    DECLARE c_user CURSOR FOR
        SELECT m.id,
               c.amount                                                                                                                            AS consume_amount,
               sum(op.price_cash * op.product_quantity * op.performance_bai_cash * 0.01 * if(ifnull(o.`discount`, 0) = 0, 10, o.`discount`) * 0.1) AS performance
        FROM zc_consume AS c
                 LEFT JOIN zc_member AS m ON c.user_id = m.id
                 LEFT JOIN zc_orders AS o ON m.id = o.uid
                 LEFT JOIN zc_order_product AS op ON o.id = op.order_id
        WHERE o.order_status IN (1, 3, 4)
        GROUP BY c.user_id
        HAVING consume_amount <> performance;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; -- 异常错误
    SET error = 0;

    out_label:
    BEGIN

        OPEN c_user;
        REPEAT
            FETCH c_user
                INTO c_user_id, c_amount, c_performance;
            IF NOT done
            THEN
                UPDATE zc_consume SET amount = c_performance WHERE user_id = c_user_id;

                CALL Income_add(c_user_id, 0, error);
                IF error THEN
                    LEAVE out_label;
                END IF;

            END IF;
        UNTIL done END REPEAT;
        CLOSE c_user;
    END out_label;
END
;;
DELIMITER ;


CALL Fix_consume(@error);
SELECT @error;

DROP PROCEDURE IF EXISTS `Fix_consume`;

-- -------------------------------
-- 修复SKN
-- -------------------------------
DROP PROCEDURE IF EXISTS `Fix_skn`;
DELIMITER ;;
CREATE PROCEDURE `Fix_skn`(
    OUT error INT(11)
)
BEGIN

    DECLARE done INT DEFAULT 0;
    DECLARE c_user_id INT DEFAULT 0;
    DECLARE c_order_id INT DEFAULT 0;

    DECLARE c_user CURSOR FOR
        SELECT m.id,
               o.id
        FROM zc_orders AS o
                 LEFT JOIN zc_member AS m ON o.uid = m.id
                 LEFT JOIN zc_account_enjoy_201906 AS ae ON ae.record_attach LIKE CONCAT('%"serial_num":"', o.id, '"%')
        WHERE o.order_status IN (1, 3, 4)
          AND ae.record_id IS NULL
          AND FROM_UNIXTIME(o.pay_time, '%Y%m%d') IN (20190605, 20190606, 20190607, 20190608, 20190609, 20190610, 20190611);

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; -- 异常错误
    SET error = 0;

    out_label:
    BEGIN

        OPEN c_user;
        REPEAT
            FETCH c_user
                INTO c_user_id, c_order_id;
            IF NOT done
            THEN
                -- 赠送SKN
                CALL Income_skn(c_order_id, error);
                IF error THEN
                    LEAVE out_label;
                END IF;
            END IF;
        UNTIL done END REPEAT;
        CLOSE c_user;
    END out_label;
END
;;
DELIMITER ;


CALL Fix_skn(@error);
SELECT @error;

DROP PROCEDURE IF EXISTS `Fix_skn`;