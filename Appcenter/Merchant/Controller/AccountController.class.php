<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 商家账户管理
// +----------------------------------------------------------------------

namespace Merchant\Controller;
use Common\Controller\AuthController;
use V4\Model\AccountRecordModel;
use V4\Model\Currency;
use V4\Model\AccountModel;
use V4\Model\AccountFinanceModel;
use V4\Model\WithdrawModel;
use V4\Model\CurrencyAction;

class AccountController extends AuthController {
	
	public function detail() {
		$where = '';
		$type = 2; //收支类型(0支出,1收入,2全部)
		
		$WithdrawModel = new WithdrawModel();
		
		//变量
		$uid = session('admin_mid');
		$page = $this->get['p']>0 ? $this->get['p'] : 1;
		$balance_type = $this->get['balance_type'];
		$start_time = $this->get['start_time'];
		$end_time = $this->get['end_time'];
		$trade_status = $this->get['trade_status'];
		$currency_type = $this->get['member_cash'];
		
		//验证变量
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
		
		//获取配置参数
		$parameter = M('parameter','g_')->where('id=1')->find();
		$this->assign('parameter', $parameter);

		$member_cash = empty($_GET['member_cash'])?'cash':$_GET['member_cash'];  //账户类型
		
		switch ($currency_type) {
			case 'goldcoin':
				$currency = Currency::GoldCoin;
				break;
			case 'points':
				$currency = Currency::Points;
				break;
			default:
				$currency = Currency::Cash;
		}
		
		$AccountRecord = new AccountRecordModel();
		$data = $AccountRecord->getPageList($uid, $currency, $month, $page, $type, 10, $where);
		
		$list = $data['list'];
		foreach ($list as $k=>$v) {
			$attach = json_decode($v['record_attach'], true);
			$attach = $AccountRecord->initAtach($attach, $currency, $month, $v['record_id'], $v['record_action']);
			$list[$k]['from_name'] = $attach['from_name'].(empty($attach['loginname']) ? '' : "[{$attach['loginname']}]");
			
			//对收支类型文字说明进行处理
			$list[$k]['record_remark'] = CurrencyAction::getLabel($v['record_action']);
			
			//对提现进行状态的进度处理
			if (($v['record_action'] == CurrencyAction::CashTixian || $v['record_action'] == CurrencyAction::CashTixianShouxufei) && !empty($attach['serial_num'])) {
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