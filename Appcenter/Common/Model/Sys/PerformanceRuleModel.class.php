<?php

namespace Common\Model\Sys;

use Common\Model\BaseModel;

/**
 * 业绩分配规则模型
 *
 */
class PerformanceRuleModel extends BaseModel {


	/**
	 * 获取业绩信息
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
	public function getList( $field = '', $where = '', $page = false, $listRows = 20, $order = 'rule_id asc', $group = 'rule_id', $having = '' ) {
		$field = empty( $field ) ? '*' : $field;

		$list = M( 'performance_rule' )
			->field( $field )
			->where( $where );

		$_totalRows = 0;
		if ( $page ) {
			$temp       = clone $list;
			$_totalRows = $temp->count( 0 );
		}

		if ( $_totalRows > 0 ) {
			$list = $list->page( $page, $listRows );
		}

		$list = $list->group( $group );
		$list = empty( $having ) ? $list : $list->having( $having );

		$list = $list->order( $order )->select();

		return [
			'paginator' => $this->paginator( $_totalRows, $listRows ),
			'list'      => $list,
		];
	}
	
	/**
	 * 获取详情
	 *
	 * @param string $fields 获取字段
	 * @param string $actionwhere 筛选条件
	 *
	 * @return array
	 */
	public function getInfo($fields='*', $actionwhere='') {
		$info = M('performance_rule')->field($fields);
		$info = empty($actionwhere) ? $info : $info->where($actionwhere);
		$info = $info->find();
	
		return $info;
	}

}