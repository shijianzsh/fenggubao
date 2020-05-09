<?php
return array(
	
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
				'21' => '丰谷宝/现金积分付款'
			),
			'exchangeway' => array('0' => '线下兑换(旧)', '1' => '商城购物', '2' => '线下买单'),
			'amount_type' => array('1' => '现金积分', '2' => '微信', '3' => '支付宝', '4' => '第三方-微信', '5' => '第三方-支付宝', '6' => '丰谷宝', '7' => '银行卡', '8' => '提货券', '9' => '兑换券', '10' => '报单币'),
			'order_status' => array(
				'0' => '未付款',
				'1' => '已付款',
				'2' => '已取消',
				'3' => '已发货',
				'4' => '已完成',
				'50' => '异常',
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
		'trade' => [
			'status' => ['0' => '待审核', '1' => '驳回', '2' => '提交失败', '3' => '提交成功', '4' => '执行队列中'],
			'type' => ['ZWY' => '中网云', 'AJS' => '澳交所', 'AGX' => 'AOGEX', 'SLU' => 'Silk Trader'],
		],
		'news' => [
			'category' => ['1' => '新动态', '2' => '知产品', '3' => '爱分享', '4' => '大事记'],	
		],
		'certification' => [
			'certification_status' => ['0' => '未审核', '1' => '已驳回', '2' => '已审核'],
		],
		'block' => [
			'block_enabled' => ['0' => '已禁用', '1' => '已激活'],
			'block_only_member' => ['0' => '否', '1' => '是'],
		],
		'goldcoin_prices' => [
			'type' => ['ZWY' => '中网云', 'AJS' => '澳交所', 'AGX' => 'AOGEX', 'SLU' => 'Silk Trader'],	
		],
		'transactions' => [
			'status' => ['0' => '未处理', '1' => '已处理', '2' => '异常', '3' => '执行队列中'],
			'type' => ['ZWY' => '中网云', 'AJS' => '澳交所', 'AGX' => 'AOGEX', 'SLU' => 'Silk Trader'],
			'category' => ['send' => '转出', 'receive' => '转入'],
		],
	),
		
);
?>