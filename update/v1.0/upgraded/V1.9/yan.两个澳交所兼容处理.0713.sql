-- 执行SQL

-- 用户附属表新增澳交所第2平台的钱包地址字段
alter table zc_user_affiliate
	add `wallet_address_2` varchar(50) default '' comment 'AOGEX的钱包地址' after `wallet_address`;