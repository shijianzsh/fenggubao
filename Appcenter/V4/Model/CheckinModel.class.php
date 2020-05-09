<?php
namespace V4\Model;

/**
 * 签到模型
 *
 */
class CheckinModel extends BaseModel {
	
	protected function M($tag = 0) {
		$tag = empty($tag) ? '' : '_'.$tag;
		return M('account_checkin'.$tag);
	}

    /**
     * 获取签到列表
     * 
     * @param string $fields 获取字段
     * @param int $page 当前页数,当为false时视为不分页
     * @param int $listRows 每页个数
     * @param string $actionwhere 筛选条件
     * @param int $tag 记录标签， 请使用 V4\Model\Tag提供的方法获取参数值
     * 
     * @return array
     */
	public function getList($fields='*', $page=1, $listRows=10, $actionwhere='', $tag=0) {
		//当$page为false时不分页
		$_totalRows = 0;
		if ($page !== false) {
			$_totalRows = $this->M($tag)->where($actionwhere)->count(0);
		}
		 
		$list = $this->M($tag)->field($fields)->where($actionwhere);
		 
		if ($page !== false) {
			$list = $list->page($page, $listRows);
		}
		
		$list = $list->order('checkin_id desc')->select();
		
		return [
			'paginator' => $this->paginator($_totalRows, $listRows),
			'list' => $list,
		];
	
	}
    
}
