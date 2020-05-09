
-- ------------------- 存储过程.-.1208.事件-代理专区赠送.sql start ------------------------ 

-- -------------------------------
-- 申请合伙人
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income_agentGive`;
DELIMITER ;;
CREATE PROCEDURE `Income_agentGive`(
  IN  userId INT(11),
  IN  performanceAmount DECIMAL(14, 4),
  IN  orderId           INT(11),
  OUT error  INT(11)
)
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      set @giveBei = GetSetting(CONCAT('give_goldcoin_bei'));
      if @giveBei <= 0
      then
        leave out_label;
      end if;

      -- 获取公让宝最新价格
      SET @goldcoinPrice = 1;
      SELECT amount INTO @goldcoinPrice FROM zc_goldcoin_prices ORDER BY id DESC LIMIT 1;

      SET @giveIncomeAmount = performanceAmount * @giveBei / @goldcoinPrice;

      SET @circulateBai = GetSetting('give_goldcoin_circulate_bai');
      IF @circulateBai > 0 THEN
        -- 添加明细
        CALL AddAccountRecord(userId, 'goldcoin', 103, @giveIncomeAmount * @circulateBai * 0.01, UNIX_TIMESTAMP(), concat('{"order_id":"', orderId, '"}'), 'xfzs', @giveBei, error);

        if error
        then
          leave out_label;
        end if;
      END IF;

      SET @lockBai = GetSetting('give_goldcoin_lock_bai');
      IF @lockBai > 0 THEN
        CALL AddLockGoldcoin(userId, @giveIncomeAmount * @lockBai * 0.01, error);
        if error
        then
          leave out_label;
        end if;
      END IF;

    END out_label;
  END
;;
DELIMITER ;

-- ------------------- 存储过程.-.1208.事件-代理专区赠送.sql end ------------------------ 


-- ------------------- 存储过程.-.1208.事件-注册.sql start ------------------------ 

-- -------------------------------
-- 订单完成事件
-- -------------------------------
DROP PROCEDURE IF EXISTS `Event_register`;
DELIMITER ;;
CREATE PROCEDURE `Event_register`(IN userId int(11), OUT error int(11))
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      # 生成推荐关系
      CALL Register_recommand(userId, error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

    END out_label;
  END
;;
DELIMITER ;

-- ------------------- 存储过程.-.1208.事件-注册.sql end ------------------------ 


-- ------------------- 存储过程.-.1208.事件-清空.sql start ------------------------ 

-- -------------------------------
--
-- -------------------------------
DROP PROCEDURE IF EXISTS `Event_adViewed`;
DELIMITER ;;
CREATE PROCEDURE `Event_adViewed`(IN  userId  INT(11),
  IN  adId    int(11),
  OUT status  tinyint(1),
  OUT message varchar(255),
  OUT error   INT(11)
  )
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

    END out_label;
  END
;;
DELIMITER ;

-- ------------------- 存储过程.-.1208.事件-清空.sql end ------------------------ 


-- ------------------- 存储过程.-.1208.事件-申请合伙人.sql start ------------------------ 

-- -------------------------------
-- 申请合伙人
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


    END out_label;
  END
;;
DELIMITER ;

-- ------------------- 存储过程.-.1208.事件-申请合伙人.sql end ------------------------ 


-- ------------------- 存储过程.-.1208.事件-订单完成.sql start ------------------------ 

-- -------------------------------
-- 订单完成事件
-- -------------------------------
DROP PROCEDURE IF EXISTS `Event_consume`;
DELIMITER ;;
CREATE PROCEDURE `Event_consume`(IN orderId INT(11), OUT error INT(11))
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN
      SET @orderStatus = 0;
      SELECT order_status INTO @orderStatus FROM zc_orders WHERE id = orderId;
      IF @orderStatus <> 4 THEN
        LEAVE out_label;
      END IF;

    END out_label;
  END
;;
DELIMITER ;

-- -------------------------------
-- 自动完成订单
-- -------------------------------
DROP PROCEDURE IF EXISTS `TimerTask_autoCompleteOrder`;
DELIMITER ;;
CREATE PROCEDURE `TimerTask_autoCompleteOrder`(OUT error INT(11))
  BEGIN

    DECLARE done INT DEFAULT 0;
    DECLARE c_order_id INT DEFAULT 0;

    DECLARE orders CURSOR FOR
      SELECT o.`id` AS order_id
      FROM
        `zc_orders` AS o
        LEFT JOIN `zc_order_affiliate` AS oa ON o.`id` = oa.order_id
      WHERE
        o.`order_status` = 3  # 已发货订单
        AND oa.`affiliate_sendtime` < UNIX_TIMESTAMP() - 3600 * 24 * 7; -- 发货时间超过7天
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; -- 异常错误
    SET error = 0;
    -- 获取当前时间
    SET @now = UNIX_TIMESTAMP();
    out_label: BEGIN
      OPEN orders;
      REPEAT
        FETCH orders
        INTO c_order_id;
        IF NOT done
        THEN
          BEGIN
            # 自动完成订单
            UPDATE `zc_orders` AS o, `zc_order_affiliate` AS of
            SET
              o.`order_status`            = 4, # 更订单改为已完成
              of.`affiliate_completetime` = @now
            WHERE
              o.`id` = of.`order_id`
              AND o.`id` = c_order_id;

--             # 调起消费事件
--             CALL Event_consume(c_order_id, error);
--             IF error
--             THEN
--               LEAVE out_label;
--             END IF;
          END;
        END IF;
      UNTIL done END REPEAT;
      CLOSE orders;
    END out_label;
  END
;;
DELIMITER ;






-- ------------------- 存储过程.-.1208.事件-订单完成.sql end ------------------------ 


-- ------------------- 存储过程.-.1208.事件.支付完成.sql start ------------------------ 

-- -------------------------------
-- 支付完成事件
-- -------------------------------
DROP PROCEDURE IF EXISTS `Event_paid`;
DELIMITER ;;
CREATE PROCEDURE `Event_paid`(IN orderId INT(11), OUT error INT(11))
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      SET @orderStatus = 0;
      SELECT order_status INTO @orderStatus FROM zc_orders WHERE id = orderId;
      IF @orderStatus <> 1 THEN
        LEAVE out_label;
      END IF;

      -- 激活个人代理
      CALL Event_activated(orderId, error);
      IF error THEN
        LEAVE out_label;
      END IF;

      -- 累计业绩
      CALL Performance_calculation(orderId, error);
      IF error THEN
        LEAVE out_label;
      END IF;

    END out_label;
  END
;;
DELIMITER ;

-- ------------------- 存储过程.-.1208.事件.支付完成.sql end ------------------------ 


-- ------------------- 存储过程.-.1208.事件.激活.sql start ------------------------ 

-- -------------------------------
-- 个人代理激活
-- -------------------------------
DROP PROCEDURE IF EXISTS `Event_activated`;
DELIMITER ;;
CREATE PROCEDURE `Event_activated`(IN orderId INT(11), OUT error INT(11))
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      -- 代理区商品，业绩计算比例，固定100%
      SET @agentPerformanceBai = 100;
      SET @performanceAmount = 0;
      SELECT
        o.uid,
        IFNULL(sum( op.price_cash * op.product_quantity * @agentPerformanceBai * 0.01 ), 0)
        INTO
          @userId,
          @performanceAmount
      FROM
        zc_orders AS o
          LEFT JOIN zc_order_product AS op ON o.id = op.order_id
          LEFT JOIN zc_product_affiliate as pa on op.product_id = pa.product_id
      WHERE
          o.id = orderId
        AND pa.block_id = 4;

      IF @performanceAmount <= 0 THEN
        LEAVE out_label;
      END IF;

      SET @performancePortionBase = GetSetting(concat('performance_portion_base'));
      SET @userLevel = 0;
      SELECT `level` INTO @userLevel FROM zc_member WHERE id = @userId AND is_lock = 0;
      IF @userLevel = 0 THEN
        LEAVE out_label;
      END IF;

      IF @userLevel = 2 OR @performanceAmount >= @performancePortionBase THEN
        CALL Income_agentGive(@userID, @performanceAmount, orderId, error);
        IF error THEN
          LEAVE out_label;
        END IF;
      END IF;

      IF @performanceAmount < @performancePortionBase THEN
        LEAVE out_label;
      END IF;

      IF @userLevel <= 1 THEN
        -- 激活个人代理
        UPDATE zc_member SET `level` = 2 WHERE id = @userId;
      END IF;

    END out_label;
  END
;;
DELIMITER ;



-- ------------------- 存储过程.-.1208.事件.激活.sql end ------------------------ 


-- ------------------- 存储过程.-.1208.收益-关爱奖.sql start ------------------------ 

-- -------------------------------
-- 收益 - 关爱奖
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income_care`;
DELIMITER ;;
CREATE PROCEDURE `Income_care`(
  IN  userId       INT(11),
  IN  incomeAmount DECIMAL(14, 4),
  IN  orderId      INT(11),
  OUT error        INT(11)
)
  BEGIN

    DECLARE done INT DEFAULT 0;
    DECLARE c_user_id INT DEFAULT 0;
    DECLARE c_consume_amount DECIMAL(14, 4) DEFAULT 0;

    # 获取所有直接下线
    DECLARE c_user CURSOR FOR
      SELECT
        m.id,
        c.amount
      FROM zc_member AS m
        LEFT JOIN zc_consume AS c ON m.id = c.user_id
      WHERE
        m.level = 2
        AND m.is_lock = 0
        AND c.is_out = 0
        AND m.reid = userId
        AND c.id IS NOT NULL;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; -- 异常错误
    SET error = 0;

    out_label: BEGIN
      IF incomeAmount < 1
      THEN
        LEAVE out_label;
      END IF;

      SET @performancePortionBase = GetSetting(concat('performance_portion_base'));
      IF @performancePortionBase <= 0
      THEN
        LEAVE out_label;
      END IF;

      SET @count = 0;

      SELECT floor(sum(c.amount) / @performancePortionBase)
      INTO @count
      FROM zc_member AS m
        LEFT JOIN zc_consume AS c ON m.id = c.user_id
      WHERE
        m.level = 2
        AND m.is_lock = 0
        AND c.is_out = 0
        AND m.reid = userId
        AND c.id IS NOT NULL
      LIMIT 1;
      IF @count = 0
      THEN
        LEAVE out_label;
      END IF;

      SET @incomeBai = GetSetting(CONCAT('prize_care_agent_bai'));
      IF @incomeBai <= 0
      THEN
        LEAVE out_label;
      END IF;

      SET @oneIncomeAmount = incomeAmount * @incomeBai * 0.01 / @count;
      IF @oneIncomeAmount < 1
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

              SET @careIncomeAmount = @oneIncomeAmount * @portion;

              -- 分发流通资产
              SET @circulateBai = GetSetting('prize_care_agent_circulate_bai');
              IF @circulateBai > 0 THEN
                -- 添加明细
                CALL AddAccountRecord(c_user_id, 'goldcoin', 108, @careIncomeAmount * @circulateBai * 0.01, UNIX_TIMESTAMP(),
                                    concat('{"order_id":"', orderId, '"}'), 'gaj', @incomeBai, error);
                if error then
                  leave out_label;
                end if;
              END IF;

              -- 分发锁定资产
              SET @lockBai = GetSetting('prize_care_agent_lock_bai');
              IF @lockBai > 0 THEN
                CALL AddLockGoldcoin(c_user_id, @careIncomeAmount * @lockBai * 0.01, error);
                IF error THEN
                  LEAVE out_label;
                END IF;
              END IF;

              -- 累计收益
              CALL Income_add(c_user_id, @careIncomeAmount, error);
              IF error
              THEN
                LEAVE out_label;
              END IF;

              -- 添加关爱奖队列
              INSERT INTO `zc_care_queue` VALUES (NULL, c_user_id, @careIncomeAmount, 0, UNIX_TIMESTAMP(), 0, 0);

            END out_repeat;
          END;
        END IF;
      UNTIL done END REPEAT;
      CLOSE c_user;
    END out_label;
  END
;;
DELIMITER ;


-- -------------------------------
-- 执行关爱奖队列
-- -------------------------------
DROP PROCEDURE IF EXISTS `Care_queue`;
DELIMITER ;;
CREATE PROCEDURE `Care_queue`(OUT error INT(11))
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      # 检查是否有队列正在执行
      SELECT count(0)
      INTO @hasLock
      FROM zc_care_queue
      WHERE queue_status = 1;
      IF @hasLock
      THEN
        LEAVE out_label;
      END IF;

      # 检查是否有队列需要执行
      SELECT count(0)
      INTO @hasQueue
      FROM zc_care_queue
      WHERE queue_status = 0;
      IF @hasQueue = 0
      THEN
        LEAVE out_label;
      END IF;

      SELECT
        id,
        user_id,
        income_amount
      INTO @queueId, @userId, @incomeAmount
      FROM zc_care_queue
      WHERE queue_status = 0
      ORDER BY id ASC
      LIMIT 1;

      UPDATE zc_care_queue
      SET queue_status = 1, queue_starttime = unix_timestamp()
      WHERE id = @queueId;

      # 开启事务
      START TRANSACTION;

      # 分发关爱奖
      CALL Income_care(@userId, @incomeAmount, 0, error);

      IF error
      THEN
        ROLLBACK; # 回滚
        UPDATE zc_care_queue
        SET queue_status = 2, queue_endtime = unix_timestamp()
        WHERE id = @queueId;
      ELSE
        COMMIT; # 提交
        UPDATE zc_care_queue
        SET queue_status = 3, queue_endtime = unix_timestamp()
        WHERE id = @queueId;
      END IF;

    END out_label;
  END
;;
DELIMITER ;

-- ------------------- 存储过程.-.1208.收益-关爱奖.sql end ------------------------ 


-- ------------------- 存储过程.-.1208.收益-区域合伙人奖.sql start ------------------------ 

-- -------------------------------
-- 收益 - 区域合伙人奖
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income_countyService`;
DELIMITER ;;
CREATE PROCEDURE `Income_countyService`(
  IN  userId            INT(11),
  IN  performanceAmount DECIMAL(14, 4),
  IN  orderId           INT(11),
  OUT error             INT(11)
)
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      SET @province = '', @city = '', @country = '';
      SELECT
        `province`,
        `city`,
        `country`
      INTO @province, @city, @country
      FROM zc_certification
      WHERE user_id = userId
      LIMIT 1;

      IF @province = '' OR @city = '' OR @country = ''
      THEN
        LEAVE out_label;
      END IF;

      SET @userId = 0;
      SELECT id
      INTO @userId
      FROM `zc_member`
      WHERE `role` = 3 AND `province` = @province AND `city` = @city AND `country` = @country
      LIMIT 1;
      IF @userId <= 0
      THEN
        LEAVE out_label;
      END IF;

      SET @incomeBai = GetSetting(CONCAT('prize_county_service_bai'));
      IF @incomeBai <= 0
      THEN
        LEAVE out_label;
      END IF;

      -- 获取公让宝最新价格
      SET @goldcoinPrice = 1;
      SELECT amount
      INTO @goldcoinPrice
      FROM zc_goldcoin_prices
      ORDER BY id DESC
      LIMIT 1;

      SET @countyIncomeAmount = performanceAmount * @incomeBai * 0.01 / @goldcoinPrice;

      -- 分发流通资产
      SET @circulateBai = GetSetting('prize_county_service_circulate_bai');
      IF @circulateBai > 0 THEN
        -- 添加明细
        CALL AddAccountRecord(@userId, 'goldcoin', 110, @countyIncomeAmount * @circulateBai * 0.01, UNIX_TIMESTAMP(),
                              concat('{"order_id":"', orderId, '"}'), 'qyhhrj', @incomeBai, error);
        if error then
          leave out_label;
        end if;
      END IF;

      -- 分发锁定资产
      SET @lockBai = GetSetting('prize_county_service_lock_bai');
      IF @lockBai > 0 THEN
        CALL AddLockGoldcoin(@userId, @countyIncomeAmount * @lockBai * 0.01, error);
        IF error THEN
          LEAVE out_label;
        END IF;
      END IF;

      -- 省级合伙人见点奖
      CALL Income_provinceServiceSee(userId, performanceAmount, orderId, error);

    END out_label;
  END
;;
DELIMITER ;

-- ------------------- 存储过程.-.1208.收益-区域合伙人奖.sql end ------------------------ 


-- ------------------- 存储过程.-.1208.收益-手动分红.sql start ------------------------ 

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
                CALL AddLockGoldcoin(c_user_id, @bonusIncomeAmount * @lockBai * 0.01, error);
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

-- ------------------- 存储过程.-.1208.收益-手动分红.sql end ------------------------ 


-- ------------------- 存储过程.-.1208.收益-省级合伙人奖.sql start ------------------------ 

-- -------------------------------
-- 收益 - 省级合伙人奖
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income_provinceService`;
DELIMITER ;;
CREATE PROCEDURE `Income_provinceService`(
  IN  userId            INT(11),
  IN  performanceAmount DECIMAL(14, 4),
  IN  orderId           INT(11),
  OUT error             INT(11)
)
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      SET @province = '';
      SELECT `province`
      INTO @province
      FROM zc_certification
      WHERE user_id = userId
      LIMIT 1;

      IF @province = ''
      THEN
        LEAVE out_label;
      END IF;

      SET @userId = 0;
      SELECT id
      INTO @userId
      FROM `zc_member`
      WHERE `role` = 4 AND `province` = @province
      LIMIT 1;

      IF @userId <= 0
      THEN
        LEAVE out_label;
      END IF;

      SET @incomeBai = GetSetting(CONCAT('prize_province_service_bai'));
      IF @incomeBai <= 0
      THEN
        LEAVE out_label;
      END IF;

      -- 获取公让宝最新价格
      SET @goldcoinPrice = 1;
      SELECT amount
      INTO @goldcoinPrice
      FROM zc_goldcoin_prices
      ORDER BY id DESC
      LIMIT 1;

      SET @provinceIncomeAmount = performanceAmount * @incomeBai * 0.01 / @goldcoinPrice;

      -- 分发流通资产
      SET @circulateBai = GetSetting('prize_province_service_circulate_bai');
      IF @circulateBai > 0 THEN
        -- 添加明细
        CALL AddAccountRecord(@userId, 'goldcoin', 111, @provinceIncomeAmount * @circulateBai * 0.01, UNIX_TIMESTAMP(),
                            concat('{"order_id":"', orderId, '"}'), 'sjhhrj', @incomeBai, error);
        if error then
          leave out_label;
        end if;
      END IF;

      -- 分发锁定资产
      SET @lockBai = GetSetting('prize_province_service_lock_bai');
      IF @lockBai > 0 THEN
        CALL AddLockGoldcoin(@userId, @provinceIncomeAmount * @lockBai * 0.01, error);
        IF error THEN
          LEAVE out_label;
        END IF;
      END IF;

    END out_label;
  END
;;
DELIMITER ;

-- ------------------- 存储过程.-.1208.收益-省级合伙人奖.sql end ------------------------ 


-- ------------------- 存储过程.-.1208.收益-省级合伙人见点奖.sql start ------------------------ 

-- -------------------------------
-- 收益 - 省级合伙人见点奖
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income_provinceServiceSee`;
DELIMITER ;;
CREATE PROCEDURE `Income_provinceServiceSee`(
  IN  userId            INT(11),
  IN  performanceAmount DECIMAL(14, 4),
  IN  orderId           INT(11),
  OUT error             INT(11)
)
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      SET @province = '';
      SELECT `province`
      INTO @province
      FROM zc_certification
      WHERE user_id = userId
      LIMIT 1;

      IF @province = ''
      THEN
        LEAVE out_label;
      END IF;

      SET @userId = 0;
      SELECT id
      INTO @userId
      FROM `zc_member`
      WHERE `role` = 4 AND `province` = @province
      LIMIT 1;

      IF @userId <= 0
      THEN
        LEAVE out_label;
      END IF;

      SET @incomeBai = GetSetting(CONCAT('prize_province_service_see_bai'));
      IF @incomeBai <= 0
      THEN
        LEAVE out_label;
      END IF;

      -- 获取公让宝最新价格
      SET @goldcoinPrice = 1;
      SELECT amount
      INTO @goldcoinPrice
      FROM zc_goldcoin_prices
      ORDER BY id DESC
      LIMIT 1;

      SET @provinceSeeIncomeAmount = performanceAmount * @incomeBai * 0.01 / @goldcoinPrice;

      -- 分发流通资产
      SET @circulateBai = GetSetting('prize_province_service_see_circulate_bai');
      IF @circulateBai > 0 THEN
        -- 添加明细
        CALL AddAccountRecord(@userId, 'goldcoin', 112, @provinceSeeIncomeAmount * @circulateBai * 0.01, UNIX_TIMESTAMP(),
                            concat('{"order_id":"', orderId, '"}'), 'sjhhrjdj', @incomeBai, error);
        if error then
          leave out_label;
        end if;
      END IF;

      -- 分发锁定资产
      SET @lockBai = GetSetting('prize_province_service_see_lock_bai');
      IF @lockBai > 0 THEN
        CALL AddLockGoldcoin(@userId, @provinceSeeIncomeAmount * @lockBai * 0.01, error);
        IF error THEN
          LEAVE out_label;
        END IF;
      END IF;

    END out_label;
  END
;;
DELIMITER ;

-- ------------------- 存储过程.-.1208.收益-省级合伙人见点奖.sql end ------------------------ 


-- ------------------- 存储过程.-.1208.收益-管理津贴.sql start ------------------------ 

-- -------------------------------
-- 收益 - 管理津贴
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income_subsidy`;
DELIMITER ;;
CREATE PROCEDURE `Income_subsidy`(
  IN  userId       INT(11),
  IN  incomeAmount DECIMAL(14, 4),
  IN  orderId      INT(11),
  OUT error        INT(11)
)
  BEGIN

    DECLARE done INT DEFAULT 0;
    DECLARE c_user_id INT DEFAULT 0;
    DECLARE c_consume_level INT DEFAULT 0;

    # 获取直推个人代理
    DECLARE c_user CURSOR FOR
      SELECT
        p.id,
        c.`level`
      FROM
        zc_member AS m
        LEFT JOIN zc_member AS p ON find_in_set(p.id, m.repath)
        LEFT JOIN zc_consume AS c ON p.id = c.user_id
      WHERE
        m.id = userId
        AND p.level = 2
        AND p.is_lock = 0
        AND c.user_id IS NOT NULL
        AND c.level > 0
        AND c.is_out = 0
      ORDER BY p.relevel DESC
      LIMIT 21;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; -- 异常错误
    SET error = 0;

    out_label: BEGIN

      SET @subsidyIncomeAmount = incomeAmount;

      OPEN c_user;
      REPEAT FETCH c_user
      INTO c_user_id, c_consume_level;
        IF NOT done
        THEN
          BEGIN

            out_repeat: BEGIN

              SET @subsidy_bai = 0;
              SELECT subsidy_bai
              INTO @subsidy_bai
              FROM zc_consume_rule
              WHERE `level` <= c_consume_level
              ORDER BY `level` DESC
              LIMIT 1;

              IF @subsidy_bai <= 0
              THEN
                LEAVE out_repeat;
              END IF;

              SET @subsidyIncomeAmount = @subsidyIncomeAmount * @subsidy_bai * 0.01;
              IF @subsidyIncomeAmount < 1
              THEN
                LEAVE out_label;
              END IF;

              -- 分发流通资产
              SET @circulateBai = GetSetting('subsidy_agent_circulate_bai');
              IF @circulateBai > 0 THEN
                -- 添加明细
                CALL AddAccountRecord(c_user_id, 'goldcoin', 105, @subsidyIncomeAmount * @circulateBai * 0.01, UNIX_TIMESTAMP(),
                                    concat('{"order_id":"', orderId, '"}'), 'gljt', @subsidy_bai, error);
                if error then
                  leave out_label;
                end if;
              END IF;

              -- 分发锁定资产
              SET @lockBai = GetSetting('subsidy_agent_lock_bai');
              IF @lockBai > 0 THEN
                CALL AddLockGoldcoin(c_user_id, @subsidyIncomeAmount * @lockBai * 0.01, error);
                IF error THEN
                  LEAVE out_label;
                END IF;
              END IF;

              -- 累计收益
              CALL Income_add(c_user_id, @subsidyIncomeAmount, error);
              IF error
              THEN
                LEAVE out_label;
              END IF;

            END out_repeat;

          END;
        END IF;
      UNTIL done END REPEAT;
      CLOSE c_user;
    END out_label;
  END
;;
DELIMITER ;

-- ------------------- 存储过程.-.1208.收益-管理津贴.sql end ------------------------ 


-- ------------------- 存储过程.-.1208.收益-销售奖.sql start ------------------------ 

-- -------------------------------
-- 收益 - 销售奖
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income_consume`;
DELIMITER ;;
CREATE PROCEDURE `Income_consume`(
  IN  userId            INT(11),
  IN  performanceAmount DECIMAL(14, 4),
  IN  orderId           INT(11),
  OUT error             INT(11)
)
  BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE c_user_id INT DEFAULT 0;

    # 获取上二级个人代理
    DECLARE c_user CURSOR FOR
      SELECT p.id
      FROM
        zc_member AS m
        LEFT JOIN zc_member AS p ON find_in_set(p.id, m.repath)
        LEFT JOIN zc_consume AS c ON p.id = c.user_id
      WHERE
        m.id = userId
        AND p.level = 2
        AND p.is_lock = 0
        AND c.is_out = 0
      ORDER BY p.relevel DESC
      LIMIT 2;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; -- 异常错误
    SET error = 0;

    SET @userLevel = 0;

    out_label: BEGIN

      OPEN c_user;
      REPEAT FETCH c_user
      INTO c_user_id;
        IF NOT done
        THEN
          BEGIN
            SET @userLevel = @userLevel + 1;

            SET @consumeBai = GetSetting(CONCAT('prize_agent_consume_bai_', @userLevel));
            IF @consumeBai <= 0
            THEN
              LEAVE out_label;
            END IF;

            -- 获取公让宝最新价格
            SET @goldcoinPrice = 1;
            SELECT amount
            INTO @goldcoinPrice
            FROM zc_goldcoin_prices
            ORDER BY id DESC
            LIMIT 1;

            SET @consumeIncomeAmount = performanceAmount * @consumeBai * 0.01 / @goldcoinPrice;

            -- 分发流通资产
            SET @circulateBai = GetSetting(CONCAT('prize_agent_consume_circulate_bai_', @userLevel));
            IF @circulateBai > 0 THEN
              -- 添加明细
              CALL AddAccountRecord(c_user_id, 'goldcoin', 104, @consumeIncomeAmount * @circulateBai * 0.01, UNIX_TIMESTAMP(),
                                  concat('{"order_id":"', orderId, '"}'), 'xsj', @consumeBai, error);
              if error then
                leave out_label;
              end if;
            END IF;

            -- 分发锁定资产
            SET @lockBai = GetSetting(CONCAT('prize_agent_consume_lock_bai_', @userLevel));
            IF @lockBai > 0 THEN
              CALL AddLockGoldcoin(c_user_id, @consumeIncomeAmount * @lockBai * 0.01, error);
              IF error THEN
                LEAVE out_label;
              END IF;
            END IF;

            -- 累计收益
            CALL Income_add(c_user_id, @consumeIncomeAmount, error);
            IF error
            THEN
              LEAVE out_label;
            END IF;

            -- 分发管理津贴
            CALL Income_subsidy(c_user_id, @consumeIncomeAmount, orderId, error);
            IF error
            THEN
              LEAVE out_label;
            END IF;

            -- 分发关爱奖
            CALL Income_care(c_user_id, @consumeIncomeAmount, orderId, error);

          END;
        END IF;
      UNTIL done END REPEAT;
      CLOSE c_user;
    END out_label;
  END
;;
DELIMITER ;

-- ------------------- 存储过程.-.1208.收益-销售奖.sql end ------------------------ 


-- ------------------- 存储过程.-.1208.收益.sql start ------------------------ 

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

      CALL Income_consume(userId, performanceAmount, orderId, error);
      if error
      then
        leave out_label;
      end if;

      CALL Income_countyService(userId, performanceAmount, orderId, error);
      if error
      then
        leave out_label;
      end if;

      CALL Income_provinceService(userId, performanceAmount, orderId, error);
      if error
      then
        leave out_label;
      end if;

    END out_label;
  END
;;
DELIMITER ;

-- ------------------- 存储过程.-.1208.收益.sql end ------------------------ 


-- ------------------- 存储过程.-.1208.计算业绩.sql start ------------------------ 

-- -------------------------------
-- 计算业绩
-- -------------------------------
DROP PROCEDURE IF EXISTS `Performance_calculation`;
DELIMITER ;;
CREATE PROCEDURE `Performance_calculation`(IN orderId INT(11), OUT error INT(11))
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      SET @performanceAmount = 0;

      SELECT
        o.uid,
        sum(op.price_cash * op.product_quantity * op.performance_bai_cash * 0.01),
        m.reid
      INTO
        @userId,
        @performanceAmount,
        @parentId
      FROM
        zc_orders AS o
        LEFT JOIN zc_order_product AS op ON o.id = op.order_id
        LEFT JOIN zc_member AS m ON o.uid = m.id
      WHERE
        o.id = orderId;

      IF @performanceAmount <= 0
      THEN
        LEAVE out_label;
      END IF;

      -- 计算消费者自己的业绩
      INSERT IGNORE INTO `zc_consume` (`user_id`) VALUE (@userId);
      SELECT
        `amount`,
        `level`
      INTO @userPerformanceAmount, @userLevel
      FROM `zc_consume`
      WHERE `user_id` = @userId;
      SET @userPerformanceAmount = @userPerformanceAmount + @performanceAmount;

      SET @newLevel = 0;
      SELECT `level`
      INTO @newLevel
      FROM zc_consume_rule
      WHERE amount <= @userPerformanceAmount
      ORDER BY `level` DESC
      LIMIT 1;

      IF @newLevel > @userLevel
      THEN
        SET @userLevel = @newLevel;
      END IF;

      UPDATE `zc_consume`
      SET `amount` = @userPerformanceAmount, `level` = @userLevel, `uptime` = UNIX_TIMESTAMP()
      WHERE `user_id` = @userId;

      CALL Income_add(@userId, 0, error);
      IF error
      THEN
        LEAVE out_label;
      END IF;


      CALL Income_add(@parentID, 0, error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      -- 计算上级业绩
      INSERT IGNORE INTO zc_performance_queue (user_id, performance_amount, order_id, queue_status, queue_addtime, queue_starttime, queue_endtime)
      VALUES (@userId, @performanceAmount, orderId, 0, unix_timestamp(), unix_timestamp(), unix_timestamp());

      -- 分发收益
      CALL Income(@userId, @performanceAmount, orderId, error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

    END out_label;
  END
;;
DELIMITER ;

-- -------------------------------
-- 执行业绩结算队列
-- -------------------------------
DROP PROCEDURE IF EXISTS `Performance_queue`;
DELIMITER ;;
CREATE PROCEDURE `Performance_queue`(OUT error INT(11))
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      # 检查是否有队列正在执行
      SELECT count(0)
      INTO @hasLock
      FROM zc_performance_queue
      WHERE queue_status = 1;
      IF @hasLock
      THEN
        LEAVE out_label;
      END IF;

      SET @queueId = 0, @userId = 0, @performanceAmount = 0, @orderId = 0;
      SELECT
        queue_id,
        user_id,
        performance_amount,
        order_id
      INTO @queueId, @userId, @performanceAmount, @orderId
      FROM zc_performance_queue
      WHERE queue_status = 0
      ORDER BY queue_id ASC
      LIMIT 1;
      IF @queueId = 0
      THEN
        LEAVE out_label;
      END IF;

      UPDATE zc_performance_queue
      SET queue_status = 1, queue_starttime = unix_timestamp()
      WHERE queue_id = @queueId;

      # 开启事务
      START TRANSACTION;

      CALL Performance_add(0, @performanceAmount, error);

      IF error <> 1
      THEN
        # 向上统计业绩
        CALL Performance_batch(@userId, @performanceAmount, error);
      END IF;

      IF error <> 1
      THEN
        CALL Performance_starBatch(@userId, error);
      END IF;

      IF error
      THEN
        ROLLBACK; # 回滚
        UPDATE zc_performance_queue
        SET queue_status = 2, queue_endtime = unix_timestamp()
        WHERE queue_id = @queueId;
      ELSE
        COMMIT; # 提交
        UPDATE zc_performance_queue
        SET queue_status = 3, queue_endtime = unix_timestamp()
        WHERE queue_id = @queueId;
      END IF;

    END out_label;
  END
;;
DELIMITER ;


-- -------------------------------
-- 批量结算用户上线业绩
-- -------------------------------
DROP PROCEDURE IF EXISTS `Performance_batch`;
DELIMITER ;;
CREATE PROCEDURE `Performance_batch`(
  IN  queueUserId INT(11),
  IN  amount      DECIMAL(14, 4),
  OUT error       INT(11)
)
  BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE c_user_id INT DEFAULT 0;

    # 用户所有上级推荐人
    DECLARE c_user CURSOR FOR
      SELECT p.id
      FROM
        zc_member AS m
        LEFT JOIN zc_member AS p ON find_in_set(p.id, m.repath)
      WHERE
        m.id = queueUserId
        AND p.`level` IN (2)  # 个人代理
        AND p.is_lock = 0;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; #异常错误
    SET error = 0;
    out_label: BEGIN

      OPEN c_user;
      REPEAT
        FETCH c_user
        INTO c_user_id;
        IF NOT done
        THEN
          BEGIN
            CALL Performance_add(c_user_id, amount, error);
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
# -- 累计业绩
# -- -------------------------------
DROP PROCEDURE IF EXISTS `Performance_add`;
DELIMITER ;;
CREATE PROCEDURE `Performance_add`(IN userId INT(11), IN amount DECIMAL(14, 4), OUT error INT(11))
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      SET @now = UNIX_TIMESTAMP();

      # 初始化总业绩
      INSERT IGNORE INTO zc_performance (user_id, performance_amount, performance_tag, performance_uptime)
      VALUES (userId, 0, 0, @now);

      # 初始化当月业绩
      INSERT IGNORE INTO zc_performance (user_id, performance_amount, performance_tag, performance_uptime)
      VALUES (userId, 0, from_unixtime(@now, '%Y%m'), @now);

      # 累计业绩
      UPDATE zc_performance
      SET performance_amount = performance_amount + amount, performance_uptime = @now
      WHERE
        user_id = userId
        AND performance_tag IN (0, from_unixtime(@now, '%Y%m'));

      # 创建当月业绩表
      SET @v_sql = CONCAT('CREATE TABLE IF NOT EXISTS `zc_performance_', from_unixtime(@now, '%Y%m'),
                          '` LIKE `zc_performance`;');
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      # 初始化当日业绩
      SET @v_sql = CONCAT(
          'INSERT IGNORE INTO zc_performance_', from_unixtime(@now, '%Y%m'),
          ' (user_id, performance_amount, performance_tag, performance_uptime) VALUES (',
          userId, ', 0, ', from_unixtime(@now, '%Y%m%d'), ', ', @now, ');');
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      # 累计当日业绩
      SET @v_sql = CONCAT('update zc_performance_', from_unixtime(@now, '%Y%m'),
                          ' set performance_amount = performance_amount + ', amount, ', performance_uptime = ', @now,
                          ' where user_id in (', userId, ') and performance_tag = ', from_unixtime(@now, '%Y%m%d'),
                          ';');
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

    END out_label;
  END
;;
DELIMITER ;

-- -------------------------------
-- 批量结算用户上线业绩
-- -------------------------------
DROP PROCEDURE IF EXISTS `Performance_starBatch`;
DELIMITER ;;
CREATE PROCEDURE `Performance_starBatch`(
  IN  queueUserId INT(11),
  OUT error       INT(11)
)
  BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE c_user_id INT DEFAULT 0;

    # 用户所有上级推荐人
    DECLARE c_user CURSOR FOR
      SELECT p.id
      FROM
        zc_member AS m
        LEFT JOIN zc_member AS p ON find_in_set(p.id, m.repath)
      WHERE
        m.id = queueUserId
        AND p.`level` IN (2)  # 个人代理
        AND p.is_lock = 0;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; #异常错误
    SET error = 0;
    out_label: BEGIN

      OPEN c_user;
      REPEAT
        FETCH c_user
        INTO c_user_id;
        IF NOT done
        THEN
          BEGIN
            CALL Performance_star(c_user_id, error);
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
DROP PROCEDURE IF EXISTS `Performance_star`;
DELIMITER ;;
CREATE PROCEDURE `Performance_star`(
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

      SELECT
        ifnull(m.`star`, 0),
        p.performance_amount
      INTO @userStar, @userPerformanceAmount
      FROM zc_member AS m
        LEFT JOIN zc_performance AS p ON m.id = p.user_id
      WHERE
        m.id = userId
        AND p.performance_tag = 0;

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


-- ------------------- 存储过程.-.1208.计算业绩.sql end ------------------------ 


-- ------------------- 存储过程.-.1208.释放锁定资产.sql start ------------------------ 

-- -------------------------------
-- 收益 - 累计所有收益
-- -------------------------------
DROP PROCEDURE IF EXISTS `Release_lock`;
DELIMITER ;;
CREATE PROCEDURE `Release_lock`(
  OUT error             INT(11)
)
  BEGIN

    declare done int default 0;
    declare c_queue_id int default 0;
    declare c_user_id int default 0;
    declare c_release_amount int default 0;

    # 获取所有释放队列
    declare c_user cursor for
    select lq.id, lq.user_id, lq.total_amount * lq.release_rate * 0.01
    from
      zc_lock_queue as lq
        left join zc_member as m on lq.user_id = m.id
    where
      lq.total_amount > lq.release_amount
      AND lq.tag <> FROM_UNIXTIME(UNIX_TIMESTAMP(), '%Y%m%d')
      AND m.is_lock = 0;

    declare continue handler for not found set done = 1;
    declare continue handler for sqlexception set error = 1; -- 异常错误
    set error = 0;

    out_label: BEGIN


      SET @tag = FROM_UNIXTIME(UNIX_TIMESTAMP(), '%Y%m%d');

      open c_user;
      repeat fetch c_user into c_queue_id, c_user_id,  c_release_amount;
      if not done
      then
        begin
          out_repeat:BEGIN

            UPDATE zc_lock_queue SET
              release_amount = release_amount + c_release_amount,
              `tag` = @tag,
              `uptime` = UNIX_TIMESTAMP()
            WHERE id = c_queue_id;

            UPDATE zc_lock SET
              release_amount = release_amount + c_release_amount,
              lock_amount = lock_amount - c_release_amount,
              `uptime` = UNIX_TIMESTAMP()
            WHERE user_id = c_user_id;

            # 添加明细
            CALL AddAccountRecord(c_user_id, 'goldcoin', 113, c_release_amount , UNIX_TIMESTAMP(), '', 'sdjcsf', 0, error);

            if error
            then
              leave out_label;
            end if;

          END out_repeat;
        end;
      end if;
      until done end repeat;
      close c_user;
    END out_label;
  END
;;
DELIMITER ;





-- ------------------- 存储过程.-.1208.释放锁定资产.sql end ------------------------ 


-- ------------------- 存储过程.-.1208.验证是否出局.sql start ------------------------ 

-- -------------------------------
-- 收益 - 累计所有收益并验证是否出局
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income_add`;
DELIMITER ;;
CREATE PROCEDURE `Income_add`(
  IN  userId       INT(11),
  IN  incomeAmount DECIMAL(14, 4),
  OUT error        INT(11)
)
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      -- 累计收益
      INSERT IGNORE INTO `zc_consume` (`user_id`) VALUE (userId);
      IF incomeAmount > 0
      THEN
        UPDATE `zc_consume`
        SET `income_amount` = `income_amount` + incomeAmount
        WHERE user_id = userId;
      END IF;

      -- 验证是否出局
      SET @totalConsumeAmount = 0, @level = 0, @totalIncomeAmount = 0, @isOut = 0, @outBei = 0;
      SELECT
        c.amount,
        c.level,
        c.income_amount,
        c.is_out,
        cr.out_bei
      INTO @totalConsumeAmount, @level, @totalIncomeAmount, @isOut, @outBei
      FROM `zc_consume` AS c
        LEFT JOIN
        zc_consume_rule AS cr ON c.level = cr.level
      WHERE c.user_id = userId AND cr.id IS NOT NULL;

      IF @totalConsumeAmount = 0 AND @level = 0 AND @totalIncomeAmount = 0 AND @isOut = 0 AND @outBei = 0
      THEN
        LEAVE out_label;
      END IF;

      SET @goldcoinPrice = 1;
      SELECT amount
      INTO @goldcoinPrice
      FROM zc_goldcoin_prices
      ORDER BY id DESC
      LIMIT 1;

      IF @totalIncomeAmount * @goldcoinPrice >= @totalConsumeAmount * @outBei
      THEN
        SET @isOut = 1;
      ELSE
        SET @isOut = 0;
      END IF;

      IF @isOut = 1 AND @level = 5
      THEN
        SELECT COUNT(0)
        INTO @coutLevel5
        FROM zc_consume AS c
          LEFT JOIN zc_member AS m ON c.user_id = m.reid
        WHERE c.user_id = userId AND c.level = 5;
        IF @coutLevel5 > 1
        THEN
          SET @isOut = 0;
        END IF;
      END IF;

      UPDATE `zc_consume`
      SET `is_out` = @isOut, uptime = unix_timestamp()
      WHERE user_id = userId;

    END out_label;
  END
;;
DELIMITER ;

-- ------------------- 存储过程.-.1208.验证是否出局.sql end ------------------------ 


-- ------------------- 存储过程.-.1212.事件.sql start ------------------------ 

# 开启事件
-- SET GLOBAL event_scheduler = 1;

-- ----------------------------
-- Event structure for delete_log_day_30
-- ----------------------------
DROP EVENT IF EXISTS `delete_log_day_30`;
DELIMITER ;;
CREATE EVENT `delete_log_day_30`
  ON SCHEDULE EVERY 1 DAY
    STARTS '2017-03-01 23:59:59'
  ON COMPLETION PRESERVE
  ENABLE DO BEGIN
  CALL DeleteLog(@error);
END
;;
DELIMITER ;

-- ----------------------------
-- Event structure for delete_withdraw_cash_month_1
-- ----------------------------
DROP EVENT IF EXISTS `delete_withdraw_cash_month_1`;

-- ----------------------------
-- Event structure for event_delete_phonecode
-- ----------------------------
DROP EVENT IF EXISTS `event_delete_phonecode`;
DELIMITER ;;
CREATE EVENT `event_delete_phonecode`
  ON SCHEDULE EVERY 1 DAY
    STARTS '2017-05-22 00:00:00'
  ON COMPLETION PRESERVE
  ENABLE DO BEGIN
  DELETE FROM zc_phonecode
  WHERE from_unixtime(post_time, '%Y%m%d') < from_unixtime(unix_timestamp(), '%Y%m%d');
END
;;
DELIMITER ;

-- ----------------------------
-- 自动完成订单（每天0时执行）
-- ----------------------------
DROP EVENT IF EXISTS `everyday_autoCompleteOrder`;
DELIMITER ;;
CREATE EVENT `everyday_autoCompleteOrder`
  ON SCHEDULE EVERY 1 DAY
    STARTS '2018-09-07 00:00:00'
  ON COMPLETION PRESERVE
  ENABLE DO BEGIN

  CALL TimerTask_autoCompleteOrder(@error);

END
;;
DELIMITER ;



-- ----------------------------
-- Event structure for everyday_Release
-- ----------------------------
DROP EVENT IF EXISTS `everyday_Release`;
DELIMITER ;;
CREATE EVENT `everyday_Release`
  ON SCHEDULE EVERY 10 SECOND
    STARTS '2018-12-01 00:00:00'
  ON COMPLETION PRESERVE
  ENABLE DO BEGIN

  SET @switch = GetSetting(CONCAT('goldcoin_release_switch'));
  IF @switch = '开启'
  THEN
    CALL Release_lock(@error);
  END IF;

END
;;
DELIMITER ;

-- ----------------------------
-- Event structure for everyday_statistics
-- ----------------------------
DROP EVENT IF EXISTS `everyday_statistics`;
DELIMITER ;;
CREATE EVENT `everyday_statistics`
  ON SCHEDULE EVERY 1 DAY
    STARTS '2018-09-07 00:00:00'
  ON COMPLETION PRESERVE
  ENABLE DO BEGIN
  CALL TimerTask(@error);
END
;;
DELIMITER ;

-- ----------------------------
-- Event structure for everyday_subsidy
-- ----------------------------
DROP EVENT IF EXISTS `everyday_subsidy`;

-- ----------------------------
-- Event structure for hour_executeCancelUnpaidOrder
-- ----------------------------
DROP EVENT IF EXISTS `hour_executeCancelUnpaidOrder`;
DELIMITER ;;
CREATE EVENT `hour_executeCancelUnpaidOrder`
  ON SCHEDULE EVERY 1 HOUR
    STARTS '2018-09-07 00:00:00'
  ON COMPLETION PRESERVE
  ENABLE DO BEGIN
  out_label: BEGIN
    # 开启事务
    START TRANSACTION;
    CALL CancelUnpaidOrder(@error);

    IF @error = 1
    THEN
      ROLLBACK; # 回滚
      LEAVE out_label;
    ELSE
      COMMIT; # 提交
    END IF;
  END out_label;
END
;;
DELIMITER ;

-- ----------------------------
-- Event structure for minute_executePerformanceQueue
-- ----------------------------
DROP EVENT IF EXISTS `minute_executePerformanceQueue`;
DROP EVENT IF EXISTS `everysecond_executePerformanceQueue`;
DELIMITER ;;
CREATE EVENT `everysecond_executePerformanceQueue`
  ON SCHEDULE EVERY 5 SECOND
    STARTS '2018-09-07 00:00:00'
  ON COMPLETION PRESERVE
  ENABLE DO BEGIN
  CALL Performance_queue(@error);
END
;;
DELIMITER ;


-- ----------------------------
-- 释放锁定资产（每天0时执行）
-- ----------------------------
DROP EVENT IF EXISTS `everyday_Release`;
DELIMITER ;;
CREATE EVENT `everyday_Release`
  ON SCHEDULE EVERY 1 DAY
    STARTS '2018-12-01 00:00:00'
  ON COMPLETION PRESERVE
  ENABLE DO BEGIN

  SET @switch = GetSetting(CONCAT('goldcoin_release_switch'));
  IF @switch = '开启'
  THEN
    CALL Release_lock(@error);
  END IF;

END
;;
DELIMITER ;


-- ----------------------------
-- 分发关爱奖收益（每天0时执行）
-- ----------------------------
DROP EVENT IF EXISTS `everysecond_Care`;
DELIMITER ;;
CREATE EVENT `everysecond_Care`
  ON SCHEDULE EVERY 5 SECOND
    STARTS '2018-12-01 00:00:00'
  ON COMPLETION PRESERVE
  ENABLE DO BEGIN

  CALL Care_queue(@error);

END
;;
DELIMITER ;

-- ------------------- 存储过程.-.1212.事件.sql end ------------------------ 


-- ------------------- 存储过程.-.1213.初始化数据表.sql start ------------------------ 



DROP PROCEDURE IF EXISTS `TimerTask_recordtable`;
DELIMITER ;;
CREATE PROCEDURE `TimerTask_recordtable`(OUT error INT(11))
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;


    SET @month_tag = FROM_UNIXTIME(unix_timestamp(), '%Y%m');
    SET @nextmonth_tag = DATE_FORMAT(DATE_ADD(@month_tag * 100 + 1, INTERVAL 1 MONTH), '%Y%m');

    # 创建当月现金币交易记录表
    SET @v_sql = CONCAT('CREATE TABLE IF NOT EXISTS `zc_account_cash_', @month_tag, '` LIKE `zc_account_record`;');
    PREPARE statement FROM @v_sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    # 创建下月现金币交易记录表
    SET @v_sql = CONCAT('CREATE TABLE IF NOT EXISTS `zc_account_cash_', @nextmonth_tag, '` LIKE `zc_account_record`;');
    PREPARE statement FROM @v_sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    # 创建当月现金币交易记录表
    SET @v_sql = CONCAT('CREATE TABLE IF NOT EXISTS `zc_account_goldcoin_', @month_tag, '` LIKE `zc_account_record`;');
    PREPARE statement FROM @v_sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    # 创建下月现金币交易记录表
    SET @v_sql = CONCAT('CREATE TABLE IF NOT EXISTS `zc_account_goldcoin_', @nextmonth_tag,
                        '` LIKE `zc_account_record`;');
    PREPARE statement FROM @v_sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    # 创建当月业绩表
    SET @v_sql = CONCAT('CREATE TABLE IF NOT EXISTS `zc_performance_', @month_tag,
                        '` LIKE `zc_performance`;');
    PREPARE statement FROM @v_sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    # 创建下月业绩表
    SET @v_sql = CONCAT('CREATE TABLE IF NOT EXISTS `zc_performance_', @nextmonth_tag,
                        '` LIKE `zc_performance`;');
    PREPARE statement FROM @v_sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;
  END
;;
DELIMITER ;


-- ------------------- 存储过程.-.1213.初始化数据表.sql end ------------------------ 


-- ------------------- 存储过程.-.1213.删除无用函数.sql start ------------------------ 

DROP PROCEDURE IF EXISTS `AddAccountRecord_taxfee`;
DROP PROCEDURE IF EXISTS `AdViewed_checkTime`;
DROP FUNCTION IF EXISTS `AdViewed_getUserTag`;
DROP PROCEDURE IF EXISTS `DeleteWithdrawCash`;
DROP PROCEDURE IF EXISTS `Income_consumeBack`;
DROP PROCEDURE IF EXISTS `Income_consumeBack_gift`;
DROP PROCEDURE IF EXISTS `Income_merchant`;
DROP PROCEDURE IF EXISTS `Income_subsidyCompany`;
DROP PROCEDURE IF EXISTS `Income_subsidyPartner`;
DROP PROCEDURE IF EXISTS `Income_subsidyPerformance`;
DROP PROCEDURE IF EXISTS `Income_subsidyService`;
DROP PROCEDURE IF EXISTS `Income_subsidyStarService`;
DROP PROCEDURE IF EXISTS `PerformanceBonus_batchPartner`;
DROP PROCEDURE IF EXISTS `PerformanceBonus_batchRole`;
DROP PROCEDURE IF EXISTS `PerformanceBonus_batchStar`;
DROP PROCEDURE IF EXISTS `PerformanceBonusTask_add`;
DROP PROCEDURE IF EXISTS `PerformanceReward_batch`;
DROP PROCEDURE IF EXISTS `PerformanceReward_record`;
DROP PROCEDURE IF EXISTS `PerformanceRewardTask_add`;
DROP PROCEDURE IF EXISTS `PerformanceRewardTask_autoAdd`;
DROP PROCEDURE IF EXISTS `PerformanceRewardTask_execute`;
DROP PROCEDURE IF EXISTS `PersonalTax`;
DROP PROCEDURE IF EXISTS `SystemManageFee`;
DROP PROCEDURE IF EXISTS `Income_activated`;


-- ------------------- 存储过程.-.1213.删除无用函数.sql end ------------------------ 


-- ------------------- 存储过程.-.1213.添加明细.sql start ------------------------ 

DROP PROCEDURE IF EXISTS `AddAccountRecord`;
DELIMITER ;;
CREATE PROCEDURE `AddAccountRecord`(
  IN userId   INT(11),
  IN currency VARCHAR(50),
  IN action   INT(11),
  IN amount   FLOAT,
  IN addtime  INT(11),
  IN attach   VARCHAR(1000),
  IN remark   VARCHAR(1000),
  IN exchange FLOAT, OUT error INT(11))
  BEGIN
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN
      # 金额=0, 不计入明细
      IF TRUNCATE(amount, 4) = 0
      THEN
        LEAVE out_label;
      END IF;

      # 动态生成表名
      SET @to_table = CONCAT('zc_account_', currency, '_', from_unixtime(addtime, '%Y%m'));

      # 检查并创建对应交易记录表
      SET @v_sql = CONCAT('CREATE TABLE IF NOT EXISTS `', @to_table, '` LIKE `zc_account_record`;');
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @attach = attach;
      IF @attach = ''
      THEN
        SET @attach = '{}';
      END IF;

      -- 初始化用户实时收支数据
      INSERT IGNORE INTO zc_account (user_id, account_tag, account_uptime) VALUES (userId, 0, addtime);
      -- 初始化用户日收支数据
      INSERT IGNORE INTO zc_account (
        user_id,
        account_cash_balance,
        account_tag,
        account_uptime)
        SELECT
          user_id,
          account_cash_balance,
          @account_tag AS account_tag,
          addtime      AS account_uptime
        FROM zc_account
        WHERE user_id = userId AND account_tag = 0;

      SET @account_tag = from_unixtime(addtime, '%Y%m%d');
      SET @field_balance = CONCAT('account_', currency, '_balance');
      SET @field_expenditure = CONCAT('account_', currency, '_expenditure');
      SET @field_income = CONCAT('account_', currency, '_income');

      SET @old_balance = 0;
      # 获取帐户余额
      SET @v_sql = CONCAT('SELECT ', @field_balance, ' INTO @old_balance FROM zc_account WHERE
                          account_tag = 0 AND user_id = ', userId, ' LIMIT 1;');
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      # 插入明细
      SET @v_sql = CONCAT(' INSERT INTO ', @to_table, ' VALUES(
      NULL,
      ', userId, ',
      \'', currency, '\',
      ', action, ',
      ', amount, ',
      ', (@old_balance + amount), ',
      ', addtime, ',
      \'', @attach, '\',
      \'', remark, '\',
      \'', exchange, '\')');
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @record_id = LAST_INSERT_ID(); # 获取明细ID
      IF @record_id = 0
      THEN
        SET error = 1;
        LEAVE out_label;
      END IF;

      # 更新用户实时收支总计和余额
      SET @v_sql = CONCAT('UPDATE zc_account SET
        ', @field_balance, ' = ', @field_balance, ' + ', amount, ',
        ', @field_expenditure, ' = ', @field_expenditure, ' + ', IF(amount < 0, amount, 0), ',
        ', @field_income, ' = ', @field_income, ' + ', IF(amount > 0, amount, 0), ',
        account_uptime = ', addtime, '
        WHERE user_id = ', userId, ' AND account_tag = 0');
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      # 更新用户当日收支总计和余额
      SET @v_sql = CONCAT('UPDATE zc_account AS za, zc_account AS za0 SET
        za.', @field_balance, ' = za0.', @field_balance, ',
        za.', @field_expenditure, ' = za.', @field_expenditure, ' + ', IF(amount < 0, amount, 0), ',
        za.', @field_income, ' = za.', @field_income, ' + ', IF(amount > 0, amount, 0), ',
        za.account_uptime = ', addtime, '
        WHERE za.user_id = za0.user_id
        AND za.user_id = ', userId, '
        AND za0.user_id = ', userId, '
        AND za.account_tag = ', @account_tag, '
        AND za0.account_tag = 0;');
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

    END out_label;
  END
;;
DELIMITER ;





-- ------------------- 存储过程.-.1213.添加明细.sql end ------------------------ 


-- ------------------- 存储过程.-.1213.添加锁定资产.sql start ------------------------ 

DROP PROCEDURE IF EXISTS `AddLockGoldcoin`;
DELIMITER ;;
CREATE PROCEDURE `AddLockGoldcoin`(
  IN userId   INT(11),
  IN lockAmount FLOAT,
  OUT error INT(11)
)
  BEGIN
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      INSERT IGNORE INTO `zc_lock`(`user_id`, `tag`) VALUE (userId, '0');
      UPDATE `zc_lock`
      SET
        `total_amount` = `total_amount` + lockAmount,
        `lock_amount` = `lock_amount` + lockAmount,
        `uptime` = UNIX_TIMESTAMP()
      WHERE `user_id` = userId AND `tag` = 0;

      SET @releaseBai = GetSetting('goldcoin_release_bai');

      INSERT INTO `zc_lock_queue` VALUE(NULL, userId, lockAmount, 0, @releaseBai, 0, '', UNIX_TIMESTAMP(), 0);

    END out_label;
  END
;;
DELIMITER ;





-- ------------------- 存储过程.-.1213.添加锁定资产.sql end ------------------------ 


-- ------------------- 存储过程.-.1213.用户收益统计.sql start ------------------------ 

-- ----------------------------
-- 定时统计-用户收益数据
-- ----------------------------
DROP PROCEDURE IF EXISTS `TimerTask_AccountIncome`;
DELIMITER ;;
CREATE PROCEDURE `TimerTask_AccountIncome`(IN nowtime INT(11), OUT error INT(11))
  BEGIN

    SET @now = nowtime;
    # 今日tag
    SET @day_tag = FROM_UNIXTIME(@now, '%Y%m%d');
    # 当月tag
    SET @moth_tag = FROM_UNIXTIME(@now, '%Y%m');
    # 当年tag
    SET @year_tag = FROM_UNIXTIME(@now, '%Y');
    # 昨日tag
    SET @yesterday_tag = DATE_FORMAT(DATE_ADD(@day_tag, INTERVAL -1 DAY), '%Y%m%d');
    # 上月tag
    SET @lastmoth_tag = DATE_FORMAT(DATE_ADD(@day_tag, INTERVAL -1 MONTH), '%Y%m');
    # 去年tag
    SET @lastyear_tag = @year_tag - 1;

#     #统计用户昨日收益数据
#     SET @task_id = TimerTask_add('Statistics_AccountIncomeDay', CONCAT(@yesterday_tag), '统计用户昨日收益数据', @yesterday_tag);
#     CALL Statistics_AccountIncomeDay(@yesterday_tag, @error);
#     IF @error
#     THEN
#       SET @affected = TimerTask_update(@task_id, 1);
#     ELSE
#       SET @affected = TimerTask_update(@task_id, 2);
#     END IF;
#
#     # 统计用户上月收益数据
#     IF NOT @error AND IF(FROM_UNIXTIME(@now, '%e') = 1, 1, 0)
#     THEN
#       SET @task_id = TimerTask_add('Statistics_AccountIncome', CONCAT(@lastmoth_tag), '统计用户上月收益数据', @lastmoth_tag);
#       CALL Statistics_AccountIncome(@lastmoth_tag, @error);
#       IF @error
#       THEN
#         SET @affected = TimerTask_update(@task_id, 1);
#       ELSE
#         SET @affected = TimerTask_update(@task_id, 2);
#       END IF;
#     END IF;
#
#     #
#     IF NOT @error AND IF(FROM_UNIXTIME(@now, '%c%e') = 11, 1, 0)
#     THEN
#       # 统计用户去年收益数据 （数据结止日期为昨天）
#       SET @task_id = TimerTask_add('Statistics_AccountIncome', CONCAT(@lastyear_tag), '统计用户去年收益数据', @lastyear_tag);
#       CALL Statistics_AccountIncome(@lastyear_tag, @error);
#       IF @error
#       THEN
#         SET @affected = TimerTask_update(@task_id, 1);
#       ELSE
#         SET @affected = TimerTask_update(@task_id, 2);
#       END IF;
#     END IF;
#
#     # 统计用户当月收益数据 （数据结止日期为昨天）
#     IF NOT @error
#     THEN
#       SET @task_id = TimerTask_add('Statistics_AccountIncome', CONCAT(@moth_tag), '统计用户当月收益数据 （数据结止日期为昨天）', @moth_tag);
#       CALL Statistics_AccountIncome(@moth_tag, @error);
#       IF @error
#       THEN
#         SET @affected = TimerTask_update(@task_id, 1);
#       ELSE
#         SET @affected = TimerTask_update(@task_id, 2);
#       END IF;
#     END IF;
#
#     # 统计用户当年收益数据 （数据结止日期为昨天）
#     IF NOT @error
#     THEN
#       SET @task_id = TimerTask_add('Statistics_AccountIncome', CONCAT(@year_tag), '统计用户当年收益数据 （数据结止日期为昨天）', @year_tag);
#       CALL Statistics_AccountIncome(@year_tag, @error);
#       IF @error
#       THEN
#         SET @affected = TimerTask_update(@task_id, 1);
#       ELSE
#         SET @affected = TimerTask_update(@task_id, 2);
#       END IF;
#     END IF;
#
#     # 统计用户总收益数据 （数据结止日期为昨天）
#     IF NOT @error
#     THEN
#       SET @task_id = TimerTask_add('Statistics_AccountIncome', CONCAT(0), '统计用户总收益数据 （数据结止日期为昨天）', 0);
#       CALL Statistics_AccountIncome(0, @error);
#       IF @error
#       THEN
#         SET @affected = TimerTask_update(@task_id, 1);
#       ELSE
#         SET @affected = TimerTask_update(@task_id, 2);
#       END IF;
#     END IF;
#
#     SET error = @error;
  END
;;
DELIMITER ;




-- ----------------------------
-- 统计用户日收益数据
-- ----------------------------
DROP PROCEDURE IF EXISTS `Statistics_AccountIncomeDay`;
DELIMITER ;;
CREATE PROCEDURE `Statistics_AccountIncomeDay`(IN tag int(11), OUT error int(11))
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;


    out_label: BEGIN

#       # 商家收益
#       CALL Statistics_AccountIncomeDay_action(tag, 'cash', '306', 'income_cash_merchant', error);
#       IF error
#       THEN
#         LEAVE out_label;
#       END IF;
#
#       # VIP销售奖（直推奖）
#       CALL Statistics_AccountIncomeDay_action(tag, 'cash', '309', 'income_cash_recommend', error);
#       IF error
#       THEN
#         LEAVE out_label;
#       END IF;
#
#       # 店长消费奖
#       CALL Statistics_AccountIncomeDay_action(tag, 'cash', '310', 'income_cash_consume', error);
#       IF error
#       THEN
#         LEAVE out_label;
#       END IF;
#
#       # 合伙人补贴奖
#       CALL Statistics_AccountIncomeDay_action(tag, 'cash', '315', 'income_cash_partner_subsidy', error);
#       IF error
#       THEN
#         LEAVE out_label;
#       END IF;
#
#       # 服务网点补贴奖
#       CALL Statistics_AccountIncomeDay_action(tag, 'cash', '311', 'income_cash_service_subsidy', error);
#       IF error
#       THEN
#         LEAVE out_label;
#       END IF;
#
#       # 营运中心补贴奖
#       CALL Statistics_AccountIncomeDay_action(tag, 'cash', '312', 'income_cash_company_subsidy', error);
#       IF error
#       THEN
#         LEAVE out_label;
#       END IF;
#
#       # 业绩结算
#       CALL Statistics_AccountIncomeDay_action(tag, 'cash', '313', 'income_cash_performance', error);
#       IF error
#       THEN
#         LEAVE out_label;
#       END IF;
#
#       # 星级店长业务补贴
#       CALL Statistics_AccountIncomeDay_action(tag, 'cash', '314', 'income_cash_bonus', error);
#       IF error
#       THEN
#         LEAVE out_label;
#       END IF;
#
#       # 广告收益
#       CALL Statistics_AccountIncomeDay_action(tag, 'cash', '316', 'income_cash_adview', error);
#       IF error
#       THEN
#         LEAVE out_label;
#       END IF;
#
#       # 店长直推奖
#       CALL Statistics_AccountIncomeDay_action(tag, 'cash', '317', 'income_cash_recommend_service', error);
#       IF error
#       THEN
#         LEAVE out_label;
#       END IF;
#
#       # 注册赠送
#       CALL Statistics_AccountIncomeDay_action(tag, 'goldcoin', '100', 'income_goldcoin_register_give', error);
#       IF error
#       THEN
#         LEAVE out_label;
#       END IF;
#
#       # 消费赠送
#       CALL Statistics_AccountIncomeDay_action(tag, 'goldcoin', '103', 'income_goldcoin_consume_give', error);
#       IF error
#       THEN
#         LEAVE out_label;
#       END IF;
#
#       # 签到赠送
#       CALL Statistics_AccountIncomeDay_action(tag, 'goldcoin', '104', 'income_goldcoin_checkin', error);
#       IF error
#       THEN
#         LEAVE out_label;
#       END IF;
#
#       # 广告收益
#       CALL Statistics_AccountIncomeDay_action(tag, 'goldcoin', '105', 'income_goldcoin_adview', error);
#       IF error
#       THEN
#         LEAVE out_label;
#       END IF;
#
#       CALL Statistics_AccountIncomeDay_action(tag, 'goldcoin', '106', 'income_goldcoin_service_give', error);
#       IF error
#       THEN
#         LEAVE out_label;
#       END IF;
#
#       # 注册赠送
#       CALL Statistics_AccountIncomeDay_action(tag, 'points', '400', 'income_points_register_give', error);
#       IF error
#       THEN
#         LEAVE out_label;
#       END IF;
#
#       # 消费赠送
#       CALL Statistics_AccountIncomeDay_action(tag, 'points', '403', 'income_points_consume_give', error);
#       IF error
#       THEN
#         LEAVE out_label;
#       END IF;
#
#       # 合计统计
#       UPDATE `zc_account_income`
#       SET
#         `income_cash_total`     = (
#           `income_cash_merchant` +
#           `income_cash_recommend` +
#           `income_cash_consume` +
#           `income_cash_partner_subsidy` +
#           `income_cash_service_subsidy` +
#           `income_cash_company_subsidy` +
#           `income_cash_performance` +
#           `income_cash_bonus` +
#           `income_cash_adview` +
#           `income_cash_recommend_service`
#         ),
#         `income_goldcoin_total` = (
#           `income_goldcoin_register_give` +
#           `income_goldcoin_consume_give` +
#           `income_goldcoin_checkin` +
#           `income_goldcoin_adview` +
#           `income_goldcoin_service_give`
#         ),
#         `income_points_total`   = (
#           `income_points_register_give` +
#           `income_points_consume_give`
#         ),
#         income_total            = (
#           `income_cash_merchant` +
#           `income_cash_recommend` +
#           `income_cash_consume` +
#           `income_cash_partner_subsidy` +
#           `income_cash_service_subsidy` +
#           `income_cash_company_subsidy` +
#           `income_cash_performance` +
#           `income_cash_bonus` +
#           `income_cash_adview` +
#           `income_cash_recommend_service` +
#           `income_goldcoin_register_give` +
#           `income_goldcoin_consume_give` +
#           `income_goldcoin_checkin` +
#           `income_goldcoin_adview` +
#           `income_goldcoin_service_give` +
#           `income_points_register_give` +
#           `income_points_consume_give` +
#           `income_goldcoin_adview`
#         )
#       WHERE `income_tag` = tag;
#
#       # 总统计
#       CALL Statistics_AccountIncome_total(tag, error);
#       IF error
#       THEN
#         LEAVE out_label;
#       END IF;

    END out_label;
  END
;;
DELIMITER ;


-- ----------------------------
-- 统计用户日单项收益数据
-- ----------------------------
DROP PROCEDURE IF EXISTS `Statistics_AccountIncomeDay_action`;
DELIMITER ;;
CREATE PROCEDURE `Statistics_AccountIncomeDay_action`(
  IN  tag      int(11),
  IN  currency varchar(50),
  IN  action   varchar(50),
  IN  field    varchar(50),
  OUT error    int(11)
)
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      SET @start_time = UNIX_TIMESTAMP(tag);
      SET @end_time = UNIX_TIMESTAMP(date_add(tag, INTERVAL 1 DAY));
      SET @temp_table = CONCAT('temp_account_income_', tag, '_', field);
      SET @target_table = 'zc_account_income';
      SET @source_table = CONCAT('zc_account_', currency, '_', substring(tag, 1, 6));

      SET @v_sql = CONCAT('DROP TABLE IF EXISTS ', @temp_table);
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @v_sql = CONCAT('CREATE TABLE ', @temp_table, ' AS SELECT
      user_id,
      IFNULL(SUM(record_amount), 0) AS ', field, '
      FROM ', @source_table, '
      WHERE record_action IN (', action, ')
      AND record_addtime >= ', @start_time, '
      AND record_addtime < ', @end_time, '
      GROUP BY user_id');
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @v_sql = CONCAT('UPDATE ', @target_table, ' as ta, ', @temp_table, ' as te SET
      ta.', field, ' = te.', field, ',
      ta.`income_uptime` = ', UNIX_TIMESTAMP(), '
      WHERE ta.user_id = te.user_id AND ta.income_tag = ', tag);
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @v_sql = CONCAT('INSERT IGNORE INTO ', @target_table, '(
      user_id,
      ', field, ',
      income_tag,
      income_uptime)
      SELECT
      user_id,
      ', field, ',
      ', tag, ',
      ', UNIX_TIMESTAMP(), '
      FROM ', @temp_table, ';');
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @v_sql = CONCAT('DROP TABLE IF EXISTS ', @temp_table);
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END out_label;
  END
;;
DELIMITER ;


-- ----------------------------
-- 统计用户日单项收益数据
-- ----------------------------
DROP PROCEDURE IF EXISTS `Statistics_AccountIncomeDay_action`;
DELIMITER ;;
CREATE PROCEDURE `Statistics_AccountIncomeDay_action`(
  IN  tag      int(11),
  IN  currency varchar(50),
  IN  action   varchar(50),
  IN  field    varchar(50),
  OUT error    int(11)
)
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      SET @start_time = UNIX_TIMESTAMP(tag);
      SET @end_time = UNIX_TIMESTAMP(date_add(tag, INTERVAL 1 DAY));
      SET @temp_table = CONCAT('temp_account_income_', tag, '_', field);
      SET @target_table = 'zc_account_income';
      SET @source_table = CONCAT('zc_account_', currency, '_', substring(tag, 1, 6));

      SET @v_sql = CONCAT('DROP TABLE IF EXISTS ', @temp_table);
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @v_sql = CONCAT('CREATE TABLE ', @temp_table, ' AS SELECT
      user_id,
      IFNULL(SUM(record_amount), 0) AS ', field, '
      FROM ', @source_table, '
      WHERE record_action IN (', action, ')
      AND record_addtime >= ', @start_time, '
      AND record_addtime < ', @end_time, '
      GROUP BY user_id');
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @v_sql = CONCAT('UPDATE ', @target_table, ' as ta, ', @temp_table, ' as te SET
      ta.', field, ' = te.', field, ',
      ta.`income_uptime` = ', UNIX_TIMESTAMP(), '
      WHERE ta.user_id = te.user_id AND ta.income_tag = ', tag);
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @v_sql = CONCAT('INSERT IGNORE INTO ', @target_table, '(
      user_id,
      ', field, ',
      income_tag,
      income_uptime)
      SELECT
      user_id,
      ', field, ',
      ', tag, ',
      ', UNIX_TIMESTAMP(), '
      FROM ', @temp_table, ';');
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @v_sql = CONCAT('DROP TABLE IF EXISTS ', @temp_table);
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END out_label;
  END
;;
DELIMITER ;


-- ----------------------------
-- 统计用总收益数据
-- ----------------------------
DROP PROCEDURE IF EXISTS `Statistics_AccountIncome_total`;
DELIMITER ;;
CREATE PROCEDURE `Statistics_AccountIncome_total`(
  IN  tag   int(11),
  OUT error int(11)
)
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN
      SET @temp_table = CONCAT('temp_account_income_', tag);
      SET @target_table = 'zc_account_income';
      SET @v_sql = CONCAT('DROP TABLE IF EXISTS ', @temp_table);
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
      SET @v_sql = CONCAT('CREATE TABLE ', @temp_table, ' AS SELECT
    SUM(`income_cash_merchant`) AS `income_cash_merchant`,
    SUM(`income_cash_recommend`) AS `income_cash_recommend`,
    SUM(`income_cash_consume`) AS `income_cash_consume`,
    SUM(`income_cash_partner_subsidy`) AS `income_cash_partner_subsidy`,
    SUM(`income_cash_service_subsidy`) AS `income_cash_service_subsidy`,
    SUM(`income_cash_company_subsidy`) AS `income_cash_company_subsidy`,
    SUM(`income_cash_performance`) AS `income_cash_performance`,
    SUM(`income_cash_bonus`) AS `income_cash_bonus`,
    SUM(`income_cash_adview`) AS `income_cash_adview`,
    SUM(`income_cash_recommend_service`) AS `income_cash_recommend_service`,
    SUM(`income_cash_total`) AS `income_cash_total`,

    SUM(`income_goldcoin_register_give`) AS `income_goldcoin_register_give`,
    SUM(`income_goldcoin_consume_give`) AS `income_goldcoin_consume_give`,
    SUM(`income_goldcoin_checkin`) AS `income_goldcoin_checkin`,
    SUM(`income_goldcoin_adview`) AS `income_goldcoin_adview`,
    SUM(`income_goldcoin_service_give`) AS `income_goldcoin_service_give`,
    SUM(`income_goldcoin_total`) AS `income_goldcoin_total`,

    SUM(`income_points_register_give`) AS `income_points_register_give`,
    SUM(`income_points_consume_give`) AS `income_points_consume_give`,
    SUM(`income_points_total`) AS `income_points_total`,

    SUM(`income_total`) AS `income_total`
    FROM ', @target_table, '
    WHERE user_id > 0 AND income_tag = ', tag, '
    ');
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @v_sql = CONCAT('UPDATE ', @target_table, ' as ta, ', @temp_table, ' as te SET
    ta.`income_cash_merchant` = te.`income_cash_merchant`,
    ta.`income_cash_recommend` = te.`income_cash_recommend`,
    ta.`income_cash_consume` = te.`income_cash_consume`,
    ta.`income_cash_partner_subsidy` = te.`income_cash_partner_subsidy`,
    ta.`income_cash_service_subsidy` = te.`income_cash_service_subsidy`,
    ta.`income_cash_company_subsidy` = te.`income_cash_company_subsidy`,
    ta.`income_cash_performance` = te.`income_cash_performance`,
    ta.`income_cash_bonus` = te.`income_cash_bonus`,
    ta.`income_cash_adview` = te.`income_cash_adview`,
    ta.`income_cash_recommend_service` = te.`income_cash_recommend_service`,
    ta.`income_cash_total` = te.`income_cash_total`,

    ta.`income_goldcoin_register_give` = te.`income_goldcoin_register_give`,
    ta.`income_goldcoin_consume_give` = te.`income_goldcoin_consume_give`,
    ta.`income_goldcoin_checkin` = te.`income_goldcoin_checkin`,
    ta.`income_goldcoin_adview` = te.`income_goldcoin_adview`,
    ta.`income_goldcoin_service_give` = te.`income_goldcoin_service_give`,
    ta.`income_goldcoin_total` = te.`income_goldcoin_total`,

    ta.`income_points_register_give` = te.`income_points_register_give`,
    ta.`income_points_consume_give` = te.`income_points_consume_give`,
    ta.`income_points_total` = te.`income_points_total`,

    ta.`income_total` = te.`income_total`,
    ta.`income_uptime` = ', UNIX_TIMESTAMP(), '
    WHERE ta.user_id = 0 AND ta.income_tag = ', tag);
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
      SET @v_sql = CONCAT('INSERT IGNORE INTO ', @target_table, '(
      user_id,
      income_cash_merchant,
      income_cash_recommend,
      income_cash_consume,
      income_cash_partner_subsidy,
      income_cash_service_subsidy,
      income_cash_company_subsidy,
      income_cash_performance,
      income_cash_bonus,
      income_cash_adview,
      income_cash_recommend_service,
      income_cash_total,

      income_goldcoin_register_give,
      income_goldcoin_consume_give,
      income_goldcoin_checkin,
      income_goldcoin_adview,
      income_goldcoin_service_give,
      income_goldcoin_total,

      income_points_register_give,
      income_points_consume_give,
      income_points_total,

      income_total,
      income_tag,
      income_uptime)
      SELECT
      0,
      income_cash_merchant,
      income_cash_recommend,
      income_cash_consume,
      income_cash_partner_subsidy,
      income_cash_service_subsidy,
      income_cash_company_subsidy,
      income_cash_performance,
      income_cash_bonus,
      income_cash_adview,
      income_cash_recommend_service,
      income_cash_total,

      income_goldcoin_register_give,
      income_goldcoin_consume_give,
      income_goldcoin_checkin,
      income_goldcoin_adview,
      income_goldcoin_service_give,
      income_goldcoin_total,

      income_points_register_give,
      income_points_consume_give,
      income_points_total,

      income_total,
      ', tag, ',
      ', UNIX_TIMESTAMP(), '
      FROM ', @temp_table, ';');
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
      SET @v_sql = CONCAT('DROP TABLE IF EXISTS ', @temp_table);
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END out_label;
  END
;;
DELIMITER ;

-- ----------------------------
-- 统计用户月年总收益数据
-- ----------------------------
DROP PROCEDURE IF EXISTS `Statistics_AccountIncome`;
DELIMITER ;;
CREATE PROCEDURE `Statistics_AccountIncome`(
  IN  tag   int(11),
  OUT error int(11)
)
  BEGIN
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN
      SET @temp_table = CONCAT('temp_account_income_', tag);
      SET @source_table = 'zc_account_income';
      IF tag = 0
      THEN
        SET @start_tag = 2016;
        SET @end_tag = 2020;
      ELSE
        SET @start_tag = tag * 100;
        SET @end_tag = (tag + 1) * 100;
      END IF;
      SET @v_sql = CONCAT('DROP TABLE IF EXISTS ', @temp_table);
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
      SET @v_sql = CONCAT('CREATE TABLE ', @temp_table, ' AS SELECT
      NULL AS income_id,
      `user_id` AS `user_id`,
      SUM(`income_cash_merchant`) AS income_cash_merchant,
      SUM(`income_cash_recommend`) AS income_cash_recommend,
      SUM(`income_cash_consume`) AS income_cash_consume,
      SUM(`income_cash_partner_subsidy`) AS income_cash_partner_subsidy,
      SUM(`income_cash_service_subsidy`) AS income_cash_service_subsidy,
      SUM(`income_cash_company_subsidy`) AS income_cash_company_subsidy,
      SUM(`income_cash_performance`) AS income_cash_performance,
      SUM(`income_cash_bonus`) AS income_cash_bonus,
      SUM(`income_cash_adview`) AS income_cash_adview,
      SUM(`income_cash_recommend_service`) AS income_cash_recommend_service,
      SUM(`income_cash_total`) as income_cash_total,

      SUM(`income_goldcoin_register_give`) AS income_goldcoin_register_give,
      SUM(`income_goldcoin_consume_give`) AS income_goldcoin_consume_give,
      SUM(`income_goldcoin_checkin`) AS income_goldcoin_checkin,
      SUM(`income_goldcoin_adview`) AS income_goldcoin_adview,
      SUM(`income_goldcoin_service_give`) AS income_goldcoin_service_give,
      SUM(`income_goldcoin_total`) as income_goldcoin_total,

      SUM(`income_points_register_give`) AS income_points_register_give,
      SUM(`income_points_consume_give`) AS income_points_consume_give,
      SUM(`income_points_total`) as income_points_total,

      SUM(`income_total`) as income_total,
      ', tag, ' AS income_tag,
      ', UNIX_TIMESTAMP(), ' AS income_uptime
      FROM ', @source_table, '
      WHERE income_tag >= ', @start_tag, ' AND income_tag < ', @end_tag, '
      GROUP BY `user_id` ORDER BY `user_id` ASC;');
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @v_sql = CONCAT('UPDATE ', @source_table, ' AS so, ', @temp_table, ' AS te SET
      so.income_cash_merchant = te.income_cash_merchant,
      so.income_cash_recommend = te.income_cash_recommend,
      so.income_cash_consume = te.income_cash_consume,
      so.income_cash_partner_subsidy = te.income_cash_partner_subsidy,
      so.income_cash_service_subsidy = te.income_cash_service_subsidy,
      so.income_cash_company_subsidy = te.income_cash_company_subsidy,
      so.income_cash_performance = te.income_cash_performance,
      so.income_cash_bonus = te.income_cash_bonus,
      so.income_cash_adview = te.income_cash_adview,
      so.income_cash_recommend_service = te.income_cash_recommend_service,
      so.income_cash_total = te.income_cash_total,

      so.income_goldcoin_register_give = te.income_goldcoin_register_give,
      so.income_goldcoin_consume_give = te.income_goldcoin_consume_give,
      so.income_goldcoin_checkin = te.income_goldcoin_checkin,
      so.income_goldcoin_adview = te.income_goldcoin_adview,
      so.income_goldcoin_service_give = te.income_goldcoin_service_give,
      so.income_goldcoin_total = te.income_goldcoin_total,

      so.income_points_register_give = te.income_points_register_give,
      so.income_points_consume_give = te.income_points_consume_give,
      so.income_points_total = te.income_points_total,

      so.income_total = te.income_total,
      so.income_uptime = ', UNIX_TIMESTAMP(), '
      WHERE so.user_id = te.user_id AND so.income_tag = ', tag, ';');
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @v_sql = CONCAT('INSERT IGNORE INTO `', @source_table, '`(
      `income_id`,
      `user_id`,
      income_cash_merchant,
      income_cash_recommend,
      income_cash_consume,
      income_cash_partner_subsidy,
      income_cash_service_subsidy,
      income_cash_company_subsidy,
      income_cash_performance,
      income_cash_bonus,
      income_cash_adview,
      income_cash_recommend_service,
      income_cash_total,

      income_goldcoin_register_give,
      income_goldcoin_consume_give,
      income_goldcoin_checkin,
      income_goldcoin_adview,
      income_goldcoin_service_give,
      income_goldcoin_total,

      income_points_register_give,
      income_points_consume_give,
      income_points_total,

      `income_total`,
      `income_tag`,
      `income_uptime`
      ) SELECT * FROM ', @temp_table, ';');
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
      SET @v_sql = CONCAT('DROP TABLE IF EXISTS ', @temp_table);
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      # 总统计
      CALL Statistics_AccountIncome_total(tag, error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

    END out_label;
  END
;;
DELIMITER ;




-- ------------------- 存储过程.-.1213.用户收益统计.sql end ------------------------ 

