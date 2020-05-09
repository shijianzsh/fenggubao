-- 执行SQL

-- 第三方(中网云)转入GRB奖励百分比
insert into zc_settings values
	(null, 5, '转入商城奖励百分比', 'zhongwy_received_bai', '10', 'text', '%', 1, 586, unix_timestamp());
	
-- 第三方(澳交所)转入GRB奖励百分比
insert into zc_settings values
	(null, 13, '转入商城奖励百分比', 'ajs_received_bai', '10', 'text', '%', 1, 1391, unix_timestamp());