alter table `zc_member`
add `role_star` tinyint(3) unsigned null default 0
comment '店长星级'
after `role`;