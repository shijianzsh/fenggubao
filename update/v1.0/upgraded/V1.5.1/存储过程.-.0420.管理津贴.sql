-- -------------------------------
-- 收益 - 管理津贴
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income_subsidy`;
DELIMITER ;;
CREATE PROCEDURE `Income_subsidy`(IN userId INT(11),
                                  IN incomeAmount DECIMAL(14, 4),
                                  IN orderId INT(11),
                                  OUT error INT(11))
BEGIN

  DECLARE done INT DEFAULT 0;
  DECLARE c_user_id INT DEFAULT 0;
  DECLARE c_consume_level INT DEFAULT 0;

  # 获取直推个人代理
  DECLARE c_user CURSOR FOR
    SELECT p.id,
           c.`level`
    FROM zc_member AS m
           LEFT JOIN zc_member AS p ON find_in_set(p.id, m.repath)
           LEFT JOIN zc_consume AS c ON p.id = c.user_id
    WHERE m.id = userId
      AND p.level = 2
      AND p.is_lock = 0
      AND c.user_id IS NOT NULL
      AND c.is_out = 0
    ORDER BY p.relevel DESC
    LIMIT 21;

  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; -- 异常错误
  SET error = 0;

  out_label:
  BEGIN

    SET @subsidyIncomeAmount = incomeAmount;

    OPEN c_user;
    REPEAT
      FETCH c_user
        INTO c_user_id, c_consume_level;
      IF NOT done
      THEN
        BEGIN

          out_repeat:
          BEGIN

            SET @subsidy_bai = 0;
            SELECT subsidy_bai INTO @subsidy_bai
            FROM zc_consume_rule
            WHERE `level` <= c_consume_level
            ORDER BY `level` DESC
            LIMIT 1;

            IF @subsidy_bai <= 0
            THEN
              LEAVE out_repeat;
            END IF;

            SET @subsidyIncomeAmount = @subsidyIncomeAmount * @subsidy_bai * 0.01;
            IF @subsidyIncomeAmount < 1
            THEN
              LEAVE out_label;
            END IF;

            -- 分发流通资产
            SET @circulateBai = GetSetting('subsidy_agent_circulate_bai');
            IF @circulateBai > 0 THEN
              -- 添加明细
              CALL AddAccountRecord(c_user_id, 'goldcoin', 105, @subsidyIncomeAmount * @circulateBai * 0.01, UNIX_TIMESTAMP(),
                                    concat('{"order_id":"', orderId, '"}'), 'gljt', @subsidy_bai, error);
              IF error THEN
                LEAVE out_label;
              END IF;
            END IF;

            -- 分发锁定资产
            SET @lockBai = GetSetting('subsidy_agent_lock_bai');
            IF @lockBai > 0 THEN
              -- 添加明细
              CALL AddAccountRecord(c_user_id, 'bonus', 205, @subsidyIncomeAmount * @lockBai * 0.01, UNIX_TIMESTAMP(),
                                    concat('{"order_id":"', orderId, '"}'), 'gljt', @subsidy_bai, error);
              IF error THEN
                LEAVE out_label;
              END IF;
            END IF;

            -- 累计收益
            CALL Income_add(c_user_id, @subsidyIncomeAmount, error);
            IF error
            THEN
              LEAVE out_label;
            END IF;


#             添加关爱奖队列
            INSERT INTO `zc_care_queue` VALUES (NULL, c_user_id, @subsidyIncomeAmount, orderId, 0, UNIX_TIMESTAMP(), 0, 0);
            -- 分发关爱奖
#             CALL Income_care(c_user_id, @subsidyIncomeAmount, orderId, error);
#             IF error
#             THEN
#               LEAVE out_label;
#             END IF;

          END out_repeat;

        END;
      END IF;
    UNTIL done END REPEAT;
    CLOSE c_user;
  END out_label;
END
;;
DELIMITER ;