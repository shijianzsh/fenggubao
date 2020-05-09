-- -------------------------------
-- 计算指定用户指定阶段矿机数
-- -------------------------------
DROP PROCEDURE IF EXISTS `CalculationMachine_stage`;
DELIMITER ;;
CREATE PROCEDURE `CalculationMachine_stage`(IN userId INT(11),
                                            IN times INT(11),
                                            OUT error INT(11))
BEGIN

  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; -- 异常错误
  SET error = 0;

  out_label:
  BEGIN
    IF times > 5 THEN
      LEAVE out_label;
    END IF;

    SET @startTime = 0;
    IF times > 1 THEN
      # 获取上次结束时间，为本次开始时间
      SET @startTime = unix_timestamp(GetSetting(concat('mine_machine_end_', times - 1)));
      # 非第1阶段，无开始时间，不统计
      IF @startTime <= 0 THEN
        LEAVE out_label;
      END IF;
    END IF;

    # 最后阶段，强制使用当前时间
    SET @endTime = unix_timestamp();
    IF times < 5 THEN
      SET @endTime = unix_timestamp(GetSetting(concat('mine_machine_end_', times)));
      # 无结束时间，使用当前时间
      IF @endTime <= 0 THEN
        SET @endTime = unix_timestamp();
      END IF;
    END IF;

    SELECT ifnull(sum(op.price_cash * op.performance_bai_cash * 0.01 * op.product_quantity), 0) INTO @performance
    FROM zc_orders AS o
           LEFT JOIN zc_order_product AS op ON o.id = op.order_id
    WHERE o.order_status IN (1, 3, 4)
      AND o.exchangeway = 1
      AND op.performance_bai_cash > 0
      AND o.uid = userId
      AND o.pay_time >= @startTime
      AND o.pay_time < @endTime;

    SET @machine_amount = 0;
    IF times = 2 THEN
      SELECT ifnull(sum(op.price_cash * op.performance_bai_cash * 0.01 * op.product_quantity), 0) INTO @performance_old
      FROM zc_orders AS o
             LEFT JOIN zc_order_product AS op ON o.id = op.order_id
      WHERE o.order_status IN (1, 3, 4)
        AND o.exchangeway = 1
        AND op.performance_bai_cash > 0
        AND o.uid = userId
        AND o.pay_time < @startTime;
      SELECT amount_old INTO @amount_old FROM zc_consume WHERE user_id = userId;
      SET @performance = @performance + (@performance_old - @amount_old);
    END IF;

    IF @performance > 0 THEN
      # 一个矿机所需业绩金额
      SET @performancePortionBase = GetSetting(concat('performance_portion_base'));
      IF times = 1 THEN
        SELECT amount_old INTO @performance FROM zc_consume WHERE user_id = userId;
        # 老矿机算力比例
        SET @mineOldMachineBai = GetSetting(concat('mine_old_machine_bai'));
        SET @machine_amount = floor(@performance / @performancePortionBase) * @mineOldMachineBai * 0.01;
      ELSE
        SET @machine_amount = floor(@performance / (@performancePortionBase / 2)) / 2;
      END IF;
    END IF;

    # 更新矿机数
    SET @v_sql = concat('UPDATE zc_consume SET machine_amount_', times, ' = ', @machine_amount, ' where user_id = ', userId);
    PREPARE statement FROM @v_sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

  END out_label;
END
;;
DELIMITER ;
