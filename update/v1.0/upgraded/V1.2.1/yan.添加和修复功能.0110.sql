-- 执行SQL
update zc_auth_rule set `name`=concat(`name`, ',Admin/Member/memberPerformanceInfo,Admin/Member/memberPerformanceDetails') where id in (49,50);

-- 更新文件
Appcenter/Admin/Controller/IndexController.class.php
Appcenter/Admin/Controller/MemberController.class.php
Appcenter/Admin/View/default/Index/index.html
