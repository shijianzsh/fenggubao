> 说明

	1、后台首页现金币统计金额累计+50W
	2、订单下单成功后同步更新商品已售数量
	3、买单时，需要验证有效农场不能超过50个，超过50个不能报单。（有效农场=总农场-报废农场）（如：有效农场49，则只能报单PV1000的，也就是说最多只能增加一个农场）
	

### 一. 更新文件

	Appcenter/Admin/Controller/IndexController.class.php
	
	Appcenter/APP/Conf/config.php
	Appcenter/APP/Controller/ShopingcartController.class.php
	
	Appcenter/V4/Model/OrderModel.class.php
	
	