<?php
namespace Common\Model\Sys;
use Common\Model\BaseModel;

/**
 * 店铺模型
 *
 */
class StoreModel extends BaseModel {
	
	/**
	 * 商家表
	 */
	protected function M() {
		return M('Merchant');
	}
	
	/**
	 * 订单表
	 */
	protected function OM() {
		return M('Order');
	}

	/**
	 * 获取店铺信息
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
	public function getList($field='', $where='', $page=false, $listRows=20, $order='merchant_id desc', $group='merchant_id', $having='') {
		$field = empty($field) ? '*' : $field;
		
		$list = $this->M()
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
	 * 获取店铺订单列表
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
	public function getOrderList($field='', $where='', $page=false, $listRows=20, $order='ord.order_id desc', $group='ord.order_id', $having='') {
		$field = empty($field) ? 'ord.*' : $field;
		
		$list = $this->OM()
			->alias('ord')
			->join('left join __MERCHANT__ mer ON mer.merchant_id=ord.merchant_id')
			->join('left join __PAYMENT__ pay ON pay.order_id=ord.order_id')
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