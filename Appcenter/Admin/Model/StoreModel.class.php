<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 店铺模型
// +----------------------------------------------------------------------
namespace Admin\Model;
use Common\Model\CommonModel;

class StoreModel extends CommonModel {
	
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
	public function getList($map='', $select=true, $field='', $distinct=false, $limit=false, $order=false, $w2=false) {
		$list = $this->alias('sto');
		$list = empty($field) ? $list->field('sto.*,fir.fm_name,sec.sm_name,sm_image,mem.loginname,mem.nickname,mem.username,mem.id mid, count(o.id) num') : $list->field($field);
		$list = $list->join('LEFT JOIN __FIRST_MENU__ as fir ON fir.fm_id=sto.fid');
		$list = $list->join('LEFT JOIN __SECOND_MENU__ as sec ON sec.sm_id=sto.sid');
		$list = $list->join('LEFT JOIN __MEMBER__ as mem ON mem.id=sto.uid');
		$list = $list->join('LEFT JOIN __PREFERENTIAL_WAY__ prw ON prw.store_id=sto.id');
		if($w2){
			$list = $list->join("left join zc_orders as o on o.storeid = sto.id and FROM_UNIXTIME(o.time,'%Y%m')=FROM_UNIXTIME(UNIX_TIMESTAMP(),'%Y%m')");
		}else{
			$list = $list->join('left join zc_orders as o on o.storeid = sto.id');
		}
		
		//排除默认管理员的店铺
		$map['mem.id'] = array('neq', 1);
		
		$list = empty($map) ? $list : $list->where($map);
		
		$list = $distinct ? $list->distinct(true) : $list;
		$list = $limit ? $list->limit($limit) : $list;
		$list = $order ? $list->order($order) : $list->order('sto.id desc');
		$list = $list->group('sto.id');
		$list = $select ? $list->select() : $list->find();
		
		//为兼容字段处理,统一模拟为select数据模式
		$list = $select ? $list : array($list);
		
		//字段对应中文加载
		foreach ($list as $k=>$v) {
			
			//加载店铺审核状态
			if (isset($v['manage_status'])) {
				$manage_status_cn = C('FIELD_CONFIG.store')['manage_status'];
				if (isset($manage_status_cn[$v['manage_status']])) {
					$list[$k]['manage_status_cn'] = $manage_status_cn[$v['manage_status']];
				}
			}
			
			//加载店铺状态
			if (isset($v['status'])) {
				$status_cn = C('FIELD_CONFIG.store')['status'];
				if (isset($status_cn[$v['status']])) {
					$list[$k]['status_cn'] = $status_cn[$v['status']];
				}
			}
			
			//加载服务状态
			if (isset($v['service'])) {
				$service_cn = C('FIELD_CONFIG.store')['service'];
				if (isset($service_cn[$v['service']])) {
					$list[$k]['service_cn'] = $service_cn[$v['service']];
				}
			}
			
		}
		
		//恢复为对应数据模式
		$list = $select ? $list : $list[0];
		
		return $list;
	}
	
	
	
	/**
	 * 获取信息列表-优化过的
	 * @param $map array where条件
	 * @param $select boolean 获取数据方式[默认:select,可选:find]
	 * @param $field string 要读取的字段列表
	 * @param $distinct boolean 是否查询唯一不同的值
	 * @param $limit string 条数
	 * @param $order string 排序
	*/
	public function getNewList($map='', $select=true, $field='', $distinct=false, $limit=false, $order=false) {
		$list = $this->alias('sto');
		$list = empty($field) ? $list->field('sto.*,mem.loginname,mem.nickname,mem.username,mem.id mid') : $list->field($field);
		$list = $list->join('LEFT JOIN __MEMBER__ as mem ON mem.id=sto.uid');
		$list = $list->join('LEFT JOIN __PREFERENTIAL_WAY__ prw ON prw.store_id=sto.id');
		
		
		//排除默认管理员的店铺
		$map['mem.id'] = array('neq', 1);
		
		$list = empty($map) ? $list : $list->where($map);
		
		$list = $distinct ? $list->distinct(true) : $list;
		$list = $limit ? $list->limit($limit) : $list;
		$list = $order ? $list->order($order) : $list->order('sto.id desc');
		//$list = $list->fetchSql(true);
		$list = $select ? $list->select() : $list->find();
		
		//exit($list);
		
		//为兼容字段处理,统一模拟为select数据模式
		$list = $select ? $list : array($list);
		
		//字段对应中文加载
		foreach ($list as $k=>$v) {
			
			//加载店铺审核状态
			if (isset($v['manage_status'])) {
				$manage_status_cn = C('FIELD_CONFIG.store')['manage_status'];
				if (isset($manage_status_cn[$v['manage_status']])) {
					$list[$k]['manage_status_cn'] = $manage_status_cn[$v['manage_status']];
				}
			}
			
			//加载店铺状态
			if (isset($v['status'])) {
				$status_cn = C('FIELD_CONFIG.store')['status'];
				if (isset($status_cn[$v['status']])) {
					$list[$k]['status_cn'] = $status_cn[$v['status']];
				}
			}
			
			//加载服务状态
			if (isset($v['service'])) {
				$service_cn = C('FIELD_CONFIG.store')['service'];
				if (isset($service_cn[$v['service']])) {
					$list[$k]['service_cn'] = $service_cn[$v['service']];
				}
			}
			
		}
		
		//恢复为对应数据模式
		$list = $select ? $list : $list[0];
		
		return $list;
	}
	
}
?>