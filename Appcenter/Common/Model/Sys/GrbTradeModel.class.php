<?php
namespace Common\Model\Sys;
use Common\Model\BaseModel;

/**
 * 公让宝流通兑换模型
 *
 */
class GrbTradeModel extends BaseModel {

	
	//实例化模型
	protected function M()
	{
		return M('Trade');
	}
	
	
	/**
	 * 获取列表
	 *
	 * @param string $fields 获取字段
	 * @param int $page 当前页数,当为false时视为不分页
	 * @param int $listRows 每页个数
	 * @param string $actionwhere 筛选条件
	 *
	 * @return array
	 */
	public function getList($fields = '*', $page = 1, $listRows = 10, $actionwhere = '')
	{
		//当$page为false时不分页
		$_totalRows = 0;
		if ($page) {
			$_totalRows = $this->M()->where($actionwhere)->count(0);
		}
	
		$list = $this->M()->field($fields)->where($actionwhere);
	
		if ($_totalRows > 0) {
			$list = $list->page($page, $listRows);
		}
	
		$list = $list->order('uptime desc,id desc')->select();
	
		return [
			'paginator' => $this->paginator($_totalRows, $listRows),
			'list' => $list,
		];
	}
	
	/**
	 * 获取新闻资讯详情
	 *
	 * @param string $fields 获取字段
	 * @param string $actionwhere 筛选条件
	 *
	 * @return array
	 */
	public function getInfo($fields = '*', $actionwhere = '')
	{
		$info = $this->M()->field($fields);
		$info = empty($actionwhere) ? $info : $info->where($actionwhere);
		$info = $info->find();
		
		return $info;
	}
	

}