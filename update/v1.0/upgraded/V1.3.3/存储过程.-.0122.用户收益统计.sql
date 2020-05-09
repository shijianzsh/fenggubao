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

    #统计用户昨日收益数据
    SET @task_id = TimerTask_add('Statistics_AccountIncomeDay', CONCAT(@yesterday_tag), '统计用户昨日收益数据', @yesterday_tag);
    CALL Statistics_AccountIncomeDay(@yesterday_tag, @error);
    IF @error
    THEN
      SET @affected = TimerTask_update(@task_id, 1);
    ELSE
      SET @affected = TimerTask_update(@task_id, 2);
    END IF;

    # 统计用户上月收益数据
    IF NOT @error AND IF(FROM_UNIXTIME(@now, '%e') = 1, 1, 0)
    THEN
      SET @task_id = TimerTask_add('Statistics_AccountIncome', CONCAT(@lastmoth_tag), '统计用户上月收益数据', @lastmoth_tag);
      CALL Statistics_AccountIncome(@lastmoth_tag, @error);
      IF @error
      THEN
        SET @affected = TimerTask_update(@task_id, 1);
      ELSE
        SET @affected = TimerTask_update(@task_id, 2);
      END IF;
    END IF;

    #
    IF NOT @error AND IF(FROM_UNIXTIME(@now, '%c%e') = 11, 1, 0)
    THEN
      # 统计用户去年收益数据 （数据结止日期为昨天）
      SET @task_id = TimerTask_add('Statistics_AccountIncome', CONCAT(@lastyear_tag), '统计用户去年收益数据', @lastyear_tag);
      CALL Statistics_AccountIncome(@lastyear_tag, @error);
      IF @error
      THEN
        SET @affected = TimerTask_update(@task_id, 1);
      ELSE
        SET @affected = TimerTask_update(@task_id, 2);
      END IF;
    END IF;

    # 统计用户当月收益数据 （数据结止日期为昨天）
    IF NOT @error
    THEN
      SET @task_id = TimerTask_add('Statistics_AccountIncome', CONCAT(@moth_tag), '统计用户当月收益数据 （数据结止日期为昨天）', @moth_tag);
      CALL Statistics_AccountIncome(@moth_tag, @error);
      IF @error
      THEN
        SET @affected = TimerTask_update(@task_id, 1);
      ELSE
        SET @affected = TimerTask_update(@task_id, 2);
      END IF;
    END IF;

    # 统计用户当年收益数据 （数据结止日期为昨天）
    IF NOT @error
    THEN
      SET @task_id = TimerTask_add('Statistics_AccountIncome', CONCAT(@year_tag), '统计用户当年收益数据 （数据结止日期为昨天）', @year_tag);
      CALL Statistics_AccountIncome(@year_tag, @error);
      IF @error
      THEN
        SET @affected = TimerTask_update(@task_id, 1);
      ELSE
        SET @affected = TimerTask_update(@task_id, 2);
      END IF;
    END IF;

    # 统计用户总收益数据 （数据结止日期为昨天）
    IF NOT @error
    THEN
      SET @task_id = TimerTask_add('Statistics_AccountIncome', CONCAT(0), '统计用户总收益数据 （数据结止日期为昨天）', 0);
      CALL Statistics_AccountIncome(0, @error);
      IF @error
      THEN
        SET @affected = TimerTask_update(@task_id, 1);
      ELSE
        SET @affected = TimerTask_update(@task_id, 2);
      END IF;
    END IF;

    SET error = @error;
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

      # 销售奖（流）
      CALL Statistics_AccountIncomeDay_action(tag, 'goldcoin', '104', 'income_goldcoin_consume', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 销售奖（锁）
      CALL Statistics_AccountIncomeDay_action(tag, 'bonus', '204', 'income_bonus_consume', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 关爱奖（流）
      CALL Statistics_AccountIncomeDay_action(tag, 'goldcoin', '108', 'income_goldcoin_care', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 关爱奖（锁）
      CALL Statistics_AccountIncomeDay_action(tag, 'bonus', '208', 'income_bonus_care', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 管理津贴（流）
      CALL Statistics_AccountIncomeDay_action(tag, 'goldcoin', '105', 'income_goldcoin_subsidy', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 管理津贴（锁）
      CALL Statistics_AccountIncomeDay_action(tag, 'bonus', '205', 'income_bonus_subsidy', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 加权分红（流）
      CALL Statistics_AccountIncomeDay_action(tag, 'goldcoin', '107', 'income_goldcoin_bonus', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 加权分红（锁）
      CALL Statistics_AccountIncomeDay_action(tag, 'bonus', '207', 'income_bonus_bonus', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 区域合伙人奖（流）
      CALL Statistics_AccountIncomeDay_action(tag, 'goldcoin', '110', 'income_goldcoin_county', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 区域合伙人奖（锁）
      CALL Statistics_AccountIncomeDay_action(tag, 'bonus', '210', 'income_bonus_county', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 省级合伙人奖（流）
      CALL Statistics_AccountIncomeDay_action(tag, 'goldcoin', '111', 'income_goldcoin_province', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 省级合伙人奖（锁）
      CALL Statistics_AccountIncomeDay_action(tag, 'bonus', '211', 'income_bonus_province', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 省级合伙人见点奖（流）
      CALL Statistics_AccountIncomeDay_action(tag, 'goldcoin', '112', 'income_goldcoin_province_see', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 省级合伙人见点奖（锁）
      CALL Statistics_AccountIncomeDay_action(tag, 'bonus', '212', 'income_bonus_province_see', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 挖矿（流）
      CALL Statistics_AccountIncomeDay_action(tag, 'goldcoin', '115', 'income_goldcoin_mining', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 挖矿（锁）
      CALL Statistics_AccountIncomeDay_action(tag, 'bonus', '215', 'income_bonus_mining', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 消费赠送（流）
      CALL Statistics_AccountIncomeDay_action(tag, 'goldcoin', '103', 'income_goldcoin_give', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 消费赠送（锁）
      CALL Statistics_AccountIncomeDay_action(tag, 'bonus', '203', 'income_goldcoin_give', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 合计统计
      UPDATE `zc_account_income`
      SET
        `income_goldcoin_total`     = (
          `income_goldcoin_consume` +
          `income_goldcoin_care` +
          `income_goldcoin_subsidy` +
          `income_goldcoin_bonus` +
          `income_goldcoin_county` +
          `income_goldcoin_province` +
          `income_goldcoin_province_see` +
          `income_goldcoin_mining` +
          `income_goldcoin_give`
        ),
        `income_bonus_total` = (
          `income_bonus_consume` +
          `income_bonus_care` +
          `income_bonus_subsidy` +
          `income_bonus_bonus` +
          `income_bonus_county` +
          `income_bonus_province` +
          `income_bonus_province_see` +
          `income_bonus_mining` +
          `income_bonus_give`
        ),
        income_total            = (
          `income_goldcoin_consume` +
          `income_goldcoin_care` +
          `income_goldcoin_subsidy` +
          `income_goldcoin_bonus` +
          `income_goldcoin_county` +
          `income_goldcoin_province` +
          `income_goldcoin_province_see` +
          `income_goldcoin_mining` +
          `income_goldcoin_give` +
          `income_bonus_consume` +
          `income_bonus_care` +
          `income_bonus_subsidy` +
          `income_bonus_bonus` +
          `income_bonus_county` +
          `income_bonus_province` +
          `income_bonus_province_see` +
          `income_bonus_mining` +
          `income_bonus_give`
        )
      WHERE `income_tag` = tag;

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
    SUM(`income_goldcoin_consume`) AS `income_goldcoin_consume`,
    SUM(`income_goldcoin_care`) AS `income_goldcoin_care`,
    SUM(`income_goldcoin_subsidy`) AS `income_goldcoin_subsidy`,
    SUM(`income_goldcoin_bonus`) AS `income_goldcoin_bonus`,
    SUM(`income_goldcoin_county`) AS `income_goldcoin_county`,
    SUM(`income_goldcoin_province`) AS `income_goldcoin_province`,
    SUM(`income_goldcoin_province_see`) AS `income_goldcoin_province_see`,
    SUM(`income_goldcoin_mining`) AS `income_goldcoin_mining`,
    SUM(`income_goldcoin_give`) AS `income_goldcoin_give`,
    SUM(`income_goldcoin_total`) AS `income_goldcoin_total`,

    SUM(`income_bonus_consume`) AS `income_bonus_consume`,
    SUM(`income_bonus_care`) AS `income_bonus_care`,
    SUM(`income_bonus_subsidy`) AS `income_bonus_subsidy`,
    SUM(`income_bonus_bonus`) AS `income_bonus_bonus`,
    SUM(`income_bonus_county`) AS `income_bonus_county`,
    SUM(`income_bonus_province`) AS `income_bonus_province`,
    SUM(`income_bonus_province_see`) AS `income_bonus_province_see`,
    SUM(`income_bonus_mining`) AS `income_bonus_mining`,
    SUM(`income_bonus_give`) AS `income_bonus_give`,
    SUM(`income_bonus_total`) AS `income_bonus_total`,

    SUM(`income_total`) AS `income_total`
    FROM ', @target_table, '
    WHERE user_id > 0 AND income_tag = ', tag, '
    ');
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @v_sql = CONCAT('UPDATE ', @target_table, ' as ta, ', @temp_table, ' as te SET
    ta.`income_goldcoin_consume` = te.`income_goldcoin_consume`,
    ta.`income_goldcoin_care` = te.`income_goldcoin_care`,
    ta.`income_goldcoin_subsidy` = te.`income_goldcoin_subsidy`,
    ta.`income_goldcoin_bonus` = te.`income_goldcoin_bonus`,
    ta.`income_goldcoin_county` = te.`income_goldcoin_county`,
    ta.`income_goldcoin_province` = te.`income_goldcoin_province`,
    ta.`income_goldcoin_province_see` = te.`income_goldcoin_province_see`,
    ta.`income_goldcoin_mining` = te.`income_goldcoin_mining`,
    ta.`income_goldcoin_give` = te.`income_goldcoin_give`,
    ta.`income_goldcoin_total` = te.`income_goldcoin_total`,

    ta.`income_bonus_consume` = te.`income_bonus_consume`,
    ta.`income_bonus_care` = te.`income_bonus_care`,
    ta.`income_bonus_subsidy` = te.`income_bonus_subsidy`,
    ta.`income_bonus_bonus` = te.`income_bonus_bonus`,
    ta.`income_bonus_county` = te.`income_bonus_county`,
    ta.`income_bonus_province` = te.`income_bonus_province`,
    ta.`income_bonus_province_see` = te.`income_bonus_province_see`,
    ta.`income_bonus_mining` = te.`income_bonus_mining`,
    ta.`income_bonus_give` = te.`income_bonus_give`,
    ta.`income_bonus_total` = te.`income_bonus_total`,

    ta.`income_total` = te.`income_total`,
    ta.`income_uptime` = ', UNIX_TIMESTAMP(), '
    WHERE ta.user_id = 0 AND ta.income_tag = ', tag);
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
      SET @v_sql = CONCAT('INSERT IGNORE INTO ', @target_table, '(
      user_id,
      `income_goldcoin_consume`,
      `income_goldcoin_care`,
      `income_goldcoin_subsidy`,
      `income_goldcoin_bonus`,
      `income_goldcoin_county`,
      `income_goldcoin_province`,
      `income_goldcoin_province_see`,
      `income_goldcoin_mining`,
      `income_goldcoin_give`,
      `income_goldcoin_total`,
      `income_bonus_consume`,
      `income_bonus_care`,
      `income_bonus_subsidy`,
      `income_bonus_bonus`,
      `income_bonus_county`,
      `income_bonus_province`,
      `income_bonus_province_see`,
      `income_bonus_mining`,
      `income_bonus_give`,
      `income_bonus_total`,
      income_total,
      income_tag,
      income_uptime)
      SELECT
      0,
      `income_goldcoin_consume`,
      `income_goldcoin_care`,
      `income_goldcoin_subsidy`,
      `income_goldcoin_bonus`,
      `income_goldcoin_county`,
      `income_goldcoin_province`,
      `income_goldcoin_province_see`,
      `income_goldcoin_mining`,
      `income_goldcoin_give`,
      `income_goldcoin_total`,
      `income_bonus_consume`,
      `income_bonus_care`,
      `income_bonus_subsidy`,
      `income_bonus_bonus`,
      `income_bonus_county`,
      `income_bonus_province`,
      `income_bonus_province_see`,
      `income_bonus_mining`,
      `income_bonus_give`,
      `income_bonus_total`,
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
      SUM(`income_goldcoin_consume`) AS `income_goldcoin_consume`,
      SUM(`income_goldcoin_care`) AS `income_goldcoin_care`,
      SUM(`income_goldcoin_subsidy`) AS `income_goldcoin_subsidy`,
      SUM(`income_goldcoin_bonus`) AS `income_goldcoin_bonus`,
      SUM(`income_goldcoin_county`) AS `income_goldcoin_county`,
      SUM(`income_goldcoin_province`) AS `income_goldcoin_province`,
      SUM(`income_goldcoin_province_see`) AS `income_goldcoin_province_see`,
      SUM(`income_goldcoin_mining`) AS `income_goldcoin_mining`,
      SUM(`income_goldcoin_give`) AS `income_goldcoin_give`,
      SUM(`income_goldcoin_total`) AS `income_goldcoin_total`,
      SUM(`income_bonus_consume`) AS `income_bonus_consume`,
      SUM(`income_bonus_care`) AS `income_bonus_care`,
      SUM(`income_bonus_subsidy`) AS `income_bonus_subsidy`,
      SUM(`income_bonus_bonus`) AS `income_bonus_bonus`,
      SUM(`income_bonus_county`) AS `income_bonus_county`,
      SUM(`income_bonus_province`) AS `income_bonus_province`,
      SUM(`income_bonus_province_see`) AS `income_bonus_province_see`,
      SUM(`income_bonus_mining`) AS `income_bonus_mining`,
      SUM(`income_bonus_give`) AS `income_bonus_give`,
      SUM(`income_bonus_total`) AS `income_bonus_total`,
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
      so.`income_goldcoin_consume` = te.`income_goldcoin_consume`,
      so.`income_goldcoin_care` = te.`income_goldcoin_care`,
      so.`income_goldcoin_subsidy` = te.`income_goldcoin_subsidy`,
      so.`income_goldcoin_bonus` = te.`income_goldcoin_bonus`,
      so.`income_goldcoin_county` = te.`income_goldcoin_county`,
      so.`income_goldcoin_province` = te.`income_goldcoin_province`,
      so.`income_goldcoin_province_see` = te.`income_goldcoin_province_see`,
      so.`income_goldcoin_mining` = te.`income_goldcoin_mining`,
      so.`income_goldcoin_give` = te.`income_goldcoin_give`,
      so.`income_goldcoin_total` = te.`income_goldcoin_total`,

      so.`income_bonus_consume` = te.`income_bonus_consume`,
      so.`income_bonus_care` = te.`income_bonus_care`,
      so.`income_bonus_subsidy` = te.`income_bonus_subsidy`,
      so.`income_bonus_bonus` = te.`income_bonus_bonus`,
      so.`income_bonus_county` = te.`income_bonus_county`,
      so.`income_bonus_province` = te.`income_bonus_province`,
      so.`income_bonus_province_see` = te.`income_bonus_province_see`,
      so.`income_bonus_mining` = te.`income_bonus_mining`,
      so.`income_bonus_give` = te.`income_bonus_give`,
      so.`income_bonus_total` = te.`income_bonus_total`,

      so.income_total = te.income_total,
      so.income_uptime = ', UNIX_TIMESTAMP(), '
      WHERE so.user_id = te.user_id AND so.income_tag = ', tag, ';');
      PREPARE statement FROM @v_sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @v_sql = CONCAT('INSERT IGNORE INTO `', @source_table, '`(
      `income_id`,
      `user_id`,
      `income_goldcoin_consume`,
      `income_goldcoin_care`,
      `income_goldcoin_subsidy`,
      `income_goldcoin_bonus`,
      `income_goldcoin_county`,
      `income_goldcoin_province`,
      `income_goldcoin_province_see`,
      `income_goldcoin_mining`,
      `income_goldcoin_give`,
      `income_goldcoin_total`,
      `income_bonus_consume`,
      `income_bonus_care`,
      `income_bonus_subsidy`,
      `income_bonus_bonus`,
      `income_bonus_county`,
      `income_bonus_province`,
      `income_bonus_province_see`,
      `income_bonus_mining`,
      `income_bonus_give`,
      `income_bonus_total`,
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


