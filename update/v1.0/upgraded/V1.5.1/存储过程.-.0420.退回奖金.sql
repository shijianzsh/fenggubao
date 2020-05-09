-- -------------------------------
-- 退回公让宝
-- -------------------------------
DROP PROCEDURE IF EXISTS `Refund_goldcoin_4`;
DELIMITER ;;
CREATE PROCEDURE `Refund_goldcoin_4`(IN orderId INT(11),
                                     OUT error TINYINT(1))
BEGIN

  DECLARE done INT DEFAULT 0;
  DECLARE c_record_id INT DEFAULT 0;
  DECLARE c_user_id INT DEFAULT 0;
  DECLARE c_record_amount DECIMAL(14, 4) DEFAULT 0;

  # 获取当日所有挖矿队列
  DECLARE c_cursor CURSOR FOR
    SELECT record_id, user_id, record_amount
    FROM zc_account_goldcoin_201904 AS ag
    WHERE record_attach LIKE CONCAT(
        '%"order_id":"',
        orderId,
        '"%'
      )
      AND record_amount > 0
      AND record_action NOT IN (116);

  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; -- 异常错误
  SET error = 0;

  out_label:
  BEGIN
    OPEN c_cursor;
    REPEAT
      FETCH c_cursor
        INTO c_record_id, c_user_id, c_record_amount;
      IF NOT done
      THEN
        BEGIN
          out_repeat:
          BEGIN
            -- 添加明细
            CALL AddAccountRecord(c_user_id, 'goldcoin', 999, -c_record_amount, UNIX_TIMESTAMP(), concat('{"order_id":"', orderId, '", "record_id":"', c_record_id, '"}'),
                                  'rollback', 0, error);
            IF error THEN
              LEAVE out_label;
            END IF;

            UPDATE zc_account_goldcoin_201904 SET record_action = 999 WHERE record_id = c_record_id;

            UPDATE zc_consume SET income_amount = income_amount - c_record_amount WHERE user_id = c_user_id;

          END out_repeat;
        END;
      END IF;
    UNTIL done END REPEAT;
    CLOSE c_cursor;
  END out_label;
END
;;
DELIMITER ;


DROP PROCEDURE IF EXISTS `Refund_cash_4`;
DELIMITER ;;
CREATE PROCEDURE `Refund_cash_4`(IN orderId INT(11),
                                 OUT error TINYINT(1))
BEGIN

  DECLARE done INT DEFAULT 0;
  DECLARE c_record_id INT DEFAULT 0;
  DECLARE c_user_id INT DEFAULT 0;
  DECLARE c_record_amount DECIMAL(14, 4) DEFAULT 0;

  # 获取当日所有挖矿队列
  DECLARE c_cursor CURSOR FOR
    SELECT record_id, user_id, record_amount
    FROM zc_account_cash_201904 AS ag
    WHERE record_attach LIKE CONCAT(
        '%"order_id":"',
        orderId,
        '"%'
      )
      AND record_amount > 0
      AND record_action NOT IN (416);

  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; -- 异常错误
  SET error = 0;

  out_label:
  BEGIN
    OPEN c_cursor;
    REPEAT
      FETCH c_cursor
        INTO c_record_id, c_user_id, c_record_amount;
      IF NOT done
      THEN
        BEGIN
          out_repeat:
          BEGIN
            -- 添加明细
            CALL AddAccountRecord(c_user_id, 'cash', 999, -c_record_amount, UNIX_TIMESTAMP(), concat('{"order_id":"', orderId, '", "record_id":"', c_record_id, '"}'),
                                  'rollback', 0, error);
            IF error THEN
              LEAVE out_label;
            END IF;

            UPDATE zc_account_cash_201904 SET record_action = 999 WHERE record_id = c_record_id;

          END out_repeat;
        END;
      END IF;
    UNTIL done END REPEAT;
    CLOSE c_cursor;
  END out_label;
END
;;
DELIMITER ;


-- -------------------------------
-- 退回公让宝
-- -------------------------------
DROP PROCEDURE IF EXISTS `Refund`;
DELIMITER ;;
CREATE PROCEDURE `Refund`(IN orderId INT(11),
                          OUT error TINYINT(1))
BEGIN

  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; -- 异常错误
  SET error = 0;

  START TRANSACTION ;
  out_label:
  BEGIN
    CALL Refund_goldcoin_4(orderId, error);
    IF error THEN
      LEAVE out_label;
    END IF;

    CALL Refund_cash_4(orderId, error);
    IF error THEN
      LEAVE out_label;
    END IF;

  END out_label;
  IF error THEN
    ROLLBACK ;
  ELSE
    COMMIT ;
  END IF;
END
;;
DELIMITER ;
