-- -------------------------------
-- 店长销售奖
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income_recommendService`;
DELIMITER ;;
CREATE PROCEDURE `Income_recommendService`(IN userId INT(11), OUT error INT(11))
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      # 直推上线
      select count(0)
      into @hasParent
      from
        zc_member as m
        left join zc_member as p on m.reid = p.id
      where
        m.id = userId
        and p.is_lock = 0;

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
      limit 1;

      # 销售奖比例
      SET @recommendAmount = GetSetting(CONCAT('prize_direct_service'));
      if @recommendAmount <= 0
      then
        leave out_label;
      end if;

      #添加明细
      CALL AddAccountRecord(@parentId, 'cash', 317, @recommendAmount, UNIX_TIMESTAMP(),
                            concat('{"from_uid":"', userId, '"}'), '店长销售奖', 0, error);


    END out_label;
  END
;;
DELIMITER ;