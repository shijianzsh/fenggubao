-- -------------------------------
-- 支付完成队列表
-- -------------------------------
DROP TABLE IF EXISTS `zc_paid_queue`;
CREATE TABLE `zc_paid_queue`
(
    `id`              INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
    `order_id`        INT(11) UNIQUE      NOT NULL DEFAULT 0 COMMENT '订单ID',
    `queue_status`    TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '队列状态：0未执行， 1执行中，2 已跳过， 3已完成',
    `queue_addtime`   INT(11) UNSIGNED    NOT NULL DEFAULT 0 COMMENT '添加时间',
    `queue_starttime` INT(11) UNSIGNED    NOT NULL DEFAULT 0 COMMENT '开始时间',
    `queue_endtime`   INT(11) UNSIGNED    NOT NULL DEFAULT 0 COMMENT '结束时间',
    PRIMARY KEY (`id`),
    KEY (`queue_status`),
    KEY (`queue_addtime`),
    KEY (`queue_starttime`),
    KEY (`queue_endtime`)
)
    ENGINE = innodb
    COMMENT = '支付完成队列表';