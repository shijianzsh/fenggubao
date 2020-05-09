-- Common/Conf/config.php
[财务管理+]: array('url' => '/GrbTransaction/view', 'title' => '公让宝流通交易查看'),

-- 增加后台权限规则 
insert into zc_auth_rule values
	(null, 'Admin/GrbTransaction/view', '公让宝流通交易查看', 1, 1, '');

-- 更新文件
Appcenter/Admin/Controller/GrbTransactionController.class.php
Appcenter/Admin/View/default/GrbTransaction/view.html
Appcenter/Common/Model/Sys/GrbTradeModel.class.php