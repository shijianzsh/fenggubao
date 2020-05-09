> 升级包含

	1、GRB转出至第三方审核管理优化
	2、两个澳交所兼容处理
	
### 一. 定时任务操作

	GRB定时转出至第三方接口：AccountTransferBit/tradeActionQueue
	

### 二、更新文件
	
	Appcenter/APP/Conf/config.php
	Appcenter/APP/Controller/AjsController.class.php
	Appcenter/APP/Controller/AccountTransferBitController.class.php
	Appcenter/APP/Controller/MemberController.class.php
	Appcenter/APP/Controller/YwtController.class.php
	Appcenter/APP/Lang/en.php
	Appcenter/APP/Lang/ko.php
	
	Appcenter/Admin/Controller/AjsTransactionController.class.php [需先还原后再更新]
	Appcenter/Admin/Controller/GrbTransactionController.class.php
	Appcenter/Admin/Controller/MemberController.class.php
	Appcenter/Admin/View/default/AjsTransaction/index.html
	Appcenter/Admin/View/default/GrbTransaction/index.html
	Appcenter/Admin/View/default/GrbTransaction/view.html
	Appcenter/Admin/View/default/Member/memberList.html
	
	Appcenter/V4/Model/WalletModel.class.php
	Appcenter/V4/Model/TransactionsModel.class.php
	
	ThinkPHP/Library/Vendor/btc/ajs_client.php
	
	Appcenter/Common/Conf/field_config.php