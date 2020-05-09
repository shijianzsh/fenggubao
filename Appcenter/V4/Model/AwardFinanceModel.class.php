<?php

namespace V4\Model;

/**
 * 奖项统计
 */
class AwardFinanceModel extends BaseModel {

	/**
	 * 获取奖金记录列表
	 *
	 * @param int $page 当前页码 ,当为false时不分页(此时$listRows参数无效)
	 * @param int $listRows 分页大小
	 * @param string $fields 获取字段
	 * @param string $actionwhere where筛选条件
	 *
	 * @return array 返回数据
	 */
	public function getPageList($page = 1, $listRows = 10, $fields = '*', $actionwhere = '') {
		$_totalRows = 0;
		if ($page !== false) {
			$_totalRows = M('AccountIncome')->where($actionwhere)->count(0); //记录总条数
		}
		
		$where = ' user_id=0 ';
		$where = empty($actionwhere) ? $where : $where.' and '.$actionwhere;
	
		$list = M('AccountIncome')->where($where)->field($fields)->order('income_tag desc');
	
		if ($page !== false) {
			$list = $list->limit($listRows)->page($page);
		}
	
		$list = $list->group('income_tag')->select();
	
		return [
			'paginator' => $this->paginator($_totalRows, $listRows),
			'list' => $list,
		];
	}
    
}
