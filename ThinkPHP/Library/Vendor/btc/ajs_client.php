<?php
require_once "btc_config.php";

class Api_Rpc_Client_AJS
{

    private $debug;
    private $url;
    private $id;
    private $notification = false;

    /**
     *
     * @param boolean $debug 是否开启调试模式(默认false)
     */
    public function __construct($debug = false)
    {
        $this->url = 'http://' . AoJSConfig::USERNAME . ':' . AoJSConfig::PASSWORD . '@' . AoJSConfig::IP . ':' . AoJSConfig::PORT;
        $this->debug = AoJSConfig::DEBUG;
        $this->id = 1;
    }

    public function setRPCNotification($notification)
    {
        $this->notification = !empty($notification);
    }

    public function __call($method, $params)
    {
        if (!is_scalar($method)) {
            //throw new Exception('Method name has no scalar value');
            die('Method name has no scalar value');
        }
        if (is_array($params)) {
            $params = array_values($params);
        } else {
            //throw new Exception('Params must be given as array');
            die('Params must be given as array');
        }
        if ($this->notification) {
            $currentId = null;
        } else {
            $currentId = $this->id;
        }
        $request = array('method' => $method, 'params' => $params, 'id' => $currentId);
        $request = json_encode($request);
        $this->debug && $this->debug .= '***** Request *****' . "\n" . $request . "\n" . '***** End Of request *****' . "\n\n";
        $opts = array('http' => array('method' => 'POST', 'header' => 'Content-type: application/json', 'content' => $request));
        $context = stream_context_create($opts);
        if ($fp = fopen($this->url, 'r', false, $context)) {
            $response = '';
            while ($row = fgets($fp)) {
                $response .= trim($row) . "\n";
            }
            $this->debug && $this->debug .= '***** Server response *****' . "\n" . $response . '***** End of server response *****' . "\n";
            $response = json_decode($response, true);
        } else {
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
        if ($this->debug) {
            echo nl2br($this->debug);
        }
        if (!$this->notification) {
            if ($response['id'] != $currentId) {
                //throw new Exception('Incorrect response id (request id: ' . $currentId . ', response id: ' . $response['id'] . ')');
                die('Incorrect response id (request id: ' . $currentId . ', response id: ' . $response['id'] . ')');
            }
            if (!is_null($response['error'])) {
                //throw new Exception('Request error: ' . $response['error']);
                die('Request error: ' . $response['error']);
            }
            return $response['result'];
        } else {
            return true;
        }
    }

    static function getAddrByCache($pKey, $pCache = 0, $coin)
    {
        if (empty($coin)) {
            return FALSE;
        }
        $tRedis = &Cache_Redis::instance();
        if ($pCache && $tAddr = $tRedis->hget($coin . 'addr', $pKey)) {
            return $tAddr;
        }
        if ($pCache && $tAddr = $tRedis->hget($coin . 'addrnew', $pKey)) {
            return $tAddr;
        }
        if (1 == $pCache) {
            return false;
        }
        $tARC = new Api_Rpc_Client_AJS(Yaf_Application::app()->getConfig()->api->rpcurl->$coin);
        $tAddr = $tARC->getnewaddress($pKey);
        $tRedis->hset($coin . 'addrnew', $pKey, $tAddr);
        return $tAddr;
    }


    //获取余额
    static function getBalance()
    {
        $tARC = new Api_Rpc_Client_AJS();
        $balance = $tARC->getinfo();

        return (empty($balance) ? 0 : $balance['balance']);
    }

    //直接转账给地址
    static function sendToUserAddress($address, $amount)
    {
        $tARC = new Api_Rpc_Client_AJS();

        //核验地址
        $validate_address = self::validateUserAddress($address);
        if ($validate_address === false) {
            return false;
        }

        $tTxid = $tARC->sendtoaddress($address, $amount);

        if (strlen($tTxid) != 64 && FALSE !== strpos($tTxid, 'error')) {
            return false;
        }

        return $tTxid;
    }


    //验证地址
    static function validateUserAddress($address)
    {
        $tARC = new Api_Rpc_Client_AJS();

        $result = $tARC->getreceivedbyaddress($address);

        return $result;
    }

    //获取交易详情
    static function getTransactionByTxid($txid)
    {
        $tARC = new Api_Rpc_Client_AJS();

        $result = $tARC->gettransaction($txid);

        return $result;
    }

    //设置交易费
    static function setUserTxFee($amount = 0)
    {
        $tARC = new Api_Rpc_Client_AJS();

        $result = $tARC->settxfee($amount);

        return $result;
    }

    //获取所有交易数据(接收和转出)
    static function getAllTransactions()
    {
        $tARC = new Api_Rpc_Client_AJS();

        $result = $tARC->listsinceblock();

        var_dump($result);
    }

    //备份钱包
    static function backupUserWallet($destination)
    {
        $tARC = new Api_Rpc_Client_AJS();

        $result = $tARC->backupwallet($destination);

        if (empty($result)) {
            return true;
        } else {
            return false;
        }
    }

    //创建新地址
    static function getNewAddressForUser($username)
    {
        $tARC = new Api_Rpc_Client_AJS();

        $result = $tARC->getnewaddress($username);

        return $result;
    }

    /**
     * 获取最新转入记录
     * @param int $limit
     * @return array
     */
    public static function getLatestReceived($limit = 99999999999)
    {
        $tARC = new Api_Rpc_Client_AJS();
        $list = $tARC->listtransactions('*', $limit, 0);
        $transactions = [];
        foreach ($list as $item) {
            // 验证是否收到节点广播（验证交易是否确认）
            if (!isset($item['blockhash']) || !isset($item['blockindex']) || !isset($item['blocktime'])) continue;
            // 只返转入记录
            if ($item['category'] != 'receive') continue;
            $transactions[] = $item;
        }
        return $transactions;
    }

}