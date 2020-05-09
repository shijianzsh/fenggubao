<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 订单模型
// +----------------------------------------------------------------------
namespace Admin\Model;
use Common\Model\CommonModel;

class OrdersModel extends CommonModel {

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
	*/
	public function getList($map='', $select=true, $field='', $distinct=false, $limit=false, $order=false) {
		$list = $this->alias('ord');
		$list = empty($field) ? $list->field('ord.*,sto.store_name,sto.store_img,pro.start_time,pro.end_time,mem.nickname as receiver_nickname') : $list->field($field);
		$list = $list->join('LEFT JOIN __STORE__ as sto ON sto.id=ord.storeid');
		$list = $list->join('LEFT JOIN __PRODUCT__ as pro ON pro.id=ord.productid');
		$list = $list->join('LEFT JOIN __MEMBER__ as mem ON mem.id=ord.uid');
		
		//排除默认管理员的店铺订单,优先使用传入$map的同一个字段筛选条件
		if (!isset($map['ord.storeid'])) {
			$map['ord.storeid'] = array('neq', 1);
		}
		
		$list = empty($map) ? $list : $list->where($map);
		
		$list = $distinct ? $list->distinct(true) : $list;
		$list = $limit ? $list->limit($limit) : $list;
		$list = $order ? $list->order($order) : $list->order('ord.id desc');
		$list = $select ? $list->select() : $list->find();
		
		//为兼容字段处理,统一模拟为select数据模式
		$list = $select ? $list : array($list);
		
		//字段对应中文加载
		foreach ($list as $k=>$v) {
			
			//加载订单状态
			if (isset($v['status'])) {
				$status_cn = C('FIELD_CONFIG.orders')['status'];
				if (isset($status_cn[$v['status']])) {
					$list[$k]['status_cn'] = $status_cn[$v['status']];
				}
			}
			
			//兑换方式
			if (isset($v['exchangeway'])) {
				$exchangeway_cn = C('FIELD_CONFIG.orders')['exchangeway'];
				if (isset($exchangeway_cn[$v['exchangeway']])) {
					$list[$k]['exchangeway_cn'] = $exchangeway_cn[$v['exchangeway']];
				}
			}
			
			//货币类型
			if (isset($v['amount_type'])) {
				$amount_type_cn = C('FIELD_CONFIG.orders')['amount_type'];
				if (isset($amount_type_cn[$v['amount_type']])) {
					$list[$k]['amount_type_cn'] = $amount_type_cn[$v['amount_type']];
				}
			}
			
		}
		
		//恢复为对应数据模式
		$list = $select ? $list : $list[0];
		
		return $list;
	}
	
}
?>