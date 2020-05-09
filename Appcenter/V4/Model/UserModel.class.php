<?php

// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 通用模型继承类
// +----------------------------------------------------------------------

namespace V4\Model;

class UserModel extends BaseModel {
    
    /**
     * 区域合伙人回本记录列表
     *
     * @param string $fields 获取字段
     * @param int $page 当前页数,当为false时视为不分页
     * @param int $listRows 每页个数
     * @param string $actionwhere 筛选条件
     *
     * @return array
     */
    public function getServiceClearingLogList($fields='*', $page=1, $listRows=10, $actionwhere='') {
        //当$page为false时不分页
        $_totalRows = 0;
        if ($page !== false) {
            $_totalRows = M('ServiceClearingLog')->where($actionwhere)->count(0);
        }
    
        $list = M('ServiceClearingLog')->field($fields)->where($actionwhere);
    
        if ($page !== false) {
            $list = $list->page($page, $listRows);
        }
    
        $list = $list->order('log_id desc')->select();
    
        return [
            'paginator' => $this->paginator($_totalRows, $listRows),
            'list' => $list,
        ];
    
    }
    
    /**
     * 金卡代理回本记录列表
     *
     * @param string $fields 获取字段
     * @param int $page 当前页数,当为false时视为不分页
     * @param int $listRows 每页个数
     * @param string $actionwhere 筛选条件
     *
     * @return array
     */
    public function getVipClearingLogList($fields='*', $page=1, $listRows=10, $actionwhere='') {
        //当$page为false时不分页
        $_totalRows = 0;
        if ($page !== false) {
            $_totalRows = M('VipClearingLog')->where($actionwhere)->count(0);
        }
    
        $list = M('VipClearingLog')->field($fields)->where($actionwhere);
    
        if ($page !== false) {
            $list = $list->page($page, $listRows);
        }
    
        $list = $list->order('log_id desc')->select();
    
        return [
            'paginator' => $this->paginator($_totalRows, $listRows),
            'list' => $list,
        ];
    
    }
    
    /**
     * 钻卡代理代理回本记录列表
     *
     * @param string $fields 获取字段
     * @param int $page 当前页数,当为false时视为不分页
     * @param int $listRows 每页个数
     * @param string $actionwhere 筛选条件
     *
     * @return array
     */
    public function getHonourVipClearingLogList($fields='*', $page=1, $listRows=10, $actionwhere='') {
    	//当$page为false时不分页
    	$_totalRows = 0;
    	if ($page !== false) {
    		$_totalRows = M('HonourVipClearingLog')->where($actionwhere)->count(0);
    	}
    
    	$list = M('HonourVipClearingLog')->field($fields)->where($actionwhere);
    
    	if ($page !== false) {
    		$list = $list->page($page, $listRows);
    	}
    
    	$list = $list->order('log_id desc')->select();
    
    	return [
	    	'paginator' => $this->paginator($_totalRows, $listRows),
	    	'list' => $list,
    	];
    
    }
    
    /**
     * 银卡代理回本记录列表
     *
     * @param string $fields 获取字段
     * @param int $page 当前页数,当为false时视为不分页
     * @param int $listRows 每页个数
     * @param string $actionwhere 筛选条件
     *
     * @return array
     */
    public function getMicroVipClearingLogList($fields='*', $page=1, $listRows=10, $actionwhere='') {
    	//当$page为false时不分页
    	$_totalRows = 0;
    	if ($page !== false) {
    		$_totalRows = M('MicroVipClearingLog')->where($actionwhere)->count(0);
    	}
    
    	$list = M('MicroVipClearingLog')->field($fields)->where($actionwhere);
    
    	if ($page !== false) {
    		$list = $list->page($page, $listRows);
    	}
    
    	$list = $list->order('log_id desc')->select();
    
    	return [
	    	'paginator' => $this->paginator($_totalRows, $listRows),
	    	'list' => $list,
    	];
    
    }
    
}
