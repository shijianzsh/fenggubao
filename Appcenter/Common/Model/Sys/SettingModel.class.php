<?php
namespace Common\Model\Sys;
use Common\Model\BaseModel;

/**
 * 配置模型
 *
 */
class SettingModel extends BaseModel {

	/**
	 * 获取配置组表相关数据
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
	public function getGroupList($field='', $where='', $page=false, $listRows=20, $order='group_order desc,group_id asc', $group='group_id', $having='') {
		$field = empty($field) ? '*' : $field;

		$list = M('SettingsGroup')
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
	 * 获取配置表相关数据
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
	public function getList($field='', $where='', $page=false, $listRows=20, $order='group_id asc,  settings_order desc, settings_id asc', $group='settings_id', $having='') {
		$field = empty($field) ? '*' : $field;

		$list = M('Settings')
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