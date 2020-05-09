-- 配置参数
一、Common/Conf/config.php
1、增加公让宝兑换专区板块ID[+]: 
	//公让宝兑换专区板块ID
	'GRB_EXCHANGE_BLOCK_ID' => 9,
	
	//礼包区板块ID
	'GIFT_PACKAGE_BLOCK_ID' => 4,
	

-- 执行SQL
-- 创建公让宝兑换专区板块
insert into zc_block values
	(9, '公让宝兑换专区', 'Uploads/block/block_icon_9.png', 'Uploads/block/cover_9.png', 0, 0, 0, 100, 0, 0, 0, 0, 0, 9, 1, 0, 0, 1, 0, 0, 0, 0);

-- 更新文件
Appcenter/APP/Controller/ShopingcartController.class.php
Appcenter/APP/Controller/IndexController.class.php
Appcenter/APP/Controller/SearchController.class.php
Appcenter/APP/Controller/OrderController.class.php

Appcenter/V4/Model/OrderModel.class.php
Appcenter/V4/Model/ProductModel.class.php

Appcenter/Shop/Controller/GoodsController.class.php
Appcenter/Shop/View/default/Goods/blockModify.html

Appcenter/Merchant/View/default/Goods/goodsAddUi.html
Appcenter/Merchant/View/default/Goods/goodsModify.html