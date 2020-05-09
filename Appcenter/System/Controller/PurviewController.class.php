<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 权限相关的角色规则管理
// +----------------------------------------------------------------------
namespace System\Controller;
use Common\Controller\AuthController;

class PurviewController extends AuthController {
	
	/**
	 * 规则管理
	 */
	public function ruleManage() {
		$rule_list = $this->ruleList();
		$rule_array = array();
		foreach ($rule_list as $k=>$list) {
			$arr = strpos($list['name'], ',') ? explode(',', $list['name']) : $list['name'];
			$arr = is_array($arr) ? explode('/', $arr[0]) : explode('/', $arr);
			$rule_array[L('rule_'.$arr[0])][] = array('list'=>$list, 'title'=>$list['title']);
		}
		$this->assign('rule_list', $rule_array);
		
		$this->display();
	}
	
	/**
	 * 规则列表
	 * @param $ispage boolean 是否启用分页
	 */
	protected function ruleList($map='', $select=true, $ispage=true) {
		$AuthRule = D("Admin/AuthRule");
		
		if ($ispage) {
			$count = $AuthRule->count();
			$limit = $this->Page($count);
			$rule_list = $AuthRule->getRuleList($map, $select, $limit);
		}
		else {
			$rule_list = $AuthRule->getRuleList($map, $select);
		}
		
		return $rule_list;
	}
	
	/**
	 * 规则添加
	 */
	public function ruleAdd() {
		$AuthRule = D('Admin/AuthRule');
		if (!$AuthRule->create(I('post.'))) {
			$this->error($AuthRule->getError(), U('Purview/ruleManage'));
		}
		else {
			$id = $AuthRule->add();
			$this->success(L('rule').L('add').L('is_success'), U('Purview/ruleManage'), false, "添加规则:{$this->post['title']}[ID:{$id}]");
		}
	}
	
	/**
	 * 规则编辑[ui]
	 */
	public function ruleModify() {
		!is_numeric(I('get.id')) && $this->error(L('param').L('is_error'), U('Purview/ruleManage'));
		
		$map['id'] = array('eq', I('get.id'));
		$rule_info = $this->ruleList($map, false, false);
		!$rule_info && $this->error(L('null_result'));
		$this->assign('rule_info', $rule_info);
		
		$this->display();
	}
	
	/**
	 * 规则保存
	 */
	public function ruleSave() {
	    $AuthRule = M("AuthRule");
		$this->autoCheckTokenPackage($AuthRule, $_POST);
		
		if ($AuthRule->save(I('post.')) === false) {
			$this->error(L('save').L('is_failed'));
		}
		else {
			$this->success(L('save').L('is_success'), U('Purview/ruleManage'), false, "编辑规则:{$this->post['title']}[ID:{$this->post['id']}]");
		}
	}
	
	/**
	 * 规则删除
	 */
	public function ruleDelete() {
		$AuthRule = M("AuthRule");
		!is_numeric(I('get.id')) && $this->error(L('param').L('is_error'), U('Purview/ruleManage'));
		$map = array(
			'id' => array('in', I('get.id')),
		);
		
		//拉取该规则信息
		$rule_info = $AuthRule->where($map)->field('title')->find();
		if (!$rule_info) {
			$this->error('该规则已不存在');
		}
		
		if (!$AuthRule->where($map)->delete()){
			$this->error(L('delete').L('is_failed'), U('Purview/ruleManage'));
		}
		else {
			$this->success(L('delete').L('is_success'), U('Purview/ruleManage'), false, "删除规则:{$rule_info['title']}[ID:{$this->get['id']}]");
		}
	}
	
	/**
	 * 角色添加UI
	 */
	public function groupAddUi() {
		$rule_list = $this->ruleList(array('status=1'), true, false);
		$rule_array = array();
		foreach ($rule_list as $k=>$list) {
			$arr = strpos($list['name'], ',') ? explode(',', $list['name']) : $list['name'];
			$arr = is_array($arr) ? explode('/', $arr[0]) : explode('/', $arr);
			$rule_array[L('rule_'.$arr[0])][] = array('list'=>$list, 'title'=>$list['title']);
		}
		$this->assign('rule_list', $rule_array);
	
		$this->display();
	}
	
	/**
	 * 角色管理
	 */
	public function groupManage() {
		$this->assign('group_list', $this->groupList());
		
		$this->display();
	}
	
	/**
	 * 角色列表
	 */
	protected function groupList($map='', $select=true) {
		$AuthGroup = D("Admin/AuthGroup");
		
		$count = $AuthGroup->count();
		$limit = $this->Page($count);
		
		$group_list = $AuthGroup->getGroupList($map, $select, $limit);
		
		return $group_list;
	}
	
	/**
	 * 角色添加
	 */
	public function groupAdd() {
		$AuthGroup = D('Admin/AuthGroup');
		if (!$AuthGroup->create(I('post.'))) {
			$this->error($AuthGroup->getError(), U('Purview/groupManage'));
		}
		else {
			$id = $AuthGroup->add();
			$this->success(L('group').L('add').L('is_success'), U('Purview/groupManage'), false, "添加角色:{$this->post['title']}[ID:{$id}]");
		}
	}
	
	/**
	 * 角色编辑[ui]
	 */
	public function groupModify() {
		!is_numeric(I('get.id')) && $this->error(L('param').L('is_error'), U('Purview/groupManage'));
		
		$map['id'] = array('eq', I('get.id'));
		$group_info = $this->groupList($map, false);
		!$group_info && $this->error(L('null_result'));
		
		$this->assign('group_info', $group_info);
		
		$rule_list = $this->ruleList(array('status=1'), true, false);
		$rule_array = array();
		foreach ($rule_list as $k=>$list) {
			$arr = strpos($list['name'], ',') ? explode(',', $list['name']) : $list['name'];
			$arr = is_array($arr) ? explode('/', $arr[0]) : explode('/', $arr);
			$rule_array[L('rule_'.$arr[0])][] = array('list'=>$list, 'title'=>$list['title']);
		}
		$this->assign('rule_list', $rule_array);
		
		$this->display();
	}
	
	/**
	 * 角色保存
	 */
	public function groupSave() {
		$AuthGroup = M("AuthGroup");
		$this->autoCheckTokenPackage($AuthGroup, $_POST);

		$data = I('post.');
		$data['rules'] = implode(',', $data['rules']);
		
		//移除 修改角色(服务中心/区域合伙人/商家)名称项
		$ROLE_MUST_LIST = C('ROLE_MUST_LIST');
		if (in_array($data['id'], $ROLE_MUST_LIST)) {
			unset($data['title']);
		}
		
		if ($AuthGroup->save($data) === false) {
			$this->error(L('save').L('is_failed'));
		}
		else {
			$this->success(L('save').L('is_success'), U('Purview/groupManage'), false, "编辑角色:{$data['title']}[ID:{$this->post['id']}]");
		}
	}
	
	/**
	 * 角色删除
	 */
	public function groupDelete() {
		$AuthGroup = M("AuthGroup");
		$AuthGroupAccess = M('AuthGroupAccess');
		
		M()->startTrans();
		
		!is_numeric(I('get.id')) && $this->error(L('param').L('is_error'), U('Purview/groupManage'));
		$map['id'] = array('in', I('get.id'));
		
		//拉取角色信息
		$group_info = $AuthGroup->where($map)->field('title')->find();
		if (!$group_info) {
			$this->error('该角色已不存在');
		}
		
		if ($AuthGroup->where($map)->delete() === false){
			$this->error(L('delete').L('is_failed'), U('Purview/groupManage'));
		}
		else {
			$map_access['group_id'] = array('eq', I('get.id'));
			if ($AuthGroupAccess->where($map_access)->delete() == false) {
				M()->rollback();
				$this->error(L('delete').L('is_failed'), U('Purview/groupManage'));
			}
			
			M()->commit();
			$this->success(L('delete').L('is_success'), U('Purview/groupManage'), false, "删除角色:{$group_info['title']}[ID:{$this->get['id']}]");
		}
	}
	
}
?>