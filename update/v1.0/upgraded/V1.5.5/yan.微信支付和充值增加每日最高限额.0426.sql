-- 执行SQL

-- 创建第三方支付配置组
insert into zc_settings_group values 
	(12, '第三方支付配置', 1, '97', unix_timestamp());
-- 创建第三方支付配置参数
insert into zc_settings values
-- (null, 12, '支付每日最高限额', 'third_pay_max_amount', '1000', 'text', '元(值为0则视为不限制)', 1, 1251, unix_timestamp()),
-- (null, 12, '充值每日最高限额', 'third_recharge_max_amount', '1000', 'text', '元(值为0则视为不限制)', 1, 1250, unix_timestamp()),
   (null, 12, '微信支付和充值每日最高限额', 'third_all_max_amount', '1000', 'text', '元(值为0则视为不限制)', 1, 1249, unix_timestamp());