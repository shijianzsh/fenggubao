-- ** 执行SQL
-- 创建用户银行卡绑定表
DROP TABLE IF EXISTS `zc_bank_bind`;
CREATE TABLE `zc_bank_bind` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `phone` INT(11) UNSIGNED NULL DEFAULT 0 COMMENT '手机号',
  `cardNo` varchar(50) NULL DEFAULT 0 COMMENT '银行卡号',
  `bankName` varchar(50) NOT NULL COMMENT '银行名称',
  `name` varchar(50) NOT NULL COMMENT '开户名',
  `created_time` INT(11) UNSIGNED NULL DEFAULT 0 COMMENT '加入时间',
  `updated_time` INT(11) UNSIGNED NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY (`user_id`),
  KEY (`created_time`)
) ENGINE = InnoDB AUTO_INCREMENT = 1 COMMENT ='银行卡绑定表';

-- ** Common/Conf/field_config.php
[orders]: 'amount_type' => array('1' => '现金币', '2' => '公让宝', '3' => '彩分', '4' => '支付宝', '5' => '微信', '7' => '银行卡'),

-- ** 更新文件
Appcenter/APP/Controller/MemberController.class.php
Appcenter/APP/Controller/ShopingcartController.class.php
Appcenter/Common/Common/function.php
Appcenter/Common/Conf/field_config.php
Appcenter/V4/Model/CurrencyAction.class.php
Appcenter/V4/Model/OrderModel.class.php
Appcenter/V4/Model/PaymentMethod.class.php
ThinkPHP/Library/Vendor/ZhongWY/ZhongWY.Api.php
Appcenter/Admin/Controller/FinanceController.class.php
Appcenter/Admin/View/default/Finance/trade.html
