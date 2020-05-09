INSERT INTO zc_settings
VALUES (NULL, 1, '关爱奖禁止收益帐号', 'prize_care_agent_prohibit_users', '18382205868', 'text', '多个账号用英文逗号","分隔, 不能有空格', 1, 187, unix_timestamp());


# # 补关爱奖
# CALL AddAccountRecord(4816, 'goldcoin', 108, 255.467, UNIX_TIMESTAMP(), concat('{"order_id":"', 4619, '"}'), 'gaj', 50, @error);
# select @error;
# CALL Income_add(4816, 255.467, @error);
# select @error;
#
# # 补关爱奖
# CALL AddAccountRecord(4538, 'goldcoin', 108, 19.5802, UNIX_TIMESTAMP(), concat('{"order_id":"', 4500, '"}'), 'gaj', 50, @error);
# select @error;
# CALL Income_add(4538, 19.5802, @error);
# select @error;


