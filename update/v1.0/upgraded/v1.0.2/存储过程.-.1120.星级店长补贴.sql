-- -------------------------------
-- 星级店长补贴
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income_subsidyService`;
DELIMITER ;;
CREATE PROCEDURE `Income_subsidyService`(
  IN  userId            INT(11),
  IN  performanceAmount DECIMAL(14, 4),
  IN  orderId           INT(11),
  OUT error             INT(11)
)
  BEGIN

    DECLARE done INT DEFAULT 0;
    DECLARE c_user_id INT DEFAULT 0;
    DECLARE c_role_star INT DEFAULT 0;

# 获取所有上级星级店长
    DECLARE c_user CURSOR FOR
      SELECT
        p.id,
        p.role_star
      FROM
        zc_member AS m
        LEFT JOIN zc_member AS p ON find_in_set(p.id, m.repath)
      WHERE
        m.id = userId
        AND p.role = 3
        AND p.role_star > 0
        AND p.is_lock = 0
      ORDER BY p.relevel DESC;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; #异常错误
    SET error = 0;


    out_label: BEGIN

      SET @parentStar = 0;

      SET @maxSubsidyBai = GetSetting(CONCAT('service_star_subsidy_8'));
      IF @maxSubsidyBai <= 0
      THEN
        LEAVE out_label;
      END IF;

      SET @alreadySubsidyBai = 0;

      OPEN c_user;
      REPEAT
        FETCH c_user
        INTO c_user_id, c_role_star;
        IF NOT done
        THEN
          repeat_label: BEGIN
            IF @alreadySubsidyBai >= @maxSubsidyBai
            THEN
              LEAVE out_label;
            END IF;

            IF @parentStar >= c_role_star
            THEN
              LEAVE repeat_label;
            END IF;
            SET @parentStar = c_role_star;

            SET @subsidyBai = GetSetting(CONCAT('service_star_subsidy_', c_role_star));
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
            CALL AddAccountRecord(c_user_id, 'cash', 311, performanceAmount * @subsidyBai * 0.01, UNIX_TIMESTAMP(),
                                  concat('{"from_uid":"', userId, '","order_id":"', orderId, '"}'), '星级店长消费补贴', @subsidyBai, error);

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