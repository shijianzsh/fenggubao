-- ----------------------------
-- 初始化板块数据
-- ----------------------------
ALTER TABLE `zc_block`
  ADD `block_cover` VARCHAR(255) NOT NULL DEFAULT ''
    COMMENT '封面图'
    AFTER `block_icon`;

TRUNCATE TABLE `zc_block`;

INSERT INTO `zc_block` (`block_name`, `block_icon`, `block_cover`, `block_order`)
  VALUE ('果蔬', 'Public/images/icon_1.png', 'Public/images/cover_1.png', 1);
INSERT INTO `zc_block` (`block_name`, `block_icon`, `block_cover`, `block_order`)
  VALUE ('干货', 'Public/images/icon_2.png', 'Public/images/cover_2.png', 2);
INSERT INTO `zc_block` (`block_name`, `block_icon`, `block_cover`, `block_order`)
  VALUE ('特色', 'Public/images/icon_3.png', 'Public/images/cover_3.png', 3);
INSERT INTO `zc_block` (`block_name`, `block_icon`, `block_cover`, `block_order`)
  VALUE ('代理专区', 'Public/images/icon_4.png', 'Public/images/cover_4.png', 5);
INSERT INTO `zc_block` (`block_name`, `block_icon`, `block_cover`, `block_order`)
  VALUE ('预售', 'Public/images/icon_5.png', 'Public/images/cover_5.png', 4);
INSERT INTO `zc_block` (`block_name`, `block_icon`, `block_cover`, `block_order`)
  VALUE ('公让宝兑换区', 'Public/images/icon_6.png', 'Public/images/cover_6.png', 6);

DROP TABLE IF EXISTS `zc_consume_rule`;
CREATE TABLE `zc_consume_rule`
(
  `id`          INT(11) UNSIGNED        NOT NULL AUTO_INCREMENT,
  `level`       TINYINT(1) UNSIGNED     NOT NULL DEFAULT 0
    COMMENT '消费等级',
  `amount`      DECIMAL(14, 4) UNSIGNED NOT NULL DEFAULT 0
    COMMENT '消费业绩金额',
  `subsidy_bai` DECIMAL(14, 4) UNSIGNED NOT NULL DEFAULT 0
    COMMENT '管理津贴比例',
  `out_bei`     DECIMAL(14, 4) UNSIGNED NOT NULL DEFAULT 0
    COMMENT '出局倍数',
  `uptime`      INT(11) UNSIGNED        NOT NULL DEFAULT 0
    COMMENT '更新时间',
  PRIMARY KEY (`id`)
)
  ENGINE = INNODB
  AUTO_INCREMENT = 1
  COMMENT = '消费规则';

DROP TABLE IF EXISTS `zc_consume`;
CREATE TABLE `zc_consume`
(
  `id`            INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`       INT(11) UNIQUE   NOT NULL DEFAULT 0
    COMMENT '用户ID',
  `level`         TINYINT(1)       NOT NULL DEFAULT 0
    COMMENT '消费等级',
  `amount`        DECIMAL(14, 4)   NOT NULL DEFAULT 0
    COMMENT '消费业绩总金额',
  `income_amount` DECIMAL(14, 4)   NOT NULL DEFAULT 0
    COMMENT '收益总金额（公让宝）',
  `is_out`        TINYINT(1)       NOT NULL DEFAULT 0
    COMMENT '是否出局',
  `uptime`        INT(11)          NOT NULL DEFAULT 0
    COMMENT '更新时间',
  PRIMARY KEY (`id`)
)
  ENGINE = INNODB
  AUTO_INCREMENT = 1
  COMMENT = '消费规则';


ALTER TABLE `zc_performance_rule`
  ADD `rule_label` VARCHAR(10) NOT NULL DEFAULT ''
    COMMENT '头衔'
    AFTER `rule_id`;

-- ----------------------------
-- 代理专区消费赠送规则
-- ----------------------------
DROP TABLE IF EXISTS `zc_agent_rule`;

-- -------------------------------------
-- 公让宝实时价格表
-- 定时从第三方平台抓取
-- 结算时取最新一次价格数据
-- 此价格为 1公让宝 = ？人民币
-- 收益发放时需用实时价格换算成公让宝，再发放到公让宝流通资产中
-- 购买或申请身份赠送奖励时需用实时价格换算成公让宝，再发放到公让宝锁定资产中
-- -------------------------------------
DROP TABLE IF EXISTS `zc_goldcoin_prices`;
CREATE TABLE `zc_goldcoin_prices`
(
  `id`     INT(11) UNSIGNED        NOT NULL AUTO_INCREMENT,
  `amount` DECIMAL(14, 4) UNSIGNED NOT NULL DEFAULT 0
    COMMENT '价格',
  `uptime` INT(11) UNSIGNED        NOT NULL DEFAULT 0
    COMMENT '跟新时间',
  PRIMARY KEY (`id`)
)
  ENGINE = InnoDB
  AUTO_INCREMENT = 1
  COMMENT ='公让宝实时价格表';

INSERT INTO `zc_goldcoin_prices` VALUE (NULL, 1, 1543631377);

-- --------------------------------------
-- 锁定资产表
-- --------------------------------------
DROP TABLE IF EXISTS `zc_lock`;
CREATE TABLE `zc_lock`
(
  `id`             INT(11) UNSIGNED        NOT NULL AUTO_INCREMENT,
  `user_id`        INT(11) UNSIGNED        NOT NULL DEFAULT 0
    COMMENT '用户ID',
  `total_amount`   DECIMAL(14, 4) UNSIGNED NOT NULL DEFAULT 0
    COMMENT '总金额',
  `release_amount` DECIMAL(14, 4) UNSIGNED NOT NULL DEFAULT 0
    COMMENT '已释放金额',
  `lock_amount`    DECIMAL(14, 4) UNSIGNED NOT NULL DEFAULT 0
    COMMENT '未释放金额',
  `tag`            INT(8)                  NOT NULL DEFAULT 0
    COMMENT '标签：0(实时数据), [year], [year][moth], [year][moth][day]',
  `uptime`         INT(11) UNSIGNED        NOT NULL DEFAULT 0
    COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE `user_tag` (`user_id`, `tag`),
  INDEX (`uptime`)
)
  ENGINE = InnoDB
  AUTO_INCREMENT = 1
  COMMENT ='锁定资产表';

-- --------------------------------------
-- 锁定资产释放队列
-- --------------------------------------
DROP TABLE IF EXISTS `zc_lock_queue`;
CREATE TABLE `zc_lock_queue`
(
  `id`             INT(11) UNSIGNED        NOT NULL AUTO_INCREMENT,
  `user_id`        INT(11) UNSIGNED        NOT NULL DEFAULT 0
    COMMENT '用户ID',
  `total_amount`   DECIMAL(14, 4) UNSIGNED NOT NULL DEFAULT 0
    COMMENT '总金额',
  `release_amount` DECIMAL(14, 4) UNSIGNED NOT NULL DEFAULT 0
    COMMENT '已释放金额',
  `release_rate`   DECIMAL(6, 2) UNSIGNED  NOT NULL DEFAULT 0
    COMMENT '每次释放比例',
  `tag`            INT(8)                  NOT NULL DEFAULT 0
    COMMENT '标签：[year][moth][day]',
  `remark`         VARCHAR(255)            NOT NULL DEFAULT ''
    COMMENT '备注',
  `addtime`        INT(11) UNSIGNED        NOT NULL DEFAULT 0
    COMMENT '加入时间',
  `uptime`         INT(11) UNSIGNED        NOT NULL DEFAULT 0
    COMMENT '最后释放时间',
  PRIMARY KEY (`id`),
  INDEX `user_tag` (`user_id`, `tag`),
  INDEX (`uptime`)
)
  ENGINE = InnoDB
  AUTO_INCREMENT = 1
  COMMENT ='锁定资产释放队列';

-- --------------------------------------
-- 新闻资讯表
-- --------------------------------------
DROP TABLE IF EXISTS `zc_news`;
CREATE TABLE `zc_news`
(
  `id`         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`      VARCHAR(255)     NOT NULL DEFAULT ''
    COMMENT '标题',
  `cover`      VARCHAR(255)     NOT NULL DEFAULT ''
    COMMENT '封面图',
  `content`    TEXT             NULL     DEFAULT NULL
    COMMENT '内容',
  `sort`       INT(11)          NOT NULL DEFAULT 0
    COMMENT '排序',
  `created_at` INT(11)          NOT NULL DEFAULT 0
    COMMENT '创建时间',
  `updated_at` INT(11)          NOT NULL DEFAULT 0
    COMMENT '更新时间',
  PRIMARY KEY (`id`)
)
  ENGINE = innodb
  COMMENT = '新闻资讯表';

DROP TABLE IF EXISTS `zc_account_income`;
CREATE TABLE `zc_account_income`
(
  `id`                      INT(11) UNSIGNED        NOT NULL AUTO_INCREMENT,
  `user_id`                 INT(11) UNSIGNED        NOT NULL DEFAULT 0
    COMMENT '用户ID',
  `income_goldcoin_consume` DECIMAL(14, 4) UNSIGNED NOT NULL DEFAULT 0
    COMMENT '销售奖',
  `income_total`            DECIMAL(14, 4) UNSIGNED NOT NULL DEFAULT 0
    COMMENT '收益总计',
  `income_tag`              INT(10)                 NULL     DEFAULT 0
    COMMENT '标签：0(累计至昨天的统计数据), [year], [year][moth], [year][moth][day]',
  `income_uptime`           INT(11) UNSIGNED        NULL     DEFAULT 0
    COMMENT '更新时间',
  PRIMARY KEY (`id`)
)
  ENGINE = INNODB
  COMMENT = '用户收益统计表';

ALTER TABLE zc_address
  ADD `province` VARCHAR(50) NOT NULL DEFAULT ''
    COMMENT '省'
    AFTER `phone`;
ALTER TABLE zc_address
  ADD `city` VARCHAR(50) NOT NULL DEFAULT ''
    COMMENT '市'
    AFTER `province`;
ALTER TABLE zc_address
  ADD `country` VARCHAR(50) NOT NULL DEFAULT ''
    COMMENT '区'
    AFTER `city`;

ALTER TABLE zc_orders
  ADD `province` VARCHAR(50) NOT NULL DEFAULT ''
    COMMENT '省'
    AFTER `username`;
ALTER TABLE zc_orders
  ADD `city` VARCHAR(50) NOT NULL DEFAULT ''
    COMMENT '市'
    AFTER `province`;
ALTER TABLE zc_orders
  ADD `country` VARCHAR(50) NOT NULL DEFAULT ''
    COMMENT '区'
    AFTER `city`;

DROP TABLE IF EXISTS `zc_performance_bonus`;
CREATE TABLE `zc_performance_bonus`
(
  `id`                 INT(11) UNSIGNED        NOT NULL AUTO_INCREMENT,
  `performance_amount` DECIMAL(14, 4) UNSIGNED NOT NULL DEFAULT 0
    COMMENT '分红业绩',
  `total_amount`       DECIMAL(14, 4) UNSIGNED NOT NULL DEFAULT 0
    COMMENT '分红总金额',
  `agent_star`         TINYINT(2) UNSIGNED     NOT NULL DEFAULT 0
    COMMENT '分红个代级别',
  `agent_count`        INT(11) UNSIGNED        NOT NULL DEFAULT 0
    COMMENT '分红份数',
  `bonus_amount`       DECIMAL(14, 4) UNSIGNED NOT NULL DEFAULT 0
    COMMENT '实际分红金额',
  `tag`                INT(10)                 NULL     DEFAULT 0
    COMMENT '标签：[year][moth][day]',
  `updated_at`         INT(11)                 NOT NULL DEFAULT 0
    COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE `agent_tag` (`agent_star`, `tag`)
)
  ENGINE = innodb
  COMMENT = '分红记录表';


DROP TABLE IF EXISTS `zc_care_queue`;
CREATE TABLE `zc_care_queue`
(
  `id`              INT(11) UNSIGNED        NOT NULL AUTO_INCREMENT,
  `user_id`         INT(11) UNSIGNED        NOT NULL DEFAULT 0
    COMMENT '用户ID',
  `income_amount`   DECIMAL(14, 4) UNSIGNED NOT NULL DEFAULT 0
    COMMENT '收益金额',
  `queue_status`    TINYINT(1) UNSIGNED     NOT NULL DEFAULT 0
    COMMENT '队列状态：0未执行， 1执行中，2 已跳过， 3已完成',
  `queue_addtime`   INT(11) UNSIGNED        NOT NULL DEFAULT 0
    COMMENT '添加时间',
  `queue_starttime` INT(11) UNSIGNED        NOT NULL DEFAULT 0
    COMMENT '开始时间',
  `queue_endtime`   INT(11) UNSIGNED        NOT NULL DEFAULT 0
    COMMENT '结束时间',
  PRIMARY KEY (`id`),
  KEY (`user_id`),
  KEY (`queue_status`)
)
  ENGINE = innodb
  COMMENT = '关爱奖队列表';


ALTER TABLE zc_certification
  ADD `province` VARCHAR(50) NOT NULL DEFAULT ''
    COMMENT '省'
    AFTER `user_id`;
ALTER TABLE zc_certification
  ADD `city` VARCHAR(50) NOT NULL DEFAULT ''
    COMMENT '市'
    AFTER `province`;
ALTER TABLE zc_certification
  ADD `country` VARCHAR(50) NOT NULL DEFAULT ''
    COMMENT '区'
    AFTER `city`;

-- 修改zc_orders表中字段属性
ALTER TABLE zc_apply_service_center
  CHANGE `apply_status` `status` TINYINT(1) DEFAULT 0
    COMMENT '审核状态:0未审核，1审核通过，2审核不通过';

