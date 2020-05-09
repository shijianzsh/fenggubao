-- -------------------------------------
-- 收益统计表
-- -------------------------------------
DROP TABLE IF EXISTS `zc_account_income`;
CREATE TABLE `zc_account_income` (
  `income_id`    INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `user_id`      INT(11) UNSIGNED    NOT NULL DEFAULT 0
  COMMENT '用户ID',
  `income_goldcoin_consume` DECIMAL (14,4) UNSIGNED NULL DEFAULT 0
  COMMENT '销售奖（流）',
  `income_goldcoin_care` DECIMAL (14,4) UNSIGNED NULL DEFAULT 0
  COMMENT '关爱奖（流）',
  `income_goldcoin_subsidy` DECIMAL (14,4) UNSIGNED NULL DEFAULT 0
  COMMENT '管理津贴（流）',
  `income_goldcoin_bonus` DECIMAL (14,4) UNSIGNED NULL DEFAULT 0
  COMMENT '加权分红（流）',
  `income_goldcoin_county` DECIMAL (14,4) UNSIGNED NULL DEFAULT 0
  COMMENT '区域合伙人奖（流）',
  `income_goldcoin_province_see` DECIMAL (14,4) UNSIGNED NULL DEFAULT 0
  COMMENT '省级合伙人见点奖（流）',
  `income_goldcoin_province` DECIMAL (14,4) UNSIGNED NULL DEFAULT 0
  COMMENT '省级合伙人奖（流）',
  `income_goldcoin_mining` DECIMAL (14,4) UNSIGNED NULL DEFAULT 0
  COMMENT '挖矿（流）',
  `income_goldcoin_give` DECIMAL (14,4) UNSIGNED NULL DEFAULT 0
  COMMENT '消费赠送（流）',
  `income_goldcoin_total` DECIMAL (14,4) UNSIGNED NULL DEFAULT 0
  COMMENT '总流',
  `income_bonus_consume` DECIMAL (14,4) UNSIGNED NULL DEFAULT 0
  COMMENT '销售奖（锁）',
  `income_bonus_care` DECIMAL (14,4) UNSIGNED NULL DEFAULT 0
  COMMENT '关爱奖（锁）',
  `income_bonus_subsidy` DECIMAL (14,4) UNSIGNED NULL DEFAULT 0
  COMMENT '管理津贴（锁）',
  `income_bonus_bonus` DECIMAL (14,4) UNSIGNED NULL DEFAULT 0
  COMMENT '加权分红（锁）',
  `income_bonus_county` DECIMAL (14,4) UNSIGNED NULL DEFAULT 0
  COMMENT '区域合伙人奖（锁）',
  `income_bonus_province` DECIMAL (14,4) UNSIGNED NULL DEFAULT 0
  COMMENT '省级合伙人奖（锁）',
  `income_bonus_province_see` DECIMAL (14,4) UNSIGNED NULL DEFAULT 0
  COMMENT '省级合伙人见点奖（锁）',
  `income_bonus_mining` DECIMAL (14,4) UNSIGNED NULL DEFAULT 0
  COMMENT '挖矿（锁）',
  `income_bonus_give` DECIMAL (14,4) UNSIGNED NULL DEFAULT 0
  COMMENT '消费赠送（锁）',
  `income_bonus_total` DECIMAL (14,4) UNSIGNED NULL DEFAULT 0
  COMMENT '总锁',
  `income_total` DECIMAL (14,4) UNSIGNED NULL DEFAULT 0
  COMMENT '总收益',
  `income_tag` INT(11) UNSIGNED    NULL     DEFAULT 0
  COMMENT '标签：0(累计至昨天的统计数据), [year], [year][moth], [year][moth][day]',
  `income_uptime` INT(11) UNSIGNED    NULL     DEFAULT 0
  COMMENT '更新时间',
  PRIMARY KEY (`income_id`),
  unique  `user_tag`(`user_id`, `income_tag`),
  KEY (`income_uptime`)
)
  ENGINE = InnoDB
  AUTO_INCREMENT = 1
  COMMENT ='收益统计表';