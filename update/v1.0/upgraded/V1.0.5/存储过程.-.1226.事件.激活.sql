-- -------------------------------
-- 个人代理激活
-- -------------------------------
DROP PROCEDURE IF EXISTS `Event_activated`;
DELIMITER ;;
CREATE PROCEDURE `Event_activated`(IN orderId INT(11), OUT error INT(11))
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      -- 代理区商品，业绩计算比例，固定100%
      SET @agentPerformanceBai = 100;
      SET @performanceAmount = 0;
      SELECT
        o.uid,
        IFNULL(sum(op.price_cash * op.product_quantity * @agentPerformanceBai * 0.01), 0)
      INTO
        @userId,
        @performanceAmount
      FROM
        zc_orders AS o
        LEFT JOIN zc_order_product AS op ON o.id = op.order_id
        LEFT JOIN zc_product_affiliate AS pa ON op.product_id = pa.product_id
      WHERE
        o.id = orderId
        AND pa.block_id = 4;

      IF @performanceAmount <= 0
      THEN
        LEAVE out_label;
      END IF;

      SET @performancePortionBase = GetSetting(concat('performance_portion_base'));
      SET @userLevel = 0;
      SELECT `level`
      INTO @userLevel
      FROM zc_member
      WHERE id = @userId AND is_lock = 0;
      IF @userLevel = 0
      THEN
        LEAVE out_label;
      END IF;

      IF @userLevel = 1
      THEN
        SET @ordersPerformanceAmount = 0;
        SELECT IFNULL(sum(op.price_cash * op.product_quantity * @agentPerformanceBai * 0.01), 0)
        INTO
          @ordersPerformanceAmount
        FROM
          zc_orders AS o
          LEFT JOIN zc_order_product AS op ON o.id = op.order_id
          LEFT JOIN zc_product_affiliate AS pa ON op.product_id = pa.product_id
        WHERE
          o.order_status = 1
          AND o.uid = @userId
          AND pa.block_id = 4;
        IF (@ordersPerformanceAmount >= @performancePortionBase)
        THEN
          CALL Income_agentGive(@userId, @ordersPerformanceAmount, orderId, error);
          IF error
          THEN
            LEAVE out_label;
          END IF;
        END IF;
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
        -- 激活个人代理
        UPDATE zc_member
        SET `level` = 2
        WHERE id = @userId;
      END IF;

    END out_label;
  END
;;
DELIMITER ;

