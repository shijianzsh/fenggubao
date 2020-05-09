-- 执行SQL
alter table zc_bank_bind add `bankAddress` varchar(255) default '' comment '开户行地址' after `name`;

-- 更新文件
Appcenter/Common/Conf/navigation_config.php
Appcenter/APP/Common/function.php
Appcenter/APP/Controller/MemberController.class.php
Appcenter/Admin/Controller/FinanceController.class.php