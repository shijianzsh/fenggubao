INSERT INTO `zc_settings`
  VALUE (NULL, 6, '特殊收益账号（多个账号用英文逗号","分隔, 不能有空格）', 'special_income_users', '18583361888', 'text', '', 1, 496, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
  VALUE (NULL, 6, '特殊收益比例', 'special_income_bai', '3', 'text', '%', 1, 493, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
  VALUE (NULL, 6, '特殊收益进入流通资产比例', 'special_income_circulate_bai', '20', 'text', '%', 1, 489, UNIX_TIMESTAMP());
INSERT INTO `zc_settings`
  VALUE (NULL, 6, '特殊收益进入锁定资产比例', 'special_income_lock_bai', '80', 'text', '%', 1, 486, UNIX_TIMESTAMP());

INSERT INTO `zc_settings`
  VALUE (NULL, 7, '矿机计算说明', 'mine_machine_captions', '', 'textarea', '', 1, 376, UNIX_TIMESTAMP());