-- 执行SQL

-- 转入记录表增加从对应用户钱包地址转出至平台指定钱包地址的txid字段
alter table zc_transactions
	add `transfer_out_txid` varchar(100) default '' comment '从用户钱包地址转出至平台指定钱包地址的交易ID';
