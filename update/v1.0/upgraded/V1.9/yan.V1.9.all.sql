-- 执行SQL

-- GRB转出表增加队列状态字段
alter table zc_trade
	add `is_queue` tinyint(1) default 0 comment '是否在队列中(0:否,1:是)',
	add index is_queue (`is_queue`);
	
-- 用户附属表新增澳交所第2平台的钱包地址字段
alter table zc_user_affiliate
	add `wallet_address_2` varchar(50) default '' comment 'AOGEX的钱包地址' after `wallet_address`;