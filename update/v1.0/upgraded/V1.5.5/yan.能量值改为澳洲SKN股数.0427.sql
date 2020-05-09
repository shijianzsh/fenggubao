-- 执行SQL

-- 修改配置组表中能量值字眼为澳洲SKN股数
update zc_settings_group set group_name=replace(group_name, '能量值', '澳洲SKN股数');

-- 修改配置项表中能量值字眼为澳洲SKN股数
update zc_settings set settings_title=replace(settings_title, '能量值', '澳洲SKN股数')
	,settings_summary=replace(settings_summary, '能量值', '澳洲SKN股数');