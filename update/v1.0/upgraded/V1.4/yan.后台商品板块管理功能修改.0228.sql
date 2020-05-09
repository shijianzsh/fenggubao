-- 执行SQL
-- 添加商品板块管理操作权限
update zc_auth_rule set `name`=concat(`name`, ',Shop/Goods/block_enabled,Shop/Goods/block_modifysort') where id=85;

-- 更新文件
Appcenter/Admin/Controller/AjaxController.class.php
Appcenter/APP/Controller/IndexController.class.php
Appcenter/Common/Conf/field_config.php
Appcenter/Shop/Controller/GoodsController.class.php
Appcenter/Shop/View/default/Goods/block.html
Appcenter/Admin/View/default/Ajax/setBlockSort.html [+]