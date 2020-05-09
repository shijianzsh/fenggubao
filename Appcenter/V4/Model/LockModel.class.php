<?php
namespace V4\Model;

/**
 * 锁定通证模型
 *
 */
class LockModel extends BaseModel {
	
	//实例化锁定通证表模型
	protected function M() {
		return M('lock');
	}
	
	//实例化锁定通证丰收队列表模型
	protected  function MQ() {
		return M('LockQueue');
	}

    /**
     * 获取锁定通证列表
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
			$_totalRows = $this->M()->where($actionwhere)->count(0);
		}
		 
		$list = $this->M()->field($fields)->where($actionwhere);
		 
		if ($page !== false) {
			$list = $list->page($page, $listRows);
		}
		
		$list = $list->order('id desc')->select();
		
		return [
			'paginator' => $this->paginator($_totalRows, $listRows),
			'list' => $list,
		];
	
	}
	
	/**
	 * 获取锁定通证详情
	 *
	 * @param string $fields 获取字段
	 * @param string $actionwhere 筛选条件
	 *
	 * @return array
	 */
	public function getInfo($fields='*', $actionwhere='') {
		$info = $this->M()->field($fields);
		$info = empty($actionwhere) ? $info : $info->where($actionwhere);
		$info = $info->find();
	
		return $info;
	}
	
	/**
	 * 获取锁定通证丰收队列列表
	 *
	 * @param string $fields 获取字段
	 * @param int $page 当前页数,当为false时视为不分页
	 * @param int $listRows 每页个数
	 * @param string $actionwhere 筛选条件
	 *
	 * @return array
	 */
	public function getQueueList($fields='*', $page=1, $listRows=10, $actionwhere='') {
		//当$page为false时不分页
		$_totalRows = 0;
		if ($page !== false) {
			$_totalRows = $this->MQ()->where($actionwhere)->count(0);
		}
			
		$list = $this->MQ()->field($fields)->where($actionwhere);
			
		if ($page !== false) {
			$list = $list->page($page, $listRows);
		}
	
		$list = $list->order('id desc')->select();
	
		return [
			'paginator' => $this->paginator($_totalRows, $listRows),
			'list' => $list,
		];
	
	}
}
