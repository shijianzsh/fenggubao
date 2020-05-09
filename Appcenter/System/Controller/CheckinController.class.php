<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 签到管理
// +----------------------------------------------------------------------
namespace System\Controller;
use Common\Controller\AuthController;
use V4\Model\CheckinModel;
use V4\Model\Tag;

class CheckinController extends AuthController {

	public function index() {
		$CheckinModel = new CheckinModel();
		
		$page = $this->get['p']>1 ? $this->get['p'] : 1;
		
		$data = $CheckinModel->getList('*', $page, 20, '', 0);
		
		$list = $data['list'];
		foreach ($list as $k=>$v) {
			$member_info = M('Member')->where('id='.$v['user_id'])->field('loginname,nickname,truename,username')->find();
			$list[$k]['loginname'] = $member_info ? $member_info['loginname'] : '';
			$list[$k]['nickname'] = $member_info ? $member_info['nickname'] : '';
			$list[$k]['truename'] = $member_info ? $member_info['truename'] : '';
			$list[$k]['username'] = $member_info ? $member_info['username'] : '';
		}
		$this->assign('list', $list);
		
		$this->Page($data['paginator']['totalPage']*$data['paginator']['everyPage'], $data['paginator']['everyPage'], $this->get);
		
		$this->display();
	}
	
}
?>