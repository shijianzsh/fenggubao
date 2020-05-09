-- -------------------------------
-- 收益
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income`;
DELIMITER ;;
CREATE PROCEDURE `Income`(
  IN  userId            INT(11),
  IN  performanceAmount DECIMAL(14, 4),
  IN  orderId           INT(11),
  OUT error             INT(11)
)
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      # 流入矿池
      CALL Mine_add(performanceAmount, orderId, error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 分发销售奖
      CALL Income_consume(userId, performanceAmount, orderId, error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 特殊身份补贴
      CALL Income_special(userId, performanceAmount, orderId, error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      SET @specialDisableRoleIncome = GetSetting(concat('special_disable_role_income'));
      IF @specialDisableRoleIncome
      THEN
        SELECT count(0)
        INTO @hasDisable
        FROM
          zc_member AS pm
          LEFT JOIN zc_member AS m ON FIND_IN_SET(pm.id, m.repath)
        WHERE
          m.id = userId
          AND find_in_set(
              pm.loginname,
              @specialDisableRoleIncome
          );
        IF @hasDisable > 0
        THEN
          LEAVE out_label;
        END IF;
      END IF;

      # 分发区域合伙人奖
      CALL Income_countyService(userId, performanceAmount, orderId, error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 分发省级合伙人奖
      CALL Income_provinceService(userId, performanceAmount, orderId, error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

    END out_label;
  END
;;
DELIMITER ;