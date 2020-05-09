-- 执行SQL

-- 用户附属表添加ETH钱包地址
alter table zc_user_affiliate
	add `eth_wallet_address` varchar(50) default '' comment 'ETH钱包地址' after `slu_wallet_address`;
	
-- 初始化ETH实时单价
insert into zc_goldcoin_prices values
	(null, 1, 1, unix_timestamp()-3600*24, 'ETH');

-- 奖项配置组表增加ETH平台对接配置
insert into zc_settings_group values
	(17, 'ETH平台对接配置', 1, 443, UNIX_TIMESTAMP());
	
-- 奖项配置表增加ETH平台配置参数
insert into zc_settings (group_id,settings_title,settings_code,settings_value,settings_type,settings_summary,settings_status,settings_order)
	select replace(group_id,13,17),settings_title,replace(settings_code,'ajs_','eth_'),replace(settings_value,'',''),settings_type,settings_summary,settings_status,replace(settings_order,'13','17') from zc_settings where group_id=13;
update zc_settings set settings_uptime=UNIX_TIMESTAMP() where group_id=17;
update zc_settings set settings_value='请输入正确的转入的钱包地址' where settings_code in ('eth_trade_caption', 'eth_trade_caption_en', 'eth_trade_caption_ko');

