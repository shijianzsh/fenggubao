<?php

namespace V4\Model;

/**
 * 用户财务统计
 */
class AccountFinanceModel extends BaseModel {
    
    //管理津贴字段
    private $finance_manage_fields = 'finance_cash_starmakermanage,finance_cash_servicemanage,finance_goldcoin_starmakermanage,finance_goldcoin_servicemanage,finance_colorcoin_starmakermanage,finance_colorcoin_servicemanage';

    public $profit_fields = ' finance_total_system - (`finance_colorcoin_starmakermanage` + `finance_colorcoin_servicemanage` +  `finance_colorcoin_repeat` + `finance_colorcoin_merchant` + `finance_colorcoin_cashconsume` + `finance_colorcoin_formconsume` +  `finance_colorcoin_bonus` +  `finance_colorcoin_recommand`) as pofit';
    
    /**
     * 动态生成数据表
     * @param \V4\Model\Tag $tag   记录标签， 请使用 V4\Model\Tag 提供的方法获取参数值
     * @return string
     */
    private function getTableName($tag = 0) {
        $_tableName = 'account_finance';
        //if ($tag >= 20170600) {
        //    $_tableName .= '_' . substr($tag . '', 0, 6);
        //}
        return $_tableName;
    }

    /**
     * 动态生成数据模块
     * @param \V4\Model\Tag $tag   记录标签， 请使用 V4\Model\Tag 提供的方法获取参数值
     * @return type 返回兑换记录数据Model
     */
    protected function M($tag = 0) {
        return M($this->getTableName($tag));
    }

    /**
     * 获取用户收益统计数据
     * @param type $user_id 用户ID
     * @param type $fields  读取字段
     * @param \V4\Model\Tag $tag   记录标签， 请使用 V4\Model\Tag 提供的方法获取参数值
     * @return array | false 返回结果数据 或 false(无结果)
     */
    public function getItemById($user_id, $finace_tag) {
        $item = $this->M($finace_tag)
                        ->where('`user_id`=%d AND `finance_tag`=%d', [$user_id, $finace_tag])
                        ->order('finance_id desc')
                        ->find();
        return $item;
    }
    
    
    /**
     * 获取用户收益统计数据
     * @param type $user_id 用户ID
     * @param type $fields  读取字段
     * @param \V4\Model\Tag $tag   记录标签， 请使用 V4\Model\Tag 提供的方法获取参数值
     * @return array | false 返回结果数据 或 false(无结果)
     */
    public function getItemByUserId($user_id, $fields = '*', $tag = 0) {
        $item = $this->M($tag)
                        ->where('`user_id`=%d AND `finance_tag`=%d', [$user_id, $tag])
                        ->field($fields)
                        ->order('finance_id desc')
                        ->find();
        return $item;
    }

    /**
     * 获取每月列表
     * Enter description here ...
     * @param $user_id
     * @param $fields
     * @param $tag
     */
    public function getListMonthByUserId($user_id, $fields = '*') {
        $ww['user_id'] = $user_id;
        $ww['finance_tag'] = array(array('elt', date('Ym')), array('egt', 201703));
        $list = $this->M()->field($fields)->where($ww)->order('finance_tag desc')->select();
        foreach ($list as $k => $v) {
            $list[$k]['year'] = substr($v['finance_tag'], 0, 4);
            $list[$k]['day'] = substr($v['finance_tag'], 4, 2);
        }
        return $list;
    }
    
    
    /**
     * 按月查询
     * @param $user_id
     * @param $fields
     * @param $month
     */
    public function getListByUserId($user_id, $fields = '*', $month) {
        $startday = $month . '01';
        $endday = date('Ym', strtotime('+1 month', strtotime(substr($month, 0, 4) . '-' . substr($month, 4, 2)))) . '01';

        $ww['user_id'] = $user_id;
        $ww['finance_tag'] = array(array('lt', $endday), array('egt', $startday));

        $list = $this->M($month*100)->field($fields)->where($ww)->order('finance_tag asc')->select();
        foreach ($list as $k => $v) {
            $list[$k]['day'] = substr($v['finance_tag'], 0, 4) . '-' . substr($v['finance_tag'], 4, 2) . '-' . substr($v['finance_tag'], 6, 8);
            
            $list[$k]['isclick'] = 1;
            unset($list[$k]['finance_uptime']);
        }

        return $list;
    }

    /**
     * 获取用户所有收益统计数据（有初始值）
     * @param type $user_id 用户ID
     * @param \V4\Model\Tag $tag   记录标签， 请使用 V4\Model\Tag 提供的方法获取参数值
     * @return array 返回结果数据
     */
    public function getFinance($user_id, $tag = 0) {
        $fields = '*';
        $item = $this->getItemByUserId($user_id, $fields, $tag);
        if (!$item) {
            $item = [
                'user_id' => $user_id,
                'finance_recommand' => 0,
                'finance_repeat' => 0,
                'finance_merchant' => 0,
                'finance_service' => 0,
                'finance_company' => 0,
                'finance_shake' => 0,
                'finance_service_jian' => 0,
                'finance_company_jian' => 0,
                'finance_bonus_goldcoin' => 0,
                'finance_bonus_colorcoin' => 0,
                'finance_bonus_cash' => 0,
                'finance_manage_goldcoin' => 0,
                'finance_manage_colorcoin' => 0,
                'finance_manage_cash' => 0,
                'finance_tag' => $tag,
                'finance_uptime' => 0,
            ];
        }
        return $item;
    }

    /**
     * 获取用户财务统计记录ID
     * 
     * @param type $user_id
     * @param \V4\Model\Tag $tag   记录标签， 请使用 V4\Model\Tag 提供的方法获取参数值
     */
    public function getFinanceIdByTag($user_id, $tag = 0) {
        return $this->M($tag)->where(['user_id' => $user_id, 'finance_tag' => $tag])->getField('finance_id');
    }

    /**
     * 获取上周管理津贴通证汇总
     * Enter description here ...
     * @param int $user_id 
     * @param array $timearray 
     */
    public function getLastWeekSM($user_id, $timearray){
        $_baseName = 'account_finance';
        $table[0] = $_baseName;
//        if($timearray[3] >= 20170600){
//            if($timearray[0] != $timearray[1]){
//                //跨表
//                $table[0] = $_baseName.'_'.$timearray[0];
//                $table[1] = $_baseName.'_'.$timearray[1];
//            }else{
//                //不跨表
//                $table[0] = $_baseName.'_'.$timearray[0];
//            }
//        }else{
//            //不分表
//            $table[0] = $_baseName;
//        }
        
        $where['user_id'] = $user_id;
        $where['finance_tag'] = array(array('egt',$timearray[2]),array('elt',$timearray[3]));
        $list1 = M($table[0])->field($this->finance_manage_fields)->where($where)->select();
        if(!empty($table[1])){
            $list2 = M($table[1])->field($this->finance_manage_fields)->where($where)->select();
            $list1 = array_merge($list1,$list2);
        }
        $amount = 0;
        foreach ($list1 as $row){
            $amount += array_sum($row);
        }
        return $amount;
    }
    
    /**
     * 获取管理津贴通证汇总
     * Enter description here ...
     * @param $tag
     */
    public function getGljtAmount($user_id, $tag=0){
        $item = $this->getItemByUserId($user_id, $this->finance_manage_fields, $tag);
        $amount = 0;
        foreach ($item as $k=>$v){
            $amount += $v;
        }
        return $amount;
    }
    
    /**
     * 按月/天查询所有用户收益数据
     * 
     * @param string $fields
     * @param int $month
     * @param int $page 当前页码,当为false时不分页(此时$listRows参数无效)
     * @param int $listRows 分页大小
     * @param string $actionwhere  筛选条件
     * @param string $group 分组条件(默认acf.finance_tag)
     * 
     * @internal 此方法所有字段均需附加别名(默认前缀为acf.)
     */
    public function getListByAllUser($fields = 'acf.*', $month, $page=1, $listRows=10, $actionwhere='', $group='acf.finance_tag') {
    	$where = ' 1 ';
    	$where .= $actionwhere;
    	
    	//当$page为false时不分页
    	$_totalRows = 0;
    	if ($page !== false) {
    		$_totalRows = $this->M($month*100)
    			->alias('acf')
    			->join('JOIN __MEMBER__ mem ON mem.id=acf.user_id')
    			->where($where)
    			->group($group)
    			->field('acf.finance_id')
    			->select();
    		$_totalRows = count($_totalRows);
    	}
    	
    	$list = $this->M($month*100)
    		->alias('acf')
    		->join('JOIN __MEMBER__ mem ON mem.id=acf.user_id')
    		->field($fields)
	    	->where($where)
	    	->group($group)
	    	->order('acf.finance_tag desc');
    	
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
     * 回购金额计算
     * @param unknown $user_id
     * @return number
     */
    public function getBackMoney($user_id){
    	$item = $this->getItemById($user_id, Tag::get());
    	//已创收毛利润
    	$create_total_profits = $item['finance_profits_cash']+$item['finance_profits_weixin'];
    	//提现手续费
    	$commission = M('withdraw_cash')->where("`status` in ('0', 'W', 'S') and uid = ".$user_id)->sum('commission');
    	//已获取利润
    	$total_get_profits = $item['finance_total_system']-$item['finance_colorcoin_bonus']-$item['finance_colorcoin_starmakermanage']-$item['finance_colorcoin_servicemanage'] - $commission;
    	//计算已回购金额
    	$buyback = M('buyback')->where('user_id = '.$user_id.' and buyback_status = 2')->find();
    	if($buyback){
    		$total_get_profits = $total_get_profits + $buyback['buyback_amount'];
    	}
    	$data['total_create_profits'] = $create_total_profits;
    	$data['total_get_profits'] = $total_get_profits;
    	$data['total_notget_profits'] = $create_total_profits - $total_get_profits;
    	return $data;
    }
    
    /**
     * 获取指定条件的指定字段的值
     *
     * @internal 主要用于获取字段累计数据，此方法使用的为find()查询
     *
     * @param string $fileds 读取字段,需要加alias前缀acf.
     * @param string $actionwhere 筛选条件
     * @param int $tag 记录标签， 请使用 V4\Model\Tag 提供的方法获取参数值
     *
     * @return mixed
     */
    public function getFieldsValues($fields='acf.*', $actionwhere='', $tag) {
        $info = $this->M($tag)->alias('acf')
            ->join('join __MEMBER__ mem ON mem.id=acf.user_id') //此处强制关联可能会导致特殊情况下比如某个用户被强制删除后出现此处查询sum金额与统计数据金额不符的情况
            ->where($actionwhere)
            ->field($fields)
            ->find();
         
        return $info;
    }
    
}
