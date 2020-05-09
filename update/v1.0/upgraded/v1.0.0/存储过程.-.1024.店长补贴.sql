-- -------------------------------
-- 店长补贴
-- -------------------------------
DROP PROCEDURE IF EXISTS `Income_subsidyService`;
DELIMITER ;;
CREATE PROCEDURE `Income_subsidyService`(
  IN  userId            INT(11),
  IN  performanceAmount decimal(14, 4),
  IN  orderId           INT(11),
  OUT error             INT(11)
)
  BEGIN

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET error = 1; # 异常错误
    SET error = 0;

    out_label: BEGIN

      select count(0)
      into @hasParent
      from
        zc_member as m
        left join zc_member as p on find_in_set(p.id, m.repath)
      where
        m.id = userId
        and p.role = 3
        and p.is_lock = 0;

      if @hasParent = 0
      then
        leave out_label;
      end if;

      select
        p.id,
        p.role_star
      into @parentId, @roleStar
      from
        zc_member as m
        left join zc_member as p on find_in_set(p.id, m.repath)
      where
        m.id = userId
        and p.role = 3
        and p.is_lock = 0
      order by p.relevel desc
      limit 1;

      # 补贴比例
      SET @subsidyBai = GetSetting(CONCAT('service_star_subsidy_', @roleStar));
      if @subsidyBai <= 0
      then
        leave out_label;
      end if;

      #添加明细
      CALL AddAccountRecord(@parentId, 'cash', 311, performanceAmount * @subsidyBai * 0.01, UNIX_TIMESTAMP(),
                            concat('{"from_uid":"', userId, '","order_id":"', orderId, '"}'), '店长补贴', @subsidyBai,
                            error);
    END out_label;
  END
;;
DELIMITER ;