-- --------------------------------------
-- 交易申请记录表
-- --------------------------------------
DROP TABLE IF EXISTS `zc_trade`;
CREATE TABLE `zc_trade` (
  `id`             INT(11) UNSIGNED        NOT NULL     AUTO_INCREMENT,
  `user_id`        INT(11) UNSIGNED        NOT NULL     DEFAULT 0
  COMMENT '用户ID',
  `amount`         DECIMAL(14, 4) UNSIGNED NOT NULL     DEFAULT 0
  COMMENT '金额',
  `fee`            DECIMAL(14, 4) UNSIGNED NOT NULL     DEFAULT 0
  COMMENT '交易费',
  `balance`        DECIMAL(14, 4) UNSIGNED NOT NULL     DEFAULT 0
  COMMENT '停留值',
  `wallet_address` VARCHAR(255)            NOT NULL     DEFAULT ''
  COMMENT '中网链公让宝钱包地址',
  `status`         TINYINT(1)              NOT NULL     DEFAULT 0
  COMMENT '状态：0 待审核，1 驳回，2 提交失败, 3 提交成功',
  `txid`           VARCHAR(255)            NOT NULL     DEFAULT ''
  COMMENT '交易号',
  `remark`         VARCHAR(255)            NOT NULL     DEFAULT ''
  COMMENT '备注',
  `addtime`        INT(11) UNSIGNED        NOT NULL     DEFAULT 0
  COMMENT '加入时间',
  `uptime`         INT(11) UNSIGNED        NOT NULL     DEFAULT 0
  COMMENT '审核时间',
  PRIMARY KEY (`id`),
  INDEX (`user_id`),
  INDEX (`addtime`),
  INDEX (`uptime`)
)
  ENGINE = InnoDB
  AUTO_INCREMENT = 1
  COMMENT ='交易申请记录表';