-- 执行SQL

-- 奖项配置组表增加SLU平台对接配置
insert into zc_settings_group values
	(16, 'SLU平台对接配置', 1, 444, UNIX_TIMESTAMP());
	
-- 奖项配置表增加SLU平台配置参数
insert into zc_settings (group_id,settings_title,settings_code,settings_value,settings_type,settings_summary,settings_status,settings_order)
	select replace(group_id,13,16),settings_title,replace(settings_code,'ajs_','slu_'),replace(settings_value,'',''),settings_type,settings_summary,settings_status,replace(settings_order,'13','16') from zc_settings where group_id=13;
update zc_settings set settings_uptime=UNIX_TIMESTAMP() where group_id=16;
update zc_settings set settings_value='请输入正确的转入的钱包地址' where settings_code in ('slu_trade_caption', 'slu_trade_caption_en', 'slu_trade_caption_ko');