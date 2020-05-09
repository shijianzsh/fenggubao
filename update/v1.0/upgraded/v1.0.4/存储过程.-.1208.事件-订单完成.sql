-- -------------------------------
-- 订单完成事件
-- -------------------------------
DROP PROCEDURE IF EXISTS `Event_consume`;
DELIMITER ;;
CREATE PROCEDURE `Event_consume`(IN orderId INT(11), OUT error INT(11))
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN
      SET @orderStatus = 0;
      SELECT order_status INTO @orderStatus FROM zc_orders WHERE id = orderId;
      IF @orderStatus <> 4 THEN
        LEAVE out_label;
      END IF;

    END out_label;
  END
;;
DELIMITER ;

-- -------------------------------
-- 自动完成订单
-- -------------------------------
DROP PROCEDURE IF EXISTS `TimerTask_autoCompleteOrder`;
DELIMITER ;;
CREATE PROCEDURE `TimerTask_autoCompleteOrder`(OUT error INT(11))
  BEGIN

    DECLARE done INT DEFAULT 0;
    DECLARE c_order_id INT DEFAULT 0;

    DECLARE orders CURSOR FOR
      SELECT o.`id` AS order_id
      FROM
        `zc_orders` AS o
        LEFT JOIN `zc_order_affiliate` AS oa ON o.`id` = oa.order_id
      WHERE
        o.`order_status` = 3  # 已发货订单
        AND oa.`affiliate_sendtime` < UNIX_TIMESTAMP() - 3600 * 24 * 7; -- 发货时间超过7天
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; -- 异常错误
    SET error = 0;
    -- 获取当前时间
    SET @now = UNIX_TIMESTAMP();
    out_label: BEGIN
      OPEN orders;
      REPEAT
        FETCH orders
        INTO c_order_id;
        IF NOT done
        THEN
          BEGIN
            # 自动完成订单
            UPDATE `zc_orders` AS o, `zc_order_affiliate` AS of
            SET
              o.`order_status`            = 4, # 更订单改为已完成
              of.`affiliate_completetime` = @now
            WHERE
              o.`id` = of.`order_id`
              AND o.`id` = c_order_id;

--             # 调起消费事件
--             CALL Event_consume(c_order_id, error);
--             IF error
--             THEN
--               LEAVE out_label;
--             END IF;
          END;
        END IF;
      UNTIL done END REPEAT;
      CLOSE orders;
    END out_label;
  END
;;
DELIMITER ;




