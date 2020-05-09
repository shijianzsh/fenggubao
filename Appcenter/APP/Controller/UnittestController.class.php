<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | API接口测试
// 公共头  key=app_common_data value={"platform":"android","api_token":"8f8683f36c70815819137af3bce93225","sessionid":"sdfsfdf","version":"5.3","registration_id":"sdfewfewf"}
// +----------------------------------------------------------------------
namespace APP\Controller;

use Common\Controller\BaseController;
use Common\Controller\ApiController;
use Common\Controller\PushController;

use V4\Model\Currency;
use V4\Model\AccountRecordModel;
use V4\Model\CurrencyAction;
use V4\Model\MemberModel;
use V4\Model\AccountModel;
use V4\Model\WalletModel;
use V4\Model\EnjoyModel;
use V4\Model\OrderModel;
use V4\Model\PaymentMethod;
use V4\Model\MiningModel;

class UnittestController extends BaseController {

	public function __construct( $request = '' ) {
		$this->myApiPrint( 'close', 400 ); die;
		parent::__construct( $request );
	}

	public function index() {
		$this->myApiPrint( 'close', 400 ); die;
		echo $dd['ss'];
	}


	public function notify() {
		$this->myApiPrint( 'close', 400 ); die;
		$aop                     = new \AopClient();
		$aop->alipayrsaPublicKey = '请填写支付宝公钥，一行字符串';
		$flag                    = $aop->rsaCheckV1( $_POST, null, "RSA2" );
	}


	public function cz() {
		$this->myApiPrint( 'close', 400 ); die;
		$user_id = I( 'post.user_id' );
		$arm     = new AccountRecordModel();
		$arm->add( $user_id, Currency::Cash, CurrencyAction::CashChongzhi, 50000, $arm->getRecordAttach( 1, '平台' ), '测试vip' );
		$arm->add( $user_id, Currency::GoldCoin, CurrencyAction::GoldCoinByChongzhi, 50, $arm->getRecordAttach( 1, '平台' ), '测试vip' );
		$this->myApiPrint( 'ok', 400 );
	}


	public function ye() {
		$this->myApiPrint( 'close', 400 ); die;
		$user_id = I( 'post.user_id' );
		$am      = new AccountModel();
		$res     = $am->getAllBalance( $user_id );
		$this->myApiPrint( '', 400, $res );
	}


	private function get_rand( $proArr ) {
		$this->myApiPrint( 'close', 400 ); die;
		$result = '';
		$proSum = array_sum( $proArr );
		foreach ( $proArr as $key => $proCur ) {
			$randNum = mt_rand( 1, $proSum );
			if ( $randNum <= $proCur ) {
				$result = $key;
				break;
			} else {
				$proSum -= $proCur;
			}
		}

		unset( $proArr );

		return $result;
	}


	/**
	 * 测试新阿里云短信接口
	 */
	public function smsNew() {
		$this->myApiPrint( 'close', 400 ); die;
		Vendor( "Aliyun.init" );

		$accessKeyId  = "LTAIqYvLX3TjYnLZ";
		$accessSecret = "aAIcZQy165oRtP6Xv2UDdOUGw8HHFk";

		$data            = array();
		$data['code']    = '332456';
		$data['product'] = C( 'APP_TITLE' );

		$Sms    = new \AliyunSms( $accessKeyId, $accessSecret );
		$status = $Sms->sendSms( C( 'APP_TITLE' ), 'SMS_105715058', '17003714004', $data );

		var_dump( $status );
	}

	/**
	 * 测试支付宝回调
	 */
	public function AliPayNotify() {
		$this->myApiPrint( 'close', 400 ); die;
		Vendor( "AliPay.AliPay#Api" );
		$AliPay = new \AliPay();

		$a = "gmt_create=2018-01-12+14%3A20%3A42&charset=utf-8&seller_email=gmcaln9180%40sandbox.com&subject=%E4%BC%97%E5%BD%A9%E7%94%9F%E6%B4%BB&sign=xFXU0CcVEI7JAiJ7WxevZ0uM%2F8f9D18ZHW1cJH8cQYXTsJFfi%2BzdUYSaGXo1LGB3Hsa7mzmV0cHswT%2FEkp083VeI09qGePNPXS5mfSADJdbCHf26Ds2sVIAcQwtChuRMI%2FZXIrblgjtm2qPi0Y3Jrw3g%2B2vcHsHMpetYiKmmLNA%2Fhdkj%2B7NGiYkEs6fZ0V3vZMGrRJcnawtoxt13%2FOVx87cBLuW1aIAn6g7Y%2B2WDDBYXDnEMv3HOADs8Zz3fyvgsEEjZeOcIaLOQlAZDJ3mJxrJF55SP1ekcHklnULP1TzSpBuD%2FRP92QMN%2B0HF6yyjYh7QVGqnSlYE1Hy4D5NbmNA%3D%3D&body=%E4%BC%97%E5%BD%A9%E7%94%9F%E6%B4%BB&buyer_id=2088102175385420&invoice_amount=0.01&notify_id=be41f5ed5dc24d16c6705e40e75f774j8q&fund_bill_list=%5b%7b%22amount%22%3a%220.01%22%2c%22fundChannel%22%3a%22ALIPAYACCOUNT%22%7d%5d&notify_type=trade_status_sync&trade_status=TRADE_SUCCESS&receipt_amount=0.01&app_id=2016091200493576&buyer_pay_amount=0.01&sign_type=RSA2&seller_id=2088102175286202&gmt_payment=2018-01-12+14%3A20%3A42&notify_time=2018-01-12+14%3A20%3A43&version=1.0&out_trade_no=OTHER2112380302360855&total_amount=0.01&trade_no=2018011221001004420200497138&auth_app_id=2016091200493576&buyer_logon_id=tfu%2A%2A%2A%40sandbox.com&point_amount=0.00";
		parse_str( $a, $b );

		$status = $AliPay->notifyRsaCheck( $b );
		if ( $status ) {
			echo 'yes';
		} else {
			echo 'no';
		}
	}


	public function wxlogin() {
		$this->myApiPrint( 'close', 400 ); die;
		$scope = I( 'get.scope' );
		$url   = 'https://api.weixin.qq.com/sns/jscode2session?appid=wx961a208b79528341&secret=59fbddf2577057e30f6632ab33e7b797&js_code=' . $scope . '&grant_type=authorization_code';
		$ch    = curl_init( $url );
		curl_setopt( $ch, CURLOPT_ENCODING, 'UTF-8' );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 10 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true ); // 获取数据返回
		//使用https协议
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 1 );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );

		$result = curl_exec( $ch );    //json 字符串
		curl_close( $ch );
		$msg = json_decode( $result );   //将消息专为obj
		$this->myApiPrint( 'ok', 400, $msg );
	}
	
	/**
	 * 省市区补全
	 */
	public function provinceSeo() {
		$this->myApiPrint( 'close', 400 ); die;
		
		$province = M('Province')->order('pid asc')->select();
		
		foreach ($province as $k=>$v) {
			M()->startTrans();
			
			if (preg_match('/市/', $v['province'])) {
				continue;
			}
			
			//内蒙古添加鄂尔多斯市
			if ($v['province'] == '内蒙古') {
				$max_cid = M('City')->order('cid desc')->getField('cid');
				$max_did = M('District')->order('did desc')->getField('did');
				
				M()->execute("INSERT INTO zc_city values (".($max_cid+1).",".$v['pid'].",'鄂尔多斯市')");
				$cid = M('City')->order('cid desc')->getField('cid');
				
				M()->execute("INSERT INTO zc_district values (".($max_did+1).",".$cid.",'东胜区')");
				M()->execute("INSERT INTO zc_district values (".($max_did+2).",".$cid.",'康巴什区')");
				M()->execute("INSERT INTO zc_district values (".($max_did+3).",".$cid.",'达拉特旗')");
				M()->execute("INSERT INTO zc_district values (".($max_did+4).",".$cid.",'准格尔旗')");
				M()->execute("INSERT INTO zc_district values (".($max_did+5).",".$cid.",'鄂托克前旗')");
				M()->execute("INSERT INTO zc_district values (".($max_did+6).",".$cid.",'鄂托克旗')");
				M()->execute("INSERT INTO zc_district values (".($max_did+7).",".$cid.",'杭锦旗')");
				M()->execute("INSERT INTO zc_district values (".($max_did+8).",".$cid.",'乌审旗')");
				M()->execute("INSERT INTO zc_district values (".($max_did+9).",".$cid.",'伊金霍洛旗')");
				M()->execute("INSERT INTO zc_district values (".($max_did+10).",".$cid.",'其他')");
			}
			
			$other_city_exists = M('City')->where("pid=".$v['pid']." and city like '%其他%'")->find();
			if (!$other_city_exists) {
				$max_cid = M('City')->order('cid desc')->getField('cid');
				$max_did = M('District')->order('did desc')->getField('did');

				M()->execute("INSERT INTO zc_city values (".($max_cid+1).",".$v['pid'].",'其他')");
				$cid = M('City')->order('cid desc')->getField('cid');

				M()->execute("INSERT INTO zc_district values (".($max_did+1).",".$cid.",'其他')");
			}
			
			$city = M('City')->where('pid='.$v['pid'])->select();
			
			foreach ($city as $k1=>$v1) {
				$other_district_exists = M('District')->where("cid=".$v1['cid']." and district like '%其他%'")->find();
				if (!$other_district_exists) {
					$max_did = M('District')->order('did desc')->getField('did');
					
					M()->execute("INSERT INTO zc_district values (".($max_did+1).",".$v1['cid'].",'其他')");
				}
			}
			
			M()->commit();
		}
	}

	/**
	 * 省市区JSON
	 */
	public function provinceCityCountry() {
		$this->myApiPrint( 'close', 400 ); die;
		
		$data = [];
		
		$province = M('Province')->order('pid asc')->select();
		
		foreach ($province as $k=>$v) {
			$data[$v['pid']]['name'] = $v['province'];
			
			$city = M('City')->where('pid='.$v['pid'])->order('cid asc')->select();
			
			foreach ($city as $k1=>$v1) {
				$country = M('District')->where('cid='.$v1['cid'])->order('did asc')->getField('district', true);
				
				$city_info['name'] = $v1['city'];
				$city_info['area'] = $country;
				
				$data[$v['pid']]['city'][] = $city_info;
			}
		}
		
		$data = array_values($data);
		
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
	}
	
	/**
	 * 生成钱包地址
	 */
	public function getNewWalletAddress() {
// 		$this->myApiPrint( 'close', 400 ); die;
		
		$WalletModel = new WalletModel('SLU');
		
// 		$a = $WalletModel->getNewAddress(); // [√]
// 		$a = $WalletModel->getBalance(); // [√]
// 		$a = $WalletModel->sendToUserAddress('SLUcBZMTfpQfbiua39pEnGhcW7f7ojhSjBdg', 0.01);
// 		$a = $WalletModel->getAddressBalance('SLUYbxCypoizB1QT6Kfz9ucnEJpLekPTTsb9');

// 		$a = $WalletModel->sendToUserAddressBySLU('SLUSsA3XzjihfvUDY32cebv3aECg2Bwafzkh', 0.02);
// 		$a = $WalletModel->getAddressBalance('SLUSsA3XzjihfvUDY32cebv3aECg2Bwafzkh');

// 		$a = $WalletModel->sendToUserAddressByAddress('SLUYbxCypoizB1QT6Kfz9ucnEJpLekPTTsb9', 0.1, 'SLUSsA3XzjihfvUDY32cebv3aECg2Bwafzkh');
		
		echo $a;
		exit;
	}
	
	/**
	 * 指定订单补赠澳洲SKN股份
	 */
	public function EnjoyGiveByOrder() {
		$this->myApiPrint( 'close', 400 ); die;
		
		$map = [
			'o.producttype' => ['eq', 4],
			'o.order_status' => ['eq', 1]
		];
		$map['_string'] = " FROM_UNIXTIME(o.time,'%Y%m%d')=FROM_UNIXTIME(unix_timestamp(),'%Y%m%d') ";
		
		$list = M('Orders')
			->alias('o')
			->join("left join zc_account_enjoy_201906 a ON a.user_id=o.uid and a.record_action=803 and FROM_UNIXTIME(a.record_addtime,'%Y%m%d')=FROM_UNIXTIME(unix_timestamp(),'%Y%m%d')")
			->where($map)
			->field('o.id,a.record_id')
			->having('record_id is null')
			->select();
		
		$EnjoyModel = new EnjoyModel();
		
		foreach ($list as $k=>$v) {
			$EnjoyModel->consumeGive($v['id']);
		}
		
		exit('success');
	}
	
	/**
	 * 检测用户中网云钱包地址是否为测试钱包地址,并修复重新生成新的正式钱包地址
	 * 
     * (供比对的测试钱包地址数据在zc_zwy_wallet_address_by_debug数据表中)
	 */
	public function repairZwyWalletAddress() {
		$this->myApiPrint( 'close', 400 ); die;
		
		set_time_limit(0);
		ignore_user_abort(true);
		
		$WalletModel = new WalletModel();
		
		$debug_exist_in_release = M('zwy_wallet_address_by_debug')
			->alias('deb')
			->join('join zc_user_affiliate aff on aff.zhongwy_wallet_address=deb.zhongwy_wallet_address_debug')
			->field('aff.user_id,deb.zhongwy_wallet_address_debug')
			->select();
		
		$need_repair_count = count($debug_exist_in_release);
		$success_repair_count = 0;
		
		//修复
		foreach ($debug_exist_in_release as $k=>$v) {
			M()->startTrans();
			
			$user_id = $v['user_id'];
			$zhongwy_wallet_address_debug = $v['zhongwy_wallet_address_debug'];

			$new_address = $WalletModel->getNewAddress($user_id);
			
			$result1 = M('zwy_wallet_address_by_debug')->where("zhongwy_wallet_address_debug='{$zhongwy_wallet_address_debug}'")->save(['zhongwy_wallet_address_release'=>$new_address]);
			$result2 = M('user_affiliate')->where('user_id='.$user_id)->save(['zhongwy_wallet_address'=>$new_address]);
			
			if ($result1 === false || $result2 === false) {
				M()->rollback();
				continue;
			}
			
			M()->commit();
			
			$success_repair_count++;
		}
		
		exit("共需修复{$need_repair_count}条数据，修复完成{$success_repair_count}条数据");
	}
	
	/**
	 * 统计修复SLU转账BUG导致转账金额不正确问题
	 */
	public function sluTransferDebugRepair() {
		//[查询]
// 		$map = [
// 			'type' => ['eq', 'SLU'],
// 			'status' => ['eq', 3],
// 			'is_queue' => ['eq', 0],
// 			'uptime' => [['egt', strtotime('2019-09-12 00:00:00')],['lt', strtotime('2019-09-13 00:00:00')], 'and']
// 		];
// 		$list = M('Trade')->where($map)->select();
// 		echo '<table border="1"><tr><td>ID</td><td>已转出金额(已8次方处理)</td><td>应转出金额(已8次方处理)</td><td>还需转出金额(已8次方处理)</td></tr>';
// 		foreach ($list as $k=>$v) {
// 			$amount = $v['amount'] - $v['fee'];
			
// 			//老方式计算已转出金额
// 			$amount_dec = dechex($amount * pow(10, 8));
// 			$amount_old = hexdec($amount_dec);
			
// 			//新方式计算需转出金额
// 			$amount_dec = base_convert($amount * pow(10, 8), 10, 16);
// 			$amount_new = hexdec($amount_dec);
			
// 			//计算差值
// 			$amount_append = $amount_new - $amount_old;
			
// 			echo <<<EOF
// 			<tr>
// 				<td>{$v['id']}</td>
// 				<td>{$amount_old}</td>
// 				<td>{$amount_new}</td>
// 				<td>{$amount_append}</td>
// 			</tr>
// EOF;
// 		}
// 		echo '</table>';

		//--------------------------------------------------
		//[补转]
		$TradeModel = M('Trade');

		M()->startTrans();
		
		$map = [
			'type' => ['eq', 'SLU'],
			'status' => ['eq', 3],
			'is_queue' => ['eq', 0],
			'uptime' => [['egt', strtotime('2019-09-12 00:00:00')],['lt', strtotime('2019-09-13 00:00:00')], 'and']
		];
		$data = $TradeModel->where($map)->order('id asc')->find();
		if (!$data) {
			exit;
		}
		 
		//处理类型
		$wallet_platform = $data['type'];
		
		//实例化模型
		$WalletModel = new WalletModel($wallet_platform);
		
		//最终转账金额
		$transfer_amount = $data['amount'] - $data['fee'];
		
		//老方式计算已转出金额
		$amount_dec = dechex($transfer_amount * pow(10, 8));
		$amount_old = hexdec($amount_dec);
			
		//新方式计算需转出金额
		$amount_dec = base_convert($transfer_amount * pow(10, 8), 10, 16);
		$amount_new = hexdec($amount_dec);
			
		//计算差值
		$amount_append = $amount_new - $amount_old;
		$amount_append = $amount_append * pow(0.1, 8);
		
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
		if ($amount_append >= $balance) {
			var_dump($data);
			echo '主钱包余额不足，终止转账，队列挂起';
			//自动退款
			//    		$data['remark'] = '自动退款:账户余额不足';
			//    		$this->tradeBackCore($data);
			exit;
		}
		
		if ($amount_append <= 0) {
			echo "操作失败！ID[{$data['id']}],转出地址[{$data['wallet_address']}],补转金额[{$amount_append}]";
		
			exit;
		}
		
		//转账操作
		$result1 = $WalletModel->sendToUserAddress($data['wallet_address'], $amount_append);
		if ($result1) {
			$data_trade = [
				'uptime' => time()
			];
			$result2 = M('Trade')->where('id='.$data['id'])->save($data_trade);
		
			if (!$result2) {
				M()->rollback();
			}
		
			M()->commit();
		}
		
		echo "操作成功！ID[{$data['id']}],转出地址[{$data['wallet_address']}],补转金额[{$amount_append}]";
		
		exit;
	}
	
	/**
	 * [1] 修复SLU平台转入已处理虚拟账号增加GRC但没有从对应钱包转出至平台钱包的数据  : 给用户批量充SLU
	 */
	public function SLUImportDataToSysAddressFirst() {
		$TransactionsModel = M('Transactions');
	
		$WalletModel = new WalletModel('SLU');
	
		$map = [
			'type' => ['eq', 'SLU'],
			'status' => ['eq', 1],
			'transfer_out_txid' => ['eq', ''],
			'created_time' => ['lt', strtotime(date('2019-09-19 11:00:00'))]
		];
		$data = $TransactionsModel->where($map)->group('address')->order('id asc')->limit(1)->select();

		if (!$data) {
			exit('无数据');
		}
		
		$fail_count = 0;
		$slu_recharge = 0;
		
		foreach ($data as $k=>$v) {
	
			//判断用户钱包地址GRC金额和SLU金额是否足够
			$balance = $WalletModel->getAddressBalance($v['address']);
			if ($balance['slu'] < 0.09) {
				$txid = $WalletModel->sendToUserAddressBySLU($v['address'], 0.1);
				if (empty($txid) || !$txid) {
					$fail_count++;
					continue;
				}
				$slu_recharge += 0.1;
			} else {
				$txid = 'without';
			}
		}
		
		echo "批量SLU充值操作成功！需执行个数[".count($data)."个],执行失败[{$fail_count}个],共充值[{$slu_recharge} SLU]";
		exit;
	}
	
	/**
	 * [2] 修复SLU平台转入已处理虚拟账号增加GRC但没有从对应钱包转出至平台钱包的数据 : 执行转出操作
	 */
	public function SLUImportDataToSysAddressSecond() {
		$TransactionsModel = M('Transactions');
		
		$WalletModel = new WalletModel('SLU');
		
		M()->startTrans();
		
		$map = [
			'type' => ['eq', 'SLU'],
			'status' => ['eq', 1],
			'transfer_out_txid' => ['eq', ''],
			'created_time' => ['lt', strtotime(date('2019-09-19 11:00:00'))]
		];
		$data = $TransactionsModel->where($map)->order('id asc')->find();
		if (!$data) {
			exit('无数据');
		}
		
		//判断用户钱包地址GRC金额和SLU金额是否足够
		$balance = $WalletModel->getAddressBalance($data['address']);
		if ($balance['grc'] < $data['amount']) {
			exit("GRC余额不足[余额:{$balance[grc]}][ID:{$data[id]}][ADDRESS:{$data[address]}]");
		}
		if ($balance['slu'] < 0.1) {
			exit('SLU余额不足或充值未到账');
		}
		
		//扣除
		$result = $WalletModel->sendToUserAddressByAddress(\SluConfig::IMPORT_RECEIVE_ADDRESS, $data['amount'], $data['address']);
		if (!$result) {
			exit("扣除失败[ID:{$data[id]}][ADDRESS:{$data[address]}]");
		}
		
		$TransactionsModel->where('id='.$data['id'])->save(['transfer_out_txid' => $result]);
		
		M()->commit();
		
		echo "扣除操作成功！ID[{$data['id']}],转出地址[{$data['address']}],接收地址[".\SluConfig::IMPORT_RECEIVE_ADDRESS."],补扣金额[{$data['amount']}]";
		exit;
	}
	
	/**
	 * 订单状态50:修复
	 */
	public function getOrderBug() {
		$om = new OrderModel();
		
		$order_trade_no_list = [
			'3920802659309412' => '4200000409201909204987956032',
			'3920803856217669' => '4200000402201909205586463556',
			'3920804106429679' => '4200000406201909203975546415',
			'3921399331208690' => '4200000421201909211585320568',
			'3921682482930483' => '4200000414201909219248103501',
			'3921786422207484' => '4200000421201909217541389160',
		];
		
		foreach ($order_trade_no_list as $order_number => $trade_no) {
			
			$orders = M('orders')->where('order_number=\'' . $order_number . '\'')->find();
			$user = M('member')->find($orders['uid']);
			$affiliate_pay = M('OrderAffiliate')->where('order_id='.$orders['id'])->getField('affiliate_pay');
			
			if ($orders['order_status'] != '50') {
				continue;
			}
			
			$out_trade_no = $order_number;
			$trade_no = $trade_no;
			$trade_status = 'SUCCESS';
			$total_amount = '';
			$receipt_amount = $affiliate_pay;
			$gmt_payment = date('Y-m-d H:i:s');
			$payway = 'wechat';
			
			M()->startTrans();
			
			$res1 = $om->updateOrder($out_trade_no, 1);
			
			$res2 = $om->updateOrderpayinfo($out_trade_no, $trade_no, $trade_status, $total_amount, $receipt_amount, $gmt_payment);
			
			$res3 = $om->shoppingpay($user, $orders, $payway);
			
			if ($res1 !== false && $res2 !== false && $res3 !== false) {
				M()->commit();
				
				echo '['.$order_number.'][success]';
			} else {
				M()->rollback();
				
				echo '['.$order_number.'][fail]'.$res1.':'.$res2.':'.$res3;
			}
			
		}
	}
	
	/**
	 * 导出用户数据
	 */
	public function exportUserData() {
		$MiningModel = new MiningModel();
		
		$data = M('Member')
			->alias('m')
			->join('left join __CERTIFICATION__ c ON c.user_id=m.id and c.certification_status=2')
			->join('left join __ADDRESS__ a ON a.uid=m.id and a.is_default=1')
			->join('left join __USER_AFFILIATE__ u ON u.user_id=m.id')
			->join('left join __BANK_BIND__ b ON b.user_id=m.id')
			->join('left join __ACCOUNT__ acc ON acc.user_id=m.id and acc.account_tag=0')
			->join('left join __GJJ_ROLES__ gr ON gr.user_id=m.id and gr.audit_status=1')
			->join('left join __CONSUME__ con ON con.user_id=m.id')
			->field('
					m.*,
					c.certification_id,c.certification_identify_1,c.certification_identify_2,c.certification_identify_3,
					a.province,a.city,a.country,a.address,
					u.alipay_account,
					b.cardNo,
					acc.account_goldcoin_balance,acc.account_cash_balance,
					gr.id gr_id, gr.role gr_role,
					con.level role_star
					')
			->order('m.id asc')
			->limit(15000,1000)
			->select();
		
		foreach ($data as $k=>$v) {
			//农场数
			$portion_info = $MiningModel->getPortionNumber($v['id'], true);
			$portion = $portion_info['enabled'];
			
			$data_xls = [
				$v['id'],
				$v['loginname'],
				$v['reid'],
				$v['loginname'],
				$v['username'],
				$v['img'],
				$v['password'],
				$v['safe_password'],
				$v['level'],
				$v['reg_time'],
				$v['id_card'],
				$v['certification_identify_1'],
				$v['certification_identify_3'],
				$v['certification_identify_2'],
				$v['address'],
				$v['province'].','.$v['city'].','.$v['country'],
				empty($v['certification_id']) ? '0' : '1',
				$v['weixin'],
				$v['alipay_account'],
				$v['cardNo'],
				$v['account_goldcoin_balance'],
				$v['account_goldcoin_balance'],
				$v['account_cash_balance'],
				$portion,
				'',
				empty($v['gr_role']) ? '0' : $v['gr_role'],
				empty($v['gr_role']) ? '0' : $v['role_star'],
				''
			];
			
			$data[$k] = $data_xls;
		}
		
		$head_array = array( '用户ID', '账号', '推荐人ID', '手机号', '昵称', '头像', '登录密码', '支付密码', '用户等级', '注册时间', '身份证号', '身份证正面', '手持身份证照片', '身份证反面', '用户地址', '所属地区', '是否已认证(0:未认证,1:已认证)', '微信(序列化数据)', '支付宝', '银行卡号', 'grb', 'grc', '现金币余额', '有效农场数量', 'QQ', '谷聚金代理等级', '谷聚金代理星级', '出生年月' );
		$file_name  .= '用户数据-' . date( 'Y-m-d' );
		$file_name = iconv( "utf-8", "gbk", $file_name );
		$return    = $this->xlsExport( $file_name, $head_array, $data );
		
		if (!empty($return['error'])) {
			echo $return['error'];
		}
	}
	
	
	
	
	/**
	 * xls电子表格导出功能封装
	 * @param string $file_name 文件名
	 * @param array $head_array 列名数组
	 * @param array $data 待导出数据
	 */
	protected function xlsExport($file_name, $head_array, $data) {
		$return = array('data'=>'', 'error'=>'', 'info'=>'');
	
		empty($file_name) && $return['error']='导出文件名不能为空';
		!is_array($head_array) && $return['error']='列名不能为空';
		!is_array($data) && $return['error']='导出数据不能为空';
	
		if (!empty($return['error'])) {
			return $return;
		}
	
		Vendor('PhpExcel.PHPExcel.IOFactory');
		$writer = new \PHPExcel();
	
		//设置列名
		$column_i = 'A';
		foreach ($head_array as $head) {
			$column = $column_i.'1';
			$writer->getActiveSheet()->setCellValue($column, $head);
			$column_i++;
		}
	
		//嵌入数据
		foreach ($data as $k=>$list) {
			$column_i = 'A';
			foreach ($list as $k1=>$list1) {
				$column = $column_i.intval($k+2);
				$writer->getActiveSheet()->setCellValue($column, ' '.$list1); //加空格避免数字自动转为科学表达式
				$column_i++;
			}
		}
	
		//设置配置信息
		ob_end_clean(); //清除缓冲区，避免乱码
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");
		header("Content-Disposition:inline;filename={$file_name}.xls");
		header("Content-Transfer-Encoding: binary");
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified:".date("D, d M Y H:i:s")." GMT");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Pragma: no-cache");
		$temp_file = '.'. C('UPLOAD_PATH'). '/xls/'. getMd5($file_name). '.xls';
		createDir($temp_file);
		$xls_writer = \PHPExcel_IOFactory::createWriter($writer, 'Excel5');
		$xls_writer->save($temp_file);
		echo file_get_contents($temp_file);
		unlink($temp_file);
	}

}

?>