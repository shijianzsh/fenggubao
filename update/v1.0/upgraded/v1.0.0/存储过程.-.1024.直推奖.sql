-- -------------------------------
-- 直推奖
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income_recommend`;
DELIMITER ;;
CREATE PROCEDURE `Income_recommend`(IN userId INT(11), IN amount DECIMAL(14, 4), OUT error INT(11))
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      # 直推上线必须是VIP，才能获取直推奖
      select count(0)
      into @hasParent
      from
        zc_member as m
        left join zc_member as p on m.reid = p.id
      where
        m.id = userId
        and p.is_lock = 0
        and p.`level` in (2);

      if @hasParent = 0
      then
        leave out_label;
      end if;

      select
        p.id,
        p.`level`,
        m.`level`
      into @parentId, @parentLevel, @userLevel
      from
        zc_member as m
        left join zc_member as p on m.reid = p.id
      where
        m.id = userId
        and p.is_lock = 0
        and p.`level` in (2);

      # 直推奖比例
      SET @recommendBai = GetSetting(CONCAT('prize_direct_bai_', @parentLevel));
      if @recommendBai <= 0
      then
        leave out_label;
      end if;

      #添加明细
      CALL AddAccountRecord(@parentId, 'cash', 309, amount * @recommendBai * 0.01, UNIX_TIMESTAMP(),
                            concat('{"from_uid":"', userId, '"}'), '直推奖', @recommendBai, error);


    END out_label;
  END
;;
DELIMITER ;