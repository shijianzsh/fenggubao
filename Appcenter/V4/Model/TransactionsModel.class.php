<?php

namespace V4\Model;

/**
 *
 * 钱包交易记录
 */
class TransactionsModel extends BaseModel
{


    public function import($data, $type = 'AJS')
    {    	
        $count = 0;
        foreach ($data as $item) {
            // 验证交易是否已经导入
            $is_import = M('Transactions')->where(['txid' => $item['txid']])->count(0);
            if ($is_import > 0) {
            	if ($type == 'SLU') { //针对SLU待导入数据:默认出现已存在txid则视为新数据已导入完毕
            		break;
            	} else {
            		continue;
            	}
            }
            $status = 0;
            $user_id = 0;
            $remark = '';
           //if ($item['account']) {
            	switch ($type) {
            		case 'ZWY':
            			$map = [
            				'zhongwy_wallet_address' => ['eq', $item['address']]
            			];
            			break;
            		case 'AJS':
            			$map = [
            				'wallet_address' => ['eq', $item['address']],
            				'wallet_address_2' => ['eq', $item['address']],
            				'_logic' => 'or'
            			];
            			break;
            		case 'SLU':
            			$map = [
            				'slu_wallet_address' => ['eq', $item['address']]
            			];
            			break;
            	}
            	if ($type != 'SLU') {
            		$map['_string'] = " user_id={$item[account]} ";
            	}
            	
            	$user_affiliate_info = M('user_affiliate')->where($map)->find();
                if (!$user_affiliate_info) {
                    $status = 2;
                    $remark = '钱包地址绑定异常';
                    continue;
                } else {
                    $user_id = $user_affiliate_info['user_id'];
                }
//             } else {
//                 $status = 2;
//                 $remark = '钱包地址未绑定用户';
//             }

            $data = [
                'user_id' => $user_id,
                'status' => $status,
                'type' => $type,
                'account' => $user_id,
                'address' => $item['address'],
                'category' => $item['category'],
                'amount' => $item['amount'],
                'txid' => $item['txid'],
                'timereceived' => $item['timereceived'],
                'attach' => json_encode($item),
                'created_time' => time(),
                'remark' => $remark,
            ];
            
            //SLU转入判断是否自动加入队列
            if ($type == 'SLU' && C('SLU_IMPORT_AUTO_ADD_QUEUE')) {
            	$data['is_queue'] = 1;
            	$data['status'] = 3;
            }
                
            if (M('Transactions')->add($data)) $count++;
        }
        return $count;
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
    	if ($page !== false) {
    		$_totalRows = M('Transactions')->where($actionwhere)->count(0);
    	}
    
    	$list = M('Transactions')->field($fields)->where($actionwhere);
    
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
     * 获取澳交所指定平台列表
     *
     * @param string $fields 获取字段
     * @param int $page 当前页数,当为false时视为不分页
     * @param int $listRows 每页个数
     * @param string $actionwhere 筛选条件
     *
     * @return array
     */
    public function getAoList($fields = 't.*', $page = 1, $listRows = 10, $actionwhere = '')
    {
    	//当$page为false时不分页
    	$_totalRows = 0;
    	if ($page !== false) {
    		$_totalRows = M('Transactions')
    			->alias('t')
    			->join('join __USER_AFFILIATE__ ua ON ua.user_id=t.user_id')
    			->where($actionwhere)
    			->count(0);
    	}
    
    	$list = M('Transactions')
    		->alias('t')
    		->field($fields)
    		->join('join __USER_AFFILIATE__ ua ON ua.user_id=t.user_id')
    		->where($actionwhere);
    
    	if ($page !== false) {
    		$list = $list->page($page, $listRows);
    	}
    
    	$list = $list->order('t.id desc')->select();
    
    	return [
	    	'paginator' => $this->paginator($_totalRows, $listRows),
	    	'list' => $list,
    	];
    }
    
    /**
     * 获取详情
     *
     * @param string $fields 获取字段
     * @param string $actionwhere 筛选条件
     *
     * @return array
     */
    public function getInfo($fields = '*', $actionwhere = '')
    {
    	$info = M('Transactions')->field($fields);
    	$info = empty($actionwhere) ? $info : $info->where($actionwhere);
    	$info = $info->find();
    
    	return $info;
    }

}