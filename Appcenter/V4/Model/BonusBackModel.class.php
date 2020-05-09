<?php
namespace V4\Model;

/**
 * 回购模型
 *
 */
class BonusBackModel extends BaseModel {

    /**
     * 获取回购申请列表
     * 
     * @param string $fields 获取字段
     * @param int $page 当前页数,当为false时视为不分页
     * @param int $listRows 每页个数
     * @param string $actionwhere 筛选条件
     * 
     * @return array
     */
	public function getList($fields='*', $page=1, $listRows=10, $actionwhere='') {
		//当$page为false时不分页
		$_totalRows = 0;
		if ($page !== false) {
			$_totalRows = M('Buyback')->where($actionwhere)->count(0);
		}
		 
		$list = M('Buyback')->field($fields)->where($actionwhere);
		 
		if ($page !== false) {
			$list = $list->page($page, $listRows);
		}
		
		$list = $list->order('buyback_id desc')->select();
		
		//数字对应中文处理
		$field_config = C('FIELD_CONFIG.buyback');
		foreach ($list as $k=>$v) {
			if (array_key_exists('buyback_status', $field_config) && isset($v['buyback_status'])) {
				$list[$k]['buyback_status_cn'] = $field_config['buyback_status'][$v['buyback_status']];
			}
		}
		
		return [
			'paginator' => $this->paginator($_totalRows, $listRows),
			'list' => $list,
		];
	
	}
	
	/**
	 * 获取回购申请详情
	 *
	 * @param string $fields 获取字段
	 * @param string $actionwhere 筛选条件
	 *
	 * @return array
	 */
	public function getInfo($fields='*', $actionwhere='') {
		$info = M('Buyback')->field($fields);
		$info = empty($actionwhere) ? $info : $info->where($actionwhere);
		$info = $info->find();
	
		//数字对应中文处理
		$field_config = C('FIELD_CONFIG.buyback');
		if (isset($info['buyback_status'])) {
			$info['buyback_status_cn'] = $field_config['buyback_status'][$info['buyback_status']];
		}
	
		return $info;
	}
	
	/**
	 * 审核回购申请信息
	 * 
	 * @param array $data 编辑保存的数据
	 * @param string $actionwhere 筛选条件
	 * 
	 * @return boolean false/true
	 */
	public function save($data, $actionwhere='') {
		if (empty($data) || !is_array($data)) {
			return false;
		}
		
		return M('Buyback')->where($actionwhere)->save($data);
	}
    
}
