> 更新说明

	1、GRB字符统一改为GRC
	2、公让宝明细接口: 针对record_action=151的明细,若record_remark='转出到锁定通证',则返回字段的action='转出到锁定通证'

### 一. 更新文件

	Appcenter/Admin/View/default/GrbTransaction/index.html
	
	Appcenter/APP/Common/function.php
	Appcenter/APP/Controller/Hack2Controller.class.php
	Appcenter/APP/Lang/en.php
	Appcenter/APP/Lang/ko.php
	
	Appcenter/Common/Conf/navigation_config.php
	
	Appcenter/V4/Model/ProductModel.class.php
	