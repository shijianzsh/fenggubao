<?php
namespace Common\Model\Sys;
use Common\Model\BaseModel;

/**
 * 充值(记录)模型
 *
 */
class RechargeModel extends BaseModel {
	
	/**
	 * 获取用户充值记录列表
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
	public function getUserRechargeList($field='', $where='', $page=false, $listRows=20, $order='payment_id desc', $group='payment_id', $having='') {
		$field = empty($field) ? '*' : $field;
	
		$list = M('Payment')
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
	 * 获取系统充值记录列表
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
	public function getSystemRechargeList($field='', $where='', $page=false, $listRows=20, $order='recharge_id desc', $group='recharge_id', $having='') {
		$field = empty($field) ? '*' : $field;
	
		$list = M('Recharge')
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