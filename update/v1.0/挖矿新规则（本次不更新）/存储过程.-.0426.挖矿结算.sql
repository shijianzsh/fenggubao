-- -------------------------------
-- 挖矿结算
-- -------------------------------
DROP PROCEDURE IF EXISTS `Mining_settle`;
DELIMITER ;;
CREATE PROCEDURE `Mining_settle`(IN miningTag INT(8),
                                 OUT error INT(11))
BEGIN

  DECLARE done INT DEFAULT 0;
  DECLARE c_user_id INT DEFAULT 0;
  DECLARE c_amount DECIMAL(14, 4) DEFAULT 0;

  # 获取所有挖矿数据
  DECLARE c_mining CURSOR FOR
    SELECT m.id,
           mi.amount
    FROM zc_mining AS mi
           LEFT JOIN zc_member AS m ON mi.user_id = m.id
    WHERE mi.tag = miningTag
      AND m.level = 2
      AND m.is_lock = 0
      AND m.id IS NOT NULL
      AND mi.user_id > 1;

  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; -- 异常错误
  SET error = 0;

  out_label:
  BEGIN

    OPEN c_mining;
    REPEAT
      FETCH c_mining
        INTO c_user_id, c_amount;
      IF NOT done
      THEN
        BEGIN
          out_repeat:
          BEGIN

            -- 分发流通资产
            SET @circulateBai = GetSetting('mine_circulate_bai');
            IF @circulateBai > 0 THEN
              -- 结算给用户
              CALL AddAccountRecord(c_user_id, 'goldcoin', 115, c_amount * @circulateBai * 0.01, UNIX_TIMESTAMP(),
                                    concat('{"miningTag":"', miningTag, '"}'), 'wk', 0, error);
              IF error THEN
                LEAVE out_label;
              END IF;
            END IF;

            -- 分发锁定资产
            SET @lockBai = GetSetting('mine_lock_bai');
            IF @lockBai > 0 THEN
              -- 结算给用户
              CALL AddAccountRecord(c_user_id, 'bonus', 215, c_amount * @lockBai * 0.01, UNIX_TIMESTAMP(),
                                    concat('{"miningTag":"', miningTag, '"}'), 'wk', 0, error);
              IF error THEN
                LEAVE out_label;
              END IF;
            END IF;

            -- 从矿池中扣除
            CALL AddAccountRecord(1, 'points', 550, -c_amount, UNIX_TIMESTAMP(),
                                  concat('{"user_id":"', c_user_id, '","miningTag":"', miningTag, '"}'), 'wk', 0,
                                  error);
            IF error
            THEN
              LEAVE out_label;
            END IF;

            # 0 表示挖矿所得
            SET @orderId = 0;
            -- 分发管理津贴
            CALL Income_subsidy(c_user_id, c_amount, @orderId, error);
            IF error
            THEN
              LEAVE out_label;
            END IF;

            # 添加关爱奖队列
            INSERT INTO `zc_care_queue` VALUES (NULL, c_user_id, c_amount, @orderId, 0, UNIX_TIMESTAMP(), 0, 0);

          END out_repeat;
        END;
      END IF;
    UNTIL done END REPEAT;
    CLOSE c_mining;
  END out_label;
END
;;
DELIMITER ;