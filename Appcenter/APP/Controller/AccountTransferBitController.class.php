<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | app转出给第三方BIT平台
// +----------------------------------------------------------------------
namespace APP\Controller;

use Common\Controller\ApiController;
use V4\Model\AccountModel;
use V4\Model\Currency;
use V4\Model\CurrencyAction;
use V4\Model\AccountRecordModel;
use V4\Model\OrderModel;
use V4\Model\Platform3Model;
use V4\Model\GrbTradeModel;
use V4\Model\EnjoyModel;
use V4\Model\WalletModel;
use WalletConfig;

class AccountTransferBitController extends ApiController
{

    /**
     * 转账说明
     * 
     * -- @param string $wallet_type 钱包类型(澳交所:AJS, 中网云:ZWY, AGX:AOGEX, 华佗商城:HT, SLU:SLU),默认ZWY
     */
    public function transferDescription()
    {
    	$user_id = $this->app_common_data['uid'];
    	
    	$current_lang = getCurrentLang(true);
    	
        $zwy_description = M('Settings')->where("settings_code='zhongwy_trade_caption".$current_lang."'")->getField('settings_value');
        $ajs_description = M('Settings')->where("settings_code='ajs_trade_caption".$current_lang."'")->getField('settings_value');
        $ht_description = M('Settings')->where("settings_code='ht_trade_caption".$current_lang."'")->getField('settings_value');
        $slu_description = M('Settings')->where("settings_code='slu_trade_caption".$current_lang."'")->getField('settings_value');

        $balance = AccountModel::getInstance()->getBalance($user_id, Currency::GoldCoin);

        $zwy_wallet_address = M('Trade')->where(['user_id' => $user_id, 'type' => 'ZWY'])->order('id desc')->getField('wallet_address');

        //第三方平台
        $data['wallet_platform'] = [
            ['tag' => 'ZWY', 'title' => '转出到信企贵交', 'description' => $zwy_description, 'title_grb' => sprintf('转出流通通证份数（余额：%.4f 份）', $balance), 'title_address' => sprintf('转账地址<font color="red">(信企贵交GRB汇率：%s元/ 个)</font>', $this->CFG['zhongwy_trade_grb_rate']), 'title_address_placeholder' => $zwy_wallet_address ?: '请输入信企贵交钱包地址'],
//         	['tag' => 'AJS', 'title' => '转出到澳交所', 'description' => $ajs_description, 'title_grb' => '转出丰谷宝份数', 'title_address' => '转账地址(第三方钱包地址)', 'title_address_placeholder' => '请输入转账地址'],
//        	['tag' => 'AGX', 'title' => '转出到AOGEX', 'description' => $ajs_description, 'title_grb' => '转出丰谷宝份数', 'title_address' => '转账地址<font color="red">(第三方钱包地址)</font>', 'title_address_placeholder' => '请输入转账地址'],
//         	['tag' => 'HT', 'title' => '转出至Quant Broker', 'description' => $ht_description, 'title_grb' => '转出丰谷宝份数', 'title_address' => '转账地址<font color="red">(Quant Broker的登录账号)</font>', 'title_address_placeholder' => '请输入Quant Broker的登录账号'],
//        	['tag' => 'SLU', 'title' => '转出到Silk Trader', 'description' => $slu_description, 'title_grb' => '转出丰谷宝份数', 'title_address' => '转账地址<font color="red">(第三方钱包地址)</font>', 'title_address_placeholder' => '请输入转账地址'],
        ];
        
        //针对特殊不受限制体系隐藏转出至SLU功能
//         $special_system = M('Settings')->where("settings_code='special_inside_transfer_system'")->getField('settings_value');
//         $special_system = preg_match('/,/', $special_system) ? explode(',', $special_system) : [$special_system];
//         $is_system = false;
//         $user_info = M('Member')->where('id='.$user_id)->field('repath')->find();
//         foreach ($special_system as $k=>$v) {
//         	$system_info = M('Member')->where('loginname='.$v)->field('id')->find();
//         	if ($system_info && $user_info) {
//         		if ( preg_match('/,'.$system_info['id'].',/', $user_info['repath']) || $system_info['id'] == $user_id ) {
//         			$is_system = true;
//         			break;
//         		}
//         	}
//         }
//         if ($is_system) {
//         	$data['wallet_platform'] = [];
//         }

        $this->myApiPrint('查询成功', 400, $data);
    }

    /**
     * 转账
     *
     * @method POST
     *
     * @param string $address BitCoin地址
     * @param double $amount 转账金额
	 * @param string $wallet_type 钱包类型(澳交所:AJS, 中网云:ZWY, AOGEX:AGX, 华佗商城:HT, SLU:SLU),默认ZWY
     */
    public function sendBtc()
    {
    	$AccountModel = new AccountModel();
    	
        $address = $this->post['address'];
        $amount = $this->post['amount'];
        $user_id = $this->app_common_data['uid'];
        $wallet_type = empty($this->post['wallet_type']) ? 'ZWY' : $this->post['wallet_type'];
        
        $wallet_platform = $wallet_type=='AGX' ? 'AJS' : $wallet_type;
        
        if ($wallet_type == 'HT') {
        	$this->transferToHT();
        	exit;
        }
           
        $WalletModel = new WalletModel($wallet_platform);
        
        if (!validateExtend($amount, 'MONEY')) {
        	$this->myApiPrint('请填写正确的金额');
        }
        if (empty($address)) {
        	$this->myApiPrint('请填写正确的钱包地址');
        }
    	if (empty($user_id)) {
            $this->myApiPrint('登录状态异常,请重新登录', 500);
        }
        
        $settings_pre = '';
        switch ($wallet_type) {
        	case 'ZWY':
        		$settings_pre = 'zhongwy';
        		break;
        	case 'AJS':
        		$settings_pre = 'ajs';
        		break;
        	case 'AGX':
        		$settings_pre = 'ajs';
        		break;
        	case 'SLU':
        		$settings_pre = 'slu';
        		break;
        }
        
        //获取开关配置
        $switch = M('Settings')->where("settings_code='{$settings_pre}_trade_switch'")->getField('settings_value');
        if ($switch != '开启') {
        	$this->myApiPrint('转账功能已关闭');
        }
        
        //验证最小兑换金额
        $trade_min = M('Settings')->where("settings_code='{$settings_pre}_trade_min'")->getField('settings_value');
        if ($amount < $trade_min) {
        	$this->myApiPrint('转账金额最小不能低于'.$trade_min);
        }
        
        //验证金额倍数
        $remainder = $amount % $trade_min;
        if ($remainder > 0) {
        	$this->myApiPrint('转账金额需为'.$trade_min.'的整数倍');
        }
        
        //核验用户每日累计申请金额不能超过限额
        $trade_max = M('Settings')->where("settings_code='{$settings_pre}_trade_max'")->getField('settings_value');
        $map_today = [
        	'status' => array('in', '0,3'),
        	'user_id' => array('eq', $user_id),
        	'addtime' => array(array('egt', strtotime(date('Y-m-d 00:00:00'))), array('elt', time()), 'and')
        ];
        $user_today_sum = M('Trade')->where($map_today)->sum('amount');
        $user_today_max = $trade_max - $user_today_sum;
        if ($user_today_max < $amount) {
        	$this->myApiPrint("今日转账剩余额度不足");
        }
        
        //从被转出账号扣除丰谷宝个数
        $balance = $AccountModel->getBalance($user_id, Currency::GoldCoin);
        if ($balance < $amount) {
            $this->myApiPrint('账户余额不足');
        }

        //计算扣除手续费后实际转出金额
        $fee = M('Settings')->where("settings_code='{$settings_pre}_trade_fee'")->getField('settings_value');
        $fee = $amount * $fee * 0.01;
        
        //判断澳洲SKN股数是否足够
        $enjoy_balance = $AccountModel->getBalance($user_id, Currency::Enjoy);
        if ($enjoy_balance < $this->CFG['enjoy_third']) {
        	$this->myApiPrint('澳洲SKN股数不足');
        }
        
        //验证钱包地址是否合法
        $check_address = $WalletModel->validateUserAddress($address);
        if ($check_address === false) {
        	$this->myApiPrint('请输入正确的钱包地址');
        }

        $third_amount = $amount;
        if ($wallet_type == 'ZWY') {
            $third_amount = $amount / floatval($this->CFG['zhongwy_trade_grb_rate']);
        }

        //提交至流通兑换审核表
        $data = [
        	'user_id' => $user_id,
            'amount' => $amount,
            'third_amount' => $third_amount,
        	'fee' => $fee,
        	'wallet_address' => $address,
        	'addtime' => time(),
            'type' => $wallet_type,
            'explain' => sprintf('消耗%.4f份流通通证，获得%.4f个GRB', $amount, $third_amount)
        ];
        $GrbTradeModel = new GrbTradeModel();
        $result = $GrbTradeModel->addTrade($data, $this->CFG);
        
        if (!$result) {
        	$this->myApiPrint('提交失败');
        } else {
        	$this->myApiPrint('转账申请已成功提交',400);
        }
    }
    
    /**
     * 定时备份钱包
     * 
     * @method GET
     * 
	 * @param string $wallet_type 钱包类型(澳交所:AJS, 中网云:ZWY),默认ZWY
     */
    public function backupWalletTask() {
    	$wallet_type = empty($this->get['wallet_type']) ? 'ZWY' : $this->get['wallet_type'];
    	
    	Vendor("Wallet.wallet_backup");
    	
 		$WalletModel = new WalletModel($wallet_type);
    	
    	$destination = WalletConfig::BACKUP_PATH.'/auto-'.date('Y-m-d-H-i-s').'.dat';
    	$WalletModel->backupUserWallet($destination);
    	
    	//删除前一周的备份文件
    	$backup_files = scandir(WalletConfig::BACKUP_PATH.'/');
    	if ($backup_files !== false) {
	    	foreach ($backup_files as $k=>$v) {
	    		if (preg_match('/^auto/', $v)) {
	    			preg_match_all('/^auto-(.*).dat/', $a, $matches);
					$date = $matches[1][0];
					
					if (!empty($date)) {
						$date = substr($date, 0, 10);
						$date = strtotime($date);
						
						if ((time() - $date) > 3600*24*7) {
							unlink(WalletConfig::BACKUP_PATH.'/'.$v);
						}
					}
	    		}
	    	}
    	}
    }
    
    /**
     * 流通兑换转账队列操作 [定时任务]
     */
    public function tradeActionQueue() {
    	$TradeModel = M('Trade');
        $wallet_type = empty($this->get['wallet_type']) ? 'ZWY' : $this->get['wallet_type'];
    	M()->startTrans();
    
    	//拉取在队列中的ID最小的一条转账记录数据
        $data = $TradeModel->where("is_queue=1 AND `type`='" . $wallet_type . "'")->order('id asc')->find();
    	if (!$data) {
    		exit;
    	}
    
    	//判断处理状态
    	if ($data['status'] != '4') {
    		$data_trade = [
    			'is_queue' => 0
    		];
    		$TradeModel->where('id='.$data['id'])->save($data_trade);
    		M()->commit();
    		exit;
    	}
    	
    	//处理类型
    	$wallet_platform = $data['type']=='AGX' ? 'AJS' : $data['type'];
    
    	//实例化模型
    	$WalletModel = new WalletModel($wallet_platform);
    
    	//最终转账金额
    	$transfer_amount = $data['third_amount'];
    
    	//核验待转账总金额和主钱包余额
    	$balance = $WalletModel->getBalance();
        // 格式化余额
        if (is_array($balance)) {
            // SLU余额必须 >= 1
            if ($data['type'] == 'SLU' && isset($balance['slu']) && $balance['slu'] >= 1) {
                $balance = $balance['grc'] ?: 0;
            } else {
                $balance = 0;
            }
        }

        // 主钱包余额不足，终止转账，队列挂起
    	if ($transfer_amount >= $balance) {
    	    var_dump($data);
    	    echo '主钱包余额不足，终止转账，队列挂起';
    		//自动退款
//    		$data['remark'] = '自动退款:账户余额不足';
//    		$this->tradeBackCore($data);
    		exit;
    	}
    
    	//转账操作
    	$result1 = $WalletModel->sendToUserAddress($data['wallet_address'], $transfer_amount);
    	if (!$result1) {
    		$data['remark'] = '钱包自动转账失败';
    		$result2 = $this->tradeBackCore($data, true);
    		if (!$result2) {
    			M()->rollback();
    		} else {
    			M()->commit();
    		}
    	} else {
    		$data_trade = [
	    		'txid' => $result1,
	    		'status' => 3,
	    		'uptime' => time(),
	    		'is_queue' => 0
    		];
    		$result3 = M('Trade')->where('id='.$data['id'])->save($data_trade);
    
    		if (!$result3) {
    			M()->rollback();
    		}
    
    		M()->commit();
    	}
    
    	exit;
    }
    
    /**
     * 退款封装
     *
     * @param array $data 兑换相关数据
     * @param boolean $return 是否返回true/false,默认false
     */
    private function tradeBackCore($data, $return=false) {
    	M()->startTrans();
    
    	//添加明细
    	$AccountRecordModel = new AccountRecordModel();
    	$record_attach = json_encode(['address'=>$data['wallet_address'], 'fee'=>$data['fee'], 'type'=>$data['type']], JSON_UNESCAPED_UNICODE);
    	$result1 = $AccountRecordModel->add($data['user_id'], Currency::GoldCoin, CurrencyAction::GoldCoinTradeRefund, $data['amount'], $record_attach);
    
    	//更改兑换状态
    	$data_trade = [
	    	'status' => 1,
	    	'remark' => $data['remark'],
	    	'is_queue' => 0
    	];
    	$result2 = M('Trade')->where('id='.$data['id'])->save($data_trade);
    
    	if (!$result1 || $result1==null || !$result2) {
    		M()->rollback();
    		if ($return) {
    			return false;
    		}
    	}
    
    	M()->commit();
    
    	if ($return) {
    		return true;
    	}
    }
    
    /**
     * 转出至华佗商城
     * 
     * @method POST
     *
     * @param string $address 转入的华佗商城会员编号登录名
     * @param double $amount 转账金额
     */
    public function transferToHT() {
    	$AccountModel = new AccountModel();
    	$AccountRecordModel = new AccountRecordModel();
    	 
    	$address = $this->post['address'];
    	$amount = $this->post['amount'];
    	$user_id = $this->app_common_data['uid'];
    	
    	if (!validateExtend($amount, 'MONEY')) {
    		$this->myApiPrint('请填写正确的金额');
    	}
    	if (empty($address)) {
    		$this->myApiPrint('请填写正确的钱包地址');
    	}
    	if (empty($user_id)) {
    		$this->myApiPrint('登录状态异常,请重新登录', 500);
    	}
    	
    	$settings_pre = 'ht';
    	
    	//获取开关配置
    	$switch = M('Settings')->where("settings_code='{$settings_pre}_trade_switch'")->getField('settings_value');
    	if ($switch != '开启') {
    		$this->myApiPrint('转账功能已关闭');
    	}
    	
    	//验证最小兑换金额
    	$trade_min = M('Settings')->where("settings_code='{$settings_pre}_trade_min'")->getField('settings_value');
    	if ($amount < $trade_min) {
    		$this->myApiPrint('转账金额最小不能低于'.$trade_min);
    	}
    	
    	//验证金额倍数
    	$remainder = $amount % $trade_min;
    	if ($remainder > 0) {
    		$this->myApiPrint('转账金额需为'.$trade_min.'的整数倍');
    	}
    	
    	//从被转出账号扣除丰谷宝个数
    	$balance = $AccountModel->getBalance($user_id, Currency::GoldCoin);
    	if ($balance < $amount) {
    		$this->myApiPrint('账户余额不足');
    	}
    	
    	//计算扣除手续费
    	$fee = M('Settings')->where("settings_code='{$settings_pre}_trade_fee'")->getField('settings_value');
    	$fee = $amount * $fee * 0.01;
    	
    	//当前用户信息
    	$user_info = M('Member')->where('id='.$user_id)->field('loginname,username')->find();
    	
    	M()->startTrans();
    	
    	//转出数据
    	$ht_param = [
    		'amount' => $amount-$fee,
    		'openid' => $address,
    		'fromid' => $user_info['loginname'],
    		'fromname' => $user_info['username'],
    	];
    	
    	//生成签名
    	$params = array_filter($ht_param);
    	ksort($params);
    	$str = "";
    	foreach ($params as $k => $v) {
    		if (!empty($v)) {
    			$str .= $k. "=". $v. "&";
    		}
    	}
    	$str .= "key=". C('HT_API')['SECRET_KEY'];
    	$ht_param['sign'] = strtoupper(MD5($str));
    	
    	//操作[1]:扣款 + 添加明细
    	$record_attach = json_encode(['address'=>$address, 'fee'=>$fee, 'type'=>'HT'], JSON_UNESCAPED_UNICODE);
    	$result1 = $AccountRecordModel->add($user_id, Currency::GoldCoin, CurrencyAction::GoldCoinTransferToHT, -$amount+$fee, $record_attach);
    	$result3 = $AccountRecordModel->add($user_id, Currency::GoldCoin, CurrencyAction::GoldCoinTransferToHTFee, -$fee, $record_attach);
    	
    	if (!$result1 || !$result3) {
    		M()->rollback();
    		$this->myApiPrint('转出失败:01');
    	}
    	
    	//操作[2]:转出至华佗商城
    	$ht_url = C('HT_API')['host']. C('HT_API')['recevied'];
    	$result2 = $this->curl($ht_url, 'post', $ht_param);
    	$result2 = json_decode($result2, true);
    	
    	if (!isset($result2['code'])) {
    		M()->rollback();
    		$this->myApiPrint('网络异常，请稍后重试');
    	}
    	
    	switch ($result2['code']) {
    		case '200':
    			M()->commit();
    			$this->myApiPrint('转出成功', 400);
    			break;
    		case '300':
    			M()->rollback();
    			$this->myApiPrint('转出失败:'.$result2['msg']);
    			break;
    		default:
    			$this->myApiPrint('返回数据异常');	
    	}
    }

}


?>