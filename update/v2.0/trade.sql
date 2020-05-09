ALTER TABLE `zc_trade`
    ADD `third_amount` DECIMAL(14, 4) NOT NULL DEFAULT 0 COMMENT '转出到第三方金额' AFTER `amount`;
ALTER TABLE `zc_trade`
    ADD `explain` VARCHAR(1000) NOT NULL DEFAULT '' COMMENT '交易说明' AFTER `remark`;


INSERT INTO `zc_settings`
VALUES (NULL, 5, 'GRB汇率', 'zhongwy_trade_grb_rate', '0.1501', 'text', '元/个', 1, 595, UNIX_TIMESTAMP());



set @incomeMinAmount = 0;
set @incomeMaxAmount = 10000;
set @incomeBei = 2;
SELECT id, user_id, sum(income_amount - amount * @incomeBei)
FROM zc_consume_bak
WHERE amount >= @incomeMinAmount
  AND amount < @incomeMaxAmount
  AND amount * @incomeBei < income_amount;

set @incomeMinAmount = 10000;
set @incomeMaxAmount = 30000;
set @incomeBei = 2.5;
SELECT id, user_id, sum(income_amount - amount * @incomeBei)
FROM zc_consume_bak
WHERE amount >= @incomeMinAmount
  AND amount < @incomeMaxAmount
  AND amount * @incomeBei < income_amount;

set @incomeMinAmount = 30000;
set @incomeMaxAmount = 50000;
set @incomeBei = 3;
SELECT id, user_id, sum(income_amount - amount * @incomeBei)
FROM zc_consume_bak
WHERE amount >= @incomeMinAmount
  AND amount < @incomeMaxAmount
  AND amount * @incomeBei < income_amount;

set @incomeMinAmount = 50000;
set @incomeMaxAmount = 100000;
set @incomeBei = 3.5;
SELECT id, user_id, sum(income_amount - amount * @incomeBei)
FROM zc_consume_bak
WHERE amount >= @incomeMinAmount
  AND amount < @incomeMaxAmount
  AND amount * @incomeBei < income_amount;

set @incomeMinAmount = 100000;
set @incomeMaxAmount = 1000000000;
set @incomeBei = 4;
SELECT id, user_id, sum(income_amount - amount * @incomeBei)
FROM zc_consume_bak
WHERE amount >= @incomeMinAmount
  AND amount < @incomeMaxAmount
  AND amount * @incomeBei < income_amount;