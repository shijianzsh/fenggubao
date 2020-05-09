DROP PROCEDURE IF EXISTS `Performance_star`;
DELIMITER ;;
CREATE PROCEDURE `Performance_star`(IN  userId    INT(11), OUT error     INT(11))
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

      SELECT
        ifnull(m.`star`, 0),
        p.performance_amount
      INTO @userStar, @userPerformanceAmount
      FROM zc_member AS m
        LEFT JOIN zc_performance AS p ON m.id = p.user_id
      WHERE
        m.id = userId
        AND p.performance_tag = 0;

      SELECT
        count(0) INTO @hasCondition
      FROM zc_performance_rule
      WHERE rule_amount * 10000 <= @userPerformanceAmount
      ORDER BY rule_id DESC
      LIMIT 1;
      IF @hasCondition = 0
      THEN
        LEAVE out_label;
      END IF;

      SET @performanceStar = 0, @conditionCount = 0, @conditionLevel = 0;
      SELECT
        rule_id,
        rule_condition_count,
        rule_condition_level
      INTO @performanceStar, @conditionCount, @conditionLevel
      FROM zc_performance_rule
      WHERE rule_amount * 10000 <= @userPerformanceAmount
      ORDER BY rule_id DESC
      LIMIT 1;

      IF @performanceStar <= @userStar
      THEN
        LEAVE out_label;
      END IF;

      IF @conditionCount > 0
      THEN
        SELECT count(0)
        INTO @childrenCount
        FROM zc_member
        WHERE find_in_set(userId, repath) AND star >= @conditionLevel;
        IF @childrenCount < @conditionCount
        THEN
          LEAVE out_label;
        END IF;
      END IF;

      UPDATE zc_member
      SET star = @performanceStar
      WHERE id = userId;

    END out_label;
  END
;;
DELIMITER ;


-- ----------------------------
-- 迁移锁定资产数据
-- ----------------------------
DROP PROCEDURE IF EXISTS `Repair_performance`;
DELIMITER ;;
CREATE PROCEDURE `Repair_performance`(OUT error INT(11))
  BEGIN

    declare done int default 0;
    declare c_user_id int default 0;

    # 获取所有释放队列
    declare c_user cursor for
    select user_id
    from
      zc_consume
    where
      amount > 0;

    declare continue handler for not found set done = 1;
    declare continue handler for sqlexception set error = 1; -- 异常错误
    set error = 0;
SET @c = 0;
    BEGIN

      open c_user;
      repeat fetch c_user into c_user_id;
      if not done
      then
        begin
            CALL Performance_star(c_user_id, error);
            SET @c = @c + 1;
        end;
      end if;
      until done end repeat;
      close c_user;
    END;
    select @c;
  END
;;
DELIMITER ;

CALL Repair_performance(@error);
select @error;
DROP PROCEDURE IF EXISTS `Repair_performance`;