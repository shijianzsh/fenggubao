alter table `zc_account_income`
  add `income_cash_recommend_service` decimal(14, 4) not null default 0
comment '店长直推奖'
  after `income_cash_adview`;

alter table `zc_account_income`
  add `income_goldcoin_service_give` decimal(14, 4) not null default 0
comment '申请店长赠送'
  after `income_goldcoin_adview`;
