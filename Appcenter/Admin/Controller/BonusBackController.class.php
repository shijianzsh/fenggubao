<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 回购管理
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Common\Controller\AuthController;
use V4\Model\BonusBackModel;
use V4\Model\AccountRecordModel;
use V4\Model\Currency;
use V4\Model\CurrencyAction;
use V4\Model\AccountModel;

class BonusBackController extends AuthController {
	
	public function __construct() {
		parent::__construct();
		
		C('TOKEN_ON', false);
	}
	
	public function index() {
		$BonusBackModel = new BonusBackModel();
		
		$where = ' 1 ';
		
		$page = $this->get['p']>1 ? $this->get['p'] : 1;
		$userid = $this->get['userid'];
		$time_min = $this->get['time_min'];
		$time_max = $this->get['time_max'];
		$status = empty($this->get['status']) ? 2 : $this->get['status'];
		
		//回购申请状态类型
		$status_config = C('FIELD_CONFIG.buyback')['buyback_status'];
		$this->assign('status_config', $status_config);
		
		if (!empty($userid) && !validateExtend($userid, 'MOBILE')) {
			$this->error('用户账号格式有误');
		}
		
		if (!empty($userid)) {
			$member_info = M('Member')->where("loginname=".$userid)->field('id,loginname,nickname')->find();
			if (!$member_info) {
				$this->error('搜索用户不存在');
			}
			$where .= " and user_id={$member_info['id']} ";
		}
		
		if (!empty($time_min)) {
			$where .= " and buyback_addtime>=".strtotime($time_min);
		}
		if (!empty($time_max)) {
			$where .= " and buyback_addtime<=".strtotime($time_max.' 23:59:59');
		}
		
		if (!array_key_exists($status, $status_config)) {
			$this->error('审核状态参数非法');
		} else {
			$where .= " and buyback_status={$status} ";
		}
		
		$data = $BonusBackModel->getList('*', $page, 20, $where);
		
		$list = $data['list'];
		foreach ($list as $k=>$v) {
			//用户账户信息
			if (empty($userid)) {
				$member_info = M('Member')->where('id='.$v['user_id'])->field('loginname,nickname')->find();
			}
			$list[$k]['loginname'] = $member_info['loginname'];
			$list[$k]['nickname'] = $member_info['nickname'];
		}
		$this->assign('list', $list);
		
		$this->Page($data['paginator']['totalPage']*$data['paginator']['everyPage'], $data['paginator']['everyPage'], $this->get);
		
		$this->display();
	}
	
	//审核回购申请
	public function review() {
		$this->error('回购申请审核功能已停用');
		
		$BonusBackModel = new BonusBackModel();
		$AccountRecordModel = new AccountRecordModel();
		$AccountModel = new AccountModel();
		
		$id_list = $this->post['id'];
		$status = $this->post['status'];
		
		if (empty($id_list)) {
			exit('请选择待操作的回购申请信息');
		}
		
		$status_config = C('FIELD_CONFIG.buyback')['buyback_status'];
		if (!array_key_exists($status, $status_config)) {
			exit('操作类型非法');
		}
		
		//通用条件
		$data = ['buyback_status' => 0];
		$log_action = '';
		switch ($status) {
			case '1': //驳回
				$data['buyback_status'] = 1;
				$log_action = '驳回';
				break;
			case '2': //审核通过
				$data['buyback_status'] = 2;
				$log_action = '审核通过';
				break;
			default:
				exit('该类型暂不支持操作');
		}
		
		//循环处理回购申请
		foreach ($id_list as $k=>$id) {
			M()->startTrans();
			
			$where = " buyback_id={$id} ";
			
			//获取回购申请信息
			$buyback_info = $BonusBackModel->getInfo('*', $where);
			if (!$buyback_info) {
				exit("编号[ID={$id}]的回购申请不存在");
			}
			
			//检测回购申请当前状态
			if ($buyback_info['buyback_status'] != '0') {
				exit("编号[ID={$id}]的回购申请已经处理过，请勿重复操作");
			}
			
			//检测回购金额是否正常
			if (!validateExtend($buyback_info['buyback_amount'], 'MONEY') || $buyback_info['buyback_amount']<=0) {
				exit("编号[ID={$id}]的回购申请回购金额异常");
			}
			
			//判断该回购申请用户是否存在已审核通过的回购申请，存在则不予处理
			$buyback_exists = $BonusBackModel->getInfo('buyback_id', "user_id={$buyback_info['user_id']} and buyback_status=2");
			if ($buyback_exists) {
				exit("编号[ID={$id}]的用户已存在审核通过的回购申请");
			}
			
			//获取用户当前丰收点余额
			$account_info = $AccountModel->getItemByUserId($buyback_info['user_id'], 'account_bonus_balance');
			if (!$account_info) {
				exit("编号[ID={$id}]的用户资金信息不存在");
			}
			
			$result = $BonusBackModel->save($data, $where);
			if ($result === false) {
				M()->rollback();
				exit("编号[ID={$id}]的审核操作失败:ERROR:001");
			} else {
				if ($status == '2') {
					//同步给用户充值,同时清零丰收点
					$ac1 = $AccountRecordModel->add($buyback_info['user_id'], Currency::Cash, CurrencyAction::CashBuyBack, $buyback_info['buyback_amount'], '', CurrencyAction::getLabel(CurrencyAction::CashBuyBack));
					$ac2 = $AccountRecordModel->add($buyback_info['user_id'], Currency::Bonus, CurrencyAction::BonusBuyBack, '-'.$account_info['account_bonus_balance'], '', CurrencyAction::getLabel(CurrencyAction::BonusBuyBack));
					if ($ac1 === false || $ac2 === false) {
						M()->rollback();
						exit("编号[ID={$id}]的审核操作失败:ERROR:002");
					}
				}
				M()->commit();
			}
			
			$this->logWrite("[{$log_action}]编号[ID={$id}]的回购申请");
		}
	
		exit('');
	}
	
}
?>