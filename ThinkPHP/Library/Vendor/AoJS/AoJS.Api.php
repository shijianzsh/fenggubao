<?php
require_once "AoJS.Config.php";
require_once "AoJS.Rsa.php";

class AoJSApi
{
	/**
	 * 获取实时单价
	 */
    public static function getPrice()
    {
        $opts = [
            'http' => [
                'method' => "GET",
                'timeout' => 10,
            ]
        ];
        $context = stream_context_create($opts);
        $response = file_get_contents(AoJSConfig::PRICE_URL, false, $context);
        try {
            $response = json_decode($response, true);
//             if ($response['code'] && floatval($response['data']) > 0) {
//                 return $response['data'];
//             }
            if (floatval($response['cny']) > 0) {
            	return round($response['cny'], 1);
            }
        } catch (Exception $exception) {

        }
        return false;
    }

}
