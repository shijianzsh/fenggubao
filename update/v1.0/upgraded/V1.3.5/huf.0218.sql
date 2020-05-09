#  `管理津贴 30% ~ 55%` 改为 `统一50%，不分级别`
TRUNCATE TABLE `zc_consume_rule`;
INSERT INTO zc_consume_rule VALUE (NULL, 1, 1000, 50, 2, UNIX_TIMESTAMP());
INSERT INTO zc_consume_rule VALUE (NULL, 2, 10000, 50, 2, UNIX_TIMESTAMP());
INSERT INTO zc_consume_rule VALUE (NULL, 3, 30000, 50, 2, UNIX_TIMESTAMP());
INSERT INTO zc_consume_rule VALUE (NULL, 5, 50000, 50, 2, UNIX_TIMESTAMP());


# 增加动态出局
ALTER TABLE `zc_consume`
  ADD dynamic_out TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '动态收益是否出局' AFTER `is_out`;


INSERT INTO `zc_settings` VALUE (NULL, 7, '每日挖矿开始时间（24小时制）', 'mine_start_hour', '7', 'text', '时', 1, 373, UNIX_TIMESTAMP());
INSERT INTO `zc_settings` VALUE (NULL, 7, '每日挖矿结束时间（24小时制）', 'mine_end_hour', '24', 'text', '时', 1, 369, UNIX_TIMESTAMP());


UPDATE  `zc_settings` SET `settings_value` = '100' WHERE `settings_code` = 'mine_circulate_bai';
UPDATE  `zc_settings` SET `settings_value` = '0' WHERE `settings_code` = 'mine_lock_bai';
UPDATE  `zc_settings` SET `settings_value` = '12' WHERE `settings_code` = 'mine_machine_day_max_amount';