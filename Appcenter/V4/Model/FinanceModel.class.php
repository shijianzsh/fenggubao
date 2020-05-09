<?php

namespace V4\Model;

/**
 * 平台财务统计
 */
class FinanceModel extends BaseModel {
	
	/**
	 * 动态生成货币类型字段名
	 *
	 * @param \V4\Model\Currency $currency 货币类型， 请使用\V4\Model\Currency提供的常量
	 * @return string
	 */
	private function getFinanceField($currency) {
		return 'finance_' . $currency;
	}

    /**
     * 获取平台收益统计数据
     * @param type $fields  读取字段
     * @param \V4\Model\Tag $tag   记录标签， 请使用 V4\Model\Tag 提供的方法获取参数值
     * @return array | false 返回结果数据 或 false(无结果)
     */
    public function getItem($fields = '*', $tag = 0) {
        return M('finance')
                        ->where('`finance_tag`=%d', [$tag])
                        ->field($fields)
                        ->order('finance_id desc')
                        ->find();
    }

    /**
     * 获取用户所有收益统计数据（有初始值）
     * @param type $user_id 用户ID
     * @param \V4\Model\Tag $tag   记录标签， 请使用 V4\Model\Tag 提供的方法获取参数值
     * @return array 返回结果数据
     */
    public function getFinance($tag = 0) {
        $fields = '*';
        $item = $this->getItem('*', $tag);
        if (!$item) {
            $item = [
                'finance_id' => $item['finance_id'],
                'finance_goldcoin' => 0,
                'finance_colorcoin' => 0,
                'finance_cash' => 0,
                'finance_points' => 0,
                'finance_bonus' => 0,
                'finance_enroll' => 0,
                'finance_supply' => 0,
                'finance_credits' => 0,
                'finance_recharge' => 0,
                'finance_withdraw' => 0,
                'finance_withdraw_fee' => 0,
                'finance_profits' => 0,
                'finance_profits_bonus' => 0,
                'finance_profits_manage' => 0,
                'finance_tag' => $tag,
                'finance_uptime' => 0,
            ];
        }
        return $item;
    }

    /**
     * 获取平台财务统计记录ID
     *
     * @param \V4\Model\Tag $tag   记录标签， 请使用 V4\Model\Tag 提供的方法获取参数值
     */
    public function getFinanceIdByTag($tag = 0) {
        return M('finance')->where(['finance_tag' => $tag])->getField('finance_id');
    }
    
    /**
     * 获取5种货币类型字段名
     * 
     * @return string 用英文逗号隔开的字段名列表
     */
    public function get5FinanceFields(){
    	$cash = $this->getFinanceField(Currency::Cash);
    	$goldcoin = $this->getFinanceField(Currency::GoldCoin);
    	$colorcoin = $this->getFinanceField(Currency::ColorCoin);
    	$points = $this->getFinanceField(Currency::Points);
    	$bonus = $this->getFinanceField(Currency::Bonus);
    	$enroll = $this->getFinanceField(Currency::Enroll);
    	$supply = $this->getFinanceField(Currency::Supply);
    	$credits = $this->getFinanceField(Currency::Credits);
    	$enjoy = $this->getFinanceField(Currency::Enjoy);
    	$redelivery = $this->getFinanceField(Currency::Redelivery);
        return $cash.','.$goldcoin.','.$colorcoin.','.$points.','.$bonus.','.$enroll.','.$supply.','.$credits.','.$enjoy. ','. $redelivery;
    }
  
    /**
     * 获取数据分页列表
     * 
     * @param string $fields  读取字段
     * @param \V4\Model\Tag $tag   记录标签， 请使用 V4\Model\Tag 提供的方法获取参数值
     * @param int $page 当前页码,当为false时不分页(此时$listRows参数无效)
     * @param int $listRows 分页大小
     * @param string $actionwhere where筛选条件
     * 
     * @internal 只获取20170301至当前的
     * 
     * @return array | false 返回结果数据 或 false(无结果)
     */
    public function getPageList($fields='*', $tag=0, $page=1, $listRows=10, $actionwhere='') {
    	$where = ' 1 ';
    	
    	//兼容处理actionwhere中可能存在finance_tag参数条件的可能性
		if (!preg_match('/finance_tag(`?)\>/', $actionwhere)) {
			$where .= " and `finance_tag`>='20170301' ";
		} 
		if (!preg_match('/finance_tag(`?)\</', $actionwhere)) {
			$where .= " and `finance_tag`<='{$tag}' ";
		}
    	$where .= $actionwhere;
    
    	//当$page为false时不分页
    	$_totalRows = 0;
    	if ($page !== false) {
    		$_totalRows = M('Finance')->where($where)->count(0);
    	}
    	
    	$list = M('Finance')->where($where)->field($fields)->order('finance_id desc');
    	
    	if ($page !== false) {
    		$list = $list->page($page, $listRows);
    	}
    	
  		$list = $list->select();
    	
    	return [
    		'paginator' => $this->paginator($_totalRows, $listRows),
    		'list' => $list,
    	];
    }
    
    /**
     * 获取指定条件的指定字段的值
     * 
     * @internal 主要用于获取字段累计数据，此方法使用的为find()查询
     * 
     * @param string $fileds 读取字段
     * @param string $actionwhere 筛选条件
     * 
     * @return mixed 
     */
    public function getFieldsValues($fields='*', $actionwhere='') {
    	$info = M('Finance')->where($actionwhere)->field($fields)->find();
    	
    	return $info;
    }

}
