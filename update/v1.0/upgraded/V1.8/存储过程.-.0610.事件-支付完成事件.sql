-- -------------------------------
-- 支付完成事件
-- -------------------------------
DROP PROCEDURE IF EXISTS `Event_paid`;
DELIMITER ;;
CREATE PROCEDURE `Event_paid`(IN orderId INT(11), OUT error INT(11))
BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label:
    BEGIN

        # 添加支付完成队列
        INSERT INTO `zc_paid_queue` VALUES (NULL, orderId, 0, UNIX_TIMESTAMP(), 0, 0);

    END out_label;
END
;;
DELIMITER ;

-- ----------------------------
-- 定时执行支付完成队列
-- ----------------------------
DROP EVENT IF EXISTS `everysecond_Paid`;
DELIMITER ;;
CREATE EVENT `everysecond_Paid`
    ON SCHEDULE EVERY 5 SECOND
        STARTS '2018-12-01 00:00:00'
    ON COMPLETION PRESERVE
    ENABLE DO
    BEGIN
        CALL Paid_queue(@error);
    END
;;
DELIMITER ;

-- -------------------------------
-- 执行支付完成队列
-- -------------------------------
DROP PROCEDURE IF EXISTS `Paid_queue`;
DELIMITER ;;
CREATE PROCEDURE `Paid_queue`(OUT error INT(11))
BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label:
    BEGIN

        # 检查是否有队列正在执行
        SELECT count(0) INTO @hasLock
        FROM zc_paid_queue
        WHERE queue_status = 1;
        IF @hasLock
        THEN
            LEAVE out_label;
        END IF;

        # 检查是否有队列需要执行
        SELECT count(0) INTO @hasQueue
        FROM zc_paid_queue
        WHERE queue_status = 0;
        IF @hasQueue = 0
        THEN
            LEAVE out_label;
        END IF;

        SELECT id,
               order_id
               INTO @queueId, @orderId
        FROM zc_paid_queue
        WHERE queue_status = 0
        ORDER BY id ASC
        LIMIT 1;

        UPDATE zc_paid_queue
        SET queue_status    = 1,
            queue_starttime = unix_timestamp()
        WHERE id = @queueId;

        # 开启事务
        START TRANSACTION;

        # 执行支付完成
        CALL Execute_paid(@orderId, error);

        IF error
        THEN
            ROLLBACK; # 回滚
            UPDATE zc_paid_queue
            SET queue_status  = 2,
                queue_endtime = unix_timestamp()
            WHERE id = @queueId;
        ELSE
            COMMIT; # 提交
            UPDATE zc_paid_queue
            SET queue_status  = 3,
                queue_endtime = unix_timestamp()
            WHERE id = @queueId;
        END IF;

    END out_label;
END
;;
DELIMITER ;


-- -------------------------------
-- 支付完成事件
-- -------------------------------
DROP PROCEDURE IF EXISTS `Execute_paid`;
DELIMITER ;;
CREATE PROCEDURE `Execute_paid`(IN orderId INT(11), OUT error INT(11))
BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label:
    BEGIN

        SET @orderStatus = 0;
        SELECT order_status INTO @orderStatus FROM zc_orders WHERE id = orderId;
        IF @orderStatus <> 1 THEN
            LEAVE out_label;
        END IF;

        SELECT count(0) INTO @isGjj
        FROM zc_order_product AS op
                 LEFT JOIN zc_product_affiliate AS pa ON op.product_id = pa.product_id
        WHERE op.order_id = orderId
          AND pa.block_id = 7
        LIMIT 1;

        IF @isGjj > 0 THEN
            -- 分发谷聚金代理专区收益
            CALL Gjj_Income(orderId, error);
            IF error THEN
                LEAVE out_label;
            END IF;
        ELSE
            -- 激活个人代理
            CALL Event_activated(orderId, error);
            IF error THEN
                LEAVE out_label;
            END IF;
            -- 赠送SKN
            CALL Income_skn(orderId, error);
            IF error THEN
                LEAVE out_label;
            END IF;
            -- 累计业绩
            CALL Performance_calculation(orderId, error);
            IF error THEN
                LEAVE out_label;
            END IF;
        END IF;

    END out_label;
END
;;
DELIMITER ;