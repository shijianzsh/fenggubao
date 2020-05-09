<?php
namespace Common\Model\Sys;
use Common\Model\BaseModel;

/**
 * 用户模型
 *
 */
class ReviewModel extends BaseModel {
	
	/**
	 * 获取代理申请表数据(兼容各代理)
	 * 
	 * @param string $agent 代理类型(province,city,county)
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
	public function getAgentList($agent='province', $field='', $where='', $page=false, $listRows=20, $order='apply_id desc', $group='apply_id', $having='') {
		$field = empty($field) ? '*' : $field;
		
		$list = M('apply_'.$agent)
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
	
	/**
	 * 获取身份认证申请表数据
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
	public function getIdentiyList($field='', $where='', $page=false, $listRows=20, $order='auth_id desc', $group='auth_id', $having='') {
		$field = empty($field) ? '*' : $field;
	
		$list = M('UserAuth')
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