-- -------------------------------------------
-- 添加谷聚金代理专区板块
-- -------------------------------------------
 INSERT INTO `zc_block`(`block_id`, `block_name`, `block_order`) VALUE (7, '谷聚金代理专区', 7);

-- -------------------------------------------
-- 谷聚金代理专区-大中华区对应省份
-- -------------------------------------------
DROP TABLE IF EXISTS `zc_gjj_regions`;
CREATE TABLE `zc_gjj_regions`
(
  `id`       INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`     VARCHAR(50)      NOT NULL DEFAULT '' COMMENT '地区名',
  `province` VARCHAR(50)      NOT NULL DEFAULT '' COMMENT '省份',
  PRIMARY KEY (`id`)
)
  ENGINE = InnoDB
  AUTO_INCREMENT = 1
  COMMENT ='谷聚金代理专区-大中华区对应省份';

INSERT INTO `zc_gjj_regions` VALUE (NULL, '华北地区', '北京市');
INSERT INTO `zc_gjj_regions` VALUE (NULL, '华北地区', '天津市');
INSERT INTO `zc_gjj_regions` VALUE (NULL, '华北地区', '河北省');
INSERT INTO `zc_gjj_regions` VALUE (NULL, '华北地区', '山西省');
INSERT INTO `zc_gjj_regions` VALUE (NULL, '华北地区', '内蒙古');
INSERT INTO `zc_gjj_regions` VALUE (NULL, '东北地区', '辽宁省');
INSERT INTO `zc_gjj_regions` VALUE (NULL, '东北地区', '吉林省');
INSERT INTO `zc_gjj_regions` VALUE (NULL, '东北地区', '黑龙江省');
INSERT INTO `zc_gjj_regions` VALUE (NULL, '华东地区', '上海市');
INSERT INTO `zc_gjj_regions` VALUE (NULL, '华东地区', '江苏省');
INSERT INTO `zc_gjj_regions` VALUE (NULL, '华东地区', '浙江省');
INSERT INTO `zc_gjj_regions` VALUE (NULL, '华东地区', '江西省');
INSERT INTO `zc_gjj_regions` VALUE (NULL, '华东地区', '江西省');
INSERT INTO `zc_gjj_regions` VALUE (NULL, '华东地区', '安徽省');
INSERT INTO `zc_gjj_regions` VALUE (NULL, '华东地区', '福建省');
INSERT INTO `zc_gjj_regions` VALUE (NULL, '华东地区', '山东省');
INSERT INTO `zc_gjj_regions` VALUE (NULL, '中南地区', '河南省');
INSERT INTO `zc_gjj_regions` VALUE (NULL, '中南地区', '湖北省');
INSERT INTO `zc_gjj_regions` VALUE (NULL, '中南地区', '湖南省');
INSERT INTO `zc_gjj_regions` VALUE (NULL, '中南地区', '广东省');
INSERT INTO `zc_gjj_regions` VALUE (NULL, '中南地区', '广西省');
INSERT INTO `zc_gjj_regions` VALUE (NULL, '中南地区', '海南省');
INSERT INTO `zc_gjj_regions` VALUE (NULL, '西南地区', '重庆市');
INSERT INTO `zc_gjj_regions` VALUE (NULL, '西南地区', '四川省');
INSERT INTO `zc_gjj_regions` VALUE (NULL, '西南地区', '贵州省');
INSERT INTO `zc_gjj_regions` VALUE (NULL, '西南地区', '云南省');
INSERT INTO `zc_gjj_regions` VALUE (NULL, '西南地区', '西藏');
INSERT INTO `zc_gjj_regions` VALUE (NULL, '西北地区', '陕西省');
INSERT INTO `zc_gjj_regions` VALUE (NULL, '西北地区', '甘肃省');
INSERT INTO `zc_gjj_regions` VALUE (NULL, '西北地区', '青海省');
INSERT INTO `zc_gjj_regions` VALUE (NULL, '西北地区', '宁夏');
INSERT INTO `zc_gjj_regions` VALUE (NULL, '西北地区', '新疆');

-- -------------------------------------------
-- 谷聚金代理专区-用户身份表
-- -------------------------------------------
DROP TABLE IF EXISTS `zc_gjj_roles`;
CREATE TABLE `zc_gjj_roles`
(
  `id`           INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `user_id`      INT(11) UNSIGNED    NOT NULL DEFAULT 0 COMMENT '用户ID',
  `role`         TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '身份：1 乡镇代理, 2 区县代理, 3 市级代理（预留），4 省营运中心, 5 大中华区',
  `region`       VARCHAR(50)         NOT NULL DEFAULT '' COMMENT '大中华区名',
  `province`     VARCHAR(50)         NOT NULL DEFAULT '' COMMENT '省份',
  `city`         VARCHAR(50)         NOT NULL DEFAULT '' COMMENT '城市',
  `country`      VARCHAR(50)         NOT NULL DEFAULT '' COMMENT '区县',
  `village`      VARCHAR(50)         NOT NULL DEFAULT '' COMMENT '乡镇',
  `audit_status` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核状态: 0 待审核, 1 已通过, 2 已驳回',
  `remark`       VARCHAR(255)        NOT NULL DEFAULT '' COMMENT '备注',
  `enabled`      TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态：0 禁用, 1 激活',
  `image`        VARCHAR(255)        NOT NULL DEFAULT '' COMMENT '打款凭证',
  `created_at`   INT(11)             NOT NULL DEFAULT 0 COMMENT '申请时间',
  `paid_at`      INT(11)             NOT NULL DEFAULT 0 COMMENT '打款凭证上传时间',
  `updated_at`   INT(11)             NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY (`user_id`),
  KEY (`region`),
  KEY (`role`),
  KEY (`province`),
  KEY (`country`),
  KEY (`audit_status`),
  KEY (`enabled`)
)
  ENGINE = InnoDB
  AUTO_INCREMENT = 1
  COMMENT ='谷聚金代理专区-用户身份表';

INSERT INTO `zc_settings` VALUE (NULL, 1, '代理销售奖【现金币】比例（一代）', 'prize_agent_consume_cash_bai_1', '10', 'text', '%', 1, 197, UNIX_TIMESTAMP());
INSERT INTO `zc_settings` VALUE (NULL, 1, '代理销售奖【现金币】比例（二代）', 'prize_agent_consume_cash_bai_2', '0', 'text', '%', 1, 194, UNIX_TIMESTAMP());
-- 业绩份额计算基数调整为500元/份
UPDATE `zc_settings`
SET `settings_value` = 1000
WHERE `settings_code` = 'performance_portion_base';

INSERT INTO `zc_settings` VALUE (NULL, 1, '钻石经销商补贴【现金币】比例（一代）', 'subsidy_level_5_cash_bai_1', '5', 'text', '%', 1, 169, UNIX_TIMESTAMP());
INSERT INTO `zc_settings` VALUE (NULL, 1, '钻石经销商补贴【现金币】比例（二代）', 'subsidy_level_5_cash_bai_2', '10', 'text', '%', 1, 166, UNIX_TIMESTAMP());

INSERT IGNORE INTO `zc_settings_group` VALUE (8, '谷聚金代理专区配置', 1, 200, UNIX_TIMESTAMP());
DELETE
FROM `zc_settings`
WHERE group_id = 8;
INSERT INTO `zc_settings` VALUE (NULL, 8, '大中华区合伙人代理费', 'gjj_agent_fee_5', '880000', 'text', '元', 1, 299, UNIX_TIMESTAMP());
INSERT INTO `zc_settings` VALUE (NULL, 8, '省营运中心合伙人代理费', 'gjj_agent_fee_4', '450000', 'text', '元', 1, 296, UNIX_TIMESTAMP());
# INSERT INTO `zc_settings` VALUE (NULL, 8, '市级代理费', 'gjj_agent_fee_3', '0', 'text', '元', 1, 293, UNIX_TIMESTAMP());
INSERT INTO `zc_settings` VALUE (NULL, 8, '区县合伙人代理费', 'gjj_agent_fee_2', '50000', 'text', '元', 1, 289, UNIX_TIMESTAMP());
INSERT INTO `zc_settings` VALUE (NULL, 8, '区县合伙人代理保证金', 'gjj_agent_deposit_2', '7000', 'text', '元', 1, 286, UNIX_TIMESTAMP());
# INSERT INTO `zc_settings` VALUE (NULL, 8, '乡镇代理费', 'gjj_agent_village_fee', '10000', 'text', '元', 1, 283, UNIX_TIMESTAMP());
INSERT INTO `zc_settings` VALUE (NULL, 8, '大中华区合伙人招商补贴', 'gjj_agent_subsidy_bai_5', '8', 'text', '%', 1, 279, UNIX_TIMESTAMP());
# INSERT INTO `zc_settings` VALUE (NULL, 8, '大中华区合伙人招商补贴进入流通资产比例', 'gjj_agent_subsidy_circulate_bai_5', '100', 'text', '%', 1, 278, UNIX_TIMESTAMP());
# INSERT INTO `zc_settings` VALUE (NULL, 8, '大中华区合伙人招商补贴进入锁定资产比例', 'gjj_agent_subsidy_lock_bai_5', '0', 'text', '%', 1, 277, UNIX_TIMESTAMP());
INSERT INTO `zc_settings` VALUE (NULL, 8, '大中华区合伙人重复消费奖', 'gjj_agent_consume_5', '1', 'text', '元/瓶', 1, 276, UNIX_TIMESTAMP());
# INSERT INTO `zc_settings` VALUE (NULL, 8, '大中华区合伙人重复消费奖进入流通资产比例', 'gjj_agent_consume_circulate_bai_5', '100', 'text', '%', 1, 275, UNIX_TIMESTAMP());
# INSERT INTO `zc_settings` VALUE (NULL, 8, '大中华区合伙人重复消费奖进入锁定资产比例', 'gjj_agent_consume_lock_bai_5', '100', 'text', '%', 1, 274, UNIX_TIMESTAMP());
INSERT INTO `zc_settings` VALUE (NULL, 8, '激活大中华区合伙人赠送谷聚金数量', 'gjj_agent_give_5', '10000', 'text', '瓶', 1, 273, UNIX_TIMESTAMP());
INSERT INTO `zc_settings` VALUE (NULL, 8, '省营运中心合伙人招商补贴', 'gjj_agent_subsidy_bai_4', '10', 'text', '%', 1, 269, UNIX_TIMESTAMP());
INSERT INTO `zc_settings` VALUE (NULL, 8, '省营运中心合伙人重复消费奖', 'gjj_agent_consume_4', '2', 'text', '元/瓶', 1, 266, UNIX_TIMESTAMP());
INSERT INTO `zc_settings` VALUE (NULL, 8, '激活省营运中心合伙人赠送谷聚金数量', 'gjj_agent_give_4', '5000', 'text', '瓶', 1, 263, UNIX_TIMESTAMP());
INSERT INTO `zc_settings` VALUE (NULL, 8, '激活区县合伙人赠送谷聚金数量', 'gjj_agent_give', '1000', 'text', '瓶', 1, 259, UNIX_TIMESTAMP());
INSERT INTO `zc_settings` VALUE (NULL, 8, '直推合伙人重复消费奖', 'gjj_recommend_county_consume', '1', 'text', '元/瓶', 1, 256, UNIX_TIMESTAMP());
INSERT INTO `zc_settings` VALUE (NULL, 8, '直推荐合伙人PV值比例', 'gjj_recommend_pv_bai', '50', 'text', '%', 1, 253, UNIX_TIMESTAMP());
INSERT INTO `zc_settings` VALUE (NULL, 8, '单次最低提货数量', 'gjj_exchange_min', '100', 'text', '瓶', 1, 249, UNIX_TIMESTAMP());

INSERT INTO `zc_settings` VALUE (NULL, 8, '合伙人申请说明', 'gjj_apply_instruction', '合伙人申请说明', 'textarea', '', 1, 246, UNIX_TIMESTAMP());

ALTER TABLE `zc_block`
  MODIFY `block_goldcoin_percent` DECIMAL(6, 2) UNSIGNED NOT NULL DEFAULT 0 COMMENT '公让宝抵扣比例， 0 表示不支持（单位：%）';
ALTER TABLE `zc_block`
  ADD `block_discount_1` DECIMAL(6, 2) UNSIGNED NOT NULL DEFAULT 0 COMMENT '普通会员折扣【最大值：10】， 0 表示不支持（单位：折）';
ALTER TABLE `zc_block`
  ADD `block_discount_5` DECIMAL(6, 2) UNSIGNED NOT NULL DEFAULT 0 COMMENT '5星会员（钻石经销商）折扣【最大值：10】， 0 表示不支持（单位：折）';

ALTER TABLE `zc_block`
  ADD `block_enabled` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态：0 禁用, 1 激活';

TRUNCATE TABLE `zc_block`;
INSERT INTO `zc_block` VALUE ('1', '谷聚金产品专区', 'Uploads/block/block_icon_1.png', 'Uploads/block/cover_1.png', 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 1);
INSERT INTO `zc_block` VALUE ('2', '品牌专区', 'Uploads/block/block_icon_2.png', 'Uploads/block/cover_2.png', 0, 0, 0, 10, 0, 0, 0, 0, 0, 1, 8, 5, 0);
INSERT INTO `zc_block` VALUE ('3', '会员特价专区', 'Uploads/block/block_icon_3.png', 'Uploads/block/cover_3.png', 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, 0, 0, 0);
INSERT INTO `zc_block` VALUE ('4', '会员代理大礼包', 'Uploads/block/block_icon_4.png', 'Uploads/block/cover_4.png', 0, 0, 0, 0, 0, 0, 0, 0, 0, 8, 0, 0, 1);
INSERT INTO `zc_block` VALUE ('5', '工厂直供专区', 'Uploads/block/block_icon_5.png', 'Uploads/block/cover_5.png', 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, 0, 0, 0);
INSERT INTO `zc_block` VALUE ('6', '海视明亮加盟店', 'Uploads/block/block_icon_6.png', 'Uploads/block/cover_6.png', 0, 0, 0, 0, 0, 0, 0, 0, 0, 6, 0, 0, 0);
INSERT INTO `zc_block` VALUE ('7', '谷聚金代理专区', 'Uploads/block/block_icon_7.png', 'Uploads/block/cover_7.png', 0, 0, 0, 0, 0, 0, 0, 0, 0, 7, 0, 0, 1);
INSERT INTO `zc_block` VALUE ('8', '现金币充值中心', 'Uploads/block/block_icon_8.png', 'Uploads/block/cover_8.png', 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, 1, 0, 0);



ALTER TABLE `zc_orders`
  ADD `discount` DECIMAL(6, 2) UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单折扣【最大值：10】,单位：折';


ALTER TABLE `zc_order_affiliate`
  ADD `affiliate_goldcoin_price` DECIMAL(6, 2) UNSIGNED NOT NULL DEFAULT 0 COMMENT '公让宝价格' AFTER `affiliate_goldcoin`;

ALTER TABLE `zc_product`
  ADD `video_url` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '产品小视频地址';

ALTER TABLE `zc_consume`
  ADD `machine_amount` DECIMAL(14, 2) NOT NULL DEFAULT 0 COMMENT '矿机数量';

-- ----------------------------------
-- 增加谷聚金代理专区产品 (content)
&amp;lt;img src=&amp;quot;http://csgrb.it-rayko.com/Uploads/keditor/image/20190301/299b7dbdf85d3d117027ba16f1b3489a.jpg&amp;quot; alt=&amp;quot;&amp;quot; /&amp;gt;
&amp;lt;img src=&amp;quot;http://csgrb.it-rayko.com/Uploads/keditor/image/20190301/09316b9f8bca5d1b2d215ca32e7dec51.jpg&amp;quot; alt=&amp;quot;&amp;quot; /&amp;gt;
&amp;lt;img src=&amp;quot;http://csgrb.it-rayko.com/Uploads/keditor/image/20190301/bb8b9d5c86d8e5530a8b0392cb1f04a7.jpg&amp;quot; alt=&amp;quot;&amp;quot; /&amp;gt;
&amp;lt;img src=&amp;quot;http://csgrb.it-rayko.com/Uploads/keditor/image/20190301/ddaab24ba339298762877c1381adbfa5.jpg&amp;quot; alt=&amp;quot;&amp;quot; /&amp;gt;
&amp;lt;img src=&amp;quot;http://csgrb.it-rayko.com/Uploads/keditor/image/20190301/dc5296cbc38d0b07022f8908509c5c82.jpg&amp;quot; alt=&amp;quot;&amp;quot; /&amp;gt;
&amp;lt;img src=&amp;quot;http://csgrb.it-rayko.com/Uploads/keditor/image/20190301/2b6f73d24c73f40c34ea56788f24e10f.png&amp;quot; alt=&amp;quot;&amp;quot; /&amp;gt;
&amp;lt;img src=&amp;quot;http://csgrb.it-rayko.com/Uploads/keditor/image/20190301/4902a2888ee5177660d23a0049e6501c.png&amp;quot; alt=&amp;quot;&amp;quot; /&amp;gt;
&amp;lt;img src=&amp;quot;http://csgrb.it-rayko.com/Uploads/keditor/image/20190301/8669b02ef54c6db7391fa6163b1e066d.jpg&amp;quot; alt=&amp;quot;&amp;quot; /&amp;gt;
-- ----------------------------------


