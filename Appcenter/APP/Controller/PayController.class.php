<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 买单接口
// +----------------------------------------------------------------------
namespace APP\Controller;
use Common\Controller\ApiController;
use V4\Model\OrderModel;
use V4\Model\PaymentMethod;
use V4\Model\Currency;
use V4\Model\AccountModel;
use V4\Model\RewardModel;

class PayController extends ApiController {
	
	public function __construct() {
		parent::__construct();
		
		vendor('MyPay.AopSdk'); //支付宝支付基础组件
		Vendor("WxPay.WxPay#Api"); //微信支付基础组件
	}
	
	/**
	 * 在线买单接口
	 * Enter description here ...
	 */
    public function consume() {
    	//23:30之后不能下单
    	$timestr = date('Y-m-d',time()).' 23:30';
    	if(time() > strtotime($timestr)){
    		$this->myApiPrint('每日23:30之后不能下单！');
    	}
        $uid = intval(I('post.uid'));
        $dutypay = intval(I('post.dutypay'));
        $amount = I('post.amount');     //总金额
        $comment = I('post.comment');
        $pay_type = intval(I('post.pay_type'));
        $storeid = I('post.storeid');
        $om = new OrderModel();
        
        //验证数据
        $post = $this->validateConsume($uid, $dutypay, $amount, $pay_type, $storeid);
        
        if($pay_type == 1){ //------支付宝
            M()->startTrans();
            $rr = $om->consumeOrder($uid, $post['money']['cash'], $post['money']['goldcoin'], PaymentMethod::Alipay, 0, $storeid);
            if(!$rr){
                $this->myApiPrint('下单失败', 300);
            }
            //冻结公让宝
            $am = new AccountModel();
            $res2 = $am->frozenRefund($uid, $rr['id'], array('domiciled_credits'=>0,'domiciled_supply'=>0,'domiciled_goldcoin'=>$post['money']['goldcoin'],'domiciled_colorcoin'=>0), '买单冻结资金');
            
            if($res2 !== false && $rr !== false){
            	M()->commit();
            }else{
            	M()->rollback();
            }
            //生成签名
            $signStr = $om->getAlipaySign($rr['orderNo'], $post['money']['cash'], 'Notify/consume');
            $returndata = $om->format_return('返回成功', 400, $signStr);
            $this->ajaxReturn($returndata);
            
        }elseif($pay_type == 2){ //------微信
        	$this->myApiPrint('微信支付或提现功能维护中');
        	M()->startTrans();
            $rr = $om->consumeOrder($uid, $post['money']['cash'], $post['money']['goldcoin'], PaymentMethod::Wechat, 0, $storeid);
            if(!$rr){
                $this->myApiPrint('下单失败', 300);
            }
            //冻结公让宝
            $am = new AccountModel();
            $res2 = $am->frozenRefund($uid, $rr['id'], array('domiciled_credits'=>0,'domiciled_supply'=>0,'domiciled_goldcoin'=>$post['money']['goldcoin'],'domiciled_colorcoin'=>0), '买单冻结资金');
            
            if($res2 !== false && $rr !== false){
            	M()->commit();
            }else{
            	M()->rollback();
            }
            //生成签名
            $signStr = $om->getWxpaySign($rr['orderNo'], $post['money']['cash'], 'Notify/consume');
            $returndata = $om->format_return('返回成功', 400, $signStr);
            $this->ajaxReturn($returndata);
        }else{
        	$this->myApiPrint('请使用微信/支付宝买单!', 300);
            M()->startTrans();
            if($pay_type == 4){      //------现金积分
                $res1 = $om->consumeByVirtualCurrency($post, $amount, $dutypay, Currency::Cash, $comment);
                
            }elseif($pay_type == 5){ //------公让宝
                $res1 = $om->consumeByVirtualCurrency($post, $amount, $dutypay, Currency::GoldCoin, $comment);
                
            }elseif($pay_type == 6){ //------商超券
                $res1 = $om->consumeByVirtualCurrency($post, $amount, $dutypay, Currency::ColorCoin, $comment);
                
            }else{
                M()->rollback();
                $this->myApiPrint('支付类型错误');
            }
            if($res1){
                M()->commit();
                $this->myApiPrint('支付成功' ,400);
            }else{
                M()->rollback();
                $this->myApiPrint('买单失败' ,300);
            }
        }
        
    }
    
    private function validateConsume($uid, $dutypay, $amount, $pay_type, $storeid, $goldcoin=0){
        $om = new OrderModel();
        //1.验证用户是否存在
        $user = M('member')->where('is_lock=0')->find($uid);
        if(!$user){
            $this->myApiPrint('无权限操作',300);
        }
        if($user['is_blacklist'] > 0){
            $this->myApiPrint('您的账号被锁定，禁止一切兑换！',300);
        }
        
        //2.验证店铺
        $store = M('store')->where('id='.$storeid.' and `status` = 0 and manage_status = 1')->find();
        if(!$store){
            $this->myApiPrint('店铺状态异常',300);
        }
        $seller = M('member')->where('is_lock=0')->find($store['uid']);
        if(!$seller){
            $this->myApiPrint('卖家账号被封',300);
        }
        
        //3.验证店铺活动
        $pw_result = M('preferential_way')->where(array('store_id' => $storeid, 'status'=>0, 'manage_status'=>1))->find();
        if (!$pw_result) {
            $this->myApiPrint('店铺未发布活动',300);
        }
        
        //4.不能自己买自己的
        if($store['uid'] == $uid){
            $this->myApiPrint('您不能在自己的店铺消费',300);
        }
        
        //5.买单类型
        if($dutypay == 1 && $pay_type != 4){
            $this->myApiPrint('责任消费只能用现金积分',300);
        }
        
        //6.商超券
        if($pay_type == 6 && $store['store_supermarket'] != 1){
            $this->myApiPrint('店铺类型不支持商超券',300);
        }
        
        if (!validateExtend($amount, 'MONEY')) {
            $this->myApiPrint('金额格式不对！');
        }
        if($amount < 0.01){
        	$this->myApiPrint('金额格式不对');
        }
        if($pay_type == 4){
            if(!$om->compareBalance($user['id'], Currency::Cash, $amount)){
                $this->myApiPrint('余额不足',300);
            }
        }
        if($pay_type == 5){
            if(!$om->compareBalance($user['id'], Currency::GoldCoin, $amount)){
                $this->myApiPrint('余额不足',300);
            }
        }
        if($pay_type == 6){
            if(!$om->compareBalance($user['id'], Currency::ColorCoin, $amount)){
                $this->myApiPrint('余额不足',300);
            }
        }
    	//计算现金积分+公让宝比例
		$rm = new RewardModel();
		$profitsData = $rm->getProfitsByOrder($amount, $pw_result['reward']);
		//获取余额
		$am = new AccountModel();
		$balance = $am->getAllBalance($uid);
		
		$return = array();
		if($balance['account_goldcoin_balance'] < $profitsData['profits']){
			$return['goldcoin'] = sprintf('%.2f', $balance['account_goldcoin_balance']);
			$return['cash'] = sprintf('%.2f', $amount - $return['goldcoin']);
		}else{
			$return['goldcoin'] = floor($profitsData['profits']*100)/100;
			$return['cash'] = ($amount - $return['goldcoin']).'';
		}
        return array('buyer'=>$user, 'seller'=>$seller, 'pw'=>$pw_result, 'store'=>$store, 'money'=>$return);
    }
    
    
    /**
     * 责任消费-买积分方案
     * Enter description here ...
     */
    public function dutyconsume() {
    	$this->myApiPrint('接口暂停使用', 300);
    	$uid = intval(I('post.uid'));
    	$amount = I('post.amount');
    	$pay_type = I('post.pay_type');  //1.现金积分 2微信
    	$om = new OrderModel();
    
    	
    	//验证数据
    	$amount = verify_dutyconsume($uid);
    	
    	if($pay_type == 2){ //------微信
    		$orderNo = $om->create($uid, $amount, PaymentMethod::Wechat, 0, 0, '微信责任消费', '', 1, 1, 2);
    		if($orderNo == ''){
    			$this->myApiPrint('下单失败', 300);
    		}
    
    		//生成签名
    		$signStr = $om->getWxpaySign($orderNo, $amount, 'Notify/dutyconsume');
    		$returndata = $om->format_return('返回成功', 400, $signStr);
    		$this->ajaxReturn($returndata);
    	}else{
    		if(!$om->compareBalance($uid, Currency::Cash, $amount)){
                $this->myApiPrint('余额不足',300);
            }
            M()->startTrans();
            $res = $om->dutyconsume2($uid, $amount);
    		if($res){
    			M()->commit();
    			$this->myApiPrint('支付成功' ,400);
    		}else{
    			M()->rollback();
    			$this->myApiPrint('支付失败' ,300);
    		}
    	}
    
    }
    
	
	/**
	 * 总支付接口
	 *
	 * 供其他控制器调用
	 *
	 * @param $store_id 商户id，0为平台
	 * @param $order_num 订单号，由系统生成
	 * @param $pay_type 支付类型，1支付宝，2微信，3银行
	 * @param $amount 支付金额
	 * @param $subject 订单标题
	 * @param $body 订单描述
	 * @param $append_url 回调url
	 */
	public function mutil_pay($store_id, $order_num, $pay_type, $amount, $subject, $body, $append_url) {
		$store_id = $store_id ? $store_id : 0;
		$pay_type = $pay_type ? $pay_type : 1;
		$amount = $amount ? $amount : 0.01;
		$subject = $subject ? $subject : C('APP_TITLE').'订单';
		$body = $body ? $body : C('APP_TITLE').'订单描述';
	
		$return_str = '';
	
		if ($pay_type == '1') {                                          // 支付宝支付
	
			// 业务参数
			$bus_params['order_time'] = time();                         // 时间
			$bus_params['storeid'] = $store_id;                         // 店铺id
			$bus_params['out_trade_no'] = $order_num;                   // 订单编号
			$bus_params['body'] =   $body; // 订单描述
			$bus_params['subject'] = $subject;                          // 订单标题
			$bus_params['total_amount'] = $amount;                      // 订单总金额
			$bus_params['product_code'] = "QUICK_MSECURITY_PAY";        // 产品代码
			$bus_params['seller_id'] = "2012045631@qq.com";             // 卖方支付宝邮箱
			$bus_params['timeout_express'] = "1.5h";                    // 超时设置
	
			// 公共参数
			$pub_params['app_id'] = '2016082201786461';
			$pub_params['method'] = 'alipay.trade.app.pay';
			$pub_params['format'] = 'JSON';
			$pub_params['charset'] = 'UTF-8';
			$pub_params['sign_type'] = 'RSA';
			$pub_params['timestamp'] = date("Y-m-d H:i:s");
			$pub_params['version'] = '1.0';
			$pub_params['notify_url'] = ROOT_URL.$append_url;   // 支付宝通知回调地址
			$pub_params['biz_content'] = json_encode($bus_params);
	
			ksort($pub_params);                                         // 排序
	
			$c = new \AopClient();
			$c->appId = '2016082201786461';                             // 应用ID
			$c->rsaPrivateKeyFilePath = dirname(__FILE__).'/key/rsa_private_key.pem';
			$pub_params['sign'] = $c->rsaSign($pub_params);             // 签名
				
			$return_str = $this->createLinkstring($pub_params);         // 拼接返回串
		}
		
		if ($pay_type == '2') {                                          // 微信支付
			$append_url = str_replace('APP/Notify', 'WxNotify', $append_url);
			$append_url = U($append_url,'','',true);
			
			//数据处理
			$amount = sprintf('%.2f', $amount);
			$amount = sprintf('%d', $amount*100);
			
			// 微信预支付订单
			$WxPay = new \WxPayUnifiedOrder();
			$WxPay->SetBody($body);
			$WxPay->SetAttach($subject);
			$WxPay->SetOut_trade_no($order_num);
			$WxPay->SetTotal_fee($amount);
			$WxPay->SetTime_start(date("YmdHis"));
			$WxPay->SetTime_expire(date("YmdHis", time() + 600));
			$WxPay->SetNotify_url($append_url);
			$WxPay->SetTrade_type("APP");
			$wx_pay_return = \WxPayApi::unifiedOrder($WxPay);
				
			if ($wx_pay_return['return_code'] == 'SUCCESS') {
				$time = time();
				$return_data = array(
					'appid' => $wx_pay_return['appid'],
					'partnerid' => $wx_pay_return['mch_id'],
					'prepayid' => $wx_pay_return['prepay_id'],
					'package' => 'Sign=WXPay',
					'noncestr' => $wx_pay_return['nonce_str'],
					'timestamp' => (String)$time
				);
	
				//生成传给APP的签名
				$WxPR = \WxPayResults::InitFromArray($return_data, true);
				$sign = $WxPR->MakeSign();
				$return_data['sign'] = $sign;
	
				//返回预支付兑换回话标识(该值有效期2小时)和签名,用于APP端调用
				return $return_data;
			} else {
				return false;
			}
		}
		
		if ($pay_type == '3') {                                          // 其他支付
			
		}
	
		if ($return_str) {
			return $return_str;
		} else {
			return null;
		}
	}
	
	/**
	 * 获取支付签名接口
	 *
	 * 供控制器调用
	 */
	public function get_sign_4_controller($param_arr, $append_url) {
		return $this->mutil_pay($param_arr['store_id'],$param_arr['order_number'] , $param_arr['pay_type'], $param_arr['amount'], $param_arr['subject'], $param_arr['body'], $append_url);
	}
	
	/**
	 * 生成16位订单号（待优化）
	 *
	 * 订单号必须唯一，以下做法只能尽可能
	 */
	public function build_order_no_core() {
		$year_code = array('0','1','2','3','4','5','6','7','8','9');
		return $year_code[intval(date('Y'))-2016].
		strtoupper(dechex(date('m'))).date('d').
		substr(time(),-5).substr(microtime(),2,5).(( $f = intval(rand(0,99))) < 10 ? '0'.$f : $f  );
	}
	
	/**
	 * 生成各种支付方式对应专属字母的订单号(独占前5位,不足5位补随机数,专属字母不能超过5位)
	 * 统一格式($type):
	 * 支付宝: ALI
	 * 微信: WX
	 * 银行卡: BANK
	 * 默认兼容为支付宝
	 */
	public function build_order_no($type='ALI') {
		$allow_type = array('ALI', 'WX', 'BANK', 'OTHER');
			
		//为不影响其他涉及支付的接口功能 和 便捷的统一管理,在此尝试获取pay_type参数来决定生成订单号规则
		//1支付宝2微信3其他4现金积分5公让宝
		$pay_type = I('post.pay_type');
		$pay_type = empty($pay_type) ? false : intval($pay_type-1);
		$type = $pay_type ? ($pay_type>1 ? $allow_type[3] : $allow_type[$pay_type]) : $allow_type[3];
			
		$order_no = $this->build_order_no_core();
			
		if (in_array($type, $allow_type)) {
			$order_no_change = function() use ($type, $order_no) {
				$padding = '';
				if (strlen($type)<5) {
					$padding_num = 5-strlen($type);
					$microtime = microtime();
					$padding = substr($microtime, 2, $padding_num);
				}
				return $type.$padding.$order_no;
			};
			$order_no = $order_no_change();
		}
			
		return $order_no;
	}
	
	/**
	 * 生成流水号
	 */
	public function build_serial_num() {
		return date('Ymd').
			substr(time(),-5).
			substr(microtime(),2,5).
			(( $f = intval(rand(0,99))) < 10 ? '0'.$f : $f  );
	}
	
	/**
	 * 拼返回串方法
	 */
	public function createLinkstring($para) {
		$arg = '';
		
		while (list($key, $val) = each($para)) {
			$arg .= $key.'='.urlencode($val).'&';
		}
		
		//去掉最后一个&字符
		$arg = substr($arg, 0, count($arg) - 2);
		
		return $arg;
	}
	
	/**
	 * 支付宝提现接口
	 *
	 * @param id 提现申请表id，注意：已经处理的申请切勿重复提交
	 */
	public function wc_deposit() {
		$ids = I('post.id');
	
		$apply_arr = $this->format_wc_id($ids);
		
		// 业务参数
		$bus_params['account_name'] = '眉山信达时空网络科技有限公司'; // 付款账号名
		$bus_params['detail_data'] = $apply_arr['str'];
		$bus_params['batch_no'] = date('Ymd',time()).rand(10000,99999).substr(microtime(),2,5);    // 批量付款批次号
		$bus_params['batch_num'] = $apply_arr['total_num'];           // 付款总笔数
		$bus_params['batch_fee'] = $apply_arr['total_amount'];        // 付款总金额
		$bus_params['email'] = '2012045631@qq.com';                 // 付款账号
		$bus_params['pay_date'] = date('Ymd', time());              // 支付日期
		$bus_params['buyer_account_name'] = "2012045631@qq.com";    // 付款账号别名
		$bus_params['service'] = 'batch_trans_notify';
		$bus_params['partner'] = '2088421708109484';
		$bus_params['_input_charset'] = 'utf-8';
		$bus_params['notify_url'] = ROOT_URL.'/APP/Notify/withdraw_cash';
	
		$ALIPAY_CONFIG = C('ALIPAY_CONFIG');
		$ALIPAY_CONFIG['private_key'] = str_replace('-----BEGIN RSA PRIVATE KEY-----','', $ALIPAY_CONFIG['private_key']);
		$ALIPAY_CONFIG['alipay_public_key'] = str_replace('-----BEGIN PUBLIC KEY-----','', $ALIPAY_CONFIG['alipay_public_key']);
	
		require_once('/ThinkPHP/Library/Vendor/batchpay/alipay_submit.class.php');
		$alipaySubmit = new \AlipaySubmit($ALIPAY_CONFIG);
		$html_text = $alipaySubmit->buildRequestForm($bus_params,"get", "确认");
		echo $html_text;
	}
	
	/**
	 * 微信提现接口
	 *
	 * @param id 提现申请表id，注意：已经处理的申请切勿重复提交
	 */
	public function wc_deposit_weixin() {
		$TixianQueue = M('TixianQueue');
		$WithdrawCash = M('WithdrawCash');
		
		$ids = I('post.id');
	
		$ids = is_array($ids) ? $ids : array($ids);
		foreach ($ids as $id) {
			if (empty($id)) {
				continue;
			}
			
			//过滤数据：该条提现信息status必须等于0才能进行提现操作
			$filter_withdraw_cash['id'] = array('eq', $id);
			$filter_withdraw_cash['status'] = array('neq', 0);
			$filter_withdraw_cash_info = $WithdrawCash->where($filter_withdraw_cash)->field('id')->find();
			if ($filter_withdraw_cash_info) {
				continue;
			}
			
			$data['wcid'] = $id;
			$data['type'] = 1;
			$map['wcid'] = array('eq', $id);
			$count = $TixianQueue->where($map)->count();
			if ($count==0) {
				if ($TixianQueue->create($data, '', true)) {
					$TixianQueue->add();
				}
			}
			
			$map_withdraw['id'] = array('eq', $id);
			$data_withdraw['submit_flag'] = time();
			$WithdrawCash->where($map_withdraw)->save($data_withdraw);
		}
		
		exit;
	}
	
	/**
	 * 微信支付回调验参
	 */
	public function wxSign() {
		//获取通知的数据
		$xml = $GLOBALS['HTTP_RAW_POST_DATA'];
	
		//如果返回成功则验证签名
		try {
			$result = \WxPayResults::Init($xml);
			if ($result) {
				//对总支付金额回归到分(微信统一下单时只能整数,故*100,此处需/100)
				$total_fee = sprintf('%.2f',$result['total_fee']/100);
				$cash_fee = sprintf('%.2f',$result['cash_fee']/100);
				$result['total_fee'] = (String)$total_fee;
				$result['cash_fee'] = (String)$cash_fee;
				return $result;
			} else {
				return false;
			}
		} catch (WxPayException $e){
			return false;
		}
	}
	
	/**
	 * 支付宝回调验参
	 */
	public function sign($respone) {
		$sign = $respone['sign'];
		unset($respone['sign']);
		unset($respone['sign_type']);
		ksort($respone);
		$link = $this->createVerifyLinkstring($respone);
		$verifyRes = $this->rsaVerify($link, $sign); //验证支付宝签名
		return $verifyRes;
	}
	
	public function createVerifyLinkstring($para) {
		$arg = "";
		while (list($key, $val) = each($para)) {
			$arg .= $key . '=' . $val . '&';
		}
		$arg = substr($arg, 0, count($arg) - 2);
		return $arg;
	}
	
	public function rsaVerify($data, $sign) {
		$pubKey = file_get_contents(dirname(__FILE__).'/key/alipay_public_key.pem');
		$res = openssl_get_publickey($pubKey);
		$result = (bool) openssl_verify($data, base64_decode($sign), $res);
		openssl_free_key($res);
		return $result;
	}
	
	/**
	 * 为特定人员提供的格式化函数
	 */
	public function format_return($msg="返回成功", $msg_code=400, $data) {
		$result = array();
		$result['code'] = $msg_code;
		$result['msg'] = $msg;
		$foo['productDetails'] = $data;
		$result['result'] = $foo;
		$this->ajaxReturn($result);
	}
	
	/**
	 * 处理订单申请表id
	 *
	 * 返回作为参数提交的字符串，此处不做重复提交检查，传入的id应全部标识为未处理过的记录
	 */
	public function format_wc_id($ids) {
		$withdraw_cash = M('withdraw_cash');
		$list = array();
		
		if (is_array($ids)) {
			$total_amount = 0;
			foreach ($ids as $v) {
				$temp_arr = array_values($withdraw_cash->where(array('id' => $v))->getField('serial_num, receiver_acount, receiver, amount, content'));
				$list[] = implode('^', $temp_arr[0]);
				$total_amount += floatval($temp_arr[0]['amount']);
			}
			return array('total_amount'=> $total_amount, 'total_num'=>count($ids), 'str'=> implode('|', $list));
		}
		else {
			$temp_arr = array_values($withdraw_cash->where(array('id' => $ids))->getField('serial_num, receiver_acount, receiver, amount, content'));
			return array('total_amount'=> $temp_arr[0]['amount'], 'total_num'=>1, 'str'=> implode('^', $temp_arr[0]));
		}
	}
	
	/**
	 * 银行家舍入
	 */
	public function round_banker($num, $precision) {
		$pow = pow(10, $precision);
		
		//舍去位为5 && 舍去位后无数字 && 舍去位前一位是偶数 。不进一
		if ((floor($num * $pow * 10) % 5 == 0) && (floor($num * $pow * 10) == $num * $pow * 10) && (floor($num * $pow) % 2 == 0)) {
			return floor($num * $pow) / $pow;
		} else {
			//四舍五入
			return round($num, $precision);
		}
	}
	
	
	/**
	 * 根据订单金额获取商家赠送商超券
	 * Enter description here ...
	 */
	public function getintegral(){
		$store_id = I('post.storeid');  //店铺id
		$money = I('post.money'); //兑换金额
		$pay_type = I('post.pay_type'); //货币类型1支付宝,2微信,3其他,4现金积分,5公让宝,6商超券
		$dutypay = I('post.dutypay');   //责任消费
		
		$uid = intval(I('post.uid'));
		if($uid == 0){
			$uid = $this->app_common_data['uid'];
		}
		
		$msg['store_points_warning'] = '';
		
		if($pay_type == 5 || $pay_type == 6){
			$this->myApiPrint('查询成功', 400, $msg);exit;
		}
		if($dutypay == 1){
			$this->myApiPrint('查询成功', 400, $msg);exit;
		}
		
		//查询店铺
		$storeinfo = verify_order_maidan($store_id);
		$store = $storeinfo['store'];
		$discount = $storeinfo['pw'];
		if($store && $discount){
			$rm = new RewardModel();
			
		    //1.计算利润
            $profitsData = $rm->getProfitsByOrder($money, $discount['reward']);
        
    		//应该赠送的商超券
    		$parameter_info = M('parameter', 'g_')->where('id=1')->field('points_merchant,points_member')->find();
            $colorCoin = $profitsData['profits'] * $parameter_info['points_member'];
            
            //step3、获取商家赠送限额配置
            $merchant_give_points_total = $store['give_points_total'] + $colorCoin; //（今日/本周对应商家已赠送总商超券）+（此次兑换该赠送给买家的商超券）
            $d_tag = 'points_merchant_max_day_' . $store['store_type'];
            $w_tag = 'points_merchant_max_week_' . $store['store_type'];
            $expr_point = C('PARAMETER_CONFIG.MERCHANT')[$d_tag] == 0 ? C('PARAMETER_CONFIG.MERCHANT')[$w_tag] : C('PARAMETER_CONFIG.MERCHANT')[$d_tag];  //商家的今日/本周可赠商超券上限
            //step4、根据限额配置计算最终赠送额度
            if ($merchant_give_points_total > $expr_point && $expr_point > 0) {
                $colorCoin = $expr_point - $store['give_points_total'];
                if($colorCoin < 0){
                    $colorCoin = 0;
                }
                $msg['store_points_warning'] = '此店铺本周剩余赠送商超券额度为：'.$colorCoin.'，支付成功后将赠送'.$colorCoin.'元商超券';
            }
			
		}else{
			$this->myApiPrint('参数错误', 400, $msg);exit;
		}
		$this->myApiPrint('查询成功', 400, $msg);exit;
		
	}
	
	/*
	public function getintegral(){
		$store_id = I('post.storeid');  //店铺id
		$money = I('post.money'); //兑换金额
		$pay_type = I('post.pay_type'); //货币类型1支付宝,2微信,3其他,4现金积分,5公让宝,6商超券
		$dutypay = I('post.dutypay');   //责任消费
		
		$uid = intval(I('post.uid'));
		if($uid == 0){
			$uid = $this->app_common_data['uid'];
		}
		
		$msg['store_points_warning'] = '';
		
		if($pay_type == 5 || $pay_type == 6){
			$this->myApiPrint('查询成功', 400, $msg);exit;
		}
		if($dutypay == 1){
			$this->myApiPrint('查询成功', 400, $msg);exit;
		}
		
		//查询店铺
		$storeinfo = verify_order_maidan($store_id);
		$store = $storeinfo['store'];
		$discount = $storeinfo['pw'];
		if($store && $discount){
			//计算积分
			$points = $this->getPointsToMM($money * $discount['reward'] / $discount['conditions']);
			
			$d_tag = 'points_merchant_max_day_'.$store['store_type'];
			$w_tag = 'points_merchant_max_week_'.$store['store_type'];
			$expr_point = C('PARAMETER_CONFIG.MERCHANT')[$d_tag]==0?C('PARAMETER_CONFIG.MERCHANT')[$w_tag]:C('PARAMETER_CONFIG.MERCHANT')[$d_tag];  //商家的今日/本周可赠积分上限
			$rest = $expr_point - $store['give_points_total'];
			$rest = sprintf('%.2f', $rest);
			$sjsyjf = $rest; //商家剩余积分
			//商家剩余积分 小于 此次兑换将赠送给买家的积分时
			if($rest < $points['member'] && $expr_point>0){
				if($rest < 0){
					$rest = 0;
				}
				$msg['store_points_warning'] = '此店铺本周剩余赠送额度为'.$rest.'积分，支付成功后将赠送'.$rest.'积分';
			}else{
				$rest = $points['member'];
			}
			//封顶
			$param = M('g_parameter', null)->find(1);
			//查询买家的丰收点
			$uw['id'] = $uid;
			$user = M('member')->where($uw)->find();
			if($user){
			    //获取积分丰收点余额
			    $am = new AccountModel();
			    $balance = $am->getAllBalance($uid); 
			    $user['bonus'] = $balance[$am->getBalanceField(Currency::Bonus)];
			    $user['points'] = $balance[$am->getBalanceField(Currency::Points)];
				//1.账号丰收点 > 封顶值
				if($user['bonus'] >= $param['member_bonus_max']){
					$msg['store_points_warning'] = '您的丰收点已到系统封顶值，本次消费不再赠送积分！';
				}
				
				$benci_bonus = floor(($rest+$user['points']) / $param['points_to_bonus']);
				//2.账号丰收点+本次兑换后 > 封顶 &&  < 封顶+浮动
				//if($user['bonus'] < $param['member_bonus_max'] && ($benci_bonus+$user['bonus']) > $param['member_bonus_max'] && ($benci_bonus+$user['bonus']) < ($param['member_bonus_max']+$param['member_bonus_float'])){
					//$rest = $benci_bonus*$param['points_to_bonus'];
					//$msg['store_points_warning'] = '本次消费后您的丰收点已到系统封顶值，本次消费只能赠送'.$rest.'丰收积分！';
				//}
				//3.账号丰收点+本次兑换后 > 封顶+浮动
				if($user['bonus'] < $param['member_bonus_max'] && ($benci_bonus+$user['bonus']) >= ($param['member_bonus_max']+$param['member_bonus_float'])){
					$rest = ($param['member_bonus_max']+$param['member_bonus_float']-$user['bonus'])*$param['points_to_bonus']-$user['points'];
					if($sjsyjf > $rest){
						$msg['store_points_warning'] = '本次消费后您的丰收点已到系统封顶值，本次消费只能赠送'.$rest.'积分！';
					}else{
						$msg['store_points_warning'] = '本次消费后您的丰收点已到系统封顶值，本次消费只能赠送'.$sjsyjf.'积分！';
					}
					if($rest < 1){
						$msg['store_points_warning'] = '您的丰收点已到系统封顶值，本次消费不再赠送积分！';
					}
				}
				
			}
		}else{
			$this->myApiPrint('参数错误', 400, $msg);exit;
		}
		$this->myApiPrint('查询成功', 400, $msg);exit;
		
	}*/
	
	
	/**
	 * 买单输入金额计算公让宝
	 */
	public function getGoldcoin(){
		$user_id = intval(I('post.user_id'));
		$storeid = I('post.storeid');
		$amount = I('post.amount');
		if (!validateExtend($amount, 'MONEY')) {
			$this->myApiPrint('金额格式不对！');
		}
		if($amount < 0.01){
			$this->myApiPrint('金额格式不对');
		}
		//3.验证店铺活动
		$pw_result = M('preferential_way')->where(array('store_id' => $storeid, 'status'=>0, 'manage_status'=>1))->find();
		if (!$pw_result) {
			$this->myApiPrint('店铺未发布活动',300);
		}
		//计算现金积分+公让宝比例
		$rm = new RewardModel();
		$profitsData = $rm->getProfitsByOrder($amount, $pw_result['reward']);
		//获取余额
		$am = new AccountModel();
		$balance = $am->getAllBalance($user_id);
		
		$return = array();
		if($balance['account_goldcoin_balance'] < $profitsData['profits']){
			$return['goldcoin'] = sprintf('%.2f', $balance['account_goldcoin_balance']);
			$return['cash'] = sprintf('%.2f', $amount - $return['goldcoin']);
		}else{
			$return['goldcoin'] = floor($profitsData['profits']*100)/100;
			$return['cash'] = ($amount - $return['goldcoin']).'';
		}
		$this->myApiPrint('计算成功', 400 , $return);
	}
	
}
?>