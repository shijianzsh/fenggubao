<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 商品模型
// +----------------------------------------------------------------------
namespace Admin\Model;
use Common\Model\CommonModel;

class ProductModel extends CommonModel {

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
		$list = $this->alias('pro');
		$list = empty($field) ? $list->field('pro.*,sto.store_name,sto.fid') : $list->field($field);
		$list = $list->join('LEFT JOIN __STORE__ as sto ON sto.id=pro.storeid');
		$list = $list->join('JOIN __MEMBER__ mem ON mem.id=sto.uid');
		$list = $list->join('left join __PRODUCT_AFFILIATE__ aff ON aff.product_id=pro.id');
		
		//排除默认管理员的店铺商品和店铺ID为0的商品,优先使用传入$map的同一个字段筛选条件
		if (!isset($map['pro.storeid'])) {
			$map['pro.storeid'] = array(array('neq', 0), array('neq', 1), 'AND');
		}
		
		$list = empty($map) ? $list : $list->where($map);
		
		$list = $distinct ? $list->distinct(true) : $list;
		$list = $limit ? $list->limit($limit) : $list;
		$list = $order ? $list->order($order) : $list->order('pro.ishot desc,pro.id desc');
		$list = $select ? $list->select() : $list->find();
		
		//为兼容字段处理,统一模拟为select数据模式
		$list = $select ? $list : array($list);
		
		//字段对应中文加载
		foreach ($list as $k=>$v) {
			
			//加载商品审核状态
			if (isset($v['manage_status'])) {
				$manage_status_cn = C('FIELD_CONFIG.product')['manage_status'];
				if (isset($manage_status_cn[$v['manage_status']])) {
					$list[$k]['manage_status_cn'] = $manage_status_cn[$v['manage_status']];
				}
			}
			
			//加载商品状态
			if (isset($v['status'])) {
				$status_cn = C('FIELD_CONFIG.product')['status'];
				if (isset($status_cn[$v['status']])) {
					$list[$k]['status_cn'] = $status_cn[$v['status']];
				}
			}
			
			//加载兑换方式
			if (isset($v['exchangeway'])) {
				$exchangeway_cn = C('FIELD_CONFIG.common')['exchangeway'];
				if (isset($exchangeway_cn[$v['exchangeway']])) {
					$list[$k]['exchangeway_cn'] = $exchangeway_cn[$v['exchangeway']];
				}
			}
			
			//处理商品图片
			if (isset($v['carousel1'])) {
				$carousel1 = json_decode($v['carousel1'], true);
				$carousel1_new = [];
				foreach ($carousel1 as $k1=>$v1) {
				    $carousel1_new[] = $v1;
				}
				$list[$k]['carousel1'] = $carousel1_new;
			}
			
		}
		
		//恢复为对应数据模式
		$list = $select ? $list : $list[0];
		
		return $list;
	}
	
}
?>