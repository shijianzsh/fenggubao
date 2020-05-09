-- 更新文件
Appcenter/Admin/Controller/FinanceController.class.php
Appcenter/Admin/Controller/IndexController.class.php
Appcenter/Admin/Controller/MemberController.class.php
Appcenter/Admin/View/default/Finance/memberCash.html
Appcenter/Admin/View/default/Finance/transfer.html
Appcenter/Admin/View/default/Index/index.html
Appcenter/Admin/View/default/Member/memberBonusInfo.html
Appcenter/APP/Controller/YwtController.class.php
Appcenter/APP/Controller/MemberController.class.php
Appcenter/V4/Model/AccountModel.class.php
Appcenter/V4/Model/CurrencyAction.class.php

-- WORKMAN操作
-- grb_function.php[+]
//正式版-定时释放锁定资产
function releaseLock() {
	if (date('H')=='01') {
		echo "[releaseLock] Timer run: ".date('Y-m-d H:i:s')."\n";
		curl(GRB_COMMON_URL.'/Ywt/releaseLockTask', 'get');
	}
} 
-- grb_all.php [+]
Timer::add(3600, 'releaseLock');