-- 执行SQL

-- 用户附属表添加SLU钱包地址
alter table zc_user_affiliate
	add `slu_wallet_address` varchar(50) default '' comment 'SLU钱包地址' after `zhongwy_wallet_address`;
	
-- 初始化SLU实时单价
insert into zc_goldcoin_prices values
	(null, 1, 1, unix_timestamp()-3600*24, 'SLU');