<?php
namespace Common\Model\Sys;
use Common\Model\BaseModel;
use V4\Model\Tag;

/**
 * 业绩模型
 *
 */
class PerformanceModel extends BaseModel {
	
	/**
	 * 获取每月业绩信息
	 * 
	 * @param string $month 年月(格式:201808)
	 * @param string $field 字段
	 * @param mixed $where 筛选条件
	 * @param mixed $page 页数(默认flash不分页)
	 * @param int $listRows 每页个数
	 * @param string $order 排序
	 * @param string $group 分组
	 * @param string $having 过滤
	 * 
	 * @return array
	 */
	public function getList($month='', $field='', $where='', $page=false, $listRows=20, $order='performance_tag desc', $group='performance_tag', $having='') {
		$field = empty($field) ? '*' : $field;
		$month = empty($month) ? Tag::getMonth() : $month;
		
		$list = M('performance_'.$month)
			->field($field)
			->where($where);
		
		$_totalRows = 0;
		if ($page) {
			$temp = clone $list;
			$_totalRows = $temp->count(0);
		}
		
		if ($_totalRows > 0) {
			$list = $list->page($page, $listRows);
		}
		
		$list = $list->group($group);
		$list = empty($having) ? $list : $list->having($having);
		
		$list = $list->order($order)->select();
		
		return [
    		'paginator' => $this->paginator($_totalRows, $listRows),
    		'list' => $list,
    	];
	}
	
}