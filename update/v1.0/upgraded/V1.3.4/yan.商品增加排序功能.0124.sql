-- 更新文件
Appcenter/Admin/Controller/AjaxController.class.php
Appcenter/APP/Controller/IndexController.class.php
Appcenter/APP/Controller/SearchController.class.php
Appcenter/Merchant/Controller/GoodsController.class.php
Appcenter/Merchant/View/default/Goods/index.html
Appcenter/Admin/View/default/Ajax/setGoodsSort.html [+]


-- 修改后台权限规则 
update zc_auth_rule set `name`=concat(`name`, ',Merchant/Goods/modifySort') where id=48;