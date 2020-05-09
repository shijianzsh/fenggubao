<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 摇一摇管理
// +----------------------------------------------------------------------
namespace System\Controller;
use Common\Controller\AuthController;
use V4\Model\ShakeModel;

class ShakeController extends AuthController {

	/**
	 * 摇一摇列表
	 */
	public function shakelogs() {
		$ShakeModel = new ShakeModel();
		
		$where = ' 1 ';
		
		$userid = $this->get['userid'];
		$time_min = $this->get['time_min'];
		$time_max = $this->get['time_max'];
		$page = $this->get['p']>1 ? $$this->get['p'] : 1;
		
		if (!empty($userid) && !validateExtend($userid, 'MOBILE')) {
			$this->error('用户账号格式有误');
		} 
		if (!empty($userid)) {
			$member_info = M('member')->where('loginname='.$userid)->field('id')->find();
			if (!$member_info) {
				$this->error('用户账号不存在');
			}
			$where .= " and user_id=".$member_info['id'];
		}
		
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
			//发布者账号信息
			$member_info = M('Member')->where('id='.$v['user_id'])->field('loginname,nickname')->find();
			$list[$k]['loginname'] = $member_info ? $member_info['loginname'] : '';
			$list[$k]['nickname'] = $member_info ? $member_info['nickname'] : '';
			
			//发布者店铺信息
			$store_info = M('Store')->where('uid='.$v['user_id'])->field('store_name')->find();
			$list[$k]['store_name'] = $store_info ? $store_info['store_name'] : '';
			
			//摇一摇状态对应中文
			if (array_key_exists('shake_status', $field_config) && isset($v['shake_status'])) {
				$list[$k]['shake_status_cn'] = $field_config['shake_status'][$v['shake_status']];
			}
			
			//获取已摇中次数
			$list[$k]['shake_record_count'] = M('ShakeRecords')->where('shake_id='.$v['shake_id'])->count();
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
		$userid = $this->get['userid'];
		$time_min = $this->get['time_min'];
		$time_max = $this->get['time_max'];
		$page = $this->get['p']>1 ? $this->get['p'] : 1;
		
		if (!validateExtend($shake_id, 'NUMBER')) {
			$this->error('参数格式有误');
		} else {
			$where .= " and shake_id=".$shake_id;
		}
		if (!empty($userid) && !validateExtend($userid, 'MOBILE')) {
			$this->error('用户账号格式有误');
		}
		if (!empty($userid)) {
			$member_info = M('member')->where('loginname='.$userid)->field('id')->find();
			if (!$member_info) {
				$this->error('用户账号不存在');
			}
			$where .= " and user_id=".$member_info['id'];
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
	
	/**
	 * 摇一摇自动回本状态处理
	 */
	public function shakeStatus() {
	    $Shake = M('Shake');
	
	    $shake_id = $this->get['shake_id'];
	     
	    if (!validateExtend($shake_id, 'NUMBER')) {
	        $this->error('参数有误');
	    }
	     
	    $where = "shake_id=".$shake_id;
	    $data = [];
	    $action = '开启';
	     
	    $shake_info = $Shake->where($where)->field('shake_status,user_id')->find();
	    if (!$shake_info) {
	        $this->error('当前用户自动回本信息不存在');
	    }
	     
	    if ($shake_info['shake_status'] == '0' || $shake_info['shake_status'] == '1') {
	        $data['shake_status'] = 2;
	    } elseif ($shake_info['shake_status'] == '2' || $shake_info['shake_status'] == '3') {
	        $data['shake_status'] = 1;
	        $action = '关闭';
	    }
	
	    if ($Shake->where($where)->save($data) === false) {
	        $this->error('操作失败');
	    }
	
	    $log_info = M('Member')->where('id='.$shake_info['user_id'])->field('loginname,nickname')->find();
	
	    $this->success('成功'.$action.'摇一摇自动回本', '', false, "[{$action}]{$log_info[nickname]}[{$log_info[loginname]}]摇一摇自动回本");
	}
	
}
?>