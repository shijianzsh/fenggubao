<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 支付回调接口 
// +----------------------------------------------------------------------
namespace APP\Controller;

use AliPay;
use Common\Controller\ApiController;
use V4\Model\AccountModel;
use V4\Model\AccountRecordModel;
use V4\Model\ApplyModel;
use V4\Model\Currency;
use V4\Model\CurrencyAction;
use V4\Model\OrderModel;
use V4\Model\PaymentMethod;
use V4\Model\EnjoyModel;
use WxPayResults;

class NotifyController extends ApiController
{

    private $logfolder = 'wxnotify';
    private $payway = 'wechat';
    private $WxPay;
    private $aliPay;

    /**
     * 商户订单号
     */
    private $out_trade_no;

    /**
     * 兑换号(支付宝 / 微信)
     */
    private $trade_no;

    /**
     * 兑换状态
     */
    private $trade_status;

    /**
     * 订单金额
     */
    private $total_amount;

    /**
     * 实收金额
     */
    private $receipt_amount;

    /**
     * 兑换付款时间
     */
    private $gmt_payment;

    public function __construct()
    {
        parent::__construct();

//        $this->recordLogWrite($this->logfolder, '[construct]' . PHP_EOL . '[step0]_' . date('Y-m-d H:i:s') . PHP_EOL);

        //test_alinotify();
        if (isset($_POST['trade_no'])) {
            //支付宝
            Vendor('Alipay.AliPay#Api');
            $this->aliPay = new AliPay();
            /** 支付宝回调  **/
            $this->payway = PaymentMethod::Alipay;
            $valid = $this->aliPay->notifyRsaCheck();
            if (!$valid) {
                $this->output('__construct.notifyRsaCheck fail' . PHP_EOL . http_build_query($_POST), 2, 'error', $this->payway);
            }
            if ($_POST['trade_status'] != 'TRADE_SUCCESS') {
                $this->output('__construct.trade_status.fail', 2, 'error', $this->payway);
            }
            $this->out_trade_no = $_POST['out_trade_no'];
            $this->trade_no = $_POST['trade_no'];
            $this->trade_status = $_POST['trade_status'];
            $this->total_amount = $_POST['total_amount'];
            $this->receipt_amount = $_POST['receipt_amount'];
            $this->gmt_payment = $_POST['gmt_create'];
        } else {
            //微信组件
            Vendor('WxPay.WxPay#Api');
            $this->WxPay = new WxPayResults();
            /** 微信回调  **/
            $this->payway = PaymentMethod::Wechat;
            $params = A('Pay')->wxSign();
            $log = sprintf('%s[construct]%s%s%s%s', PHP_EOL, date('Y-m-d H:i:s'), PHP_EOL, json_encode($params), PHP_EOL);
            $this->recordLogWrite($this->logfolder, $log);
            if (!$params) {
                $this->output('__construct.wxsign.fail', 2);
            }

            $this->out_trade_no = $params['out_trade_no'];
            $this->trade_no = $params['transaction_id'];
            $this->trade_status = $params['result_code'];
            $this->total_amount = $params['amount'];
            $this->receipt_amount = $params['cash_fee'];
            $this->gmt_payment = $params['time_end'];
        }

    }

    /**
     * 申请区域合伙人回调
     */
    public function apply_service()
    {
        //1.验证订单
        $order = $this->validateOrder('apply_service', 1);
        M()->startTrans();
        $result = true;
        $om = new OrderModel();
        if ($result) {
            $result = $om->updateOrder($this->out_trade_no);
        }

        if ($result) {
            $result = $om->updateOrderpayinfo($this->out_trade_no, $this->trade_no, $this->trade_status, $this->total_amount, $this->receipt_amount, $this->gmt_payment);
        }

        // 添加明细
        $arm = new AccountRecordModel();
        if ($result) {
            $result = $arm->add($order['uid'], Currency::Cash, CurrencyAction::CashXiaofeiChongzhiWeixin, $order['amount'], $arm->getRecordAttach($order['uid']), CurrencyAction::getLabel(CurrencyAction::CashXiaofeiChongzhiWeixin));
        }
        if ($result) {
            $result = $arm->add($order['uid'], Currency::Cash, CurrencyAction::CashApplyServiceWechat, -$order['amount'], $arm->getRecordAttach($order['uid']), CurrencyAction::getLabel(CurrencyAction::CashApplyServiceWechat));
        }

        if ($result) {
            $result = ApplyModel::getInstance()->serviceCallback($order['uid'], 3);
        }

        if ($result) {
            M()->commit();
            $this->output('apply_service', 10, 'SUCCESS', 'wechat');
        } else {
            M()->rollback();

            //修改订单状态为异常
            M('Orders')->where("order_number='{$this->out_trade_no}'")->save(['order_status'=>50, 'cancel_time'=>time()]);
            $this->output('apply_service', 11, 'SUCCESS', $this->payway);

//             $this->output('apply_service', 10, 'FAIL', 'wechat');
        }
        exit;
    }

    /**
     * 申请创客回调
     */
    public function hack_apply()
    {
//        1.验证订单
        $orders = $this->validateOrder('hack_apply', 4);

        M()->startTrans();
        $user = M('member')->where('id=' . $orders['uid'])->find();
        $res1 = M('member')->where(array('id' => $orders['uid']))->save(array('is_tt' => 1));

        // 更新明细
        $arm = new AccountRecordModel();
        if ($this->payway == PaymentMethod::Wechat) {
            $balance = floatval(AccountModel::getInstance()->getBalance($orders['uid'], Currency::Supply));
            $unlock_amount = $balance * intval($orders['dutypay']) * 0.01;

            if ($unlock_amount >= 0.01) {
                $res3_1 = $arm->add($orders['uid'], Currency::Bonus, CurrencyAction::BonusFromGoldCoin, $unlock_amount, '', '激活锁定资产', $orders['dutypay']);
                $res3_2 = $arm->add($orders['uid'], Currency::Supply, CurrencyAction::SupplyXiaofei, -$unlock_amount, '', '激活锁定资产', $orders['dutypay']);
                $res3 = $res3_1 && $res3_2;
            } else {
                $res3 = true;
            }
        } else {
//			$res3 = $arm->add($orders['uid'], Currency::Cash, CurrencyAction::AliPayRecharge, $orders['amount'], $arm->getRecordAttach($user['id'], $user['nickname'], $user['img']), '支付宝在线充值');
        }
//        $res4 = $arm->add($orders['uid'], Currency::Cash, CurrencyAction::CashActiveMember, -$orders['amount'], $arm->getRecordAttach(1, '管理员'), '开通创客');
        $res4 = true;
        //订单主表状态
        $om = new OrderModel();
        $res5 = $om->updateOrder($this->out_trade_no);
        $res6 = $om->updateOrderpayinfo($this->out_trade_no, $this->trade_no, $this->trade_status, $this->total_amount, $this->receipt_amount, $this->gmt_payment);

        if ($res1 !== false && $res3 !== false && $res4 !== false && $res5 !== false && $res6 !== false) {
            M()->commit();
            $this->output('hack_apply', 10, 'SUCCESS', $this->payway);
            exit;
        } else {
            M()->rollback();
            $this->output('hack_apply', 10, 'FAIL', $this->payway);
            exit;
        }

    }


    /**
     * 商城回调
     */
    public function shopping()
    {
        $om = new OrderModel();
        $arm = new AccountRecordModel();
        $EnjoyModel = new EnjoyModel();

        //1.验证订单
        $orders = $this->validateOrder('shopping', 1);
        $user = M('member')->find($orders['uid']);

        M()->startTrans();

        //订单主表状态
        $res1 = $om->updateOrder($this->out_trade_no, 1);

        $res2 = $om->updateOrderpayinfo($this->out_trade_no, $this->trade_no, $this->trade_status, $this->total_amount, $this->receipt_amount, $this->gmt_payment);

        $this->recordLogWrite($this->logfolder, '[shopping]' . PHP_EOL . '[step20]_' . date('Y-m-d H:i:s') . PHP_EOL);

        $res3 = $om->shoppingpay($user, $orders, $this->payway);

        $this->recordLogWrite($this->logfolder, '[shopping]' . PHP_EOL . '[step21]_' . date('Y-m-d H:i:s') . PHP_EOL);

        //组合支付扣除
//         $result_combined = $om->orderCombinedComplete($orders['id']);
        $result_combined = true;

        //赠送澳洲SKN股数
//      $result_enjoy = $EnjoyModel->consumeGive($orders['id']);
        $result_enjoy = true;

        if ($res1 !== false && $res2 !== false && $res3 !== false && $result_combined !== false && $result_enjoy !== false) {
            M()->commit();
            $this->output('shopping', 10, 'SUCCESS', $this->payway);
            exit;
        } else {
            M()->rollback();
            $this->recordLogWrite($this->logfolder, '[shopping]'. PHP_EOL. '[step22]_'. date('Y-m-d H:i:s'). $res1. ':'. $res2. ':'. $res3. PHP_EOL);

            //修改订单状态为异常
            M('Orders')->where("order_number='{$this->out_trade_no}'")->save(['order_status'=>50, 'cancel_time'=>time()]);
            $this->output('shopping', 11, 'SUCCESS', $this->payway);

            exit;
        }

    }


    /**
     * 充值回调函数
     */
    public function recharge()
    {
        //1.验证订单
        $orders = $this->validateOrder('recharge', 4);
        $user = M('member')->find($orders['uid']);

        M()->startTrans();
        //订单主表状态
        $om = new OrderModel();
        $res1 = $om->updateOrder($this->out_trade_no, 4);
        $res2 = $om->updateOrderpayinfo($this->out_trade_no, $this->trade_no, $this->trade_status, $this->total_amount, $this->receipt_amount, $this->gmt_payment);

        //充值账户
        if ($orders['num'] == 2) {
            //提货券
            $currency = Currency::ColorCoin;
            if ($this->payway == PaymentMethod::Wechat) {
                $action = CurrencyAction::colorcoinChongzhiWeixin;
            } else {
                $action = CurrencyAction::colorcoinChongzhiZhifubao;
            }

            //计算充值获取的对应提货券个数
            $orders['amount'] = intval( $orders['amount'] / $orders['checknum'] );
        } else {
            //现金积分
            $currency = Currency::Cash;
            if ($this->payway == PaymentMethod::Wechat) {
                $action = CurrencyAction::CashChongzhiWeixin;
            } else {
                $action = CurrencyAction::CashChongzhiZhifubao;
            }
        }

        $arm = new AccountRecordModel();
        if ($this->payway == PaymentMethod::Wechat) {
            $res3 = $arm->add($orders['uid'], $currency, $action, $orders['amount'], $arm->getRecordAttach($user['id'], $user['nickname'], $user['img']), '微信在线充值');
        } else {
            $res3 = $arm->add($orders['uid'], $currency, $action, $orders['amount'], $arm->getRecordAttach($user['id'], $user['nickname'], $user['img']), '支付宝在线充值');
        }

        if ($res1 !== false && $res2 !== false && $res3 !== false) {
            M()->commit();
            $this->output('recharge', 10, 'SUCCESS', $this->payway);
            exit;
        } else {
            M()->rollback();

            //修改订单状态为异常
            M('Orders')->where("order_number='{$this->out_trade_no}'")->save(['order_status'=>50, 'cancel_time'=>time()]);
            $this->output('recharge', 11, 'SUCCESS', $this->payway);

//             $this->output('recharge', 10, 'FAIL', $this->payway);
            exit;
        }


    }


    /**
     * 买单回调函数
     */
    public function consume()
    {
        //1.验证订单
        $orders = $this->validateOrder('consume', 4);
        $user = M('member')->find($orders['uid']);
        $store = M('store')->where(array('id' => $orders['storeid'], 'status' => 0, 'manage_status' => 1))->find();
        $pw_result = M('preferential_way')->where(array('store_id' => $store['id'], 'status' => 0, 'manage_status' => 1))->find();
        $seller = M('member')->where('id = ' . $store['uid'])->find();
        $post = array('buyer' => $user, 'seller' => $seller, 'pw' => $pw_result, 'store' => $store, 'order' => $orders);

        M()->startTrans();
        $om = new OrderModel();
        $params['out_trade_no'] = $this->out_trade_no;
        $params['amount'] = $this->total_amount;
        $res = $om->consumeByWechatAndAlipay($post, $params, $this->payway);

        if ($res) {
            M()->commit();
            $this->output('consume', 10, 'SUCCESS', $this->payway);
            exit;
        } else {
            M()->rollback();

            //修改订单状态为异常
            M('Orders')->where("order_number='{$this->out_trade_no}'")->save(['order_status'=>50, 'cancel_time'=>time()]);
            $this->output('consume', 11, 'SUCCESS', $this->payway);

//             $this->output('consume', 10, 'FAIL', $this->payway);
            exit;
        }


    }


    /** res4 无明细类型
     * 责任消费回调函数
     */
    public function dutyconsume()
    {
        //1.验证订单
//		$orders = $this->validateOrder('dutyconsume', 4);
//		$user = M('member')->find($orders['uid']);
//		$parameter = M('g_parameter', null)->find();
//
//		M()->startTrans();
//		//订单主表状态
//		$om = new OrderModel();
//		$res1 = $om->updateOrder($this->out_trade_no, 4);
//		$res2 = $om->updateOrderpayinfo($this->out_trade_no, $this->trade_no, $this->trade_status, $this->total_amount, $this->receipt_amount, $this->gmt_payment);
//
//		//消费记录
//    	$arm = new AccountRecordModel();
//    	if($this->payway == PaymentMethod::Wechat){
//	    	$res3 = $arm->add($orders['uid'], Currency::Cash, CurrencyAction::WechatPayRecharge, $orders['amount'], $arm->getRecordAttach($user['id'], $user['nickname'], $user['img']), '微信支付在线充值');
//	    	$res4 = $arm->add($orders['uid'], Currency::Cash, CurrencyAction::WechatDutyConsume, -$orders['amount'], $arm->getRecordAttach(1, '管理员'), '微信责任消费');
//    	}else{
//    		$res3 = $arm->add($orders['uid'], Currency::Cash, CurrencyAction::AliPayRecharge, $orders['amount'], $arm->getRecordAttach($user['id'], $user['nickname'], $user['img']), '支付宝在线充值');
//    		$res4 = $arm->add($orders['uid'], Currency::Cash, CurrencyAction::WechatDutyConsume, -$orders['amount'], $arm->getRecordAttach(1, '管理员'), '支付宝责任消费');
//    	}
//
//    	//获得积分
//    	$credits = $orders['amount']*$parameter['duty_consume_exchange_bei'];
//    	$res5 = $arm->add($orders['uid'], Currency::Credits, CurrencyAction::CreditsDutyConsumeExchane, $credits, $arm->getRecordAttach(1, '系统'), '责任消费兑换积分');
//
//    	//责任消费金额累加
//    	M('dutyconsume')->where('user_id = '.$orders['uid'])->save(array('dutyconsume_complete_amount'=>array('exp','dutyconsume_complete_amount+'.$orders['amount']), 'dutyconsume_income_enable'=>1));
//
//    	M()->execute(C('ALIYUN_TDDL_MASTER') . "call Bonus_dutyconsume(".$orders['uid'].", ".$orders['amount'].")");
//
//    	if($res1 && $res2 && $res3 && $res4 && $res5){
//			M()->commit();
//			$this->output('dutyconsume', 10, 'SUCCESS', $this->payway);
//			exit;
//		}else{
//			M()->rollback();
//			$this->output('dutyconsume', 10, 'FAIL', $this->payway);
//			exit;
//		}
    }


    /**
     * 金卡代理申请回调函数
     */
    public function vip_apply()
    {
        //1.验证订单
//		$orders = $this->validateOrder('vip_apply', 4);
//		$user = M('member')->find($orders['uid']);
//
//		M()->startTrans();
//		//订单主表状态
//		$om = new OrderModel();
//		$res1 = $om->updateOrder($this->out_trade_no, 4);
//		$res2 = $om->updateOrderpayinfo($this->out_trade_no, $this->trade_no, $this->trade_status, $this->total_amount, $this->receipt_amount, $this->gmt_payment);
//
//		//vip审核状态,首次申请直接通过
//		$res6 = M('vip_apply')->where('user_id = '.$orders['uid'])->save(array('apply_status'=>3));
//
//		//充值账户
//		$arm = new AccountRecordModel();
//		if($this->payway == PaymentMethod::Wechat){
//			$res3 = $arm->add($orders['uid'], Currency::Cash, CurrencyAction::WechatPayRecharge, $orders['amount'], $arm->getRecordAttach($user['id'], $user['nickname'], $user['img']), '微信支付在线充值');
//    		$res4 = $arm->add($orders['uid'], Currency::Cash, CurrencyAction::WechatApplyVIP, -$orders['amount'], $arm->getRecordAttach(1, '管理员'), '开通金卡代理');
//		}else{
//			$res3 = $arm->add($orders['uid'], Currency::Cash, CurrencyAction::AliPayRecharge, $orders['amount'], $arm->getRecordAttach($user['id'], $user['nickname'], $user['img']), '支付宝在线充值');
//    		$res4 = $arm->add($orders['uid'], Currency::Cash, CurrencyAction::AlipayApplyVIP, -$orders['amount'], $arm->getRecordAttach(1, '管理员'), '开通金卡代理');
//		}
//
//		//注册币
//		$res5 = true;
//		if($orders['order_enroll_amount'] > 0){
//			$res5 = $arm->add($orders['uid'], Currency::Enroll, CurrencyAction::ENrollApplyVIP, -$orders['order_enroll_amount'], $arm->getRecordAttach(1, '系统', '', $this->out_trade_no), '申请金卡代理扣款');
//		}
//
//		//升级
//		$res7 = M('member')->where('id = '.$orders['uid'])->save(array('roleid'=>array('exp','`level`'), 'level'=>6, 'open_time'=>time()));
//
//		//定时结算
//		$mm = new MemberModel();
//		$res8 = $mm->vipclear($orders['uid']);
//
//		//吊起存储过程
//		$pm = new ProcedureModel();
//		$res9 = $pm->execute('V51_Event_apply', $orders['uid'], '@error');
//
//
//		if($res1 !== false && $res2 !== false && $res3 !== false && $res4 !== false && $res5 !== false && $res6 !== false && $res7 !== false && $res8 && $res9){
//			M()->commit();
//			$this->output('vip_apply', 10, 'SUCCESS', $this->payway);
//			exit;
//		}else{
//			M()->rollback();
//			$this->output('vip_apply', 10, 'FAIL', $this->payway);
//			exit;
//		}
    }


    /**
     * 银卡代理申请回调函数
     */
    public function v_apply()
    {
        //1.验证订单
//		$orders = $this->validateOrder('v_apply', 4);
//		$user = M('member')->find($orders['uid']);
//
//		M()->startTrans();
//		//订单主表状态
//		$om = new OrderModel();
//		$res1 = $om->updateOrder($this->out_trade_no, 4);
//		$res2 = $om->updateOrderpayinfo($this->out_trade_no, $this->trade_no, $this->trade_status, $this->total_amount, $this->receipt_amount, $this->gmt_payment);
//
//		//银卡代理审核状态,首次申请直接通过
//    	$res6 = M('micro_vip_apply')->where('user_id = '.$orders['uid'])->save(array('apply_status'=>3));
//
//		//充值账户
//		$arm = new AccountRecordModel();
//		if($this->payway == PaymentMethod::Wechat){
//			$res3 = $arm->add($orders['uid'], Currency::Cash, CurrencyAction::WechatPayRecharge, $orders['amount'], $arm->getRecordAttach($user['id'], $user['nickname'], $user['img']), '微信支付在线充值');
//    		$res4 = $arm->add($orders['uid'], Currency::Cash, CurrencyAction::WechatApplyMicroVIP, -$orders['amount'], $arm->getRecordAttach(1, '管理员'), '开通银卡代理');
//		}else{
//			$res3 = $arm->add($orders['uid'], Currency::Cash, CurrencyAction::AliPayRecharge, $orders['amount'], $arm->getRecordAttach($user['id'], $user['nickname'], $user['img']), '支付宝在线充值');
//    		$res4 = $arm->add($orders['uid'], Currency::Cash, CurrencyAction::AlipayApplyMicroVIP, -$orders['amount'], $arm->getRecordAttach(1, '管理员'), '开通银卡代理');
//		}
//
//		//注册币
//    	$res5 = true;
//    	if($orders['order_enroll_amount'] > 0){
//    		$res5 = $arm->add($orders['uid'], Currency::Enroll, CurrencyAction::ENrollApplyMicroVIP, -$orders['order_enroll_amount'], $arm->getRecordAttach(1, '系统', '', $this->out_trade_no), '申请银卡代理扣款');
//    	}
//
//		//升级
//    	$res7 = M('member')->where('id = '.$orders['uid'])->save(array('roleid'=>array('exp','`level`'), 'level'=>5, 'open_time'=>time()));
//
//    	//定时结算
//    	$mm = new MemberModel();
//    	$res8 = $mm->v_vipclear($orders['uid']);
//
//		//吊起存储过程
//    	$pm = new ProcedureModel();
//    	$res9 = $pm->execute('V51_Event_apply', $orders['uid'], '@error');
//
//		if($res1 !== false && $res2 !== false && $res3 !== false && $res4 !== false && $res5 !== false && $res6 !== false && $res7 !== false && $res8 !== false && $res9){
//			M()->commit();
//			$this->output('v_apply', 10, 'SUCCESS', $this->payway);
//			exit;
//		}else{
//			M()->rollback();
//			$this->output('v_apply', 10, 'FAIL', $this->payway);
//			exit;
//		}
    }

    /**
     * 钻卡代理申请回调函数
     */
    public function honoureVip_apply()
    {
        //1.验证订单
//		$orders = $this->validateOrder('honoureVip_apply', 4);
//		$user = M('member')->find($orders['uid']);
//		$parameter = M('g_parameter', null)->find();
//
//		M()->startTrans();
//		//订单主表状态
//		$om = new OrderModel();
//		$mm = new MemberModel();
//		$res1 = $om->updateOrder($this->out_trade_no, 4);
//		$res2 = $om->updateOrderpayinfo($this->out_trade_no, $this->trade_no, $this->trade_status, $this->total_amount, $this->receipt_amount, $this->gmt_payment);
//
//		//vip审核状态,首次申请直接通过
//    	$res6 = M('honour_vip_apply')->where('user_id = '.$orders['uid'])->save(array('apply_status'=>3));
//
//		//充值账户
//		$arm = new AccountRecordModel();
//		if($this->payway == PaymentMethod::Wechat){
//			$res3 = $arm->add($orders['uid'], Currency::Cash, CurrencyAction::WechatPayRecharge, $orders['amount'], $arm->getRecordAttach($user['id'], $user['nickname'], $user['img']), '微信支付在线充值');
//    		$res4 = $arm->add($orders['uid'], Currency::Cash, CurrencyAction::WechatApplyHonourVIP, -$orders['amount'], $arm->getRecordAttach(1, '管理员'), '开通钻卡代理');
//		}else{
//			$res3 = $arm->add($orders['uid'], Currency::Cash, CurrencyAction::AliPayRecharge, $orders['amount'], $arm->getRecordAttach($user['id'], $user['nickname'], $user['img']), '支付宝在线充值');
//    		$res4 = $arm->add($orders['uid'], Currency::Cash, CurrencyAction::AlipayApplyHonourVIP, -$orders['amount'], $arm->getRecordAttach(1, '管理员'), '开通钻卡代理');
//		}
//
//		//注册币
//		$res5 = true;
//    	if($orders['order_enroll_amount'] > 0){
//    		$res5 = $arm->add($orders['uid'], Currency::Enroll, CurrencyAction::ENrollApplyHonourVIP, -$orders['order_enroll_amount'], $arm->getRecordAttach(1, '系统', '', $this->out_trade_no), '申请钻卡代理扣款');
//    	}
//
//    	$plantype = 0;
//    	//交完钱-升级
//    	$res7 = true;
//    	$res99 = true;
//    	if($parameter['honour_vip_apply_amount'] - $parameter['honour_vip_apply_first_amount'] == 0){
//    		$res7 = M('member')->where('id = '.$orders['uid'])->save(array('roleid'=>array('exp','`level`'), 'level'=>7, 'open_time'=>time()));
//    		//回本
//    		$mm->honoureVipclear($orders['uid']);
//    		//吊起存储过程
//    		$pm = new ProcedureModel();
//    		$res99 = $pm->execute('V51_Event_apply', $orders['uid'], '@error');
//    	}
//
//    	if($res1 !== false && $res2 !== false && $res3 !== false && $res4 !== false && $res5 && $res6 !== false && $res7 !== false && $res99){
//			M()->commit();
//			$this->output('honoureVip_apply', 10, 'SUCCESS', $this->payway);
//			exit;
//		}else{
//			M()->rollback();
//			$this->output('honoureVip_apply', 10, 'FAIL', $this->payway);
//			exit;
//		}
    }


    /**
     * 续费钻卡代理申请回调函数
     */
    public function honoureVip_renew()
    {
        //1.验证订单
//		$orders = $this->validateOrder('honoureVip_renew', 4);
//		$user = M('member')->find($orders['uid']);
//		$parameter = M('g_parameter', null)->find();
//
//		M()->startTrans();
//		//订单主表状态
//		$om = new OrderModel();
//		$mm = new MemberModel();
//		$res1 = $om->updateOrder($this->out_trade_no, 4);
//		$res2 = $om->updateOrderpayinfo($this->out_trade_no, $this->trade_no, $this->trade_status, $this->total_amount, $this->receipt_amount, $this->gmt_payment);
//
//		//处理业务
//    	$firstpay = M('user_affiliate')->where('user_id = '.$orders['uid'])->find();
//    	if(empty($firstpay)){
//    		M()->rollback();
//			$this->output('honoureVip_apply', 9, 'FAIL', $this->payway);
//			exit;
//    	}
//    	$arm = new AccountRecordModel();
//    	if($this->payway == PaymentMethod::Wechat){
//    		$res3 = $arm->add($orders['uid'], Currency::Cash, CurrencyAction::WechatPayRecharge, $firstpay['honour_vip_unpaid_amount'], $arm->getRecordAttach($user['id'], $user['nickname'], $user['img']), '微信支付在线充值');
//    	}else{
//    		$res3 = $arm->add($orders['uid'], Currency::Cash, CurrencyAction::AliPayRecharge, $firstpay['honour_vip_unpaid_amount'], $arm->getRecordAttach($user['id'], $user['nickname'], $user['img']), '支付宝在线充值');
//    	}
//
//    	$mm = new MemberModel();
//    	$res1 = $mm->honourVipRenew($orders['uid'], $firstpay);
//    	$res2 = $mm->honoureVipclear($orders['uid']);
//    	//吊起存储过程
//    	$pm = new ProcedureModel();
//    	$res5 = $pm->execute('V51_Event_apply', $orders['uid'], '@error');
//    	if($res1 && $res2 && $res3 && $res5){
//			M()->commit();
//			$this->output('honoureVip_renew', 10, 'SUCCESS', $this->payway);
//			exit;
//		}else{
//			M()->rollback();
//			$this->output('honoureVip_renew', 10, 'FAIL', $this->payway);
//			exit;
//		}
    }


    /**
     * 微信处理结果返回
     *
     * @param unknown $method
     * @param number $step
     * @param string $status SUCCESS/FAIL
     */
    private function wxreturn($method, $step = 1, $status = 'FAIL')
    {
        $this->recordLogWrite($this->logfolder, '[' . $method . ']' . PHP_EOL . '[step' . $step . ']_' . date('Y-m-d H:i:s')) . PHP_EOL;
        $this->WxPay->SetData('return_code', $status);
        echo $this->WxPay->ToXml();
        exit;
    }

    /**
     * 支付宝返回结果
     *
     * @param unknown $method
     * @param number $step
     * @param string $status success/
     */
    private function alieturn($method, $step = 1, $status = 'error')
    {
        $this->recordLogWrite($this->logfolder, '[' . $method . ']' . PHP_EOL . '[step' . $step . ']_' . date('Y-m-d H:i:s') . PHP_EOL);
        echo $status;
        exit;
    }

    /**
     * 输出结果
     *
     * @param unknown $method
     * @param number $step
     * @param string $status
     * @param string $payway
     */
    private function output($method, $step = 1, $status = 'FAIL', $payway = 'wechat')
    {
        if ($payway == PaymentMethod::Wechat) {
            $this->wxreturn($method, $step, $status);
        } else {
            $this->alieturn($method, $step, strtolower($status));
        }
    }


    /**
     * 验证订单信息
     *
     * @param string $method
     * @param number $orderstatus 1=已付款,4=已完成
     *
     * @return NULL
     */
    private function validateOrder($method, $orderstatus = 1)
    {
        $this->recordLogWrite($this->logfolder, '[validateOrder]' . PHP_EOL . '[step1:method:' . $method . ']_' . date('Y-m-d H:i:s') . PHP_EOL);

        $orders = M('orders')->where('order_number=\'' . $this->out_trade_no . '\'')->find();
        if (!$orders) {
            $this->output($method, 3, 'SUCCESS', $this->payway);
        }

        //将order_status!=0的订单统一回复为SUCCESS
        if ($orders['order_status'] != 0) {
            $this->output($method, 4, 'SUCCESS', $this->payway);
        }

        if ($orders['order_status'] == $orderstatus) {
            $this->output($method, 5, 'SUCCESS', $this->payway);
        }

        //查询流水记录
        $payinfo = M('orders_pay_info')->where(array('order_number' => $this->out_trade_no))->find();
        if (!$payinfo) {
            $this->output($method, 6, 'SUCCESS', $this->payway);
        }
        if ($payinfo['trade_status'] != '') {
            $this->output($method, 7, 'SUCCESS', $this->payway);
        }

        return $orders;
    }

}

?>