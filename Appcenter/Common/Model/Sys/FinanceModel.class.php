<?php
namespace Common\Model\Sys;
use Common\Model\BaseModel;

/**
 * 财务统计模型
 *
 */
class FinanceModel extends BaseModel {
	
	/**
	 * 获取平台统计表信息
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
	public function getFinanceList($field='', $where='', $page=false, $listRows=20, $order='finance_tag desc', $group='finance_tag', $having='') {
		$field = empty($field) ? '*' : $field;
		
		$list = M('Finance')
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
	 * 获取用户收益统计表信息
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
	public function getIncomeList($field='', $where='', $page=false, $listRows=20, $order='income_id desc', $group='income_id', $having='') {
		$field = empty($field) ? '*' : $field;
	
		$list = M('AccountIncome')
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