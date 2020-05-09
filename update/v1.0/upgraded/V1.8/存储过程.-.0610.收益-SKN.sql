-- -------------------------------
-- 收益 - SKN
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income_skn`;
DELIMITER ;;
CREATE PROCEDURE `Income_skn`(IN orderId INT(11), OUT error INT(11))
BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; -- 异常错误
    SET error = 0;

    out_label:
    BEGIN

        SELECT o.uid,
               floor(sum(op.price_cash * op.product_quantity * op.performance_bai_cash * 0.01 * if(ifnull(o.`discount`, 0) = 0, 10, o.`discount`) * 0.1) /
                     b.block_enjoy_order_amount) * b.block_enjoy_give_amount
               INTO @userId, @enjoyAmount
        FROM zc_orders AS o
                 LEFT JOIN zc_order_product AS op ON o.id = op.order_id
                 LEFT JOIN zc_block AS b ON o.producttype = b.block_id
        WHERE o.id = orderId
          AND order_status IN (1, 3, 4);

        IF @userId IS NULL OR @enjoyAmount IS NULL OR @enjoyAmount <= 0 THEN
            LEAVE out_label;
        END IF;

        -- 添加明细
        CALL AddAccountRecord(@userId, 'enjoy', 803, @enjoyAmount, UNIX_TIMESTAMP(),
                              concat('{"serial_num":"', orderId, '"}'), '消费赠送澳洲SKN股数', 0, error);
        IF error THEN
            LEAVE out_label;
        END IF;

    END out_label;
END
;;
DELIMITER ;
