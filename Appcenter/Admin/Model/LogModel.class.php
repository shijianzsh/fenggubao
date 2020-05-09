<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 日志模型
// +----------------------------------------------------------------------
namespace Admin\Model;
use Common\Model\CommonModel;

class LogModel extends CommonModel {

	protected $insertFields = array('admin_id', 'admin_ip', 'content', 'date_created', 'type');
	protected $updateFields = array();
	
	protected $_validate = array(
	);
	protected $_auto = array(
			array('date_created', 'time', 1, 'function'),
			array('admin_ip', 'get_client_ip', 1, 'function'),
	);
	
	/**
	 * 获取信息列表
	 * @param $map array where条件
	 * @param $select boolean 获取数据方式[默认:select,可选:find]
	 * @param $field string 要读取的字段列表
	 * @param $distinct boolean 是否查询唯一不同的值
	 * @param $limit string 
	*/
	public function getList($map='', $select=true, $field='', $distinct=false, $limit=false) {
		$list = $this->alias('log');
		$list = empty($field) ? $list->field('log.id,log.admin_id,log.admin_ip,log.content,log.date_created,log.type,mem.loginname,mem.nickname') : $list->field($field);
		$list = $list->join('LEFT JOIN __MANAGER__ as mem ON mem.id=log.admin_id');

		$list = empty($map) ? $list : $list->where($map);
		$list = $list->order('log.id desc,log.admin_id desc');
		
		$list = $distinct ? $list->distinct(true) : $list;
		$list = $limit ? $list->limit($limit) : $list;
		$list = $select ? $list->select() : $list->find();
		
		return $list;
	}
	
}
?>