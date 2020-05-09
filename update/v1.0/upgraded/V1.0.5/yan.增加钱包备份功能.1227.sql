-- Common/Conf/config.php [系统设置增加子菜单]
array('url' => '/BackupWallet/index', 'title' => '备份钱包管理'),


-- 增加后台权限规则 
insert into zc_auth_rule values
	(null, 'System/BackupWallet/index', '备份钱包管理', 1, 1, '');