<?php
namespace V4\Model;

/**
 * 定时任务模型
 *
 */
class TaskModel extends BaseModel {

    /**
     * 获取定时任务列表
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
			$_totalRows = M('TimerTask')->where($actionwhere)->count(0);
		}
		 
		$list = M('TimerTask')->field($fields)->where($actionwhere);
		 
		if ($page !== false) {
			$list = $list->page($page, $listRows);
		}
		
		$list = $list->order('task_id desc')->select();
		
		//数字对应中文处理
		$field_config = C('FIELD_CONFIG.timer_task');
		foreach ($list as $k=>$v) {
			if (array_key_exists('task_status', $field_config) && isset($v['task_status'])) {
				$list[$k]['task_status_cn'] = $field_config['task_status'][$v['task_status']];
			}
		}
		
		return [
			'paginator' => $this->paginator($_totalRows, $listRows),
			'list' => $list,
		];
	
	}
    
}
