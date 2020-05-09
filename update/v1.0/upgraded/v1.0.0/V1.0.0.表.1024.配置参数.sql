truncate table `zc_settings_group`;
INSERT INTO `zc_settings_group` VALUES (1, '奖项配置', 1, 900, 1536368233);
INSERT INTO `zc_settings_group` VALUES (2, '大礼包区配置', 1, 800, 1536368233);
INSERT INTO `zc_settings_group` VALUES (3, '业绩配置', 1, 700, 1536368233);
INSERT INTO `zc_settings_group` VALUES (4, '提现配置', 1, 500, 1540541981);
INSERT INTO `zc_settings_group` VALUES (5, '广告配置', 1, 400, 1536368233);
INSERT INTO `zc_settings_group` VALUES (6, '店长配置', 1, 600, 1540541945);


truncate table `zc_settings`;

INSERT INTO `zc_settings`
VALUES (1, 1, '注册体验会员赠送兑换券金额', 'register_experience_give_goldcoin_amount', '100', 'text', '兑换券', 1, 899, 1536368434);
INSERT INTO `zc_settings`
VALUES (2, 1, '签到赠送兑换券金额', 'signin_give_goldcoin_amount', '10', 'text', '兑换券', 1, 889, 1536368434);
INSERT INTO `zc_settings` VALUES (3, 1, 'VIP会员直推奖比例', 'prize_direct_bai_2', '12', 'text', '%', 1, 879, 1540369506);
INSERT INTO `zc_settings`
VALUES (4, 1, '合伙人消费奖比例(直推人)', 'prize_partner_consume_bai', '0', 'text', '%', 2, 869, 1536369260);
INSERT INTO `zc_settings`
VALUES (5, 1, '店长消费奖比例(一代)', 'prize_service_consume_bai_1', '6', 'text', '%', 1, 866, 1536654332);
INSERT INTO `zc_settings`
VALUES (6, 1, '营运中心消费奖比例(直推人)', 'prize_company_consume_bai', '10', 'text', '%', 2, 863, 1536654317);
INSERT INTO `zc_settings`
VALUES (7, 1, '合伙人补贴奖比例(压缩查找上两级)', 'prize_partner_subsidy_bai', '0', 'text', '%', 2, 859, 1536369260);
INSERT INTO `zc_settings`
VALUES (8, 1, '店长补贴奖比例(压缩查找上两级)', 'prize_service_subsidy_bai', '1', 'text', '%', 2, 856, 1536369286);
INSERT INTO `zc_settings`
VALUES (9, 1, '营运中心补贴奖比例(压缩查找上两级)', 'prize_company_subsidy_bai', '2', 'text', '%', 2, 853, 1536369260);
INSERT INTO `zc_settings` VALUES (10, 1, '平台管理费比例', 'taxfee_fee', '0', 'text', '%', 1, 849, 1536369260);
INSERT INTO `zc_settings` VALUES (11, 1, '个人所得税比例', 'taxfee_tax', '6', 'text', '%', 1, 846, 1536369260);
INSERT INTO `zc_settings` VALUES (12, 2, 'VIP会员激活金额', 'buy_gift_amount_2', '380', 'text', '米宝', 1, 799, 1536637918);
INSERT INTO `zc_settings` VALUES (13, 2, '爱心创客激活金额', 'buy_gift_amount_5', '1888', 'text', '米宝', 2, 796, 1536637918);
INSERT INTO `zc_settings`
VALUES (14, 2, 'VIP会员购买大礼包区商品赠送积分比例', 'buy_gift_give_points_bai_2', '10', 'text', '%', 2, 789, 1536637918);
INSERT INTO `zc_settings`
VALUES (15, 2, '爱心创客购买大礼包区商品赠送积分比例', 'buy_gift_give_points_bai_5', '15', 'text', '%', 2, 786, 1536637918);
INSERT INTO `zc_settings`
VALUES (16, 2, 'VIP会员购买大礼包区商品赠送兑换券比例', 'buy_gift_give_goldcoin_bai_2', '100', 'text', '%', 1, 779, 1536637928);
INSERT INTO `zc_settings`
VALUES (17, 2, '爱心创客购买大礼包区商品赠送兑换券比例', 'buy_gift_give_goldcoin_bai_5', '100', 'text', '%', 2, 776, 1536637928);
INSERT INTO `zc_settings`
VALUES (18, 2, '购买大礼包区商品（非380商品）产生业绩比例', 'yeji_no_365_bai', '0', 'text', '%', 2, 769, 1540351537);
INSERT INTO `zc_settings` VALUES (19, 3, '（营运中心+合伙人）分红比例', 'bonus_bai_role', '2', 'text', '%', 2, 699, 1536369355);
INSERT INTO `zc_settings` VALUES (20, 3, '皇冠(V8)分红比例', 'bonus_bai_v8', '1', 'text', '%', 2, 689, 1536369355);
INSERT INTO `zc_settings` VALUES (21, 3, '爱心大使(V9)分红比例', 'bonus_bai_v9', '1', 'text', '%', 2, 679, 1536369355);
INSERT INTO `zc_settings` VALUES (22, 3, '爱心特使分红比例', 'bonus_bai_v10', '1', 'text', '%', 2, 669, 1536369355);
INSERT INTO `zc_settings` VALUES (23, 3, '爱心荣耀特使分红比例', 'bonus_bai_v11', '1', 'text', '%', 2, 659, 1536369355);
INSERT INTO `zc_settings` VALUES (70, 1, '爱心创客直推奖比例', 'prize_direct_bai_5', '15', 'text', '%', 2, 876, 1536368610);
INSERT INTO `zc_settings`
VALUES (71, 3, '业绩结算开关', 'performance_reward_switch', '关闭', 'options', '开启,关闭', 1, 723, 1536368434);
INSERT INTO `zc_settings`
VALUES (72, 3, '业绩结算频率', 'performance_reward_frequency', '每小时', 'options', '每月,每周,每天,每小时', 1, 719, 1536368434);
INSERT INTO `zc_settings`
VALUES (73, 3, '加权分红开关', 'performance_bonus_switch', '开启', 'options', '开启,关闭', 2, 713, 1536368434);
INSERT INTO `zc_settings`
VALUES (74, 3, '加权分红频率', 'performance_bonus_frequency', '每小时', 'options', '每月,每周,每天,每小时', 2, 709, 1536368434);
INSERT INTO `zc_settings`
VALUES (75, 4, '银行卡提现开关', 'withdraw_switch_bank', '关闭', 'options', '开启,关闭', 1, 499, 1536368434);
INSERT INTO `zc_settings`
VALUES (76, 4, '微信提现开关', 'withdraw_switch_wechat', '关闭', 'options', '开启,关闭', 1, 498, 1536368434);
INSERT INTO `zc_settings`
VALUES (77, 4, '支付宝提现开关', 'withdraw_wechat_alipay', '关闭', 'options', '开启,关闭', 1, 497, 1536368434);
INSERT INTO `zc_settings`
VALUES (78, 4, '用户提现最小金额', 'withdraw_user_amount_min', '100', 'text', '米宝', 1, 496, 1536368434);
INSERT INTO `zc_settings`
VALUES (79, 4, '用户提现增加倍数金额', 'withdraw_user_amount_bei', '100', 'text', '米宝', 1, 493, 1536368434);
INSERT INTO `zc_settings`
VALUES (80, 4, '用户银行卡提现手续费比例', 'withdraw_user_bank_fee', '2', 'text', '%', 1, 489, 1536368434);
INSERT INTO `zc_settings`
VALUES (81, 4, '用户微信提现手续费比例', 'withdraw_user_wechat_fee', '2', 'text', '%', 1, 486, 1536368434);
INSERT INTO `zc_settings`
VALUES (82, 4, '用户支付宝提现手续费比例', 'withdraw_user_alipay_fee', '2', 'text', '%', 1, 483, 1536368434);
INSERT INTO `zc_settings`
VALUES (83, 4, '商家提现最小金额', 'withdraw_merchant_amount_min', '1', 'text', '米宝', 1, 479, 1536368434);
INSERT INTO `zc_settings`
VALUES (84, 4, '商家提现增加倍数金额', 'withdraw_merchant_amount_bei', '1', 'text', '米宝', 1, 476, 1536368434);
INSERT INTO `zc_settings`
VALUES (85, 4, '商家银行卡提现手续费比例', 'withdraw_merchant_bank_fee', '1', 'text', '%', 1, 473, 1536368434);
INSERT INTO `zc_settings`
VALUES (86, 4, '商家微信提现手续费比例', 'withdraw_merchant_wechat_fee', '1', 'text', '%', 1, 469, 1536368434);
INSERT INTO `zc_settings`
VALUES (87, 4, '商家支付宝提现手续费比例', 'withdraw_merchant_alipay_fee', '1', 'text', '%', 1, 466, 1536368434);
INSERT INTO `zc_settings` VALUES
  (88, 4, '银行卡提现说明', 'withdraw_description_bank', '提现的金额将转账到你的银行卡帐号，请务必绑定正确的银行卡帐号；\r\n到帐时间为7个工作日内，请留意查看银行卡到帐信息',
   'textarea', '', 1, 463, 1536368434);
INSERT INTO `zc_settings` VALUES (89, 4, '银行卡提现规则', 'withdraw_rule_bank',
                                  '提现最低{%withdraw_amount_min%}，并且必须是{%withdraw_amount_bei%}的整数倍；银行卡手续费{%withdraw_bank_fee%}%；您目前有米宝{%cash%}',
                                  'textarea', '', 1, 459, 1536368434);
INSERT INTO `zc_settings` VALUES (90, 4, '微信提现说明', 'withdraw_description_wechat',
                                  '提现的金额将转账到你的微信帐号，请务必绑定正确的微信帐号；\r\n微信认证用户每日提现最高限额1万元；\r\n微信非认证用户或与APP账号姓名不一致的认证用户将无法提现至微信钱包；\r\n到帐时间为7个工作日内，请留意查看微信钱包',
                                  'textarea', '', 1, 456, 1536368434);
INSERT INTO `zc_settings` VALUES (91, 4, '微信提现规则', 'withdraw_rule_wechat',
                                  '提现最低{%withdraw_amount_min%}，并且必须是{%withdraw_amount_bei%}的整数倍；微信手续费{%withdraw_wechat_fee%}%；您目前有米宝{%cash%}',
                                  'textarea', '', 1, 453, 1536368434);
INSERT INTO `zc_settings` VALUES (92, 4, '支付宝提现说明', 'withdraw_description_alipay',
                                  '提现的金额将转账到你的支付宝帐号，请务必绑定正确的支付宝帐号；\r\n支付宝认证用户每日提现最高限额1万元；\r\n支付宝非认证用户或与APP账号姓名不一致的认证用户将无法提现至支付宝钱包；\r\n到帐时间为7个工作日内，请留意查看支付宝钱包',
                                  'textarea', '', 1, 449, 1536368434);
INSERT INTO `zc_settings` VALUES (93, 4, '支付宝提现规则', 'withdraw_rule_alipay',
                                  '提现最低{%withdraw_amount_min%}，并且必须是{%withdraw_amount_bei%}的整数倍；支付宝手续费{%withdraw_alipay_fee%}%；您目前有米宝{%cash%}',
                                  'textarea', '', 1, 446, 1536368434);
INSERT INTO `zc_settings` VALUES (94, 0, '不参与分红的合伙人', 'bonus_exclude_partner', NULL, 'text', '', 1, 99, 1538278381);
INSERT INTO `zc_settings` VALUES (95, 0, '不参与分红的营运中心', 'bonus_exclude_company', '572', 'text', '', 1, 96, 1538278387);
INSERT INTO `zc_settings` VALUES (191, 5, '广告开关', 'ad_switch', '关闭', 'options', '开启,关闭', 1, 599, 1536368434);
INSERT INTO `zc_settings` VALUES (192, 5, '关闭时的提示信息', 'ad_close_tip', '', 'textarea', '', 1, 596, 1536368434);
INSERT INTO `zc_settings` VALUES (193, 5, '每天可点广告的起始时间', 'ad_start', '6', 'text', '点(24小时制)', 1, 593, 1536368434);
INSERT INTO `zc_settings` VALUES (194, 5, '每天可点广告的结束时间', 'ad_end', '22', 'text', '点(24小时制)', 1, 590, 1536368434);
INSERT INTO `zc_settings` VALUES (195, 5, '每周可点击广告时间', 'ad_days', '', 'options', '工作日,周末,每天', 1, 589, 1536368434);
INSERT INTO `zc_settings`
VALUES (206, 5, '体验用户每日点击广告获得米宝封顶金额', 'ad_cash_max_1', '0', 'text', '米宝', 1, 586, 1536368434);
INSERT INTO `zc_settings`
VALUES (207, 5, '体验用户每日点击广告获得兑换券封顶金额', 'ad_goldcoin_max_1', '0', 'text', '兑换券', 1, 586, 1536368434);
INSERT INTO `zc_settings` VALUES (208, 5, '体验用户点击单个广告价格比例', 'ad_price_bai_1', '0', 'text', '%', 1, 586, 1536368434);
INSERT INTO `zc_settings` VALUES (209, 5, '体验用户点击单个广告获取米宝概率', 'ad_cash_bai_1', '0', 'text', '%', 1, 586, 1536368434);
INSERT INTO `zc_settings`
VALUES (210, 5, '体验用户点击单个广告获取兑换券概率', 'ad_goldcoin_bai_1', '0', 'text', '%', 1, 586, 1536368434);
INSERT INTO `zc_settings`
VALUES (211, 5, 'VIP会员每日点击广告获得米宝封顶金额', 'ad_cash_max_2', '0', 'text', '米宝', 1, 576, 1536368434);
INSERT INTO `zc_settings`
VALUES (212, 5, 'VIP会员每日点击广告获得兑换券封顶金额', 'ad_goldcoin_max_2', '0', 'text', '兑换券', 1, 576, 1536368434);
INSERT INTO `zc_settings` VALUES (213, 5, 'VIP会员点击单个广告价格比例', 'ad_price_bai_2', '0', 'text', '%', 1, 576, 1536368434);
INSERT INTO `zc_settings` VALUES (214, 5, 'VIP会员点击单个广告获取米宝概率', 'ad_cash_bai_2', '0', 'text', '%', 1, 576, 1536368434);
INSERT INTO `zc_settings`
VALUES (215, 5, 'VIP会员点击单个广告获取兑换券概率', 'ad_goldcoin_bai_2', '0', 'text', '%', 1, 576, 1536368434);
# INSERT INTO `zc_settings`
# VALUES (216, 5, '爱心创客每日点击广告获得米宝封顶金额', 'ad_cash_max_5', '0', 'text', '米宝', 1, 566, 1536368434);
# INSERT INTO `zc_settings`
# VALUES (217, 5, '爱心创客每日点击广告获得兑换券封顶金额', 'ad_goldcoin_max_5', '0', 'text', '兑换券', 1, 566, 1536368434);
# INSERT INTO `zc_settings` VALUES (218, 5, '爱心创客点击单个广告价格比例', 'ad_price_bai_5', '0', 'text', '%', 1, 566, 1536368434);
# INSERT INTO `zc_settings` VALUES (219, 5, '爱心创客点击单个广告获取米宝概率', 'ad_cash_bai_5', '0', 'text', '%', 1, 566, 1536368434);
# INSERT INTO `zc_settings`
# VALUES (220, 5, '爱心创客点击单个广告获取兑换券概率', 'ad_goldcoin_bai_5', '0', 'text', '%', 1, 566, 1536368434);
# INSERT INTO `zc_settings`
# VALUES (221, 5, '合伙人每日点击广告获得米宝封顶金额', 'ad_cash_max_partner', '0', 'text', '米宝', 1, 556, 1536368434);
# INSERT INTO `zc_settings`
# VALUES (222, 5, '合伙人每日点击广告获得兑换券封顶金额', 'ad_goldcoin_max_partner', '0', 'text', '兑换券', 1, 556, 1536368434);
# INSERT INTO `zc_settings`
# VALUES (223, 5, '合伙人点击单个广告价格比例', 'ad_price_bai_partner', '0', 'text', '%', 1, 556, 1536368434);
# INSERT INTO `zc_settings`
# VALUES (224, 5, '合伙人点击单个广告获取米宝概率', 'ad_cash_bai_partner', '0', 'text', '%', 1, 556, 1536368434);
# INSERT INTO `zc_settings`
# VALUES (225, 5, '合伙人点击单个广告获取兑换券概率', 'ad_goldcoin_bai_partner', '0', 'text', '%', 1, 556, 1536368434);
INSERT INTO `zc_settings`
VALUES (226, 5, '店长每日点击广告获得米宝封顶金额', 'ad_cash_max_service', '0', 'text', '米宝', 1, 546, 1536368434);
INSERT INTO `zc_settings`
VALUES (227, 5, '店长每日点击广告获得兑换券封顶金额', 'ad_goldcoin_max_service', '0', 'text', '兑换券', 1, 546, 1536368434);
INSERT INTO `zc_settings`
VALUES (228, 5, '店长点击单个广告价格比例', 'ad_price_bai_service', '0', 'text', '%', 1, 546, 1536368434);
INSERT INTO `zc_settings`
VALUES (229, 5, '店长点击单个广告获取米宝概率', 'ad_cash_bai_service', '0', 'text', '%', 1, 546, 1536368434);
INSERT INTO `zc_settings`
VALUES (230, 5, '店长点击单个广告获取兑换券概率', 'ad_goldcoin_bai_service', '0', 'text', '%', 1, 546, 1536368434);
# INSERT INTO `zc_settings`
# VALUES (231, 5, '营运中心每日点击广告获得米宝封顶金额', 'ad_cash_max_company', '0', 'text', '米宝', 1, 536, 1536368434);
# INSERT INTO `zc_settings`
# VALUES (232, 5, '营运中心每日点击广告获得兑换券封顶金额', 'ad_goldcoin_max_company', '0', 'text', '兑换券', 1, 536, 1536368434);
# INSERT INTO `zc_settings`
# VALUES (233, 5, '营运中心点击单个广告价格比例', 'ad_price_bai_company', '0', 'text', '%', 1, 536, 1536368434);
# INSERT INTO `zc_settings`
# VALUES (234, 5, '营运中心点击单个广告获取米宝概率', 'ad_cash_bai_company', '0', 'text', '%', 1, 536, 1536368434);
# INSERT INTO `zc_settings`
# VALUES (235, 5, '营运中心点击单个广告获取兑换券概率', 'ad_goldcoin_bai_company', '0', 'text', '%', 1, 536, 1536368434);
INSERT INTO `zc_settings`
VALUES (236, 1, '店长消费奖比例(二代)', 'prize_service_consume_bai_2', '3', 'text', '%', 1, 865, 1540286085);
INSERT INTO `zc_settings`
VALUES (237, 1, '店长消费奖比例(三代)', 'prize_service_consume_bai_3', '3', 'text', '%', 1, 864, 1540286099);
INSERT INTO `zc_settings`
VALUES (335, 1, '店长消费奖基础比例（取订单业绩的X%）', 'prize_service_consume_bai', '70', 'text', '%', 1, 867, 1540369379);
INSERT INTO `zc_settings`
VALUES (336, 3, '业绩计算比例（取订单兑换券的X%）', 'performance_bai_order_goldcoin', '50', 'text', '%', 1, 718, 1540369350);
INSERT INTO `zc_settings`
VALUES (370, 6, '一星店长晋升条件（旗下累计X位店长）', 'service_star_condition_1', '30', 'text', '位店长', 1, 699, 1540542718);
INSERT INTO `zc_settings`
VALUES (371, 6, '一星店长补贴比例（新增业绩的X%）', 'service_star_subsidy_1', '0.5', 'text', '%', 1, 696, 1540542757);
INSERT INTO `zc_settings`
VALUES (372, 6, '二星店长晋升条件（旗下累计X位店长）', 'service_star_condition_2', '80', 'text', '位店长', 1, 693, 1540542718);
INSERT INTO `zc_settings`
VALUES (373, 6, '二星店长补贴比例（新增业绩的X%）', 'service_star_subsidy_2', '1', 'text', '%', 1, 689, 1540542757);
INSERT INTO `zc_settings`
VALUES (374, 6, '三星店长晋升条件（旗下累计X位店长）', 'service_star_condition_3', '200', 'text', '位店长', 1, 686, 1540542718);
INSERT INTO `zc_settings`
VALUES (375, 6, '三星店长补贴比例（新增业绩的X%）', 'service_star_subsidy_3', '1.5', 'text', '%', 1, 683, 1540542757);
INSERT INTO `zc_settings`
VALUES (376, 6, '五星店长晋升条件（旗下累计X位店长）', 'service_star_condition_5', '500', 'text', '位店长', 1, 679, 1540542718);
INSERT INTO `zc_settings`
VALUES (377, 6, '五星店长补贴比例（新增业绩的X%）', 'service_star_subsidy_5', '2', 'text', '%', 1, 676, 1540542757);
