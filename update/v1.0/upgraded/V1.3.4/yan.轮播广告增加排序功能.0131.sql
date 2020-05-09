-- 执行SQL
alter table zc_carousel 
	add `sort` int(8) default '0' comment '排序',
	add `is_hidden` tinyint(1) default '0' comment '是否关闭(0:否,1:是)';

-- 更新文件
Appcenter/APP/Controller/IndexController.class.php
Appcenter/APP/Controller/NewsController.class.php
Appcenter/System/View/default/Index/advAddUi.html
Appcenter/System/View/default/Index/advList.html
Appcenter/System/View/default/Index/advModify.html
