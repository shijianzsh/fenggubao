-- Common/Conf/config.php 平台管理增加子菜单
array('url' => '/Index/advList/type/2', 'title' => '资讯轮播广告'),

-- 增加后台权限规则 
insert into zc_auth_rule values
	(null, 'System/Index/advList/type/2,System/Index/advAddUi,System/Index/advAdd,System/Index/advModify,System/Index/advSave,System/Index/advDelete', '资讯轮播广告管理', 1, 1, '');
	
-- 更新文件
Appcenter/APP/Controller/NewsController.class.php
Appcenter/System/View/default/Index/advList.html
Appcenter/V4/Model/NewsModel.class.php