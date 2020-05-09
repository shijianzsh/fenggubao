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



