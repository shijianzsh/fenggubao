-- 执行SQL
update zc_member set role=0 where id in (select uid from zc_apply_service_center where status=2);

-- 更新文件
Appcenter/Admin/Controller/ReviewController.class.php
