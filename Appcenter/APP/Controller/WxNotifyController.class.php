<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 微信支付回调接口【停用】
// +----------------------------------------------------------------------
namespace APP\Controller;
use Common\Controller\ApiController;
use V4\Model\OrderModel;
use V4\Model\PaymentMethod;
use V4\Model\MemberModel;
use V4\Model\CurrencyAction;
use V4\Model\Currency;
use V4\Model\AccountRecordModel;
use V4\Model\ProcedureModel;

class WxNotifyController extends ApiController {
	
	private $WxPay;
	private $AppId;
	private $MchId;
	
	public function __construct() {
		parent::__construct();
		
		Vendor('WxPay.WxPay#Api');
		
		$this->WxPay = new \WxPayResults();
		$this->AppId = \WxPayConfig::APPID;
		$this->MchId = \WxPayConfig::MCHID;
	}
	
	/**
	 * 创客申请回调函数
	 */
	public function hack_apply() {
		
		$log_folder = 'wxnotify';
		
		//写入开始日志
		$this->recordLogWrite($log_folder, '[START:hack_apply]'.PHP_EOL);
		
		$params = A('Pay')->wxSign();  // 异步验签，通过才能进行下一个步骤
	
		if ($params && $this->AppId == $params['appid'] && $this->MchId == $params['mch_id']) {
			//查询订单
   			$orders = M('orders')->where('order_number=\'' . $params['out_trade_no'] . '\'')->find();
			$orders_pay_info = M('orders_pay_info');
			
			//查询流水记录
			$payinfo = $orders_pay_info->where(array('order_number' => $params['out_trade_no']))->find();

			M()->startTrans();
			if ($params['result_code']=='SUCCESS' && $orders['id'] > 0 && $payinfo['id'] > 0) {
				
				// 业务操作，更改为创客
				$member = M('member');
				$res1 = $member->where(array('id' => $orders['uid'])) ->save(array('level' => 2, 'open_time'=>time()));

				$user = M('member')->where('id='.$orders['uid'])->find();
				$res2 = M('member')->where('id='.$user['reid'])->setInc('recount',1);
					
				// 更新明细
				$arm = new AccountRecordModel();
				$res3 = $arm->add($orders['uid'], Currency::Cash, CurrencyAction::WechatPayRecharge, $orders['amount'], $arm->getRecordAttach($user['id'], $user['nickname'], $user['img']), '微信支付在线充值');
				$res4 = $arm->add($orders['uid'], Currency::Cash, CurrencyAction::WechatApplyMaker, -$orders['amount'], $arm->getRecordAttach(1, '管理员'), '开通创客');
				
				if($res1 === false || $res2 === false || $res3 === false || $res4 === false){
					M()->rollback();
					$this->recordLogWrite($log_folder, "notify_result:FAIL:03".PHP_EOL."[END]".PHP_EOL);
					$this->WxPay->SetData('return_code', 'FAIL');
					echo $this->WxPay->ToXml();
					exit;
				}
				
				// 更新付款状态
				$data = array();
			    $data['trade_no'] = $params['transaction_id'];
                $data['trade_status'] = $params['result_code'];
                $data['total_amount'] = $orders['amount']; 
                $data['receipt_amount'] = $params['cash_fee'];
                $data['gmt_payment'] = $params['time_end'];
                $res5 = $orders_pay_info->where(array('order_number' => $params['out_trade_no']))->save($data);
                //订单主表状态
                $om = new OrderModel();
                $res6 = $om->updateOrder($params['out_trade_no']);
                if($res5 === false || $res6 === false){
                    M()->rollback();
                    $this->recordLogWrite($log_folder, "notify_result:FAIL:02".PHP_EOL."[END]".PHP_EOL);
                    $this->WxPay->SetData('return_code', 'FAIL');
                    echo $this->WxPay->ToXml();
                    exit;
                }
                M()->commit();
                $this->recordLogWrite($log_folder, "notify_result:SUCCESS:01".PHP_EOL."[END]".PHP_EOL);
                $this->WxPay->SetData('return_code', 'SUCCESS');
                echo $this->WxPay->ToXml();
                exit;
			}
			M()->rollback();
            $this->recordLogWrite($log_folder, "notify_result:FAIL:04".PHP_EOL."[END]".PHP_EOL);
            $this->WxPay->SetData('return_code', 'FAIL');
            echo $this->WxPay->ToXml();
            exit;
				
		} else {
			$this->recordLogWrite($log_folder, "notify_result:FAIL:01".PHP_EOL."[END]".PHP_EOL);
			$this->WxPay->SetData('return_code', 'FAIL');
			echo $this->WxPay->ToXml();
		}
	}
	
	/**
	 * 充值回调函数
	 */
    public function recharge() {
        $log_folder = 'wxnotify';
        //写入开始日志
        $this->recordLogWrite($log_folder, '[START:recharge]'.PHP_EOL);
        
        $params = A('Pay')->wxSign();  // 异步验签，通过才能进行下一个步骤
    
        if ($params && $this->AppId == $params['appid'] && $this->MchId == $params['mch_id'] && $params['result_code']=='SUCCESS') {
            $order = M('orders')->where('order_number=\'' . $params['out_trade_no'] . '\'')->find();
            $pay_info = M('orders_pay_info')->where(array('order_number' => $params['out_trade_no']))->find();

            if (!empty($pay_info['trade_status'])) {
                $this->recordLogWrite($log_folder, "notify_result:SUCCESS:01".PHP_EOL."[END]".PHP_EOL);
                $this->WxPay->SetData('return_code', 'SUCCESS');
                echo $this->WxPay->ToXml();
                exit();
            }
            
            M()->startTrans();
            //更新订单
            $om = new OrderModel();
            $res1 = $om->updateOrder($params['out_trade_no'], 4);
            $res2 = $om->updateOrderInfo($params, $order['amount']);
            
            $user = M('member')->where('id='.$order['uid'])->find();
            
            //充值账户
            $arm = new AccountRecordModel();
            $res3 = $arm->add($order['uid'], Currency::Cash, CurrencyAction::CashRecharge, $order['amount'], $arm->getRecordAttach($user['id'], $user['nickname'], $user['img']), '微信在线充值');
            
            if($res1 !== false && $res2 !== false && $res3 !== false){
                M()->commit();
                $this->recordLogWrite($log_folder, "notify_result:SUCCESS:02".PHP_EOL."[END]".PHP_EOL);
                $this->WxPay->SetData('return_code', 'SUCCESS');
                echo $this->WxPay->ToXml();
                exit();
            }else{
                M()->rollback();
                $this->recordLogWrite($log_folder, "notify_result:FAIL:01".PHP_EOL."[END]".PHP_EOL);
                $this->WxPay->SetData('return_code', 'FAIL');
                echo $this->WxPay->ToXml();
                exit();
            }
            
        } else {
            $this->recordLogWrite($log_folder, "notify_result:FAIL:03".PHP_EOL."[END]".PHP_EOL);
            $this->WxPay->SetData('return_code', 'FAIL');
            echo $this->WxPay->ToXml();
            exit;
        }
    }
	
	/**
	* 消费回调函数
	*/
    public function consume() {
        
        //写入开始日志
        $log_folder = 'wxnotify';
        $this->recordLogWrite($log_folder, '[START:consume]'.PHP_EOL);
        
        // 异步验签，通过才能进行下一个步骤
        $params = A('Pay')->wxSign();
        if ($params && $this->AppId == $params['appid'] && $this->MchId == $params['mch_id']) {
            //写入执行日志
            $this->recordLogWrite($log_folder, "order_number:".$params['out_trade_no'].PHP_EOL."[END]".PHP_EOL);
            
            $post = $this->validateCousume($params);
            $om = new OrderModel();
            //微信业务处理成功后
            if ($params['result_code']=='SUCCESS'){
            	//获取订单
            	$order = M('orders')->where('order_number = \''.$params['out_trade_no'].'\'')->find();
                M()->startTrans();
                //更新明细
                $res = $om->consumeByWechatAndAlipay($post, $params);
                
                if($res){
                    M()->commit();
                    
                    $this->recordLogWrite($log_folder, "notify_result:SUCCESS:02".PHP_EOL."[END]".PHP_EOL);
                    $this->WxPay->SetData('return_code', 'SUCCESS');
                    echo $this->WxPay->ToXml();
                    exit();
                }else{
                    M()->rollback();
                    $this->recordLogWrite($log_folder, "notify_result:FAIL:01".PHP_EOL."[END]".PHP_EOL);
                    $this->WxPay->SetData('return_code', 'FAIL');
                    echo $this->WxPay->ToXml();
                    exit();
                }
            }else{
                $this->recordLogWrite($log_folder, "notify_result:FAIL:04".PHP_EOL."[END]".PHP_EOL);
                $this->WxPay->SetData('return_code', 'FAIL');
                echo $this->WxPay->ToXml();
                exit;
            }
        } else {
            $this->recordLogWrite($log_folder, "notify_result:FAIL:03".PHP_EOL."[END]".PHP_EOL);
            $this->WxPay->SetData('return_code', 'FAIL');
            echo $this->WxPay->ToXml();
            exit;
        }
        
    }
    
    
    
    public function validateCousume($params){
        //1.查询订单相关信息
        $orders = M('orders')->where('order_number=\'' . $params['out_trade_no'] . '\'')->find();
        $pay_info = M('orders_pay_info')->where(array('order_number' => $params['out_trade_no']))->find();
        if ($pay_info['trade_status'] != '') {
            $this->recordLogWrite($log_folder, "notify_result:SUCCESS:01".PHP_EOL."[END]".PHP_EOL);
            $this->WxPay->SetData('return_code', 'SUCCESS');
            echo $this->WxPay->ToXml();
            exit();
        }
        //2.查询订单店铺相关
        $store = M('store')->where(array('id' => $orders['storeid'], 'status'=>0, 'manage_status'=>1))->find();
        $pw_result = M('preferential_way')->where(array('store_id' => $store['id'], 'status'=>0, 'manage_status'=>1))->find();
        if(!$store || !$pw_result){
            $this->recordLogWrite($log_folder, "notify_result:FAIL:01".PHP_EOL."[END]".PHP_EOL);
            $this->WxPay->SetData('return_code', 'FAIL');
            echo $this->WxPay->ToXml();
            exit();
        }
        
        $user = M('member')->where(array('id' => $orders['uid']))->find();
        $seller = M('member')->where('id = '. $store['uid'])->find(); 
        if(!$user || !$seller){
            $this->recordLogWrite($log_folder, "notify_result:FAIL:02".PHP_EOL."[END]".PHP_EOL);
            $this->WxPay->SetData('return_code', 'FAIL');
            echo $this->WxPay->ToXml();
            exit();
        }
        
        return array('buyer'=>$user, 'seller'=>$seller, 'pw'=>$pw_result, 'store'=>$store, 'order'=>$orders);
    }
    
    
    /**
     * 责任消费回调函数
     */
    public function dutyconsume() {
    
    	//写入开始日志
    	$log_folder = 'wxnotify';
    	$this->recordLogWrite($log_folder, '[START:consume]'.PHP_EOL);
    
    	// 异步验签，通过才能进行下一个步骤
    	$params = A('Pay')->wxSign();
    	if ($params && $this->AppId == $params['appid'] && $this->MchId == $params['mch_id']) {
    		//写入执行日志
    		$this->recordLogWrite($log_folder, "order_number:".$params['out_trade_no'].PHP_EOL."[END]".PHP_EOL);
    		M()->startTrans();
    		//查询订单
    		$orders = M('orders')->lock(true)->where('order_number=\'' . $params['out_trade_no'] . '\'')->find();
    		if($orders['order_status'] == 4){
    			M()->rollback();
    			$this->recordLogWrite($log_folder, "notify_result:SUCCESS:01".PHP_EOL."[END]".PHP_EOL);
    			$this->WxPay->SetData('return_code', 'SUCCESS');
    			echo $this->WxPay->ToXml();
    			exit;
    		}
    		
    		$orders_pay_info = M('orders_pay_info');
    		//查询流水记录
    		$payinfo = $orders_pay_info->lock(true)->where(array('order_number' => $params['out_trade_no']))->find();
    		if ($payinfo['trade_status'] != '') {
    			$this->recordLogWrite($log_folder, "notify_result:SUCCESS:01".PHP_EOL."[END]".PHP_EOL);
    			$this->WxPay->SetData('return_code', 'SUCCESS');
    			echo $this->WxPay->ToXml();
    			exit();
    		}
    		$parameter = M('g_parameter', null)->find();
    		
    		//微信业务处理成功后
    		if ($params['result_code']=='SUCCESS' && $orders['id'] > 0 && $payinfo['id'] > 0) {
    			//订单主表状态
    			$om = new OrderModel();
    			$res1 = $om->updateOrder($params['out_trade_no']);
    			 
    			// 更新付款状态
    			$data = array();
    			$data['trade_no'] = $params['transaction_id'];
    			$data['trade_status'] = $params['result_code'];
    			$data['total_amount'] = $orders['amount'];
    			$data['receipt_amount'] = $params['cash_fee'];
    			$data['gmt_payment'] = $params['time_end'];
    			$res2 = $orders_pay_info->where(array('order_number' => $params['out_trade_no']))->save($data);
    			
    			//消费记录
    			$arm = new AccountRecordModel();
    			$res3 = $arm->add($orders['uid'], Currency::Cash, CurrencyAction::WechatPayRecharge, $orders['amount'], $arm->getRecordAttach($user['id'], $user['nickname'], $user['img']), '微信支付在线充值');
    			$res4 = $arm->add($orders['uid'], Currency::Cash, CurrencyAction::WechatDutyConsume, -$orders['amount'], $arm->getRecordAttach(1, '管理员'), '微信责任消费');
    			
    			//获得积分
    			$credits = $orders['amount']*$parameter['duty_consume_exchange_bei'];
    			$res5 = $arm->add($orders['uid'], Currency::Credits, CurrencyAction::CreditsDutyConsumeExchane, $credits, $arm->getRecordAttach(1, '系统'), '责任消费兑换积分');
    			 
    			//责任消费金额累加
    			M('dutyconsume')->where('user_id = '.$orders['uid'])->save(array('dutyconsume_complete_amount'=>array('exp','dutyconsume_complete_amount+'.$orders['amount']), 'dutyconsume_income_enable'=>1));
    			 
    			M()->execute(C('ALIYUN_TDDL_MASTER') . "call Bonus_dutyconsume(".$orders['uid'].", ".$orders['amount'].")");
    			 
    			if($res1 && $res2 && $res3 && $res4 && $res5){
    				M()->commit();
    				$this->recordLogWrite($log_folder, "notify_result:SUCCESS:02".PHP_EOL."[END]".PHP_EOL);
    				$this->WxPay->SetData('return_code', 'SUCCESS');
    				echo $this->WxPay->ToXml();
    				exit();
    			}else{
    				M()->rollback();
    				$this->recordLogWrite($log_folder, "notify_result:FAIL:01".PHP_EOL."[END]".PHP_EOL);
    				$this->WxPay->SetData('return_code', 'FAIL');
    				echo $this->WxPay->ToXml();
    				exit();
    			}
    		}else{
    			M()->rollback();
    			$this->recordLogWrite($log_folder, "notify_result:FAIL:04".PHP_EOL."[END]".PHP_EOL);
    			$this->WxPay->SetData('return_code', 'FAIL');
    			echo $this->WxPay->ToXml();
    			exit;
    		}
    	} else {
    		$this->recordLogWrite($log_folder, "notify_result:FAIL:03".PHP_EOL."[END]".PHP_EOL);
    		$this->WxPay->SetData('return_code', 'FAIL');
    		echo $this->WxPay->ToXml();
    		exit;
    	}
    
    }
    
	
	
    /**
     * 金卡代理申请回调函数
     */
    public function vip_apply() {
    
    	$log_folder = 'wxnotify';
    
    	//写入开始日志
    	$this->recordLogWrite($log_folder, '[START:vip_apply]'.PHP_EOL);
    
    	$params = A('Pay')->wxSign();  // 异步验签，通过才能进行下一个步骤
    
    	if ($params && $this->AppId == $params['appid'] && $this->MchId == $params['mch_id']) {
    		M()->startTrans();
    		
    		//查询订单
    		$orders = M('orders')->lock(true)->where('order_number=\'' . $params['out_trade_no'] . '\'')->find();
    		$user = M('member')->find($orders['uid']);
    		if($orders['order_status'] == 4){
    			M()->rollback();
    			$this->recordLogWrite($log_folder, "notify_result:SUCCESS:01".PHP_EOL."[END]".PHP_EOL);
    			$this->WxPay->SetData('return_code', 'SUCCESS');
    			echo $this->WxPay->ToXml();
    			exit;
    		}
    		$orders_pay_info = M('orders_pay_info');
    			
    		//查询流水记录
    		$payinfo = $orders_pay_info->lock(true)->where(array('order_number' => $params['out_trade_no']))->find();
    		if ($payinfo['trade_status'] != '') {
    			$this->recordLogWrite($log_folder, "notify_result:SUCCESS:01".PHP_EOL."[END]".PHP_EOL);
    			$this->WxPay->SetData('return_code', 'SUCCESS');
    			echo $this->WxPay->ToXml();
    			exit();
    		}
    		if ($params['result_code']=='SUCCESS' && $orders['id'] > 0 && $payinfo['id'] > 0) {
    			$parameter = M('g_parameter', null)->find();
    			
    			//订单主表状态
    			$om = new OrderModel();
    			$res1 = $om->updateOrder($params['out_trade_no']);
    			
    			// 更新付款状态
    			$data = array();
    			$data['trade_no'] = $params['transaction_id'];
    			$data['trade_status'] = $params['result_code'];
    			$data['total_amount'] = $orders['amount'];
    			$data['receipt_amount'] = $params['cash_fee'];
    			$data['gmt_payment'] = $params['time_end'];
    			$res2 = $orders_pay_info->where(array('order_number' => $params['out_trade_no']))->save($data);
    			
    			//vip审核状态,首次申请直接通过
    			$res6 = M('vip_apply')->where('user_id = '.$orders['uid'])->save(array('apply_status'=>3));
    			
    			// 更新明细
    			$arm = new AccountRecordModel();
    			$res3 = $arm->add($orders['uid'], Currency::Cash, CurrencyAction::WechatPayRecharge, $orders['amount'], $arm->getRecordAttach($user['id'], $user['nickname'], $user['img']), '微信支付在线充值');
    			$res4 = $arm->add($orders['uid'], Currency::Cash, CurrencyAction::WechatApplyVIP, -$orders['amount'], $arm->getRecordAttach(1, '管理员'), '开通vip');
    			
    			//注册币
    			$res5 = true;
    			if($orders['order_enroll_amount'] > 0){
    				$res5 = $arm->add($orders['uid'], Currency::Enroll, CurrencyAction::ENrollApplyVIP, -$orders['order_enroll_amount'], $arm->getRecordAttach(1, '系统', '', $params['out_trade_no']), '申请金卡代理扣款');
    			}
    			
    			//升级
    			$res7 = M('member')->where('id = '.$orders['uid'])->save(array('roleid'=>array('exp','`level`'), 'level'=>6, 'open_time'=>time()));
    			
    			//定时结算
    			$mm = new MemberModel();
    			$res8 = $mm->vipclear($orders['uid']);
    			 
    			//吊起存储过程
    			$pm = new ProcedureModel();
    			$res9 = $pm->execute('V51_Event_apply', $orders['uid'], '@error');
    			
    			
    			
    			if($res1 !== false && $res2 !== false && $res3 !== false && $res4 !== false && $res5 !==false && $res6 !== false && $res7 !== false && $res8 && $res9){
    				M()->commit();
    				$this->recordLogWrite($log_folder, "notify_result:SUCCESS:01".PHP_EOL."[END]".PHP_EOL);
    				$this->WxPay->SetData('return_code', 'SUCCESS');
    				echo $this->WxPay->ToXml();
    				exit;
    			}else{
    				M()->rollback();
    				$this->recordLogWrite($log_folder, "notify_result:FAIL:02".PHP_EOL."[END]".PHP_EOL);
    				$this->WxPay->SetData('return_code', 'FAIL');
    				echo $this->WxPay->ToXml();
    				exit;
    			}
    			
    		}
    		M()->rollback();
    		$this->recordLogWrite($log_folder, "notify_result:FAIL:04".PHP_EOL."[END]".PHP_EOL);
    		$this->WxPay->SetData('return_code', 'FAIL');
    		echo $this->WxPay->ToXml();
    		exit;
    
    	} else {
    		$this->recordLogWrite($log_folder, "notify_result:FAIL:01".PHP_EOL."[END]".PHP_EOL);
    		$this->WxPay->SetData('return_code', 'FAIL');
    		echo $this->WxPay->ToXml();
    		exit;
    	}
    }
    
    
    /**
     * 银卡代理申请回调函数
     */
    public function v_apply() {
    
    	$log_folder = 'wxnotify';
    
    	//写入开始日志
    	$this->recordLogWrite($log_folder, '[START:vip_apply]'.PHP_EOL);
    
    	$params = A('Pay')->wxSign();  // 异步验签，通过才能进行下一个步骤
    	if ($params && $this->AppId == $params['appid'] && $this->MchId == $params['mch_id']) {
    		M()->startTrans();
    		
    		//查询订单
    		$orders = M('orders')->lock(true)->where('order_number=\'' . $params['out_trade_no'] . '\'')->find();
    		$user = M('member')->find($orders['uid']);
    		if($orders['order_status'] == 4){
    			M()->rollback();
    			$this->recordLogWrite($log_folder, "notify_result:SUCCESS:01".PHP_EOL."[END]".PHP_EOL);
    			$this->WxPay->SetData('return_code', 'SUCCESS');
    			echo $this->WxPay->ToXml();
    			exit;
    		}
    		$orders_pay_info = M('orders_pay_info');
    			
    		//查询流水记录
    		$payinfo = $orders_pay_info->lock(true)->where(array('order_number' => $params['out_trade_no']))->find();
    		if ($payinfo['trade_status'] != '') {
    			$this->recordLogWrite($log_folder, "notify_result:SUCCESS:01".PHP_EOL."[END]".PHP_EOL);
    			$this->WxPay->SetData('return_code', 'SUCCESS');
    			echo $this->WxPay->ToXml();
    			exit();
    		}
    		if ($params['result_code']=='SUCCESS' && $orders['id'] > 0 && $payinfo['id'] > 0) {
    			$parameter = M('g_parameter', null)->find();
    			
    			//订单主表状态
    			$om = new OrderModel();
    			$res1 = $om->updateOrder($params['out_trade_no']);
    			
    			// 更新付款状态
    			$data = array();
    			$data['trade_no'] = $params['transaction_id'];
    			$data['trade_status'] = $params['result_code'];
    			$data['total_amount'] = $orders['amount'];
    			$data['receipt_amount'] = $params['cash_fee'];
    			$data['gmt_payment'] = $params['time_end'];
    			$res2 = $orders_pay_info->where(array('order_number' => $params['out_trade_no']))->save($data);
    			
    			//银卡代理审核状态,首次申请直接通过
    			$res6 = M('micro_vip_apply')->where('user_id = '.$orders['uid'])->save(array('apply_status'=>3));
    			
    			// 更新明细
    			$arm = new AccountRecordModel();
    			$res3 = $arm->add($orders['uid'], Currency::Cash, CurrencyAction::WechatPayRecharge, $orders['amount'], $arm->getRecordAttach($user['id'], $user['nickname'], $user['img']), '微信支付在线充值');
    			$res4 = $arm->add($orders['uid'], Currency::Cash, CurrencyAction::WechatApplyMicroVIP, -$orders['amount'], $arm->getRecordAttach(1, '管理员'), '开通银卡代理');
    			
    			//注册币
    			$res5 = true;
    			if($orders['order_enroll_amount'] > 0){
    				$res5 = $arm->add($orders['uid'], Currency::Enroll, CurrencyAction::ENrollApplyMicroVIP, -$orders['order_enroll_amount'], $arm->getRecordAttach(1, '系统', '', $params['out_trade_no']), '申请银卡代理扣款');
    			}
    			
    			//升级
    			$res7 = M('member')->where('id = '.$orders['uid'])->save(array('roleid'=>array('exp','`level`'), 'level'=>5, 'open_time'=>time()));
    			
    			//定时结算
    			$mm = new MemberModel();
    			$res8 = $mm->v_vipclear($orders['uid']);
    			
    			//吊起存储过程
    			$pm = new ProcedureModel();
    			$res9 = $pm->execute('V51_Event_apply', $orders['uid'], '@error');
    			
    			if($res1 !== false && $res2 !== false && $res3 !== false && $res4 !== false && $res5 && $res6 !== false && $res7 !== false && $res8 && $res9){
    				M()->commit();
    				$this->recordLogWrite($log_folder, "notify_result:SUCCESS:01".PHP_EOL."[END]".PHP_EOL);
    				$this->WxPay->SetData('return_code', 'SUCCESS');
    				echo $this->WxPay->ToXml();
    				exit;
    			}else{
    				M()->rollback();
    				$this->recordLogWrite($log_folder, "notify_result:FAIL:02".PHP_EOL."[END]".PHP_EOL);
    				$this->WxPay->SetData('return_code', 'FAIL');
    				echo $this->WxPay->ToXml();
    				exit;
    			}
    			
    		}
    		M()->rollback();
    		$this->recordLogWrite($log_folder, "notify_result:FAIL:04".PHP_EOL."[END]".PHP_EOL);
    		$this->WxPay->SetData('return_code', 'FAIL');
    		echo $this->WxPay->ToXml();
    		exit;
    
    	} else {
    		$this->recordLogWrite($log_folder, "notify_result:FAIL:01".PHP_EOL."[END]".PHP_EOL);
    		$this->WxPay->SetData('return_code', 'FAIL');
    		echo $this->WxPay->ToXml();
    		exit;
    	}
    }
    
    
    /**
     * 钻卡代理申请回调函数
     */
    public function honoureVip_apply() {
    
    	$log_folder = 'wxnotify';
    
    	//写入开始日志
    	$this->recordLogWrite($log_folder, '[START:vip_apply]'.PHP_EOL);
    
    	$params = A('Pay')->wxSign();  // 异步验签，通过才能进行下一个步骤
    
    	if ($params && $this->AppId == $params['appid'] && $this->MchId == $params['mch_id']) {
    		M()->startTrans();
    		
    		//查询订单
    		$orders = M('orders')->lock(true)->where('order_number=\'' . $params['out_trade_no'] . '\'')->find();
    		$user = M('member')->find($orders['uid']);
    		if($orders['order_status'] == 4){
    			M()->rollback();
    			$this->recordLogWrite($log_folder, "notify_result:SUCCESS:01".PHP_EOL."[END]".PHP_EOL);
    			$this->WxPay->SetData('return_code', 'SUCCESS');
    			echo $this->WxPay->ToXml();
    			exit;
    		}
    		$orders_pay_info = M('orders_pay_info');
    			
    		//查询流水记录
    		$payinfo = $orders_pay_info->lock(true)->where(array('order_number' => $params['out_trade_no']))->find();
    		if ($payinfo['trade_status'] != '') {
    			$this->recordLogWrite($log_folder, "notify_result:SUCCESS:01".PHP_EOL."[END]".PHP_EOL);
    			$this->WxPay->SetData('return_code', 'SUCCESS');
    			echo $this->WxPay->ToXml();
    			exit();
    		}
    		
    		if ($params['result_code']=='SUCCESS' && $orders['id'] > 0 && $payinfo['id'] > 0) {
    			$parameter = M('g_parameter', null)->find();
    			//订单主表状态
    			$mm = new MemberModel();
    			$om = new OrderModel();
    			$res1 = $om->updateOrder($params['out_trade_no']);
    			
    			// 更新付款状态
    			$data = array();
    			$data['trade_no'] = $params['transaction_id'];
    			$data['trade_status'] = $params['result_code'];
    			$data['total_amount'] = $orders['amount'];
    			$data['receipt_amount'] = $params['cash_fee'];
    			$data['gmt_payment'] = $params['time_end'];
    			$res2 = $orders_pay_info->where(array('order_number' => $params['out_trade_no']))->save($data);
    			
    			//vip审核状态,首次申请直接通过
    			$res6 = M('honour_vip_apply')->where('user_id = '.$orders['uid'])->save(array('apply_status'=>3));
    			
    			// 更新明细
    			$arm = new AccountRecordModel();
    			$res3 = $arm->add($orders['uid'], Currency::Cash, CurrencyAction::WechatPayRecharge, $orders['amount'], $arm->getRecordAttach($user['id'], $user['nickname'], $user['img']), '微信支付在线充值');
    			$res4 = $arm->add($orders['uid'], Currency::Cash, CurrencyAction::WechatApplyHonourVIP, -$orders['amount'], $arm->getRecordAttach(1, '管理员'), '开通钻卡代理');
    			
    			//注册币
    			$res5 = true;
    			if($orders['order_enroll_amount'] > 0){
    				$res5 = $arm->add($orders['uid'], Currency::Enroll, CurrencyAction::ENrollApplyHonourVIP, -$orders['order_enroll_amount'], $arm->getRecordAttach(1, '系统', '', $params['out_trade_no']), '申请金卡代理扣款');
    			}
    			
    			$plantype = 0;
    			//交完钱-升级
    			$res7 = true;
    			$res99 = true;
    			if($parameter['honour_vip_apply_amount'] - $parameter['honour_vip_apply_first_amount'] == 0){
    				$res7 = M('member')->where('id = '.$orders['uid'])->save(array('roleid'=>array('exp','`level`'), 'level'=>7, 'open_time'=>time()));
    				//回本
    				$mm->honoureVipclear($orders['uid']);
    				//吊起存储过程
    				$pm = new ProcedureModel();
    				$res99 = $pm->execute('V51_Event_apply', $orders['uid'], '@error');
    			}

    			if($res1 !== false && $res2 !== false && $res3 !== false && $res4 !== false && $res5 && $res6 !== false && $res7 !== false && $res99){
    				M()->commit();
    				$this->recordLogWrite($log_folder, "notify_result:SUCCESS:01".PHP_EOL."[END]".PHP_EOL);
    				$this->WxPay->SetData('return_code', 'SUCCESS');
    				echo $this->WxPay->ToXml();
    				exit;
    			}else{
    				M()->rollback();
    				$this->recordLogWrite($log_folder, "notify_result:FAIL:02".PHP_EOL."[END]".PHP_EOL);
    				$this->WxPay->SetData('return_code', 'FAIL');
    				echo $this->WxPay->ToXml();
    				exit;
    			}
    			
    		}
    		M()->rollback();
    		$this->recordLogWrite($log_folder, "notify_result:FAIL:04".PHP_EOL."[END]".PHP_EOL);
    		$this->WxPay->SetData('return_code', 'FAIL');
    		echo $this->WxPay->ToXml();
    		exit;
    
    	} else {
    		$this->recordLogWrite($log_folder, "notify_result:FAIL:01".PHP_EOL."[END]".PHP_EOL);
    		$this->WxPay->SetData('return_code', 'FAIL');
    		echo $this->WxPay->ToXml();
    		exit;
    	}
    }
    
    
    
    
    /**
     * 续费钻卡代理申请回调函数
     */
    public function honoureVip_renew() {
    
    	$log_folder = 'wxnotify';
    
    	//写入开始日志
    	$this->recordLogWrite($log_folder, '[START:vip_apply]'.PHP_EOL);
    
    	$params = A('Pay')->wxSign();  // 异步验签，通过才能进行下一个步骤
    
    	if ($params && $this->AppId == $params['appid'] && $this->MchId == $params['mch_id']) {
    		M()->startTrans();
    
    		//查询订单
    		$orders = M('orders')->lock(true)->where('order_number=\'' . $params['out_trade_no'] . '\'')->find();
    		$user = M('member')->find($orders['uid']);
    		if($orders['order_status'] == 4){
    			M()->rollback();
    			$this->recordLogWrite($log_folder, "notify_result:SUCCESS:01".PHP_EOL."[END]".PHP_EOL);
    			$this->WxPay->SetData('return_code', 'SUCCESS');
    			echo $this->WxPay->ToXml();
    			exit;
    		}
    		$orders_pay_info = M('orders_pay_info');
    		 
    		//查询流水记录
    		$payinfo = $orders_pay_info->lock(true)->where(array('order_number' => $params['out_trade_no']))->find();
    		if ($payinfo['trade_status'] != '') {
    			$this->recordLogWrite($log_folder, "notify_result:SUCCESS:01".PHP_EOL."[END]".PHP_EOL);
    			$this->WxPay->SetData('return_code', 'SUCCESS');
    			echo $this->WxPay->ToXml();
    			exit();
    		}
    
    		if ($params['result_code']=='SUCCESS' && $orders['id'] > 0 && $payinfo['id'] > 0) {
    			$parameter = M('g_parameter', null)->find();
    			//订单主表状态
    			$om = new OrderModel();
    			$res1 = $om->updateOrder($params['out_trade_no']);
    			 
    			// 更新付款状态
    			$data = array();
    			$data['trade_no'] = $params['transaction_id'];
    			$data['trade_status'] = $params['result_code'];
    			$data['total_amount'] = $orders['amount'];
    			$data['receipt_amount'] = $params['cash_fee'];
    			$data['gmt_payment'] = $params['time_end'];
    			$res2 = $orders_pay_info->where(array('order_number' => $params['out_trade_no']))->save($data);
    			
    			//处理业务
    			$firstpay = M('user_affiliate')->where('user_id = '.$orders['uid'])->find();
    			if(empty($firstpay)){
    				M()->rollback();
    				$this->recordLogWrite($log_folder, "notify_result:FAIL:02".PHP_EOL."[END]".PHP_EOL);
    				$this->WxPay->SetData('return_code', 'FAIL');
    				echo $this->WxPay->ToXml();
    				exit;
    			}
    			$arm = new AccountRecordModel();
    			$res3 = $arm->add($orders['uid'], Currency::Cash, CurrencyAction::WechatPayRecharge, $firstpay['honour_vip_unpaid_amount'], $arm->getRecordAttach($user['id'], $user['nickname'], $user['img']), '微信支付在线充值');
    			
    			$mm = new MemberModel();
    			$res1 = $mm->honourVipRenew($orders['uid'], $firstpay);
    			$res2 = $mm->honoureVipclear($orders['uid']);
    			//吊起存储过程
    			$pm = new ProcedureModel();
    			$res5 = $pm->execute('V51_Event_apply', $orders['uid'], '@error');
    			if($res1 && $res2 && $res3 && $res5){
    				M()->commit();
    				$this->recordLogWrite($log_folder, "notify_result:SUCCESS:01".PHP_EOL."[END]".PHP_EOL);
    				$this->WxPay->SetData('return_code', 'SUCCESS');
    				echo $this->WxPay->ToXml();
    				exit;
    			}else{
    				M()->rollback();
    				$this->recordLogWrite($log_folder, "notify_result:FAIL:02".PHP_EOL."[END]".PHP_EOL);
    				$this->WxPay->SetData('return_code', 'FAIL');
    				echo $this->WxPay->ToXml();
    				exit;
    			}
    			 
    		}
    		M()->rollback();
    		$this->recordLogWrite($log_folder, "notify_result:FAIL:04".PHP_EOL."[END]".PHP_EOL);
    		$this->WxPay->SetData('return_code', 'FAIL');
    		echo $this->WxPay->ToXml();
    		exit;
    
    	} else {
    		$this->recordLogWrite($log_folder, "notify_result:FAIL:01".PHP_EOL."[END]".PHP_EOL);
    		$this->WxPay->SetData('return_code', 'FAIL');
    		echo $this->WxPay->ToXml();
    		exit;
    	}
    }
    
    
    
    
    /**
     * 商城微信支付
     */
    public function shopping() {
    
    	$log_folder = 'wxnotify';
    
    	//写入开始日志
    	$this->recordLogWrite($log_folder, '[START:vip_apply]'.PHP_EOL);
    
    	$params = A('Pay')->wxSign();  // 异步验签，通过才能进行下一个步骤
    
    	if ($params && $this->AppId == $params['appid'] && $this->MchId == $params['mch_id']) {
    		M()->startTrans();
    
    		//查询订单
    		$orders = M('orders')->lock(true)->where('order_number=\'' . $params['out_trade_no'] . '\'')->find();
    		$user = M('member')->find($orders['uid']);
    		if($orders['order_status'] == 4){
    			M()->rollback();
    			$this->recordLogWrite($log_folder, "notify_result:SUCCESS:01".PHP_EOL."[END]".PHP_EOL);
    			$this->WxPay->SetData('return_code', 'SUCCESS');
    			echo $this->WxPay->ToXml();
    			exit;
    		}
    		$orders_pay_info = M('orders_pay_info');
    		 
    		//查询流水记录
    		$payinfo = $orders_pay_info->lock(true)->where(array('order_number' => $params['out_trade_no']))->order('id desc')->find();
    		if ($payinfo['trade_status'] != '') {
    			$this->recordLogWrite($log_folder, "notify_result:SUCCESS:01".PHP_EOL."[END]".PHP_EOL);
    			$this->WxPay->SetData('return_code', 'SUCCESS');
    			echo $this->WxPay->ToXml();
    			exit();
    		}
    
    		if ($params['result_code']=='SUCCESS' && $orders['id'] > 0 && $payinfo['id'] > 0) {
    			$parameter = M('g_parameter', null)->find();
    			//订单主表状态
    			$om = new OrderModel();
    			$res1 = $om->updateOrder($params['out_trade_no'],1);
    			
    			// 更新付款状态
    			$data = array();
    			$data['trade_no'] = $params['transaction_id'];
    			$data['trade_status'] = $params['result_code'];
    			$data['total_amount'] = $orders['amount'];
    			$data['receipt_amount'] = $params['cash_fee'];
    			$data['gmt_payment'] = $params['time_end'];
    			$res2 = $orders_pay_info->where(array('order_number' => $params['out_trade_no']))->save($data);
    			
    		    $res3 = $om->shoppingpay($user, $orders, 2);
    			if($res1 !== false && $res2 !== false && $res3 !== false){
    				M()->commit();
    				$this->recordLogWrite($log_folder, "notify_result:SUCCESS:01".PHP_EOL."[END]".PHP_EOL);
    				$this->WxPay->SetData('return_code', 'SUCCESS');
    				echo $this->WxPay->ToXml();
    				exit;
    			}else{
    				M()->rollback();
    				$this->recordLogWrite($log_folder, "notify_result:FAIL:02".PHP_EOL."[END]".PHP_EOL);
    				$this->WxPay->SetData('return_code', 'FAIL');
    				echo $this->WxPay->ToXml();
    				exit;
    			}
    
    		}
    		M()->rollback();
    		$this->recordLogWrite($log_folder, "notify_result:FAIL:04".PHP_EOL."[END]".PHP_EOL);
    		$this->WxPay->SetData('return_code', 'FAIL');
    		echo $this->WxPay->ToXml();
    		exit;
    
    	} else {
    		$this->recordLogWrite($log_folder, "notify_result:FAIL:01".PHP_EOL."[END]".PHP_EOL);
    		$this->WxPay->SetData('return_code', 'FAIL');
    		echo $this->WxPay->ToXml();
    		exit;
    	}
    }
    
  
    
}
?>