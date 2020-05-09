-- 执行SQL

-- 第三方转入表增加奖励金额字段
alter table zc_transactions
	add `reward_amount` decimal(14,4) default '0' comment '奖励金额';