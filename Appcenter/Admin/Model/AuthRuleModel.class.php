<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 规则模型
// +----------------------------------------------------------------------
namespace Admin\Model;
use Common\Model\CommonModel;

class AuthRuleModel extends CommonModel {
	
	protected $insertFields = array('name', 'title', 'condition');
	protected $updateFields = array('name', 'status', 'condition');
	
	protected $_validate = array(
		array('name', '', '{%rule_name_is_exist}', 0, 'unique'),
		array('title', '', '{%rule_title_is_exist}', 0, 'unique'),
		array('name', 'require', '{%rule_name_no_null}', 0, 'regex'),
		array('title', 'require', '{%rule_title_no_null}', 0, 'regex'),
	);
	
	/**
	 * 获取规则列表
	 * @param $map array where条件
	 * @param $select boolean 获取数据方式[默认:select,可选:find]
	 * @param $limit string
	 */
	public function getRuleList($map='', $select=true, $limit=false) {
		$rule_list = $this->field('id,name,title,status');
		$rule_list = empty($map) ? $rule_list : $rule_list->where($map);
		$rule_list = $rule_list->order('name asc,id asc');
		$rule_list = $limit ? $rule_list->limit($limit) : $rule_list;
		$rule_list = $select ? $rule_list->select() : $rule_list->find();
		return $rule_list;
	}

}
?>