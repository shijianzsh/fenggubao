<?php
require_once "EasemobChat.Config.php";

class EasemobChatApi
{
	private $client_id;
	private $client_secret;
	private $org_name;
	private $app_name;
	private $url;
	private $token_file;

	/**
	 * 初始化参数		
	 */
	public function __construct() {
		$this->client_id = EasemobChatConfig::client_id;
		$this->client_secret = EasemobChatConfig::client_secret;
		$this->org_name = EasemobChatConfig::org_name;
		$this->app_name = EasemobChatConfig::app_name;
		
		if (! empty ( $this->org_name ) && ! empty ( $this->app_name )) {
			$this->url = 'https://a1-vip5.easemob.com/' . $this->org_name . '/' . $this->app_name . '/';
		}
		
		$this->token_file = dirname(__FILE__). '/EasemobChat.token';
		if (!file_exists($this->token_file)) {
			$this->generateToken();
		}
	}	
	
	/**
	 * 生成具有时效的token
	 */
	private function generateToken() {
		$options = array(
			"grant_type" => "client_credentials",
			"client_id" => $this->client_id,
			"client_secret" => $this->client_secret
		);
		
		$body = json_encode($options);
		$url = $this->url.'token';
		$tokenResult = $this->postCurl($url,$body,$header=array());
		$tokenResult['generate_time'] = time();
		
		file_put_contents($this->token_file, json_encode($tokenResult, JSON_UNESCAPED_UNICODE));
	}
	
	/**
	 * 获取token 
	 */
	public function getToken()
	{
		$token_data = file_get_contents($this->token_file);
		$token_data = json_decode($token_data, true);
		
		$current_time = time();
		if ($current_time > $token_data['generate_time'] + $token_data['expires_in']) {
			$this->generateToken();
			$this->getToken();
		} else {
			return "Authorization:Bearer ".$token_data['access_token'];
		}
	}
	
	/**
	 * 授权注册
	 */
	public function createUser($username,$password) {
		$url = $this->url.'users';
		$options = array(
			"username" => $username,
			"password" => $password
		);
		$body = json_encode($options);
		$header = array($this->getToken());
		$result = $this->postCurl($url,$body,$header);
		return $result;
	}
	
	/**
	 *	批量注册用户
	 */
	public function createUsers($options) {
		$url = $this->url.'users';
	
		$body = json_encode($options);
		$header = array($this->getToken());
		$result = $this->postCurl($url,$body,$header);
		return $result;
	}
	
	/**
	 *	重置用户密码
	 */
	public function resetPassword($username,$newpassword) {
		$url = $this->url.'users/'.$username.'/password';
		$options = array(
			"newpassword" => $newpassword
		);
		$body = json_encode($options);
		$header = array($this->getToken());
		$result = $this->postCurl($url,$body,$header,"PUT");
		return $result;
	}
	
	/**
	 *	获取单个用户
	 */
	public function getUser($username) {
		$url = $this->url.'users/'.$username;
		$header = array($this->getToken());
		$result = $this->postCurl($url,'',$header,"GET");
		return $result;
	}
	
	/**
	 *	获取批量用户----不分页
	 */
	public function getUsers($limit=0) {
		if (!empty($limit)) {
			$url = $this->url.'users?limit='.$limit;
		} else {
			$url = $this->url.'users';
		}
		$header = array($this->getToken());
		$result = $this->postCurl($url,'',$header,"GET");
		return $result;
	}
	
	/**
	 *	获取批量用户---分页
	 */
	public function getUsersForPage($limit=0,$cursor='') {
		$url = $this->url.'users?limit='.$limit.'&cursor='.$cursor;
		
		$header = array($this->getToken());
		$result = $this->postCurl($url,'',$header,"GET");
		if (!empty($result["cursor"])) {
			$cursor = $result["cursor"];
			$this->writeCursor("userfile.txt",$cursor);
		}
		//var_dump($GLOBALS['cursor'].'00000000000000');
		return $result;
	}
	
	//创建文件夹
	public function mkdirs($dir, $mode = 0777) {
		 if (is_dir($dir) || @mkdir($dir, $mode)) return TRUE;
		 if (!mkdirs(dirname($dir), $mode)) return FALSE;
		 return @mkdir($dir, $mode);
	} 
	 
	//写入cursor
	public function writeCursor($filename,$content) {
		//判断文件夹是否存在，不存在的话创建
		if (!file_exists("resource/txtfile")) {
			mkdirs("resource/txtfile");
		}
		$myfile = @fopen("resource/txtfile/".$filename,"w+") or die("Unable to open file!");
		@fwrite($myfile,$content);
		fclose($myfile);	
	}
	
	//读取cursor
	public function readCursor($filename) {
		//判断文件夹是否存在，不存在的话创建
		if (!file_exists("resource/txtfile")) {
			mkdirs("resource/txtfile");
		}
		$file = "resource/txtfile/".$filename;
		$fp = fopen($file,"a+");//这里这设置成a+
		if ($fp) {
			while (!feof($fp)) {
				//第二个参数为读取的长度
				$data=fread($fp,1000);	
			}	
			fclose($fp);
		}	 
		return $data;	
	}
	
	//删除单个用户
	public function deleteUser($username) {
		$url = $this->url.'users/'.$username;
		$header = array($this->getToken());
		$result = $this->postCurl($url,'',$header,'DELETE');
		return $result;
	}
	
	/**
	 *	删除批量用户
	 *	limit:建议在100-500之间
	 *	注：具体删除哪些并没有指定, 可以在返回值中查看。
	 */
	public function deleteUsers($limit) {
		$url = $this->url.'users?limit='.$limit;
		$header = array($this->getToken());
		$result = $this->postCurl($url,'',$header,'DELETE');
		return $result;
		
	}
	
	/**
	 *	修改用户昵称
	 */
	public function editNickname($username,$nickname) {
		$url = $this->url.'users/'.$username;
		$options = array(
			"nickname"=>$nickname
		);
		$body = json_encode($options);
		$header = array($this->getToken());
		$result = $this->postCurl($url,$body,$header,'PUT');
		return $result;
	}
	
	/**
	 *	添加好友
	 */
	public function addFriend($username,$friend_name) {
		$url = $this->url.'users/'.$username.'/contacts/users/'.$friend_name;
		$header = array($this->getToken(),'Content-Type:application/json');
		$result = $this->postCurl($url,'',$header,'POST');
		return $result;	
		
			
	}
	
	
	/**
	 *	删除好友
	 */
	public function deleteFriend($username,$friend_name) {
		$url = $this->url.'users/'.$username.'/contacts/users/'.$friend_name;
		$header = array($this->getToken());
		$result = $this->postCurl($url,'',$header,'DELETE');
		return $result;	
		
	}
	
	/**
	 *	查看好友
	 */
	public function showFriends($username) {
		$url = $this->url.'users/'.$username.'/contacts/users';
		$header = array($this->getToken());
		$result = $this->postCurl($url,'',$header,'GET');
		return $result;	
		
	}
	
	/**
	 *	查看用户黑名单
	 */
	public function getBlacklist($username) {
		$url = $this->url.'users/'.$username.'/blocks/users';
		$header = array($this->getToken());
		$result = $this->postCurl($url,'',$header,'GET');
		return $result;
		
	}
	
	/**
	 *	往黑名单中加人
	 */
	public function addUserForBlacklist($username,$usernames) {
		$url = $this->url.'users/'.$username.'/blocks/users';
		$body = json_encode($usernames);
		$header = array($this->getToken());
		$result = $this->postCurl($url,$body,$header,'POST');
		return $result;	
		
	}
	
	/**
	 *	从黑名单中减人
	 */
	public function deleteUserFromBlacklist($username,$blocked_name) {
		$url = $this->url.'users/'.$username.'/blocks/users/'.$blocked_name;
		$header = array($this->getToken());
		$result = $this->postCurl($url,'',$header,'DELETE');
		return $result;	
		
	}
	
	/**
	 *	查看用户是否在线
	 */
	public function isOnline($username) {
		$url = $this->url.'users/'.$username.'/status';
		$header = array($this->getToken());
		$result = $this->postCurl($url,'',$header,'GET');
		return $result;	
		
	}
	
	/**
	 *	查看用户离线消息数
	 */
	public function getOfflineMessages($username) {
		$url = $this->url.'users/'.$username.'/offline_msg_count';
		$header = array($this->getToken());
		$result = $this->postCurl($url,'',$header,'GET');
		return $result;	
			
	}
	
	/**
	 *	查看某条消息的离线状态
	 *	----deliverd 表示此用户的该条离线消息已经收到
	 */
	public function getOfflineMessageStatus($username,$msg_id) {
		$url = $this->url.'users/'.$username.'/offline_msg_status/'.$msg_id;
		$header = array($this->getToken());
		$result = $this->postCurl($url,'',$header,'GET');
		return $result;	
		
	}
	
	/**
	 *	禁用用户账号
	 */ 
	public function deactiveUser($username) {
		$url = $this->url.'users/'.$username.'/deactivate';
		$header = array($this->getToken());
		$result = $this->postCurl($url,'',$header);
		return $result;
	}
	
	/**
	 *	解禁用户账号
	 */ 
	public function activeUser($username) {
		$url = $this->url.'users/'.$username.'/activate';
		$header = array($this->getToken());
		$result = $this->postCurl($url,'',$header);
		return $result;
	} 
	
	/**
	 *	强制用户下线
	 */ 
	public function disconnectUser($username) {
		$url = $this->url.'users/'.$username.'/disconnect';
		$header = array($this->getToken());
		$result = $this->postCurl($url,'',$header,'GET');
		return $result;
	}
	
	/**
	 * $this->postCurl方法
	 */
	private function postCurl($url,$body,$header,$type="POST"){
		//1.创建一个curl资源
		$ch = curl_init();
		//2.设置URL和相应的选项
		curl_setopt($ch,CURLOPT_URL,$url);//设置url
		//1)设置请求头
		//array_push($header, 'Accept:application/json');
		//array_push($header,'Content-Type:application/json');
		//array_push($header, 'http:multipart/form-data');
		//设置为false,只会获得响应的正文(true的话会连响应头一并获取到)
		curl_setopt($ch,CURLOPT_HEADER,0);
//		curl_setopt ( $ch, CURLOPT_TIMEOUT,5); // 设置超时限制防止死循环
		//设置发起连接前的等待时间，如果设置为0，则无限等待。
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,5);
		//将curl_exec()获取的信息以文件流的形式返回，而不是直接输出。
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		//2)设备请求体
		if (count($body)>0) {
			//$b=json_encode($body,true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);//全部数据使用HTTP协议中的"POST"操作来发送。
		}
		//设置请求头
		if(count($header)>0){
			curl_setopt($ch,CURLOPT_HTTPHEADER,$header);
		}
		//上传文件相关设置
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// 对认证证书来源的检查
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);// 从证书中检查SSL加密算
		
		//3)设置提交方式
		switch ($type) {
			case "GET":
				curl_setopt($ch,CURLOPT_HTTPGET,true);
				break;
			case "POST":
				curl_setopt($ch,CURLOPT_POST,true);
				break;
			case "PUT"://使用一个自定义的请求信息来代替"GET"或"HEAD"作为HTTP请求。这对于执行"DELETE" 或者其他更隐蔽的HTT
				curl_setopt($ch,CURLOPT_CUSTOMREQUEST,"PUT");
				break;
			case "DELETE":
				curl_setopt($ch,CURLOPT_CUSTOMREQUEST,"DELETE");
				break;
		}
		
		
		//4)在HTTP请求中包含一个"User-Agent: "头的字符串。-----必设
	
//		curl_setopt($ch, CURLOPT_USERAGENT, 'SSTS Browser/1.0');
//		curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
	
		curl_setopt ( $ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0)' ); // 模拟用户使用的浏览器
		//5)
		
		
		//3.抓取URL并把它传递给浏览器
		$res = curl_exec($ch);
		$result = json_decode($res,true);
		//4.关闭curl资源，并且释放系统资源
		curl_close($ch);
		if (empty($result)) {
			return $res;
		} else {
			return $result;
		}
	}
	
}
