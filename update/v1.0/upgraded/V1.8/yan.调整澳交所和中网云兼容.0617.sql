--- 执行SQL

-- 中网云和澳交所配置增加实时单价设置项
--insert into zc_settings values
--	(null, 5, '实时单价', 'zhongwy_price', '1', 'text', '元', 1, 500, unix_timestamp()),
--	(null, 13, '实时单价', 'ajs_price', '1', 'text', '元', 1, 1300, unix_timestamp());

-- 用户附属表新增中网云钱包地址字段
alter table zc_user_affiliate
	add `zhongwy_wallet_address` varchar(50) default '' comment '中网云 - 钱包地址',
	modify `wallet_address` varchar(50) default '' comment '澳交所 - 钱包地址';
	
-- 增加公让宝实时价格管理权限规则
insert into zc_auth_rule values
	(null, 'System/GoldcoinPrice/index,System/GoldcoinPrice/save', '公让宝实时价格管理', 1, 1, '');
	
-- 修改增加GRB转出第三方审核管理权限规则
update zc_auth_rule set `title`='GRB转出中网云审核管理' where id=97;
update zc_auth_rule set `title`='GRB转出中网云审核查看' where id=98;
insert into zc_auth_rule values
	(null, 'Admin/GrbTransaction/index/wallet_type/AJS,Admin/GrbTransaction/tradeAction,Admin/GrbTransaction/tradeBack', 'GRB转出澳交所审核管理', 1, 1, ''),
	(null, 'Admin/GrbTransaction/index/wallet_type/AJS', 'GRB转出澳交所审核查看', 1, 1, '');
	
-- 中网云转入申请审核管理权限
insert into zc_auth_rule values
	(null, 'Admin/ZwyTransaction/index,Admin/ZwyTransaction/tradeAction,Admin/ZwyTransaction/tradeBack,Admin/ZwyTransaction/getDetails', '中网云转入申请审核管理', 1, 1, '');
	
-- 公让宝实时价格表插入最新澳交所和中网云价格数据
insert into zc_goldcoin_prices values
	(null, 1, 1, UNIX_TIMESTAMP(), 'ZWY'),
	(null, 1, 1, UNIX_TIMESTAMP(), 'AJS');