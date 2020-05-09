<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 登陆管理
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Common\Controller\PushController;
use Think\Verify;

class LoginController extends PushController {
	
	/**
	 * 自动识别登录状态跳转
	 */
	public function index() {
		$sess_auth = session(C('AUTH_SESSION'));
		$sess_auth ? $this->success(L('login_successed'),U('Index/index')) : $this->login();
	}
	
	/**
	 * ie阻断页面
	 */
	public function ie() {
		$this->display();
	}
	
	/**
	 * 登录渲染
	 */
	public function login() {
		C('TOKEN_ON', false);
		
		$Auth = D('Manager');
		$login_status = $Auth->checkExist();
		if (!$login_status['error']) {
			$this->index();
			exit;
		}
		
		//检测当前账户是否是由 超级管理员/小管理员 模拟登陆的,如果是则自动以 超管/小管理员 身份登录后台
		if (session('admin_super_login') !== null && session('admin_super_login') != '') {
			$manager_info = M('Manager')
				->alias('man')
				->join('join __MEMBER__ mem ON mem.id=man.uid')
				->where('man.id='.session('admin_super_login'))
				->field('mem.loginname')
				->find();
			if ($manager_info) {
				$member_info = M('Member')->where("loginname='".$manager_info['username']."'")->field('password')->find();
				if ($member_info) {
					$login_data = array(
						'username' => $manager_info['username'],
						'password' => $member_info['password'],
					);
					$login_status = $Auth->checkExist($login_data);
					if ($login_status['error'] === false) {
						session('admin_super_login', null);
						$this->index();
						exit;
					}
				}
			}
		}
		
		//获取登录失败计数
		$login_fail_count = session('admin_mid_'.session('admin_mid').'_login_fail_count');
		$login_fail_count = $login_fail_count===null ? 0 : intval($login_fail_count);
		$this->assign('login_fail_count', $login_fail_count);
		$this->assign('login_fail_count_max', C('LOGIN_FIAL_COUNT_MAX'));
		
		$this->display('login');
	}
	
	/**
	 * 登录动作
	 */
	public function loginAction() {
		$Auth = D('Manager');
		
		//验证验证码
		$Verify = new Verify();
		if (!$Verify->check(I('post.verify_code'))) {
			$this->error(L('verify_code').L('is_error'), U('Login/login'));
		}
		
		$member = $Auth->checkExist();
		if (!$member['error']) {
			$this->logWrite('后台登录');
			$this->success(L('login_success'), U('Index/index'));
		} else {
			$this->error(L('account').L('or').L('password').L('is_error').L('or').L('account').L('is_disable'), U('Login/login'));
		}
	}
	
	/**
	 * 登出
	 * @param $notice boolean 是否提示后跳转,默认提示后跳转
	 */
	public function logout($notice=true) {
		session(C('AUTH_SESSION'), null);
		cookie(C('AUTH_COOKIE'), null);
		
		//清除登录失败计数
		session('admin_mid_'.session('admin_mid').'_login_fail_count', null);
		
		//兼容老版session
		session('admin_id', null);
		session('admin_level', null);
		session('admin_loginname', null);
		session('admin_nickname', null);
		session('admin_username', null);
		session('admin_img', null);
		session('admin_group', null);
		session('admin_group_id', null);
		session('admin_mid', null);
		session('admin_module_list', null);
		
		session('session_safe_password', null);
		session('session_three_safe_password', null);
		
		//删除其他session,排除超管模拟登陆服务中心或区域合伙人用户
		$sess_super = session('admin_super_login');
		if (empty($sess_super)) {
			session('admin_super_login', null);
		}
		
		if (!$notice) {
			redirect(U('Login/login'));
			exit;
		}
		$this->success(L('logout_success'), U('Login/login'));
	}
	
	/**
	 * 异步登录
	 */
	public function loginAsyn() {
		$Auth = D('Manager');
		
		//START:特殊处理个别帐号可以非手机号登录(此类帐号统一强制不记录cookie)
		$member_login_no_mobile = C('MEMBER_LOGIN_NO_MOBILE');
		//第一步:检测是否存在用特殊处理的帐号对应的手机号来登录,存在则禁止,强制使用对应的特殊帐号
		foreach ($member_login_no_mobile as $k=>$v) {
			if (I('post.username') == $v) {
				exit('该帐号已禁止使用用户名登录');
			}
		}
		if (array_key_exists(I('post.username'), $member_login_no_mobile)) {
			$_POST['username'] = $member_login_no_mobile[I('post.username')];
			unset($_POST['remember']);
		}
		//END
		
		$verify_code = I('post.verify_code');
		$username = I('post.username');
		
		//获取loginname对应的mid
		$map_member['loginname'] = array('eq', $username);
		$member_info = M('Member')->where($map_member)->field('id,loginname')->find();

		if (!$member_info) {
			exit('帐号不存在');
		}

		$mid = $member_info['id'];
		
		//获取登录失败计数
		$login_fail_count = session('admin_mid_'.$mid.'_login_fail_count');
		$login_fail_count = $login_fail_count===null ? 0 : intval($login_fail_count);
		
		//验证文字/短信验证码
		if ($login_fail_count >= C('LOGIN_FIAL_COUNT_MAX')) {
			//获取短信验证码
			$map_phonecode['loginname'] = array('eq', $member_info['loginname']);
			$map_phonecode['code'] = array('eq', $verify_code);
			$map_phonecode['status'] = array('eq', 0);
			$map_phonecode['post_time'] = array('EGT', time()-C('SMS_ENABLE_TIME'));
			$phonecode_info = M('Phonecode')->where($map_phonecode)->field('id')->find();
			if (!$phonecode_info) {
				exit('短信验证码错误');
			}
		} else {
			$Verify = new Verify();
			if (!$Verify->check($verify_code)) {
				exit('验证码有误');
			}
		}

		$member = $Auth->checkExist(false, true);
		if (!$member['error']) {
			//清除登录失败计数
			session('admin_mid_'.$mid.'_login_fail_count', null);
			
			//判断当前用户是否为小管理员身份
			$is_small_super = false;
			$role_must_list = C('ROLE_MUST_LIST');
			$session_group_id = strpos(session('admin_group_id'), ',') ? explode(',', session('admin_group_id')) : array(session('admin_group_id'));
			//小管理员身份ID数组
			$small_super_group_id = array();
			foreach ($session_group_id as $k=>$v) {
				if (!in_array($v, $role_must_list)) {
					$small_super_group_id[] = $v;
				}
			}
			if (count($small_super_group_id)>0) {
				$is_small_super = true;
			}
			
			//发送成功登录系统短信通知(只针对超级管理员和小管理员)
//			if ($mid=='1' || $is_small_super) {
//				$curl_data = array(
//					'id' => $mid,
//					'template' => 'login_action',
//					'msg' => date('Y-m-d H:i:s')
//				);
//				$result = $this->curl(C('SMS_SEND_URL'), 'post', $curl_data, '', 2000);
//				if (empty($result)) {
//					exit('短信发送失败');
//				} elseif ($result!='1') {
//					exit($result);
//				}
//			}
			
			//删除已使用的验证码
			if (is_array($map_phonecode)) {
				M('Phonecode')->where($map_phonecode)->delete();
			}
			
			$this->logWrite('后台登录');
			exit('');
		} else {
			//当密码输入有误时,计数入session,便于当同一用户计数超过n次时,强制输入短信验证码登录功能
			if ($member['data'] == '密码不正确') {
				$login_fail_count++;
				session('admin_mid_'.$mid.'_login_fail_count', $login_fail_count);
			}
			
			//检测登录失败计数
//			if ($login_fail_count >= C('LOGIN_FIAL_COUNT_MAX')) {
//				//如果$login_fail_count>C('LOGIN_FIAL_COUNT_MAX'),则视为已经为当前IP的该用户发送过短信验证码,不再重复发送
//				if ($login_fail_count==C('LOGIN_FIAL_COUNT_MAX')) {
//					//发送短信验证码
//					$curl_data = array(
//						'id' => $mid,
//						'template' => 'login_warning',
//						'msg' => ''
//					);
//					$result = $this->curl(C('SMS_SEND_URL'), 'post', $curl_data, '', 2000);
//					if (empty($result)) {
//						exit('短信发送失败');
//					} elseif ($result!='1') {
//						exit($result);
//					}
//				}
//
//				exit('LOGIN_FIAL_WARNING');
//			}
			
			exit($member['data']);
		}
	}
	
}
?>