<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 登录注册重设密码等相关接口 
// +----------------------------------------------------------------------
namespace APP\Controller;
use Common\Controller\ApiController;
use V4\Model\AccountModel;
use V4\Model\Currency;
use V4\Model\Tag;
use V4\Model\AccountRecordModel;
use V4\Model\CurrencyAction;
use V4\Model\ProcedureModel;

class LoginRegisterController extends ApiController {
	
	/**
	 * 用户注册
	 * @param recommer 推荐人账号
	 * @param truename 真实姓名
	 * @param password 密码
	 * @param phone_code 短信验证码
	 * @param phone 账号
	 * @param m_phone_code MD5验证码
	 */
	public function register() {
	    //注册地区限制
	    //$ipmsg = getIpAddr();
	    //regAddrFilter($ipmsg);
	    
		//判断IP是否属于大陆
		$is_china = getIpLocation()['is_china'];

		$data['loginname'] = I('post.phone');
		$data['username'] =  I('post.truename');
		$data['truename'] =  I('post.truename');
		$data['nickname'] =  I('post.truename');
		$data['reuserid'] =  I('post.recommer');
		$data['password'] =  I('post.password');
		$data['phone_code'] = I('post.phone_code');
		$m_phone_code = I('post.m_phone_code');

		if ($data['reuserid']=="" || $data['truename']=="" || $data['loginname']=="" || $data['password']=="") {
			$this->myApiPrint('输入有空值！');
		}
		
		//区代和省级不能作为推荐人
// 		$map_recommer_role_check['loginname'] = array('eq', $data['reuserid']);
// 		$map_recommer_role_check['role'] = array('in', '3,4');
// 		$recommer_role_check = M('Member')->where($map_recommer_role_check)->field('id')->find();
// 		if ($recommer_role_check) {
// 			$this->myApiPrint('推荐人不能是合伙人');
// 		}
		
		$uw1['loginname'] = $data['loginname'];
		$usernameisexists = M('member')->where($uw1)->find();
		if($usernameisexists){
			$this->myApiPrint('手机号：'.$data['loginname'].'已经被注册了！', 300);
		}
		if(!validateExtend($data['truename'], 'CHS')){
			//$this->myApiPrint('姓名只能输入中文', 300);
		}
		if (!validateExtend($data['loginname'], 'MOBILE') && $is_china == '1') {
			$this->myApiPrint('手机号码格式不对！');
		}
        
		if ($is_china == '1') {
			$mwhere['phone'] = $data['loginname'];
			$mwhere['status'] = 0;
			$mwhere['post_time'] = array('EGT', time()-c('SMS_ENABLE_TIME'));
			$mcode = M('phonecode')->where($mwhere)->order('id desc')->getField('code');
			if($mcode != $data['phone_code'] || strlen($data['phone_code']) < 4 || $mcode== ''){
				$this->myApiPrint('抱歉，你输入的验证码不正确！');
			}
		}
		
	    $wherekey['loginname'] = $data['reuserid'];
        //$wherekey['id'] = array('neq', 1);
        $recommer_check = M('member')->field('id,repath,relevel,is_lock')->where($wherekey)->find();
        if (empty($recommer_check)) {
            $this->myApiPrint('推荐人不存在！');
        }
        if ($recommer_check['is_lock']==1) {
            $this->myApiPrint('推荐人已被后台锁定！');
        }
		//开启事务
		$params = M('g_parameter', null)->find();

		M()->startTrans();
		$data['password'] = md5($data['password']);
		$data['reg_time'] = time();
		$data['reid'] = $recommer_check['id'];
		$add_uid = M('member')->add($data);

		if($add_uid === false){
			M()->rollback();
			$this->myApiPrint('注册失败！');
		}
		
		$result = M('member')->field('id uid,username,loginname,nickname,img,(select loginname from zc_member where id='.$recommer_check['id'].') as reloginname,level,store_flag, role')
			->where($uw1)
			->find();
		foreach ($result as $k=>$v){
			if(trim($v) == ''){
				$result[$k]='';
			}
		}
		
		//已使用验证码
		if ($is_china == '1') {
			M('phonecode')->where('phone = \''.$data['loginname'].'\'')->save(array('status'=>1));
		}
		
		//插入自动登录信息===================================
		//验证唯一标识码是否已存在
		$map_login['uid'] = $result['uid'];
		$map_login['sessionid'] = $this->app_common_data['sessionid'];
		$login_info = M('login')->where($map_login)->find();
		if(!$login_info){
			$data_login['version'] = $this->app_common_data['version'];
			$data_login['platform'] = $this->app_common_data['platform'];
			$data_login['sessionid'] = $this->app_common_data['sessionid'];
			$data_login['token'] = $this->app_common_data['api_token'];
			$data_login['uid'] = $result['uid'];
			$data_login['date_created'] = time();
			$data_login['last_updated'] = time();


            $data_login['registration_id'] = $this->app_common_data['registration_id'];
			if (isset($this->app_common_data['platform_version'])) {
				$data_login['platform_version'] = $this->app_common_data['platform_version'];
			}
			//if($data_login['registration_id'] != ''){
				//M()->rollback();
				//$this->myApiPrint('注册失败:请开启获取手机信息权限', 300);
                $login_id = M('login')->add($data_login);
                if ($login_id === false) {
                    M()->rollback();
                    $this->myApiPrint('登录失败:001', 300);
                }
			//}

		}
        //吊起存储过程
        $pm = new ProcedureModel();
        $res5 = $pm->execute('Event_register', $add_uid, '@error');
        if(!$res5){
            M()->rollback();
            $this->myApiPrint('注册失败');
        }

        M()->commit();
		//记录日志
		//$this->logWrite('app注册，IP:'.get_client_ip().'，tel:'.$data['loginname'], 1, false, $add_uid);
		
		$this->myApiPrint('恭喜您，注册成功！',400,$result);
	}
	
	/**
	 * 用户登录
	 */
	public function login() {
		$Login = M('Login');
		
		$username = I('post.username');
		$password = I('post.user_password');
		$wherekey['loginname'] = $username;
		$login_check = M('member')->field('reid,password')->where($wherekey)->find();
		if (empty($login_check)) {
			$this->myApiPrint('账号不存在！',300);
		}
		if (md5($password) != $login_check['password']) {
			$this->myApiPrint('密码不正确！',300);
		}
		$data = M('member')
			->field('id uid,username, loginname,nickname,email,img,level,store_flag, star,role')
			->where($wherekey)
			->find();
		foreach ($data as $k=>$v){
			if(trim($v) == ''){
				$data[$k]='';
			}
		}
		
		//验证唯一标识码是否已存在
		$map_login['uid'] = array('eq', $data['uid']);
		$map_login['sessionid'] = array('eq', $this->app_common_data['sessionid']);
		$login_info = $Login->where($map_login)->field('id')->find();
		
		//登录信息写入用户登录表
		$data_login = array();
		$data_login['registration_id'] = $this->app_common_data['registration_id'];
		if ($login_info) { //更新修改日期
			
			$data_login['last_updated'] = time();
			if (isset($this->app_common_data['platform_version'])) {
				$data_login['platform_version'] = $this->app_common_data['platform_version'];
			}
			
			if ($Login->where('id='.$login_info['id'])->save($data_login) === false) {
				$this->myApiPrint('登录失败:002', 300);
			}
			
		} else {
			
			//检测sessionid是否存在,并且uid非当前登录uid(此种情况一般是由于用户退出登录时调用退出登录接口操作失败造成sessionid对应数据未成功删除,目前在此先对其进行手动删除操作)
			unset($map_login['uid']);
			$sessionid_info = $Login->where($map_login)->field('id')->find();
			if ($sessionid_info) {
				if ($Login->where('id='.$sessionid_info['id'])->delete() === false) {
					$this->myApiPrint('登录失败,请重试', 300);
				}
			}
			
			//插入新数据
			$data_login['version'] = $this->app_common_data['version'];
			$data_login['platform'] = $this->app_common_data['platform'];
			$data_login['sessionid'] = $this->app_common_data['sessionid'];
			$data_login['token'] = $this->app_common_data['api_token'];
			$data_login['uid'] = $data['uid'];
			$data_login['date_created'] = time();
			$data_login['last_updated'] = time();
			if (isset($this->app_common_data['platform_version'])) {
				$data_login['platform_version'] = $this->app_common_data['platform_version'];
			}
			
			if (!$Login->create($data_login, '', true)) {
				$this->myApiPrint('登录失败:002'.$Login->getError(), 300);
			}
		    if(empty($this->app_common_data['registration_id']) || empty($this->app_common_data['platform'])){
                //$this->myApiPrint('登录失败:请开启获取手机信息权限', 300);
            }
                
            $login_id = $Login->add($data_login);
            if ($login_id === false) {
                $this->myApiPrint('登录失败:001', 300);
            }
			
		}
		
		//判断会员类型买单的时候是否显示‘责任消费’的字段
		if($data['level']==3 || $data['level']==4 || $data['star']==3 || $data['star']==5){
			$data['allowanceuser'] = 1;
		}else{
			$data['allowanceuser'] = 0;
		}
		//写入操作日志表
		$log_content = "{$data['loginname']}[{$data['nickname']}]使用[{$this->app_common_data['platform']}]终端设备登录".C('APP_TITLE')."APP客户端";
		$this->logWrite($log_content, '1', false, $data['uid']);
		
		$this->myApiPrint('登录成功！',400,$data);
	}
	
	public function findusername(){
		$username = I('post.username');
		if($username != ''){
			if(!validateExtend($username, 'STRING') || strlen($username) < 5){
				$this->myApiPrint('ID号格式不正确：至少5位数字或字母', 400, '1');
			}
			
			$where['username'] = $username;
			$u = M('member')->where($where)->find();
			if($u){
				$this->myApiPrint('用户名'.$username.'已经存在', 400, '1');
			}else{
				$this->myApiPrint('可以注册', 400, '0');
			}
		}else{
			$this->myApiPrint('请输入用户名', 400, '0');
		}
	}
	
	/**
	 * 重设密码
	 */
	public function passwordSet(){
		$phone = I('post.phone');
		$phone_code = I('post.phone_code');
		$first_password = I('post.first_password');

		$wherekey['loginname'] = $phone;
		$user = M('member')->where($wherekey)->find();
		if (empty($user)) {
			$this->myApiPrint('此会员不存在！');
		}
		if ($phone=="" || $phone_code=="" ) {
			$this->myApiPrint('输入登录名和验证码不能为空！');
		}
		//验证短信验证码
		$mwhere['phone'] = $phone;
		$mwhere['status'] = 0;
		$mwhere['post_time'] = array('EGT', time()-c('SMS_ENABLE_TIME'));
		$mcode = M('phonecode')->where($mwhere)->order('id desc')->getField('code');
		if($mcode != $phone_code || strlen($phone_code) < 4 || $mcode== ''){
			$this->myApiPrint('抱歉，你输入的验证码不正确！');
		}
		
		M()->startTrans();
	
		$data['password'] = md5($first_password);
		$result = M('member')->where($wherekey)->save($data);
		if ($result !== false) {
			M()->commit();
			//删除验证码
			M('phonecode')->where('phone = \''.$phone.'\'')->delete();
			
			$this->myApiPrint('修改密码成功！',400);
		} else {
			M()->rollback();
			$this->myApiPrint('修改密码失败！');
		}
	}
	
}
?>