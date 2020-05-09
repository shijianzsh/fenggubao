INSERT INTO `zc_settings` VALUES (NULL, 6, '申请店长产生业绩（拨比）金额', 'apply_service_performance', '300', 'text', '元', 1, 698, 1540542718);

DELETE FROM zc_settings
WHERE settings_code IN ('performance_reward_switch', 'performance_reward_frequency');

ALTER TABLE zc_performance_queue MODIFY order_id INT(11) not null DEFAULT  0;
ALTER TABLE zc_performance_queue DROP KEY `order_id`;