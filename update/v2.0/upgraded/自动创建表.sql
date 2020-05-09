-- ----------------------------
-- 定时创建交易记录表
-- ----------------------------
DROP PROCEDURE IF EXISTS `TimerTask_recordtable`;
DELIMITER ;;
CREATE PROCEDURE `TimerTask_recordtable`(OUT error INT(11))
BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    SET @month_tag = FROM_UNIXTIME(unix_timestamp(), '%Y%m');
    SET @nextmonth_tag = DATE_FORMAT(DATE_ADD(@month_tag * 100 + 1, INTERVAL 1 MONTH), '%Y%m');

    CALL Create_recordtable('goldcoin', @month_tag, error);
    CALL Create_recordtable('goldcoin', @nextmonth_tag, error);

    CALL Create_recordtable('bonus', @month_tag, error);
    CALL Create_recordtable('bonus', @nextmonth_tag, error);

    CALL Create_recordtable('cash', @month_tag, error);
    CALL Create_recordtable('cash', @nextmonth_tag, error);

    CALL Create_recordtable('colorcoin', @month_tag, error);
    CALL Create_recordtable('colorcoin', @nextmonth_tag, error);

    CALL Create_recordtable('enroll', @month_tag, error);
    CALL Create_recordtable('enroll', @nextmonth_tag, error);

    CALL Create_recordtable('points', @month_tag, error);
    CALL Create_recordtable('points', @nextmonth_tag, error);

    CALL Create_recordtable('credits', @month_tag, error);
    CALL Create_recordtable('credits', @nextmonth_tag, error);

    CALL Create_recordtable('supply', @month_tag, error);
    CALL Create_recordtable('supply', @nextmonth_tag, error);


    # 创建当月业绩表
    SET @v_sql = CONCAT('CREATE TABLE IF NOT EXISTS `zc_performance_', @month_tag, '` LIKE `zc_performance`;');
    PREPARE statement FROM @v_sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    # 创建下月业绩表
    SET @v_sql = CONCAT('CREATE TABLE IF NOT EXISTS `zc_performance_', @nextmonth_tag, '` LIKE `zc_performance`;');
    PREPARE statement FROM @v_sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

END
;;
DELIMITER ;

-- ----------------------------
-- 创建交易记录表
-- ----------------------------
DROP PROCEDURE IF EXISTS `Create_recordtable`;
DELIMITER ;;
CREATE PROCEDURE `Create_recordtable`(IN currency VARCHAR(20), IN tag VARCHAR(10), OUT error INT(11))
BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    # 创建当月流通资产交易记录表
    SET @v_sql = CONCAT('CREATE TABLE IF NOT EXISTS `zc_account_', currency, '_', tag, '` LIKE `zc_account_record`;');
    PREPARE statement FROM @v_sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

END
;;
DELIMITER ;


CALL TimerTask_recordtable(@error);
SELECT @error;

