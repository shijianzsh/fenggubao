-- -------------------------------
-- 收益 - 特殊身份补贴
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income_special`;
DELIMITER ;;
CREATE PROCEDURE `Income_special`(
  IN  userId            INT(11),
  IN  performanceAmount DECIMAL(14, 4),
  IN  orderId           INT(11),
  OUT error             INT(11)
)
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      SET @specialIncomeUsers = GetSetting(concat('special_income_users'));
      IF @specialIncomeUsers = '' THEN
        LEAVE out_label;
      END IF;

      SELECT count(0)
      INTO @hasSpecialUsers
      FROM zc_member AS m
        LEFT JOIN zc_member AS pm ON find_in_set(pm.id, m.repath)
      WHERE
        m.id = userId
        AND find_in_set(pm.loginname, @specialIncomeUsers)
      ORDER BY pm.relevel DESC
      LIMIT 1;

      IF @hasSpecialUsers = 0 THEN
        leave out_label;
      END IF;

      SET @specialIncomeBai = GetSetting(concat('special_income_bai'));
      IF @specialIncomeBai <= 0 THEN
        leave out_label;
      END IF;


      SELECT pm.id
      INTO @userId
      FROM zc_member AS m
        LEFT JOIN zc_member AS pm ON find_in_set(pm.id, m.repath)
      WHERE
        m.id = userId
        AND find_in_set(pm.loginname, @specialIncomeUsers)
      ORDER BY pm.relevel DESC
      LIMIT 1;

      -- 获取公让宝最新价格
      SET @goldcoinPrice = 1;
      SELECT amount
      INTO @goldcoinPrice
      FROM zc_goldcoin_prices
      ORDER BY id DESC
      LIMIT 1;

      SET @specialIncomeAmount = performanceAmount * @specialIncomeBai * 0.01 / @goldcoinPrice;

      -- 分发流通资产
      SET @circulateBai = GetSetting('special_income_circulate_bai');
      IF @circulateBai > 0 THEN
        -- 添加明细
        CALL AddAccountRecord(@userId, 'goldcoin', 111, @specialIncomeAmount * @circulateBai * 0.01, UNIX_TIMESTAMP(),
                            concat('{"order_id":"', orderId, '"}'), '特殊身份补贴', @specialIncomeBai, error);
        if error then
          leave out_label;
        end if;
      END IF;

      -- 分发锁定资产
      SET @lockBai = GetSetting('special_income_lock_bai');
      IF @lockBai > 0 THEN
        CALL AddAccountRecord(@userId, 'bonus', 211, @specialIncomeAmount * @lockBai * 0.01, UNIX_TIMESTAMP(),
                            concat('{"order_id":"', orderId, '"}'), '特殊身份补贴', @specialIncomeBai, error);
        IF error THEN
          LEAVE out_label;
        END IF;
      END IF;

    END out_label;
  END
;;
DELIMITER ;