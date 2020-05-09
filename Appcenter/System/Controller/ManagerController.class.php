<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 管理员管理
// +----------------------------------------------------------------------
namespace System\Controller;
use Common\Controller\AuthController;

class ManagerController extends AuthController {
	
	public function index() {
		$AuthMember = D("Admin/Manager");
		$AuthGroupAccess = D("Admin/AuthGroupAccess");
		$AuthGroup = D("Admin/AuthGroup");
		
		$map = array();
		
		$userid = $this->get['userid'];
		$group_id = $this->get['group_id'];
		
		if (!empty($userid)) {
			$map_1['mem.loginname'] = array('eq', $userid);
			$map_1['m.nickname'] = array('eq', $userid);
			$map_1['m.username'] = array('eq', $userid);
			$map_1['_logic'] = 'or';
			$map['_complex'] = $map_1;
		}
		
		if (validateExtend($group_id, 'NUMBER')) {
			$map['aga.group_id'] = array('eq', $group_id);
		}
		
		//所有角色列表
		$group_list = $AuthGroup->getGroupList('', true);
		$this->assign('group_list', $group_list);
		
		$count = $AuthMember
			->alias('mem')
			->join('left join __MEMBER__ m ON m.id=mem.uid')
			->join('left join __AUTH_GROUP_ACCESS__ aga ON aga.uid=mem.id')
			->where($map)
			->field('count(distinct mem.id) count')
			->find();
		$count = $count['count'];
		$limit = $this->Page($count, 20, $this->get);
		
		$member_list = $AuthMember->getMemberList($map, true, 'mem.*,m.username', false, $limit);
		
		//获取用户角色信息
		foreach ($member_list as $k=>$list) {
			$map_group_access['uid'] = array('eq', $list[id]);
			$group_access_list = $AuthGroupAccess->getGroupAccessList('ag.title', $map_group_access, true);
			$member_list[$k]['group_access_list'] = arrayToString($group_access_list, ',');
		}
		
		$this->assign("member_list", $member_list);
		
		$this->display();
	}
	
	/**
	 * 账户添加[ui]
	 */
	public function memberAddUi() {
		$AuthGroup = D("Admin/AuthGroup");
		
		C('TOKEN_ON', false);
		
		$group_info = $AuthGroup->getGroupList();
		$this->assign('group_list', $group_info);
		
		$this->display();
	}
	
	/**
	 * 账户添加
	 */
	public function memberAdd() {
		$AuthMember = D("Admin/Manager");
		$Member = M('Member');
		$AuthGroupAccess = D("Admin/AuthGroupAccess");
		
		C('TOKEN_ON', false);
		
		$data = I('post.');
		
//		if (!validateExtend($data['username'], 'USERNAME')) {
//			$this->error('用户账户格式有误');
//		}
		
		//查询member表信息,并拉取对应账号的密码和昵称
		$map_member['loginname'] = array('eq', $data['username']);
		$member_info = $Member->where($map_member)->field('password,nickname,loginname,id')->find();
		if ($member_info) {
			$data['loginname'] = $member_info['loginname'];
			$data['nickname'] = $member_info['nickname'];
			$data['uid'] = $member_info['id'];
		} else {
			$this->error(L('account_is_exist'));
		}
		
		$map['mem.uid'] = array('eq', $member_info['id']);
		if ($AuthMember->getMemberList($map, false, '')) {
			$this->error(L('account_is_exist'));
		}
		
		$group_access_group_id = $data['group_id'];
		unset($data['group_id']);
		
		if (!$AuthMember->create($data)) {
			$this->error($AuthMember->getError());
		} else {
			$id = $AuthMember->add();
			
			foreach ($group_access_group_id as $k=>$v) {
				$AuthGroupAccess->addAccess($group_access_group_id, $id);
				$AuthGroupAccess->delAccess($group_access_group_id, $id);
			}
			
			$this->success(L('account_add_success'), U('Manager/index'), false, "添加后台管理员用户:{$data['username']}[{$data['loginname']}][{$member_info['nickname']}]");
		}
	}
	
	/**
	 * 账户编辑[ui]
	 */
	public function memberModify() {
		$AuthMember = D("Admin/Manager");
		$AuthGroup = D("Admin/AuthGroup");
		$AuthGroupAccess = D("Admin/AuthGroupAccess");
		
		$id = I('get.id');
		
		!is_numeric($id) && $this->error(L('param').L('is_error'));
		
		if ($id==1) {
			$this->error('超级管理员不能修改');
		}
		
		$map_member['mem.id'] = array('eq', $id);
		$member_info = $AuthMember->getMemberList($map_member);
		!$member_info && $this->error(L('null_result'));
		$this->assign('member_info', $member_info);
		
		$map_group['g.status'] = array('eq', 1);
		$group_info = $AuthGroup->getGroupList($map_group);
		$this->assign('group_list', $group_info);
		
		$map_group_access['uid'] = array('eq', $id);
		$group_access_list = $AuthGroupAccess->getGroupAccessList('group_id', $map_group_access, true);
		$group_access_list = arrayToString($group_access_list, ',');
		$this->assign('group_access_list', $group_access_list);
		
		$this->display();
	}
	
	/**
	 * 账户保存
	 */
	public function memberSave() {
		$AuthMember = M("Manager");
		$AuthGroupAccess = D("Admin/AuthGroupAccess");
		
		$this->autoCheckTokenPackage($AuthMember, $_POST);
		
		$data = I('post.');
		
		if (empty($data['password'])) {
			unset($data['password']);
		}
		else {
			$data['password'] = md5($data['password']);
		}
		
		if (empty($data['nickname'])) {
			unset($data['nickname']);
		}
		
		$group_access_group_id = $data['group_id'];
		unset($data['group_id']);
		
		if ($AuthMember->save($data) === false) {
			$this->error(L('save').L('is_failed'));
		}
		else {
			foreach ($group_access_group_id as $k=>$v) {
				$AuthGroupAccess->addAccess($group_access_group_id, $data['id']);
				$AuthGroupAccess->delAccess($group_access_group_id, $data['id']);
			}
			$this->success(L('save').L('is_success'), U('Manager/index'), false, "编辑后台管理员用户:{$data['username']}");
		}
	}
	
	/**
	 * 账户删除
	 */
	public function memberDelete() {
		$AuthMember = D("Admin/Manager");
		$AuthGroupAccess = D("Admin/AuthGroupAccess");
		
		!is_numeric(I('get.id')) && $this->error(L('param').L('is_error'));
		
		$map['mem.id'] = array('eq', I('get.id'));
		$member_info = $AuthMember->getMemberList($map);
		if ($member_info['id'] == 1) {
			$this->error(L('super_unable_delete'));
		}
		
		//检测是否含有服务中心或区域合伙人或商家角色,如果有,则需先去会员列表取消对应身份后才能删除
		$role_must_list = array_values(C('ROLE_MUST_LIST'));
		$map_group_access['uid'] = array('eq', I('get.id'));
		$map_group_access['group_id'] = array('in', implode(',', $role_must_list));
		$role_exist = $AuthGroupAccess->isExist($map_group_access);
		if ($role_exist) {
			$this->error('请先去会员列表取消该会员的区域合伙人或商家身份');
		}
		
		if (!$AuthMember->where('id='.I('get.id'))->delete()) {
			$this->error(L('delete').L('is_failed'));
		}
		else {
			$AuthGroupAccess->delAccess(array(), I('get.id'));
			$this->success(L('delete').L('is_success'), '', false, "删除后台管理员用户:{$member_info['username']}[{$member_info['loginname']}][{$member_info['nickname']}]");
		}
	}
	
}
?>