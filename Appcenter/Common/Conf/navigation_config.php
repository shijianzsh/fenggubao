<?php 
return array(
		
	//后台各模块左侧栏目导航
	'NAVIGATION' => array(
		'Admin' => array(
			array(
				'url' => '',
				'title' => '团队管理',
				'son' => array(
					array('url' => '/Member/memberList', 'title' => '个人代理'),
					array('url' => '/Member/memberListNot', 'title' => '体验用户'),
					array('url' => '/Member/tree', 'title' => '推荐关系'),
//					array('url'=>'/Member/performance', 'title'=>'业绩查询'),
				)
			),
			array(
				'url' => '',
				'title' => '审核管理',
				'son' => array(
//					array( 'url' => '/Review/partnerReview/type/0', 'title' => '未审合伙人' ),
//					array( 'url' => '/Review/partnerReview/type/1', 'title' => '已审合伙人' ),
					array('url' => '/Review/serviceReview/type/0', 'title' => '未审区域合伙人'),
					array('url' => '/Review/serviceReview/type/1', 'title' => '已审区域合伙人'),
					array( 'url' => '/Review/agentReview/type/0', 'title' => '未审省级合伙人' ),
					array( 'url' => '/Review/agentReview/type/1', 'title' => '已审省级合伙人' ),
//			        array('url'=>'/Review/vipReview/type/0', 'title'=>'未审金卡代理'),
//			        array('url'=>'/Review/vipReview/type/1', 'title'=>'已审金卡代理'),
//					array('url'=>'/Review/honourVipReview/type/0', 'title'=>'未审钻卡代理'),
//					array('url'=>'/Review/honourVipReview/type/1', 'title'=>'已审钻卡代理'),
					array('url' => '/Review/identiyReview/type/0', 'title' => '未审身份信息'),
					array('url' => '/Review/identiyReview/type/1', 'title' => '已审身份信息'),
				)
			),
//			array(
//				'url' => '',
//				'title' => '谷聚金审核',
//				'son' => array(
//					array('url'=>'/Partner/region', 'title'=>'大中华区合伙人'),
//					array('url'=>'/Partner/province/type/0', 'title'=>'未审省营运中心'),
//					array('url'=>'/Partner/province/type/1', 'title'=>'已审省营运中心'),
//					array('url'=>'/Partner/country/type/0', 'title'=>'未审区县代理'),
//					array('url'=>'/Partner/country/type/1', 'title'=>'已审区县代理'),
//				)
//			),
			array(
				'url' => '',
				'title' => '财务管理',
				'son' => array(
//					array( 'url' => '/Finance/ratio', 'title' => '拨出查询' ),
//                  array('url' => '/Finance/bonus', 'title' => '奖金记录'),
					array('url' => '/Finance/withdraw', 'title' => '提现管理'),
					array('url' => '/Finance/memberCash', 'title' => '后台充值'),
					//array('url' => '/Performance/rewardTask', 'title' => '业绩结算'),
					//array('url' => '/Performance/rewardRecord', 'title' => '区域合伙人业务补贴'),
					//array('url'=>'/Finance/exchange', 'title'=>'货币转换记录'),
					array('url' => '/Finance/transfer', 'title' => '会员转账记录'),
					//array('url' => '/Finance/recharge/type/WX', 'title' => '微信充值记录'),
					//array('url' => '/Finance/recharge/type/ALI', 'title' => '支付宝充值记录'),
// 					array('url' => '/Finance/trade/type/WX', 'title' => '微信支付宝兑换记录'),
                    array('url' => '/Finance/trade22/type/1', 'title' => '现金币兑丰谷宝'),
					array('url'=>'/Third/transfer', 'title'=>'第三方互转记录'),
//					array('url'=>'/BonusBack/index', 'title'=>'回购管理'),
					//array('url' => '/Fees/personProfits', 'title' => '个人所得税管理'),
					//array('url' => '/Fees/systemManage', 'title' => '平台管理费管理'),
					array('url'=>'/GrbTransaction/index', 'title'=>'商城转中网云审核'),
//					array('url'=>'/GrbTransaction/index/wallet_type/AJS', 'title'=>'GRB转出澳交所审核管理'),
//					array('url'=>'/GrbTransaction/index/wallet_type/SLU', 'title'=>'GRB转出Silk Trader审核管理'),
					array('url' => '/Performance/bonusTask', 'title' => '加权分红管理'),
					array('url' => '/GrbTransaction/view', 'title' => '丰谷宝流通兑换查看'),
//					array('url' => '/AjsTransaction/index', 'title' => '澳交所转入GRB申请管理'),
					array('url' => '/ZwyTransaction/index', 'title' => '中网云转商城申请'),
//					array('url' => '/SluTransaction/index', 'title' => 'Silk Trader转入GRB申请管理'),
				)
			),
		),
		'Shop' => array(
			array(
				'url' => '',
				'title' => '店铺管理',
				'son' => array(
					array('url' => '/Store/storeList/type/0', 'title' => '未审店铺'),
					array('url' => '/Store/storeList/type/1', 'title' => '已审店铺'),
					array('url' => '/Store/storeList/type/2', 'title' => '被驳店铺'),
					array('url' => '/Store/storeList/type/3', 'title' => '申请注销店铺'),
					array('url' => '/Store/storeList/type/4', 'title' => '已注销店铺'),
					/*
					 array('url'=>'/Store/storePreferentialWayList/type/0', 'title'=>'未审活动'),
					 array('url'=>'/Store/storePreferentialWayList/type/1', 'title'=>'已审活动'),
					 array('url'=>'/Store/storePreferentialWayList/type/2', 'title'=>'被驳活动'),
					 */
				)
			),
			array(
				'url' => '',
				'title' => '商品管理',
				'son' => array(
                 	array('url' => '/Goods/category', 'title' => '商品分类'),
					array('url' => '/Goods/block', 'title' => '商品板块'),
					array('url' => '/Goods/goodsList/type/0', 'title' => '未审商品'),
					array('url' => '/Goods/goodsList/type/1', 'title' => '已审商品'),
					array('url' => '/Goods/goodsList/type/2', 'title' => '被驳商品'),
				)
			),
		),
		'Merchant' => array(
			array(
				'url' => '',
				'title' => '商户中心',
				'son' => array(
//                  array('url' => '/Index/storeDetail', 'title' => '店铺信息'),
//                  array('url' => '/Account/detail', 'title' => '账户管理'),
					array('url' => '/Order/index', 'title' => '订单管理'),
					array('url' => '/Order/gjj', 'title' => '谷聚金订单管理'),
					array('url' => '/Goods/index', 'title' => '商品管理'),
					array('url' => '/Goods/goodsAddUi', 'title' => '发布商品'),
					//array('url'=>'/Activity/index', 'title'=>'活动管理'),
//					array('url'=>'/Shake/index', 'title'=>'摇 一 摇'),
//					array('url'=>'/Fans/index', 'title'=>'我的粉丝'),
				)
			),
		),
		'System' => array(
			array(
				'url' => '',
				'title' => '系统设置',
				'son' => array(
					array('url' => '/Purview/ruleManage', 'title' => '规则管理'),
					array('url' => '/Purview/groupManage', 'title' => '角色管理'),
					array('url' => '/Manager/index', 'title' => '用户管理'),
//					array( 'url' => '/Parameter/index', 'title' => '配置管理' ),
					array('url' => '/Config/index', 'title' => '奖项管理'),
					array('url' => '/Config/gjj', 'title' => '谷聚金专区奖项管理'),
					array('url' => '/Config/mining', 'title' => '挖矿奖项管理'),
//					array( 'url' => '/Config/special', 'title' => '特殊分红管理' ),
					array('url' => '/Performance/rule', 'title' => '个代晋升规则'),
					array('url' => '/Consume/rule', 'title' => '消费等级规则'),
					array('url' => '/Log/logList', 'title' => '后台记录管理'),
//					array( 'url' => '/Backup/index', 'title' => '数据库管理' ),
					array('url' => '/Version/index', 'title' => 'APP版本管理'),
					array('url' => '/Parameter/mustRead', 'title' => 'APP首页弹窗管理'),
//					array( 'url' => '/Queue/index', 'title' => '执行队列管理' ),
//                  array('url' => '/Task/index', 'title' => '定时任务管理'),
// 					array('url' => '/BackupWallet/index', 'title' => '备份钱包管理'),
					array('url' => '/Gjj/regions', 'title' => '大中华区区域管理'),
				)
			),
			array(
				'url' => '',
				'title' => '平台管理',
				'son' => array(
					array('url' => '/Index/advList', 'title' => '首页轮播广告'),
//                  array('url' => '/Index/advList/type/1', 'title' => '商城轮播广告'),
					array('url' => '/Index/advList/type/2', 'title' => '资讯轮播广告'),
					array('url' => '/Index/agreementDetail', 'title' => '协议管理'),
					array('url' => '/News/newsList', 'title' => '快讯管理'),
					array('url' => '/Zixun/index', 'title' => '新闻资讯管理'),
                    array('url' => '/SeniorSearch/index', 'title' => '高级查询'),
//					array('url'=>'/Shake/shakelogs', 'title'=>'摇一摇管理'),
//                  array('url' => '/Feedback/index', 'title' => '意见反馈管理'),
					//array('url' => '/Checkin/index', 'title' => '签到管理'),
					//array('url' => '/Ad/index', 'title' => '广告管理'),
					//array('url' => '/Index/customerService', 'title' => '客服平台管理'),
					array('url' => '/GoldcoinPrice/index', 'title' => '丰谷宝实时价格管理'),
				)
			),
			array('url' => '/Bonus/siteStatus', 'title' => '系统维护'),
//			array(
// 				'url'=>'', 
// 				'title'=>'系统维护', 
// 				'son'=>array(
//					array('url'=>'/Bonus/bonusIndex', 'title'=>'今日分红'),
//					array('url'=>'/Bonus/bonusList', 'title'=>'每日分红'),
//					array('url'=>'/Bonus/siteStatus', 'title'=>'系统维护'),
//				)
//			),
		),
	),
		
);
?>