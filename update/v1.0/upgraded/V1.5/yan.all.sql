-- 执行SQL
-- 创建报单币和能量值当前月明细表
create table zc_account_supply_201904 like zc_account_record;
create table zc_account_enjoy_201904 like zc_account_record;
-- 创建能量值配置组
insert into zc_settings_group values 
	(11, '能量值配置', 1, '98', unix_timestamp());
-- 创建能量值配置参数
insert into zc_settings values
	(null, 11, '签到', 'enjoy_signin', '100', 'text', '能量值(每次签到获得个数)', 1, 1151, unix_timestamp()),
	(null, 11, '分享朋友圈', 'enjoy_share', '200', 'text', '能量值(每次分享获得个数)', 1, 1150, unix_timestamp()),
	(null, 11, '挖矿', 'enjoy_mining', '50', 'text', '能量值(每次开启挖矿消耗个数)', 1, 1149, unix_timestamp()),
	(null, 11, '提现', 'enjoy_tixian', '50', 'text', '能量值(每次提现消耗个数)', 1, 1148, unix_timestamp()),
	(null, 11, '转赠', 'enjoy_transfer', '50', 'text', '能量值(每次转赠消耗个数)', 1, 1147, unix_timestamp()),
	(null, 11, '流通到交易平台', 'enjoy_third', '1', 'text', '能量值(每次流通消耗个数)', 1, 1146, unix_timestamp());
-- 板块管理增加能量值赠送规则字段
alter table zc_block
	add `block_enjoy_order_amount` decimal(14,4) default '0' comment '订单每满此金额则赠送指定能量值',
	add `block_enjoy_give_amount` decimal(14,4) default '0' comment '订单每满指定金额则赠送能量值的个数';
	
-- 签到表增加获取能量值字段
alter table zc_account_checkin
	add `checkin_amount` decimal(14,4) default '0' comment '获取能量值金额';
-- 新增能量值配置参数
insert into zc_settings values
	(null, 11, '分享朋友圈获赠次数', 'enjoy_share_count', '5', 'text', '次(每天限制次数)', 1, 1145, unix_timestamp());
-- 创建分享记录表
DROP TABLE IF EXISTS `zc_account_share`;
CREATE TABLE `zc_account_share` (
  `share_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `share_addtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '分享时间',
  `share_amount` decimal(14,4) DEFAULT '0.0000' COMMENT '获取能量值金额',
  PRIMARY KEY (`share_id`),
  KEY `share_addtime` (`share_addtime`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='分享记录表';

-- 创建公让宝兑换专区板块
insert into zc_block values
	(9, '公让宝兑换专区', 'Uploads/block/block_icon_9.png', 'Uploads/block/cover_9.png', 0, 0, 0, 100, 0, 0, 0, 0, 0, 9, 1, 0, 0, 1, 0, 0, 0, 0);