INSERT INTO `zc_settings_group` VALUES (7, '挖矿配置', 1, 300, UNIX_TIMESTAMP());

delete from `zc_settings` where group_id = 7;
INSERT INTO `zc_settings` VALUES (NULL, 7, '挖矿开关', 'mine_switch', '关闭', 'options', '开启,关闭', 1, 400, UNIX_TIMESTAMP());
INSERT INTO `zc_settings` VALUES (NULL, 7, '订单业绩流入矿池比例', 'mine_order_bai', '20', 'text', '%', 1, 399, UNIX_TIMESTAMP());
INSERT INTO `zc_settings` VALUES (NULL, 7, '每天矿池最大产出金额', 'mine_pool_max_amount', '20000', 'text', '公让宝', 1, 396, UNIX_TIMESTAMP());
INSERT INTO `zc_settings` VALUES (NULL, 7, '每天单个矿机最大产出金额', 'mine_machine_day_max_amount', '3', 'text', '公让宝', 1, 393, UNIX_TIMESTAMP());
INSERT INTO `zc_settings` VALUES (NULL, 7, '每次单个矿机最大产出金额', 'mine_machine_one_max_amount', '0.1', 'text', '公让宝', 1, 389, UNIX_TIMESTAMP());
INSERT INTO `zc_settings` VALUE (NULL, 7, '挖矿进入流通资产比例', 'mine_circulate_bai', '20', 'text', '%', 1, 386, UNIX_TIMESTAMP());
INSERT INTO `zc_settings` VALUE (NULL, 7, '挖矿进入锁定资产比例', 'mine_lock_bai', '80', 'text', '%', 1, 383, UNIX_TIMESTAMP());
INSERT INTO `zc_settings` VALUES (NULL, 7, '老矿机算力比例', 'mine_old_machine_bai', '50', 'text', '%', 1, 379, UNIX_TIMESTAMP());


ALTER TABLE zc_consume ADD `amount_old` DECIMAL (14,4) DEFAULT 0 COMMENT '内排期消费金额' AFTER amount ;
UPDATE zc_consume SET `amount_old` = `amount`;

-- -------------------------------------
-- 挖矿队列表
-- -------------------------------------
DROP TABLE IF EXISTS `zc_mining_queue`;
CREATE TABLE `zc_mining_queue` (
  `id`           INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `user_id`      INT(11) UNSIGNED    NOT NULL DEFAULT 0
  COMMENT '用户ID',
  `exec_time`    INT(11) UNSIGNED    NULL     DEFAULT 0
  COMMENT '执行时间',
  `is_expired`   TINYINT(1) UNSIGNED NULL     DEFAULT 0
  COMMENT '是否过期: 0 未过期, 1 已过期',
  `created_time` INT(11) UNSIGNED    NULL     DEFAULT 0
  COMMENT '加入时间',
  `updated_time` INT(11) UNSIGNED    NULL     DEFAULT 0
  COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY (`user_id`),
  KEY (`created_time`),
  KEY (`is_expired`)
)
  ENGINE = InnoDB
  AUTO_INCREMENT = 1
  COMMENT ='挖矿队列表';

-- -------------------------------------
-- 挖矿记录表
-- -------------------------------------
DROP TABLE IF EXISTS `zc_mining`;
CREATE TABLE `zc_mining` (
  `id`           INT(11) UNSIGNED        NOT NULL AUTO_INCREMENT,
  `user_id`      INT(11) UNSIGNED        NOT NULL DEFAULT 0
  COMMENT '用户ID',
  `amount`       DECIMAL(14, 4) UNSIGNED NULL     DEFAULT 0
  COMMENT '金额',
  `tag`          INT(8) UNSIGNED         NOT NULL DEFAULT 0
  COMMENT '记录标识：0 实时总数据，[year][moth][day]',
  `created_time` INT(11) UNSIGNED        NULL     DEFAULT 0
  COMMENT '创建时间',
  `updated_time` INT(11) UNSIGNED        NULL     DEFAULT 0
  COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE `user_tag` (`user_id`, `tag`)
)
  ENGINE = InnoDB
  AUTO_INCREMENT = 1
  COMMENT ='挖矿记录表';