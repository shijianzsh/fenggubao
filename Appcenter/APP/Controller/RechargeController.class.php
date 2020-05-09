<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 充值相关接口
// +----------------------------------------------------------------------
namespace APP\Controller;
use Common\Controller\ApiController;
use V4\Model\OrderModel;
use V4\Model\PaymentMethod;
use V4\Model\Currency;
use V4\Model\AccountRecordModel;
use V4\Model\CurrencyAction;

class RechargeController extends ApiController {
	
	/**
	 * 银行卡充值提交图片审核接口-废弃
	 * @param uid 会员id
	 * @param amount 充值金额，需为正整数
	 * @param img 上传待审核的图片，3M以内，jpg，png，jpeg格式
	 */
	public function bankcard_recharge() {
		$this->myApiPrint('暂未开放');
	}
	
	/**
	 * 银行卡转账充值审核函数-废弃
	 *
	 * 供管理平台使用
	 *
	 * @param id 会员充值表 id，单个或数组
	 * @param check_status 审核位[1:审核并充值,2审核未通过] (单个或数组，个数与id对应，每一批里，可以同时包含过审及不过审的情况)
	 * @param checker_id 审核人id
	 */
	public function bankcard_recharge_check() {
		$ids = I('post.id');
		$checker_id = I('post.checker_id');
		$check_status = I('post.check_status');
		
		if (empty($ids) || empty($checker_id)) {
			$this->myApiPrint('id(s) or checker ID is empty', 300);
		}
	
		$in['id'] = array('in', $ids);
		$member_recharge = M('member_recharge');
		$prepare = $member_recharge->where($in)->getField('id,uid,amount,check');
		$prepare = array_values($prepare);  // 重置key
	
		$af = 0;
	
		foreach ($prepare as $k => $v) {
			if ($v['check'] == 0 && $check_status == 1) {
				M()->startTrans();
				$member = M('member');
				$user = $member->lock(true)->where('id = '. $v['uid'])->find();
				if ($user) {
	
					// 插入明细
					$insert_data['uid'] = $v['uid'];
					$insert_data['from_uid'] = 1;
					$insert_data['type'] = 10;
					$insert_data['money'] = $v['amount'];
					$insert_data['exchange'] = 1;
					$insert_data['is_pay'] = 1;
					$insert_data['post_time'] = time();
					$insert_data['pay_time'] = time();
					M('g_bonus', null)->add($insert_data);
	
					$data['cash'] = array('exp', 'cash+' . $v['amount']);
					$affect = $member->where('id=' . $v['uid'])->save($data);
					if ($affect === false) {
						M()->rollback();
						$temp['check'] = 3;
					} else {
						$temp['check'] = 1;
						$af++;
					}
	
					$temp['check'] = $check_status;
					$temp['check_time'] = time();
					$temp['checker_id'] = $checker_id;
					$member_recharge->where('id='.$v['id'])->save($temp);
				}
				
				M()->commit();
			} elseif ($v['check'] == 0 && $check_status[$k] != 1) {
				$af++;
				$temp['check'] = 2;
				$temp['check_time'] = time();
				$temp['checker_id'] = $checker_id;
				$member_recharge->where('id='.$v['id'])->save($temp);
			}
		}
		
		if ($af) {
			$this->myApiPrint($af.' record(s) updated', 400);
		} else {
			$this->myApiPrint('no record(s) updated', 300);
		}
	}
	
	/**
	 * 银行卡转账充值审核函数-废弃
	 *
	 * 供管理平台使用
	 *
	 * @param id 会员充值表 id，单个或数组
	 * @param check_status 审核位 1审核并充值2审核未通过
	 * @param checker_id 审核人id
	 */
	public function bankcard_recharge_check_plus() {
		$ids = I('post.id');
		$checker_id = I('post.checker_id');
		$check_status = I('post.check_status');
		
		if (empty($ids) || empty($checker_id)) {
			$this->myApiPrint('id(s) or checker ID is empty', 300);
		}
	
		if (count($ids) != count($check_status)) {
			$this->myApiPrint('count(ids) is not equal to count(check_status)', 300);
		}
	
		$in['id'] = array('in', $ids);
		$member_recharge = M('member_recharge');
		$prepare = $member_recharge->where($in)->getField('id,uid,amount,check');
		$prepare = array_values($prepare);  // 重置key
	
		$af = 0;
	
		foreach ($prepare as $k => $v) {
			if ($v['check'] == 0 && $check_status[$k] == 1) {
				M()->startTrans();
				$member = M('member');
				$user = $member->lock(true)->where('id = '. $v['uid'])->find();
				if ($user) {
					$data['cash'] = array('exp', 'cash+' . $v['amount']);
					$affect = $member->where('id=' . $v['uid'])->save($data);
					if ($affect === false) {
						M()->rollback();
						$temp['check'] = 3;
					} else {
						$temp['check'] = 1;
						$af++;
					}
					$temp['check_time'] = time();
					$temp['checker_id'] = $checker_id;
					$member_recharge->where('id='.$v['id'])->save($temp);
	
					// 插入明细
					$insert_data['uid'] = 1;
					$insert_data['from_uid'] = $v['uid'];
					$insert_data['type'] = 10;
					$insert_data['money'] = $v['amount'];
					$insert_data['exchange'] = 1;
					$insert_data['is_pay'] = 1;
					$insert_data['post_time'] = time();
					$insert_data['pay_time'] = time();
					M('g_bonus', null)->add($insert_data);
				}
				
				M()->commit();
			} elseif ($v['check'] == 0 && $check_status[$k] != 1) {
				$af++;
				$temp['check'] = 2;
				$temp['check_time'] = time();
				$temp['checker_id'] = $checker_id;
				$member_recharge->where('id='.$v['id'])->save($temp);
			}
		}
		
		if ($af) {
			$this->myApiPrint($af.' record(s) updated', 400);
		} else {
			$this->myApiPrint('no record(s) updated', 300);
		}
	}
	
	/**
	 * 银行卡充值出现3时手动充值的方法-废弃
	 *
	 * @param id check值为3的记录, 单个或数组
	 * @param checker_id 审核人id
	 */
	public function bankcard_recharge_manu() {
		$ids = I('post.id');
		$checker_id = I('post.checker_id');
		
		if (empty($ids) || empty($checker_id)) {
			$this->myApiPrint('id(s) or checker ID is empty', 300);
		}
	
		$in['id'] = array('in', $ids);
		$member_recharge = M('member_recharge');
		$prepare = $member_recharge->where($in)->getField('id,uid,amount,check');
		$prepare = array_values($prepare);
	
		$af = 0;
	
		foreach ($prepare as $k => $v) {
			if ($v['check'] == 3) {
				M()->startTrans();
				$member = M('member');
				$user = $member->lock(true)->where('id = ' . $v['uid'])->find();
				if ($user) {
					$data['cash'] = array('exp', 'cash+' . $v['amount']);
					$affect = $member->where('id=' . $v['uid'])->save($data);
					if ($affect === false) {
						M()->rollback();
					} else {
						$temp['check'] = 1;
						$temp['check_time'] = time();
						$temp['checker_id'] = $checker_id;
						$member_recharge->where('id=' . $v['id'])->save($temp);
						$af++;
	
						// 插入明细
						$insert_data['uid'] = $v['uid'];
						$insert_data['from_uid'] = 1;
						$insert_data['type'] = 10;
						$insert_data['money'] = $v['amount'];
						$insert_data['exchange'] = 1;
						$insert_data['is_pay'] = 1;
						$insert_data['post_time'] = time();
						$insert_data['pay_time'] = time();
						M('g_bonus', null)->add($insert_data);
					}
				}
				
				M()->commit();
			}
		}
		
		if ($af) {
			$this->myApiPrint($af.' record(s) updated', 400);
		} else {
			$this->myApiPrint('no record(s) updated', 300);
		}
	}
	
	/**
	 * 第三方充值 (支付宝,微信)
	 *
	 * @param uid  充值用户id
	 * @param amount 充值金额
	 * @param pay_type 支付类型 1支付宝2微信
	 * @param subject 订单标题
	 * @param body 订单描述
	 */
	public function thirdparty_recharge() {
        //$this->myApiPrint('暂停充值');
		$uid = I('post.uid');
		$amount = I('post.amount');
		$pay_type = I('post.pay_type') ? I('post.pay_type') : 1 ;
		$currency = I('post.currency') ? I('post.currency') : 1 ; //1=现金积分； 2=注册币. 对应order表num字段
		
		//限制只允许充值现金积分
		$currency = 1;
	    
		//验证参数
		verify_thirdparty_recharge($uid, $amount, $pay_type);
		
		//判断每日限额
		$third_all_max_amount = $this->CFG['third_all_max_amount'];
		if ($amount >= $third_all_max_amount) {
			$this->myApiPrint('微信充值金额已超今日限额');
		} else {
			$user_third_all_amount_by_today = getUserThirdAmountByToday($uid, $pay_type);
			if (($user_third_all_amount_by_today + $amount) > $third_all_max_amount) {
				$this->myApiPrint('微信充值金额已超今日限额');
			}
		}
	    
		M()->startTrans();
		if($pay_type == 2){
// 			$this->myApiPrint('');
			//1.生产订单
			$om = new OrderModel();
			$order_no = $om->create($uid, $amount, PaymentMethod::Wechat, 0, 0, '微信充值', '', 0, 0, 3, $currency);
			
			//2.生成订单签名
			$sign_str = $om->getWxpaySign($order_no, $amount,  'Notify/recharge');
			if ($order_no && $sign_str) {
				M()->commit();
				$returndata = $om->format_return('提交成功',400, $sign_str);
				$this->ajaxReturn($returndata);
			} else {
				M()->rollback();
				$this->myApiPrint('return null',300);
			}
		}else{
			$this->myApiPrint('');
			//1.支付宝生产订单
			$om = new OrderModel();
			$order_no = $om->create($uid, $amount, PaymentMethod::Alipay, 0, 0, '支付宝充值', '', 0, 0, 3, $currency);
				
			//2.生成订单签名
			$sign_str = $om->getAlipaySign($order_no, $amount,  'Notify/recharge');
			if ($order_no && $sign_str) {
				M()->commit();
				$returndata = $om->format_return('提交成功',400, $sign_str);
				$this->ajaxReturn($returndata);
			} else {
				M()->rollback();
				$this->myApiPrint('return null',300);
			}
		}
	}
	
	/**
	 * 充值明细-废弃
	 * @param uid 会员ID
	 * @param page 分页
	 */
	public function recharge_details(){
		$uid = intval(I('post.uid'));
		$post_page = intval(I('post.page'));
		$post_page = $post_page>0 ? $post_page*10 : 0;
		
		$map['uid'] = array('eq', $uid);
		$where['_complex'] = $map;
		$where['bonus_type'] = array('in',array(10));
		
		$totalPage = M('view_member_bonus')->where($where)->count();
		$everyPage = '10';
		$pageString = $post_page.','.$everyPage;
		$bonus = M('view_member_bonus')->where($where)->limit($pageString)->order('post_time desc')->select();
		if (empty($bonus)) {
			$this->myApiPrint('对不起，你还没有任何数据！',400,$bonus);
		}
		
		$data=$bonus;
		 
		$bonus_type = C('BONUS_TYPE');
		$is_bool = C('BONUS_IS_BOOL');
		 
		foreach ($data as $k=>$v) {
			$bonus_tag = '+';
			$data[$k]['bonus_type'] = $bonus_type[$v['bonus_type']];
			$data[$k]['bonus'] = $bonus_tag.$v['bonus'];
			$data[$k]['is_bool'] = $is_bool['common'][$v['is_bool']];
	
			$data[$k]['from_nickname'] = $v['uid']==$uid ? $v['from_nickname'] : $v['nickname'];
			$data[$k]['img'] = $v['uid']==$uid ? $v['img'] : $v['from_img'];
			
			unset($data[$k]['from_img']);
			unset($data[$k]['from_uid']);
		}
		header("content-type:text/html;charset=utf-8");
		$this->myApiPrint('查询成功！',400,$data);
	}
	
	/**
	 * 提现明细-废弃
	 * @param uid 会员ID
	 * @param page 分页
	 */
	public function withdraw_cash_details(){
		$uid = intval(I('post.uid'));
		$post_page = intval(I('post.page'));
		$post_page = $post_page>0 ? $post_page*10 : 0;
		
		$map['uid']= array('eq', $uid);
		$where['_complex'] = $map;
		$where['bonus_type']=array('in',array(13,55));
		
		$totalPage = M('view_member_bonus')->where($where)->count();
		$everyPage = '10';
		$pageString = $post_page.','.$everyPage;
		$bonus = M('view_member_bonus')->where($where)->limit($pageString)->order('post_time desc')->select();
		if (empty($bonus)) {
			$this->myApiPrint('对不起，你还没有任何数据！',400,$bonus);
		}
		
		$data=$bonus;
		 
		$bonus_type = C('BONUS_TYPE');
		$is_bool = C('BONUS_IS_BOOL');
		 
		foreach ($data as $k=>$v) {
			$bonus_tag = '-';
			$data[$k]['bonus_type'] = $bonus_type[$v['bonus_type']];
			$data[$k]['bonus'] = $bonus_tag.$v['bonus'];
	
			$withdata = M('withdraw_cash')->field('status,failure_code')->where('serial_num='.$v['serial_num'])->find();
			switch ($withdata['status']) {
				case '0':
					$data[$k]['is_bool'] = '审核中';
					break;
				case 'S':
					$data[$k]['is_bool'] = '已完成';
					break;
				case 'F':
					$data[$k]['is_bool'] ='已退款，原因：'. $withdata['failure_code'];
					break;
				case 'TS':
					$data[$k]['is_bool'] = '已退款，原因：审核不通过';
					break;
				case 'TF':
					$data[$k]['is_bool'] = '退款失败，未知，请联系管理员';
					break;
			}
	
			$data[$k]['from_nickname'] = $v['uid']==$uid ? $v['from_nickname'] : $v['nickname'];
			$data[$k]['img'] = $v['uid']==$uid ? $v['img'] : $v['from_img'];
			
			unset($data[$k]['from_img']);
			unset($data[$k]['from_uid']);
		}
		
		header("content-type:text/html;charset=utf-8");
		$this->myApiPrint('查询成功！',400,$data);
	}
	
}
?>