### 一. 配置修改

	* 后台商品板块：
	1、大礼包区【是否只允许会员购买】设置为否，其他板块均设置为是。
	
	* Common/Conf/config.php
	1、增加公让宝兑换专区板块ID[+]: 
		//公让宝兑换专区板块ID
		'GRB_EXCHANGE_BLOCK_ID' => 9,
		
		//礼包区板块ID
		'GIFT_PACKAGE_BLOCK_ID' => 4,
	
	* 后台奖项配置：
	1、后台配置参数中提现功能需要全部关闭
	

### 二. 更新文件

	Appcenter/APP/Common/function.php
	Appcenter/APP/Controller/ShopingcartController.class.php [此文件更新前需先将服务器正式版对应文件还原后再更新]
	Appcenter/APP/Controller/MemberController.class.php
	Appcenter/APP/Controller/NotifyController.class.php
	Appcenter/APP/Controller/ZhongWYController.class.php
	Appcenter/APP/Controller/MiningController.class.php
	Appcenter/APP/Controller/AccountTransferBitController.class.php
	Appcenter/APP/Controller/Hack2Controller.class.php
	Appcenter/APP/Controller/IndexController.class.php
	Appcenter/APP/Controller/OrderController.class.php
	Appcenter/APP/Controller/SysController.class.php
	Appcenter/APP/Controller/SearchController.class.php
	Appcenter/APP/Controller/YwtController.class.php
	Appcenter/APP/Controller/ApplyController.class.php
	Appcenter/APP/Controller/GjjController.class.php
	Appcenter/APP/Controller/NotifyController.class.php
	Appcenter/APP/Controller/PayController.class.php
	Appcenter/APP/Controller/ProductController.class.php
	Appcenter/APP/Controller/RechargeController.class.php
	Appcenter/APP/Controller/ShopController.class.php
	Appcenter/APP/View/default/Unittest/hack_apply.html
	
	Appcenter/Common/Conf/field_config.php
	Appcenter/Common/Conf/navigation_config.php
	Appcenter/Common/Conf/data.php
	Appcenter/Common/Conf/parameter.php
	
	Appcenter/Public/exception_api.html
	Appcenter/Public/public/head.html

	Public/Admin/css/public.css
	Public/Admin/css/zc_system.css
	Public/Admin/css/zc_index.css
	
	Appcenter/V4/Model/Currency.class.php
	Appcenter/V4/Model/CurrencyAction.class.php
	Appcenter/V4/Model/AccountModel.class.php
	Appcenter/V4/Model/OrderModel.class.php
	Appcenter/V4/Model/EnjoyModel.class.php [+]
	Appcenter/V4/Model/MemberModel.class.php
	Appcenter/V4/Model/MiningModel.class.php
	Appcenter/V4/Model/GrbTradeModel.class.php
	Appcenter/V4/Model/AccountRecordModel.class.php
	Appcenter/V4/Model/ProductModel.class.php
	Appcenter/V4/Model/PaymentMethod.class.php
	
	Appcenter/Admin/Controller/IndexController.class.php
	Appcenter/Admin/Controller/MemberController.class.php [此文件更新前需先将服务器正式版对应文件还原后再更新]
	Appcenter/Admin/Controller/FinanceController.class.php
	Appcenter/Admin/Controller/ReviewController.class.php [此文件更新前需先将服务器正式版对应文件还原后再更新]
	Appcenter/Admin/View/default/Index/index.html
	Appcenter/Admin/View/default/Member/memberBonusInfo.html
	Appcenter/Admin/View/default/Finance/memberCash.html
	
	Appcenter/Shop/Controller/GoodsController.class.php
	Appcenter/Shop/View/default/Goods/block.html
	Appcenter/Shop/View/default/Goods/blockAdd.html
	Appcenter/Shop/View/default/Goods/blockModify.html

	Appcenter/Merchant/View/default/Goods/goodsAddUi.html
	Appcenter/Merchant/View/default/Goods/goodsModify.html

	Appcenter/System/Controller/SeniorSearchController.class.php
	Appcenter/System/View/default/SeniorSearch/index.html


### 三. 奖项配置参数初始固化

	签到能量值(每次签到获得个数)          10
	分享朋友圈能量值(每次分享获得个数)     5
	分享朋友圈获赠次数(每天限制次数)       3次
	挖矿能量值(每次开启挖矿消耗个数)       10
	提现能量值(每次提现消耗个数)           20
	转赠能量值(每次转赠消耗个数)           20
	流通到交易平台能量值(每次流通消耗个数)  20
	
	
### Linux 更新命令
svn update Appcenter/APP/Common/function.php

rm -f Appcenter/APP/Controller/ShopingcartController.class.php
svn update Appcenter/APP/Controller/ShopingcartController.class.php

svn update Appcenter/APP/Controller/MemberController.class.php
svn update Appcenter/APP/Controller/NotifyController.class.php
svn update Appcenter/APP/Controller/ZhongWYController.class.php
svn update Appcenter/APP/Controller/MiningController.class.php
svn update Appcenter/APP/Controller/AccountTransferBitController.class.php
svn update Appcenter/APP/Controller/Hack2Controller.class.php
svn update Appcenter/APP/Controller/IndexController.class.php
svn update Appcenter/APP/Controller/OrderController.class.php
svn update Appcenter/APP/Controller/SysController.class.php
svn update Appcenter/APP/Controller/SearchController.class.php
svn update Appcenter/APP/Controller/YwtController.class.php
svn update Appcenter/APP/Controller/ApplyController.class.php
svn update Appcenter/APP/Controller/GjjController.class.php
svn update Appcenter/APP/Controller/NotifyController.class.php
svn update Appcenter/APP/Controller/PayController.class.php
svn update Appcenter/APP/Controller/ProductController.class.php
svn update Appcenter/APP/Controller/RechargeController.class.php
svn update Appcenter/APP/Controller/ShopController.class.php
svn update Appcenter/APP/View/default/Unittest/hack_apply.html

svn update Appcenter/Common/Conf/field_config.php
svn update Appcenter/Common/Conf/navigation_config.php
svn update Appcenter/Common/Conf/data.php
svn update Appcenter/Common/Conf/parameter.php

svn update Appcenter/Public/exception_api.html
svn update Appcenter/Public/public/head.html

svn update Public/Admin/css/public.css
svn update Public/Admin/css/zc_system.css
svn update Public/Admin/css/zc_index.css

svn update Appcenter/V4/Model/Currency.class.php
svn update Appcenter/V4/Model/CurrencyAction.class.php
svn update Appcenter/V4/Model/AccountModel.class.php
svn update Appcenter/V4/Model/OrderModel.class.php
svn update Appcenter/V4/Model/EnjoyModel.class.php
svn update Appcenter/V4/Model/MemberModel.class.php
svn update Appcenter/V4/Model/MiningModel.class.php
svn update Appcenter/V4/Model/GrbTradeModel.class.php
svn update Appcenter/V4/Model/AccountRecordModel.class.php
svn update Appcenter/V4/Model/ProductModel.class.php
svn update Appcenter/V4/Model/PaymentMethod.class.php

svn update Appcenter/Admin/Controller/IndexController.class.php

rm -f Appcenter/Admin/Controller/MemberController.class.php
svn update Appcenter/Admin/Controller/MemberController.class.php

svn update Appcenter/Admin/Controller/FinanceController.class.php

rm -f Appcenter/Admin/Controller/ReviewController.class.php
svn update Appcenter/Admin/Controller/ReviewController.class.php

svn update Appcenter/Admin/View/default/Index/index.html
svn update Appcenter/Admin/View/default/Member/memberBonusInfo.html
svn update Appcenter/Admin/View/default/Finance/memberCash.html
 
svn update Appcenter/Shop/Controller/GoodsController.class.php
svn update Appcenter/Shop/View/default/Goods/block.html
svn update Appcenter/Shop/View/default/Goods/blockAdd.html
svn update Appcenter/Shop/View/default/Goods/blockModify.html

svn update Appcenter/Merchant/View/default/Goods/goodsAddUi.html
svn update Appcenter/Merchant/View/default/Goods/goodsModify.html

svn update Appcenter/System/Controller/SeniorSearchController.class.php
svn update Appcenter/System/View/default/SeniorSearch/index.html
