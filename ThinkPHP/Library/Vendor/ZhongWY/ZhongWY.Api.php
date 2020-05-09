<?php
require_once "ZhongWY.Config.php";
require_once "ZhongWY.Rsa.php";

class ZhongWYApi
{
    /**
     * @param array $bank_data 银行卡参数数组
     */
    public static function pay($outOrderNo, $payType, $amount, $notifyUrl, $bank_data='')
    {
        $params = [
            'uid' => ZhongWYConfig::UID,
            'goodsId' => ZhongWYConfig::GOODS_ID,
            'outOrderNo' => $outOrderNo,
            'payType' => $payType,
            'price' => intval($amount * 100),
            'ts' => time(),
            'notifyUrl' => $notifyUrl,
        ];
        
        
        //银行卡支付
        if ($payType == 'bank_card') {
        	$params_bank = [
        		'phone' => $bank_data['phone'],
        		'cardNo' => $bank_data['cardNo'],
        		'bankName' => $bank_data['bankName'],
        		'name' => $bank_data['name'],
        		'receivePrice' => $bank_data['receivePrice']
        	];
        	$params = array_merge($params, $params_bank);
        }
        
        $params['sign'] = self::sign($params);
        $params = [
            'pay_url' => sprintf('%s?%s', ZhongWYConfig::PAY_URL, http_build_query($params)),
            'referer' => ZhongWYConfig::HOST
        ];
        return $params;
    }

    private static function twoMd5($params)
    {
        $data = implode('', array_values($params));
        return md5(md5($data));
    }

    private static function sign($params)
    {
        $rsa = new ZhongWYRsa();
        unset($params['payType']);
        $data = self::twoMd5($params);
        $sign = $rsa->publicEncrypt($data);
        return $sign;
    }

    public static function check($sign, $params)
    {
        $rsa = new ZhongWYRsa();
        $decrypt = $rsa->publicDecrypt($sign);
        $data = self::twoMd5($params);
        return $decrypt == $data;
    }

    public static function getPrice()
    {
        $opts = [
            'http' => [
                'method' => "GET",
                'timeout' => 10,
            ]
        ];
        $context = stream_context_create($opts);
        $response = file_get_contents(ZhongWYConfig::PRICE_URL, false, $context);
        try {
            $response = json_decode($response, true);
            if ($response['code'] == '0000' && floatval($response['data']['price']) > 0) {
                return $response['data']['price'];
            }
        } catch (Exception $exception) {

        }
        return false;
    }

}
