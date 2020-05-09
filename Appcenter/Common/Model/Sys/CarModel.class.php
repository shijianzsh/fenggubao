<?php
namespace Common\Model\Sys;
use Common\Model\BaseModel;

/**
 * 车模型
 *
 */
class CarModel extends BaseModel {
	
	/**
	 * 车型表
	 */
	public function CMM() {
		return M('CarModel');
	}
	
	/**
	 * 车型列表
	 *
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
	public function getModelList($field='', $where='', $page=false, $listRows=20, $order='model_id asc', $group='', $having='') {
		$field = empty($field) ? '*' : $field;
	
		$list = $this->CMM()
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