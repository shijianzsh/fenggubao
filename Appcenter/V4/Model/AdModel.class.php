<?php
namespace V4\Model;

/**
 * 广告模型
 *
 */
class AdModel extends BaseModel {
	
	//实例化ad广告表模型
	protected function M_Ad($tag = 0) {
		$tag = empty($tag) ? '' : '_'.$tag;
		return M('ad'.$tag);
	}
	
	//实例化ad_view广告浏览记录表模型
	protected function M_AdView($tag = 0) {
		$tag = empty($tag) ? '' : '_'.$tag;
		return M('ad_view'.$tag);
	}

    /**
     * 获取广告列表
     * 
     * @param string $fields 获取字段
     * @param int $page 当前页数,当为false时视为不分页
     * @param int $listRows 每页个数
     * @param string $actionwhere 筛选条件
     * @param int $tag 记录标签， 请使用 V4\Model\Tag提供的方法获取参数值
     * 
     * @return array
     */
	public function getAdList($fields='*', $page=1, $listRows=10, $actionwhere='', $tag=0) {
		//当$page为false时不分页
		$_totalRows = 0;
		if ($page !== false) {
			$_totalRows = $this->M_Ad($tag)->where($actionwhere)->count(0);
		}
		 
		$list = $this->M_Ad($tag)->field($fields)->where($actionwhere);
		 
		if ($page !== false) {
			$list = $list->page($page, $listRows);
		}
		
		$list = $list->order('ad_id desc')->select();
		
		return [
			'paginator' => $this->paginator($_totalRows, $listRows),
			'list' => $list,
		];
	
	}
	
	/**
	 * 添加广告
	 * 
	 * @param array $data 待添加数据数组
	 * @param int $tag 记录标签， 请使用 V4\Model\Tag提供的方法获取参数值
	 * 
	 * return mixed
	 */
	public function adAdd($data, $tag=0) {
		$return = ['status'=>true, 'id'=>''];
		
		if (!$this->M_ad($tag)->create($data, '', true)) {
			$return['status'] = $this->M_ad($tag)->getError();
		} else {
			$return['id'] = $this->M_ad($tag)->add();
		}
		
		return $return;
	}
	
	/**
	 * 获取广告详情
	 *
	 * @param string $fields 获取字段
	 * @param string $actionwhere 筛选条件
	 * @param int $tag 记录标签， 请使用 V4\Model\Tag提供的方法获取参数值
	 *
	 * @return array
	 */
	public function getAdInfo($fields='*', $actionwhere='', $tag=0) {
		$info = $this->M_ad($tag)->field($fields);
		$info = empty($actionwhere) ? $info : $info->where($actionwhere);
		$info = $info->find();
	
		return $info;
	}
	
	/**
	 * 保存广告信息
	 *
	 * @param array $data 编辑保存的数据
	 * @param string $actionwhere 筛选条件
	 * @param int $tag 记录标签， 请使用 V4\Model\Tag提供的方法获取参数值
	 *
	 * @return boolean false/true
	 */
	public function adSave($data, $actionwhere='', $tag=0) {
		if (empty($data) || !is_array($data)) {
			return false;
		}
	
		return $this->M_Ad($tag)->where($actionwhere)->save($data);
	}
	
	/**
	 * 删除指定广告
	 *
	 * @param string $actionwhere 筛选条件
	 * @param int $tag 记录标签， 请使用 V4\Model\Tag提供的方法获取参数值
	 *
	 * @return boolean false/true
	 */
	public function adDelete($actionwhere='', $tag=0) {
	    if (empty($actionwhere)) {
	        return false;
	    }
	
	    return $this->M_Ad($tag)->where($actionwhere)->delete();
	}
	
	/**
	 * 获取广告浏览记录列表
	 *
	 * @param string $fields 获取字段
	 * @param int $page 当前页数,当为false时视为不分页
	 * @param int $listRows 每页个数
	 * @param string $actionwhere 筛选条件
	 * @param int $tag 记录标签， 请使用 V4\Model\Tag提供的方法获取参数值
	 *
	 * @return array
	 */
	public function getAdViewList($fields='*', $page=1, $listRows=10, $actionwhere='', $tag=0) {
		//当$page为false时不分页
		$_totalRows = 0;
		if ($page !== false) {
			$_totalRows = $this->M_AdView($tag)->where($actionwhere)->count(0);
		}
			
		$list = $this->M_AdView($tag)->field($fields)->where($actionwhere);
			
		if ($page !== false) {
			$list = $list->page($page, $listRows);
		}
	
		$list = $list->order('view_id desc')->select();
	
		return [
			'paginator' => $this->paginator($_totalRows, $listRows),
			'list' => $list,
		];
	
	}
    
}
