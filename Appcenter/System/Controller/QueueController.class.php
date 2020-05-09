<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 执行队列管理
// +----------------------------------------------------------------------
namespace System\Controller;
use Common\Controller\AuthController;
use V4\Model\QueueModel;

class QueueController extends AuthController {

	public function index() {
		$page = $this->get['p']>0 ? $this->get['p'] : 1;
		
		$QueueModel = new QueueModel();
		
		$data = $QueueModel->getList('*', $page, 20);
		
		$list = $data['list'];
		$this->assign('list', $list);
		
		$this->Page($data['paginator']['totalPage']*$data['paginator']['everyPage'], $data['paginator']['everyPage'], $this->get);
		
		$this->display();
	}
	
	/**
	 * 修改执行队列信息
	 */
	public function modify() {
		$QueueModel = new QueueModel();
		
		$queue_id = $this->get['queue_id'];
		
		if (!validateExtend($queue_id, 'NUMBER')) {
			$this->error('参数有误');
		}
		
		$info = $QueueModel->getInfo('*', "queue_id={$queue_id}");
		$this->assign('info', $info);
		
		$queue_status_list = C('FIELD_CONFIG.procedure_queue')['queue_status'];
		$this->assign('queue_status_list', $queue_status_list);
		
		$this->display();
	}
	
	/**
	 * 保存执行队列信息
	 */
	public function save() {
		$data = $this->post;
		
		$QueueModel = new QueueModel();
		if ($QueueModel->save($data) === false) {
			$this->error('保存失败');
		} else {
			$this->success('保存成功', U('Queue/index'), false, "成功保存执行队列信息[ID:{$data[queue_id]}]");
		}
	}
	
}
?>