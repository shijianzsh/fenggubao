DROP PROCEDURE IF EXISTS `Release_oldLock2`;
DELIMITER ;;
CREATE PROCEDURE `Release_oldLock2`(OUT error INT(11))
BEGIN

    declare done int default 0;
    declare c_user_id int default 0;
    declare c_id int default 0;
    declare c_total_amount DECIMAL(14,4) default 0;
    declare c_lock_amount DECIMAL(14,4) default 0;

    # 获取所有释放队列
    declare c_user cursor for
        select
            m.id,
            l.id,
            l.total_amount,
            l.lock_amount
        from
            zc_lock as l
                left join zc_member as m on l.user_id = m.id
        where
          l.lock_amount > 0
          AND m.is_lock = 0;

    declare continue handler for not found set done = 1;
    declare continue handler for sqlexception set error = 1; -- 异常错误
    set error = 0;

    out_label: BEGIN

        open c_user;
        repeat fetch c_user into c_user_id, c_id, c_total_amount, c_lock_amount;
        if not done
        then
            begin
                out_repeat:BEGIN

                    SET  @releaseBai = 0.5;

                    SET @releaseAmount = c_total_amount * @releaseBai * 0.01;
                    if @releaseAmount > c_lock_amount THEN
                        set @releaseAmount = c_lock_amount;
                    end if;

                    # 添加明细
                    CALL AddAccountRecord(c_user_id, 'goldcoin', 113, @releaseAmount, UNIX_TIMESTAMP(), '', 'sdjcsf', @releaseBai, error);

                    if error
                    then
                        leave out_label;
                    end if;

                END out_repeat;
            end;
        end if;
        until done end repeat;
        close c_user;
    END out_label;
END
;;
DELIMITER ;




CALL Release_oldLock2(@error);
SELECT @error;

DROP PROCEDURE IF EXISTS `Release_oldLock2`;
