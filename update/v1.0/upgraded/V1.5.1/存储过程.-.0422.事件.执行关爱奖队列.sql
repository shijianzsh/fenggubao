-- -------------------------------
-- 执行关爱奖队列
-- -------------------------------
DROP PROCEDURE IF EXISTS `Care_queue`;
DELIMITER ;;
CREATE PROCEDURE `Care_queue`(OUT error INT(11))
BEGIN

  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
  SET error = 0;

  out_label:
  BEGIN

    UPDATE zc_care_queue SET queue_status = 2 WHERE queue_status = 0 AND income_amount < 0.5;

    # 检查是否有队列正在执行
    SELECT count(0) INTO @hasLock
    FROM zc_care_queue
    WHERE queue_status = 1;
    IF @hasLock
    THEN
      LEAVE out_label;
    END IF;

    # 检查是否有队列需要执行
    SELECT count(0) INTO @hasQueue
    FROM zc_care_queue
    WHERE queue_status = 0;
    IF @hasQueue = 0
    THEN
      LEAVE out_label;
    END IF;

    SELECT id,
           user_id,
           income_amount,
           order_id
           INTO @queueId, @userId, @incomeAmount,@orderId
    FROM zc_care_queue
    WHERE queue_status = 0
    ORDER BY id ASC
    LIMIT 1;

    UPDATE zc_care_queue
    SET queue_status    = 1,
        queue_starttime = unix_timestamp()
    WHERE id = @queueId;

    # 开启事务
    START TRANSACTION;

    # 分发关爱奖
    CALL Income_care(@userId, @incomeAmount, @orderId, error);

    IF error
    THEN
      ROLLBACK; # 回滚
      UPDATE zc_care_queue
      SET queue_status  = 2,
          queue_endtime = unix_timestamp()
      WHERE id = @queueId;
    ELSE
      COMMIT; # 提交
      UPDATE zc_care_queue
      SET queue_status  = 3,
          queue_endtime = unix_timestamp()
      WHERE id = @queueId;
    END IF;

  END out_label;
END
;;
DELIMITER ;