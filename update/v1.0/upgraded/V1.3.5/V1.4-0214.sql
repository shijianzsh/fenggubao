-- -------------------------------------------
-- 谷聚金代理专区-用户身份表
-- -------------------------------------------
DROP TABLE IF EXISTS `zc_gjj_roles`;
CREATE TABLE `zc_gjj_roles`
(
  `id`           INT(11) UNSIGNED        NOT NULL AUTO_INCREMENT,
  `user_id`      INT(11) UNSIGNED UNIQUE NOT NULL DEFAULT 0 COMMENT '用户ID',
  `role`         TINYINT(1) UNSIGNED     NOT NULL DEFAULT 0 COMMENT '身份：1 乡镇代理, 2 区县代理, 3 市级代理（预留），4 省营运中心',
  `province`     VARCHAR(50)             NOT NULL DEFAULT '' COMMENT '省份',
  `city`         VARCHAR(50)             NOT NULL DEFAULT '' COMMENT '城市',
  `county`       VARCHAR(50)             NOT NULL DEFAULT '' COMMENT '区县',
  `village`      VARCHAR(50)             NOT NULL DEFAULT '' COMMENT '乡镇',
  `enabled`      TINYINT(1) UNSIGNED     NOT NULL DEFAULT 0 COMMENT '状态：0 禁用, 1 激活',
  `audit_status` TINYINT(1) UNSIGNED     NOT NULL DEFAULT 0 COMMENT '审核状态: 0 待审核, 1 已通过, 2 已驳回',
  `remark`       VARCHAR(255)            NOT NULL DEFAULT '' COMMENT '备注',
  `created_at`   INT(11)                 NOT NULL DEFAULT 0 COMMENT '申请时间',
  `updated_at`   INT(11)                 NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY (`user_id`),
  KEY (`role`),
  KEY (`enabled`),
  KEY (`id`)
)
  ENGINE = InnoDB
  AUTO_INCREMENT = 1
  COMMENT ='谷聚金代理专区-用户身份表';



INSERT INTO `zc_settings_group`
VALUES (8, '谷聚金代理专区配置', 1, 200, UNIX_TIMESTAMP());
DELETE
FROM `zc_settings`
WHERE group_id = 8;
# INSERT INTO `zc_settings` VALUES (NULL, 8, '大中华代理费', 'gjj_agent_china_fee', '880000', 'text', '元', 1, 299, UNIX_TIMESTAMP());
# INSERT INTO `zc_settings` VALUES (NULL, 8, '省营运中心代理费', 'gjj_agent_province_fee', '450000', 'text', '元', 1, 296, UNIX_TIMESTAMP());
# INSERT INTO `zc_settings` VALUES (NULL, 8, '市级代理费', 'gjj_agent_city_fee', '0', 'text', '元', 1, 293, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
VALUES (NULL, 8, '区县代理费', 'gjj_agent_county_fee', '50000', 'text', '元', 1, 289, UNIX_TIMESTAMP());
# INSERT INTO `zc_settings` VALUES (NULL, 8, '区县代理保证金', 'gjj_agent_county_deposit', '7000', 'text', '元', 1, 286, UNIX_TIMESTAMP());
# INSERT INTO `zc_settings` VALUES (NULL, 8, '乡镇代理费', 'gjj_agent_village_fee', '10000', 'text', '元', 1, 283, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
VALUES (NULL, 8, '大中华区管理津贴', 'gjj_agent_china_subsidy_bai', '5', 'text', '%', 1, 279, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
VALUES (NULL, 8, '大中华区重复消费奖', 'gjj_agent_china_consume', '2', 'text', '元/瓶', 1, 276, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
VALUES (NULL, 8, '省营运中心管理津贴', 'gjj_agent_province_subsidy_bai', '10', 'text', '%', 1, 273, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
VALUES (NULL, 8, '省营运中心重复消费奖', 'gjj_agent_province_consume', '2', 'text', '元/瓶', 1, 269, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
VALUES (NULL, 8, '激活区县代理赠送提货券金额', 'gjj_agent_county_give', '1000', 'text', '提货券', 1, 266, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
VALUES (NULL, 8, '直推荐区县代理PV值比例', 'gjj_recommend_county_pv_bai', '100', 'text', '%', 1, 263, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
VALUES (NULL, 8, '直推重复消费奖', 'gjj_recommend_county_consume', '1', 'text', '元/瓶', 1, 259, UNIX_TIMESTAMP());



