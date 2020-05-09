> 更新说明

	1、三个钱包平台均配置主钱包信息,并在后台GRC转出审核管理页面显示
	2、后台GRC转出审核提交队列时判断主钱包余额是否充足
	3、修复点击钱包页面自动检测并分配钱包地址的bug
	
### 一. 手动更新

	1、ThinkPHP/Library/Vendor/btc/btc_config.php
		[class:BtcConfig]中增加如下配置：
			const MASTER_ADDRESS = 'FvhGTxk1kJg6w14npyxxotrjVa2eLoxeWQ'; //主钱包地址
		[class:AoJSConfig]中增加如下配置：
			const MASTER_ADDRESS = 'GciJ8A92jkRx3ZZ5VWHfHuNna178xJs237'; //主钱包地址
	
### 二. 更新文件

	Appcenter/Admin/Controller/GrbTransactionController.class.php
	Appcenter/Admin/View/default/GrbTransaction/index.html
	
	Appcenter/APP/Controller/MemberController.class.php
	
	Appcenter/V4/Model/WalletModel.class.php
	