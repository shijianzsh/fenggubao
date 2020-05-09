-- -------------------------------------
-- 业绩分配规则表
-- -------------------------------------
DROP TABLE IF EXISTS `zc_performance_rule`;
CREATE TABLE `zc_performance_rule` (
  `rule_id`              INT(11) UNSIGNED        NOT NULL AUTO_INCREMENT,
  `rule_bai`             decimal(14, 4) UNSIGNED NULL     DEFAULT 0
  COMMENT '分配比例（%）',
  `rule_amount`          decimal(14, 4) UNSIGNED NULL     DEFAULT 0
  COMMENT '指标（万）',
  `rule_condition_count` INT(11)     UNSIGNED    NOT NULL DEFAULT 0
  COMMENT '附加条件1：下线个数',
  `rule_condition_level` INT(11)     UNSIGNED    NOT NULL DEFAULT 0
  COMMENT '附加条件2：细线级别',
  PRIMARY KEY (`rule_id`),
  KEY `rule_bai` (`rule_bai`),
  KEY `rule_amount` (`rule_amount`)
)
  ENGINE = InnoDB
  AUTO_INCREMENT = 1
  COMMENT ='业绩分配规则表';

insert into zc_performance_rule values
  (null, 4, 0.1, 0, 0),
  (null, 6, 0.5, 0, 0),
  (null, 8, 3, 0, 0),
  (null, 10, 8, 0, 0),
  (null, 12, 20, 0, 0),
  (null, 14, 50, 0, 0),
  (null, 16, 100, 0, 0),
  (null, 18, 300, 0, 0),
  (null, 20, 1000, 0, 0);


