-- -------------------------------
-- 收益 - 钻石经销商补贴
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income_subsidy_5`;
DELIMITER ;;
CREATE PROCEDURE `Income_subsidy_5`(IN userId INT(11),
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
      AND c.level = 5
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

          SET @subsidyCashBai = GetSetting(CONCAT('subsidy_level_5_cash_bai_', @userLevel));
          IF @subsidyCashBai <= 0
          THEN
            LEAVE out_label;
          END IF;

          -- 添加明细
          CALL AddAccountRecord(c_user_id, 'cash', 315, performanceAmount * @subsidyCashBai * 0.01, UNIX_TIMESTAMP(),
                                concat('{"order_id":"', orderId, '"}'), '钻石经销商补贴', @subsidyCashBai, error);
          IF error THEN
            LEAVE out_label;
          END IF;

        END;
      END IF;
    UNTIL done END REPEAT;
    CLOSE c_user;
  END out_label;
END
;;
DELIMITER ;