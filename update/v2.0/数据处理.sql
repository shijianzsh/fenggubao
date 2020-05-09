CREATE TABLE `zc_orders_20191230` LIKE `zc_orders`;

INSERT IGNORE INTO `zc_orders_20191230`
SELECT *
FROM zc_orders;

DELETE
FROM `zc_orders`
WHERE `time` < unix_timestamp('2019-12-27');


CREATE TABLE `zc_consume_bak_20191230` LIKE `zc_consume_bak`;
INSERT IGNORE INTO `zc_consume_bak_20191230`
SELECT *
FROM zc_consume_bak;

DELETE
FROM `zc_consume_bak`
WHERE `uptime` < unix_timestamp('2019-12-27');


UPDATE zc_member SET star = 0;