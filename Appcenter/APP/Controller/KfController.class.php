<?php
// +----------------------------------------------------------------------
// | 环信客户互动云接口
// +----------------------------------------------------------------------
namespace APP\Controller;
use Common\Controller\ApiController;

class KfController extends ApiController {
	
	private $EasemobChat;
	
	public function __construct( $request = '' ) {
		parent::__construct($request);
		
		Vendor("EasemobChat.EasemobChat#Api");
		
		$this->EasemobChat = new \EasemobChatApi();
	}
	
	/**
	 * 注册CHAT用户[单个]
	 * 
	 * @method POST
	 * 
	 * @param int $user_id 用户ID
	 */
	public function regSingleUser() {
		$user_id = $this->post['user_id'];
		
		if (!validateExtend($user_id, 'NUMBER')) {
			$this->myApiPrint('参数格式有误');
		}
		
		$user_info = M('Member')->where('id='.$user_id)->field('id,loginname,username')->find();
		if (!$user_info) {
			$this->myApiPrint('未查询到用户信息');
		}
		
		$pwd = md5($user_info['id'].$user_info['loginname']);
		
		$data_common = [
			'pwd' => $pwd,
			'token' => $this->returnToken()
		];
		
		//判断用户是否已存在
		$data = $this->getUserInfo($user_info['loginname']);
		if ($data) {
			$data = array_merge($data, $data_common);
			$this->myApiPrint('用户已存在', 400, $data);
		}
		
		$data = $this->EasemobChat->createUser($user_info['loginname'], $pwd);
		
		if (!empty($data['error'])) {
			die($data['error']);
		}
		
		$data = array_merge($data, $data_common);
		
		$this->myApiPrint('注册成功', 400, $data);
	}
	
	/**
	 * 注册CHAT用户[批量]
	 * 
	 * @method POST
	 * 
	 * @param array $user_id 用户ID数组
	 */
	public function regMultiUser() {
		$user_id_arr = $this->post['user_id'];
		
		if (!is_array($user_id_arr)) {
			$this->myApiPrint('参数格式有误');
		}
		
		if (count($user_id_arr) > 1) {
			$user_id_list = implode(',', $user_id_arr);
			$where = " id in ({$user_id_list}) ";
		} else {
			$where = " id={$user_id_arr[0]} ";
		}
		
		$user_list = M('Member')->where($where)->field('id,loginname,username')->find();
		if (!$user_list) {
			$this->myApiPrint('未查询到用户信息');
		}
		
		$body = [];
		foreach ($user_list as $k=>$v) {
			$body[] = [
				'username' => $v['loginname'],
				'password' => md5($v['id'].$v['loginname'])
			];
		}
		$data = $this->EasemobChat->createUsers($body);
		
		if (!empty($data['error'])) {
			die($data['error']);
		}
		
		$this->myApiPrint('批量注册成功', 400, $data);
	}
	
	/**
	 * 删除用户
	 * 
	 * @method POST
	 * 
	 * @param int $user_id 用户ID
	 */
	public function deleteUser() {
		$user_id = $this->post['user_id'];
		
		if (!validateExtend($user_id, 'NUMBER')) {
			$this->myApiPrint('参数格式有误');
		}
		
		$user_info = M('Member')->where('id='.$user_id)->field('id,loginname,username')->find();
		if (!$user_info) {
			$this->myApiPrint('未查询到用户信息');
		}
		
		$data = $this->EasemobChat->deleteUser($user_info['loginname']);
		
		if (!empty($data['error'])) {
			die($data['error']);
		}
		
		$this->myApiPrint('删除成功', 400, $data);
	}
	
	/**
	 * 获取token
	 */
	public function getToken() {
		$data['token'] = $this->returnToken();
		
		$this->myApiPrint('获取成功', 400, $data);
	}
	
	/**
	 * 返回Token
	 */
	private function returnToken() {
		$token = $this->EasemobChat->getToken();
		$token = str_replace('Authorization:Bearer ', '', $token);
		
		return $token;
	}
	
	/**
	 * 获取用户信息
	 */
	private function getUserInfo($loginname='') {
		if (!validateExtend($loginname, 'MOBILE')) {
			return false;
		}
		
		$data = $this->EasemobChat->getUser($loginname);
		
		if (!empty($data['error'])) {
			return false;
		}
		
		return $data;
	}
	
}