<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 商城管理首页
// +----------------------------------------------------------------------

namespace Shop\Controller;
use Common\Controller\AuthController;

class IndexController extends AuthController {
	
	public function index() {
		$this->display();
	}
	
}
?>