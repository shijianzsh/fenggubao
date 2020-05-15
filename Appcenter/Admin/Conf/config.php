<?php
return array(
		
	//默认控制器
	'DEFAULT_CONTROLLER' => 'Login',
	
	//拨比查询开始日期
	'RATIO_START_DATE' => '2017-02-25 00:00:00',
		
	//三级安全密码(主要用于奖项管理三级安全验证)
	'THREE_SAFE_PWD' => '6dc3cacb3d23edaddc0c11169e9672d8',
	
	//短信验证码有效时长(秒)
	'SMS_ENABLE_TIME' => '120',
		
	//特殊处理个别帐号可以非手机号登录
	'MEMBER_LOGIN_NO_MOBILE' => array(
		//'nmgsyps' => '18199999999',
	),
	
	//登录安全配置参数
	'LOGIN_FIAL_COUNT_MAX' => 3, //登录失败启用短信验证码的最大次数
	'SMS_SEND_URL' => 'http://api5.zcsh123.com/Phone/sendSmsToManager', //登录失败N次发送短信调用的网址
		
);