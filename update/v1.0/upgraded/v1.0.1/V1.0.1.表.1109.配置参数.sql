update zc_settings
set settings_title = 'VIP会员销售奖比例（直推VIP会员）'
where settings_code = 'prize_direct_bai_2';

insert into zc_settings values (null, 1, '店长销售奖（直推店长）', 'prize_direct_service', '1000', 'text', '米宝', 1, 876, 1540542718);

insert into zc_settings values (null, 1, '申请店长赠送', 'apply_service_give_goldcoin_amount', '1000', 'text', '兑换券', 1, 873, 1540542718);

delete from zc_settings
where settings_code in
      ('service_star_condition_1', 'service_star_subsidy_1', 'service_star_condition_2', 'service_star_subsidy_2', 'service_star_condition_3', 'service_star_subsidy_3', 'service_star_condition_4', 'service_star_subsidy_4', 'service_star_condition_5', 'service_star_subsidy_5', 'prize_service_consume_bai', 'performance_bai_order_goldcoin');

INSERT INTO `zc_settings` VALUES (null, 6, '申请店长所需代理费金额', 'apply_service_amount', '3000', 'text', '元', 1, 699, 1540542718);

INSERT INTO `zc_settings` VALUES (null, 6, '一星店长晋升条件（旗下累计X位店长）', 'service_star_condition_1', '5', 'text', '位店长', 1, 696, 1540542718);
INSERT INTO `zc_settings` VALUES (null, 6, '一星店长补贴比例（新增业绩的X%）', 'service_star_subsidy_1', '0.5', 'text', '%', 1, 693, 1540542757);

INSERT INTO `zc_settings` VALUES (null, 6, '二星店长晋升条件（旗下累计X位店长）', 'service_star_condition_2', '10', 'text', '位店长', 1, 679, 1540542718);
INSERT INTO `zc_settings` VALUES (null, 6, '二星店长补贴比例（新增业绩的X%）', 'service_star_subsidy_2', '1', 'text', '%', 1, 676, 1540542757);

INSERT INTO `zc_settings` VALUES (null, 6, '三星店长晋升条件（旗下累计X位店长）', 'service_star_condition_3', '20', 'text', '位店长', 1, 673, 1540542718);
INSERT INTO `zc_settings` VALUES (null, 6, '三星店长补贴比例（新增业绩的X%）', 'service_star_subsidy_3', '1.5', 'text', '%', 1, 669, 1540542757);

INSERT INTO `zc_settings` VALUES (null, 6, '四星店长晋升条件（旗下累计X位店长）', 'service_star_condition_4', '40', 'text', '位店长', 1, 666, 1540542718);
INSERT INTO `zc_settings` VALUES (null, 6, '四星店长补贴比例（新增业绩的X%）', 'service_star_subsidy_4', '2', 'text', '%', 1, 663, 1540542757);

INSERT INTO `zc_settings` VALUES (null, 6, '五星店长晋升条件（旗下累计X位店长）', 'service_star_condition_5', '80', 'text', '位店长', 1, 659, 1540542718);
INSERT INTO `zc_settings` VALUES (null, 6, '五星店长补贴比例（新增业绩的X%）', 'service_star_subsidy_5', '2.5', 'text', '%', 1, 656, 1540542757);

INSERT INTO `zc_settings` VALUES (null, 6, '六星店长晋升条件（旗下累计X位店长）', 'service_star_condition_6', '160', 'text', '位店长', 1, 653, 1540542718);
INSERT INTO `zc_settings` VALUES (null, 6, '六星店长补贴比例（新增业绩的X%）', 'service_star_subsidy_6', '3', 'text', '%', 1, 649, 1540542757);

INSERT INTO `zc_settings` VALUES (null, 6, '七星店长晋升条件（旗下累计X位店长）', 'service_star_condition_7', '320', 'text', '位店长', 1, 646, 1540542718);
INSERT INTO `zc_settings` VALUES (null, 6, '七星店长补贴比例（新增业绩的X%）', 'service_star_subsidy_7', '3.5', 'text', '%', 1, 643, 1540542757);

INSERT INTO `zc_settings` VALUES (null, 6, '八星店长晋升条件（旗下累计X位店长）', 'service_star_condition_8', '640', 'text', '位店长', 1, 639, 1540542718);
INSERT INTO `zc_settings` VALUES (null, 6, '八星店长补贴比例（新增业绩的X%）', 'service_star_subsidy_8', '4', 'text', '%', 1, 636, 1540542757);


update zc_settings_group
set group_name = '业绩和业务补贴配置'
where group_id = 3;

INSERT INTO `zc_settings` VALUES (null, 3, '星级店长每日业务补贴金额', 'service_subsidy_amount_everyday', '100', 'text', '米宝', 1, 399, 1540542757);
INSERT INTO `zc_settings` VALUES (null, 3, '星级店长业务补贴封顶金额', 'service_subsidy_amount_max', '9000', 'text', '米宝', 1, 396, 1540542757);

-- -------------------------------------
-- 星级店长业务补贴记录表
-- -------------------------------------
drop table if exists `zc_subsidy_record`;
create table `zc_subsidy_record` (
  `record_id`       int(11) unsigned        not null auto_increment,
  `user_id`         int(11) unsigned        null     default 0
  comment '用户id',
  `subsidy_amount`  decimal(14, 4) unsigned null     default 0
  comment '业务补贴金额',
  `subsidy_tag`     int(8) unsigned  null     default 0
  comment '业务补贴标识',
  `subsidy_addtime` int(11) unsigned        not null default 0
  comment '业务补贴时间',
  primary key (`record_id`),
  unique `user_tag` (`user_id`, `subsidy_tag`)

)
  engine = innodb
  auto_increment = 1
  comment ='星级店长业务补贴记录表';

update zc_settings_group set group_status = 0 where group_id = 5;
update zc_settings set settings_status = 2 where group_id = 5;
update zc_settings set settings_status = 2 where settings_code = 'signin_give_goldcoin_amount';

