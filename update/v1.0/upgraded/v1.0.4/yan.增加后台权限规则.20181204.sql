-- ---------------------
-- 增加后台权限规则 
-- ---------------------
insert into zc_auth_rule values
	-- 新闻资讯
	(null, 'System/Zixun/index,System/Zixun/addUi,System/Zixun/add,System/Zixun/modify,System/Zixun/save,System/Zixun/delete', '新闻资讯管理', 1, 1, ''),
	(null, 'System/Zixun/index', '新闻资讯查看', 1, 1, ''),
	
	-- 个人代理等级晋升规则
	(null, 'System/Performance/rule,System/Performance/modify,System/Performance/save', '个人代理等级晋升规则管理', 1, 1, ''),
	(null, 'System/Performance/rule', '个人代理等级晋升规则查看', 1, 1, ''),
	
	-- 消费等级规则
	(null, 'System/Consume/rule,System/Consume/modify,System/Consume/save', '消费等级规则管理', 1, 1, ''),
	(null, 'System/Consume/rule', '消费等级规则查看', 1, 1, '');