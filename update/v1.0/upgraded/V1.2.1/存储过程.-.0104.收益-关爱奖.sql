-- -------------------------------
-- 收益 - 关爱奖
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income_care`;
DELIMITER ;;
CREATE PROCEDURE `Income_care`(
  IN  userId       INT(11),
  IN  incomeAmount DECIMAL(14, 4),
  IN  orderId      INT(11),
  OUT error        INT(11)
)
  BEGIN

    DECLARE done INT DEFAULT 0;
    DECLARE c_user_id INT DEFAULT 0;
    DECLARE c_consume_amount DECIMAL(14, 4) DEFAULT 0;

    # 获取所有直接下线
    DECLARE c_user CURSOR FOR
      SELECT
        m.id,
        c.amount
      FROM zc_member AS m
        LEFT JOIN zc_consume AS c ON m.id = c.user_id
      WHERE
        m.level = 2
        AND m.is_lock = 0
        AND c.is_out = 0
        AND m.reid = userId
        AND c.id IS NOT NULL;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; -- 异常错误
    SET error = 0;

    out_label: BEGIN
      IF incomeAmount < 1
      THEN
        LEAVE out_label;
      END IF;

      SET @performancePortionBase = GetSetting(concat('performance_portion_base'));
      IF @performancePortionBase <= 0
      THEN
        LEAVE out_label;
      END IF;

      SET @count = 0;

      SELECT floor(sum(c.amount) / @performancePortionBase)
      INTO @count
      FROM zc_member AS m
        LEFT JOIN zc_consume AS c ON m.id = c.user_id
      WHERE
        m.level = 2
        AND m.is_lock = 0
        AND c.is_out = 0
        AND m.reid = userId
        AND c.id IS NOT NULL
      LIMIT 1;
      IF @count = 0
      THEN
        LEAVE out_label;
      END IF;

      SET @incomeBai = GetSetting(CONCAT('prize_care_agent_bai'));
      IF @incomeBai <= 0
      THEN
        LEAVE out_label;
      END IF;

      SET @oneIncomeAmount = incomeAmount * @incomeBai * 0.01 / @count;
      IF @oneIncomeAmount < 0.01
      THEN
        LEAVE out_label;
      END IF;

      OPEN c_user;
      REPEAT FETCH c_user
      INTO c_user_id, c_consume_amount;
        IF NOT done
        THEN
          BEGIN

            out_repeat: BEGIN

              SET @portion = floor(c_consume_amount / @performancePortionBase);
              IF @portion < 1
              THEN
                LEAVE out_repeat;
              END IF;

              SET @careIncomeAmount = @oneIncomeAmount * @portion;

              -- 分发流通资产
              SET @circulateBai = GetSetting('prize_care_agent_circulate_bai');
              IF @circulateBai > 0 THEN
                -- 添加明细
                CALL AddAccountRecord(c_user_id, 'goldcoin', 108, @careIncomeAmount * @circulateBai * 0.01, UNIX_TIMESTAMP(),
                                    concat('{"order_id":"', orderId, '"}'), 'gaj', @incomeBai, error);
                if error then
                  leave out_label;
                end if;
              END IF;

              -- 分发锁定资产
              SET @lockBai = GetSetting('prize_care_agent_lock_bai');
              IF @lockBai > 0 THEN
                CALL AddLockGoldcoin(c_user_id, @careIncomeAmount * @lockBai * 0.01, error);
                IF error THEN
                  LEAVE out_label;
                END IF;
              END IF;

              -- 累计收益
              CALL Income_add(c_user_id, @careIncomeAmount, error);
              IF error
              THEN
                LEAVE out_label;
              END IF;

              -- 添加关爱奖队列
              INSERT INTO `zc_care_queue` VALUES (NULL, c_user_id, @careIncomeAmount, 0, UNIX_TIMESTAMP(), 0, 0);

            END out_repeat;
          END;
        END IF;
      UNTIL done END REPEAT;
      CLOSE c_user;
    END out_label;
  END
;;
DELIMITER ;