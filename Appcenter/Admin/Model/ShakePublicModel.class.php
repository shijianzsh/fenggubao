<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 摇一摇发布模型
// +----------------------------------------------------------------------
namespace Admin\Model;
use Common\Model\CommonModel;

class ShakePublicModel extends CommonModel {

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
	 * @param $group string 集合
	*/
	public function getList($map='', $select=true, $field='', $distinct=false, $limit=false, $order=false, $group=false) {
		$list = $this->alias('shp');
		$list = empty($field) ? $list->field('shp.*,mem.loginname,mem.nickname,sto.store_name') : $list->field($field);
		$list = $list->join('JOIN __MEMBER__ as mem ON mem.id=shp.uid');
		$list = $list->join('JOIN __STORE__ as sto ON sto.uid=shp.uid');
		
		//排除默认管理员的店铺发布的摇一摇
		$map['shp.uid'] = array('neq', 1);
		
		$list = empty($map) ? $list : $list->where($map);
		
		$list = $distinct ? $list->distinct(true) : $list;
		$list = $limit ? $list->limit($limit) : $list;
		$list = $order ? $list->order($order) : $list->order('shp.id desc');
		$list = $group ? $list->group($group) : $list;
		$list = $select ? $list->select() : $list->find();
		
		//为兼容字段处理,统一模拟为select数据模式
		$list = $select ? $list : array($list);
		
		//字段对应中文加载
		foreach ($list as $k=>$v) {
			
			//加载摇中标识
			if (isset($v['shake_flag'])) {
				$shake_flag_cn = C('FIELD_CONFIG.shake_public')['shake_flag'];
				if (isset($shake_flag_cn[$v['shake_flag']])) {
					$list[$k]['shake_flag_cn'] = $shake_flag_cn[$v['shake_flag']];
				}
			}
			
		}
		
		//恢复为对应数据模式
		$list = $select ? $list : $list[0];
		
		return $list;
	}
	
}
?>