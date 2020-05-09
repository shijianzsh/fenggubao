<?php
ini_set('date.timezone','Asia/Shanghai');

require_once "Slu.Config.php";
require_once "Slu.Rsa.php";

class SluApi
{
	/**
	 * 获取实时单价 (原则上获取昨天的数据]
	 */
    public static function getPrice()
    {
        $opts = [
            'http' => [
                'method' => "GET",
                'timeout' => 10,
            ]
        ];
        
        $from = self::getMillisecond(strtotime(date('Y-m-d 00:00:00')) - 3600*24*2); //前天日期
        $to = self::getMillisecond(); //今天日期
        $context = stream_context_create($opts);
        $response = file_get_contents(SluConfig2::PRICE_URL.'&from='.$from.'&to='.$to, false, $context);
        
        try {
        	//只获取昨天的价格
        	$yesterday = strtotime(date('Y-m-d 00:00:00')) - 3600*24;
        	$price = 0;
        	
        	$response = json_decode($response, true);
        	
        	foreach ($response as $k=>$v) {
        		$timestamp = substr($v[0],0,10);
        		if (date('Ymd', $timestamp) == date('Ymd', $yesterday)) {
        			$price = $v[3];
        		} else {
        			continue;
        		}
        	}
        	
        	if ($price == 0) {
        		return false;
        	}
        	
        	//通过汇率转换为CNY价格
        	$rate = self::getExchangeRate();
        	if ($rate) {
        		return $rate * $price;
        	}
        } catch (Exception $exception) {
        	
        }
        
        return false;
    }
    
    /**
     * 获取实时汇率
     */
    public static function getExchangeRate()
    {
    	$opts = [
	    	'http' => [
		    	'method' => "GET",
		    	'timeout' => 10,
    	]
    	];
    	$context = stream_context_create($opts);
    	$response = file_get_contents(SluConfig2::EXCHANGE_RATE_URL, false, $context);
    	try {
    		$response = json_decode($response, true);
    		return $response['data'];
    	} catch (Exception $exception) {
    		 
    	}
    	return false;
    }
    
    /**
     * 获取指定时间的毫秒级别的时间戳
     * 
     * @param string $timestamp 指定时间的时间戳
     */
    private static function getMillisecond($timestamp)
    {
    	if (empty($timestamp)) {
    		$timestamp = time();
    	}
    	
    	//获取毫秒的时间戳
    	$time = explode ( " ", microtime () );
    	$time = $timestamp . ($time[0] * 1000);
    	$time2 = explode( ".", $time );
    	$time = $time2[0];
    	
    	return $time;
    }

}
