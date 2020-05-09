<?php
return array(
		
	//华佗商城接口地址
	'HT_API' => [
		'host' => 'http://huatuoshop.com/',
		'recevied' => 'Account/recevied',
		'SECRET_KEY' => '18578FBF960F50ABFD626237D2CCB641',
	],
		
	//报单需满足不能超过的有效农场数
	'BAODAN_FARM_ENABLED_NUMBER' => 50,
		
	//澳交所实时价格是否只获取收盘前价格
	'AJS_PRICE_GET_NOT_CLOSED' => true,
	'AJS_PRICE_MIN' => 1, //澳交所实时单价保底价格
	
	//SLU实时价格相关配置
	'SLU_PRICE_MIN' => 1, //SLU实时单价保底价格
	'SLU_IMPORT_AUTO_ADD_QUEUE' => true, //SLU转入数据是否自动加入队列
		
	//丰收页面动态图片地址
	'MINING_DYNAMIC_IMG' => 'http://gongrangbao.oss-cn-shenzhen.aliyuncs.com/Uploads/mining/mine_05051650.jpg',

	//第三方钱包信息
	'THIRD_WALLET' => array(
		'name' => '企信贵交', //'澳交所',
		'icon' =>  'http://gongrangbao.oss-cn-shenzhen.aliyuncs.com/Uploads/third_wallet/logo-xinqgj.png', // 'http://gongrangbao.oss-cn-shenzhen.aliyuncs.com/Uploads/third_wallet/icon-aogex.png', //'http://gongrangbao.oss-cn-shenzhen.aliyuncs.com/Uploads/third_wallet/icon-digital.png',
		'url' =>  'https://www.xinqgj.com/downapp', // 'https://copy.im/a/S2PjZd', //'http://zh.indiancoean.com',
	),
		
	//微信支付宝等支付方式是否强行切换至华佗
	'PAY_METHOD_MUST_HT' => true,
		
	//第三方系统币种转出地址
	'ZCGYURL' => 'http://zhixiao5.cnhv5.hostpod.cn/Otcms/Api/Ta', //测试
	//'ZCGYURL' => 'http://www.xlsfxjj66.com/Otcms/Api/Ta',
	'ZCGY_IP' => '192.151.231.14',
		
	//@override 异常配置 
	'SHOW_ERROR_MSG' => true,
	'ERROR_MESSAGE' => 'Found Error!',
	'TMPL_EXCEPTION_FILE' => APP_PATH.'/Public/exception_api.html',
		
	//默认控制器
	'DEFAULT_CONTROLLER' => 'Index',
		
    //APP后台交互通用配置
    'APP_CONFIG' => array(
        'SECRET_KEY' => '8f8683f36c70815819137af3bce93225',
    ),
	
	//显示指定店铺的商品,店铺id
	'SHOW_PRODUCTS_STOREID' => '',
	
	//IM配置
	'IM_CONFIG' => array(
		'org_name' => 'xxx',
	    'app_name' => 'xxx',
		'client_id' => 'xxx',
		'client_secret' => 'xxx',
	),
		
    //公共信息:设备类型platform,时间戳time,api_token,sessionid(终端唯一标识码),version(版本号),uid(用户ID)
    'APP_COMMON_DATA' => array('platform', 'time', 'api_token', 'sessionid', 'version', 'uid', 'registration_id', 'sign'),
		
	//公共头时间戳超时限制(秒)
	'APP_COMMON_DATA_TIME_MAX' => '60',
		
	//是否启用api_token权限验证
	'API_TOKEN' => false,
		
	//是否启用单点登录验证功能
	'APP_SINGLE_LOGIN' => false,
		
	//短信验证码有效时长(秒)
	'SMS_ENABLE_TIME' => '120',
	
	//公益的URL地址
	'CHARIT_URL' => 'http://192.151.231.14',
		
    //无需UID权限验证配置
    'API_NO_PURVIEWCHECK' => array(
    	'/Index/',
    	'/LoginRegister/',
    	//'/Paypass/',
    	'/Phone/',
    	'/Notify/',
    	'/WxNotify/',
    	'/Search/',
    	'/Sys/',
    	'/Member/get_nickname',
    	'/Member/loginout',
    	'/Pay/wc_deposit_weixin',
    	'/Pay/wc_deposit_redirect',
    	'/Pay/wc_deposit_queue',
		'/Safe/',
		'/Withdraw/',
		'/Store/getaggrements',
    	'/MultiLanguage/',
    	'/ZhongWY/',
    	'/Slu/',
    ),
		
	/**
	 * v1 config
	 */
	'ALIPAY_CONFIG' => array(
		'partner' => '2088421708109484',
		'private_key' => file_get_contents(APP_PATH.'APP/Controller/key/rsa_private_key.pem'),
		'alipay_public_key' => file_get_contents(APP_PATH.'APP/Controller/key/alipay_public_key.pem'),
		'sign_type' => strtoupper('RSA'),
		'input_charset' => strtolower('utf-8'),
		'cacert' => getcwd() . '\\Pay\\cacert.pem',
		'transport' => 'http',
	),
	
	'ALIPAY_CONFIG2' => array(
		'partner' => '2088421708109484',
		'alipay_public_key' => file_get_contents(APP_PATH.'APP/Controller/key/partner_public_key.pem'),
		'sign_type' => strtoupper('RSA'),
		'input_charset' => strtolower('utf-8'),
		'cacert' => getcwd() . '\\Pay\\cacert.pem',
		'transport' => 'http',
	),
	
	//配置模板
	'DEFAULT_THEME' => 'default',
	'LAYOUT_ON' => true,
	'LAYOUT_NAME' => '../../../Public/apitest',
		
	//数据缓存配置
	'DATA_CACHE_ON' => false, //数据缓存开关
	'DATA_CACHE_TYPE' => 'Memcache',
	'MEMCACHE_HOST' => '127.0.0.1',
	'MEMCACHE_PORT' => '11211',
	'DATA_CACHE_PREFIX' => 'zcsh123',
	'DATA_CACHE_TIMEOUT' => 20,
	'DATA_CACHE_TIME' => 20,
	//自动启用数据缓存的接口列表
	'DATA_CACHE_AUTO_PATH' => array(
		'/Index/',
		'/Address/addressList',            //收货地址列表
		'/Hack2/',                          //现金、公让宝、分红等查询
		'/Hack/recommand_hacker',          //推荐用户列表
		'/Hack/total_cash_details',        //现金+公让宝（通证汇总）明细
		'/Hack/total_details',             //
		'/Member/memberInfo',              //个人中心
		'/Member/order_list',              //个人中心-订单
		'/Member/exchange_details',        //个人中心-订单详情
		'/Member/commentList',             //个人中心-评论列表
		'/Member/myShoppingList',          //个人中心-买单列表
		'/Member/systemMessage',           //系统消息
		'/Member/favorite_listS',          //个人中心-收藏列表
		'/Member/favorite_listP',          //个人中心-收藏列表
		'/Search/',                         //搜索
		'/Shake/index',                    //摇一摇界面
		'/Shake/shakelist',                //摇一摇发布记录
		'/Shake/shakewin',                 //摇一摇中奖列表
		'/Shop/myShop',                    //我的店铺
		'/Shop/myFavoriteS',               //店铺粉丝
		'/Shop/myFavoriteP',               //商品粉丝
		'/Shop/deliver_list',              //发货订单列表
		'/Shop/exchange_list',             //兑换订单列表
		'/Shop/exchange_details',          //兑换商品详情
		'/Shop/product_list',              //我的商品
		'/Shop/productDetails',            //查看商品详情
		'/Shop/product_record',            //发布商品记录
		'/Shop/post.storeid',              //店铺评论列表
		'/Shop/myStoreSet',                //店铺设置页面
		'/Store/order_maidan',             //商家-众彩买单
		'/Store/order_maidaninfo',         //买单详情
	),
	
	//提现配置
    'WITHDRAW_BETWEEN' => 60,
    'WITHDRAW_TIMES' => 5,
		
);