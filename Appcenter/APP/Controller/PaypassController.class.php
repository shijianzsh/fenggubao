<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 支付密码相关接口
// +----------------------------------------------------------------------
namespace APP\Controller;
use Common\Controller\ApiController;

class PaypassController extends ApiController {
	
	/**
	 * 修改支付密码
	 * @param uid 用户ID
	 * @param password 登陆密码
	 * @param safepassword 支付密码
	 * @param phone_code 短信验证码
	 * @param m_phone_code MD5验证码
	 */
	public function setSafePassword() {
		$uid = I('post.uid');
		$password = I('post.password');
		$safepassword = I('post.safepassword');
		$phone_code = I('post.phone_code');
		$m_phone_code = I('post.m_phone_code');
	
		if (!validateExtend($uid, 'NUMBER')) {
			$this->myApiPrint('用户ID格式有误');
		}
		if (empty($safepassword)) {
			$this->myApiPrint('支付密码不能为空！');
		}
		
		//判断IP是否属于大陆
		$is_china = getIpLocation()['is_china'];
		
		$where['id'] = array('eq', $uid);
		$info = M('member')->where($where)->field('loginname')->find();
		if (count($info)==0) {
			$this->myApiPrint('账号错误！');
		}
		
		//验证短信验证码
		if ($is_china == '1') {
			$mwhere['phone'] = array('eq', $info['loginname']);
			$mwhere['status'] = array('eq', 0);
			$mwhere['post_time'] = array('EGT', time()-c('SMS_ENABLE_TIME'));
			$mcode = M('phonecode')->where($mwhere)->order('id desc')->field('code')->find();
			if (!$mcode || $mcode['code']!=$phone_code) {
				$this->myApiPrint('抱歉，你输入的验证码不正确！');
			}
		}
		
		$data['safe_password'] = md5($safepassword);
		$res = M('member')->where($where)->save($data);
		if($res !== false){
			//删除验证码
			if ($is_china == '1') {
				M('phonecode')->where('phone = \''.$info['loginname'].'\'')->delete();
			}
			$this->myApiPrint('支付密码设置成功',400);
		}else{
			$this->myApiPrint('密码修改失败！');
		}
	}
	
	/**
	 * 验证支付密码
	 * @param uid 用户ID
	 * @param safepassword 支付密码
	 */
	public function pass() {
		$where['id'] = I('post.uid');
		
		$em = M('member')->where($where)->getField('safe_password');
		if (empty($em) || $em==null || $em=="") {
			$this->myApiPrint('支付密码为空，请设置支付密码！',401);
		}
		
		$where['safe_password'] = md5(I('post.safepassword'));
		$count = M('member')->where($where)->count();
		if ($count==0) {
			$this->myApiPrint('支付密码错误！');
		} else {
			$this->myApiPrint('支付密码正确',400);
		}
	}
	
	
	/**
	 * 判断是否设置支付密码
	 * @param uid 用户ID
	 * @param safepassword 支付密码
	 */
	public function hasPasswd() {
		$id = intval(I('post.uid'));
		if($id < 1){
			$this->myApiPrint('参数错误！');
		}
		$where['id'] = $id;
		$em = M('member')->where($where)->getField('safe_password');
		if (empty($em) || $em==null || $em=="") {
			$this->myApiPrint('支付密码为空，请设置支付密码！',300);
		}else{
			$this->myApiPrint('没毛病',400);
		}
	}
	
}
?>