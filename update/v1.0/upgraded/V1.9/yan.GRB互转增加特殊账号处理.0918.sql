-- 执行SQL

-- 增加GRB站内互转不受限制特殊账号配置
insert into zc_settings values 
	(null, 6, 'GRB站内互转不受限制特殊账号', 'special_inside_transfer_account', '13388889999,13183080111,18538553588', 'text', '多个账号用英文逗号","分隔, 不能有空格', 1, 483, UNIX_TIMESTAMP());

-- 增加GRB站内互转不受限制体系账号配置
insert into zc_settings values 
	(null, 6, 'GRB站内互转不受限制体系账号', 'special_inside_transfer_system', '17380338711', 'text', '多个体系用英文逗号","分隔, 不能有空格', 1, 482, UNIX_TIMESTAMP());