-- Common/Conf/config.php ** 重要修改 ***
1、【LOAD_EXT_CONFIG】增加",field_config"内容
2、移除【通用数据库字段数据相关配置'FIELD_CONFIG'】整块数据
3、同目录下新增【field_config.php】文件

-- 资讯表增加分类字段
alter table zc_news add `category` tinyint(1) unsigned not null default '1' comment '分类(1:新动态,2:知产品,3:爱分享,4:大事记)' after `sort`;

-- 更新文件
Appcenter/APP/Controller/NewsController.class.php
Appcenter/System/Controller/ZixunController.class.php
Appcenter/System/View/default/Zixun/addUi.html
Appcenter/System/View/default/Zixun/index.html
Appcenter/System/View/default/Zixun/modify.html
Appcenter/Public/public/head.html
[+] Appcenter/Common/Conf/field_config.php
[+] Public/Public/js/layui