<?php
namespace V4\Model;

/**
 * 丰收模型
 *
 */
class MiningModel extends BaseModel {

    /**
     * 丰收记录列表
     * 
     * @param string $fields 获取字段
     * @param int $page 当前页数,当为false时视为不分页
     * @param int $listRows 每页个数
     * @param string $actionwhere 筛选条件
     * @param int $tag 记录标签， 请使用 V4\Model\Tag提供的方法获取参数值
     * 
     * @return array
     */
	public function getList($fields='*', $page=1, $listRows=10, $actionwhere='') {
		//当$page为false时不分页
		$_totalRows = 0;
		if ($page !== false) {
			$_totalRows = M('Mining')->where($actionwhere)->count(0);
		}
		 
		$list = M('Mining')->field($fields)->where($actionwhere);
		 
		if ($page !== false) {
			$list = $list->page($page, $listRows);
		}
		
		$list = $list->order('amount desc,id desc')->select();
		
		return [
			'paginator' => $this->paginator($_totalRows, $listRows),
			'list' => $list,
		];
	
	}
    
	/**
	 * 封装丰收判断条件[系统条件]
	 * 
	 * @param array $CFG 配置数据
	 */
	public function mineValidateBySystem($CFG) {
		$data = ['error'=>true, 'msg'=>'', 'is_print'=>false];
		
		if (empty($CFG) || !is_array($CFG)) {
			$data['msg'] = '配置数据异常';
			return $data;
		}
		
		//丰收开关
		$mine_switch = $CFG['mine_switch'];
		if ($mine_switch == '关闭') {
			$data['msg'] = '丰收功能已关闭';
			$data['is_print'] = true;
			return $data;
		}
	
		//一个农场所需业绩金额
		$performancePortionBase = $CFG['performance_portion_base'];
		if ($performancePortionBase <= 0) {
			$data['msg'] = '农场所需业绩金额参数有误';
			return $data;
		}
	
		//矿池金额
		$mineTotalAmount = M('Account')->where("user_id=1 and account_tag=0")->getField('account_points_balance');
	
		//今日标识
		$todayTag = Tag::getDay();
	
		//今日产出总金额
		$todayTotalMiningAmount = M('Mining')->where("user_id=0 and tag='{$todayTag}'")->getField('amount');
	
		//验证是否有矿
		/*
		if ($mineTotalAmount - $todayTotalMiningAmount <= 0) {
			$data['msg'] = '矿池金额不足';
			return $data;
		}
		*/
	
		//验证今日产出是否达到上限
		$todayPoolMaxAmount = $CFG['mine_pool_max_amount'];
		if ($todayTotalMiningAmount >= $todayPoolMaxAmount) {
			$data['msg'] = '今日产出已经达到上限';
			return $data;
		}
	
		//每日单个农场最大产出金额
		$todayMachineMaxAmount  = $CFG['mine_machine_day_max_amount'];
		if ($todayMachineMaxAmount <= 0) {
			$data['msg'] = '单日单农场最大产出金额不足';
			return $data;
		}
	
		//每次单个农场最大产出金额
		$onceMachineMaxAmount = $CFG['mine_machine_one_max_amount'];
		if ($onceMachineMaxAmount <= 0) {
			$data['msg'] = '单次单农场最大产出金额不足';
			return $data;
		}
	
		$data['error'] = false;
		return $data;
	}
	
	/**
	 * 封装丰收判断条件[用户条件]
	 *
	 * @param int $user_id 用户ID
	 * @param array $CFG 配置数据
	 */
	public function mineValidateByUser($user_id=0, $CFG) {
		$AccountModel = new AccountModel();
		
		$data = ['error'=>true, 'msg'=>'', 'is_print'=>false];
	
		if (empty($user_id)) {
			$data['msg'] = '用户参数有误';
			$data['is_print'] = true;
			return $data;
		}
		
		if (empty($CFG) || !is_array($CFG)) {
			$data['msg'] = '配置数据异常';
			return $data;
		}
	
		//获取用户数据
		$data_user = M('Member')
			->alias('m')
			->join('left join __CONSUME__ c ON m.id=c.user_id')
			->where('m.id='.$user_id)
			->field('m.id,m.level,m.is_lock,c.amount,c.amount_old,c.is_out,c.dynamic_out')
			->find();
		
		//用户是否存在
		if (!$data_user) {
			$data['msg'] = '用户信息查询失败';
			$data['is_print'] = true;
			return $data;
		}
		
		//用户是否level=2,并且is_lock=0
		if ($data_user['is_lock'] != 0) {
			$data['msg'] = '该账号已被锁定';
			$data['is_print'] = true;
			return $data;
		}
		if ($data_user['level'] != 2) {
			$data['msg'] = '非代理用户无权限';
			$data['is_print'] = true;
			return $data;
		}
		
		//用户静态收益是否已出局
// 		if ($data_user['is_out'] == '1') {
// 			$data['msg'] = '用户静态收益已出局';
// 			$data['is_print'] = true;
// 			return $data;
// 		}
		
		//用户动态收益是否已出局
		if ($data_user['dynamic_out'] == '1') {
			$data['msg'] = '农场丰收已出局，不能丰收';
			$data['is_print'] = true;
			return $data;
		}
		
		//农场个数
		$portion_info = $this->getPortionNumber($user_id, true);
		$portion = $portion_info['enabled'];
		if ($portion <= 0) {
			$data['msg'] = '无农场';
			$data['is_print'] = true;
			return $data;
		}
		
		//判断澳洲SKN股数是否足够
		$enjoy_balance = $AccountModel->getBalance($user_id, Currency::Enjoy);
		$mining_use_amount = floor ( $portion / 0.5 ) * $CFG['enjoy_mining'];
		if ($enjoy_balance < $mining_use_amount) {
			$data['msg'] = '澳洲SKN股数不足';
			$data['is_print'] = true;
			return $data;
		}
		
		//今日用户产出最大金额
		$todayMachineMaxAmount  = $CFG['mine_machine_day_max_amount'];
		$todayMaxAmount = $todayMachineMaxAmount * $portion;
		
		//今日产出金额
		$todayTag = Tag::getDay();
		$todayMiningAmount = M('Mining')->where("user_id={$user_id} and tag='{$todayTag}'")->getField('amount');
		
		//验证今日用户丰收是否达到上限
		if ($todayMiningAmount >= $todayMaxAmount) {
			$data['msg'] = '今日丰收已达上限';
			$data['is_print'] = true;
			return $data;
		}
		
		$data['error'] = false;
		return $data;
	}
	
	/**
	 * 计算农场数
	 * 
	 * @param int $user_id 用户ID(默认空则获取全部)
	 * @param boolean $return_array 是否返回数组(默认false:只返回总农场数)
	 * 
	 * @return mixed
	 */
	public function getPortionNumber($user_id=0, $return_array=false) {
		$GoldcoinPricesModel = new GoldcoinPricesModel();
		
		$performancePortionBase = M('Settings')->where("settings_code='performance_portion_base'")->getField('settings_value');
        $mineOldMachineBai = M('Settings')->where("settings_code='mine_old_machine_bai'")->getField('settings_value');
        
        if (!empty($user_id)) { //用户
        	$consume_info = M('Consume')->where('user_id='.$user_id)->field('amount, amount_old, machine_amount, level, static_worth')->find();
        	$mining_info = M('Mining')->where('tag=0 and user_id='.$user_id)->field('amount')->find();
        	$consume_rule_info = $consume_info ? M('ConsumeRule')->where('level='.$consume_info['level'])->field('out_bei')->find() : ['out_bei' => 3.5];
        } else { //平台
        	$consume_info = M('Consume')->field('sum(amount) amount, sum(amount_old) amount_old, sum(machine_amount) machine_amount, sum(static_worth) static_worth')->find();
        	$mining_info = M('Mining')->where('tag=0')->field('sum(amount) amount')->find();
        	$consume_rule_info = ['out_bei' => 3.5];
        }
        
        $consume_amount = !$consume_info ? 0 : $consume_info['amount'];
        $consume_amount_old = !$consume_info ?  0 : $consume_info['amount_old'];
        
        //公让宝实时价格
        $goldcoin_price = $GoldcoinPricesModel->getInfo('amount');
        $goldcoin_price = $goldcoin_price['amount'];
        
        //丰收总额(公让宝)
        $mining_amount = !$mining_info ? 0 : $mining_info['amount'];
        
        //内排农场数据
        $portion_old = floor( $consume_amount_old / $performancePortionBase ) * $mineOldMachineBai / 100;
        
        //正式农场数据
        $portion_release = floor( ($consume_amount - $consume_amount_old) / ( $performancePortionBase / 2 ) ) / 2;
        
        //后台充值农场数据
        $portion_recharge = !$consume_info ? 0 : $consume_info['machine_amount'];
        
        //总农场数据
        $portion = $portion_release + $portion_old + $portion_recharge;
	    $portion = sprintf('%.1f', $portion);
	    
	    //有效农场数据
	    $consume_rule_info['out_bei'] = 3.5;
	    $portion_enabled = $portion - floor ( $consume_info['static_worth'] / ( $performancePortionBase * $consume_rule_info['out_bei'] ) );
	    
	    if ($return_array) {
	    	$portion = [
	    		'old' => sprintf('%.1f', $portion_old),
	    		'release' => sprintf('%.1f', $portion_release),
	    		'recharge' => sprintf('%.1f', $portion_recharge),
	    		'enabled' => sprintf('%.1f', $portion_enabled),
	    		'all' => $portion,
	    		'pv_old' => sprintf('%.4f', $consume_amount_old),
	    		'pv_release' => sprintf('%.4f', $consume_amount - $consume_amount_old),
	    		'pv_not_generate' => sprintf('%.4f', ($consume_amount - $consume_amount_old) % $performancePortionBase),
	    	];
	    }
        
        return $portion;
	}
	
}
