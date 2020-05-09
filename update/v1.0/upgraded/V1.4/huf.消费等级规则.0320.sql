TRUNCATE TABLE `zc_consume_rule`;
INSERT INTO `zc_consume_rule` VALUE (NULL, 0, 0, 50, 2, unix_timestamp());
INSERT INTO `zc_consume_rule` VALUE (NULL, 5, 30000, 50, 2, unix_timestamp());


UPDATE `zc_consume` SET `level` = 0;
UPDATE `zc_consume` SET `level` = 5 WHERE amount >= 30000;