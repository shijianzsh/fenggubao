-- ******** 配置参数
一、Common/Conf/config.php增加谷聚金板块配置参数：
//谷聚金板块ID
'GJJ_BLOCK_ID' => 7,
二、Common/Conf/config.php
1、删除'NAVIGATION'整个数组块
2、'LOAD_EXT_CONFIG' => 'data,parameter,imagesize,field_config,gjj_field_config,navigation_config',

-- ******** 执行SQL
-- 添加后台合伙人审核功能相关权限
insert into zc_auth_rule values
	(null, 'Admin/Partner/region,Admin/Partner/province,Admin/Partner/country,Admin/Partner/province/type/0,Admin/Partner/province/type/1,Admin/Partner/country/type/0,Admin/Partner/country/type/1', '谷聚金代理审核查看', 1, 1, ''),
	(null, 'Admin/Partner/region,Admin/Partner/getUserInfo,Admin/Partner/openRegion,Admin/Partner/closeRegion,Admin/Partner/activateRegion,Admin/Partner/province,Admin/Partner/country,Admin/Partner/province/type/0,Admin/Partner/province/type/1,Admin/Partner/country/type/0,Admin/Partner/country/type/1,Admin/Partner/review', '谷聚金代理审核管理', 1, 1, '');
-- 添加后台大中华区区域管理功能相关权限

-- 添加后台商户管理中谷聚金订单管理功能相关权限
update zc_auth_rule set `name`=concat(`name`, ',Merchant/Order/gjj') where id=53;
-- 添加后台谷聚金专区奖项管理功能相关权限
insert into zc_auth_rule values
	(null, 'System/Config/gjj', '谷聚金专区奖项管理', 1, 1, '');

-- 添加商品板块管理操作权限
update zc_auth_rule set `name`=concat(`name`, ',Shop/Goods/block_enabled,Shop/Goods/block_modifysort') where id=85;

-- 用户银行卡绑定表增加开户行地址字段
alter table zc_bank_bind add `bankAddress` varchar(255) default '' comment '开户行地址' after `name`;

-- ******** 更新文件
Appcenter/APP/Common/function.php
Appcenter/APP/Controller/GjjController.class.php
Appcenter/APP/Controller/MemberController.class.php
Appcenter/APP/Controller/ShopingcartController.class.php
Appcenter/APP/Controller/OrderController.class.php
Appcenter/APP/Controller/IndexController.class.php
Appcenter/APP/Controller/ProductController.class.php
Appcenter/APP/Controller/NotifyController.class.php
Appcenter/APP/Controller/MiningController.class.php
Appcenter/APP/View/default/Product/showDetail.html

Appcenter/Common/Conf/navigation_config.php [+]
Appcenter/Common/Conf/field_config.php
Appcenter/Common/Conf/gjj_field_config.php
Appcenter/Common/Common/function.php
Appcenter/Common/Controller/ApiController.class.php

Appcenter/V4/Model/Currency.class.php
Appcenter/V4/Model/CurrencyAction.class.php
Appcenter/V4/Model/GjjModel.class.php
Appcenter/V4/Model/OrderModel.class.php
Appcenter/V4/Model/AccountModel.class.php
Appcenter/V4/Model/GoldcoinPricesModel.class.php

Appcenter/Admin/Controller/IndexController.class.php
Appcenter/Admin/Controller/AjaxController.class.php
Appcenter/Admin/Controller/FinanceController.class.php
Appcenter/Admin/Controller/MemberController.class.php
Appcenter/Admin/Controller/ThirdController.class.php
Appcenter/Admin/Controller/ReviewController.class.php
Appcenter/Admin/Controller/PartnerController.class.php [+]
Appcenter/Admin/Model/ManagerModel.class.php
Appcenter/Admin/View/default/Index/index.html
Appcenter/Admin/View/default/Finance/memberCash.html [此文件更新前需先将服务器正式版对应文件还原后再更新]
Appcenter/Admin/View/default/Finance/transfer.html
Appcenter/Admin/View/default/Finance/trade.html
Appcenter/Admin/View/default/Finance/trade22.html
Appcenter/Admin/View/default/Finance/transfer.html
Appcenter/Admin/View/default/Member/memberList.html
Appcenter/Admin/View/default/Member/memberBonusInfo.html
Appcenter/Admin/View/default/Third/transfer.html
Appcenter/Admin/View/default/Partner [+]
Appcenter/Admin/View/default/Ajax/getRegionsCountry.html [+]
Appcenter/Admin/View/default/Ajax/getRegionsProvince.html [+]
Appcenter/Admin/View/default/Ajax/openRegion.html [+]
Appcenter/Admin/View/default/Ajax/setBlockSort.html [+]

Appcenter/System/Controller/GjjController.class.php [+]
Appcenter/System/Controller/ConfigController.class.php
Appcenter/System/Controller/SeniorSearchController.class.php
Appcenter/System/View/default/Gjj [+]
Appcenter/System/View/default/News/newsList.html
Appcenter/System/View/default/SeniorSearch [+]

Appcenter/Merchant/Controller/OrderController.class.php
Appcenter/Merchant/Controller/GoodsController.class.php
Appcenter/Merchant/View/default/Goods/goodsModify.html
Appcenter/Merchant/View/default/Goods/goodsAddUi.html
Appcenter/Merchant/View/default/Order/gjj.html [+]

Appcenter/Shop/Controller/GoodsController.class.php
Appcenter/Shop/View/default/Goods/block.html
Appcenter/Shop/View/default/Goods/blockModify.html
