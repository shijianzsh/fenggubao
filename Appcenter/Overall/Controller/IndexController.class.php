<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 系统通用功能管理
// +----------------------------------------------------------------------

namespace Overall\Controller;
use Common\Controller\AuthController;
use V4\Model\AccountRecordModel;
use V4\Model\Currency;
use V4\Model\AccountModel;
use V4\Model\AccountFinanceModel;
use V4\Model\WithdrawModel;
use V4\Model\CurrencyAction;

class IndexController extends AuthController {
	
	/**
	 * 会员密码修改
	 */
	public function passwordModify(){
		C('TOKEN_ON', false);
		
		//识别切换到账户明细
		$is_detail = $this->get['type']=='detail' ? true : false;
		if ($is_detail) {
			$this->detail();
			$this->display('detail');
			exit;
		}
		
		$id = session('admin_mid');
	
		if (!validateExtend($id, 'NUMBER')) {
			$this->error('参数格式有误');
		}
		
		$_info = M('member')->where("id=".$id)->find();
		if (!$_info) {
			$this->error('该会员已不存在');
		}
		$this->assign('info', $_info);
		$this->display();
	}
	
	/**
	 * 会员密码保存
	 */
	public function passwordSave(){
		C('TOKEN_ON', false);
		
		$data = I('post.data');
		$data = trimarray($data);
		
		if ($data['id'] == "") {
			$this->error ('数据错误');
		}
		if (!is_numeric($data['id'])) {
			$this->error ('数据错误');
		}
		
		$member = M("member");
	
		foreach ($data as $k=>$v) {
			if (empty($v)) {
				unset($data[$k]);
			}
		}
	
		if ($data['password'] != "") {
			if ($data['password'] != $data['repassword']) {
				$this->error('登陆密码和确认登陆密码不一致');
			}
			$data['password'] = md5($data['password']);
		} else {
			unset($data['password']);
		}
		if ($data['safe_password'] != "") {
			if ($data['safe_password'] != $data['resafe_password']) {
				$this->error('安全密码和确认安全密码不一致');
			}
			$data['safe_password'] = md5($data['safe_password']);
		} else {
			unset($data['safe_password']);
		}
	
		$zc = M('member')->save($data);
		if ($zc===false) {
			$this->error('修改失败');
		} else {
			$this->success('操作成功', '', false, '成功修改自己的个人资料');
		}
	}
	
	/**
	 * 账户明细
	 */
	public function detail() {
		$uid = session('admin_mid');
		
		$WithdrawModel = new WithdrawModel();
		
		$where = '';
		$type = 2; //收支类型(0支出,1收入,2全部)
		
		$page = $this->get['p']>0 ? $this->get['p'] : 1;
		$balance_type = $this->get['balance_type'];
		$start_time = $this->get['start_time'];
		$end_time = $this->get['end_time'];
		$trade_status = $this->get['trade_status'];
		$bonus_type = $this->get['bonus_type'];
		$currency_type = $this->get['member_cash'];
		
		if (empty($uid)) {
			$this->error('UID参数格式有误');
		}
		
		//对变量进行处理
		$start_time = !empty($start_time) ? strtotime($start_time.' 00:00:00') : $start_time;
		$end_time = !empty($end_time) ? strtotime($end_time.' 23:59:59') : $end_time;
		
		//针对用户账户明细[收入支出]筛选条件进行处理
		if ($balance_type == 'income') {
			$type = 1;
		} elseif ($balance_type == 'expense') {
			$type = 0;
		}
		
		//日期筛选
		$month = date('Ym');
		if (!empty($start_time)) {
			$where .= " and record_addtime>='{$start_time}' ";
			$month = date('Ym', $start_time);
		} else {
			$where .= " and record_addtime>='".strtotime(date('Ym').'01')."' ";
		}
		if (!empty($end_time)) {
			$where .= " and record_addtime<='{$end_time}' ";
			$month = date('Ym', $start_time);
		} else {
			$where .= " and record_addtime<='".strtotime(date('Ymd').' 23:59:59')."' ";
		}
		if (date('Ym', $start_time) != date('Ym', $end_time)) {
			$this->error('查询日期必须在同一个月');
		}
		
		
		//针对用户账户明细[收支类型]筛选条件进行处理
		if (!empty($bonus_type)) {
			$where .= " and record_action='{$bonus_type}' ";
		}
		
		//获取配置参数
		$parameter = M('parameter','g_')->where('id=1')->find();
		$this->assign('parameter', $parameter);
		
		$member_cash = empty($_GET['member_cash'])?'cash':$_GET['member_cash'];  //账户类型
		
		$currency = '';
		switch ($currency_type) {
			case 'goldcoin':
				$currency = Currency::GoldCoin;
				break;
			case 'colorcoin':
				$currency = Currency::ColorCoin;
				break;
			case 'points':
				$currency = Currency::Points;
				break;
			case 'bonus':
				$currency = Currency::Bonus;
				break;
			case 'enroll':
				$currency = Currency::Enroll;
				break;
			case 'credits':
				$currency = Currency::Credits;
				break;
			case 'supply':
				$currency = Currency::Supply;
				break;
			case 'enjoy':
			    $currency = Currency::Enjoy;
			    break;
			default:
				$currency = Currency::Cash;
		}
		
		$AccountRecord = new AccountRecordModel();
		$data = $AccountRecord->getPageList($uid, $currency, $month, $page, $type, 10, $where);
		
		$list = $data['list'];
		foreach ($list as $k=>$v) {
			$attach = json_decode($v['record_attach'], true);
			$attach_init = $AccountRecord->initAtach($attach, $currency, $month, $v['record_id'], $v['record_action']);
			$list[$k]['from_name'] = $attach_init['from_name'].(empty($attach_init['loginname']) ? '' : "[{$attach_init['loginname']}]");
			
			//对提现进行状态的进度处理
			if (($v['record_action'] == CurrencyAction::CashWithdraw || $v['record_action'] == CurrencyAction::CashWithdrawFee) && !empty($attach['serial_num'])) {
				$withdraw_status = $WithdrawModel->getStatus($attach['serial_num']);
				$list[$k]['record_remark'] .= "[{$withdraw_status}]";
			}
		}
		$this->assign("datalist", $list);
		
		$this->Page($data['paginator']['totalPage']*$data['paginator']['everyPage'], $data['paginator']['everyPage'], $this->get);
		
		//查询账户余额
		$AccountModel = new AccountModel();
		$account = $AccountModel->getItemByUserId($uid, $AccountModel->get5BalanceFields());
		$this->assign("account", $account);
		
		//查询用户信息
		$member_info = M('Member')->where('id='.$uid)->field('nickname,loginname')->find();
		$this->assign("member_info", $member_info);
		
		//累计现金币收益
		$AccountFinaceModel = new AccountFinanceModel();
		$account_finance_info = $AccountFinaceModel->getItemByUserId($uid, 'finance_total');
		$total_income = $account_finance_info['finance_total'];
		$this->assign('total_income', $total_income);
		
		$this->display();
	}
	
}
?>