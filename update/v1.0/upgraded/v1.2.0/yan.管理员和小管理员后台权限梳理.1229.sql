-- ** 此更新文件无需负载服务器同步更新 **  --

-- Common/Conf/config.php [ROLE_MUST_LIST]增加键：

//普通管理员(以下角色ID主要用于把默认的小管理员设置为普通管理员)
    	'common1' => 7,
    	'common2' => 8,
    	'common3' => 9,
    	'common4' => 10,
    	'common5' => 11,
    	'common6' => 13,
    	'common7' => 14,
    	'common8' => 15,
    	'common9' => 16,
    	'common10' => 17
    	
    	
-- 更新文件
Appcenter/Admin/Controller/IndexController.class.php
Appcenter/System/View/default/Manager/index.html
Appcenter/System/View/default/Purview/groupManage.html
