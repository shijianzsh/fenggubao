# -- -------------------------------
# -- 上级定星
# -- -------------------------------
DROP PROCEDURE IF EXISTS `Service_parentStar`;
DELIMITER ;;
CREATE PROCEDURE `Service_parentStar`(
  IN  userId INT(11),
  OUT error  INT(11)
)
  BEGIN

    DECLARE done INT DEFAULT 0;
    DECLARE c_user_id INT DEFAULT 0;

    DECLARE c_user CURSOR FOR
      SELECT p.id
      FROM
        zc_member AS m
        LEFT JOIN zc_member AS p ON find_in_set(p.id, m.repath)
      WHERE
        m.id = userId
        AND p.is_lock = 0
      ORDER BY p.relevel DESC;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN
      OPEN c_user;
      REPEAT
        FETCH c_user
        INTO c_user_id;
        IF NOT done
        THEN
          BEGIN

            CALL Service_star(c_user_id, error);

            IF error
            THEN
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


# -- -------------------------------
# -- 业绩定星
# -- -------------------------------
DROP PROCEDURE IF EXISTS `Service_star`;
DELIMITER ;;
CREATE PROCEDURE `Service_star`(
  IN  userId INT(11),
  OUT error  INT(11)
)
  BEGIN
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      SELECT count(0)
      INTO @hasUser
      FROM zc_member
      WHERE id = userId AND is_lock = 0;

      IF @hasUser = 0
      THEN
        LEAVE out_label;
      END IF;

      SET @childrenCount = 0;
      SELECT count(0)
      INTO @childrenCount
      FROM
        zc_member
      WHERE
        reid = userId;
      SET @condition = GetSetting(concat('service_star_condition_1'));

      IF @childrenCount < @condition
      THEN
        LEAVE out_label;
      END IF;


      SELECT count(0)
      INTO @childrenCount
      FROM
        zc_member
      WHERE
        find_in_set(userId, repath);

      SET @i = 0;
      SET @roleStar = 0;
      starloop: LOOP
        SET @i = @i + 1;
        IF @i <> 4
        THEN
          SET @condition = GetSetting(concat('service_star_condition_', @i));
          IF @childrenCount >= @condition
          THEN
            SET @roleStar = @i;
          ELSE
            LEAVE starloop;
          END IF;
        END IF;

        IF @i >= 5
        THEN
          LEAVE starloop;
        END IF;
      END LOOP starloop;

      UPDATE zc_member
      SET role_star = @roleStar
      WHERE id = userId AND role_star < @roleStar;

    END out_label;
  END
;;
DELIMITER ;