<?php 
return array(
	//启用表单令牌
	'view_filter' => array('Behavior\TokenBuildBehavior'),
		
	//启用多语言支持
	'app_begin' => array('Behavior\CheckLangBehavior'),
);
?>