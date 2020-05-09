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

-- ----------------------------
-- 初始化业绩规则
-- ----------------------------
ALTER TABLE `zc_performance_rule`
  ADD `rule_label` VARCHAR(10) NOT NULL DEFAULT ''
COMMENT '头衔'
  AFTER `rule_id`;

TRUNCATE TABLE `zc_performance_rule`;
INSERT INTO `zc_performance_rule` VALUE (1, '1星', 10, 5, 0, 0);
INSERT INTO `zc_performance_rule` VALUE (2, '2星', 20, 10, 0, 0);
INSERT INTO `zc_performance_rule` VALUE (3, '3星', 24, 20, 0, 0);
INSERT INTO `zc_performance_rule` VALUE (4, '4星', 28, 50, 0, 0);
INSERT INTO `zc_performance_rule` VALUE (5, '宝石', 32, 100, 1, 1);
INSERT INTO `zc_performance_rule` VALUE (6, '钻石', 34, 200, 2, 2);
INSERT INTO `zc_performance_rule` VALUE (7, '皇冠', 36, 500, 3, 3);
INSERT INTO `zc_performance_rule` VALUE (8, '荣尊', 38, 1000, 4, 4);
INSERT INTO `zc_performance_rule` VALUE (9, '帝王', 40, 2000, 5, 5);

-- ----------------------------
-- 代理专区消费赠送规则
-- ----------------------------
DROP TABLE IF EXISTS `zc_agent_rule`;
CREATE TABLE `zc_agent_rule` (
  `id`     INT(11) UNSIGNED        NOT NULL     AUTO_INCREMENT,
  `amount` DECIMAL(14, 4) UNSIGNED NOT NULL     DEFAULT 0
  COMMENT '订单金额',
  `rate`   DECIMAL(14, 4) UNSIGNED NOT NULL     DEFAULT 0
  COMMENT '赠送比例',
  PRIMARY KEY (`id`)
)
  ENGINE = InnoDB
  AUTO_INCREMENT = 1
  COMMENT ='代理专区消费赠送规则';

INSERT INTO `zc_agent_rule` VALUE (NULL, 500, 50);
INSERT INTO `zc_agent_rule` VALUE (NULL, 2000, 60);
INSERT INTO `zc_agent_rule` VALUE (NULL, 5000, 70);
INSERT INTO `zc_agent_rule` VALUE (NULL, 10000, 80);
INSERT INTO `zc_agent_rule` VALUE (NULL, 30000, 90);
INSERT INTO `zc_agent_rule` VALUE (NULL, 50000, 100);

TRUNCATE TABLE `zc_settings_group`;
TRUNCATE TABLE `zc_settings`;

INSERT INTO `zc_settings_group` VALUE (1, '奖项配置', 1, 900, UNIX_TIMESTAMP());

INSERT INTO `zc_settings`
  VALUE (NULL, 1, '锁定资产每日释放比例', 'release_lock_goldcoin_bai', '0.5', 'text', '%', 1, 199, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
  VALUE (NULL, 1, '开通服务网点赠送锁定资产金额', 'give_service_amount', '200000', 'text', '', 1, 196, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
  VALUE (NULL, 1, '代理销售奖比例（一代）', 'prize_agent_consume_bai_1', '18', 'text', '%', 1, 193, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
  VALUE (NULL, 1, '代理销售奖比例（二代）', 'prize_agent_consume_bai_2', '20', 'text', '%', 1, 189, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
  VALUE (NULL, 1, '服务网点销售奖比例（一代）', 'prize_service_consume_bai_1', '18', 'text', '%', 1, 183, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
  VALUE (NULL, 1, '服务网点销售奖比例（二代）', 'prize_service_consume_bai_2', '20', 'text', '%', 1, 186, UNIX_TIMESTAMP());

INSERT INTO `zc_settings_group` VALUE (2, '加权分红', 1, 800, UNIX_TIMESTAMP());

INSERT INTO `zc_settings`
  VALUE (NULL, 2, '加权分红开关', 'performance_bonus_switch', '开启', 'options', '开启,关闭', 1, 299, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
  VALUE (NULL, 2, 'V9每天平分当天总业绩的比例', 'performance_bonus_bai_9', '2', 'text', '%', 1, 296, UNIX_TIMESTAMP());

-- -------------------------------------
-- 公让宝实时价格表
-- 定时从第三方平台抓取
-- 结算时取最新一次价格数据
-- 此价格为 1公让宝 = ？人民币
-- 收益发放时需用实时价格换算成公让宝，再发放到公让宝流通资产中
-- 购买或申请身份赠送奖励时需用实时价格换算成公让宝，再发放到公让宝锁定资产中
-- -------------------------------------
DROP TABLE IF EXISTS `zc_goldcoin_prices`;
CREATE TABLE `zc_goldcoin_prices` (
  `id`     INT(11) UNSIGNED        NOT NULL     AUTO_INCREMENT,
  `amount` DECIMAL(14, 4) UNSIGNED NOT NULL     DEFAULT 0
  COMMENT '价格',
  `uptime` INT(11) UNSIGNED        NOT NULL     DEFAULT 0
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
CREATE TABLE `zc_lock` (
  `id`             INT(11) UNSIGNED        NOT NULL     AUTO_INCREMENT,
  `user_id`        INT(11) UNSIGNED        NOT NULL     DEFAULT 0
  COMMENT '用户ID',
  `total_amount`   DECIMAL(14, 4) UNSIGNED NOT NULL     DEFAULT 0
  COMMENT '总金额',
  `release_amount` DECIMAL(14, 4) UNSIGNED NOT NULL     DEFAULT 0
  COMMENT '已释放金额',
  `lock_amount`    DECIMAL(14, 4) UNSIGNED NOT NULL     DEFAULT 0
  COMMENT '未释放金额',
  `tag`            INT(8)                  NOT NULL     DEFAULT 0
  COMMENT '标签：0(实时数据), [year], [year][moth], [year][moth][day]',
  `uptime`         INT(11) UNSIGNED        NOT NULL     DEFAULT 0
  COMMENT '更新时间',
  PRIMARY KEY (`id`),
  INDEX `user_tag` (`user_id`, `tag`),
  INDEX (`uptime`)
)
  ENGINE = InnoDB
  AUTO_INCREMENT = 1
  COMMENT ='锁定资产表';

-- --------------------------------------
-- 锁定资产释放队列
-- --------------------------------------
DROP TABLE IF EXISTS `zc_lock_queue`;
CREATE TABLE `zc_lock_queue` (
  `id`             INT(11) UNSIGNED        NOT NULL     AUTO_INCREMENT,
  `user_id`        INT(11) UNSIGNED        NOT NULL     DEFAULT 0
  COMMENT '用户ID',
  `total_amount`   DECIMAL(14, 4) UNSIGNED NOT NULL     DEFAULT 0
  COMMENT '总金额',
  `release_amount` DECIMAL(14, 4) UNSIGNED NOT NULL     DEFAULT 0
  COMMENT '已释放金额',
  `release_rate`   DECIMAL(6, 2) UNSIGNED  NOT NULL     DEFAULT 0
  COMMENT '每次释放比例',
  `tag`            INT(8)                  NOT NULL     DEFAULT 0
  COMMENT '标签：[year][moth][day]',
  `remark`         VARCHAR(255)            NOT NULL     DEFAULT ''
  COMMENT '备注',
  `addtime`        INT(11) UNSIGNED        NOT NULL     DEFAULT 0
  COMMENT '加入时间',
  `uptime`         INT(11) UNSIGNED        NOT NULL     DEFAULT 0
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
CREATE TABLE `zc_news` (
  `id` INT ( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR ( 255 ) NOT NULL DEFAULT '' COMMENT '标题',
  `cover` VARCHAR ( 255 ) NOT NULL DEFAULT '' COMMENT '封面图',
  `content` TEXT NULL DEFAULT NULL COMMENT '内容',
  `sort` INT ( 11 ) NOT NULL DEFAULT 0 COMMENT '排序',
  `created_at` DATETIME NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` DATETIME NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY ( `id` )
) ENGINE = INNODB COMMENT = '新闻资讯表';










