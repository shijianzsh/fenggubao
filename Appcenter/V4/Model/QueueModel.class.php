<?php
namespace V4\Model;

/**
 * 执行队列模型
 *
 */
class QueueModel extends BaseModel {

    /**
     * 获取执行队列列表
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
			$_totalRows = M('ProcedureQueue')->where($actionwhere)->count(0);
		}
		 
		$list = M('ProcedureQueue')->field($fields)->where($actionwhere);
		 
		if ($page !== false) {
			$list = $list->page($page, $listRows);
		}
		
		$list = $list->order('queue_tag desc,queue_id desc')->select();
		
		//数字对应中文处理
		$field_config = C('FIELD_CONFIG.procedure_queue');
		foreach ($list as $k=>$v) {
			if (array_key_exists('queue_status', $field_config) && isset($v['queue_status'])) {
				$list[$k]['queue_status_cn'] = $field_config['queue_status'][$v['queue_status']];
			}
		}
		
		return [
			'paginator' => $this->paginator($_totalRows, $listRows),
			'list' => $list,
		];
	
	}
	
	/**
	 * 获取执行队列信息
	 * 
     * @param string $fields 获取字段
     * @param string $actionwhere 筛选条件
     * 
     * @return array
	 */
	public function getInfo($fields='*', $actionwhere='') {
		$info = M('ProcedureQueue')->field($fields);
		$info = empty($actionwhere) ? $info : $info->where($actionwhere);
		$info = $info->find();
		
		//数字对应中文处理
		$field_config = C('FIELD_CONFIG.procedure_queue');
		if (isset($info['queue_status'])) {
			$info['queue_status_cn'] = $field_config['queue_status'][$info['queue_status']];
		}
		
		return $info;
	}
	
	/**
	 * 编辑保存执行队列信息
	 * 
	 * @param array $data 编辑保存的数据
	 * 
	 * @return boolean false/true
	 */
	public function save($data) {
		if (empty($data) || !is_array($data)) {
			return false;
		}
		
		return M('ProcedureQueue')->save($data);
	}
    
}
