-- 修改zc_orders表中字段属性
alter table zc_orders  
	modify `comment_img` varchar(500) default '' comment '评论带图',
	modify `cancel_reason` varchar(30) default '' comment '取消/退款原因',
	modify `cancel_descp` varchar(200) default '' comment '取消/退款备注描述',
	modify `cancel_time` varchar(22) default '' comment '取消/退款时间';