-- -------------------------------
-- 个人代理激活
-- -------------------------------
DROP PROCEDURE IF EXISTS `Event_activated`;
DELIMITER ;;
CREATE PROCEDURE `Event_activated`(IN orderId INT(11), OUT error INT(11))
BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label:
    BEGIN

        SET @performanceAmount = 0;
        SELECT o.uid,
               sum(op.price_cash * op.product_quantity * op.performance_bai_cash * 0.01 * if(ifnull(o.`discount`, 0) = 0, 10, o.`discount`) * 0.1)
               INTO
                   @userId,
                   @performanceAmount
        FROM zc_orders AS o
                 LEFT JOIN zc_order_product AS op ON o.id = op.order_id
                 LEFT JOIN zc_member AS m ON o.uid = m.id
        WHERE o.id = orderId
          AND order_status IN (1, 3, 4);

        IF @performanceAmount <= 0
        THEN
            LEAVE out_label;
        END IF;

        SET @performancePortionBase = GetSetting(concat('active_member_min_amount'));
        SET @isLock = 0;
        SELECT count(0) INTO @isLock
        FROM zc_member
        WHERE id = @userId
          AND is_lock = 1;
        IF @isLock
        THEN
            LEAVE out_label;
        END IF;

        SET @userLevel = 0;
        SELECT `level` INTO @userLevel
        FROM zc_member
        WHERE id = @userId
          AND is_lock = 0;

        SET @ordersPerformanceAmount = 0;
        SET @consumeAmount = 0;
        IF @userLevel <= 1
        THEN
            SELECT IFNULL(sum(op.price_cash * op.product_quantity * op.performance_bai_cash * 0.01 * if(ifnull(o.`discount`, 0) = 0, 10, o.`discount`) * 0.1), 0) INTO
                @ordersPerformanceAmount
            FROM zc_orders AS o
                     LEFT JOIN zc_order_product AS op ON o.id = op.order_id
                     LEFT JOIN zc_member AS m ON o.uid = m.id
            WHERE o.order_status IN (1, 3, 4)
              AND o.uid = @userId;

            IF (@ordersPerformanceAmount >= @performancePortionBase)
            THEN
                CALL Income_agentGive(@userId, @ordersPerformanceAmount, orderId, error);
                IF error
                THEN
                    LEAVE out_label;
                END IF;
            END IF;
            SET @performanceAmount = @ordersPerformanceAmount;
        ELSEIF @userLevel = 2
        THEN
            CALL Income_agentGive(@userId, @performanceAmount, orderId, error);
            IF error
            THEN
                LEAVE out_label;
            END IF;
        END IF;

        IF @performanceAmount < @performancePortionBase
        THEN
            LEAVE out_label;
        END IF;

        IF @userLevel <= 1
        THEN

            SELECT ifnull(sum(amount), 0) INTO @ccAmount
            FROM zc_member AS cm
                     LEFT JOIN zc_consume AS cc ON cm.id = cc.user_id
            WHERE cm.reid = @userId;

            IF @ccAmount >= @performanceAmount * 2 THEN
                -- 激活个人代理
                UPDATE zc_member
                SET `level`   = 2,
                    open_time = unix_timestamp()
                WHERE id = @userId;
            END IF;
        END IF;

    END out_label;
END
;;
DELIMITER ;


-- -------------------------------
-- 个人代理激活
-- -------------------------------
DROP PROCEDURE IF EXISTS `Activated_parent`;
DELIMITER ;;
CREATE PROCEDURE `Activated_parent`(IN userId INT(11), OUT error INT(11))
BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label:
    BEGIN

        SELECT ifnull(max(`uptime`), 0) INTO @lasttime FROM zc_consume_bak WHERE user_id = userId ORDER BY id DESC LIMIT 1;

        SELECT ifnull(max(o.id), 0) INTO @orderId
        FROM zc_member AS m
                 LEFT JOIN zc_member AS pm ON m.reid = pm.id
                 LEFT JOIN zc_orders AS o ON pm.id = o.uid

        WHERE m.id = userId
          AND o.order_status IN (1, 3, 4)
          AND o.exchangeway = 1
          AND o.pay_time > @lasttime
          AND m.`level`
        ORDER BY o.id DESC
        LIMIT 1;

        IF @orderId > 0 THEN
            CALL Event_activated(orderId, error);
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

        SET @orderStatus = 0, @userId = 0;
        SELECT order_status, uid INTO @orderStatus,@userId FROM zc_orders WHERE id = orderId;
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

            CALL Activated_parent(@userId, error);
            IF error THEN
                LEAVE out_label;
            END IF;

        END IF;

    END out_label;
END
;;
DELIMITER ;