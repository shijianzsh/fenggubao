DROP PROCEDURE IF EXISTS `Performance_star`;
DELIMITER ;;
CREATE PROCEDURE `Performance_star`(IN userId INT(11), OUT error INT(11))
BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label:
    BEGIN

        SELECT count(0) INTO @hasUser FROM zc_member WHERE id = userId AND is_lock = 0;
        IF @hasUser = 0 THEN
            LEAVE out_label;
        END IF;

        SELECT pr.rule_id,
               pr.rule_amount,
               pr.rule_condition_level,
               pr.rule_condition_count,
               count(cm.id) AS cm_count
               INTO @ruleId, @ruleAmount, @ruleConditionLevel, @ruleConditionCount, @cmCount
        FROM zc_performance AS p
                 LEFT JOIN zc_performance_rule AS pr ON p.performance_amount >= pr.rule_amount * 10000
                 LEFT JOIN zc_performance_rule AS cpr ON pr.rule_condition_level = cpr.rule_id
                 LEFT JOIN zc_member AS cm ON cm.reid = p.user_id
                 LEFT JOIN zc_performance AS cp ON cm.id = cp.user_id
        WHERE p.performance_tag = 0
          AND p.user_id = userId
          AND cp.performance_tag = 0
          AND cp.performance_amount >= ifnull(cpr.rule_amount, 0) * 10000
        GROUP BY pr.rule_id
        HAVING cm_count >= pr.rule_condition_count
        ORDER BY rule_id DESC
        LIMIT 1;

        UPDATE zc_member
        SET star = @ruleId
        WHERE id = userId;

    END out_label;
END
;;
DELIMITER ;


-- -------------------------------
-- 修复挖矿
-- -------------------------------
DROP PROCEDURE IF EXISTS `Fix_starBatch`;
DELIMITER ;;
CREATE PROCEDURE `Fix_starBatch`(OUT error INT(11))
BEGIN

    DECLARE done INT DEFAULT 0;
    DECLARE c_user_id INT DEFAULT 0;

    DECLARE c_user CURSOR FOR
        SELECT p.user_id
        FROM zc_performance AS p
        WHERE p.performance_amount >= 50000
          AND p.performance_tag = 0
        ORDER BY p.performance_amount ASC;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; -- 异常错误
    SET error = 0;
    SET @count = 0;
    out_label:
    BEGIN
        OPEN c_user;
        REPEAT
            FETCH c_user
                INTO c_user_id;
            IF NOT done
            THEN
                BEGIN
                    out_repeat:
                    BEGIN
                        CALL Performance_star(c_user_id, error);
                        IF error THEN
                            LEAVE out_label;
                        END IF;
                    END out_repeat;
                END;
            END IF;
        UNTIL done END REPEAT;
        CLOSE c_user;
    END out_label;
    SELECT @count;
END
;;
DELIMITER ;


UPDATE zc_member
SET star = 0
WHERE star > 0;

CALL Fix_starBatch(@error);
SELECT @error;

DROP PROCEDURE IF EXISTS `Fix_starBatch`;

