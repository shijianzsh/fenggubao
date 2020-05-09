-- -------------------------------
-- 收益 - 销售奖
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income_consume`;
DELIMITER ;;
CREATE PROCEDURE `Income_consume`(IN userId INT(11),
                                  IN performanceAmount DECIMAL(14, 4),
                                  IN orderId INT(11),
                                  OUT error INT(11))
BEGIN
  DECLARE done INT DEFAULT 0;
  DECLARE c_user_id INT DEFAULT 0;

  # 获取上二级个人代理
  DECLARE c_user CURSOR FOR
    SELECT p.id
    FROM zc_member AS m
           LEFT JOIN zc_member AS p ON find_in_set(p.id, m.repath)
           LEFT JOIN zc_consume AS c ON p.id = c.user_id
    WHERE m.id = userId
      AND p.level = 2
      AND p.is_lock = 0
      AND c.is_out = 0
    ORDER BY p.relevel DESC
    LIMIT 2;

  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; -- 异常错误
  SET error = 0;

  SET @userLevel = 0;

  out_label:
  BEGIN

    OPEN c_user;
    REPEAT
      FETCH c_user
        INTO c_user_id;
      IF NOT done
      THEN
        BEGIN
          SET @userLevel = @userLevel + 1;

          SET @consumeBai = GetSetting(CONCAT('prize_agent_consume_bai_', @userLevel));
          IF @consumeBai <= 0
          THEN
            LEAVE out_label;
          END IF;

          -- 获取公让宝最新价格
          SET @goldcoinPrice = 1;
          SELECT amount INTO @goldcoinPrice
          FROM zc_goldcoin_prices
          ORDER BY id DESC
          LIMIT 1;

          SET @consumeIncomeAmount = performanceAmount * @consumeBai * 0.01 / @goldcoinPrice;

          -- 分发流通资产
          SET @circulateBai = GetSetting(CONCAT('prize_agent_consume_circulate_bai_', @userLevel));
          IF @circulateBai > 0 THEN
            -- 添加明细
            CALL AddAccountRecord(c_user_id, 'goldcoin', 104, @consumeIncomeAmount * @circulateBai * 0.01, UNIX_TIMESTAMP(),
                                  concat('{"order_id":"', orderId, '"}'), 'xsj', @consumeBai, error);
            IF error THEN
              LEAVE out_label;
            END IF;
          END IF;

          -- 分发锁定资产
          SET @lockBai = GetSetting(CONCAT('prize_agent_consume_lock_bai_', @userLevel));
          IF @lockBai > 0 THEN
            -- 添加明细
            CALL AddAccountRecord(c_user_id, 'bonus', 204, @consumeIncomeAmount * @lockBai * 0.01, UNIX_TIMESTAMP(),
                                  concat('{"order_id":"', orderId, '"}'), 'xsj', @lockBai, error);
            IF error THEN
              LEAVE out_label;
            END IF;
          END IF;

          SET @consumeCashBai = GetSetting(CONCAT('prize_agent_consume_cash_bai_', @userLevel));
          IF @consumeCashBai > 0
          THEN
            -- 添加明细
            CALL AddAccountRecord(c_user_id, 'cash', 314, performanceAmount * @consumeCashBai * 0.01, UNIX_TIMESTAMP(),
                                  concat('{"order_id":"', orderId, '"}'), '代理商销售奖', @consumeCashBai, error);
            IF error THEN
              LEAVE out_label;
            END IF;
          END IF;

          -- 累计收益
          CALL Income_add(c_user_id, @consumeIncomeAmount, error);
          IF error
          THEN
            LEAVE out_label;
          END IF;

          -- 分发管理津贴
          CALL Income_subsidy(c_user_id, @consumeIncomeAmount, orderId, error);
          IF error
          THEN
            LEAVE out_label;
          END IF;

          #           -- 分发关爱奖
#           CALL Income_care(c_user_id, @consumeIncomeAmount, orderId, error);


          # 添加关爱奖队列
          INSERT INTO `zc_care_queue` VALUES (NULL, c_user_id, @consumeIncomeAmount, orderId, 0, UNIX_TIMESTAMP(), 0, 0);

        END;
      END IF;
    UNTIL done END REPEAT;
    CLOSE c_user;
  END out_label;
END
;;
DELIMITER ;