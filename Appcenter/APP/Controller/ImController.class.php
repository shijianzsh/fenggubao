<?php
// +----------------------------------------------------------------------
// | IM预留接口
// +----------------------------------------------------------------------
namespace APP\Controller;
use Common\Controller\ApiController;

class ImController extends ApiController {
	
	private $user;
	private $report_url = 'https://report.im.jpush.cn/v2';
	private $api_url = 'https://api.im.jpush.cn';
	private $history;
	
	private $robot_uid = [1]; //视为机器人的用户ID (当target_id在此配置数组中时,则采用智能机器人规则直接回复智能匹配的消息)
	
	/**
	 * 初始化配置参数
	 */
	private function initConfig() {
		$config = [
			'appkey' => C('PUSH_CONFIG.APP_KEY'),
			'timestamp' => $this->getMillisecond(),
			'random_str' => randCode(10, 0),
			'key' => C('PUSH_CONFIG.MASTER_SECRET'),
		];
		
		$signature = md5(http_build_query($config));
		unset($config['key']);
		
		$config['signature'] = $signature;
		$config['flag'] = 1;
		
		return $config;
	}
	
	/**
	 * 身份鉴权
	 */
	private function checkPurview() {
		/*
		$uid = session('app_common_data_uid');
		
		if (empty($this->app_common_data['uid'])) {
			if (empty($uid) || $uid != $this->get['current_id']) {
				$this->imPrint('身份鉴权失败');
			}
		} else {
			if ($this->app_common_data['uid'] != $this->get['current_id']) {
				$this->imPrint('身份鉴权失败');
			} else {
				session('admin_common_data_uid', $this->app_common_data['uid']);
			}
		}
		*/
	}
	
	/**
	 * 身份鉴权(后台系统使用)
	 * @param int $current_id 当前用户ID
	 */
	private function checkPurviewLocal($current_id) {
		if (empty($current_id)) {
			exit('<center>登录超时或无权限，请重新登录</center>');
		}
	}
	
	/**
	 * 后台会话列表 [AJAX]
	 */
	public function chatList() {
		$current_id = session('admin_mid');
		
		$this->checkPurviewLocal($current_id);
		
		$this->setCurrentUser($current_id);
	    
	    $config = $this->initConfig();
	    $this->assign('config', $config);
	    
	    //用户信息
	    $user = json_encode($this->user, JSON_UNESCAPED_UNICODE);
	    $this->assign('user', $user);
	    
	    $html = $this->fetch();
	    
	    echo $html;
		exit;
	}
	
	/**
	 * 后台聊天对话
	 */
	public function chat() {
		$current_id = session('admin_mid');
		
		$this->checkPurviewLocal($current_id);
		
		$this->setCurrentUser($current_id);
		$this->setTargetUser($this->get['target_id']);
		
		$config = $this->initConfig();
		$this->assign('config', $config);
		
		//是否为智能机器人模式
		$chat_model = 'normal';
		if (in_array($this->get['target_id'], $this->robot_uid)) {
			$chat_model = 'robot';
		}
		$this->assign('chat_model', $chat_model);
		
		//用户信息
		$user = json_encode($this->user, JSON_UNESCAPED_UNICODE);
		$this->assign('user', $user);
		
		//获取聊天记录
		$this->getHistoryMessages();
		$this->assign('history', json_encode($this->history, JSON_UNESCAPED_UNICODE));
		
		//获取商品信息
		$product_info = $this->getProductInfo($this->get['product_id']);
		$this->assign('product_info', json_encode($product_info,JSON_UNESCAPED_UNICODE));
		
		$this->display('index');
	}

	/**
	 * 初始化
	 * 
	 * @param int $current_id 当前用户ID
	 * @param int $target_id 目标用户ID
	 */
	public function index() {
		$this->checkPurview();
		
		$this->setCurrentUser($this->get['current_id']);
		$this->setTargetUser($this->get['target_id']);
		
		$config = $this->initConfig();
		$this->assign('config', $config);
		
		//是否为智能机器人模式
		$chat_model = 'normal';
		if (in_array($this->get['target_id'], $this->robot_uid)) {
			$chat_model = 'robot';
		}
		$this->assign('chat_model', $chat_model);
		
		//用户信息
		$user = json_encode($this->user, JSON_UNESCAPED_UNICODE);
		$this->assign('user', $user);
		
		//获取聊天记录
		$this->getHistoryMessages();
		$this->assign('history', json_encode($this->history, JSON_UNESCAPED_UNICODE));
		
		//获取商品信息
		$product_info = $this->getProductInfo($this->get['product_id']);
		$this->assign('product_info', json_encode($product_info,JSON_UNESCAPED_UNICODE));
		
		$this->display();
	}
	
	/**
	 * 用户会话列表
	 * 
	 * @param int $current_id 当前用户ID
	 */
	public function dialog() {
		$this->checkPurview();
		
	    $this->setCurrentUser($this->get['current_id']);
	    
	    $config = $this->initConfig();
	    $this->assign('config', $config);
	    
	    //用户信息
	    $user = json_encode($this->user, JSON_UNESCAPED_UNICODE);
	    $this->assign('user', $user);
	    
	    $this->display();
	}
	
	/**
	 * 设置当前用户信息
	 *
	 * @param int $current_id 当前账号ID
	 */
	private function setCurrentUser($current_id) {
		if (!validateExtend($current_id, 'NUMBER')) {
			$this->imPrint('用户账号格式有误');
		}
	
		//获取用户信息
		$Member = M('Member');
		$current_info = $Member->where('id='.$current_id)->field('loginname,nickname,img')->find();
	
		if (!$current_info) {
			$this->imPrint('用户账号信息不存在');
		}
	
		$user = [
		    'user_id' => $current_id,
			'username' => $current_info['loginname'],
			'nickname' => $current_info['nickname'],
			'password' => md5($current_info['loginname'].C('APP_CONFIG.SECRET_KEY')),
			'avatar'   => U('/', '','', preg_replace('/^(http:\/\/|https:\/\/)?/', '', C('DEVICE_CONFIG')[C('DEVICE_CONFIG_DEFAULT')]['attach_domain'][0])).$current_info['img']
		];
		
		$this->user['current'] = $user;
	}
	
	/**
	 * 设置目标用户信息
	 *
	 * @param int $target_id 目标用户ID
	 */
	private function setTargetUser($target_id) {
	    if (!validateExtend($target_id, 'NUMBER')) {
	        $this->imPrint('用户账号格式有误');
	    }
	
	    //获取用户信息
	    $Member = M('Member');
	    $target_info = $Member
	    	->alias('mem')
	    	->join('left join __STORE__ sto ON sto.uid=mem.id and sto.status=0 and sto.manage_status=1')
	    	->where('mem.id='.$target_id)
	    	->field('mem.loginname,mem.nickname,mem.img
	    			,sto.store_name,sto.store_img')
	    	->find();
	
	    if (!$target_info) {
	        $this->imPrint('用户账号信息不存在');
	    }
	    
	    $storeimg = preg_match('/^http/', $target_info['store_img']) ? $target_info['store_img'] : U('/', '','', preg_replace('/^(http:\/\/|https:\/\/)?/', '', C('DEVICE_CONFIG')[C('DEVICE_CONFIG_DEFAULT')]['attach_domain'][0])).$target_info['store_img'];
	    
	    $user = [
	        'user_id' => $target_id,
            'username' => $target_info['loginname'],
            'nickname' => $target_info['nickname'],
            'password' => md5($target_info['loginname'].C('APP_CONFIG.SECRET_KEY')),
            'avatar'   => U('/', '','', preg_replace('/^(http:\/\/|https:\/\/)?/', '', C('DEVICE_CONFIG')[C('DEVICE_CONFIG_DEFAULT')]['attach_domain'][0])).$target_info['img'],
            'storename' => $target_info['store_name'],
            'storeimg' => $storeimg,
	    ];
	    
	    $this->user['target'] = $user;
	}
	
	/**
	 * 获取用户信息
	 * 
	 * @internal AJAX
	 * 
	 * @param string $username 用户账号
	 * 
	 * @return array
	 */
	public function getUserInfo() {
	    $data = ['error'=>'', 'data'=>''];
	    
	    $Member = M('Member');
	    
	    $username = $this->post['username'];
	    
	    if (!validateExtend($username, 'MOBILE')) {
	        $data['error'] = '参数格式有误';
	    } else {
	        $user_info = $Member
	        	->alias('mem')
	        	->join('left join __STORE__ sto ON sto.uid=mem.id')
	        	->where("mem.loginname='{$username}'")
		        ->field('mem.id,mem.img
		        		,sto.store_name,sto.store_img')
		        ->find();
	        if($user_info){
	        	if (!empty($user_info['img'])) {
	        		$user_info['img'] = U('/', '','', preg_replace('/^(http:\/\/|https:\/\/)?/', '', C('DEVICE_CONFIG')[C('DEVICE_CONFIG_DEFAULT')]['attach_domain'][0])).$user_info['img'];
	        	}
	        	if (!empty($user_info['store_img']) && !preg_match('/^http/', $user_info['store_img'])) {
	        		$user_info['store_img'] = U('/', '','', preg_replace('/^(http:\/\/|https:\/\/)?/', '', C('DEVICE_CONFIG')[C('DEVICE_CONFIG_DEFAULT')]['attach_domain'][0])).$user_info['store_img'];
	        	}
	            $data['data'] = $user_info;
	        } else {
	            //$data['error'] = '用户信息不存在';
	            $data['data'] = ''; //此处值需调用者针对性处理
	        }
	    }
	    
	    exit(json_encode($data, JSON_UNESCAPED_UNICODE));
	}
	
	/**
	 * 获取当前用户与目标用户的历史聊天记录
	 * 
	 */
	public function getHistoryMessages() {
	    $begin_time = urlencode(date('Y-m-d H:i:s', time()-3600*24));
	    $end_time = urlencode(date('Y-m-d H:i:s'));
	    
	    $this->getHistoryMessagesBase($this->user['current']['username'], $this->user['target']['username'], $begin_time, $end_time);
	    $this->getHistoryMessagesBase($this->user['target']['username'], $this->user['current']['username'], $begin_time, $end_time);
	    
	    ksort($this->history);
	}
	
	/**
	 * 获取聊天记录
	 * 
	 * @param string $current_username 当前用户账号
	 * @param string $target_username 目标用户账号
	 * @param string $begin_time 开始时间
	 * @param string $end_time 结束时间
	 * 
	 * @return array
	 */
	private function getHistoryMessagesBase($current_username, $target_username, $begin_time, $end_time) {
	    $return = [];
	    
	    //获取当前用户与目标用户的聊天记录
	    $messages = $this->restApi($this->report_url."/users/{$current_username}/messages?count=100&begin_time={$begin_time}&end_time={$end_time}");
	    
	    //筛选中与目标用户的记录
	    $history = json_decode($messages, true);
	    if ($history['total'] > 0){
	        foreach ($history['messages'] as $k=>$v) {
	            if ($v['target_id'] == $target_username) {
	                
	                //聊天记录类型
	                $msg_type = $v['msg_type'];
	                switch ($msg_type) {
	                    case 'text':
	                        $msg = $v['msg_body']['text'];
	                        break;
	                    case 'image':
	                        $msg_info = $this->restApi($this->api_url.'/v1/resource?mediaId='.$v['msg_body']['media_id']);
	                        $msg_info = json_decode($msg_info, true);
	                        $msg = '<img onclick="look(this)" src="'.$msg_info['url'].'">';
	                        break;
	                }
	                
	                $this->history[$v['create_time']] = [
	                    'current_username' => $current_username,
	                    'target_username' => $target_username,
	                    'time' => date('Y-m-d H:i:s', substr($v['create_time'],0,10)),
	                    'msg' => $msg,
	                    'msgid' => $v['msgid'],
	                ];
	            }
	        }
	    }
	}
	
	/**
	 * 敏感词检测
	 * 
	 * @internal AJAX
	 * 
	 * @param string content 信息内容
	 */
	public function checkWord() {
	    $content = $this->post['content'];
	    
	    if (empty($content) || preg_match('/^[[[(.*)]]]$/', $content)) { //对参数体不予检测
	        $result['count'] = 0;
	    } else {
    	    $url = "http://www.ju1.cn/index.php/Index/add.html";
    	    $data = [
    	        'mgtype' => 0, //敏感词
    	        'ty_wj_type' => 1, //通用违禁词
    	        'mz_wj_type' => 0, //美妆违禁词
    	        'xw_wj_type' => 0, //新闻违禁词
    	        'text' => $content,
    	    ];
    	    $data = $this->curl($url, 'post', $data, '', 5);
    	    
    	    if (preg_match('/<<</', $data)) {
        	    $data = explode('<<<', $data);
        	     
        	    $result = [
        	        'count' => intval($data[1]),
        	        'content' => $data[0],
        	    ];
    	    } else {
    	        $result['count'] = 0;
    	    }
	    }
	     
	    exit(json_encode($result, JSON_UNESCAPED_UNICODE));
	}
	
	/**
	 * 获取指定用户在线状态
	 * 
	 * @param string $username 用户账号
	 */
	public function getUserState($username) {
	    if (!validateExtend($username, 'MOBILE')) {
	        return false;
	    }
	    
	    $messages = $this->restApi($this->api_url."/v1/users/{$username}/userstat");
	    if ($messages === false) {
	        return false;
	    }
	    
	    $messages = json_decode($messages, true);
	    return $messages['online'];
	}
	
	/**
	 * 当发送消息给目标用户，且目标用户不在线时，推送消息给用户
	 * 
	 * @internal AJAX
	 * 
	 * @param string $receiver_user_id 接收者用户ID
	 * @param string $msg 消息内容
	 * @param string $sender_user_id 发送者用户ID
	 */
	public function pushAlertToTarget() {
	    $receiver_user_id = $this->post['receiver_user_id'];
	    $msg = $this->post['msg'];
	    $sender_user_id = $this->post['sender_user_id'];
	    
	    if (validateExtend($receiver_user_id, 'NUMBER') && !empty($msg)) {
	        //获取用户账号
	        $username = M('Member')->where('id='.$receiver_user_id)->getField('loginname');
	        $online = $this->getUserState($username);
	        if ($online === false) {
	            //获取目标用户推送id
	            $registration_id = M('login')
	                ->alias('log')
	                ->where('log.uid='.$receiver_user_id)
	                ->order('log.id desc')
	                ->getField('registration_id', true);
	            //设置参数
	            $ids['all'] = $registration_id;
	            $content = '您有新消息：'.$msg;
	            //附加参数
	            $extraparams['target'] = 'messages';
	            $extraparams['url'] = U('Im/index/current_id/'.$receiver_user_id.'/target_id/'.$sender_user_id, '', '', true);
	            //推送
	            $this->push($ids, $content, $extraparams);
	        }
	    }
	}
	
	/**
	 * 获取商品信息
	 * 
	 * @param int $product_id 商品ID
	 */
	private function getProductInfo($product_id) {		
		if (!validateExtend($product_id, 'NUMBER')) {
			return;
		}
		
		$product_info = M('Product')
			->alias('pro')
			->join('join __PRODUCT_AFFILIATE__ aff ON aff.product_id=pro.id')
			->join('join __BLOCK__ blo ON blo.block_id=aff.block_id')
			->field('pro.id,pro.name,pro.img
					,blo.block_name')
			->where('pro.id='.$product_id)
			->find();
		if (!$product_info) {
			return;
		}
		$product_info['img'] = U('/', '','', preg_replace('/^(http:\/\/|https:\/\/)?/', '', C('DEVICE_CONFIG')[C('DEVICE_CONFIG_DEFAULT')]['attach_domain'][0])).$product_info['img'];
		
		return $product_info;
	}
	
	/**
	 * 消息参数体转换 [AJAX]
	 * 
	 * @param string msg 消息内容
	 * 
	 * @return string
	 */
	public function msgTransf() {
		$msg = $this->post['msg'];
				
		if (empty($msg)) {
			exit;
		}
		
		$msg = strtolower(substr($msg, 3, -3));
		$data = explode(':', $msg);
		
		$result = [
			'tag' => $data[0],
			'data' => ''
		];
		switch ($data[0]) {
			case 'product_id':
				$product_info = $this->getProductInfo($data[1]);
				$result['data'] = $product_info;
				break;
			case 'emoji_id':
				$result['data'] = $data[1];
				break;
		}
		
		echo json_encode($result, JSON_UNESCAPED_UNICODE);
		exit;
	}
	
	/**
	 * 智能匹配机器人消息
	 */
	public function getRobotResponseMsg() {
		$msg = $this->post['msg'];
		
		Vendor("Scws.PSCWS4");
		$Scws = new \PSCWS4('utf8');
		$Scws->set_charset('utf-8');
		$Scws->set_dict(VENDOR_PATH. '/Scws/dict.utf8.xdb');
		$Scws->set_rule(VENDOR_PATH. '/Scws/etc/rules.utf8.ini');
		$Scws->set_ignore(true);
		$Scws->send_text($msg);
		$words = $Scws->get_tops();
		$Scws->close();
		
		$html = '';
		foreach ($words as $val) {
			$word = mb_convert_encoding($val['word'], 'utf-8');
			
			$map['flash_content'] = array('like', "%{$word}%");
			$map['flash_link'] = array('like', "%{$word}%");
			$map['_logic'] = 'or';
			$arr = M('FlashNews')->where($map)->field('flash_id,flash_content,h5_path')->select();
			foreach ($arr as $k=>$v) {
				$html .= <<<EOF
				<label onclick="getRobotResponseDetail('{$v['flash_content']}',{$v['flash_id']});" class="robot">{$v['flash_content']}</label>
EOF;
			}
		}
		
		$html = empty($html) ? '主银，很抱歉没能帮到您，再聊点其他的吧...' : $html;
		$html = '<div class="robot_list">'.$html.'</div>';
		
		echo $html;
		exit;
	}
	
	/**
	 * 获取智能匹配机器人消息详情
	 */
	public function getRobotResponseMsgDetail() {
		$flash_id = $this->post['flash_id'];
		
		if (!validateExtend($flash_id, 'NUMBER')) {
			exit('主银，你要的内容溜了哦...');
		}
		
		$content = M('FlashNews')->where('flash_id='.$flash_id)->getField('flash_link');
		if (!$content) {
			exit('主银，你要的内容溜了哦...');
		}
		
		$content = '<div class="robot_detail">'.htmlspecialchars_decode($content).'</div>';
		
		echo $content;
		exit;
	}
	
	/**
	 * REST API
	 * 
	 * @param string $url API地址
	 * 
	 * @return JSON
	 */
	private function restApi($url) {
	    if (empty($url)) {
	        return false;
	    }
	    
	    $auth = base64_encode(C('PUSH_CONFIG.APP_KEY').':'.C('PUSH_CONFIG.MASTER_SECRET'));
	    $header = [
	        "Authorization: Basic {$auth}",
	        'Content-Type: application/json',
	        'Connection: Keep-Alive'
	    ];
	    $messages = $this->curl($url, 'get', '', $header, 30);
	    
	    return $messages;
	}
	
	/**
	 * 统一输出
	 * 
	 * @param string $content 输出信息
	 */
	private function imPrint($content='') {
		header("content-type:text/html;carset=utf-8");
		echo $content;
		exit;
	}
	
	/**
	 * 获取毫秒级别的时间戳
	 */
	private static function getMillisecond() {
		//获取毫秒的时间戳
		$time = explode ( " ", microtime () );
		$time = $time[1] . ($time[0] * 1000);
		$time2 = explode( ".", $time );
		$time = $time2[0];
		return $time;
	}
	
}