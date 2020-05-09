<?php
require_once "Luosimao.Config.php";

class LuosimaoApi
{
    public static function send($phone, $message)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, sprintf('%s/v1/send.json', LuosimaoConfig::HOST));

        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);

        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, LuosimaoConfig::API_KEY);

        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array('mobile' => $phone, 'message' => $message));

        $res = curl_exec($ch);
        curl_close($ch);
        return json_decode($res, true);
    }

    /**
     *
     */
    private static function getMessage($templateKey, $code)
    {
        return sprintf(LuosimaoConfig::TEMPLATES[$templateKey], $code);
    }

    /**
     *
     * @param $phone
     * @param $templateKey
     * @param $code
     * @return mixed
     */
    public static function sendByTemplateKey($phone, $templateKey, $code)
    {
        return self::send($phone, self::getMessage($templateKey, $code));
    }

}
