-- 执行SQL

-- 用户附属表添加SLU钱包地址
alter table zc_user_affiliate
	add `slu_wallet_address` varchar(50) default '' comment 'SLU钱包地址' after `zhongwy_wallet_address`;
	
-- 初始化SLU实时单价
insert into zc_goldcoin_prices values
	(null, 1, 1, unix_timestamp()-3600*24, 'SLU');

-- 奖项配置组表增加SLU平台对接配置
insert into zc_settings_group values
	(16, 'SLU平台对接配置', 1, 444, UNIX_TIMESTAMP());
	
-- 奖项配置表增加SLU平台配置参数
insert into zc_settings (group_id,settings_title,settings_code,settings_value,settings_type,settings_summary,settings_status,settings_order)
	select replace(group_id,13,16),settings_title,replace(settings_code,'ajs_','slu_'),replace(settings_value,'',''),settings_type,settings_summary,settings_status,replace(settings_order,'13','16') from zc_settings where group_id=13;
update zc_settings set settings_uptime=UNIX_TIMESTAMP() where group_id=16;
update zc_settings set settings_value='请输入正确的转入的钱包地址' where settings_code in ('slu_trade_caption', 'slu_trade_caption_en', 'slu_trade_caption_ko');

-- 修改奖项配置中SLU平台相关配置参数
update zc_settings set `settings_value` = '开启' where `settings_code` = 'slu_switch';
update zc_settings set `settings_value` = '0' where `settings_code` = 'slu_received_bai';
update zc_settings set `settings_value` = '10' where `settings_code` = 'slu_trade_fee';

-- 增加GRB转出SLU申请审核管理权限
insert into zc_auth_rule values
	(null, 'Admin/GrbTransaction/index/wallet_type/SLU,Admin/GrbTransaction/tradeAction,Admin/GrbTransaction/tradeBack', 'GRB转出SLU审核管理', 1, 1, '');
insert into zc_auth_rule values
	(null, 'Admin/GrbTransaction/index/wallet_type/SLU', 'GRB转出SLU审核查看', 1, 1, '');
-- 增加SLU转入申请审核管理权限
insert into zc_auth_rule values
	(null, 'Admin/SluTransaction/index,Admin/SluTransaction/tradeAction,Admin/SluTransaction/tradeBack,Admin/SluTransaction/getDetails', 'SLU转入申请审核管理', 1, 1, '');
-- 补增AJS转入申请审核管理权限
insert into zc_auth_rule values
	(null, 'Admin/AjsTransaction/index,Admin/AjsTransaction/tradeAction,Admin/AjsTransaction/tradeBack,Admin/AjsTransaction/getDetails', 'AJS转入申请审核管理', 1, 1, '');