
-- ------------------- 存储过程.-.1120.业绩结算.sql start ------------------------ 

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

            IF @parentStar = c_star
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


-- ------------------- 存储过程.-.1120.业绩结算.sql end ------------------------ 


-- ------------------- 存储过程.-.1120.事件-申请店长.sql start ------------------------ 

-- -------------------------------
-- 申请店长
-- -------------------------------
DROP PROCEDURE IF EXISTS `Event_applyService`;
DELIMITER ;;
CREATE PROCEDURE `Event_applyService`(
  IN  userId INT(11),
  OUT error  INT(11)
)
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

# 获取店长赠送兑换券金额
      SET @giveGoldCoinAmount = GetSetting(CONCAT('apply_service_give_goldcoin_amount'));
      IF @giveGoldCoinAmount <= 0
      THEN
        LEAVE out_label;
      END IF;

# 添加明细
      CALL AddAccountRecord(userId, 'goldcoin', 106, @giveGoldCoinAmount, UNIX_TIMESTAMP(), '', '申请店长赠送', 0, error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

# 分发店长销售奖（直推店长）
      CALL Income_recommendService(userId, error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

# 店长星级评定
      CALL Service_star(userId, error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

# 上线星级评定
      CALL Service_parentStar(userId, error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

# 获取店长赠送兑换券金额
      SET @servicePerformance = GetSetting(CONCAT('apply_service_performance'));
# 添加业绩累计对列
      IF @servicePerformance > 0
      THEN
        INSERT IGNORE INTO zc_performance_queue (user_id, performance_amount, order_id, queue_status, queue_addtime, queue_starttime, queue_endtime)
        VALUES (userId, @servicePerformance, 0, 0, unix_timestamp(), unix_timestamp(), unix_timestamp());

        CALL Income(userId, @servicePerformance, 0, error);
        IF error
        THEN
          LEAVE out_label;
        END IF;

      END IF;
    END out_label;
  END
;;
DELIMITER ;

-- ------------------- 存储过程.-.1120.事件-申请店长.sql end ------------------------ 


-- ------------------- 存储过程.-.1120.星级店长补贴.sql start ------------------------ 

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

            IF @parentStar = c_role_star
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

-- ------------------- 存储过程.-.1120.星级店长补贴.sql end ------------------------ 


-- ------------------- 存储过程.-.1121.收益.sql start ------------------------ 

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

# 分发星级VIP业绩补贴（级差）
      CALL Income_subsidyPerformance(userId, performanceAmount, orderId, error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

# 分发店长消费奖（上三级店长）
      CALL Income_consume(userId, performanceAmount, orderId, error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

# 分发星级店长补贴（级差）
      CALL Income_subsidyService(userId, performanceAmount, orderId, error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

    END out_label;
  END
;;
DELIMITER ;

-- ------------------- 存储过程.-.1121.收益.sql end ------------------------ 

