-- 执行SQL
-- 创建提现配置组
insert into zc_settings_group values 
	(9, '提现配置', 1, '100', unix_timestamp());
-- 创建提现配置参数
insert into zc_settings values
	(null, 9, '提现最小金额', 'withdraw_amount_min', '100', 'text', '元', 1, 951, unix_timestamp()),
	(null, 9, '提现最大金额', 'withdraw_amount_max', '10000', 'text', '元', 1, 950, unix_timestamp()),
	(null, 9, '提现金额倍数', 'withdraw_amount_bei', '1', 'text', '倍', 1, 949, unix_timestamp()),
	(null, 9, '提现手续费', 'withdraw_fee', '0', 'text', '%(百分比)', 1, 948, unix_timestamp()),
	(null, 9, '提现每日累计限额', 'withdraw_day_amount_max', '10000', 'text', '元', 1, 947, unix_timestamp()),
	(null, 9, '提现每日次数限制', 'withdraw_day_number_max', '1', 'text', '次', 1, 946, unix_timestamp()),
	
	(null, 9, '每周可提现天数', 'withdraw_week_enabled_day', '1,2,3,4,5', 'text', '(0:周日,1:周一,2:周二,3:周三,4:周四,5:周五,6:周六)', 1, 945, unix_timestamp()),
	(null, 9, '每天可提现开始时间', 'withdraw_day_enabled_hour_start', '10', 'text', '点(24小时制)', 1, 944, unix_timestamp()),
	(null, 9, '每天可提现结束时间', 'withdraw_day_enabled_hour_end', '17', 'text', '点(24小时制)', 1, 943, unix_timestamp()),
	
	(null, 9, '提现开关 - 银行卡', 'withdraw_switch_bank', '开启', 'options', '开启,关闭', 1, 942, unix_timestamp()),
	(null, 9, '提现开关 - 微信', 'withdraw_switch_wechat', '开启', 'options', '开启,关闭', 1, 941, unix_timestamp()),
	(null, 9, '提现开关 - 支付宝', 'withdraw_switch_alipay', '开启', 'options', '开启,关闭', 1, 940, unix_timestamp()),
	(null, 9, '提现说明', 'withdraw_description', '这周提现下周到账', 'textarea', '', 1, 939, unix_timestamp()),
	(null, 9, '提现规则', 'withdraw_rule', '提现最小金额{%withdraw_amount_min%}元,提现金额需为{%withdraw_amount_min%}的{%withdraw_amount_bei%}倍，提现最大金额{%withdraw_amount_max%}元,提现手续费{%withdraw_fee%}%,每日累计限额{%withdraw_day_amount_max%}元,每日次数限制{%withdraw_day_number_max%}次,每周的周{%withdraw_week_enabled_day%}的{%withdraw_day_enabled_hour_start%}点到{%withdraw_day_enabled_hour_end%}点可以提现,当前账户余额{%cash%}元', 'textarea', '', 1, 938, unix_timestamp());

-- 板块管理增加是否只允许会员购买和低于指定金额增加运费
alter table zc_block
	add `block_only_member` tinyint(1) default '0' comment '是否只允许会员购买(0:否,1:是)',
	add `block_freight_order_amount` decimal(14,4) default '0' comment '订单不满此金额则自动增加运费',
	add `block_freight_increase_amount` decimal(14,4) default '0' comment '订单不满指定金额则自动增加运费的金额';
	
-- 创建充值配置组
insert into zc_settings_group values 
	(10, '充值配置', 1, '99', unix_timestamp());
-- 创建充值配置参数
insert into zc_settings values
	(null, 10, '充值说明', 'recharge_description', '充值说明', 'textarea', '', 1, 1050, unix_timestamp());
	
-- 增加系统维护管理规则
insert into zc_auth_rule values
	(null, 'System/Bonus/siteStatus,System/Bonus/siteStatusSave', '系统维护', 1, 1, '');
	
-- 完善奖项配置管理权限规则
update zc_auth_rule set `name` = concat(`name`, ',System/Config/special,System/Config/specialSave,System/Config/parameterSave,System/Config/addSettingsGroup,System/Config/addSettings,System/Config/saveSettings,System/Config/saveSettingsGroup') where id=46;

-- 更新文件
Appcenter/APP/Common/function.php
Appcenter/APP/Controller/CoinController.class.php
Appcenter/APP/Controller/IndexController.class.php
Appcenter/APP/Controller/MemberController.class.php [此文件更新前需先将服务器正式版对应文件还原后再更新]
Appcenter/APP/Controller/ShopingcartController.class.php

Appcenter/Common/Conf/field_config.php
Appcenter/Common/Conf/navigation_config.php

Appcenter/Shop/Controller/GoodsController.class.php
Appcenter/Shop/View/default/Goods/block.html
Appcenter/Shop/View/default/Goods/blockAdd.html
Appcenter/Shop/View/default/Goods/blockModify.html

Appcenter/V4/Model/OrderModel.class.php

Appcenter/Merchant/Controller/GoodsController.class.php
Appcenter/Merchant/View/default/Goods/goodsAddUi.html
Appcenter/Merchant/View/default/Goods/goodsModify.html
