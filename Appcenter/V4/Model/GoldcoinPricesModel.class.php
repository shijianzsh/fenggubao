<?php

namespace V4\Model;

/**
 * 公让宝实时价格模型
 *
 */
class GoldcoinPricesModel extends BaseModel
{

    //实例化锁定通证表模型
    protected function M()
    {
        return M('GoldcoinPrices');
    }

    /**
     * 获取实时价格列表
     *
     * @param string $fields 获取字段
     * @param int $page 当前页数,当为false时视为不分页
     * @param int $listRows 每页个数
     * @param string $actionwhere 筛选条件
     * 
     * @param string $type 第三方类型(AJS:澳交所,ZWY:中网云,SLU:SLU)
     *
     * @return array
     */
    public function getList($fields = '*', $page = 1, $listRows = 10, $actionwhere = '', $type)
    {
    	$type = empty($type) ? C('GRB_PRICE_TYPE') : $type;
    	
    	//类型筛选
    	if (is_array($actionwhere) || empty($actionwhere)) {
    		$actionwhere['type'] = ['eq', $type];
    	} else {
    		$actionwhere .= " and type='{$type}' ";
    	}
    	
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
     * 获取实时价格详情
     *
     * @param string $fields 获取字段
     * @param string $actionwhere 筛选条件
     * @param string $order 排序
     * 
     * @param string $type 第三方类型(AJS:澳交所,ZWY:中网云,SLU:SLU)
     *
     * @return array
     */
    public function getInfo($fields = '*', $actionwhere = '', $order = 'id desc', $type)
    {
    	$type = empty($type) ? C('GRB_PRICE_TYPE') : $type;
    	
    	if (is_array($actionwhere) || empty($actionwhere)) {
    		$actionwhere['type'] = ['eq', $type];
    	} else {
    		$actionwhere .= " and type='{$type}' ";
    	}
    	
        $info = $this->M()->field($fields);
        $info = empty($actionwhere) ? $info : $info->where($actionwhere);
        $info = $info->order($order);
        $info = $info->find();

        return $info;
    }

    /**
     * 生成最新价格
     * @param $price 单价
     * @param $type 平台类型
     * @param $ajs_price_min 保底价格(默认0:无保底价格)
     */
    public function add($price, $type='', $ajs_price_min=0)
    {
    	$price_original = $price;
    	
    	//针对实时单价核验保底价格大于0的进行是否采用保底价格分析
    	if ($ajs_price_min > 0) {
    		$price = $price < $ajs_price_min ? $ajs_price_min : $price;
    	}
    	
    	$data = [
    		'amount' => $price,
    		'amount_original' => $price_original,
    		'uptime' => time(),
    		'type' => $type
    	];
        $this->M()->add($data);
        
        $this->M()->order('id desc')->limit(1000, 10000)->delete();
    }

	/**
	 * 获取公让宝对应人民币金额
	 *
	 * @param decimal $price 待转换的公让宝金额
     * @param string $type 第三方类型(AJS:澳交所,ZWY:中网云,SLU:SLU)
	 */
	public function getRmbByGrb($price = '0.00', $type) {
		if ( !validateExtend( $price, 'MONEY' ) ) {
			return false;
		}
		
		$type = empty($type) ? C('GRB_PRICE_TYPE') : $type;

		//获取公让宝实时价格
		$price_info = $this->getInfo('amount', '', 'id desc', $type);
		$price_now = sprintf('%.2f', $price_info['amount'] * $price);

		return $price_now;
	}

	/**
	 * 获取人民币对应公让宝金额
	 *
	 * @param decimal $price 待转换的人民币金额
     * @param string $type 第三方类型(AJS:澳交所,ZWY:中网云,SLU:SLU)
	 */
	public function getGrbByRmb($price = '0.00', $type) {
		if ( !validateExtend( $price, 'MONEY' ) ) {
			return false;
		}
		
		$type = empty($type) ? C('GRB_PRICE_TYPE') : $type;

		//获取公让宝实时价格
		$price_info = $this->getInfo('amount', '', 'id desc', $type);
		$price_now = 1 / $price_info['amount'] * $price;

		return $price_now;
	}

}
