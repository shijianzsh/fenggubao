> 说明

	1. GRB页面接口(Ywt/index)自动检测用户钱包并生成钱包地址的功能，迁移至用户中心的我的钱包页面接口(Member/userWalletInfo)中
	2. 钱包自动备份接口(AccountTransferBit/backupWalletTask):自动删除前一周备份的文件

### 一. 更新文件

	Appcenter/APP/Controller/AccountTransferBitController.class.php
	Appcenter/APP/Controller/MemberController.class.php
	Appcenter/APP/Controller/YwtController.class.php
		