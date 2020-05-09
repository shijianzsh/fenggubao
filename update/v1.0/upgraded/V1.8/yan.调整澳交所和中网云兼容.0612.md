### 一. 更新文件

	Appcenter/Admin/Controller/GrbTransactionController.class.php
	Appcenter/Admin/Controller/AjsTransactionController.class.php
	Appcenter/Admin/Controller/ZwyTransactionController.class.php [+]
	Appcenter/Admin/View/default/ZwyTransaction [+]
	Appcenter/Admin/View/default/GrbTransaction/index.html
	Appcenter/Admin/View/default/GrbTransaction/view.html

	Appcenter/APP/Conf/config.php
	Appcenter/APP/Controller/AccountTransferBitController.class.php
	Appcenter/APP/Controller/AjsController.class.php
	Appcenter/APP/Controller/MemberController.class.php
	Appcenter/APP/Controller/YwtController.class.php [先还原后再更新]
	Appcenter/APP/Controller/ZhongWYController.class.php [先还原后再更新]
	
	Appcenter/Common/Conf/navigation_config.php
	
	Appcenter/System/Controller/GoldcoinPriceController.class.php [+]
	Appcenter/System/View/default/GoldcoinPrice [+]

	Appcenter/V4/Model/GoldcoinPricesModel.class.php
	Appcenter/V4/Model/WalletModel.class.php
	Appcenter/V4/Model/TransactionsModel.class.php
	
	ThinkPHP/Library/Vendor/btc/btc_client.php
	
### 二. 修改配置

	1. 修改Vendor/btc/btc_config.php的钱包配置 

### APP调用需增加钱包类型的接口如下：

> [转出至第三方说明](http://pms.it-rayko.com/www/index.php?m=doc&f=view&docID=10003917)

> [转出至第三方](http://pms.it-rayko.com/www/index.php?m=doc&f=view&docID=10003918)

> [获取第三方支付开关状态](http://pms.it-rayko.com/www/index.php?m=doc&f=view&docID=10003919)

> [公让宝首页](http://pms.it-rayko.com/www/index.php?m=doc&f=view&docID=10003858)


### 定时任务需增加钱包类型的接口如下：

> [定时备份钱包] AccountTransferBit/backupWalletTask

	传参方式：GET
	增加参数：wallet_type 钱包类型(澳交所:AJS, 中网云:ZWY),默认:澳交所ZWY
	
> [获取实时单价-澳交所] Ajs/price

> [导入转入交易 - 中网云] ZhongWY/importReceives