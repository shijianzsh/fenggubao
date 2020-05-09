# 增加矿机分段统计字段
ALTER TABLE `zc_consume`
  ADD machine_amount_1 DECIMAL(14, 2) UNSIGNED NOT NULL DEFAULT 0 COMMENT '第1时间段产生的矿机（内排期）';
ALTER TABLE `zc_consume`
  ADD machine_amount_2 DECIMAL(14, 2) UNSIGNED NOT NULL DEFAULT 0 COMMENT '第2时间段产生的矿机';
ALTER TABLE `zc_consume`
  ADD machine_amount_3 DECIMAL(14, 2) UNSIGNED NOT NULL DEFAULT 0 COMMENT '第3时间段产生的矿机';
ALTER TABLE `zc_consume`
  ADD machine_amount_4 DECIMAL(14, 2) UNSIGNED NOT NULL DEFAULT 0 COMMENT '第4时间段产生的矿机';
ALTER TABLE `zc_consume`
  ADD machine_amount_5 DECIMAL(14, 2) UNSIGNED NOT NULL DEFAULT 0 COMMENT '第5时间段产生的矿机';
ALTER TABLE `zc_consume`
  ADD machine_amount_uptime INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '矿机批量统计时间';



INSERT INTO `zc_settings` VALUE (NULL, 7, '第 1 时间段矿机结束日期（不包含）', 'mine_machine_end_1', '2019-01-01', 'text', '内排期，固定时间，不要修改', 1, 359, UNIX_TIMESTAMP());
INSERT INTO `zc_settings` VALUE (NULL, 7, '第 1 时间段矿机每天单台最大产出金额', 'mine_machine_day_max_amount_1', '8', 'text', '公让宝', 1, 356, UNIX_TIMESTAMP());
INSERT INTO `zc_settings` VALUE (NULL, 7, '第 2 时间段矿机结束日期（不包含）', 'mine_machine_end_2', '2019-04-01', 'text', '以前, 0 表示不设置', 1, 353, UNIX_TIMESTAMP());
INSERT INTO `zc_settings` VALUE (NULL, 7, '第 2 时间段矿机每天单台最大产出金额', 'mine_machine_day_max_amount_2', '8', 'text', '公让宝', 1, 349, UNIX_TIMESTAMP());
INSERT INTO `zc_settings` VALUE (NULL, 7, '第 3 时间段矿机结束日期（不包含）', 'mine_machine_end_3', '2029-04-01', 'text', '以前, 0 表示不设置', 1, 346, UNIX_TIMESTAMP());
INSERT INTO `zc_settings` VALUE (NULL, 7, '第 3 时间段矿机每天单台最大产出金额', 'mine_machine_day_max_amount_3', '20', 'text', '公让宝', 1, 343, UNIX_TIMESTAMP());
INSERT INTO `zc_settings` VALUE (NULL, 7, '第 4 时间段矿机结束日期（不包含）', 'mine_machine_end_4', '0', 'text', '以前, 0 表示不设置', 1, 339, UNIX_TIMESTAMP());
INSERT INTO `zc_settings` VALUE (NULL, 7, '第 4 时间段矿机每天单台最大产出金额', 'mine_machine_day_max_amount_4', '0', 'text', '公让宝', 1, 336, UNIX_TIMESTAMP());
INSERT INTO `zc_settings` VALUE (NULL, 7, '第 5 时间段矿机结束日期（不包含）', 'mine_machine_end_5', '0', 'text', '以前, 0 表示不设置', 1, 333, UNIX_TIMESTAMP());
INSERT INTO `zc_settings` VALUE (NULL, 7, '第 5 时间段矿机每天单台最大产出金额', 'mine_machine_day_max_amount_5', '0', 'text', '公让宝', 1, 329, UNIX_TIMESTAMP());
INSERT INTO `zc_settings` VALUE (NULL, 7, '后台充值矿机每天单台最大产出金额', 'mine_machine_day_max_amount_0', '8', 'text', '公让宝', 1, 326, UNIX_TIMESTAMP());