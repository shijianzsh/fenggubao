<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 全局通用继承类
// +----------------------------------------------------------------------
namespace Common\Controller;
use Think\Controller;

class PushController extends Controller {
	
	private $app_key;
	private $master_secret;
	private $push_url;
	private $apns_production;
	
	private $_get;
	private $_post;
	private $_cookie;
	private $_get_filter = '(alert|confirm|prompt|onerror|onmousemove|onload|onclick|onmouseover|bin|script|exec|union|select|update|insert|create|alter|drop|truncate|database){0,}';
	private $_post_filter = '(alert|confirm|prompt|onerror|onmousemove|onload|onclick|onmouseover|update|insert|select|delete|create|alter|drop|truncate|database){0,}';
	private $_cookie_filter = '(bin|script|exec|union|select|update|insert|delete|create|alter|drop|truncate|database){0,}';
	
	public function __construct() {
		parent::__construct();
		vendor('JPush');
		$this->app_key = C('PUSH_CONFIG.APP_KEY');
		$this->master_secret = C('PUSH_CONFIG.MASTER_SECRET');
		$this->push_url = C('PUSH_CONFIG.PUSH_URL');
		$this->apns_production = C('PUSH_CONFIG.APNS_PRODUCTION');
		
		//参数安全过滤
		$this->safeFilterInit();
		
		//路由安全过滤
		$this->routeFilter();
		
		//记录日记
		if ($_SERVER["REQUEST_METHOD"] == "POST"){
			$log_post_data = $_POST;
			
			//密码处理
			if (isset($log_post_data['password'])) {
				$log_post_data['password'] = md5($log_post_data['password']);
			}
			if (isset($log_post_data['safe_password'])) {
				$log_post_data['safe_password'] = md5($log_post_data['safe_password']);
			}
			
			$arg = "";
			$arg .= 'TIME: '. date('Y-m-d H:i:s'). PHP_EOL;
			$arg .= 'IP: '. get_client_ip(0, true). PHP_EOL;
			$arg .= 'PATH: '. MODULE_NAME. '/'. CONTROLLER_NAME.'/'. ACTION_NAME. PHP_EOL;
			$arg .= 'DATA: ';
			$arg .= http_build_query($log_post_data);
			
			if (get_magic_quotes_gpc()) {
				$arg = stripslashes($arg);
			}
			
			$arg .= PHP_EOL;
			$arg = urldecode($arg);
			
			$time_str = date('Y').'_'.date('m').'_'.date('d');
			$log_file = $_SERVER['DOCUMENT_ROOT'].'/record/'.MODULE_NAME.'/'.$time_str.'.log.php';
			
			//第一次生成生成文件时,执行特殊的写入
			if (!file_exists($log_file)) {
				$content = "<?php\n exit; \n ?> \n".$arg.PHP_EOL;
			} else {
				$content = $arg.PHP_EOL;
			}
			
			//写入日志
			$dir = dirname($log_file);
	        if (!is_dir($dir)) {
	            mkdir($dir,0777,true);
	        }
			file_put_contents($log_file, $content, FILE_APPEND);
		}
	}
	
	/**
	 * 通用CURL封装
	 * @param string $url url地址
	 * @param string $type HTTP方式(post,get)
	 * @param array $param 传值数组
	 * @param string $header header头信息
	 * @param int $timeout 允许执行的最长秒数
	 */
	public function curl($url, $type='post', $param='', $header='', $timeout=false) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		if ($timeout) {
			curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		}
		
		if ($type=='post') {
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_POST, 1);
			
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
		}
		
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	
		$data = curl_exec($ch);
		curl_close($ch);
		//unittest($data);
		return $data;
	}
	
	/**
	 * 按设备类型进行推送
	 */
	public function push($receiver='all', $content='', $extraparams, $m_time='86400') {
		$max_num = 1000; //每批次最多推送个数
		$return = '';
	    if (is_array($receiver)) {
	    	foreach ($receiver as $platform=>$key) {
	    		if(count($receiver[$platform]) > 0) {
	    			//超过最大条数自动分批处理
	    			if (count($key) > $max_num) {
	    				$key_more = array_chunk($key, $max_num);
	    				foreach ($key_more as $k=>$key_list) {
	    					$receiver_list = array('registration_id'=>$key_list);
	    					$return = $this->pushAction($receiver_list, $content, $extraparams, $m_time, $platform);
	    				}
	    			}
	    			else {
	    				$receiver_list = array('registration_id'=>$key);
	    				$return = $this->pushAction($receiver_list, $content, $extraparams, $m_time, $platform);
	    			}
	    		}
	    	}
	    }
	    else {
	    	$return = $this->pushAction($receiver, $content, $extraparams, $m_time);
	    }
	    return $return;
	}
	
	
	/**
	 * 推送
	 * @param string $receiver 接收者的信息
	 *     all 字符串 该产品下的所有用户,对app_key下的所有用户推送消息
	 *     tag(20个) array标签组(并集): tag=>array('昆明','北京','上海');
	 *     tag_and(20个) array标签组(交集): tag_and=>array('广州','女');
	 *     alias(1000) array别名(并集): alias=>array('93d78b73611d886a74*****88497f501','606d05090896228f66ae10d1*****310');
	 *     registeration_id(1000) 注册ID设备标识(并集): registeration_id=>array('20effc071de0b45c1a**********2824746e1ff2001bd80308a467d800bed39e');
	 * @param string $content 推送的内容
	 * @param string $m_type 推送附加字段的类型(可为空) http,tips,chat...
	 * @param string $m_txt 推送附加字段的类型对应的内容(可为空) 可能是url,也可能是一段文字
	 * @param string $m_time 保存离线时间的秒数(默认为一天,可为空,单位为秒)
	 * @param string $platform 设备类型(默认all,可选android,ios,...)
	 */
	protected function pushAction($receiver='all', $content='', $extraparams, $m_time='86400', $platform='all') {
		$base64 = base64_encode("$this->app_key:$this->master_secret");
		$header = array("Authorization:Basic $base64", "Content-Type:application/json");
		
		$data = array();
		$data['platform'] = $platform;
		$data['audience'] = $receiver;
		
		$extraparams = array('type'=>'', 'txt'=>$extraparams);
		
		if ($platform == 'all') {
			$data['notification'] = array(
				//统一标准模式
				'alert' => $content,
				//安卓自定义模式
				'android' => array(
					'alert' => $content,
					'title' => '',
					'builder_id' => 1,
					'extras' => $extraparams
				),
				//IOS自定义模式
				'ios' => array(
					'alert' => $content,
					'badge' => '+1',
					'sound' => 'default',
					'extras' => $extraparams
				),
			);
		}
		//ios目前采用通知方式
		elseif ($platform == 'ios') {
			$data['notification'] = array(
				//统一标准模式
				'alert' => $content,
				//IOS自定义模式
				'ios' => array(
					'alert' => $content,
					'badge' => '+1',
					'sound' => 'default',
					'content-available' => true,
					'extras' => $extraparams
				),
			);
		}
		//android目前采用消息方式
		elseif ($platform == 'android') {
			$data['message'] = array(
				'msg_content' => $content,
				'extras' => $extraparams
			);
		}
		
		//附加选项
		$data['options'] = array(
			'sendno' => time(),
			'time_to_live' => $m_time, //保存离线时间的秒数(默认一天)
			'apns_production' => $this->apns_production, //指定APNS通知发送环境: 0开发环境, 1生产环境
		);
		$param = json_encode($data);
		$res = $this->curl($this->push_url, 'post', $param, $header);
		//echo '<pre>';
		//print_r($res);
		if ($res) {
			return $res;
		}
		else {
			return false;
		}
	}
	
	/**
	 * 邮件发送封装
	 * @param array $data 邮件参数 array('email'=>'', 'subject'=>'', 'body'=>'', 'channel'=>'one')
	 */
	public function sendMail($data) {
		Vendor('PhpMailer.PHPMailer');
		$mail = new \PHPMailer();
		$mail_config = C('MAIL_CONFIG');
	
		$channel = isset($data['channel']) ? strtoupper($data['channel']) : 'ONE';
		if (isset($mail_config[$channel])) {
			$mail_config = $mail_config[$channel];
		}
		else {
			return false;
		}
	
		$mail->IsSMTP();
		$mail->Host = $mail_config['host'];
		$mail->SMTPDebug = false;
		$mail->SMTPAuth = true;
		$mail->Host = $mail_config['host'];
		$mail->Port = $mail_config['port'];
		$mail->Username = $mail_config['username'];
		$mail->Password = $mail_config['password'];
		$mail->SetFrom($mail_config['username'], $mail_config['username']);
		$mail->AddReplyTo($mail_config['username'], $mail_config['username']);
		$mail->Subject = $data['subject'];
		$mail->AltBody = "AltBody";
		$mail->MsgHTML($data['body']);
		$mail->AddAddress($data['email'], "Member");
	
		if (!$mail->Send()) {
			return false;
		}
		else {
			return true;
		}
	}
	
	/**
	 * 日志入库封装
	 * @param mixed $content 日志内容
	 * @param int $type 日志类型(0:后台日志,1:接口日志)
	 * @param boolean $local 是否同步到本地日志文件中(默认false)
	 * @param int $uid 用户ID(仅在$type=1时有效且不能为false)
	 */
	public function logWrite($content, $type='0', $local=false, $uid=false) {
		$Log = new \Think\Log();
		$LogData = D("Admin/Log");
		
		C('TOKEN_ON', false);
		
		if (empty($content)) {
			return;
		}
		
		//入本地日志文件
		if ($local) {
			$Log->write($content, 'INFO');
		}
		
		//判断日志类型
		switch ($type) {
			case '0':
				$sess_auth = session(C('AUTH_SESSION'));
				$admin_id = isset($sess_auth['admin_id']) ? $sess_auth['admin_id'] : 1;
				break;
			case '1':
				$admin_id = $uid ? $uid : 0;
				break;
			default:
				$admin_id = 0;
		}
		
		//入库
		$data_log = array(
			'admin_id' => $admin_id,
			'content' => $content,
			'type' => $type
		);
		
		
		if ($LogData->create($data_log)) {
			$LogData->add();
		}
	}
	
	/**
	 * 筛选掉同级以下会员(包括/不包括同级)的条件封装
	 * @param $uid int 当前会员ID
	 * @param $contain boolean 是否包含同级(默认包含)
	 * @param $alias string Member表别名(默认为false)
	 * @param $where array 若调用此方法前已有repath或id相关的筛选,则可通过此参数传入,本方法对对其进行兼容处理(默认false)
	 * @param $type string 返回数据类型[array,string](默认array)
	 * @return $return array
	 * @description 
	 * 调用此方法时:
	 * 若已存在原有筛选条件,且$type=array时,则可通过array_merge对已有筛选条件和本方法返回的条件进行合并使用
	 * 若已存在原有筛选条件,且$type=string时,则可通过字符串形式将原有筛选条件和本方法返回的条件进行合并使用
	 */
	public function filterMember($uid, $contain=true, $alias='', $where=false, $type='array') {
		$Member = M('Member');
		
		$return = array();
		
		if (!validateExtend($uid, 'NUMBER')) {
			return $return;
		}
		
		//判断$alias是否含英文小数点符号,无则自动添加
		if (!empty($alias) && !preg_match('/\.$/', $alias)) {
			$alias = $alias. '.';
		}
		
		//获取$uid对应的级别
		$map['id'] = array('eq', $uid);
		$member_info = $Member->where($map)->field('level')->find();
		if (!$member_info) {
			return $return;
		}
		
		//拼装筛选条件
		$map_sql['level'] = array(array('lt', 5), array('egt', $member_info['level']), 'and');
		$map_sql['repath'] = array('like', "%,".$uid.",%");
		$member_sql_info = M('Member')
			->where($map_sql)
			->order('relevel desc')
			->field('id')
			->select();
		if ($member_sql_info) {
			$repath_list = array();
			$id_list = array();
			
			$repath_string = '';
			$id_string = '';
			
			//对传入的where做兼容处理
			if ($where) {
				if (isset($where[$alias.'repath'])) {
					$repath_list[] = $where[$alias.'repath'];
					$repath_string .= " {$where[$alias.'repath']} ";
				}
				if (isset($where[$alias.'id'])) {
					$id_list[] = $where[$alias.'id'];
					$id_string .= " {$where[$alias.'id']} ";
				}
			}
			
			foreach ($member_sql_info as $k=>$v) {
				$repath_string = empty($repath_string) ? '' : $repath_string. " and ";
				$id_string = empty($id_string) ? '' : $id_string. " and ";
				
				$repath_list[] = array('notlike', "%,".$v['id'].",%");
				$id_list[] = array('neq', $v['id']);
				
				$repath_string .= " {$alias}repath not like '%{$v['id']}%'";
				$id_string .= " {$alias}id <> {$v['id']} ";
			}
			
			//对$type进行处理
			switch ($type) {
				case 'array':
					$repath_list[] = 'and';
					$id_list[] = 'and';
					
					$return[$alias.'repath'] = $repath_list;
					
					//不包含同级处理
					if (!$contain) {
						$return[$alias.'id'] = $id_list;
					}
					break;
				case 'string':
					$return[$alias.'repath'] = " ({$repath_string}) ";
					
					if (!$contain) {
						$return[$alias.'id'] = " ({$id_string}) ";
					}
					break;
				default:
					return $return;
			}
		}
		
		return $return;
	}
	
	/**
	 * 计算买家应得积分、商家应得积分、平台应得毛利润的封装
	 * @param $money 兑换产生的毛利润金额
	 */
	public function getPointsToMM($money=0) {
		$Parameter = M('Parameter', 'g_');
		
		$data = array('merchant'=>0, 'member'=>0, 'profits'=>$money);
			
		if (empty($money)) {
			return $data;
		}
		if (!validateExtend($money, 'MONEY')) {
			return $data;
		}
		
		//拉取商家和买家应得积分的配置参数
		$parameter_info = $Parameter->where('id=1')->field('points_merchant,points_member')->find();
		
		$data['merchant'] = $money*$parameter_info['points_merchant'];
		$data['member'] = $money*$parameter_info['points_member'];
		
		return $data;
	}
	
	/**
	 * 参数安全过滤初始化
	 */
	public function safeFilterInit() {
		$this->_get = I('get.');
		$this->_post = I('post.');
		$this->_cookie = I('cookie.');
		
		$filter_default = array();
		
		$this->safeFilter($filter_default, 'get');
		$this->safeFilter($filter_default, 'post');
		$this->safeFilter($filter_default, 'cookie');
	}
	
	/**
	 * 执行参数安全过滤
	 *
	 */
	private function safeFilter(&$arr=array(), $type='get') {
	
		$get = ($type=='get' && count($arr)>0) ? $arr : $this->_get;
		$post = ($type=='post' && count($arr)>0) ? $arr : $this->_post;
		$cookie = ($type=='cookie' && count($arr)>0) ? $arr : $this->_cookie;
	
		//GET
		if ($type=='get' && count($get)>0) {
			foreach ($get as $k=>$v) {
				if (!is_array($v)) {
					$get[$k] = preg_replace("/{$this->_get_filter}/is", "", $v);
				} else {
					$get[$k] = $this->safeFilter($v, 'get');
				}
			}
			
			$_GET = $get;
			return $get;
		}

		 //POST
		if ($type=='post' && count($post)>0) {
			foreach ($post as $k=>$v) {
				if (!is_array($v)) {
					$post[$k] = preg_replace("/{$this->_post_filter}/is", "", $v);
				} else {
					$post[$k] = $this->safeFilter($v, 'post');
				}
			}
			
			$_POST = $post;
			return $post;
		}
		
		//COOKIE
		if ($type=='cookie' && count($cookie)>0) {
			foreach ($cookie as $k=>$v) {
				if (!is_array($v)) {
					$cookie[$k] = preg_replace("/{$this->_cookie_filter}/is", "", $v);
				} else {
					$cookie[$k] = $this->safeFilter($v, 'cookie');
				}
			}
			
			$_COOKIE = $cookie;
			return $cookie;
		}
	}
	
	/**
	 * 路由过滤
	 */
	public function routeFilter() {
		$route_filter = C('ROUTE_FILTER');
		$return = array('msg'=>'', 'code'=>300, 'result'=>(object)array());
		
		if (!is_array($route_filter)) {
			return;
		}
		
		//获取当前domain后两组信息
		$domain = $_SERVER['HTTP_HOST'];
		$domain_host = explode('.', $domain);
		$domain_host = $domain_host[count($domain_host)-2].'.'.$domain_host[count($domain_host)-1];
		$domain_prefix = str_replace('.'.$domain_host, '', $domain);
		
		//获取当前路由地址信息(module/controller/action)
		$current_route = MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME;
		
		if (array_key_exists($domain_prefix, $route_filter)) {
			$allow = $route_filter[$domain_prefix]['ALLOW'];
			$deny = $route_filter[$domain_prefix]['DENY'];
			
			//允许规则
			if ($allow) {
				$is_allow = false;
				
				foreach ($allow as $k=>$v) {
					if (strpos($current_route, $v) !== false) {
						$is_allow = true;
						break;
					}
				}
				
				if (!$is_allow) {
					$return['msg'] = '无访问权限';
					$this->ajaxReturn($return);
				}
			}
			
			//禁止规则
			if ($deny) {
				$is_deny = false;
			
				foreach ($deny as $k=>$v) {
					if (strpos($current_route, $v) !== false) {
						$is_deny = true;
						break;
					}
				}
			
				if ($is_deny) {
					$return['msg'] = '无访问权限';
					$this->ajaxReturn($return);
				}
			}
		}
	}
	
	/**
	 * 重写基础控制器display方法
	 */
	public function display($templateFile='',$charset='',$contentType='',$content='',$prefix='') {
		$finance_manager = C('FINANCE_MANAGER');
		if (!empty($finance_manager) && in_array(session('admin_loginname'), $finance_manager)) { 
			parent::theme(C('FINANCE_THEME'));
		}
		parent::display($templateFile,$charset,$contentType,$content,$prefix);
	}
	
}
?>