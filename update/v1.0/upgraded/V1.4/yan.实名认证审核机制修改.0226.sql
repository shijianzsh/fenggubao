-- -------------------------
-- 说明
-- 1、未实名认证用户(未审核和已审核状态记录都不存在)首次下单则将收货地址的省市区记入实名认证表中,并设置审核状态为未审核
-- 2、用户申请实名认证时需添加判断条件三个身份证图片字段是否为空。若为空，则为未实名认证状态，用户上传信息只把图片信息保存至未审核申请记录中
-- 3、后台实名认证列表中隐藏未审核的图片字段为空的申请信息，避免后台误操作
-- -------------------------

-- 更新文件
Appcenter/Admin/Controller/ReviewController.class.php
Appcenter/APP/Controller/MemberController.class.php
Appcenter/APP/Controller/ShopingcartController.class.php
Appcenter/Common/Conf/field_config.php