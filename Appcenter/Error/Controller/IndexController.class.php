<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 错误处理
// +----------------------------------------------------------------------

namespace Error\Controller;
use Think\Controller;

class IndexController extends Controller {
	
	public function __construct() {
		parent::__construct();
		
		layout(false);
	}
	
	public function err() {
		$code = trim($_GET['code']);
		
		if (!validateExtend($code, 'NUMBER')) {
			$this->error('错误类型参数有误');
		}
	
		$this->display($code);
	}
	
}