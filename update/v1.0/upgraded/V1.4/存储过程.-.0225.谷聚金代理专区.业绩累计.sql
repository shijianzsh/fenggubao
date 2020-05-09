-- -------------------------------
-- 谷聚金代理专区 - 业绩累计
-- -------------------------------
DROP PROCEDURE IF EXISTS `Gjj_Performance_calculation`;
DELIMITER ;;
CREATE PROCEDURE `Gjj_Performance_calculation`(IN userId INT(11), IN role TINYINT(1), OUT error INT(11))
BEGIN

  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
  SET error = 0;

  out_label:
  BEGIN
    -- 获取代理费
    SET @agentFee = GetSetting(concat('gjj_agent_fee_', role));
    IF @agentFee <= 0 THEN
      LEAVE out_label;
    END IF;

    -- 获取PV值比例
    SET @pvBai = GetSetting('gjj_recommend_pv_bai');
    IF @pvBai <= 0 THEN
      LEAVE out_label;
    END IF;

    SELECT count(0) INTO @hasRecommend
    FROM zc_member AS m
           LEFT JOIN zc_member AS p ON p.id = m.reid
    WHERE m.id = userId
      AND m.is_lock = 0
      AND p.level = 2
      AND p.is_lock = 0
    LIMIT 1;
    IF @hasRecommend = 0 THEN
      LEAVE out_label;
    END IF;

    SELECT p.id INTO @userId
    FROM zc_member AS m
           LEFT JOIN zc_member AS p ON p.id = m.reid
    WHERE m.id = userId
      AND m.is_lock = 0
      AND p.level = 2
      AND p.is_lock = 0
    LIMIT 1;

    SET @performanceAmount = @agentFee * @pvBai * 0.01;

    -- 加入业绩统计队列
    INSERT IGNORE INTO zc_performance_queue (user_id, performance_amount, order_id, queue_status, queue_addtime, queue_starttime, queue_endtime)
    VALUES (@userId, @performanceAmount, 0, 0, unix_timestamp(), unix_timestamp(), unix_timestamp());

  END out_label;
END
;;
DELIMITER ;