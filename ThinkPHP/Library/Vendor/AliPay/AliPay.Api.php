<?php
include_once dirname(__FILE__).'/AopSdk.php';
include_once dirname(__FILE__).'/AliPay.Config.php';

/**
 * 支付宝支付
 *
 */
class AliPay {
	
	protected $PayObject; //支付对象
	protected $data; //订单相关数据
	
	/**
	 * 初始化构造方法
	 * 
	 * @param array $data 订单数组数据，格式
	 * {
	 *    "body":"我是测试数据"
	 *    "subject": "App支付测试"
	 *    "out_trade_no": "20170125test01"
	 *    "timeout_express": "30m"
	 *    "total_amount": "0.01"
     *    "product_code":"QUICK_MSECURITY_PAY"
	 * }
	 */
	public function __construct($data='') {
		$this->setData($data);
		
		//支付配置相关
		$this->PayObject = new AopClient();
		$this->PayObject->gatewayUrl = AliPayConfig::gateWay;
		$this->PayObject->appId = AliPayConfig::AppId;
		$this->PayObject->rsaPrivateKey = file_get_contents(dirname(__FILE__).'/cert/'.AliPayConfig::RsaPrivateKey_FileName);
		$this->PayObject->alipayrsaPublicKey = file_get_contents(dirname(__FILE__).'/cert/'.AliPayConfig::RsaPublickKey_FileName);
		//$this->PayObject->apiVersion = AliPayConfig::ApiVersion;
		$this->PayObject->signType = AliPayConfig::SignType;
		$this->PayObject->postCharset = AliPayConfig::PostCharset;
		$this->PayObject->format = AliPayConfig::Format;
	}
	
	/**
	 * 设置data数据
	 */
	public function setData($data) {
		if (!empty($data)) {
			$this->data = json_encode($data, JSON_UNESCAPED_UNICODE);
		}
	}
	
	/**
	 * 预创建支付宝平台订单，返回签名字符串给APP
	 * @param  $notify_url
	 */
	public function createOrder($notify_url) {
		$request = new AlipayTradeAppPayRequest();
		$request->setNotifyUrl($notify_url);
		$request->setBizContent($this->data);
		$response = $this->PayObject->sdkExecute($request);
		return $response;
	}
	
	/**
	 * 回调验签
	 * @return boolean
	 */
	public function notifyRsaCheck(){
		$_POST['fund_bill_list'] = str_replace('&quot;', '"', $_POST['fund_bill_list']);
		return $this->PayObject->rsaCheckV1($_POST, NULL, AliPayConfig::SignType);
	}
	
	/**
	 * 单笔转账到支付宝账户
	 */
	public function tixian() {
		$request = new \AlipayFundTransToaccountTransferRequest();
		$request->setBizContent($this->data);
		$result = $this->PayObject->execute($request);
		
		return $result;
	}
	
	/**
	 * 查询转账订单
	 */
	public function orderQuery() {
		$request = new \AlipayFundTransOrderQueryRequest();
		$request->setBizContent($this->data);
		$result = $this->PayObject->execute($request);
		
		return $result;
	}
	
	
	/**
	 * 获取授权参数
	 */
	public function getAccountAuth($user_id){
		$param['apiname'] = 'com.alipay.account.auth';
		$param['method']  = 'alipay.open.auth.sdk.code.get';
		$param['app_id']  = AliPayConfig::AppId;
		$param['app_name'] = 'mc';
		$param['biz_type'] = 'openservice';
		$param['pid'] = AliPayConfig::PId;
		$param['product_id'] = 'APP_FAST_LOGIN';
		$param['scope'] = 'kuaijie';
		$param['target_id'] = md5($user_id.time());
		$param['auth_type'] = 'AUTHACCOUNT';
		$param['sign_type'] = AliPayConfig::SignType;
		$param['sign'] = $this->PayObject->generateSign($param, AliPayConfig::SignType);
		
		$infostr = $this->PayObject->getSignContent($param);
		return $infostr;
	}
	
	
	public function getAuthUserInfo($auth_code){
		//1.拿token
		$request = new AlipaySystemOauthTokenRequest();
		$request->setGrantType('authorization_code');
		$request->setCode($auth_code);
		$result1 = $this->PayObject->execute($request);
		$auth_token = $result1->alipay_system_oauth_token_response->access_token;
		
		//2.info
		$request = new AlipayUserInfoShareRequest();
		$result2 = $this->PayObject->execute($request,$auth_token);
		$info = (array)$result2->alipay_user_info_share_response;
		return $info;
		/*
		 *   [code] => 10000
		    [msg] => Success
		    [avatar] => https://tfs.alipayobjects.com/images/partner/TB1ZG4FXTpT81Jjme6tXXci2pXa
		    [city] => 成都市
		    [gender] => m
		    [is_certified] => T
		    [is_student_certified] => F
		    [province] => 四川省
		    [user_id] => 2088702993435457
		    [user_status] => T
		    [user_type] => 2
		 */
	}
	
}
