> 说明

	1、定时任务需增加SLU钱包平台接口如下：
		[导入转入交易 - SLU]: Slu/importReceives
		[获取实时单价 - SLU]: Slu/price
	2、后台奖项配置:需开启SLU对接开关
		
### 一. 配置文件修改

	1、Common/Conf/config.php [新增配置:]
		//公让宝实时单价采用平台类型名称[ZWY/AJS/SLU]
		'GRB_PRICE_TYPE' => 'SLU',

### 二. 更新文件

	Appcenter/Admin/Controller/MemberController.class.php
	Appcenter/Admin/Controller/GrbTransactionController.class.php
	Appcenter/Admin/Controller/SluTransactionController.class.php [+]
	Appcenter/Admin/View/default/SluTransaction [+]
	Appcenter/Admin/View/default/GrbTransaction/index.html
	Appcenter/Admin/View/default/Member/memberList.html [更新前需先还原]
	Appcenter/Admin/View/default/Member/memberListNot.html [更新前需先还原]
	
	Appcenter/APP/Conf/config.php
	Appcenter/APP/Controller/AccountTransferBitController.class.php
	Appcenter/APP/Controller/MemberController.class.php
	Appcenter/APP/Controller/UnittestController.class.php
	Appcenter/APP/Controller/SluController.class.php [+]
	Appcenter/APP/Controller/YwtController.class.php
	Appcenter/APP/Controller/ZhongWYController.class.php
	
	Appcenter/Common/Conf/navigation_config.php
	
	Appcenter/System/Controller/GoldcoinPriceController.class.php
	
	Appcenter/V4/Model/WalletModel.class.php
	Appcenter/V4/Model/TransactionsModel.class.php
	Appcenter/V4/Model/GoldcoinPricesModel.class.php
	
	ThinkPHP/Library/Vendor/btc/btc_config.php
	ThinkPHP/Library/Vendor/btc/slu_client.php [+]
	ThinkPHP/Library/Vendor/Slu [+]
	