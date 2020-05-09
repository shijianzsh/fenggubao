TRUNCATE TABLE `zc_consume_rule`;
INSERT INTO zc_consume_rule VALUE (NULL, 1, 1000, 30, 1.2, UNIX_TIMESTAMP());
INSERT INTO zc_consume_rule VALUE (NULL, 2, 10000, 40, 1.4, UNIX_TIMESTAMP());
INSERT INTO zc_consume_rule VALUE (NULL, 3, 30000, 50, 1.6, UNIX_TIMESTAMP());
INSERT INTO zc_consume_rule VALUE (NULL, 5, 50000, 55, 2, UNIX_TIMESTAMP());

-- ----------------------------
-- 初始化业绩规则
-- ----------------------------
TRUNCATE TABLE `zc_performance_rule`;
INSERT INTO `zc_performance_rule` VALUE (1, '1星', 0, 5, 0, 0);
INSERT INTO `zc_performance_rule` VALUE (2, '2星', 0, 10, 0, 0);
INSERT INTO `zc_performance_rule` VALUE (3, '3星', 0, 20, 0, 0);
INSERT INTO `zc_performance_rule` VALUE (4, '白金', 0, 50, 3, 1);
INSERT INTO `zc_performance_rule` VALUE (5, '宝石', 0, 100, 3, 2);
INSERT INTO `zc_performance_rule` VALUE (6, '钻石', 0, 200, 4, 3);
INSERT INTO `zc_performance_rule` VALUE (7, '皇冠', 0, 500, 4, 4);
INSERT INTO `zc_performance_rule` VALUE (8, '荣尊', 0, 1000, 5, 5);
INSERT INTO `zc_performance_rule` VALUE (9, '至尊', 0, 5000, 5, 6);


TRUNCATE TABLE `zc_settings_group`;
TRUNCATE TABLE `zc_settings`;

INSERT INTO `zc_settings_group` VALUE (1, '奖项配置', 1, 900, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
  VALUE (NULL, 1, '代理销售奖比例（一代）', 'prize_agent_consume_bai_1', '10', 'text', '%', 1, 199, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
  VALUE (NULL, 1, '代理销售奖进入流通资产比例', 'prize_agent_consume_circulate_bai_1', '20', 'text', '%', 1, 198, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
  VALUE (NULL, 1, '代理销售奖进入锁定资产比例', 'prize_agent_consume_lock_bai_1', '80', 'text', '%', 1, 197, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
  VALUE (NULL, 1, '代理销售奖比例（二代）', 'prize_agent_consume_bai_2', '10', 'text', '%', 1, 196, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
  VALUE
  (NULL, 1, '代理销售奖进入流通资产比例（二代）', 'prize_agent_consume_circulate_bai_2', '20', 'text', '%', 1, 195, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
  VALUE (NULL, 1, '代理销售奖进入锁定资产比例（二代）', 'prize_agent_consume_lock_bai_2', '80', 'text', '%', 1, 194, UNIX_TIMESTAMP());
# INSERT INTO `zc_settings`
#   VALUE (NULL, 1, '管理津贴比例', 'subsidy_agent_bai', '50', 'text', '%', 1, 193, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
  VALUE (NULL, 1, '管理津贴进入流通资产比例', 'subsidy_agent_circulate_bai', '20', 'text', '%', 1, 192, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
  VALUE (NULL, 1, '管理津贴进入锁定资产比例', 'subsidy_agent_lock_bai', '80', 'text', '%', 1, 191, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
  VALUE (NULL, 1, '关爱奖比例', 'prize_care_agent_bai', '50', 'text', '%', 1, 189, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
  VALUE (NULL, 1, '关爱奖进入流通资产比例', 'prize_care_agent_circulate_bai', '20', 'text', '%', 1, 188, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
  VALUE (NULL, 1, '关爱奖进入锁定资产比例', 'prize_care_agent_lock_bai', '80', 'text', '%', 1, 187, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
  VALUE (NULL, 1, '区域合伙人奖比例', 'prize_county_service_bai', '5', 'text', '%', 1, 186, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
  VALUE (NULL, 1, '区域合伙人奖进入流通资产比例', 'prize_county_service_circulate_bai', '20', 'text', '%', 1, 185, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
  VALUE (NULL, 1, '区域合伙人奖进入锁定资产比例', 'prize_county_service_lock_bai', '80', 'text', '%', 1, 184, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
  VALUE (NULL, 1, '省级合伙人奖比例', 'prize_province_service_bai', '5', 'text', '%', 1, 183, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
  VALUE
  (NULL, 1, '省级合伙人奖进入流通资产比例', 'prize_province_service_circulate_bai', '20', 'text', '%', 1, 182, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
  VALUE (NULL, 1, '省级合伙人奖进入锁定资产比例', 'prize_province_service_lock_bai', '80', 'text', '%', 1, 181, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
  VALUE (NULL, 1, '省级合伙人见点奖比例', 'prize_province_service_see_bai', '1', 'text', '%', 1, 179, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
  VALUE (NULL, 1, '省级合伙人见点奖进入流通资产比例', 'prize_province_service_see_circulate_bai', '20', 'text', '%', 1, 178,
         UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
  VALUE
  (NULL, 1, '省级合伙人见点奖进入锁定资产比例', 'prize_province_service_see_lock_bai', '80', 'text', '%', 1, 176, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
  VALUE (NULL, 1, '手动分红进入流通资产比例', 'bonus_circulate_bai', '20', 'text', '%', 1, 173, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
  VALUE (NULL, 1, '手动分红进入锁定资产比例', 'bonus_lock_bai', '80', 'text', '%', 1, 172, UNIX_TIMESTAMP());

INSERT INTO `zc_settings_group` VALUE (2, '代理专区配置', 1, 800, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
  VALUE (NULL, 2, '代理专区消费增送公让宝倍数', 'give_goldcoin_bei', '2', 'text', '倍', 1, 299, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
  VALUE (NULL, 2, '代理专区消费增送进入流通资产比例', 'give_goldcoin_circulate_bai', '20', 'text', '%', 1, 296, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
  VALUE (NULL, 2, '代理专区消费增送进入锁定资产比例', 'give_goldcoin_lock_bai', '80', 'text', '%', 1, 293, UNIX_TIMESTAMP());

INSERT INTO `zc_settings_group` VALUE (3, '业绩配置', 1, 700, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
  VALUE (NULL, 3, '业绩份额计算基数（如：1000元1份）', 'performance_portion_base', '1000', 'text', '元/份', 1, 399, UNIX_TIMESTAMP());


INSERT INTO `zc_settings_group` VALUE (4, '锁定资产配置', 1, 600, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
  VALUE (NULL, 4, '锁定资产释放开关', 'goldcoin_release_switch', '关闭', 'options', '开启,关闭', 1, 499, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
  VALUE (NULL, 4, '锁定资产每日释放比例', 'goldcoin_release_bai', '0.5', 'text', '%', 1, 496, UNIX_TIMESTAMP());

INSERT INTO `zc_settings_group` VALUE (5, '中网云对接配置', 1, 500, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
  VALUE (NULL, 5, '对接开关', 'zhongwy_switch', '关闭', 'options', '开启,关闭', 1, 599, UNIX_TIMESTAMP());


TRUNCATE TABLE `zc_goldcoin_prices`;
INSERT INTO `zc_goldcoin_prices` VALUE (NULL, 2, unix_timestamp());