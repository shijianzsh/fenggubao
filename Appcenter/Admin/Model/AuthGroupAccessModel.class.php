<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 角色/用户组权限模型
// +----------------------------------------------------------------------
namespace Admin\Model;
use Common\Model\CommonModel;

class AuthGroupAccessModel extends CommonModel {

	protected $_validate = array(
		array('uid', 'number', '{%uid_format_error}'),
		array('group_id', 'number', '{%group_id_format_error}'),
	);
	
	/**
	 * 获取角色明细信息或列表
	 * @param string $field 查询字段,默认uid,group_id,title[角色名称]
	 * @param string $map where条件
	 * @param boolean $select 查询方式[select,find默认]
	 */
	public function getGroupAccessList($field='uid,group_id', $map='', $select=false) {
		$group_access_field = array('uid', 'group_id');
		
		//兼容处理field,map
		$field = empty($field) ? 'uid,group_id' : $field;
		$field = strpos($field, ',') ? explode(',', $field) : array($field);
		foreach ($field as $k=>$v) {
			if (preg_match('/(agc.|ag.){1,}/', $v)) {
				$field[$k] = $v;
			} else {
				$field[$k] = in_array($v, $group_access_field) ? 'agc.'.$v : 'ag.'.$v;
			}
		}
		if (is_array($map)) {
			foreach ($map as $k=>$v) {
				if (preg_match('/(agc.|ag.){1,}/', $k)) {
					$map[$k] = $v;
				} else {
					if (in_array($k, $group_access_field)) {
						$map['agc.'.$k] = $v;
					} else {
						$map['ag.'.$k] = $v;
					}
					unset($map[$k]);
				}
			}
		}
		
		$group_access_list = $this->alias('agc')->field($field);
		$group_access_list = $group_access_list->join('left join __AUTH_GROUP__ ag on ag.id=agc.group_id');
		$group_access_list = empty($map) ? $group_access_list : $group_access_list->where($map);
		$group_access_list = $select ? $group_access_list->select() : $group_access_list->find();
		return $group_access_list;
	}
	
	/**
	 * 判断是否存在符合条件的查询
	 * @param array $map 查询条件 
	 */
	public function isExist($map) {
		$result = $this->getGroupAccessList('uid', $map);
		if (count($result)<=0 || !$result) {
			return false;
		}
		else {
			return true;
		}
	}
	
	/**
	 * 添加勾选的角色
	 * @param array $data 提交的新角色数组
	 * @param number $uid 目标账户ID
	 */
	public function addAccess($data, $uid) {
		$map['uid'] = array('eq', $uid);
		$data_group_access['uid'] = $uid;
		foreach ($data as $k=>$v) {
		    $map['group_id'] = array('eq', $v);
		    if (!$this->isExist($map)) {
			    $data_group_access['group_id'] = $v;
			    if ($this->data($data_group_access)->add() === false) {
				    $this->error(L('group').L('save').L('is_failed'));
			    }
		    }
		}
	}
	
	/**
	 * 删除未勾选的角色
	 * @param array $data 提交的新角色数组
	 * @param number $uid 目标账户ID
	 */
	public function delAccess($data, $uid) {
		$map['uid'] = array('eq', $uid);
		$old_data = $this->getGroupAccessList('', $map, true);
		foreach ($old_data as $k=>$v) {
			if (!in_array($v['group_id'], $data)) {
				$map['group_id'] = array('eq', $v['group_id']);
				$this->where($map)->delete();
			}
		}
	}
	
}
?>