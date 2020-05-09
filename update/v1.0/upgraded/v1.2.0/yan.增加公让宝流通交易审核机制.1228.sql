-- Common/Conf/config.php 财务管理增加子菜单
array('url'=>'/GrbTransaction/index', 'title'=>'公让宝流通交易申请管理'),

-- Common/Conf/config.php [FIELD_CONFIG]增加表字段对应中文配置
'trade' => [
    		'status' => ['0' => '待审核', '1' => '驳回', '2' => '提交失败', '3' => '提交成功'],	
    	],

-- 增加后台权限规则 
insert into zc_auth_rule values
	(null, 'Admin/GrbTransaction/index,Admin/GrbTransaction/tradeAction,Admin/GrbTransaction/tradeBack', '公让宝流通交易申请管理', 1, 1, ''),
	(null, 'Admin/GrbTransaction/index', '公让宝流通交易申请查看', 1, 1, '');