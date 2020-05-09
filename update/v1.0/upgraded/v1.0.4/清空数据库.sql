DROP TABLE IF EXISTS zc_account_cash_201810;
DROP TABLE IF EXISTS zc_account_goldcoin_201810;
DROP TABLE IF EXISTS zc_account_points_201810;
DROP TABLE IF EXISTS `zc_performance_201810`;

TRUNCATE TABLE `zc_account`;
TRUNCATE TABLE `zc_account_cash_201812`;
TRUNCATE TABLE `zc_account_goldcoin_201812`;
TRUNCATE TABLE `zc_orders`;
TRUNCATE TABLE `zc_orders_pay_info`;
TRUNCATE TABLE `zc_order_affiliate`;
TRUNCATE TABLE `zc_order_product`;
TRUNCATE TABLE `zc_order_cancel`;
TRUNCATE TABLE `zc_performance`;
TRUNCATE TABLE `zc_performance_201812`;
TRUNCATE TABLE `zc_performance_queue`;
TRUNCATE TABLE `zc_member_account`;
TRUNCATE TABLE `zc_login`;
TRUNCATE TABLE `zc_apply_service_center`;
TRUNCATE TABLE `zc_account_checkin`;
TRUNCATE TABLE `zc_account_income`;
TRUNCATE TABLE `zc_ad`;
TRUNCATE TABLE `zc_ad_view`;
TRUNCATE TABLE `zc_feedback`;
TRUNCATE TABLE `zc_frozen_fund`;
TRUNCATE TABLE `zc_log`;
TRUNCATE TABLE `zc_performance`;
TRUNCATE TABLE `zc_performance_201812`;
TRUNCATE TABLE `zc_performance_bonus_record`;
TRUNCATE TABLE `zc_performance_bonus_task`;
TRUNCATE TABLE `zc_performance_queue`;
TRUNCATE TABLE `zc_performance_reward_record`;
TRUNCATE TABLE `zc_performance_reward_task`;
TRUNCATE TABLE `zc_phonecode`;
TRUNCATE TABLE `zc_preferential_way`;

TRUNCATE TABLE `zc_push_queue`;
TRUNCATE TABLE `zc_shake_log`;
TRUNCATE TABLE `zc_shopping_cart`;
TRUNCATE TABLE `zc_timer_task`;
TRUNCATE TABLE `zc_withdraw_bankcard`;
TRUNCATE TABLE `zc_withdraw_cash`;

TRUNCATE TABLE `zc_consume`;
TRUNCATE TABLE `zc_lock`;
TRUNCATE TABLE `zc_lock_queue`;
TRUNCATE TABLE `zc_performance_bonus`;
TRUNCATE TABLE `zc_care_queue`;

TRUNCATE TABLE `zc_address`;
# TRUNCATE TABLE `zc_product`;
# TRUNCATE TABLE `zc_product_affiliate`;
# TRUNCATE TABLE `zc_product_comment`;
# TRUNCATE TABLE `zc_product_price`;
TRUNCATE TABLE `zc_certification`;

DELETE FROM zc_member
WHERE id NOT IN (1, 437, 709, 712, 921, 922);
UPDATE zc_member
SET `level` = 1, star = 0, role = 0, province = '', city = '', country = '';

# DELETE FROM zc_manager WHERE uid > 922;

# -- 超级管理员
# UPDATE zc_member
# SET loginname = '18998888888', `password` = md5('89grb99'), `safe_password` = md5('89grb99')
# WHERE id = 1;
#
# -- 大管理员
# UPDATE zc_member
# SET loginname = '18966666666', `password` = md5('89grb33'), `safe_password` = md5('89grb33')
# WHERE id = 712;
#
# -- 财务管理员
# UPDATE zc_member
# SET loginname = '18933333333', `password` = md5('89grb66'), `safe_password` = md5('89grb66')
# WHERE id = 709;
#
# -- 商城管理员
# UPDATE zc_member
# SET loginname = '18900000000', `password` = md5('89grb11'), `safe_password` = md5('89grb11')
# WHERE id = 437;
#
# -- 共享账号
# UPDATE zc_member
# SET loginname = '18999999999', `password` = md5('89grb77'), `safe_password` = md5('89grb77')
# WHERE id = 921;

# -- 实名认证管理员
# UPDATE zc_member
# SET loginname = '18911111111', `password` = md5('89grb00'), `safe_password` = md5('89grb00')
# WHERE id = 922;


# 超级管理员(18998888888), 登陆密码、二级密码：89grb99
# 大管理员(18966666666), 登陆密码、二级密码：89grb33
# 财务管理员(18933333333), 登陆密码、二级密码：89grb66
# 商城管理员(18900000000), 登陆密码、二级密码：89grb11
# 共享账号(18999999999), 登陆密码、二级密码：89grb77
# 实名认证管理员(18911111111), 登陆密码、二级密码：89grb00

# 三级密码: 33668899