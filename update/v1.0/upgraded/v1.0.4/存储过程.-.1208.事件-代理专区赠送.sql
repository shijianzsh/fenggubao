-- -------------------------------
-- 申请合伙人
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income_agentGive`;
DELIMITER ;;
CREATE PROCEDURE `Income_agentGive`(
  IN  userId INT(11),
  IN  performanceAmount DECIMAL(14, 4),
  IN  orderId           INT(11),
  OUT error  INT(11)
)
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      set @giveBei = GetSetting(CONCAT('give_goldcoin_bei'));
      if @giveBei <= 0
      then
        leave out_label;
      end if;

      -- 获取公让宝最新价格
      SET @goldcoinPrice = 1;
      SELECT amount INTO @goldcoinPrice FROM zc_goldcoin_prices ORDER BY id DESC LIMIT 1;

      SET @giveIncomeAmount = performanceAmount * @giveBei;

      SET @circulateBai = GetSetting('give_goldcoin_circulate_bai');
      IF @circulateBai > 0 THEN
        -- 添加明细
        CALL AddAccountRecord(userId, 'goldcoin', 103, @giveIncomeAmount * @circulateBai * 0.01, UNIX_TIMESTAMP(), concat('{"order_id":"', orderId, '"}'), 'xfzs', @giveBei, error);

        if error
        then
          leave out_label;
        end if;
      END IF;

      SET @lockBai = GetSetting('give_goldcoin_lock_bai');
      IF @lockBai > 0 THEN
        CALL AddLockGoldcoin(userId, @giveIncomeAmount * @lockBai * 0.01, error);
        if error
        then
          leave out_label;
        end if;
      END IF;

    END out_label;
  END
;;
DELIMITER ;