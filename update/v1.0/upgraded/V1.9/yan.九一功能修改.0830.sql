-- 执行SQL

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