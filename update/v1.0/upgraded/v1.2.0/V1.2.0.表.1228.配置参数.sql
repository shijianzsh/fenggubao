INSERT INTO `zc_settings`
  VALUE (NULL, 5, '每人每天交易最大金额', 'zhongwy_trade_max', '50000', 'text', '', 1, 596, UNIX_TIMESTAMP());


INSERT INTO `zc_settings_group` VALUE (6, '特殊配置', 1, 400, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
  VALUE (NULL, 6, '禁止产生合伙人收益账号（多个账号用英文逗号","分隔, 不能有空格）', 'special_disable_role_income', '', 'text', '17781423139', 1, 499, UNIX_TIMESTAMP());