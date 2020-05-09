<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 商品一级分类模型
// +----------------------------------------------------------------------
namespace Admin\Model;
use Common\Model\CommonModel;

class FirstMenuModel extends CommonModel {

	//protected $insertFields = array();
	//protected $updateFields = array();
	
	protected $_validate = array();
	
	protected $_auto = array();
	
	/**
	 * 获取信息列表
	 * @param $map array where条件
	 * @param $select boolean 获取数据方式[默认:select,可选:find]
	 * @param $field string 要读取的字段列表
	 * @param $distinct boolean 是否查询唯一不同的值
	 * @param $limit string 条数
	 * @param $order string 排序
	 * @param $group string GROUP BY
	*/
	public function getList($map='', $select=true, $field='', $distinct=false, $limit=false, $order=false, $group=false) {
		$list = $this->alias('fir');
		$list = empty($field) ? $list->field('fir.fm_id,fir.fm_name,fir.fm_order,sec.sm_id,sec.sm_name,sec.sm_image,sec.fm_order sm_order') : $list->field($field);
		$list = $list->join('LEFT JOIN __SECOND_MENU__ as sec ON sec.fm_id=fir.fm_id');
		
		$list = empty($map) ? $list : $list->where($map);
		
		$list = $distinct ? $list->distinct(true) : $list;
		$list = $limit ? $list->limit($limit) : $list;
		$list = $order ? $list->order($order) : $list->order('fir.fm_order desc,fir.fm_id desc');
		$list = $group ? $list->group($group) : $list;
		$list = $select ? $list->select() : $list->find();
		
		return $list;
	}
	
}
?>