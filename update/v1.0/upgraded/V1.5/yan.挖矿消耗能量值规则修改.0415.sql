-- 执行SQL

-- 修改挖矿消耗能量值配置参数文字说明
update zc_settings set settings_summary='能量值(每次开启挖矿每0.5个矿机消耗个数)' where settings_code='enjoy_mining';