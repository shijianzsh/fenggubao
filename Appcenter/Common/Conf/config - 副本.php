<?php
return array(

    //财务版管理员账户和主题
    'FINANCE_MANAGER' => [],
    'FINANCE_THEME' => 'finance',

    //TDDL(主要用于阿里DRDS主从库时存储过程等调用定向主数据库)
    'ALIYUN_TDDL_MASTER' => '', //'/!TDDL:MASTER*/',

    'APP_TITLE' => '公让宝',

    //非绑定模块通用域名
    'LOCAL_HOST' => 'http://grb.58dzt.com/',

    //当前服务器mysql/bin绝对路径
    'MYSQL_BIN_PATH' => 'C:/wamp/bin/mysql/mysql5.6.17/bin/',

    //后台超管专用登陆地址参数
    'ADMINISTRATOR_VAR' => '', //'29D58384-FF827703-8C059D99-EE3DDC59',

    //Auth session配置
    'AUTH_SESSION' => 'zcshAuthSession',

    //Auth cookie配置
    'AUTH_COOKIE' => 'zcshAuthCookie',

    //COOKIE配置
    'COOKIE_EXPIRE' => 3600 * 24 * 7,
    //'COOKIE_PREFIX' => '',
    //'COOKIE_PATH' => '',
    //'COOKIE_DOMAIN' => '',
    //'COOKIE_HTTPONLY' => '',

    //SESSION配置
    'SESSION_OPTIONS' => array(
        'expire' => 3600 * 24,
        //'path' => '',
        //'domain' => '',
        //'prefix' => '',
    ),

    //限制区域
    'FILTER_REGADDR' => array(/*
        '四川省' => array('眉山市'),
        '湖南省' => array(),
        '湖北省' => array('黄冈市'),
        '河南省' => array('焦作市','平顶山市'),
        */
    ),

    //Auth权限认证配置
    'AUTH_CONFIG' => array(
        'AUTH_ON' => true,                      // 认证开关
        'AUTH_TYPE' => 1,                         // 认证方式，1为实时认证；2为登录认证。
        'AUTH_GROUP' => 'zc_auth_group',        // 用户组数据表名
        'AUTH_GROUP_ACCESS' => 'zc_auth_group_access', // 用户-用户组关系表
        'AUTH_RULE' => 'zc_auth_rule',         // 权限规则表
        'AUTH_USER' => 'zc_manager'
    ),

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
            array(
                'url' => '',
                'title' => '财务管理',
                'son' => array(
//					array( 'url' => '/Finance/ratio', 'title' => '拨出查询' ),
//                    array('url' => '/Finance/bonus', 'title' => '奖金记录'),
                    //array('url' => '/Finance/withdraw', 'title' => '提现管理'),
                    array('url' => '/Finance/memberCash', 'title' => '后台充值'),
                    //array('url' => '/Performance/rewardTask', 'title' => '业绩结算'),
                    //array('url' => '/Performance/rewardRecord', 'title' => '区域合伙人业务补贴'),
                    //array('url'=>'/Finance/exchange', 'title'=>'货币转换记录'),
                    array('url' => '/Finance/transfer', 'title' => '会员转账记录'),
                    //array('url' => '/Finance/recharge/type/WX', 'title' => '微信充值记录'),
                    //array('url' => '/Finance/recharge/type/ALI', 'title' => '支付宝充值记录'),
                    array('url' => '/Finance/trade/type/WX', 'title' => '微信支付宝交易记录'),
                    //array('url' => '/Finance/trade22/type/1', 'title' => '现金币公让宝交易记录'),
//					array('url'=>'/Third/transfer', 'title'=>'第三方互转记录'),
//					array('url'=>'/BonusBack/index', 'title'=>'回购管理'),
                    //array('url' => '/Fees/personProfits', 'title' => '个人所得税管理'),
                    //array('url' => '/Fees/systemManage', 'title' => '平台管理费管理'),
                )
            ),
        ),
        'Shop' => array(
//            array(
//                'url' => '',
//                'title' => '店铺管理',
//                'son' => array(
//                    array('url' => '/Store/storeList/type/0', 'title' => '未审店铺'),
//                    array('url' => '/Store/storeList/type/1', 'title' => '已审店铺'),
//                    array('url' => '/Store/storeList/type/2', 'title' => '被驳店铺'),
//                    array('url' => '/Store/storeList/type/3', 'title' => '申请注销店铺'),
//                    array('url' => '/Store/storeList/type/4', 'title' => '已注销店铺'),
//                    /*
//                    array('url'=>'/Store/storePreferentialWayList/type/0', 'title'=>'未审活动'),
//                    array('url'=>'/Store/storePreferentialWayList/type/1', 'title'=>'已审活动'),
//                    array('url'=>'/Store/storePreferentialWayList/type/2', 'title'=>'被驳活动'),
//                    */
//                )
//            ),
            array(
                'url' => '',
                'title' => '商品管理',
                'son' => array(
//                    array('url' => '/Goods/category', 'title' => '商品分类'),
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
//                    array('url' => '/Index/storeDetail', 'title' => '店铺信息'),
//                    array('url' => '/Account/detail', 'title' => '账户管理'),
                    array('url' => '/Order/index', 'title' => '订单管理'),
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
//					array( 'url' => '/Config/special', 'title' => '特殊分红管理' ),
                    array('url' => '/Performance/rule', 'title' => '个代晋升规则'),
                	array('url' => '/Consume/rule', 'title' => '消费等级规则'),
                    array('url' => '/Log/logList', 'title' => '后台记录管理'),
//					array( 'url' => '/Backup/index', 'title' => '数据库管理' ),
                    array('url' => '/Version/index', 'title' => 'APP版本管理'),
                    array('url' => '/Parameter/mustRead', 'title' => 'APP过渡页管理'),
//					array( 'url' => '/Queue/index', 'title' => '执行队列管理' ),
//                    array('url' => '/Task/index', 'title' => '定时任务管理'),
                )
            ),
            array(
                'url' => '',
                'title' => '平台管理',
                'son' => array(
                    array('url' => '/Index/advList', 'title' => '首页轮播广告'),
//                    array('url' => '/Index/advList/type/1', 'title' => '商城轮播广告'),
                    array('url' => '/Index/agreementDetail', 'title' => '协议管理'),
 //                   array('url' => '/News/newsList', 'title' => '快讯管理'),
                		array('url' => '/Zixun/index', 'title' => '新闻资讯管理'),
//					array('url'=>'/Shake/shakelogs', 'title'=>'摇一摇管理'),
//                    array('url' => '/Feedback/index', 'title' => '意见反馈管理'),
                    //array('url' => '/Checkin/index', 'title' => '签到管理'),
                    //array('url' => '/Ad/index', 'title' => '广告管理'),
                    //array('url' => '/Index/customerService', 'title' => '客服平台管理'),
                )
            ),
            array('url' => '/Bonus/siteStatus/sys', 'title' => '系统维护'),

//			array('url'=>'', 'title'=>'系统维护', 'son'=>array(
////					array('url'=>'/Bonus/bonusIndex', 'title'=>'今日分红'),
////					array('url'=>'/Bonus/bonusList', 'title'=>'每日分红'),
//					array('url'=>'/Bonus/siteStatus', 'title'=>'系统维护'),
//				)
//			),

        ),
    ),

    //APP版本
    'APP_VERSION' => '1',

    //URL模式配置
    'URL_MODEL' => 2,
    //'URL_PATHINFO_DEPR' => '-',
    'URL_CASE_INSENSITIVE' => false, //不区分URL大小写

    //模块配置
    //'MULTI_MODULE' => false,
    'DEFAULT_MODULE' => 'Admin',
    //以下三项修改会修改对应全控制器或模型或视图,慎用
    //'DEFAULT_C_LAYER' => 'Controller',
    //'DEFAULT_V_LAYER' => 'View',
    //'DEFAULT_M_LAYER' => 'Model',
    //模块部署
    //'MODULE_ALLOW_LIST' => array(), //允许模块
    //'MODULE_DENY_LIST' => array('Admin'), //禁止模块
    //'URL_MODULE_MAP' => array('test'=>'admin'), //模块映射

    //设置伪静态(默认html,可设置多个)
    //'URL_HTML_SUFFIX' => 'html|shtml|xml',

    //路由配置
    'URL_ROUTER_ON' => true,
    'URL_ROUTE_RULES' => array(
        //'my' => 'Member/index',
        //'new/top' => 'News/index?type=top',
        //'blog/:id' => 'Blog/read',
        //'new/:year/:month/:day' => 'News/read',
        //':user/:blog_id' => 'Blog/read',
        //'test' => function() {
        //	echo 'just test';
        //},
        //'hello/:name' => function($name) {
        //	echo 'Hello,'.$name;
        //},
        //'new/:name$' => 'News/read',
        //'/^new\/(\d+)$/' => 'News/read?id=:1',
        'wcd/:id' => 'Admin/Login/login?wcd=:1',
    ),

    //设置参数绑定 (URL变量绑定到操作方法作为参数)
    //'URL_PARAMS_BIND' => true,

    //模版配置
    'DEFAULT_THEME' => 'default',
    'LAYOUT_ON' => true,
    'LAYOUT_NAME' => '../../../Public/layout',

    //模板替换过滤
    'TMPL_PARSE_STRING' => array(
        //通用
        '__PUBLIC__' => '/Public/Public',
        '__JS__' => '/Public/Public/js',
        '__CSS__' => '/Public/Public/css',
        '__UPLOAD__' => '/Uploads',
        //后台
        '__PUBLIC_ADMIN__' => '/Public/Admin',
        '__JS_ADMIN__' => '/Public/Admin/js',
        '__CSS_ADMIN__' => '/Public/Admin/css',
        //API
        '__PUBLIC_API__' => '/Public/Api',
        '__JS_API__' => '/Public/Api/js',
        '__CSS_API__' => '/Public/Api/css',
    ),

    //异常配置
    'SHOW_ERROR_MSG' => true,
    'ERROR_MESSAGE' => '发生错误',
    'TMPL_EXCEPTION_FILE' => APP_PATH . '/Public/exception.html', //服务器配置:exception.html,本地配置:exception_debug.html
    'ERROR_PAGE' => 'http://' . $_SERVER['HTTP_HOST'] . '/Error/Index/err/code/404',

    //设置默认跳转操作对应模板文件(也可使用项目内部模板文件.如:Public:error,Public:success')
    //'TMPL_ACTION_ERROR' => THINK_PATH. 'Tpl/dispatch_jump.tpl',
    //'TMPL_ACTION_SUCCESS' => THINK_PATH. 'Tpl/dispatch_jump.tpl',
    'TMPL_ACTION_ERROR' => APP_PATH . 'Public/jump.html',
    'TMPL_ACTION_SUCCESS' => APP_PATH . 'Public/jump.html',

    //设置默认模板文件后缀
    //'TMPL_TEMPLATE_SUFFIX' => '.html',

    //设置默认模板引擎
    /*
    'TMPL_ENGINE_TYPE' => 'Smarty',
    'TMPL_ENGINE_CONFIG' => array(
        'plugins_dir' => './App/Smarty/Plugins',
    ),
    */

    //开启Trace
    //'SHOW_PAGE_TRACE' => true,
    //保存Trace信息到日志中
    //'PAGE_TRACE_SAVE' => true,
    //Trace异常显示配置[为true则trace($info,'错误','ERR')会抛出异常]
    //'TRACE_EXCEPTION' => true,

    //安全过滤
    'DEFAULT_FILTER' => 'htmlspecialchars,trim,addslashes',

    //静态缓存配置
    /*
    'HTML_PATH' => APP_PATH.'/html/',
    'HTML_CACHE_ON' => true,
    'HTML_CACHE_TIME' => 60,
    'HTML_FILE_SUFFIX' => '.html',
    'HTML_CACHE_RULES' => array(
        //'*' => array('{$_SERVER.REQUEST_URI|md5}'),
        '*' => array('{:module}_{:controller}_{:action}_{$_SERVER.REQUEST_URI|md5}'),
    ),
    */

    //关闭字段缓存
    //'DB_FIELDS_CACHE' => false,

    //配置模块视图目录
    //'VIEW_PATH' => './Theme/',
    //启用配置后App/Home/View/User/add.html将变成./Theme/User/add.html
    //如果同时定义了TMPL_PATH和VIEW_PATH参数，则以当前模块的VIEW_PATH参数设置优先

    //文件缓存配置(默认缓存文件名)
    //'DATA_CACHE_KEY' => 'think',

    //配置SQL解析缓存
    /*
    'DB_SQL_BUILD_CACHE' => true,
    'DB_SQL_BUILD_QUEUE' => 'xcache', //可支持xcache和apc方式
    'DB_SQL_BUILD_LENGTH' => 20,
    */

    //数据缓存配置
    //'DATA_CACHE_TYPE' => 'Memcache',

    //Memcache缓存配置
    /*
    'MEMACACHE_CONFIG' => array(
        'type' => 'memcache',
        'host' => '220.166.64.253',
        'port' => '11211',
        'prefix' => 'zcsh',
        'expire' => 60
    ),
    */

    //文件上传配置
    'UPLOAD_PATH' => '/Uploads',

    //TOKEN
    'TOKEN_ON' => true,
    'TOKEN_NAME' => '__hash__',
    'TOKEN_TYPE' => 'md5',
    'TOKEN_RESET' => true,

    //语言包配置
    'LANG_SWITCH_ON' => true,
    'LANG_AUTO_DETECT' => true, //自动侦测语言
    'LANG_LIST' => 'zh-cn', //允许切换的语言列表
    'VAR_LANGUAGE' => '1', //默认语言切换变量

    //数据库配置
    'DB_TYPE' => 'mysql',
    'DB_HOST' => 'rm-wz9ysbe0rvc09yp8i.mysql.rds.aliyuncs.com',
    'DB_NAME' => 'grb',
    'DB_USER' => 'grb_root',
    'DB_PWD' => 'Grb88888',
    'DB_PORT' => '3306',
    'DB_PREFIX' => 'zc_',
    'DB_CHARSET' => 'utf8',
    'DB_DEBUG' => true,
    'DB_PWD_SAFE' => false, //密码启用加密
    'DB_BACKUP_RDS' => false, //备份的数据库类型是否为RDS数据库,如果是则在后台备份数据库时则使用适合rds的mysqldump命令
    'DB_PARAMS' => array(
        PDO::ATTR_PERSISTENT => true, //持久化连接
    ),

    //分布式数据库配置
    /*
    'DB_DEPLOY_TYPE' => 1,
    'DB_TYPE' => 'mysql',
    'DB_HOST' => 'drds5c08708617e9public.drds.aliyuncs.com,drds5c08708617e9public.drds.aliyuncs.com',
    'DB_NAME' => 'zc_life4',
    'DB_USER' => 'zc_life4,zc_life4_ro',
    'DB_PWD'  => 'sXhy24WnpbCFqnGnr3iUqLmnqJiCgJxouZO4poSDdKI,sXhy24WnpbCFqnGnr3iUqLmnqJiCgJxouZO4poSDdKI',
    'DB_PORT' => '3306',
    'DB_PREFIX' => 'zc_',
    'DB_RW_SEPARATE'=> true, //读写分离
    'DB_MASTER_NUM' => 1, //主数据库个数
    */

    //域名部署
    'APP_SUB_DOMAIN_DEPLOY' => 1,
    'APP_SUB_DOMAIN_RULES' => array(

        /***参考配置方法***/
        //'子域名/IP/子域头' => '模块名[/控制器名]',
        //'子域名/IP/子域头' => array('模块名[/控制器名]', 'var1=a&var2=b&var3=c'),
        //'*' => array('Test', 'var1=1&var2=2'),
        //'*.user' => array('User', 'status=1&name=2'),

        /***正式服务器配置参数***/
        /*
        'admin.zcsh123.net' => 'Admin',
        'api.zcsh123.net' => 'Api',
        '220.166.64.253:8081' => 'Api',
        */

        /***测试服务器配置参数***/
        //'admin' => 'Admin',
        'apigrb' => 'APP',
        //'220.166.64.253:8080' => 'Api',

        /***本地测试配置参数***/
        /*
        '192.168.1.220:801' => 'Admin',
        '192.168.1.220:802' => 'Api',
        */
    ),

    //加载扩展配置文件
    'LOAD_EXT_CONFIG' => 'data,parameter,imagesize',
    //若在应用公共配置文件中配置，则自动加载App/Common/Conf/user.php和db.php
    //若在模块配置文件中配置，则自动加载App/模块/Confg/usr.php和db.php

    //设置插件目录(默认addon; http://***.com/模块/控制器/方法/addon/插件名)
    //'VAR_ADDON' => 'addon',

    //设置操作方法定位到类(需要全控制器对应改动,慎用)
    //'ACTION_BIND_CLASS' => true,

    //配置日志记录级别和记录方式
    //日志级别:
    //EMERG: 严重错误，导致系统崩溃无法使用
    //ALERT: 警戒性错误，必须被立即修改的错误
    //CRIT: 临界值错误，超过临界值的错误
    //ERR: 一般性错误
    //WARN: 警告性错误，需要发出警告的错误
    //NOTICE: 通知，程序可以运行但是还不够完美的错误
    //INFO: 信息，程序输出信息
    //DEBUG: 调试，用于调试信息
    //SQL: SQL语句，该级别只在调试模式开启时有效
    'LOG_RECORD' => true,
    'LOG_LEVEL' => 'EMERG,ALERT,CRIT',
    'LOG_TYPE' => 'File',

    //通用正则验证规则(用于直接调用或在公共函数validateExtend中使用)
    'COMMON_VALIDATE' => array(
        'CHS' => '/^[\x{4e00}-\x{9fa5}]+/u',
        'STRING' => '/^[a-zA-Z0-9]+$/',
        'USERNAME' => '/^[a-z]{1}[a-zA-Z0-9]{5,}$/',
        'NUMBER' => '/^[0-9]+$/',
        'QQ' => '/^[1-9][0-9]{4,}$/',
        'MOBILE' => '/^1(3|4|5|7|8){1}[0-9]{1}[0-9]{8}$/',
        'PHONE' => '/^(0[0-9]{2,3}-)?([0-9]{7,8})+(-[0-9]{1,4})?$/',
        'EMAIL' => '/^([a-zA-Z0-9_-]){1,}@([a-zA-Z0-9_-]){1,}\.([a-zA-Z0-9_-]){1,}$/',
        'ZIP' => '/^[1-9][0-9]{5}$/',
        'YEAR' => '/^[1-2][0-9]{3}$/',
        'CARD' => '/^[1-9]{1}[0-9]{5}[1-2]{1}[0-9]{7}[0-9]{3}([0-9]{1}|X|x)$/', //身份证号
        'HM' => '/^([1-9]{1}|[1-2]{1}[0-9]{1}):[0-9]{2}$/', //小时分钟
        'IDLIST' => '/^[0-9]{1}[0-9,]{0,}$/', //ID列表
        'TIME_STAMP' => '/^[0-9]{10}$/', //时间戳
        'WEEK' => '/^[1-5]{1}$/', //周数
        'MONEY' => '/^[0-9]{1,}(.[0-9]{1,})?$/', //金额
    ),

    //通用数据库字段数据相关配置
    'FIELD_CONFIG' => array(
        'common' => array(
            'exchangeway' => array('0' => '现场兑换', '1' => '送货上门', '2' => '到店消费'),
        ),
        'store' => array(
            'service' => array('0' => '都没有', '1' => 'wifi', '2' => '停车场', '3' => '都有'),
            'status' => array('0' => '正常', '1' => '冻结'),
            'manage_status' => array('0' => '未审核', '1' => '已审核', '2' => '已驳回'),
            'store_type' => array('1' => '普通商家', '2' => '中型商家', '3' => '大型商家'),
            'store_supermarket' => array('0' => '非自营', '1' => '自营'),
        ),
        'orders' => array(
            'status' => array(
                '0' => '未处理',
                '1' => '未使用',
                '2' => '已使用',
                '3' => '已过期',
                '4' => '已取消',
                '8' => '支付宝/微信付款',
                '11' => '未发货',
                '12' => '已发货',
                '13' => '已完成',
                '14' => '已取消',
                '21' => '公让宝/现金币付款'
            ),
            'exchangeway' => array('0' => '线下兑换(旧)', '1' => '商城购物', '2' => '线下买单'),
            'amount_type' => array('1' => '现金币', '2' => '公让宝', '3' => '彩分', '4' => '支付宝', '5' => '微信'),
            'order_status' => array(
                '0' => '未付款',
                '1' => '已付款',
                '2' => '已取消',
                '3' => '已发货',
                '4' => '已完成',
                '99' => '已删除'
            ),
        ),
        'preferential_way' => array(
            'manage_status' => array('0' => '未审核', '1' => '已审核', '2' => '已驳回'),
            'status' => array('0' => '已启用', '1' => '已停用'),
        ),
        'product' => array(
            'manage_status' => array('0' => '未审核', '1' => '已审核', '2' => '已驳回'),
            'status' => array('0' => '正常', '1' => '下架'),
        ),
        'shake_public' => array(
            'shake_flag' => array('0' => '未摇中', '1' => '摇中'),
        ),
        'member' => array(
            'is_blacklist' => array('0' => '移出', '1' => '提现', '2' => '店铺'),
        ),
        'procedure_queue' => array(
            'queue_status' => ['0' => '待执行', '1' => '正在执行', '2' => '执行失败', '3' => '执行成功', '4' => '暂停执行'],
        ),
        'timer_task' => array(
            'task_status' => ['0' => '正在执行', '1' => '执行失败', '2' => '执行成功'],
        ),
        'buyback' => array(
            'buyback_status' => ['0' => '申请中', '1' => '未通过', '2' => '已通过'],
        ),
        'ad' => array(
            'ad_status' => ['0' => '申请中', '1' => '未通过', '2' => '已发布', '3' => '已结束'],
            'ad_type' => ['0' => '不跳转', '1' => '外部链接', '2' => '店铺ID', '3' => '商品ID'],
        ),
        'shake' => array(
            'shake_status' => ['0' => '申请中', '1' => '未通过', '2' => '已发布', '3' => '已结束', '4' => '已回本'],
        ),
        'settings' => [
            'settings_type' => ['text' => '文字', 'options' => '选项', 'textarea' => '文本域', 'html' => 'HTML富文本'],
        ],
    ),

    //短信接口配置
    'SMS_CONFIG' => array(
        'CHUANGXIN' => array(
            'userid' => '1174',
            'account' => 'zcsh321',
            'password' => 'zcsh@qq.com',
            'action' => 'send',
            'sendTime' => '',
            'url' => 'http://web.28inter.com:8888/sms.aspx',
        ),
        'ALI' => array(
            'key' => 'LTAI9Ec4WRWxMQfg',
            'secret' => 'TfsgM4yC6AOLMNwoKAm0SNIxIIv22i',
            'template' => array(
                'yanzhengma' => 'SMS_141895191', //验证码
                'warning' => 'SMS_141895189', //预警
                'event' => 'SMS_141895189', //事务
                'login_submit' => 'SMS_141895193', //登录确认验证码
                'login_warning' => 'SMS_141895192', //登录异常验证码
                'login_action' => 'SMS_141895194', //登录事件短信通知
            ),
            'sign' => '锐科智能',
        ),
// 身份验证验证码 SMS_145480401
// 登录确认验证码 SMS_145480400
// 登录异常验证码 SMS_145480399
// 用户注册验证码 SMS_145480398
// 修改密码验证码 SMS_145480397
// 信息变更验证码 SMS_145480396
    ),
    'SMS_USE_DEFAULT' => 'ALI', //默认启用短信类型

    //邮件相关配置
    'MAIL_CONFIG' => array(
        'ONE' => array(
            'host' => 'ssl://smtp.163.com',
            'port' => 465,
            'username' => 'zcsh_service@163.com',
            'password' => '2ea95037d62fb9b7',
        ),
    ),

    //推送配置
    'PUSH_CONFIG' => array(
        'APP_KEY' => 'b35b0dbd1cf0c541082fbfec',
        'MASTER_SECRET' => 'fabc6e4e9987ef32bafebd78',
        'PUSH_URL' => 'https://api.jpush.cn/v3/push',
        'APNS_PRODUCTION' => 0, //0:开发环境,1:生产环境
    ),

    //客服热线
    'KEFU_PHONE' => '02887851988',

    //安全预警提示短信接收者手机号
    'SAFE_WARNING_PHONE' => '170xxxxx',

    //附件上传驱动配置信息
    'ATTACH_DOMAIN_ENABLE' => true, //是否启用附件头域名
    'ATTACH_SEPARATION_ON' => false, //附件分离开关
    'DEVICE_CONFIG' => array(
        'G1' => array(
            'device' => 'Ftp',
            'host' => '120.25.251.67',
            'port' => 21,
            'timeout' => 60,
            'username' => 'php',
            'password' => '123456',
            'domain' => '',
            'attach_domain' => array(
                'http://zc.zcsh123.com/',
            ),
        ),
        'G2' => array(
            'device' => 'Qiniu',
            'secrectKey' => 'QjanQj2pfgdb-RF5F8aqBUDHOfKuJS46p1nFjsIY',
            'accessKey' => 'bFq61zVHV95xL614hXMvh3G6_jfAaX543QLc-5ER',
            'domain' => 'oi89lz2j2.bkt.clouddn.com',
            'bucket' => 'attach-zcsh',
            'timeout' => 60,
            'attach_domain' => array(
                'http://oi89lz2j2.bkt.clouddn.com/',
            ),
        ),
        'G3' => array(
            'device' => 'Oss',
            'accessKeyId' => 'LTAIebzixGy8uK3Q',
            'accessKeySecret' => '12JTOwVxJUrAoX3WXAnQsysTMm6gvm',
            'endpoint' => 'oss-cn-shenzhen.aliyuncs.com',
            'bucket' => 'gongrangbao',
            'attach_domain' => array(
                'http://gongrangbao.oss-cn-shenzhen.aliyuncs.com/',
            ),
        ),
    ),

    'DEVICE_CONFIG_DEFAULT' => 'G3', //默认选择驱动配置

    //通用会员级别
    'MEMBER_LEVEL' => array(
        '1' => '体验用户',
        '2' => '个人代理',
        '3' => '区域合伙人',
//		'4'  => '区域合伙人',
        '99' => '管理员',
//		'5'  => '银卡代理',
//		'6'  => '金卡代理',
//		'7'  => '钻卡代理',
    ),

    //通用后台角色对应ID：主要用于后台设置创客会员为区域合伙人或服务中心 以及 分配商家时使用,需配置正确
    'ROLE_MUST_LIST' => array(
        'service' => 4,
        'agent' => 5,
        'merchant' => 6,
    ),

    //后台特殊场景下小管理员视为非小管理员的管理员列表(默认除了服务中心,区域合伙人,商家,超级管理员外均为小管理员)
    'NOT_SMALL_SUPER_MANAGER' => array(
        //后台首页
        'Admin/Index/index' => array(8, 10),

        //创客会员,体验会员,已审/未审服务中心,已审/未审区域合伙人
        'Review' => array(10),

        //体验/创客会员
        'Admin/Member' => array(10),

        //店铺/活动管理
        'Shop/Store' => array(10),

        //提现管理
        'Admin/Finance/withdraw' => array(8, 9),
    ),


    //苹果APP下载地址
    'APP_IOS_URL' => '',

    //路由过滤配置(格式:模型名[/控制器名][/方法名][/参数])
    'ROUTE_FILTER' => array(
        'cs.b' => array(
            'ALLOW' => false,
            'DENY' => array(
                'APP/Safe',
                'APP/Withdraw',
            ),
        ),
        'aa635efe8168de77db6b8ddacb5c4191' => array(
            'ALLOW' => array(
                'APP/Safe',
                'APP/Withdraw'
            ),
            'DENY' => false,
        ),
        'local.test' => array(
            'ALLOW' => array(
                'APP/Safe',
                'APP/Withdraw'
            ),
            'DENY' => false,
        ),
    ),

    //数据库备份表配置(仅适用于RDS模式)
    'DB_BACKUP_TABLE_LIST' => array(
        [
            'table' => 'zc_account',
            'where' => "--where=\"from_unixtime(account_uptime,'%Y%m%d')=from_unixtime(unix_timestamp(),'%Y%m%d')\"",
        ],
        [
            'table' => 'zc_account_cash_' . date('Ym'),
            'where' => "--where=\"from_unixtime(record_addtime,'%Y%m%d')=from_unixtime(unix_timestamp(),'%Y%m%d')\"",
        ],
        [
            'table' => 'zc_account_goldcoin_' . date('Ym'),
            'where' => "--where=\"from_unixtime(record_addtime,'%Y%m%d')=from_unixtime(unix_timestamp(),'%Y%m%d')\"",
        ],
        [
            'table' => 'zc_account_colorcoin_' . date('Ym'),
            'where' => "--where=\"from_unixtime(record_addtime,'%Y%m%d')=from_unixtime(unix_timestamp(),'%Y%m%d')\"",
        ],
        [
            'table' => 'zc_account_points_' . date('Ym'),
            'where' => "--where=\"from_unixtime(record_addtime,'%Y%m%d')=from_unixtime(unix_timestamp(),'%Y%m%d')\"",
        ],
        [
            'table' => 'zc_account_bonus_' . date('Ym'),
            'where' => "--where=\"from_unixtime(record_addtime,'%Y%m%d')=from_unixtime(unix_timestamp(),'%Y%m%d')\"",
        ],
    ),

);