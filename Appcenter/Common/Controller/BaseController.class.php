<?php

// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | APP模型继承类
// +----------------------------------------------------------------------

namespace Common\Controller;

use Think\Controller;
use Sms\Request\V20160927 as Sms;

class BaseController extends Controller {


    /**
     * 短信接口
     * @param int $telphone
     * @param string $sms_name 选用的短信接口名称
     * @param string $content 短信内容
     * @param string $template 短信模板(非通用,视需调用)
     */
    protected function sms($telphone, $sms_name = '', $content = '', $template = 'yanzhengma') {
        $sms_config = C('SMS_CONFIG');

        //判断是否有默认使用短信类型
        if (empty($sms_name)) {
            if (C('SMS_USE_DEFAULT')) {
                $sms_name = C('SMS_USE_DEFAULT');
            } else {
                $sms_name = 'CHUANGXIN';
            }
        }

        //对单个或多个手机号进行正则验证
        $telphone_arr = strpos($telphone, ',') ? explode(',', $telphone) : array($telphone);
        foreach ($telphone_arr as $tel) {
            if (!validateExtend($tel, 'MOBILE')) {
                return getReturn('手机号码格式有误');
            }
        }
        if (!isset($sms_config[$sms_name])) {
            return getReturn('无对应短信接口');
        }

        //短信通用开头标识
        $content_prefix = "【{$sms_config[$sms_name]['sign']}】";

        //生成唯一六位数数字短信验证码
        $unique_id = getMd5($telphone);
        $unique_id = substr($unique_id, 0, 6);
        $unique_id = preg_replace('/[a-zA-Z]{1}/', rand(1, 9), $unique_id);

        //组装短信内容
        $unique_id = empty($content) ? $unique_id : $content;
        $sms_content = empty($content) ? $content_prefix . '验证码：' . $unique_id : $content_prefix . $content;
        $_SESSION['contentcode'] = $unique_id;
        switch ($sms_name) {
            case 'CHUANGXIN':
                $sms_var = $sms_config[$sms_name];
                $sms_push = $sms_var['url'];
                unset($sms_var['url']);
                $sms_var['mobile'] = $telphone;
                $sms_var['content'] = $sms_content;
                $this->curl($sms_push, 'post', $sms_var);
                return getReturn('', $unique_id);
                break;
            case 'ALI':
                $this->aliSms2($telphone, $unique_id, $template);
                return getReturn('', $unique_id);
                break;
        }
    }
    
    /**
     * 阿里短信接口【老接口，适合ZCSH】
     * @param $telphone string 手机号码
     * @param $unique_id 验证码
     * @param $template 短信模板
     */
    private function aliSms($telphone, $unique_id, $template) {
        Vendor("AliSms.aliyun-php-sdk-core.Config");
        
        $config = C('SMS_CONFIG.ALI');
        
        if (!isset($config['template'][$template])) {
            return getReturn('未识别的短信模板类型');
        }
        $template = $config['template'][$template];
        
        $iClientProfile = \DefaultProfile::getProfile("cn-hangzhou", $config['key'], $config['secret']);
        $client = new \DefaultAcsClient($iClientProfile);
        $request = new Sms\SingleSendSmsRequest();
        $request->setSignName($config['sign']);
        $request->setTemplateCode($template);
        $request->setRecNum($telphone);
        
        $data = array();
        $data['code'] = $unique_id;
        $data['product'] = $config['sign'];
        $request->setParamString(json_encode($data));
        try {
            $response = $client->getAcsResponse($request);
            //return getReturn('', $unique_id);
        } catch (\ClientException $e) {
            //return getReturn('['.$e->getErrorCode().']:'.$e->getErrorMessage());
        } catch (\ServerException $e) {
            //return getReturn('['.$e->getErrorCode().']:'.$e->getErrorMessage());
        }
    }

    /**
     * 阿里短信接口【新接口，适合新申请的，如FRB】
     * @param $telphone string 手机号码
     * @param $unique_id 验证码
     * @param $template 短信模板
     */
    private function aliSms2($telphone, $unique_id, $template) {
        Vendor("Aliyun.init");

        $config = C('SMS_CONFIG.ALI');

        if (!isset($config['template'][$template])) {
            return getReturn('未识别的短信模板类型');
        }
        
        $data = array();
        $data['code'] = $unique_id;
        
        //验证码不能传product参数
        $vcode_template = ['yanzhengma', 'login_submit', 'login_warning'];
        if (!in_array($template, $vcode_template)) {
        	$data['product'] = $config['sign'];
        }
        
        $template = $config['template'][$template];
        
        $Sms = new \AliyunSms($config['key'], $config['secret']);
        $Sms->sendSms($config['sign'], $template, $telphone, $data);
    }


    /**
     * 公共返回函数
     * @param $msg 需要打印的错误信息
     * @param $code 默认打印300信息(300:fial,400:success)
     */
    public function myApiPrint($msg = '', $code = 300, $data = '') {
        if ($this->app_common_data['uid'] != '') {
            $unique_sessionkey = CONTROLLER_NAME . '_' . ACTION_NAME . '_' . $this->app_common_data['uid'];
            session($unique_sessionkey, NULL);
        }

        $result = array();

        //对data中的附件地址进行统一自动添加附件头域名(便于实现附件分离)
        if (C('ATTACH_SEPARATION_ON')) {
            if (!empty($data) && is_array($data)) {
                //随机获取一个附件头域名
                $attach_domain_key = array_rand(C('DEVICE_CONFIG')[C('DEVICE_CONFIG_DEFAULT')]['attach_domain'], 1);
                $attach_domain = C('DEVICE_CONFIG')[C('DEVICE_CONFIG_DEFAULT')]['attach_domain'][$attach_domain_key];

                //对任一接口返回的含有Uploads字符串的并且不带http头的内容进行附加头域名
                $data = json_encode($data, JSON_UNESCAPED_SLASHES);
                $data = preg_replace('/(\"){1}(\.)?(\/)?Uploads\//', '"' . $attach_domain . '/Uploads/', $data);
                $data = preg_replace('/(\,){1}(\.)?(\/)?Uploads\//', ',' . $attach_domain . '/Uploads/', $data);
                
                $data = json_decode($data, true);
            }
        }
        
        //转译
        $accept_language = getCurrentLang();
        if ($accept_language != 'zh-cn') {
	        $lang_package = include ($_SERVER['DOCUMENT_ROOT'].'/Appcenter/APP/Lang/'.$accept_language.'.php');
	        $data = json_encode( $data, JSON_UNESCAPED_SLASHES );
	        $data = decodeUnicode($data);
	        foreach ($lang_package as $k=>$v) {
	        	$data = preg_replace("/{$k}/", $v, $data);
	        	$msg = preg_replace("/{$k}/", $v, $msg);
	        }
	        $data = json_decode( $data, true );
        }

        if ($data != $this->myapiprint_no_result) {
            if ($data == '') {
                $result['result'] = (object) array();
            } else {
                $result['result'] = $data;
            }
        }

        $result['code'] = $code;
        $result['msg'] = $msg;

        //自动缓存写入
        if ($result['code'] == '400' && !empty($result['result'])) {
            $this->cacheAutoWrite(json_encode($result));
        }

        $this->ajaxReturn($result);
        exit;
    }

   
}

?>