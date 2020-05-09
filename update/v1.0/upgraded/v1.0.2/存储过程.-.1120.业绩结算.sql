-- -------------------------------
-- VIP业绩结算
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income_subsidyPerformance`;
DELIMITER ;;
CREATE PROCEDURE `Income_subsidyPerformance`(
  IN  userId            INT(11),
  IN  performanceAmount DECIMAL(14, 4),
  IN  orderId           INT(11),
  OUT error             INT(11)
)
  BEGIN

    DECLARE done INT DEFAULT 0;
    DECLARE c_user_id INT DEFAULT 0;
    DECLARE c_star INT DEFAULT 0;

# 获取所有上级星级店长
    DECLARE c_user CURSOR FOR
      SELECT
        p.id,
        p.star
      FROM
        zc_member AS m
        LEFT JOIN zc_member AS p ON find_in_set(p.id, m.repath)
      WHERE
        m.id = userId
        AND p.`level` = 2
        AND p.`star` > 0
        AND p.is_lock = 0
      ORDER BY p.relevel DESC;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; #异常错误
    SET error = 0;

    out_label: BEGIN
      SET @parentStar = 0;

      SELECT
        rule_id,
        rule_bai
      INTO @maxStar, @maxSubsidyBai
      FROM zc_performance_rule
      ORDER BY rule_id DESC
      LIMIT 1;
      IF @maxSubsidyBai <= 0
      THEN
        LEAVE out_label;
      END IF;

      SET @alreadySubsidyBai = 0;

      OPEN c_user;
      REPEAT
        FETCH c_user
        INTO c_user_id, c_star;
        IF NOT done
        THEN
          repeat_label: BEGIN
            IF @alreadySubsidyBai >= @maxSubsidyBai
            THEN
              LEAVE out_label;
            END IF;

            IF @parentStar >= c_star
            THEN
              LEAVE repeat_label;
            END IF;
            SET @parentStar = c_star;

            SET @subsidyBai = 0;
            SELECT rule_bai
            INTO @subsidyBai
            FROM zc_performance_rule
            WHERE rule_id = c_star;

            SET @subsidyBai = @subsidyBai - @alreadySubsidyBai;
            IF @subsidyBai > @maxSubsidyBai - @alreadySubsidyBai
            THEN
              SET @subsidyBai = @maxSubsidyBai - @alreadySubsidyBai;
            END IF;

            IF @subsidyBai <= 0
            THEN
              LEAVE out_label;
            END IF;

            SET @alreadySubsidyBai = @alreadySubsidyBai + @subsidyBai;

#添加明细
            CALL AddAccountRecord(c_user_id, 'cash', 313, performanceAmount * @subsidyBai * 0.01, UNIX_TIMESTAMP(),
                                  concat('{"from_uid":"', userId, '","order_id":"', orderId, '"}'), '星级VIP业绩补贴', @subsidyBai, error);

            IF error
            THEN
              LEAVE out_label;
            END IF;
          END repeat_label;
        END IF;
      UNTIL done END REPEAT;
      CLOSE c_user;
    END out_label;
  END
;;
DELIMITER ;


DROP EVENT IF EXISTS `minute_executePerformanceRewardTask`;
DROP PROCEDURE IF EXISTS `PerformanceRewardTask_autoAdd`;
DROP PROCEDURE IF EXISTS `PerformanceRewardTask_execute`;
DROP PROCEDURE IF EXISTS `PerformanceBonusTask_autoAdd`;
DROP PROCEDURE IF EXISTS `PerformanceBonusTask_execute`;
