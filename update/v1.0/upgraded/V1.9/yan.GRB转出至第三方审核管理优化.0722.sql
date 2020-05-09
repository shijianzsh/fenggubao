-- 执行SQL

-- GRB转出表增加队列状态字段
alter table zc_trade
	add `is_queue` tinyint(1) default 0 comment '是否在队列中(0:否,1:是)',
	add index is_queue (`is_queue`);