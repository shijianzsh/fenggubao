-- 执行SQL

-- 奖项配置组表增加华佗商城对接配置
insert into zc_settings_group values
	(15, '华佗商城对接配置', 1, 445, UNIX_TIMESTAMP());
	
-- 奖项配置表增加华佗商城配置参数
insert into zc_settings (group_id,settings_title,settings_code,settings_value,settings_type,settings_summary,settings_status,settings_order)
	select replace(group_id,13,15),settings_title,replace(settings_code,'ajs_','ht_'),replace(settings_value,'',''),settings_type,settings_summary,settings_status,replace(settings_order,'13','15') from zc_settings where group_id=13;
update zc_settings set settings_uptime=UNIX_TIMESTAMP() where group_id=15;
update zc_settings set settings_value='请输入正确的转入的会员编号登录名' where settings_code in ('ht_trade_caption', 'ht_trade_caption_en', 'ht_trade_caption_ko');

-- 更改配置参数
-- 第三方转入奖励配置
update zc_settings set `settings_value`='0' where `settings_code`='zhongwy_received_bai';
update zc_settings set `settings_value`='0' where `settings_code`='ajs_received_bai';
-- 挖矿配置
update zc_settings set `settings_value`='10' where `settings_code`='mine_thanksgiving_dai_5000';
update zc_settings set `settings_value`='0.5' where `settings_code`='mine_thanksgiving_bai_5000';
update zc_settings set `settings_value`='20' where `settings_code`='mine_thanksgiving_dai_10000';
update zc_settings set `settings_value`='0.5' where `settings_code`='mine_thanksgiving_bai_10000';
update zc_settings set `settings_value`='30' where `settings_code`='mine_thanksgiving_dai_30000';
update zc_settings set `settings_value`='0.5' where `settings_code`='mine_thanksgiving_bai_30000';
update zc_settings set `settings_value`='40' where `settings_code`='mine_thanksgiving_dai_50000';
update zc_settings set `settings_value`='0.5' where `settings_code`='mine_thanksgiving_bai_50000';