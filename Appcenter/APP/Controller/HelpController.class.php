<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 帮助资料相关
// +----------------------------------------------------------------------
namespace APP\Controller;
use Common\Controller\ApiController;

class HelpController extends ApiController {
	
	/**
	 * 用户提交留言
	 */
	public function leave() {
		$content = I('post.content');          //内容
		$contactinfo = I('post.contactinfo');  //联系方式
		$uid = I('post.uid');  //id
		if($content == ''){
			$this->myApiPrint('请填写留言内容', 300);exit;
		}
	    if(!validateExtend($contactinfo, 'MOBILE')) {
            $this->myApiPrint('手机号码格式不对！');
        }
		if($contactinfo != ''){
			if(validateExtend($contactinfo, 'MOBILE') || validateExtend($contactinfo, 'PHONE') || validateExtend($contactinfo, 'EMAIL') || validateExtend($contactinfo, 'QQ')){
				//echo 'ok';
			}else{
				$this->myApiPrint('联系方式格式不正确', 300);exit;
			}
		}
		
		//验证用户
		$user = M('member')->find($uid);
		if($user){
			$vo['uid'] = $uid;
			$vo['content'] = $content;
			$vo['contact'] = $contactinfo;
			$vo['date_created'] = time();
			M('feedback')->add($vo);
			$this->myApiPrint('提交成功', 400);exit;
		}else{
			$this->myApiPrint('请登录', 300);exit;
		}
	}
	
	public function yylive(){
        $this->myApiPrint('此功能暂停使用', 300, '');exit;
        if(intval($this->app_common_data['uid']) != 30){
            $this->myApiPrint('加载成功', 400, C('PARAMETER_CONFIG.ONLINE_CLASSROOM'));exit;
        }else{
            $this->myApiPrint('此功能暂停使用', 300, '');exit;
        }
	}
	
	/**
	 * 检测银行卡
	 * Enter description here ...
	 */
	public function ckbanccard(){
		$bankcard = I('post.bankcard');          //卡号
		
		$host = "http://jisuyhkgsd.market.alicloudapi.com";
	    $path = "/bankcard/query";
	    $method = "GET";
	    $appcode = "de288a1c383f4cc58af82f3002969b7d";
	    $headers = array();
	    array_push($headers, "Authorization:APPCODE " . $appcode);
	    $querys = "bankcard=$bankcard";
	    $bodys = "";
	    $url = $host . $path . "?" . $querys;
	
	    $curl = curl_init();
	    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
	    curl_setopt($curl, CURLOPT_URL, $url);
	    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	    curl_setopt($curl, CURLOPT_FAILONERROR, false);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	 //   curl_setopt($curl, CURLOPT_HEADER, true);
	    if (1 == strpos("$".$host, "https://"))
	    {
	        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	    }
	    $res = curl_exec($curl);
	    $res = json_decode($res, true);
	//	unittest($res, $array2);
	    if($res['status'] == 0 && $res['msg'] == 'ok'){
	    	$bank = M('Bank')->where('bank = \''.$res['result']['bank'].'\'')->find();
	    	if($bank['is_get'] == 0){
	    		
	    		//下载logo
	    		$img = http($res['result']['logo']);
	    		$filename = '/Uploads/banklogo/'.$res['result']['tel'].'.png';  
			    $fp= @fopen($_SERVER['DOCUMENT_ROOT'].$filename,"a"); //将文件绑定到流    
			    fwrite($fp,$img); //写入文件  
			    
			    $vo['logo'] = $filename;
	    		$vo['tel'] = $res['result']['tel'];
	    		$vo['bank'] = $res['result']['bank'];
	    		$vo['website'] = $res['result']['website'];
	    		$vo['is_get'] = 1;
	    		M('Bank')->where('is_get=0 and bank = \''.$res['result']['bank'].'\'')->save($vo);
	    		
	    		$this->myApiPrint('获取成功', 400, $vo);exit;
	    	}
	    	$this->myApiPrint('获取成功', 400, $bank);exit;
	    }else{
	    	$this->myApiPrint('没有检测到数据', 400, '');exit;
	    }
	}
	
	/**
	 * 获取电话等信息
	 */
	public function getAppConfig(){
		$data['appname'] = C('APP_TITLE');
		$data['tel'] = '13990141242';
		$this->myApiPrint('获取成功', 400, $data);
	}
}
?>