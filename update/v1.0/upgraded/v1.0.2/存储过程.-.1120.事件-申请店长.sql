-- -------------------------------
-- 申请店长
-- -------------------------------
DROP PROCEDURE IF EXISTS `Event_applyService`;
DELIMITER ;;
CREATE PROCEDURE `Event_applyService`(
  IN  userId INT(11),
  OUT error  INT(11)
)
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

# 获取店长赠送兑换券金额
      SET @giveGoldCoinAmount = GetSetting(CONCAT('apply_service_give_goldcoin_amount'));
      IF @giveGoldCoinAmount <= 0
      THEN
        LEAVE out_label;
      END IF;

# 添加明细
      CALL AddAccountRecord(userId, 'goldcoin', 106, @giveGoldCoinAmount, UNIX_TIMESTAMP(), '', '申请店长赠送', 0, error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

# 分发店长销售奖（直推店长）
      CALL Income_recommendService(userId, error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

# 店长星级评定
      CALL Service_star(userId, error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

# 上线星级评定
      CALL Service_parentStar(userId, error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

# 获取店长赠送兑换券金额
      SET @servicePerformance = GetSetting(CONCAT('apply_service_performance'));
# 添加业绩累计对列
      IF @servicePerformance > 0
      THEN
        INSERT IGNORE INTO zc_performance_queue (user_id, performance_amount, order_id, queue_status, queue_addtime, queue_starttime, queue_endtime)
        VALUES (userId, @servicePerformance, 0, 0, unix_timestamp(), unix_timestamp(), unix_timestamp());

        CALL Income(userId, @servicePerformance, 0, error);
        IF error
        THEN
          LEAVE out_label;
        END IF;

      END IF;
    END out_label;
  END
;;
DELIMITER ;