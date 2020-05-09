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

      # 商家收益
      CALL Statistics_AccountIncomeDay_action(tag, 'cash', '306', 'income_cash_merchant', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # VIP销售奖（直推奖）
      CALL Statistics_AccountIncomeDay_action(tag, 'cash', '309', 'income_cash_recommend', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 店长消费奖
      CALL Statistics_AccountIncomeDay_action(tag, 'cash', '310', 'income_cash_consume', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 合伙人补贴奖
      CALL Statistics_AccountIncomeDay_action(tag, 'cash', '315', 'income_cash_partner_subsidy', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 服务网点补贴奖
      CALL Statistics_AccountIncomeDay_action(tag, 'cash', '311', 'income_cash_service_subsidy', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 营运中心补贴奖
      CALL Statistics_AccountIncomeDay_action(tag, 'cash', '312', 'income_cash_company_subsidy', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 业绩结算
      CALL Statistics_AccountIncomeDay_action(tag, 'cash', '313', 'income_cash_performance', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 星级店长业务补贴
      CALL Statistics_AccountIncomeDay_action(tag, 'cash', '314', 'income_cash_bonus', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 广告收益
      CALL Statistics_AccountIncomeDay_action(tag, 'cash', '316', 'income_cash_adview', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 店长直推奖
      CALL Statistics_AccountIncomeDay_action(tag, 'cash', '317', 'income_cash_recommend_service', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 注册赠送
      CALL Statistics_AccountIncomeDay_action(tag, 'goldcoin', '100', 'income_goldcoin_register_give', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 消费赠送
      CALL Statistics_AccountIncomeDay_action(tag, 'goldcoin', '103', 'income_goldcoin_consume_give', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 签到赠送
      CALL Statistics_AccountIncomeDay_action(tag, 'goldcoin', '104', 'income_goldcoin_checkin', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 广告收益
      CALL Statistics_AccountIncomeDay_action(tag, 'goldcoin', '105', 'income_goldcoin_adview', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      CALL Statistics_AccountIncomeDay_action(tag, 'goldcoin', '106', 'income_goldcoin_service_give', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 注册赠送
      CALL Statistics_AccountIncomeDay_action(tag, 'points', '400', 'income_points_register_give', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 消费赠送
      CALL Statistics_AccountIncomeDay_action(tag, 'points', '403', 'income_points_consume_give', error);
      IF error
      THEN
        LEAVE out_label;
      END IF;

      # 合计统计
      UPDATE `zc_account_income`
      SET
        `income_cash_total`     = (
          `income_cash_merchant` +
          `income_cash_recommend` +
          `income_cash_consume` +
          `income_cash_partner_subsidy` +
          `income_cash_service_subsidy` +
          `income_cash_company_subsidy` +
          `income_cash_performance` +
          `income_cash_bonus` +
          `income_cash_adview` +
          `income_cash_recommend_service`
        ),
        `income_goldcoin_total` = (
          `income_goldcoin_register_give` +
          `income_goldcoin_consume_give` +
          `income_goldcoin_checkin` +
          `income_goldcoin_adview` +
          `income_goldcoin_service_give`
        ),
        `income_points_total`   = (
          `income_points_register_give` +
          `income_points_consume_give`
        ),
        income_total            = (
          `income_cash_merchant` +
          `income_cash_recommend` +
          `income_cash_consume` +
          `income_cash_partner_subsidy` +
          `income_cash_service_subsidy` +
          `income_cash_company_subsidy` +
          `income_cash_performance` +
          `income_cash_bonus` +
          `income_cash_adview` +
          `income_cash_recommend_service` +
          `income_goldcoin_register_give` +
          `income_goldcoin_consume_give` +
          `income_goldcoin_checkin` +
          `income_goldcoin_adview` +
          `income_goldcoin_service_give` +
          `income_points_register_give` +
          `income_points_consume_give` +
          `income_goldcoin_adview`
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


