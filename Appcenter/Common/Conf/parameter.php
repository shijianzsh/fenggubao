<?php

return array(
	'CASHTOGOLDCOIN'   =>
		array(
//			'cash_goldcoin_min' => '10',
//			'cash_goldcoin_bei' => '10',
			'cash_min'          => '100',
			'cash_bei'          => '100',
		),
	'PARAMETER_CONFIG' =>
		array(
			'COLORCOIN_MSG'    =>
				array(
					'instruction' => '彩分转换成现金积分之后可用于支付买单或者提现',
					'rule'        => '最低{%zhuan_color_min%}，并且必须是{%zhuan_color_bei%}的整数倍。1彩分等于{%color_rate%}个现金积分。您目前有彩分{%colorcoin%}',
				),
			'WITHDRAW_MSG'     =>
				array(
					'instruction' => '提现的金额将转账到你的支付宝/微信账号，请务必填写正确的支付宝账号+真实姓名/微信账号；',
					'rule'        => '提现最低{%tiqu_cash_min%}，并且必须是{%tiqu_cash_bei%}的整数倍；支付宝手续费{%tiqu_fee%}%、微信手续费{%tiqu_fee_weixin%}%；您目前有现金积分{%cash%}',
				),
			'GOLDZHUANCASH'    =>
				array(
					'instruction' => '现金积分转丰谷宝，可以用于消费，商家可以发布摇一摇',
					'rule'        => '最低{%cash_goldcoin_min%}，并且必须是{%cash_goldcoin_bei%}的整数倍。您目前有现金积分{%cash%}',
				),
			'zhuan_color_fee'  => '1',
			'POINTS'           =>
				array(
					'service_company_points'       => '1',
					'service_company_points_clear' => '1',
				),
			'ONLINE_CLASSROOM' => '房间号：95488656   备注： (每周六晚上19:30开课)',
			'MERCHANT'         =>
				array(
					'points_merchant_max_day_1'  => '0',
					'points_merchant_max_week_1' => '100000',
					'points_merchant_max_day_2'  => '0',
					'points_merchant_max_week_2' => '300000',
					'points_merchant_max_day_3'  => '0',
					'points_merchant_max_week_3' => '1000000',
				),
		),
);