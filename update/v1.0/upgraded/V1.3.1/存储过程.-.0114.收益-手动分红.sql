-- -------------------------------
-- 收益 - 手动分红
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income_bonus`;
DELIMITER ;;
CREATE PROCEDURE `Income_bonus`(
  IN  userStar          INT(11),
  IN  performanceAmount DECIMAL(14, 4),
  IN  totalAmount       DECIMAL(14, 4),
  OUT error             INT(11)
)
  BEGIN

    DECLARE done INT DEFAULT 0;
    DECLARE bonusId INT DEFAULT 0;
    DECLARE c_user_id INT DEFAULT 0;
    DECLARE c_consume_amount DECIMAL(14, 4) DEFAULT 0;

    # 获取指定等级的个代
    DECLARE c_user CURSOR FOR
      SELECT
        m.id,
        c.amount
      FROM zc_member AS m
        LEFT JOIN zc_consume AS c ON m.id = c.user_id
      WHERE
        m.level = 2
        AND m.star = userStar
        AND m.is_lock = 0
        AND c.is_out = 0
        AND c.id IS NOT NULL;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; -- 异常错误
    SET error = 0;

    out_label: BEGIN

      SET @tag = FROM_UNIXTIME(UNIX_TIMESTAMP(), '%Y%m%d');

      SELECT COUNT(0)
      INTO @hasBouns
      FROM zc_performance_bonus
      WHERE agent_star = userStar AND tag = @tag
      LIMIT 1;

      IF @hasBouns
      THEN
        LEAVE out_label;
      END IF;

      SET @performancePortionBase = GetSetting(concat('performance_portion_base'));
      IF @performancePortionBase <= 0
      THEN
        LEAVE out_label;
      END IF;

      SELECT floor(sum(c.amount) / @performancePortionBase)
      INTO @count
      FROM zc_member AS m
        LEFT JOIN zc_consume AS c ON m.id = c.user_id
      WHERE
        m.level = 2
        AND m.star = userStar
        AND m.is_lock = 0
        AND c.is_out = 0
        AND c.id IS NOT NULL
      LIMIT 1;
      IF @count = 0
      THEN
        LEAVE out_label;
      END IF;

      -- 添加分红记录
      INSERT INTO zc_performance_bonus
        VALUE (NULL, performanceAmount, totalAmount, userStar, @count, 0, @tag, UNIX_TIMESTAMP());

      SET bonusId = LAST_INSERT_ID(); # 获取明细ID
      SET @bonusOneAmount = totalAmount / @count;
      IF @bonusOneAmount < 0.01
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

              SET @bonusIncomeAmount = @bonusOneAmount * @portion;

              -- 分发流通资产
              SET @circulateBai = GetSetting('bonus_circulate_bai');
              IF @circulateBai > 0 THEN
                -- 添加明细
                CALL AddAccountRecord(c_user_id, 'goldcoin', 107, @bonusIncomeAmount * @circulateBai * 0.01, UNIX_TIMESTAMP(), '', 'jqfh', 0, error);
                if error then
                  leave out_label;
                end if;
              END IF;

              -- 分发锁定资产
              SET @lockBai = GetSetting('bonus_lock_bai');
              IF @lockBai > 0 THEN
                -- 添加明细
                CALL AddAccountRecord(c_user_id, 'bonus', 207, @bonusIncomeAmount * @lockBai * 0.01, UNIX_TIMESTAMP(), '', 'jqfh', 0, error);
                if error then
                  leave out_label;
                end if;
                IF error THEN
                  LEAVE out_label;
                END IF;
              END IF;

              # 累计实际分红金额
              UPDATE zc_performance_bonus
              SET bonus_amount = bonus_amount + @bonusIncomeAmount, updated_at = UNIX_TIMESTAMP()
              WHERE id = bonusId;

            END out_repeat;
          END;
        END IF;
      UNTIL done END REPEAT;
      CLOSE c_user;
    END out_label;
  END
;;
DELIMITER ;