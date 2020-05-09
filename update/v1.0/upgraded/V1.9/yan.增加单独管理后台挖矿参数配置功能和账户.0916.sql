-- 执行SQL

-- 增加挖矿管理管理员
insert into zc_manager values
	(76, 18955555555, '挖矿管理', 15642, 0, 0, 0, 0);

-- 增加挖矿配置管理权限规则
insert into zc_auth_rule values
	(114, 'System/Config/mining,System/Config/parameterSave', '挖矿奖项管理', 1, 1, '');
	
-- 增加挖矿管理角色
insert into zc_auth_group values
	(18, '挖矿配置管理', 1, '114,46,5');
	
-- 增加挖矿管理用户
insert into zc_auth_group_access values
	(76, 18, null);