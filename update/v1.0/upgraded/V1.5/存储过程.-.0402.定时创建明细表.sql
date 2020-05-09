-- ----------------------------
-- 定时创建明细表
-- ----------------------------
DROP PROCEDURE IF EXISTS `TimerTask_recordtable`;
DELIMITER ;;
CREATE PROCEDURE `TimerTask_recordtable`(OUT error INT(11))
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    SET @month_tag = FROM_UNIXTIME(unix_timestamp(), '%Y%m');
    SET @nextmonth_tag = DATE_FORMAT(DATE_ADD(@month_tag * 100 + 1, INTERVAL 1 MONTH), '%Y%m');

    # 创建当月流通资产交易记录表
    SET @v_sql = CONCAT('CREATE TABLE IF NOT EXISTS `zc_account_goldcoin_', @month_tag, '` LIKE `zc_account_record`;');
    PREPARE statement FROM @v_sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    # 创建下月流通资产交易记录表
    SET @v_sql = CONCAT('CREATE TABLE IF NOT EXISTS `zc_account_goldcoin_', @nextmonth_tag, '` LIKE `zc_account_record`;');
    PREPARE statement FROM @v_sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    # 创建当月锁定资产交易记录表
    SET @v_sql = CONCAT('CREATE TABLE IF NOT EXISTS `zc_account_bonus_', @month_tag, '` LIKE `zc_account_record`;');
    PREPARE statement FROM @v_sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    # 创建下月锁定资产交易记录表
    SET @v_sql = CONCAT('CREATE TABLE IF NOT EXISTS `zc_account_bonus_', @nextmonth_tag, '` LIKE `zc_account_record`;');
    PREPARE statement FROM @v_sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

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

    # 创建当月提货券交易记录表
    SET @v_sql = CONCAT('CREATE TABLE IF NOT EXISTS `zc_account_colorcoin_', @month_tag, '` LIKE `zc_account_record`;');
    PREPARE statement FROM @v_sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    # 创建下月提货券交易记录表
    SET @v_sql = CONCAT('CREATE TABLE IF NOT EXISTS `zc_account_colorcoin_', @nextmonth_tag, '` LIKE `zc_account_record`;');
    PREPARE statement FROM @v_sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    # 创建当月兑换券交易记录表
    SET @v_sql = CONCAT('CREATE TABLE IF NOT EXISTS `zc_account_enroll_', @month_tag, '` LIKE `zc_account_record`;');
    PREPARE statement FROM @v_sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    # 创建下月兑换券交易记录表
    SET @v_sql = CONCAT('CREATE TABLE IF NOT EXISTS `zc_account_enroll_', @nextmonth_tag, '` LIKE `zc_account_record`;');
    PREPARE statement FROM @v_sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    # 创建当月矿池交易记录表
    SET @v_sql = CONCAT('CREATE TABLE IF NOT EXISTS `zc_account_points_', @month_tag, '` LIKE `zc_account_record`;');
    PREPARE statement FROM @v_sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    # 创建下月矿池交易记录表
    SET @v_sql = CONCAT('CREATE TABLE IF NOT EXISTS `zc_account_points_', @nextmonth_tag, '` LIKE `zc_account_record`;');
    PREPARE statement FROM @v_sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    # 创建当月报单币交易记录表
    SET @v_sql = CONCAT('CREATE TABLE IF NOT EXISTS `zc_account_supply_', @month_tag, '` LIKE `zc_account_record`;');
    PREPARE statement FROM @v_sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    # 创建下月报单币交易记录表
    SET @v_sql = CONCAT('CREATE TABLE IF NOT EXISTS `zc_account_supply_', @nextmonth_tag, '` LIKE `zc_account_record`;');
    PREPARE statement FROM @v_sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    # 创建当月能量值交易记录表
    SET @v_sql = CONCAT('CREATE TABLE IF NOT EXISTS `zc_account_enjoy_', @month_tag, '` LIKE `zc_account_record`;');
    PREPARE statement FROM @v_sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    # 创建下月能量值交易记录表
    SET @v_sql = CONCAT('CREATE TABLE IF NOT EXISTS `zc_account_enjoy_', @nextmonth_tag, '` LIKE `zc_account_record`;');
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

CALL TimerTask_recordtable(@error);
select @error;