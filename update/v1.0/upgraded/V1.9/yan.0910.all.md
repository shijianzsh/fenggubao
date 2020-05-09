> 更新说明

	1、yan.GRC新钱包功能.0904
	2、yan.优化转出界面显示.0904 [注:此次升级只针对该功能升级了程序文件,APP并未做对应升级,但不影响正常功能,届时只需升级APP即可实现该功能的升级]
	3、yan.转出至SLU功能.0904
	
### 一. 需操作事项

	1、定时任务需增加SLU钱包平台接口如下：
		[导入转入交易 - SLU]: Slu/importReceives
		[获取实时单价 - SLU]: Slu/price
	2、需开启SLU对接开关、转入无奖励、转出扣10%手续费 [√]
	3、把所有定时任务中的备份钱包数据功能关闭
		
### 二. 配置文件修改

	1、Common/Conf/config.php [新增配置:]
		//公让宝实时单价采用平台类型名称[ZWY/AJS/SLU]
		'GRB_PRICE_TYPE' => 'SLU',

### 三. 更新文件

	Appcenter/Admin/Controller/MemberController.class.php
	Appcenter/Admin/Controller/GrbTransactionController.class.php
	Appcenter/Admin/Controller/SluTransactionController.class.php [+]
	Appcenter/Admin/View/default/SluTransaction [+]
	Appcenter/Admin/View/default/GrbTransaction/index.html
	Appcenter/Admin/View/default/GrbTransaction/view.html
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
	Appcenter/Common/Conf/field_config.php
	
	Appcenter/System/Controller/GoldcoinPriceController.class.php
	
	Appcenter/V4/Model/WalletModel.class.php
	Appcenter/V4/Model/TransactionsModel.class.php
	Appcenter/V4/Model/GoldcoinPricesModel.class.php
	
	ThinkPHP/Library/Vendor/btc/btc_config.php
	ThinkPHP/Library/Vendor/btc/slu_client.php [+]
	ThinkPHP/Library/Vendor/Slu [+]
	