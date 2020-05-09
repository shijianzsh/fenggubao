<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 第三方管理
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Common\Controller\AuthController;
use V4\Model\AccountRecordModel;
use V4\Model\CurrencyAction;
use V4\Model\FinanceModel;
use V4\Model\Currency;

class ThirdController extends AuthController {
	
	public function __construct() {
		parent::__construct();
		
		C('TOKEN_ON', false);
	}
	
	/**
	 * 第三方互转
	 */
	public function transfer() {
		$AccountRecordModel = new AccountRecordModel();
		$FinanceModel = new FinanceModel();
		
		$userid = $this->get['userid'];
		$time_min = $this->get['time_min'];
		$time_max = $this->get['time_max'];
		$type = empty($this->get['type']) ? 2 : $this->get['type'];
		$inout = empty($this->get['inout']) ? 2 : $this->get['inout'];
		$page = $this->get['p']>0 ? $this->get['p'] : 1;
		$page = $this->get['action']=='exportData' ? false : $page;
		
		
		if (!empty($userid) && !validateExtend($userid, 'MOBILE') && !validateExtend($userid, 'CHS') && !validateExtend($userid, 'USERNAME')) {
			$this->error('用户账号格式有误');
		}
		if (!empty($type) && !validateExtend($type, 'NUMBER')) {
			$this->error('类型格式有误');
		}
		if (!empty($inout) && !validateExtend($inout, 'NUMBER')) {
			$this->error('操作格式有误');
		}
		
		$where = '';
		$where_fee = '';
		$month = date('Ym');
		$currency = Currency::GoldCoin;
		$where_action = CurrencyAction::GoldCoinTransferToGRB;
		
		//指定账号筛选
		if (!empty($userid)) {
			$map_member['loginname'] = array('eq', $userid);
			$map_member['nickname'] = array('eq', $userid);
			$map_member['username'] = array('eq', $userid);
			$map_member['_logic'] = 'or';
			$userid = M('Member')->where($map_member)->getField('id');
			if (empty($userid)) {
				$this->error('该用户账号不存在');
			}
			$where .= " and user_id={$userid} ";
		}
		
		//时间段筛选
		if (!empty($time_min)) {
			$where .= " and record_addtime>=".strtotime($time_min);
		}
		if (!empty($time_max)) {
			$where .= " and record_addtime<".strtotime("+1 days", strtotime($time_max));
		}
		if (!empty($time_min) && !empty($time_max)) {
			if (date('Ym', strtotime($time_min)) != date('Ym', strtotime($time_max))) {
				$this->error('只能筛选同一个月的记录');
			}
			$month = date('Ym', strtotime($time_min));
		}
		
		//货币类型+操作筛选
		switch ($type) {
			case '2':
				$currency = Currency::GoldCoin;
				if ($inout == 1) {
				} elseif ($inout == 2) {
					$where_action = CurrencyAction::GoldCoinTransferToGRB;
				}
				break;
		}
		
		$where .= " and record_action=".$where_action;
		
		//数据列表
		$data = $AccountRecordModel->getListByAllUser($currency, $month, $page, 20, $where);
		
		$list = $data['list'];
		$export_data = []; //导出数据
		foreach ($list as $k=>$v) {
			$attach = json_decode($v['record_attach'], true);
			
			//第三方信息
			$list[$k]['third_info'] = $attach;
			
			//用户账号信息
			$member_info = M('Member')->where('id='.$v['user_id'])->field('loginname,nickname,username')->find();
			$list[$k]['loginname'] = $member_info['loginname'];
			$list[$k]['nickname'] = $member_info['nickname'];
			$list[$k]['username'] = $member_info['username'];
			
			$export_data[] = [
				$v['loginname'].'['.$v['username'].']',
				"地址:{$v['third_info']['address']},兑换费率:{$v['third_info']['fee']}%",
				$v['record_amount'],
				$v['record_remark'],
				date('Y-m-d H:i:s', $v['record_addtime'])
			];
		}
		$this->assign('list', $list);
		
		//导出功能
		if ($this->get['action'] == 'exportData') {
			$head_array = array('用户账号', '第三方账号', '转账金额', '转账说明', '操作时间');
			$this->exportData($head_array, $export_data);
		}
		
		$this->Page($data['paginator']['totalPage']*$data['paginator']['everyPage'], $data['paginator']['everyPage'], $this->get);
		
		//总转账金额
		$total = $AccountRecordModel->getFieldsValues($currency, $month, "sum(record_amount) total", " 1 ".$where);
		$total = $total['total'];
		$this->assign('total', $total);
		
		$this->display();
	}
	
}
?>