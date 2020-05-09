<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 定时任务管理
// +----------------------------------------------------------------------
namespace System\Controller;
use Common\Controller\AuthController;
use V4\Model\TaskModel;

class TaskController extends AuthController {

	public function index() {
		$page = $this->get['p']>0 ? $this->get['p'] : 1;
		
		$TaskModel = new TaskModel();
		
		$data = $TaskModel->getList('*', $page, 20);
		
		$list = $data['list'];
		$this->assign('list', $list);
		
		$this->Page($data['paginator']['totalPage']*$data['paginator']['everyPage'], $data['paginator']['everyPage'], $this->get);
		
		$this->display();
	}
	
}
?>