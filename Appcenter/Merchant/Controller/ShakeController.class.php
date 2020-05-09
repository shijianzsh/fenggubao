<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 商家摇一摇
// +----------------------------------------------------------------------

namespace Merchant\Controller;
use Common\Controller\AuthController;
use V4\Model\ShakeModel;
use V4\Model\Tag;

class ShakeController extends AuthController {
	
	/**
	 * 摇一摇列表
	 */
	public function index() {
		$ShakeModel = new ShakeModel();
		
		$where = ' user_id='.session('admin_mid');
		
		$time_min = $this->get['time_min'];
		$time_max = $this->get['time_max'];
		$page = $this->get['p']>1 ? $$this->get['p'] : 1;
		
		if (!empty($time_min)) {
			$where .= " and shake_addtime>=".strtotime($time_min);
		}
		if (!empty($time_max)) {
			$where .= " and shake_addtime<=".strtotime($time_max.' 23:59:59');
		}
		
		$data = $ShakeModel->getShakeList('*', $page, 20, $where);
		
		$list = $data['list'];
		$field_config = C('FIELD_CONFIG.shake');
		foreach ($list as $k=>$v) {
			//摇一摇状态对应中文
			if (array_key_exists('shake_status', $field_config) && isset($v['shake_status'])) {
				$list[$k]['shake_status_cn'] = $field_config['shake_status'][$v['shake_status']];
			}
		}
		$this->assign('list', $list);
		
		$this->Page($data['paginator']['totalPage']*$data['paginator']['everyPage'], $data['paginator']['everyPage'], $this->get);
		
		$this->display();
	}
	
	/**
	 * 摇一摇记录列表
	 */
	public function shakeRecords() {
		$ShakeModel = new ShakeModel();
	
		$where = ' 1 ';
	
		$shake_id = $this->get['shake_id'];
		$time_min = $this->get['time_min'];
		$time_max = $this->get['time_max'];
		$page = $this->get['p']>1 ? $this->get['p'] : 1;
	
		if (!validateExtend($shake_id, 'NUMBER')) {
			$this->error('参数格式有误');
		} else {
			$where .= " and shake_id=".$shake_id;
		}
	
		if (!empty($time_min)) {
			$where .= " and records_addtime>=".strtotime($time_min);
		}
		if (!empty($time_max)) {
			$where .= " and records_addtime<=".strtotime($time_max.' 23:59:59');
		}
	
		$data = $ShakeModel->getShakeRecordsList('*', $page, 20, $where);
	
		$list = $data['list'];
		foreach ($list as $k=>$v) {
			//摇中者账号信息
			$member_info = M('Member')->where('id='.$v['user_id'])->field('loginname,nickname')->find();
			$list[$k]['loginname'] = $member_info ? $member_info['loginname'] : '';
			$list[$k]['nickname'] = $member_info ? $member_info['nickname'] : '';
		}
		$this->assign('list', $list);
	
		$this->Page($data['paginator']['totalPage']*$data['paginator']['everyPage'], $data['paginator']['everyPage'], $this->get);
	
		$this->display();
	}
	
	/**
	 * 摇一摇回本记录列表
	 */
	public function shakeRefund() {
		$ShakeModel = new ShakeModel();
	
		$where = ' user_id='.session('admin_mid');
	
		$shake_id = $this->get['shake_id'];
		$time_min = $this->get['time_min'];
		$time_max = $this->get['time_max'];
		$page = $this->get['p']>1 ? $this->get['p'] : 1;
	
		if (!validateExtend($shake_id, 'NUMBER')) {
			$this->error('参数格式有误');
		} else {
			$where .= " and shake_id=".$shake_id;
		}
	
		if (!empty($time_min)) {
			$where .= " and refund_addtime>=".strtotime($time_min);
		}
		if (!empty($time_max)) {
			$where .= " and refund_addtime<=".strtotime($time_max.' 23:59:59');
		}
	
		$data = $ShakeModel->getShakeRefundList('*', $page, 20, $where);
	
		$list = $data['list'];
		$this->assign('list', $list);
	
		$this->Page($data['paginator']['totalPage']*$data['paginator']['everyPage'], $data['paginator']['everyPage'], $this->get);
	
		$this->display();
	}
	
}
?>