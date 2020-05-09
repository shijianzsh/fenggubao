# 开启事件
-- SET GLOBAL event_scheduler = 1;

-- ----------------------------
-- Event structure for delete_log_day_30
-- ----------------------------
DROP EVENT IF EXISTS `delete_log_day_30`;
DELIMITER ;;
CREATE EVENT `delete_log_day_30`
  ON SCHEDULE EVERY 1 DAY
    STARTS '2017-03-01 23:59:59'
  ON COMPLETION PRESERVE
  ENABLE DO BEGIN
  CALL DeleteLog(@error);
END
;;
DELIMITER ;

-- ----------------------------
-- Event structure for delete_withdraw_cash_month_1
-- ----------------------------
DROP EVENT IF EXISTS `delete_withdraw_cash_month_1`;

-- ----------------------------
-- Event structure for event_delete_phonecode
-- ----------------------------
DROP EVENT IF EXISTS `event_delete_phonecode`;
DELIMITER ;;
CREATE EVENT `event_delete_phonecode`
  ON SCHEDULE EVERY 1 DAY
    STARTS '2017-05-22 00:00:00'
  ON COMPLETION PRESERVE
  ENABLE DO BEGIN
  DELETE FROM zc_phonecode
  WHERE from_unixtime(post_time, '%Y%m%d') < from_unixtime(unix_timestamp(), '%Y%m%d');
END
;;
DELIMITER ;

-- ----------------------------
-- 自动完成订单（每天0时执行）
-- ----------------------------
DROP EVENT IF EXISTS `everyday_autoCompleteOrder`;
DELIMITER ;;
CREATE EVENT `everyday_autoCompleteOrder`
  ON SCHEDULE EVERY 1 DAY
    STARTS '2018-09-07 00:00:00'
  ON COMPLETION PRESERVE
  ENABLE DO BEGIN

  CALL TimerTask_autoCompleteOrder(@error);

END
;;
DELIMITER ;



-- ----------------------------
-- Event structure for everyday_Release
-- ----------------------------
DROP EVENT IF EXISTS `everyday_Release`;
DELIMITER ;;
CREATE EVENT `everyday_Release`
  ON SCHEDULE EVERY 10 SECOND
    STARTS '2018-12-01 00:00:00'
  ON COMPLETION PRESERVE
  ENABLE DO BEGIN

  SET @switch = GetSetting(CONCAT('goldcoin_release_switch'));
  IF @switch = '开启'
  THEN
    CALL Release_lock(@error);
  END IF;

END
;;
DELIMITER ;

-- ----------------------------
-- Event structure for everyday_statistics
-- ----------------------------
DROP EVENT IF EXISTS `everyday_statistics`;
DELIMITER ;;
CREATE EVENT `everyday_statistics`
  ON SCHEDULE EVERY 1 DAY
    STARTS '2018-09-07 00:00:00'
  ON COMPLETION PRESERVE
  ENABLE DO BEGIN
  CALL TimerTask(@error);
END
;;
DELIMITER ;

-- ----------------------------
-- Event structure for everyday_subsidy
-- ----------------------------
DROP EVENT IF EXISTS `everyday_subsidy`;

-- ----------------------------
-- Event structure for hour_executeCancelUnpaidOrder
-- ----------------------------
DROP EVENT IF EXISTS `hour_executeCancelUnpaidOrder`;
DELIMITER ;;
CREATE EVENT `hour_executeCancelUnpaidOrder`
  ON SCHEDULE EVERY 1 HOUR
    STARTS '2018-09-07 00:00:00'
  ON COMPLETION PRESERVE
  ENABLE DO BEGIN
  out_label: BEGIN
    # 开启事务
    START TRANSACTION;
    CALL CancelUnpaidOrder(@error);

    IF @error = 1
    THEN
      ROLLBACK; # 回滚
      LEAVE out_label;
    ELSE
      COMMIT; # 提交
    END IF;
  END out_label;
END
;;
DELIMITER ;

-- ----------------------------
-- Event structure for minute_executePerformanceQueue
-- ----------------------------
DROP EVENT IF EXISTS `minute_executePerformanceQueue`;
DROP EVENT IF EXISTS `everysecond_executePerformanceQueue`;
DELIMITER ;;
CREATE EVENT `everysecond_executePerformanceQueue`
  ON SCHEDULE EVERY 5 SECOND
    STARTS '2018-09-07 00:00:00'
  ON COMPLETION PRESERVE
  ENABLE DO BEGIN
  CALL Performance_queue(@error);
END
;;
DELIMITER ;


-- ----------------------------
-- 释放锁定资产（每天0时执行）
-- ----------------------------
DROP EVENT IF EXISTS `everyday_Release`;
DELIMITER ;;
CREATE EVENT `everyday_Release`
  ON SCHEDULE EVERY 1 DAY
    STARTS '2018-12-01 00:00:00'
  ON COMPLETION PRESERVE
  ENABLE DO BEGIN

  SET @switch = GetSetting(CONCAT('goldcoin_release_switch'));
  IF @switch = '开启'
  THEN
    CALL Release_lock(@error);
  END IF;

END
;;
DELIMITER ;


-- ----------------------------
-- 分发关爱奖收益（每天0时执行）
-- ----------------------------
DROP EVENT IF EXISTS `everysecond_Care`;
DELIMITER ;;
CREATE EVENT `everysecond_Care`
  ON SCHEDULE EVERY 5 SECOND
    STARTS '2018-12-01 00:00:00'
  ON COMPLETION PRESERVE
  ENABLE DO BEGIN

  CALL Care_queue(@error);

END
;;
DELIMITER ;