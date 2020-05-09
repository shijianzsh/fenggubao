### 一. 配置修改

	1、ThinkPHP/Library/Vendor/btc/btc_config.php
		[class:SluConfig]增加配置项:
			const IMPORT_RECEIVE_ADDRESS = 'SLUQFCmpvSU1VCBXtwJQBHBePEGx4vcWmuES'; //后台转入金额被接收钱包地址
			
			//钱包转钱包手续费
			const ADDRESS_TO_ADDRESS_FEE = 0.05;
	2、定时任务
		[SLU转入队列定时任务接口]: Slu/tradeActionQueue
	3、修复之前转入未扣除用户钱包地址GRC数据的接口: Unittest/SLUImportDataToSysAddressFirst 和  Unittest/SLUImportDataToSysAddressSecond

### 二. 更新文件

	Appcenter/Admin/Controller/SluTransactionController.class.php
	Appcenter/Admin/View/default/SluTransaction/index.html
	
	Appcenter/APP/Conf/config.php
	Appcenter/APP/Controller/UnittestController.class.php
	Appcenter/APP/Controller/SluController.class.php
	
	Appcenter/Common/Conf/field_config.php
	
	Appcenter/V4/Model/TransactionsModel.class.php
	Appcenter/V4/Model/WalletModel.class.php
	
	ThinkPHP/Library/Vendor/btc/slu_client.php
	