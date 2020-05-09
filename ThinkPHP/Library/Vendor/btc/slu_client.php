<?php
require_once "btc_config.php";

class Api_Rpc_Client_SLU {
	
	private $debug;
	private $url;
	private $id;
	private $notification = false;
	
	private static $user_wallet_address_all; //所有用户的GRC钱包地址

	/**
	 * 
	 * @param boolean $debug 是否开启调试模式(默认false)
	 */
	public function __construct($debug = false){
		$this->url = 'http://'.SluConfig::USERNAME.':'.SluConfig::PASSWORD.'@'.SluConfig::IP.':'.SluConfig::PORT;
		$this->debug = SluConfig::DEBUG;
		$this->id = 1;
	}

	public function setRPCNotification($notification){
		$this->notification = !empty($notification);
	}

	public function __call($method, $params){
		if(!is_scalar($method)){
			//throw new Exception('Method name has no scalar value');
			die('Method name has no scalar value');
		}
		if(is_array($params)){
			$params = array_values($params);
		}
		else{
			//throw new Exception('Params must be given as array');
			die('Params must be given as array');
		}
		if($this->notification){
			$currentId = null;
		}
		else{
			$currentId = $this->id;
		}
		
		$request = array('method' => $method, 'params' => $params, 'id' => $currentId);
		$request = json_encode($request);
		$this->debug && $this->debug .= '***** Request *****' . "\n" . $request . "\n" . '***** End Of request *****' . "\n\n";
		$opts = array('http' => array('method' => 'POST', 'header' => 'Content-type: application/json', 'content' => $request));
		$context = stream_context_create($opts);
		
		// --------------
		try {
			$fp = fopen($this->url, 'r', false, $context);
			$response = '';
			while($row = fgets($fp)){
				$response .= trim($row) . "\n";
			}
			$this->debug && $this->debug .= '***** Server response *****' . "\n" . $response . '***** End of server response *****' . "\n";
			$response = json_decode($response, true);
		} catch (Exception $e) {
			if (MODULE_NAME == 'APP') {
				ajax_return($e->getMessage(), 300);
			} else {
				die($e->getMessage());
			}
		}
		
		if($this->debug){
			echo nl2br($this->debug);
		}
		
		if(!$this->notification){
			if($response['id'] != $currentId){
				$msg = 'Incorrect response id (request id: ' . $currentId . ', response id: ' . $response['id'] . ')';
				if (MODULE_NAME == 'APP') {
					ajax_return($msg, 300);
				} else {
					die($msg);
				}
			}
			if(!is_null($response['error'])){
				$msg = 'Request error: ' . $response['error'];
				if (MODULE_NAME == 'APP') {
					ajax_return($msg, 300);
				} else {
					die($msg);
				}
			}
			return $response['result'];
		}
		else{
			return true;
		}
		
		// --------------
		
		/*
		if($fp = fopen($this->url, 'r', false, $context)){
			$response = '';
			while($row = fgets($fp)){
				$response .= trim($row) . "\n";
			}
			$this->debug && $this->debug .= '***** Server response *****' . "\n" . $response . '***** End of server response *****' . "\n";
			$response = json_decode($response, true);
		}
		else{
			//throw new Exception('钱包地址错误或官方钱包维护');
			$current_lang = getCurrentLang();
			
			if ($current_lang == 'en') {
				die('Wrong wallet address or official wallet maintenance');
			} elseif ($current_lang == 'ko') {
				die('지갑 주소 오류 또는 공식 지갑 관리');
			} else {
				die('钱包地址错误或官方钱包维护');
			}
		}
		
		
		if($this->debug){
			echo nl2br($this->debug);
		}
		if(!$this->notification){
			if($response['id'] != $currentId){
				//throw new Exception('Incorrect response id (request id: ' . $currentId . ', response id: ' . $response['id'] . ')');
				die('Incorrect response id (request id: ' . $currentId . ', response id: ' . $response['id'] . ')');
			}
			if(!is_null($response['error'])){
				//throw new Exception('Request error: ' . $response['error']);
				die('Request error: ' . $response['error']);
			}
			return $response['result'];
		}
		else{
			return true;
		}
		*/
	}

	static function getAddrByCache($pKey, $pCache = 0, $coin){
        if(empty($coin)){
            return FALSE;
        }
		$tRedis = &Cache_Redis::instance();
		if($pCache && $tAddr = $tRedis->hget($coin.'addr', $pKey)){
			return $tAddr;
		}
		if($pCache && $tAddr = $tRedis->hget($coin.'addrnew', $pKey)){
			return $tAddr;
		}
		if(1 == $pCache){
			return false;
        }
		$tARC = new Api_Rpc_Client_SLU(Yaf_Application::app()->getConfig()->api->rpcurl->$coin);
		$tAddr = $tARC->getnewaddress($pKey);
		$tRedis->hset($coin.'addrnew', $pKey, $tAddr);
		return $tAddr;
	}
    
	
	//获取余额 (获取GRC总钱包的SLU和GRC余额)
    static function getBalance(){
		$tARC = new Api_Rpc_Client_SLU();

		//获取GRC总钱包的SLU和GRC余额
		$balance_grc = $tARC->callcontract(SluConfig::CONTRACT, SluConfig::BALANCE_OF. str_pad($tARC->gethexaddress(SluConfig::GRC_ADDRESS),64,'0',STR_PAD_LEFT));
		$balance_grc = self::amountChangePoint(hexdec($balance_grc['executionResult']['output']));
		$balance_slu = $tARC->getwalletinfo();
		$balance_slu = $balance_slu['balance'];
		
		$balance = [
			'grc' => $balance_grc,
			'slu' => $balance_slu
		];
		
        return $balance;
    }
    
    //获取指定钱包地址的余额 (SLU和GRC余额)
    static function getAddressBalance($address){
    	$tARC = new Api_Rpc_Client_SLU();
    
    	//获取SLU和GRC余额 
    	$balance_grc = $tARC->callcontract(SluConfig::CONTRACT, SluConfig::BALANCE_OF. str_pad($tARC->gethexaddress($address),64,'0',STR_PAD_LEFT));
    	$balance_grc = self::amountChangePoint(hexdec($balance_grc['executionResult']['output']));
    	
    	//[API方法]
//     	$balance_slu = get_by_curl(SluConfig::API_HOST.'/addrs/balance/'.$address, 'get');
//     	$balance_slu = json_decode($balance_slu,true)[0]['balance'];
//     	$balance_slu = empty($balance_slu) ? 0 : $balance_slu;

    	//[直接调取方法]
		$balance_slu = $tARC->listunspent(1, 9999999, ["{$address}"]);
		$balance_slu_amount = 0;
		foreach ($balance_slu as $k=>$v) {
			$balance_slu_amount += $v['amount'];
		}
    
    	$balance = [
	    	'grc' => $balance_grc,
	    	'slu' => $balance_slu_amount
    	];
    
    	return $balance;
    }

    //直接转账给地址[GRC转账]:由总钱包地址转出
	static function sendToUserAddress($address, $amount){
		$tARC = new Api_Rpc_Client_SLU();
		
		//核验地址
		$validate_address = self::validateUserAddress($address);
		if (!$validate_address) {
			return false;
		}
		
		//16进制金额: [stop:精度放大8倍],并转为16进制,补齐64位
		$amount_dec = $amount * pow(10, 8);
		$amount_dec = base_convert($amount_dec, 10, 16);
		$amount_dec = str_pad($amount_dec, 64, '0', STR_PAD_LEFT);
		
		//钱包地址: 转换为64位
		$address = str_pad($tARC->gethexaddress($address),64,'0',STR_PAD_LEFT);
		
		$result = $tARC->sendtocontract(SluConfig::CONTRACT, SluConfig::TRANSFER_OF. $address. $amount_dec, 0, SluConfig::GAS_LIMIT, SluConfig::GAS_PRICE, SluConfig::GRC_ADDRESS);
		
		if (empty($result['txid'])) {
			return false;
		}
		
		return $result['txid'];
	}
	
	//直接转账给地址[GRC转账]:由指定钱包地址转至另一指定钱包地址
	static function sendToUserAddressByAddress($address, $amount, $senderaddress){
		$tARC = new Api_Rpc_Client_SLU();
		
		//核验地址
		$validate_address = self::validateUserAddress($address);
		if (!$validate_address) {
			return false;
		}
		
		//16进制金额: [stop:精度放大8倍],并转为16进制,补齐64位
		$amount_dec = $amount * pow(10, 8);
		$amount_dec = base_convert($amount_dec, 10, 16);
		$amount_dec = str_pad($amount_dec, 64, '0', STR_PAD_LEFT);
		
		//钱包地址: 转换为64位
		$address = str_pad($tARC->gethexaddress($address),64,'0',STR_PAD_LEFT);
		
		$result = $tARC->sendtocontract(SluConfig::CONTRACT, SluConfig::TRANSFER_OF. $address. $amount_dec, 0, SluConfig::GAS_LIMIT, SluConfig::GAS_PRICE, $senderaddress);
		
		if (empty($result['txid'])) {
			return false;
		}
		
		return $result['txid'];
	}
	
	//直接转账给地址[SLU转账]:由主钱包给指定钱包地址转SLU金额
	static function sendToUserAddressBySLU($address, $amount){
		$tARC = new Api_Rpc_Client_SLU();
		
		//核验地址
		$validate_address = self::validateUserAddress($address);
		if (!$validate_address) {
			return false;
		}
		
		//createrawtransaction
		$senderaddress = SluConfig::GRC_ADDRESS;
		$data = $tARC->listunspent(1, 9999999, ["{$senderaddress}"]);
		$slu_amount = 0;
		foreach ($data as $k=>$v) {
			$slu_amount += $v['amount'];
			
			unset($data[$k]['amount']);
			unset($data[$k]['address']);
			unset($data[$k]['label']);
			unset($data[$k]['scriptPubKey']);
			unset($data[$k]['confirmations']);
			unset($data[$k]['spendable']);
			unset($data[$k]['solvable']);
			unset($data[$k]['safe']);
		}
		if ($slu_amount < ($amount + SluConfig::ADDRESS_TO_ADDRESS_FEE)) {
			return false;
		}
		
		$param = [
			["{$address}" => $amount],
			["{$senderaddress}" => $slu_amount - $amount - SluConfig::ADDRESS_TO_ADDRESS_FEE]
		];
		
		$result_3 = false;
		
		$result_1 = $tARC->createrawtransaction($data, $param);
		if ($result_1) {
			$result_2 = $tARC->signrawtransactionwithwallet($result_1);
			if (isset($result_2['hex'])) {
				$result_3 = $tARC->sendrawtransaction($result_2['hex'], false);
			}
		}
		return $result_3;
	}
	
	//验证地址
	static function validateUserAddress($address) {
		$tARC = new Api_Rpc_Client_SLU();
		
		$result = $tARC->validateaddress($address);
		
		return $result['isvalid'];
	}
	
	//获取交易详情
	static function getTransactionByTxid($txid) {
		$tARC = new Api_Rpc_Client_SLU();

		$result = get_by_curl(SluConfig::API_HOST.'tx/'.$txid, 'get');
		$result = json_decode($result, true);
		if (!is_array($result)) {
			return false;
		}
		
		$result = [
			'txid' => $txid,
			'amount' => self::amountChangePoint(hexdec($result['receipt'][0]['log'][0]['data'])),
			'fee' => $result['fees'],
			'confirmations' => $result['confirmations']
		];
		
		return $result;
	}
	
	//设置交易费
	static function setUserTxFee($amount=0) {
		$tARC = new Api_Rpc_Client_SLU();
		
		$result = $tARC->settxfee($amount);
		
		return $result;
	}
	
	//获取所有交易数据(接收和转出)
	static function getAllTransactions() {
		$tARC = new Api_Rpc_Client_SLU();
		
		$result = $tARC->listsinceblock();
		
		var_dump($result);
	}
	
	//备份钱包
	static function backupUserWallet($destination) {
		$tARC = new Api_Rpc_Client_SLU();
		
		$result = $tARC->backupwallet($destination);
		
		if (empty($result)) {
			return true;
		} else {
			return false;
		}
	}
	
	//创建新地址
	static function getNewAddressForUser($username) {
		$tARC = new Api_Rpc_Client_SLU();
		
		$result = $tARC->getnewaddress($username);
		
		return $result;
	}
	
	/**
	 * 获取最新转入记录
	 * @param int $limit
	 * @return array
	 */
	public static function getLatestReceived($limit=100, $offset=0)
	{
		$tARC = new Api_Rpc_Client_SLU();

		//首次拉取所有用户的GRC钱包地址
		if (empty(self::$user_wallet_address_all)) {
			self::$user_wallet_address_all = M('UserAffiliate')->where("slu_wallet_address<>''")->getField('slu_wallet_address', true);
		}

		$list = get_by_curl(SluConfig::API_HOST.'/tokenTransfer/?limit='.$limit.'&offset='.$offset*$limit.'&contractAddress='.SluConfig::CONTRACT, 'get');
		$list = json_decode($list, true);
		$transactions = [];
		foreach ($list['items'] as $k=>$v) {
			if (in_array($v['to'], self::$user_wallet_address_all) && $v['symbol'] == SluConfig::CONTRACT_TAG) {
				$transactions[] = [
					'address' => $v['to'],
					'category' => 'receive',
					'amount' => self::amountChangePoint($v['value']),
					'txid' => $v['tx_hash'],
					'timereceived' => $v['tx_time']
				];
			}
		}
		
		$transactions = [
			'count' => $list['count'],
			'list' => $transactions
		];
		
		return $transactions;
	}
	
	/**
	 * 金额精度处理
	 * 
	 * @param int $amount 待转换的未精度处理的金额
	 */
	private static function amountChangePoint($amount) {
		$amount = $amount > 0 ? $amount : 0;
		
		return pow(0.1, SluConfig::AMOUNT_POINT) * $amount;
	}
	
}