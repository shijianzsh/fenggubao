<?php

use Common\Controller\UploadController;
use V4\Model\AccountModel;
use V4\Model\Currency;

/**
 * 兑换商品-验证参数
 * Enter description here ...
 * @param unknown_type $product_id
 * @param unknown_type $number
 * @param unknown_type $comment
 * @param unknown_type $uid
 * @return array(buyer,store,pw,product);
 */
function verify_exchange_confirm($product_id = 0, $number = 0, $comment, $uid = 0) {
    if ($product_id == 0 || $number == 0 || $uid == 0) {
        ajax_return('参数错误');
    }

    //1.获取商品信息
    $wherekey['id'] = $product_id;
    $wherekey['status'] = 0;
    $wherekey['manage_status'] = 1;
    $product = M('product')
            ->field('id,name,price,storeid,totalnum,exchangenum,start_time,end_time,manage_status,`status`, is_super')
            ->where($wherekey)
            ->find();
    if (!$product) {
        ajax_return('查询无商品代码!');
    }
    if ($number > $product['totalnum'] - $product['exchangenum']) {
        ajax_return('数量不足!');
    }
    if ($product['end_time'] < time() && $product['end_time'] > 0) {
        ajax_return('商品兑换时间已过，兑换失败!');
    }
    //2.验证店铺
    $where['id'] = $product['storeid'];
    $where['status'] = 0;
    $where['manage_status'] = 1;
    $store = M('store')->where($where)->find();
    if (!$store) {
        ajax_return('店铺状态异常!');
    }
    if ($store['uid'] == $uid) {
        ajax_return('自己不能购买自己店铺的商品!');
    }
    $pw_result = M('preferential_way')->where(array('store_id' => $product['storeid'], 'status' => 0, 'manage_status' => 1))->find();
    if (!$pw_result) {
        ajax_return('店铺未发布活动', 300);
    }

    //3.获取会员信息
    $whereu['id'] = $uid;
    $whereu['is_lock'] = 0;
    $user = M('member')->where($whereu)->find();
    if (empty($user)) {
        ajax_return('查询无个人代码!');
    }
    return array('buyer' => $user, 'store' => $store, 'pw' => $pw_result, 'product' => $product);
}

/**
 * 兑换商品-验证参数
 * Enter description here ...
 * @param  $orderid
 * @param  $storeid
 * @param  $cknum
 */
function verify_redeem_code($orderid, $storeid, $cknum) {
    //1.查询订单
    $where['id'] = $orderid;
    $where['storeid'] = $storeid;
    $where['exchangeway'] = 0;
    $where['order_status'] = 1;
    $order = M('orders')->where($where)->find();
    if (!$order) {
        ajax_return('订单不存在，兑换失败！');
    }
    if ($order['chknum'] != trim($cknum)) {
        ajax_return('兑换码错误，兑换失败！');
    }

    //2.验证店铺
    $where['id'] = $storeid;
    $where['status'] = 0;
    $where['manage_status'] = 1;
    $store = M('store')->where($where)->find();
    if (!$store) {
        ajax_return('店铺状态异常!');
    }
    $pw_result = M('preferential_way')->where(array('store_id' => $storeid, 'status' => 0, 'manage_status' => 1))->find();
    if (!$pw_result) {
        ajax_return('店铺未发布活动', 300);
    }

    //3.验证兑换订单是否过期
    if ($order['start_time'] > time() && $order['start_time'] > 0) {
        ajax_return('兑换时间还没到，暂时不能兑换');
    }
    if ($order['end_time'] < time() && $order['end_time'] > 0) {
        ajax_return('订单已过期');
    }
    $buyer = M('member')->where('id=' . $order['uid'])->find();
    $seller = M('member')->where('id=' . $store['uid'])->find();
    //判断是否是商超商品
    $is_super = M('product')->where('id=' . $order['productid'])->getField('is_super');
    $order['is_super'] = $is_super;

    //4.验证买家
    if ($buyer['is_blacklist'] > 0) {
        ajax_return('买家的账号被锁定，禁止一切兑换！', 300);
    }

    return array('buyer' => $buyer, 'seller' => $seller, 'order' => $order, 'pw' => $pw_result, 'store' => $store);
}

/**
 * 现金积分/丰谷宝转赠接口-验证参数
 * Enter description here ...
 * @param unknown_type $stel 转出者
 * @param unknown_type $dtel 受赠者
 * @param unknown_type $type
 * @param unknown_type $amount
 */
function verify_account_transfer($stel = '', $dtel = '', $type, $amount = 0, $CFG=array()) {

    if ($type == 2) {
//        ajax_return('GRB互转功能已停用');
    }
    if ($type == 3) {
        ajax_return('锁定资产禁止互转');
    }
	$AccountModel = new AccountModel();
	
	$current_lang = getCurrentLang();
	
	$suser = M('member')->where(array('loginname' => $stel))->find(); //转出者
	$duser = M('member')->where(array('loginname' => $dtel))->find(); //接收者
	
	//特殊不受限制账户
	$special_user = M('Settings')->where("settings_code='special_inside_transfer_account'")->getField('settings_value');
	$special_user = preg_match('/,/', $special_user) ? explode(',', $special_user) : [$special_user];
	
	//特殊不受限制体系
	$special_system = M('Settings')->where("settings_code='special_inside_transfer_system'")->getField('settings_value');
	$special_system = preg_match('/,/', $special_system) ? explode(',', $special_system) : [$special_system];
	
//	if ($current_lang != 'ko') {
		if ($type == 2) {
			if (!in_array($stel, $special_user) && !in_array($dtel, $special_user)) {

				//判断转出或接收者是否在同一个特殊不受限制体系内
				$is_system = false;
				foreach ($special_system as $k=>$v) {
					$system_info = M('Member')->where('loginname='.$v)->field('id')->find();
					if ($system_info) {
						if ( ( preg_match('/,'.$system_info['id'].',/', $suser['repath']) || $stel == $v ) && ( preg_match('/,'.$system_info['id'].',/', $duser['repath']) || $dtel == $v ) ) {
							$is_system = true;
							break;
						}
					}
				}

				if (!$is_system) {
					ajax_return('FGB互转功能已停用');
				}
			}
		}
//	}

    if ($stel == '' || $dtel == '' || $amount < 0) {
        ajax_return('参数错误', 300);
    }
    if ($stel == $dtel) {
        ajax_return('转出账号不能是自己的账号');
    }
    if(empty($suser)){
    	ajax_return('账号不存在');
    }
    if(empty($duser)){
    	ajax_return('对方账号不存在');
    }
    $dstore = M('store')->where('manage_status = 1 and uid = ' . intval($duser['id']))->find();
    if ($dstore && $type == 1) {
        ajax_return('不能转赠现金积分给商家');
    }
    if ($suser['is_lock'] == 1) {
        ajax_return('账号已锁定，请联系管理员解锁!');
    }
    if ($duser['is_lock'] == 1) {
        ajax_return('对方账号已锁定');
    }
    if ($suser['is_blacklist'] > 0) {
        ajax_return('您的账号被锁定，禁止一切兑换！', 300);
    }
    
    // 网体信息
    // $tmp = explode(',', trim($duser['repath'],','));
    // 获取最近的五个上级
    // $arr = array_slice($tmp, -5);
    //转出者必须和受赠者在一个体系
    // if ($type == 1 || $type == 2) {
        // if (!in_array($suser['id'], $arr)) {
            // ajax_return('很抱歉只能转赠给您网体内的用户');
        // }
    // }

    if ($type == 1) {
        //现金积分转账判断是否是商家
        if ( $suser['store_flag'] == 1 ) {
	        ajax_return( '商家无法进行现金积分转赠操作' );
        }
        // 是否大于最小值
        if ($amount - C('CASHTOGOLDCOIN.cash_min') < 0) {
            ajax_return('现金积分需大于最低额度' . C('CASHTOGOLDCOIN.cash_min'));
        }

        // 是否整倍
        if ($amount % C('CASHTOGOLDCOIN.cash_bei') != 0) {
            ajax_return('现金积分需为' . C('CASHTOGOLDCOIN.cash_bei') . '的整数倍');
        }
    }
    
    //提货券
    if ($type == 4) {
    	//判断转出用户是否为区县代理
    	$stel_check = M('gjj_roles')->where("user_id={$suser['id']} and role=2 and enabled=1 and audit_status=1")->field('id')->find();
    	if (!$stel_check) {
    		ajax_return('非区县代理无转赠权限');
    	}
    	
    	//判断接收用户是否为乡镇代理
    	$dtel_check = M('gjj_roles')->where("user_id={$duser['id']} and role=1 and enabled=1 and audit_status=1")->field('id')->find();
    	if (!$dtel_check) {
    		ajax_return('提货券只能转赠给乡镇代理');
    	}
    	
    	//判断接收用户是否为其线下用户
    	$dtel_line_check = preg_match('/,'.$suser['id'].',/', $duser['repath']) ? true : false;
    	if (!$dtel_line_check) {
    		ajax_return('提货券只能转赠给旗下乡镇代理');
    	}
    }
    
    //判断澳洲SKN股数是否足够
    $enjoy_balance = $AccountModel->getBalance($suser['id'], Currency::Enjoy);
    if ($enjoy_balance < $CFG['enjoy_transfer']) {
    	ajax_return('澳洲SKN股数不足');
    }

    return array('suser' => $suser, 'duser' => $duser);
}

/**
 * 转出第三方验证
 * Enter description here ...
 * @param unknown_type $stel
 * @param unknown_type $type
 * @param unknown_type $amount
 */
function verify_source_transfer($stel = '', $type, $amount = 0, $suid) {
    if ($stel == '' || $amount < 1) {
        ajax_return('参数错误', 300);
    }
    $suser = M('member')->where(array('username' => $stel))->find();
    if(empty($suser)){
    	$suser = M('member')->find($suid);
    	if(empty($suser)){
    		ajax_return('会员不存在');
    	}
    }
    if ($suser['level'] > 2) {
        ajax_return('服务中心或区域合伙人不能转赠现金积分给商家');
    }
    if ($suser['is_lock'] == 1) {
        ajax_return('账号已锁定，请联系管理员解锁!');
    }
    if ($suser['is_blacklist'] > 0) {
        ajax_return('您的账号被锁定，禁止一切兑换！', 300);
    }


    //现金积分转账判断是否是商家
    if ($type < 1 || $type > 5) {
        ajax_return('转出类型错误!');
    }
    // 是否大于最小值
    if ($amount - C('PARAMETER_CONFIG.THIRD_CURRENCY_TRANSFER_MIN') < 0 && $type != 4) {
        ajax_return('现金积分需大于最低额度' . C('PARAMETER_CONFIG.THIRD_CURRENCY_TRANSFER_MIN'));
    }

    // 是否整倍
    if ($amount % C('PARAMETER_CONFIG.THIRD_CURRENCY_TRANSFER_BEI') != 0 && $type != 4) {
        ajax_return('现金积分需为' . C('PARAMETER_CONFIG.THIRD_CURRENCY_TRANSFER_BEI') . '的整数倍');
    }

    return $suser;
}

/**
 * 第三方转入验证
 * Enter description here ...
 * @param unknown_type $stel
 * @param unknown_type $type
 * @param unknown_type $amount
 */
function verify_source_recevied($stel = '', $type, $amount = 0) {
    if ($stel == '' || $amount < 1) {
        ajax_return('参数错误', 300);
    }
    $suser = M('member')->where(array('username' => $stel))->find();
    if ($suser['level'] > 2 && $suser['store_flag'] == 1) {
        ajax_return('服务中心或区域合伙人不能转赠现金积分给商家');
    }
    if ($suser['is_lock'] == 1) {
        ajax_return('账号已锁定，请联系管理员解锁!');
    }
    if ($suser['is_blacklist'] > 0) {
        ajax_return('您的账号被锁定，禁止一切兑换！', 300);
    }


    //现金积分转账判断是否是商家
    if ($type < 1 || $type > 4) {
        ajax_return('转出类型错误!');
    }
    // 是否大于最小值
    if ($amount - C('PARAMETER_CONFIG.THIRD_CURRENCY_TRANSFER_MIN') < 0 && $type != 4) {
        ajax_return('金额需大于最低额度' . C('PARAMETER_CONFIG.THIRD_CURRENCY_TRANSFER_MIN'));
    }

    // 是否整倍
    if ($amount % C('PARAMETER_CONFIG.THIRD_CURRENCY_TRANSFER_BEI') != 0 && $type != 4) {
        ajax_return('金额需为' . C('PARAMETER_CONFIG.THIRD_CURRENCY_TRANSFER_BEI') . '的整数倍');
    }

    return $suser;
}

/**
 * 现金积分转丰谷宝-验证
 * Enter description here ...
 * @param unknown_type $uid
 * @param unknown_type $amount
 */
function verify_cash2goldcoin($uid, $amount) {
    $user = M('member')->where(array('id' => $uid))->find();
    if (!$user) {
        ajax_return('账号不存在');
    }
    // 取规则
    $cash_goldcoin_min = intval(C('CASHTOGOLDCOIN.cash_goldcoin_min'));
    $cash_goldcoin_bei = intval(C('CASHTOGOLDCOIN.cash_goldcoin_bei'));

    // 是否大于最小值
    if ($amount - $cash_goldcoin_min < 0) {
        ajax_return('现金积分转丰谷宝需大于最低额度' . $cash_goldcoin_min);
    }

    // 是否整倍
    if ($amount % $cash_goldcoin_bei != 0) {
        ajax_return('现金积分转丰谷宝需为' . $cash_goldcoin_bei . '的整数倍');
    }
    return $user;
}

/**
 * 第三方充值接口-验证
 * Enter description here ...
 * @param unknown_type $uid
 * @param unknown_type $amount
 * @param unknown_type $pay_type
 */
function verify_thirdparty_recharge($uid, $amount, $pay_type) {
    //暂停支付宝充值功能
    if ($pay_type == 1) {
        //ajax_return('支付宝充值功能暂停,请使用微信充值!', 300);
    }
    $user = M('member')->where(array('id' => $uid))->find();
    if (!$user) {
        ajax_return('账号不存在');
    }
    if (!is_numeric($amount)) {
        ajax_return('充值金额格式不正确');
    }
}

/**
 * 微信申请提现-验证参数
 * Enter description here ...
 * @param unknown_type $uid
 * @param unknown_type $amount
 */
function verify_withdraw_by_weixin($uid, $amount, $para_arr) {
    $user = M('member')->where('id = ' . $uid)->find();
    //获取用户微信昵称
    if (empty($user['weixin'])) {
        ajax_return('用户还未绑定微信');
    }
    $weixin = unserialize($user['weixin']);
    $return['wx'] = $weixin;
    $return['tiqu_cash_min'] = $para_arr['withdraw_amount_min'];
    $return['tiqu_cash_bei'] = $para_arr['withdraw_amount_bei'];
    $return['tiqu_fee_weixin'] = $para_arr['withdraw_fee'];
    //1.验证店铺
    //判断是否为正常营业商家 (店铺:审核通过+未冻结,活动:审核通过+未停用)
    /* 第一步:先判断是否存在已被审核通过的店铺和已经审核通过并启用的活动 */
    $map_store['sto.manage_status'] = array('eq', 1);
    $map_store['prw.status'] = array('eq', 0);
    $map_store['prw.manage_status'] = array('eq', 1);
    $map_store['sto.uid'] = array('eq', $uid);
    $store_info = M('Store')
            ->alias('sto')
            ->join('JOIN __PREFERENTIAL_WAY__ prw ON prw.store_id=sto.id')
            ->where($map_store)
            ->field('sto.id,sto.status,sto.date_created')
            ->find();
    if ($store_info) {
        /* 第二步:判断该店铺是否被冻结,若被冻结,则禁止进行提现操作 */
        if ($store_info['status'] == '0') {
        	//2018年前的统一按普通用户的最小金额和倍数提现
        	if($store_info['date_created'] >= 1514764800){
	            $return['tiqu_cash_min'] = $para_arr['withdraw_amount_min'];
	            $return['tiqu_cash_bei'] = $para_arr['withdraw_amount_bei'];
        	}
            $return['tiqu_fee_weixin'] = $para_arr['withdraw_fee'];
        } else {
            ajax_return('您的店铺已被冻结,无法进行提现操作');
        }
    }


    //实名认证
    if ($user['reg_time'] > 1511165083) {
        $affiliate = M('certification')->where('user_id = ' . $uid)->find();
        if (empty($affiliate)) {
            ajax_return('未实名认证');
        }
        if ($affiliate['certification_status'] == 0) {
            ajax_return('实名认证正在审核中，审核时间为1-3个工作日');
        } elseif ($affiliate['certification_status'] == 1) {
            ajax_return('实名认证被驳回');
        }
    }

    //当日提现<1w
    $sumamount = M('withdraw_cash')->where("uid = '" . $uid . "' and tiqu_type=1 and (`status` = '0' or `status` = 'S') and SUBSTR(serial_num,1,8)=FROM_UNIXTIME(UNIX_TIMESTAMP(), '%Y%m%d') ")->sum('amount');
    if ($sumamount + $amount > $para_arr['withdraw_day_amount_max']) {
        ajax_return('微信提现每人每天累计提现金积分额不能大于'.$para_arr['withdraw_day_amount_max'].'！');
    }
    // 是否大于最小值
    if ($amount - $return['tiqu_cash_min'] < 0) {
        ajax_return('提现金积分额需大于最低额度' . $return['tiqu_cash_min'] . '元');
    }
//     //B计划最小金额
//     $plan = M('user_plan')->where('user_id= ' . $uid . ' and plan_type = 1')->find();
//     if ($plan && $amount < 200) {
//     	ajax_return('提现金积分额需大于最低额度200元');
//     }
//     // 是否大于10000
//     if ($amount > $para_arr['withdraw_amount_max']) {
//         ajax_return('系统提示：提现金积分额大于1万，请使用银行卡提现');
//     }
    // 是否整倍
    if ($amount % $return['tiqu_cash_bei'] != 0 || floor($amount) != $amount) {
        ajax_return('提现金积分额需为' . $return['tiqu_cash_bei'] . '的整数倍');
    }
    //获取上次提现时间
    $lasttime = M('withdraw_cash')->where('uid=' . $uid)->order('id desc')->getField('add_time');
    $cash_between = intval(c('WITHDRAW_BETWEEN'));
    if ($cash_between > 0 && time() - intval($lasttime) < $cash_between) {
        ajax_return('请休息一下再操作！');
    }
    //验证今日次数
    $todaycount = M('withdraw_cash')->where('uid=' . $uid . ' and add_time > ' . strtotime(date('Y-m-d')))->count();
    $cash_times = intval($para_arr['withdraw_day_number_max']);
    if ($cash_times > 0 && $todaycount > $cash_times) {
        ajax_return('每日提现次数不超过' . $cash_times . '次');
    }
    return $return;
}

/**
 *支付宝申请提现-验证参数
 * Enter description here ...
 * @param unknown_type $uid
 * @param unknown_type $amount
 */
function verify_withdraw_by_alipay($uid, $amount, $para_arr) {
    $user = M('member')->where('id = ' . $uid)->find();
    $affiliate = M('user_affiliate')->where('user_id = '.$uid)->find();
    //获取用户微信昵称
    if (empty($affiliate['alipay_account'])) {
        ajax_return('用户还未绑定支付宝');
    }
    $return['alipay_account'] = $affiliate['alipay_account'];
    $return['nickname'] = $affiliate['nickname'];
    $return['tiqu_cash_min'] = $para_arr['withdraw_amount_min'];
    $return['tiqu_cash_bei'] = $para_arr['withdraw_amount_bei'];
    $return['tiqu_fee'] = $para_arr['withdraw_fee'];
    //1.验证店铺
    //判断是否为正常营业商家 (店铺:审核通过+未冻结,活动:审核通过+未停用)
    /* 第一步:先判断是否存在已被审核通过的店铺和已经审核通过并启用的活动 */
    $map_store['sto.manage_status'] = array('eq', 1);
    $map_store['prw.status'] = array('eq', 0);
    $map_store['prw.manage_status'] = array('eq', 1);
    $map_store['sto.uid'] = array('eq', $uid);
    $store_info = M('Store')
            ->alias('sto')
            ->join('JOIN __PREFERENTIAL_WAY__ prw ON prw.store_id=sto.id')
            ->where($map_store)
            ->field('sto.id,sto.status,sto.date_created')
            ->find();
    if ($store_info) {
        /* 第二步:判断该店铺是否被冻结,若被冻结,则禁止进行提现操作 */
        if ($store_info['status'] == '0') {
        	//2018年前的统一按普通用户的最小金额和倍数提现
        	if($store_info['date_created'] >= 1514764800){
        		$return['tiqu_cash_min'] = $para_arr['withdraw_amount_min'];
        		$return['tiqu_cash_bei'] = $para_arr['withdraw_amount_bei'];
        	}
            $return['tiqu_fee'] = $para_arr['withdraw_fee'];
        } else {
            ajax_return('您的店铺已被冻结,无法进行提现操作');
        }
    }


    //实名认证
    if ($user['reg_time'] > 1511165083) {
        $affiliate = M('certification')->where('user_id = ' . $uid)->find();
        if (empty($affiliate)) {
            ajax_return('未实名认证');
        }
        if ($affiliate['certification_status'] == 0) {
            ajax_return('实名认证正在审核中，审核时间为1-3个工作日');
        } elseif ($affiliate['certification_status'] == 1) {
            ajax_return('实名认证被驳回');
        }
    }

    //当日提现<1w
    $sumamount = M('withdraw_cash')->where("uid = '" . $uid . "' and tiqu_type=0 and (`status` = '0' or `status` = 'S') and SUBSTR(serial_num,1,8)=FROM_UNIXTIME(UNIX_TIMESTAMP(), '%Y%m%d') ")->sum('amount');
    if ($sumamount + $amount > $para_arr['withdraw_day_amount_max']) {
        ajax_return('支付宝提现每人每天累计提现金积分额不能大于'.$para_arr['withdraw_day_amount_max'].'！');
    }
    // 是否大于最小值
    if ($amount - $return['tiqu_cash_min'] < 0) {
        ajax_return('提现金积分额需大于最低额度' . $return['tiqu_cash_min'] . '元');
    }
//     //B计划最小金额
//     $plan = M('user_plan')->where('user_id= ' . $uid . ' and plan_type = 1')->find();
//     if ($plan && $amount < 200) {
//     	ajax_return('提现金积分额需大于最低额度200元');
//     }
    // 是否大于10000
//     if ($amount > 10000) {
//         ajax_return('系统提示：提现金积分额大于1万，请使用银行卡提现');
//     }
    // 是否整倍
    if ($amount % $return['tiqu_cash_bei'] != 0 || floor($amount) != $amount) {
        ajax_return('提现金积分额需为' . $return['tiqu_cash_bei'] . '的整数倍');
    }
    //获取上次提现时间
    $lasttime = M('withdraw_cash')->where('uid=' . $uid)->order('id desc')->getField('add_time');
    $cash_between = intval(c('WITHDRAW_BETWEEN'));
    if ($cash_between > 0 && time() - intval($lasttime) < $cash_between) {
        ajax_return('请休息一下再操作！');
    }
    //验证今日次数
    $todaycount = M('withdraw_cash')->where('uid=' . $uid . ' and add_time > ' . strtotime(date('Y-m-d')))->count();
    $cash_times = intval($para_arr['withdraw_day_number_max']);
    if ($cash_times > 0 && $todaycount > $cash_times) {
        ajax_return('每日提现次数不超过' . $cash_times . '次');
    }
    return $return;
}

/**
 * 银行卡申请提现-验证参数
 * Enter description here ...
 * @param unknown_type $uid
 * @param unknown_type $amount
 */
function verify_withdraw_by_bankcard($uid, $amount, $para_arr) {
	$AccountModel = new AccountModel();
	
    $user = M('member')->where('id = ' . $uid)->find();
    //获取用户
    if (!$user) {
        ajax_return('用户不存在');
    }
    
    //获取银行卡信息
    $bankcard = M('BankBind')->where('user_id = ' . $uid)->find();
    if (!$bankcard) {
        ajax_return('用户还未绑定银行卡');
    }
    //只能使用农行卡
    if ($bankcard['bankname'] != '中国农业银行') {
    	ajax_return('当前操作只支持中国农业银行卡，请重新绑定中国农业银行卡');
    }
    
    //兼容旧银行卡绑定相关数据
    $bankcard['inaccname'] = $bankcard['name'];
    $bankcard['inacc'] = $bankcard['cardno'];
    $bankcard['inaccbank'] = $bankcard['bankname'];
    $bankcard['inaccadd'] = $bankcard['bankaddress'];
    
    //银行卡实名验证
    if ($user['nickname'] != $bankcard['inaccname']) {
        ajax_return('银行卡账号姓名与注册姓名不一致！', 300);
    }
    //验证行内卡
    if (mb_strpos($bankcard['inaccbank'], '广发银行', null, 'utf-8') === false) {
        //验证大金额
        if ($amount > 50000 && $bankcard['bankcode'] == '') {
            ajax_return('提现金积分额大于50000需要设置银行卡联行号', 300);
        }
    }
    $return['wx']['nickname'] = $bankcard['inaccname'];
    $return['wx']['img'] = $user['img'];
    $return['tiqu_cash_min'] = $para_arr['withdraw_amount_min'];
    $return['tiqu_cash_bei'] = $para_arr['withdraw_amount_bei'];
    $return['tiqu_fee_bank'] = $para_arr['withdraw_fee'];

    //实名认证
    if ($user['reg_time'] > 1511165083) {
        $affiliate = M('certification')->where('user_id = ' . $uid)->find();
        if (empty($affiliate)) {
            ajax_return('未实名认证');
        }
        if ($affiliate['certification_status'] == 0) {
            ajax_return('实名认证正在审核中');
        } elseif ($affiliate['certification_status'] == 1) {
            ajax_return('实名认证被驳回');
        }
    }

    //1.验证店铺
    //判断是否为正常营业商家 (店铺:审核通过+未冻结,活动:审核通过+未停用)
    /* 第一步:先判断是否存在已被审核通过的店铺和已经审核通过并启用的活动 */
    $map_store['sto.manage_status'] = array('eq', 1);
    $map_store['prw.status'] = array('eq', 0);
    $map_store['prw.manage_status'] = array('eq', 1);
    $map_store['sto.uid'] = array('eq', $uid);
    $store_info = M('Store')
            ->alias('sto')
            ->join('JOIN __PREFERENTIAL_WAY__ prw ON prw.store_id=sto.id')
            ->where($map_store)
            ->field('sto.id,sto.status,sto.date_created')
            ->find();
    if ($store_info) {
        /* 第二步:判断该店铺是否被冻结,若被冻结,则禁止进行提现操作 */
        if ($store_info['status'] == '0') {
        	//2018年前的统一按普通用户的最小金额和倍数提现
        	if($store_info['date_created'] >= 1514764800){
        		$return['tiqu_cash_min'] = $para_arr['withdraw_merchant_amount_min'];
        		$return['tiqu_cash_bei'] = $para_arr['withdraw_merchant_amount_bei'];
        	}
            $return['tiqu_fee_bank'] = $para_arr['withdraw_merchant_bank_fee'];
        } else {
            ajax_return('您的店铺已被冻结,无法进行提现操作');
        }
    }


    //当日提现限额
    $sumamount = M('withdraw_cash')->where("uid = '" . $uid . "' and tiqu_type=2 and (`status` = '0' or `status` = 'S' or `status` = 'W') and SUBSTR(serial_num,1,8)=FROM_UNIXTIME(UNIX_TIMESTAMP(), '%Y%m%d') ")->sum('amount');
    if ($sumamount + $amount > $para_arr['withdraw_day_amount_max']) {
        ajax_return('每人每天累计提现金积分额不能大于'.$para_arr['withdraw_day_amount_max'].'！');
    }
    // 单笔提现金积分额<10000
    if ($amount > $para_arr['withdraw_amount_max']) {
        ajax_return('每笔提现金积分额不能超过'.$para_arr['withdraw_amount_max'].'！');
    }
    // 是否大于最小值
    if ($amount - $return['tiqu_cash_min'] < 0) {
        ajax_return('提现金积分额需大于最低额度' . $return['tiqu_cash_min'] . '元');
    }
    // 是否整倍
    if ($amount % $return['tiqu_cash_bei'] != 0 || floor($amount) != $amount) {
        ajax_return('提现金积分额需为' . $return['tiqu_cash_bei'] . '的整数倍');
    }
    
    //判断当日是否可提现
    $withdraw_week_enabled_day = $para_arr['withdraw_week_enabled_day'];
    $withdraw_week_enabled_day_list = preg_match('/,/', $withdraw_week_enabled_day) ? explode(',', $withdraw_week_enabled_day) : [$withdraw_week_enabled_day];
    $today = date('w');
    if (!in_array($today, $withdraw_week_enabled_day_list)) {
    	ajax_return("每周的周{$withdraw_week_enabled_day}可提现");
    }
    
    //判断当前时间是否可提现
    $withdraw_day_enabled_hour_start = $para_arr['withdraw_day_enabled_hour_start'];
    $withdraw_day_enabled_hour_end = $para_arr['withdraw_day_enabled_hour_end'];
    $hour = date('H');
    if ($hour < $withdraw_day_enabled_hour_start || $hour > $withdraw_day_enabled_hour_end) {
    	ajax_return("每天可提现时间为{$withdraw_day_enabled_hour_start}点到{$withdraw_day_enabled_hour_end}点");
    }

    //获取上次提现时间
    $lasttime = M('withdraw_cash')->where('uid=' . $uid)->order('id desc')->getField('add_time');
    $cash_between = intval(c('WITHDRAW_BETWEEN'));
    if ($cash_between > 0 && time() - intval($lasttime) < $cash_between) {
        ajax_return('请休息一下再操作！');
    }
    //验证今日次数
    $todaycount = M('withdraw_cash')->where('uid=' . $uid . ' and add_time > ' . strtotime(date('Y-m-d')))->count();
    $cash_times = intval($para_arr['withdraw_day_number_max']);
    if ($cash_times > 0 && $todaycount > $cash_times) {
        ajax_return('每日提现次数不超过' . $cash_times . '次');
    }
    
    //判断澳洲SKN股数是否足够
    $enjoy_balance = $AccountModel->getBalance($uid, Currency::Enjoy);
    if ($enjoy_balance < $para_arr['enjoy_tixian']) {
    	ajax_return('澳洲SKN股数不足');
    }
    
    return $return;
}

/**
 * 取消兑换订单
 * Enter description here ...
 * @param $order_id
 */
function verify_exchangeOrder_cancel($order_id) {
    //读取订单信息
    $wherekey['id'] = $order_id;
    $order = M('orders')->where($wherekey)->find();
    if (!$order) {
        ajax_return('找不到订单数据！');
    }
    if ($order['order_status'] != 1) {
        ajax_return('订单状态不支持改操作！');
    }
    //读取商品信息
    $where1["id"] = $order['productid'];
    $product = M('product')->where($where1)->find();
    if (!$product) {
        ajax_return('找不到商品数据！');
    }

    //读取会员信息
    $where2["id"] = $order['uid'];
    $member = M('member')->where($where2)->find();
    if (!$member) {
        ajax_return('找不到用户数据！');
    }
    return array('order' => $order, 'product' => $product);
}

function verify_shake($uid, $lng, $lat, $params, $result) {
    //1.验证用户是否存在
    $user = M('member')->where('is_lock=0 and is_blacklist=0')->find($uid);
    if (!$user) {
        ajax_return('无权限操作', 300);
    }
    $result['user_loginname'] = $user['loginname'];

    //2.经纬度验证
    if ($lng == '' || $lat == '') {
        ajax_return('无法获取您的位置，请打开gps！', 302, $result);
    }

    //3.统计会员当天摇奖次数 ，剩余次数
    $where3 = 'user_id=' . $uid . " and FROM_UNIXTIME(log_addtime,'%Y%m%d')=FROM_UNIXTIME(unix_timestamp(),'%Y%m%d')";
    $todayShakeTimes = M('shake_log')->where($where3)->count();
    //获取会员今日分享增加的次数
    $todayshare = M('shake_addtimes')->where('uid=' . $uid . ' and useday = \'' . date('Y-m-d') . '\'')->find();
    if ($todayshare) {
        $result['sharetimes'] = 0;
    } else {
        $result['sharetimes'] = $params['b11'];
    }
    $result['residue'] = strval($params['b7'] - $todayShakeTimes + intval($todayshare['times']));
    if ($result['residue'] < 1) {
        return null;
    }

    return $result;
}

/**
 * 发布摇一摇-验证
 * @param unknown $user_id
 * @param unknown $shake_times
 * @param unknown $shake_amount
 * @param unknown $shake_ranges
 * @return multitype:multitype:unknown  \Think\mixed
 */
function verify_shake_save($user_id, $shake_times, $shake_amount, $shake_ranges, $params) {
    //1.验证信息
    if ($shake_times == '' || $shake_amount == '' || $shake_ranges == '') {
        ajax_return('信息必填！');
        exit;
    }
    if ($shake_times < 1) {
        ajax_return('次数必须大于1！');
        exit;
    }
    if ($shake_amount < $params['shake_amount_min']) {
        ajax_return('单次金额必须大于' . $params['shake_amount_min'] . '元噢');
        exit;
    }
    if ($shake_amount * $shake_times < $params['shake_push_amount_min']) {
        ajax_return('发布金额最少' . $params['shake_push_amount_min'] . '元');
        exit;
    }

    //获取店铺-计算经纬度范围
    $storeinfo = M('store')->where('uid=' . $user_id)->order('id desc')->find();
    //验证店铺状态
    if ($storeinfo['manage_status'] != 1) {
        ajax_return('您的店铺未审核通过，发布失败');
        exit;
    }
    return array('store' => $storeinfo);
}

/**
 * 明细列表查询
 * Enter description here ...
 * @param $uid
 * @param $month 格式 201707
 * @param $tag
 */
function verify_cash_list($uid, $month = '', $tag = 0) {
    //只能查当月明细
    if ($month != date('Ym')) {
    	if(intval(date('Ym'))-1 != $month &&  $month!=201801){
        	//ajax_return('暂不支持该月记录查询');
    	}
    }
    if ($uid < 1) {
        ajax_return('参数错误');
    }
    if ($month == '' || empty($month)) {
        ajax_return('参数错误');
    }
    if ($tag != 1 && $tag != 0) {
        ajax_return('参数错误');
    }
    //$month_suffix  = substr($month, 2, strlen($month));
    return $month;
}

/**
 * 检测用户是否存在
 * Enter description here ...
 * @param $uid
 */
function verify_user($uid) {
    $user = M('member zm')
                    ->field('zm.*, IFNULL(ua.affiliate_income_disable, 0) affiliate_income_disable')
                    ->join('LEFT JOIN `zc_user_affiliate` AS ua ON zm.id = ua.user_id')
                    ->where(array('zm.id' => $uid, 'zm.is_lock' => 0))->find();
    if (!$user) {
        ajax_return('账号不存在');
    }
    return $user;
}

/**
 * 上传图片函数
 * Enter description here ...
 * @param unknown_type $folder
 * @param unknown_type $inputname
 */
function uploadImg($folder, $inputname) {
    $filename = '';
    $upload_config = array(
        'file' => $_FILES[$inputname],
        'exts' => array('jpg', 'png', 'gif', 'jpeg'),
        'path' => $folder . '/' . date('Ymd')
    );
    $Upload = new UploadController($upload_config);
    $info = $Upload->upload();
    if (!empty($info['error'])) {
        ajax_return('图片上传失败，请重新上传图片！', 300, (object) $info);
    } else {
        if (empty($info['data']['url'])) {
            ajax_return('图片上传失败，请重新上传！');
        }
        $filename = $info['data']['url'];
    }
    return $filename;
}

/**
 * 商家的买单列表
 * Enter description here ...
 * @param unknown_type $orderid
 * @param unknown_type $storeid
 * @param unknown_type $cknum
 */
function verify_order_maidan($storeid) {
    //1.验证店铺
    $where['id'] = $storeid;
    $where['status'] = 0;
    $where['manage_status'] = 1;
    $store = M('store')->where($where)->find();
    if (!$store) {
        ajax_return('店铺状态异常!');
    }
    $pw_result = M('preferential_way')->where(array('store_id' => $storeid, 'status' => 0, 'manage_status' => 1))->find();
    if (!$pw_result) {
        ajax_return('店铺未发布活动', 300);
    }

    return array('store' => $store, 'pw' => $pw_result);
}

/**
 * 验证签到用户
 * 
 * @param int $uid 用户ID
 */
function verify_checkin_user($uid) {
    //验证用户是否存在
    $where['id'] = $uid;
    $user = M('member')->field('id, loginname, is_lock, is_blacklist')->where($where)->find();
    if (empty($user)) {
        ajax_return('账号不存在');
    }
    if ($user['is_lock'] == 1) {
        ajax_return('你的账号已锁定，暂停操作！');
    }
    if ($user['is_blacklist'] > 0) {
        ajax_return('你的账号有异常，暂停操作！');
    }
}

/**
 * 验证是否已签到
 * 
 * @param int $uid 用户ID
 * 
 * @return boolean true:已签到,false:未签到
 */
function verify_checkin($uid) {
	//今日是否签到
	$ckwhere['user_id'] = $uid;
	$ckwhere['_string'] = "FROM_UNIXTIME(checkin_addtime,'%Y%m%d') = FROM_UNIXTIME(UNIX_TIMESTAMP(),'%Y%m%d')";
	$checkin = M('account_checkin')->where($ckwhere)->order('checkin_id desc')->find();
	if ($checkin) {
		return true;
	}
	
	return false;
}

/**
 * 验证看广告
 * @param unknown $adid
 * @param unknown $uid
 */
function verify_watchads($adid, $uid, $params) {

    //后台是否开启
    if ($params['ad_switch'] != 1) {
    	add_adview($adid, $uid);
        ajax_return($params['ad_close_msg']);
    }
    //周末不看
    $n = date('w');
    if (strpos($params['ad_click_enable_week'], $n) === false) {
    	$weekstr = ['周日','周一','周二','周三','周四','周五','周六'];
    	add_adview($adid, $uid);
        ajax_return($weekstr[$n].'广告无收益', 400);
    }
    //看广告时间
    if (date('H') < $params['ad_click_enable_hour']) {
    	add_adview($adid, $uid);
        ajax_return('太早了，天都没亮呢！', 400);
    }

    $user = M('member')->find($uid);
    if(empty($user)){
    	ajax_return('账号不存在');
    }
    if ($user['is_blacklist'] > 0) {
    	add_adview($adid, $uid);
        ajax_return('你的账号被锁定');
    }

    //1.今日是否已看该广告
    $w1['ad_id'] = $adid;
    $w1['user_id'] = $uid;
    $w1['_string'] = "FROM_UNIXTIME(view_addtime,'%Y%m%d') = FROM_UNIXTIME(UNIX_TIMESTAMP(),'%Y%m%d')";
    $viewad = M('ad_view')->where($w1)->find();
    if ($viewad) {
    	add_adview($adid, $uid);
        ajax_return('该广告已看', 400);
    }
    //2.获取广告
    $w2['ad_id'] = $adid;
    $w2['ad_status'] = 2;
    $ad = M('ad')->where($w2)->find();
    if (!$ad) {
        ajax_return('广告不存在');
    }

    //3.今日看广告通证汇总
    $w3['user_id'] = $uid;
    $w3['_string'] = "FROM_UNIXTIME(view_addtime,'%Y%m%d') = FROM_UNIXTIME(UNIX_TIMESTAMP(),'%Y%m%d')";
    $totalcash = M('ad_view')->field('IFNULL(sum(view_cash),0) as cash, IFNULL(sum(view_goldcoin),0) as credits')->where($w3)->find();

    //当前是积分还是现金积分 && 根据会议等级
    if ($user['level'] > 2 || $user['role'] > 0) {
        $rd = get_rand(array($params['ad_profits_vip_cash_bai'], $params['ad_profits_vip_credits_bai']));
    } else {
        $rd = get_rand(array($params['ad_profits_cash_bai'], $params['ad_profits_credits_bai']));
    }

    //如果是积分，调研单独的金额
    if ($rd == 1) {
        $ad['ad_amount'] = $ad['ad_amount_credits'];
    }
    if ($ad['ad_amount'] == 0) {
    	add_adview($adid, $uid);
        ajax_return('该广告福利今日已发放完毕', 400);
    }
    //4.根据用户等级，获得收益封顶=$max_amount， 计算用户获得金额=$ad_amount
    if ($user['level'] == 1) {
        $cash_top = $params['ad_profits_day_level_1_amount'];
        $credits_top = $params['ad_profits_day_level_1_credits_amount'];
        $ad_amount = floor($ad['ad_amount'] * $params['ad_profits_day_level_1_bai']) / 100;
    } elseif ($user['level'] == 2) {
        $cash_top = $params['ad_profits_day_level_2_amount'];
        $credits_top = $params['ad_profits_day_level_2_credits_amount'];
        $ad_amount = floor($ad['ad_amount'] * $params['ad_profits_day_level_2_bai']) / 100;
    } elseif ($user['level'] == 5) {
        $cash_top = $params['v51_ad_profits_day_level_5_amount'];
        $credits_top = $params['v51_ad_profits_day_level_5_credits_amount'];
        $ad_amount = floor($ad['ad_amount'] * $params['v51_ad_profits_day_level_5_bai']) / 100;
        
    } elseif ($user['level'] == 6) {
        $cash_top = $params['ad_profits_day_level_6_amount'];
        $credits_top = $params['ad_profits_day_level_6_credits_amount'];
        $ad_amount = floor($ad['ad_amount'] * $params['ad_profits_day_level_6_bai']) / 100;
        
    } elseif ($user['level'] == 7) {
        $cash_top = $params['ad_profits_day_level_7_amount'];
        $credits_top = $params['ad_profits_day_level_7_credits_amount'];
        $ad_amount = floor($ad['ad_amount'] * $params['ad_profits_day_level_7_bai']) / 100;
        
    }
    if ($user['role'] == 3) {
        $cash_top = $params['ad_profits_day_role_3_amount'] > $cash_top ? $params['ad_profits_day_role_3_amount'] : $cash_top;
        $credits_top = $params['ad_profits_day_role_3_credits_amount'] > $credits_top ? $params['ad_profits_day_role_3_credits_amount'] : $credits_top;
        $ad_amount = floor($ad['ad_amount'] * $params['ad_profits_day_role_3_bai']) / 100 > $ad_amount ? floor($ad['ad_amount'] * $params['ad_profits_day_role_3_bai']) / 100 : $ad_amount;
    } elseif ($user['role'] == 4) {
        $cash_top = $params['ad_profits_day_role_4_amount'] > $cash_top ? $params['ad_profits_day_role_4_amount'] : $cash_top;
        $credits_top = $params['ad_profits_day_role_4_credits_amount'] > $credits_top ? $params['ad_profits_day_role_4_credits_amount'] : $credits_top;
        $ad_amount = floor($ad['ad_amount'] * $params['ad_profits_day_role_4_bai']) / 100 > $ad_amount ? floor($ad['ad_amount'] * $params['ad_profits_day_role_4_bai']) / 100 : $ad_amount;
    }

    $coin = 'cash';
    if ($rd == 1) {
        //积分
        if ($totalcash['credits'] >= $credits_top) {
        	add_adview($adid, $uid);
            ajax_return('今日看广告收益已达到封顶。', 400);
        }
        $coin = 'credits';
    } else {
        //现金积分
        if ($totalcash['cash'] >= $cash_top) {
        	add_adview($adid, $uid);
            ajax_return('今日看广告收益已达到封顶', 400);
        }
    }

    /* ---单个广告的封顶------ */
    if ($user['level'] <= 2) {
        $and_tw1 = ' and m.`level` <= 2 and m.role<3 ';
        $level_cash_top = $ad['ad_amount_max']; //现金积分总额
        $level_credits_top = $ad['ad_amount_credits_max']; //积分总额
    }
    if ($user['role'] > 0 || $user['level'] > 2) {
        $and_tw1 = ' and (m.`level` > 2 or m.role>0 ) ';
        $level_cash_top = $ad['ad_amount_vip_max']; //现金积分总额
        $level_credits_top = $ad['ad_amount_vip_credits_max']; //积分总额
    }
    $level_cash_top = ($level_cash_top == 0) ? 100000 : $level_cash_top;
    $level_credits_top = ($level_credits_top == 0) ? 100000 : $level_credits_top;

    $tw1['v.ad_id'] = $adid;
    $tw1['_string'] = "FROM_UNIXTIME(v.view_addtime,'%Y%m%d') = FROM_UNIXTIME(UNIX_TIMESTAMP(),'%Y%m%d')";
    $level_total = M('ad_view v')->field('IFNULL(sum(v.view_cash) + sum(v.view_goldcoin),0) as cash, IFNULL(sum(v.view_credits),0) credits')
                    ->join('left join zc_member as m on m.id = v.user_id ' . $and_tw1)
                    ->where($tw1)->find();
    
    $return = array();
    $return['currenty'] = 'cash';
    if ($coin == 'cash') {
        if ($totalcash['cash'] + $ad_amount > $cash_top && $totalcash['cash'] < $cash_top) {
            $ad_amount = $cash_top - $totalcash['cash'];
        }
        //广告每日支付封顶判断
        if (!empty($level_total) && $level_total['cash'] + $ad_amount > $level_cash_top) {
        	add_adview($adid, $uid);
            ajax_return('该广告福利今日已发放完毕。', 400);
        }
        if ($ad_amount <= 0) {
        	add_adview($adid, $uid);
            ajax_return('该广告福利今日已发放完毕.', 400);
        }
        $ad_amount = floor($ad_amount*100)/100;
        $return['cash'] = $ad_amount;
        $return['msg'] = '观看成功, 收益' . $ad_amount . '元';
    } else {
        if ($totalcash['credits'] + $ad_amount > $credits_top && $totalcash['credits'] < $credits_top) {
            $ad_amount = $credits_top - $totalcash['credits'];
        }
        //广告每日支付封顶判断
        if (!empty($level_total) && $level_total['credits'] + $ad_amount > $level_credits_top) {
        	add_adview($adid, $uid);
            ajax_return('该广告福利今日已发放完毕！', 400);
        }
        $ad_amount = floor($ad_amount*100)/100;
        $return['credits'] = $ad_amount;
        $return['msg'] = '观看成功, 收益' . $ad_amount . '丰谷宝';
        $return['currenty'] = 'credits';
    }
    //2018-02-13后注册的用户，只得丰谷宝。
	if($user['reg_time'] > 1518480000 && $return['currenty'] == 'cash'){
		add_adview($adid, $uid);
		ajax_return('该广告福利今日已发放完毕！', 400);
	}
    //6.即将封顶
    $ad['view_result'] = $return;
    return $ad;
}

/**
 * 点击记录
 * @param unknown $ad_id
 * @param unknown $user_id
 */
function add_adview($ad_id, $user_id){
	//观看记录
	$vo['ad_id'] = $ad_id;
	$vo['user_id'] = $user_id;
	$vo['view_cash'] = 0;
	$vo['view_goldcoin'] = 0;
	$vo['view_redelivery'] = 0;
	$vo['view_enjoy'] = 0;
	$vo['view_credits'] = 0;
	$vo['view_addtime'] = time();
	$res1 = M('ad_view')->add($vo);
}

/**
 * 申请viip验证
 * @param unknown $uid
 */
function verify_vipapply($uid) {
    $where['id'] = $uid;
    $where['is_lock'] = 0;
    $where['is_blacklist'] = 0;
    $member = M('member')->where($where)->find();
    if (empty($member)) {
        ajax_return('此用户信息错误或不存在！', 300);
    }

    if ($member['level'] == 6) {
        ajax_return('您已经是金卡代理了，不能重复申请！', 300);
    }
    if ($member['level'] == 7) {
        ajax_return('你是钻卡代理，不能降级操作！', 300);
    }
    //是否已经缴了钻卡代理定金
    $hvip = M('user_affiliate')->where('honour_vip_unpaid_amount > 0 and user_id=' . $uid)->find();
    if ($hvip) {
        ajax_return('你申请了钻卡代理，操作无效！');
    }
    return $member;
}


/**
 * 申请viip验证
 * @param unknown $uid
 */
function verify_v_vipapply($uid) {
    $where['id'] = $uid;
    $where['is_lock'] = 0;
    $where['is_blacklist'] = 0;
    $member = M('member')->where($where)->find();
    if (empty($member)) {
        ajax_return('此用户信息错误或不存在！', 300);
    }

    if ($member['level'] == 5) {
        ajax_return('您已经是银卡代理了，不能重复申请！', 300);
    }
    if ($member['level'] > 5) {
        ajax_return('不能降级操作！', 300);
    }
    
    return $member;
}

/**
 * 申请viip验证
 * @param unknown $uid
 */
function verify_honouredvipapply($uid, $plan_type) {
    $where['id'] = $uid;
    $where['is_lock'] = 0;
    $where['is_blacklist'] = 0;
    $member = M('member')->where($where)->find();
    if (empty($member)) {
        ajax_return('此用户信息错误或不存在！', 300);
    }

    if ($member['level'] < 6) {
        //删除责任消费统计
    	M('dutyconsume')->where('user_id = '.$uid)->delete();
    }
    if ($member['level'] == 7) {
        ajax_return('您已经是钻卡代理了，不能重复申请！', 300);
    }
    //如果之前是b计划vip
    $plan = M('user_plan')->where('user_id = ' . $uid . ' and plan_type = 1')->order('plan_id desc')->find();
    if ($member['level'] == 6 && !empty($plan) && $plan_type == 0) {
        ajax_return('方案错误！', 300);
    }
    return $member;
}

/**
 * 责任消费买积分验证
 * @param unknown $uid
 */
function verify_dutyconsume($uid, $amount = 0) {
    //1.验证用户是否存在
    $user = M('member')->where('is_lock=0')->find($uid);
    if (!$user) {
        ajax_return('无权限操作', 300);
    }
    if ($user['is_blacklist'] > 0) {
        ajax_return('您的账号被锁定，禁止一切兑换！', 300);
    }
    if ($user['level'] <= 2) {
        ajax_return('您的等级不支持责任消费');
    }

    //2.计算责任消费金额
    $consume = M('dutyconsume')->where('user_id = ' . $uid)->find();
    $rest = $consume['dutyconsume_need_amount'] - $consume['dutyconsume_complete_amount'];
    if ($rest > 0) {
        $rest = sprintf('%.2f', ceil($rest * 100) / 100);
    } else {
        ajax_return('你已完成责任消费了');
    }

    return $rest;
}

/**
 * 钻卡代理复投
 * @param unknown $user_id
 */
function verify_vip_redelivery($user_id, $plan_type = 1) {
    $user = M('member')->where('id=' . $user_id)->find();
    if (empty($user)) {
        ajax_return('账号不存在');
    }
    if ($user['is_blacklist'] > 0) {
        ajax_return('您的账号被锁定，禁止一切兑换！', 300);
    }
    if ($user['level'] < 6) {
        ajax_return('身份不适合');
    }
    $plan = M('user_plan')->where('user_id=' . $user_id)->find();
    if (empty($plan)) {
        ajax_return('操作异常1');
    }
    if ($plan['plan_type'] != $plan_type) {
        ajax_return('操作异常2');
    }
    if ($plan['plan_out'] != 1) {
        ajax_return('目前不能复投');
    }
    if ($plan['plan_round'] >= 4) {
        ajax_return('只能复投4次');
    }
    return $user;
}

/**
 * 获取钻卡代理收益的最终总（现金积分+乐享币+复投币）金额
 * @param unknown $amount_params
 */
function get_honour_profits_amount($user_id, $amount_params, $static) {

    M()->execute(C('ALIYUN_TDDL_MASTER') . 'CALL BPlanCheck'.$static.'(' . $user_id . ', ' . $amount_params . ', @is_out, @out_amount, @error);');
    $res = M()->query(C('ALIYUN_TDDL_MASTER') . 'select @is_out,@out_amount,@error');


    if ($res) {
        return $res[0]['@out_amount'];
    } else {
        ajax_return('计算收益异常1');
        return 0;
    }
}

function get_honour_profits_cash_enjoy_redelivery($user_id, $amount, $params, $temp_cash_bai = 100, $static='', $remark1='') {

    $out_amount = get_honour_profits_amount($user_id, $amount, $static);
    if ($out_amount <= 0) {
        return 0;
    }

    $pp = M('user_plan')->where('plan_type = 1 and user_id = ' . $user_id)->order('plan_id desc')->find();
    // 收益用户已经出局
    if ($pp && $pp['plan_type'] == 1 && $pp['plan_out'] == 1) {
        return 0;
    }

    // 计算复投币比例
    $redel_bai = 0;
    if ($pp['plan_type'] == 1) {
        if ($pp['plan_round'] == 1) {
            $redel_bai = $params['planb_first_redelivery_bai'];
        } elseif ($pp['plan_round'] == 2) {
            $redel_bai = $params['planb_second_redelivery_bai'];
        } elseif ($pp['plan_round'] == 3) {
            $redel_bai = $params['planb_third_redelivery_bai'];
        } elseif ($pp['plan_round'] == 4) {
            $redel_bai = $params['planb_fourth_redelivery_bai'];
        }
    }

    // 收益转乐享币（自动添加明细）
    M()->execute(C('ALIYUN_TDDL_MASTER') . 'CALL Bonus_enjoy(' . $user_id . ', ' . $out_amount . ', 0, \''.$remark1.'\' , @error, @enjoy_bai);');
    $res = M()->query(C('ALIYUN_TDDL_MASTER') . 'select @error,@enjoy_bai');


    if ($res && count($res) > 0 && $res[0]['@error'] != 1) {
        $enjoy_bai = $res[0]['@enjoy_bai'];
        $cash_bai = $temp_cash_bai - $enjoy_bai - $redel_bai;
        $return['out_amount'] = $out_amount;
        $return['cash'] = ceil($out_amount * $cash_bai) / 100;
        $return['cash_bai'] = $cash_bai;
        $return['enjoy'] = ceil($out_amount * $enjoy_bai) / 100;
        $return['redelivery'] = ceil($out_amount * $redel_bai) / 100;
        if ($pp) {
            $return['plant'] = 1;
        } else {
            $return['plant'] = -1;
        }
        return $return;
    } else {
        ajax_return('计算收益异常2');
        return 0;
    }
}

/**
 * 接口返回json函数
 * Enter description here ...
 * @param $msg
 * @param $code
 * @param $data
 */
function ajax_return($msg, $code = 300, $data = '') {
    header('Content-Type:application/json; charset=utf-8');
    if ($data != '') {
        $result['result'] = $data;
    } else {
        $result['result'] = (object) array();
    }
    $result['code'] = $code;
    $result['msg'] = $msg;
    echo json_encode($result);
    exit;
}

/**
 * 获取int类型数据
 * Enter description here ...
 * @param $value
 * @param $default
 */
function getInt($value, $default = 0) {
    if (empty($value) || $value == '' || intval($value) == 0) {
        return $default;
    }
    return $value;
}

function getString($value, $default = '') {
    if (empty($value) || $value == '') {
        return $default . '';
    }
    return $value . '';
}

function postdata($url, $msg_body) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $msg_body); // 发送json
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  //不返还数据
    //使用https协议
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $result = curl_exec($ch);    //json 字符串
    curl_close($ch);
    $res = json_decode($result, true);
    return $res;
}

/**
 * 概率算法
 * @param unknown $proArr
 * @return Ambigous <string, unknown>
 */
function get_rand($proArr) {
    $result = '';
    $proSum = array_sum($proArr);
    foreach ($proArr as $key => $proCur) {
        $randNum = mt_rand(1, $proSum);
        if ($randNum <= $proCur) {
            $result = $key;
            break;
        } else {
            $proSum -= $proCur;
        }
    }

    unset($proArr);
    return $result;
}

/**
 * 
 */
function getPriceStr($v){
	if($v['block_id'] == 1){
		$cash = $v['price'] - $v['product_credits']-$v['product_supply']-$v['product_goldcoin']-$v['product_colorcoin'];
		$cash = sprintf('%.2f', $cash);
		//ios
		$data['pricestr'] = '<div style="font-size:15px; line-height:15px; margin:0px; "><del>原价'.$v['price'].'元</del> </div>';
		$data['pricestr'] .= '<div style="font-size:12px; line-height:12px; margin:0px;"><span style="color:red;"><b>'.$cash.'</b></span>元现金积分';
		//android
		$data['priceline1'] = '原价'.$v['price'].'元';
		$data['priceline2'] = '现金积分'.$cash.'元';
		if($v['product_credits'] > 0){
			$data['pricestr'] .= ' + <span style="color:red;"><b>'.$v['product_credits'].'</b></span>积分';
			$data['priceline2'] .= ' + '.$v['product_credits'].'积分';
		}
		if($v['product_supply'] > 0){
			$data['pricestr'] .= ' + <span style="color:red;"><b>'.$v['product_supply'].'</b></span>特供券';
			$data['priceline2'] .= ' + '.$v['product_supply'].'特供券';
		}
		if($v['product_goldcoin'] > 0){
			$data['pricestr'] .= ' + <span style="color:red;"><b>'.$v['product_goldcoin'].'</b></span>丰谷宝';
			$data['priceline2'] .= ' + '.$v['product_goldcoin'].'丰谷宝';
		}
		if($v['product_colorcoin'] > 0){
			$data['pricestr'] .= ' + <span style="color:red;"><b>'.$v['product_colorcoin'].'</b></span>商超券';
			$data['priceline2'] .= ' + '.$v['product_colorcoin'].'商超券';
		}
		$data['pricestr'] .= '</div>';
	}elseif($v['block_id'] == 2){  //免费区
		$cash = $v['price'] - $v['product_credits']-$v['product_supply']-$v['product_goldcoin']-$v['product_colorcoin'];
		$cash = sprintf('%.2f', $cash);
		//ios
		$data['pricestr'] .= ' <div style="font-size:12px; line-height:12px; margin:0px;">';
		//android
		$data['priceline1'] = '';
		$data['priceline2'] = '';
		$tag = 0;
		if($v['product_credits'] > 0){
			$tag=1;
			$data['pricestr'] .= '<span style="color:red;"><b>'.$v['product_credits'].'</b></span>积分';
			$data['priceline2'] .= $v['product_credits'].'积分';
		}
		if($v['product_supply'] > 0){
			$data['pricestr'] .= (($tag > 0)?' +':'').'<span style="color:red;"><b>'.$v['product_supply'].'</b></span>特供券';
			$data['priceline2'] .= (($tag > 0)?' +':'').$v['product_supply'].'特供券';
			$tag=1;
		}
		if($v['product_goldcoin'] > 0){
			$data['pricestr'] .= (($tag > 0)?' +':'').'<span style="color:red;"><b>'.$v['product_goldcoin'].'</b></span>丰谷宝';
			$data['priceline2'] .= (($tag > 0)?' +':'').$v['product_goldcoin'].'丰谷宝';
			$tag=1;
		}
		if($v['product_colorcoin'] > 0){
			$data['pricestr'] .= (($tag > 0)?' +':'').'<span style="color:red;"><b>'.$v['product_colorcoin'].'</b></span>商超券';
			$data['priceline2'] .= (($tag > 0)?' +':'').$v['product_colorcoin'].'商超券';
		}
		$data['priceline2'] .= '兑换';
		if($data['priceline2'] == '兑换'){
			$data['pricestr'] = '<div style="font-size:12px; line-height:12px; margin:0px;">免费兑换</div>';
			$data['priceline2'] = '免费兑换';
		}
		$data['pricestr'] .= '</div>';
	}else{
		$data['pricestr'] = '<div style="font-size:15px; line-height:20px; margin:0px;"><span style="color:red;"><b>'.$v['price'].'</b></span>特供券</div>';
		$data['priceline2'] = $v['price'].'特供券';
		$data['priceline1'] = '';
	}
	return $data;
}




function getUserInBashu($tag = false){
    if(empty(I('post.uid'))){
        if(empty(I('post.user_id'))){
            ajax_return('uid必填');
        }else{
            $where['id'] = I('post.user_id');
        }
    }else{
        $where['id'] = I('post.uid');
    }
    $user = M('member')->where($where)->find();
    if(empty($user)){
        ajax_return('user_id有误');
    }

    if(!$tag){
        unset($user['password']);
        unset($user['safe_password']);
        unset($user['entry']);
    }
    if($user['is_lock'] == 1){
        $this->myApiPrint('您的账号已被锁定，请联系您的营运部');
    }
    return $user;
}




/**
 * 测试微信回调用的
 * @return string
 */
function test_wxnotify() {
    $params['out_trade_no'] = 'WX2222110623672225269';
    $params['transaction_id'] = '1202382983823822898';
    $params['result_code'] = 'SUCCESS';
    $params['cash_fee'] = '2';
    $params['time_end'] = 18498329839;
    $params['amount'] = '22.2';
    return $params;
}
function test_alinotify() {
    $_POST['out_trade_no'] = '2211329418112935';
    $_POST['trade_no'] = '2211329418112935';
    $_POST['trade_status'] = 'TRADE_SUCCESS';
    $_POST['total_amount'] = '2';
    $_POST['gmt_create'] = 18498329839;
    $_POST['receipt_amount'] = '22.2';
    return $params;
}


/**
 * 封装获取广告轮播链接地址
 * 
 * @param $id 轮播ID
 */
function getLunboLink($id) {
	if (!validateExtend($id, 'NUMBER')) {
		return false;
	}
	
	$info = M('carousel')->where('car_id='.$id)->field("car_link,uid,cid,h5_path")->find();
	
	$car_link = '';
	
	if (validateExtend($info['car_link'], 'NUMBER')) {
		$car_link = U('News/showNewsDetails/id/' . $info['car_link'], '', '', true);
	} elseif (!empty($info['h5_path'])) {
		$car_link = $info['h5_path'];
	} elseif (!empty($info['uid'])) {
		
	} elseif (!empty($info['cid'])) {
		
	}
	
	return $car_link;
}

/**
 * 获取广告所属类别
 * 
 * @param $id 广告ID
 * 
 * @return type 广告类别[0:外链, 1:商家, 2:商品, 3:资讯]
 */
function getAdvType($id) {
	if (!validateExtend($id, 'NUMBER')) {
		return false;
	}
	
	$info = M('carousel')->where('car_id='.$id)->field("car_link,uid,cid,h5_path")->find();
	
	$type = 0;
	if (!empty($info['h5_path'])) {
		$type = 0;
	}
	if (!empty($info['uid'])) {
		$type = 1;
	}
	if (!empty($info['cid'])) {
		$type = 2;
	}
	if (!empty($info['car_link'])) {
		$type = 3;
	}
	
	return $type;
}