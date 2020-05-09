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
    IF @performance > 0 THEN
      # 一个矿机所需业绩金额
      SET @performancePortionBase = GetSetting(concat('performance_portion_base'));
      IF times = 1 THEN
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

-- -------------------------------
-- 计算指定用户最新时间矿机数
-- -------------------------------
DROP PROCEDURE IF EXISTS `CalculationMachine_latest`;
DELIMITER ;;
CREATE PROCEDURE `CalculationMachine_latest`(IN userId INT(11), OUT error INT(11))
BEGIN

  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; -- 异常错误
  SET error = 0;

  out_label:
  BEGIN
    SET @times = 5;
    IF unix_timestamp(GetSetting(concat('mine_machine_end_', 5))) <= 0 THEN
      SET @times = 5;
    END IF;

    IF unix_timestamp(GetSetting(concat('mine_machine_end_', 4))) <= 0 THEN
      SET @times = 4;
    END IF;

    IF unix_timestamp(GetSetting(concat('mine_machine_end_', 3))) <= 0 THEN
      SET @times = 3;
    END IF;

    IF unix_timestamp(GetSetting(concat('mine_machine_end_', 2))) <= 0 THEN
      SET @times = 2;
    END IF;

    IF unix_timestamp(GetSetting(concat('mine_machine_end_', 1))) <= 0 THEN
      SET @times = 1;
    END IF;

    CALL CalculationMachine_stage(userId, @times, @error);
    IF @error THEN
      LEAVE out_label;
    END IF;
  END out_label;
END
;;
DELIMITER ;


-- -------------------------------
-- 计算指定用户矿机数
-- -------------------------------
DROP PROCEDURE IF EXISTS `CalculationMachine`;
DELIMITER ;;
CREATE PROCEDURE `CalculationMachine`(IN userId INT(11), OUT error INT(11))
BEGIN

  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; -- 异常错误
  SET error = 0;

  out_label:
  BEGIN
    CALL CalculationMachine_stage(userId, 1, @error);
    IF @error THEN
      LEAVE out_label;
    END IF;

    CALL CalculationMachine_stage(userId, 2, @error);
    IF @error THEN
      LEAVE out_label;
    END IF;

    CALL CalculationMachine_stage(userId, 3, @error);
    IF @error THEN
      LEAVE out_label;
    END IF;

    CALL CalculationMachine_stage(userId, 4, @error);
    IF @error THEN
      LEAVE out_label;
    END IF;

    CALL CalculationMachine_stage(userId, 5, @error);
    IF @error THEN
      LEAVE out_label;
    END IF;

    UPDATE zc_consume SET machine_amount_uptime = unix_timestamp() WHERE user_id = userId;

  END out_label;
END
;;
DELIMITER ;


-- -------------------------------
-- 批量计算用户矿机数
-- -------------------------------
DROP PROCEDURE IF EXISTS `CalculationMachine_batch`;
DELIMITER ;;
CREATE PROCEDURE `CalculationMachine_batch`(OUT error INT(11))
BEGIN
  DECLARE done INT DEFAULT 0;
  DECLARE c_user_id INT DEFAULT 0;

  # 获取所有有消费用户
  DECLARE c_cursor CURSOR FOR
    SELECT c.user_id
    FROM zc_consume AS c;

  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; -- 异常错误
  SET error = 0;

  out_label:
  BEGIN
    OPEN c_cursor;
    REPEAT
      FETCH c_cursor
        INTO c_user_id;
      IF NOT done
      THEN
        BEGIN
          out_repeat:
          BEGIN

            CALL CalculationMachine(c_user_id, error);
            IF error THEN
              LEAVE out_label;
            END IF;

          END out_repeat;
        END;
      END IF;
    UNTIL done END REPEAT;
    CLOSE c_cursor;
  END out_label;
END
;;
DELIMITER ;


-- -------------------------------
-- 获取公让宝最新价格
-- -------------------------------
DROP FUNCTION IF EXISTS `GetUserTodayMachineMaxAmount`;
DELIMITER ;;
CREATE FUNCTION `GetUserTodayMachineMaxAmount`(userId INT)
  RETURNS DECIMAL(14, 4)
BEGIN
  SET @maxAmount = 0;

  SELECT count(0) INTO @hasConsume FROM zc_consume WHERE user_id = userId LIMIT 1;
  IF @hasConsume = 0 THEN
    RETURN @maxAmount;
  END IF;

  SELECT machine_amount,
         machine_amount_1,
         machine_amount_2,
         machine_amount_3,
         machine_amount_4,
         machine_amount_5
         INTO @machineAmount_0, @machineAmount_1, @machineAmount_2, @machineAmount_3, @machineAmount_4, @machineAmount_5
  FROM zc_consume
  WHERE user_id = userId
  LIMIT 1;

  # 计算用户后台充值矿机今日最大产出金额
  IF @machineAmount_0 > 0 THEN
    SET @maxAmount = @maxAmount + GetSetting('mine_machine_day_max_amount_0') * @machineAmount_0;
  END IF;

  # 计算用户第1阶段矿机今日最大产出金额
  IF @machineAmount_1 > 0 THEN
    SET @maxAmount = @maxAmount + GetSetting('mine_machine_day_max_amount_1') * @machineAmount_1;
  END IF;

  # 计算用户第2阶段矿机今日最大产出金额
  IF @machineAmount_2 > 0 THEN
    SET @maxAmount = @maxAmount + GetSetting('mine_machine_day_max_amount_2') * @machineAmount_2;
  END IF;

  # 计算用户第3阶段矿机今日最大产出金额
  IF @machineAmount_3 > 0 THEN
    SET @maxAmount = @maxAmount + GetSetting('mine_machine_day_max_amount_3') * @machineAmount_3;
  END IF;

  # 计算用户第4阶段矿机今日最大产出金额
  IF @machineAmount_4 > 0 THEN
    SET @maxAmount = @maxAmount + GetSetting('mine_machine_day_max_amount_4') * @machineAmount_4;
  END IF;

  # 计算用户第5阶段矿机今日最大产出金额
  IF @machineAmount_5 > 0 THEN
    SET @maxAmount = @maxAmount + GetSetting('mine_machine_day_max_amount_5') * @machineAmount_5;
  END IF;
  RETURN @maxAmount;
END
;;
DELIMITER ;