<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 角色/用户组模型
// +----------------------------------------------------------------------
namespace Admin\Model;
use Common\Model\CommonModel;

class AuthGroupModel extends CommonModel {
	
	protected $insertFields = array('title', 'rules');
	protected $updateFields = array('rules');
	
	protected $_validate = array(
		array('title', '', '{%group_title_is_exist}', 0, 'unique'),
		array('title', 'require', '{%group_title_no_null}', 0, 'regex'),
		array('rules', 'is_array', '{%rule_no_null}', 1, 'function'),
	);
	protected $_auto = array(
		array('rules', 'implode', 3, 'callback'),
	);
	
	/**
	 * 回调方法
	 */
	protected function implode($var) {
		return implode(',', $var);
	}
	
	/**
	 * 获取角色列表
     * @param $map array where条件
	 * @param $select boolean 获取数据方式[默认:select,可选:find]
	 * @param $limit string 
	 */
	public function getGroupList($map='', $select=true, $limit=false) {
		$group_list = $this->alias('g')
		    ->field('g.id,g.title,g.status,g.rules');
		$group_list = empty($map) ? $group_list : $group_list->where($map);
		$group_list = $limit ? $group_list->limit($limit) : $group_list;
		$group_list = $select ? $group_list->select() : $group_list->find();
		return $group_list;
	}
	
}
?>