DROP PROCEDURE IF EXISTS `Performance_star`;
DELIMITER ;;
CREATE PROCEDURE `Performance_star`(IN userId INT(11), OUT error INT(11))
BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label:
    BEGIN

        SELECT count(0) INTO @hasUser FROM zc_member WHERE id = userId AND is_lock = 0 LIMIT 1;
        IF @hasUser = 0 THEN
            LEAVE out_label;
        END IF;

        SELECT star INTO @oldStar FROM zc_member WHERE id = userId LIMIT 1;

        SELECT pr.rule_id,
               pr.rule_amount,
               pr.rule_condition_level,
               pr.rule_condition_count,
               count(cm.id) AS cm_count
               INTO @newStar, @ruleAmount, @ruleConditionLevel, @ruleConditionCount, @cmCount
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

        -- 只升不降
        IF @newStar > @oldStar THEN
            UPDATE zc_member
            SET star = @newStar
            WHERE id = userId;
        END IF;

    END out_label;
END
;;
DELIMITER ;

UPDATE zc_performance_rule SET rule_amount = 10, rule_condition_count = 0, rule_condition_level = 0 WHERE rule_id = 1;
UPDATE zc_performance_rule SET rule_amount = 20, rule_condition_count = 0, rule_condition_level = 0 WHERE rule_id = 2;
UPDATE zc_performance_rule SET rule_amount = 50, rule_condition_count = 0, rule_condition_level = 0 WHERE rule_id = 3;
UPDATE zc_performance_rule SET rule_amount = 100, rule_condition_count = 3, rule_condition_level = 2 WHERE rule_id = 4;
UPDATE zc_performance_rule SET rule_amount = 200, rule_condition_count = 3, rule_condition_level = 3 WHERE rule_id = 5;
UPDATE zc_performance_rule SET rule_amount = 500, rule_condition_count = 4, rule_condition_level = 4 WHERE rule_id = 6;
UPDATE zc_performance_rule SET rule_amount = 1500, rule_condition_count = 5, rule_condition_level = 5 WHERE rule_id = 7;
UPDATE zc_performance_rule SET rule_amount = 5000, rule_condition_count = 5, rule_condition_level = 6 WHERE rule_id = 8;
UPDATE zc_performance_rule SET rule_amount = 10000, rule_condition_count = 5, rule_condition_level = 7 WHERE rule_id = 9;