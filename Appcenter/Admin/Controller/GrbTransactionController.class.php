<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 丰谷宝流通兑换审核管理 
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Common\Controller\AuthController;
use Common\Model\Sys\GrbTradeModel;
use V4\Model\Currency;
use V4\Model\CurrencyAction;
use V4\Model\AccountModel;
use V4\Model\AccountRecordModel;
use V4\Model\WalletModel;

class GrbTransactionController extends AuthController {
	
	private $GrbTransactionModel;
	
	public function __construct($request='') {
		parent::__construct(false, $request);
		
		$this->GrbTransactionModel = new GrbTradeModel();
	}
	
	/**
	 * 流通兑换管理
	 * 
	 * @param string $tpl 模板名称
	 * 
	 * @param string $wallet_type 钱包类型(澳交所:AJS, 中网云:ZWY, SLU:SLU),默认ZWY
	 */
	public function index($tpl='') {
		$userid = $this->get['userid'];
		$time_min = empty($this->get['time_min']) ? strtotime(date('Y-m-01 00:00:00')) : strtotime($this->get['time_min']);
		$time_max = empty($this->get['time_max']) ? time() : strtotime($this->get['time_max'].' 23:59:59');
		$date_type = $this->get['date_type'];
		$status = empty($this->get['status']) ? 0 : $this->get['status'];
		$page = empty($this->get['p']) ? 1 : $this->get['p'];
		$page = $this->get['action']=='exportData' ? false : $page;
		$wallet_type = empty($this->get['wallet_type']) ? 'ZWY' : $this->get['wallet_type'];
		$type = empty($this->get['type']) ? ($wallet_type=='AJS' ? 'AGX' : $wallet_type) : $this->get['type'];
		
		$this->assign('wallet_type', $wallet_type);
		$this->assign('type', $type);
		
		$where = '';
		
		if (date('Ym', $time_min) != date('Ym', $time_max)) {
			$this->error('日期筛选只能为同一个月');
		}
		
		if (!empty($userid)) {
			$map_member['loginname'] = array('eq', $userid);
			$map_member['username'] = array('eq', $userid);
			$map_member['truename'] = array('eq', $userid);
			$map_member['nickname'] = array('eq', $userid);
			$map_member['_logic'] = 'or';
			$member_info = M('Member')->where($map_member)->field('id')->find();
			if ($member_info) {
				$where['user_id'] = array('eq', $member_info['id']);
			}
		}
		$where[$date_type] = array(array('egt', $time_min), array('elt', $time_max), 'and');
		if (validateExtend($status, 'NUMBER')) {
			$where['status'] = array('eq', $status);
		}
		
		//钱包类型
		$where['type'] = ['eq', $type];
		
		$data = $this->GrbTransactionModel->getList('*', $page, 20, $where);
		
		//整合数据
		$list = $data['list'];
		$export_data = []; //导出数据
		foreach ($list as $k=>$v) {
			$list[$k]['user'] = M('Member')->where('id='.$v['user_id'])->field('loginname,username')->find();
			
			//字段对应中文转换
			$list[$k]['status_cn'] = C('FIELD_CONFIG')['trade']['status'][$v['status']];
			$list[$k]['type_cn'] = C('FIELD_CONFIG')['trade']['type'][$v['type']];
			
			//组装导出数据
			$export_data[] = [
				$v['id'],
				$list[$k]['user']['loginname'].'['.$list[$k]['user']['username'].']',
				$v['amount'],
				$v['fee'],
				$v['wallet_address'],
				$v['txid'],
				$v['remark'],
				$list[$k]['status_cn'],
				$v['balance'],
				empty($v['addtime']) ? '' : date('Y-m-d H:i:s', $v['addtime']),
				empty($v['uptime']) ? '' : date('Y-m-d H:i:s', $v['uptime'])
			];
		}
		$this->assign('list', $list);
		
		$this->Page($data['paginator']['totalRows'], $data['paginator']['everyPage'], $this->get);
		
		//手续费总计
		$map_fee = [
			'status' => ['eq', 3],
			'type' => ['eq', $type],
			'addtime' => ['egt', '1560960000'], //从6/20开始统计
		];
		$fee_score = $this->GrbTransactionModel->getInfo('sum(fee) fee', $map_fee);
		$this->assign('fee_score', $fee_score['fee']);
		
		//导出功能
		if ($this->get['action'] == 'exportData') {
			$head_array = array('ID', '用户', '金额', '手续费', '钱包地址', '兑换号', '备注', '状态', '停留余额', '提交时间', '处理时间');
			$this->exportData($head_array, $export_data);
		}
		
		if ($tpl == '') {
			//获取当前主钱包余额
			$WalletModel = new WalletModel($wallet_type);
			$balance = $WalletModel->getBalance();
			$this->assign('balance', $balance);
			
			//获取主钱包地址
			$this->assign('master_wallet_address', $WalletModel->getMasterWalletAddress());
			
			$this->display();
		} else {
			$this->display($tpl);
		}
	}
	
	/**
	 * 流通兑换转账操作 [加入队列]
	 */
	public function tradeAction() {
		$checkId = $this->post['id'];
		$checkList = is_array( $checkId ) ? $checkId : array( $checkId );
		
		$data = [
			'is_queue' => 1,
			'status' => 4
		];
		$map = [
			'id' => ['in', implode(',', $checkList)]
		];
		
		//SLU转账直接操作状态为成功,目前不进行实质性转入第三方操作
// 		$trade_info = M('Trade')->where('id='.$checkList[0])->find();
// 		if ($trade_info['type'] == 'SLU') {
// 			$data = [
// 				'status' => 3
// 			];
// 		}
		
		$result = M('Trade')->where($map)->save($data);
		
		if ($result === false) {
			exit('加入队列失败');
		}
		
		exit;
	}
	
	/**
	 * 流通兑换退款操作
	 */
	public function tradeBack() {
		set_time_limit(0);
		ignore_user_abort(false);
		
		$checkId = $this->post['id'];
		$checkList = is_array( $checkId ) ? $checkId : array( $checkId );
		$remark = $this->post['remark'];
		
		foreach ($checkList as $k=>$v) {
			$data = M('Trade')->where('id='.$v)->find();
			if (!$data) {
				continue;
			}
			
			//判断兑换状态
			if ($data['status'] != '0') {
				continue;
			}
			
			$data['remark'] = $remark;
			
			$this->tradeBackCore($data);
		}
		
		exit;
	}
	
	/**
	 * 退款封装
	 * 
	 * @param array $data 兑换相关数据
	 * @param boolean $return 是否返回true/false,默认false
	 */
	private function tradeBackCore($data, $return=false) {
		M()->startTrans();
		
		//添加明细
		$AccountRecordModel = new AccountRecordModel();
		$record_attach = json_encode(['address'=>$data['wallet_address'], 'fee'=>$data['fee'], 'type'=>$data['type']], JSON_UNESCAPED_UNICODE);
		$result1 = $AccountRecordModel->add($data['user_id'], Currency::GoldCoin, CurrencyAction::GoldCoinTradeRefund, $data['amount'], $record_attach);
		
		//更改兑换状态
		$data_trade = [
			'status' => 1,
			'remark' => $data['remark']
		];
		$result2 = M('Trade')->where('id='.$data['id'])->save($data_trade);
		
		if (!$result1 || $result1==null || !$result2) {
			M()->rollback();
			if ($return) {
				return false;
			}
		}
		
		M()->commit();
		
		if ($return) {
			return true;
		}
	}
	
	/**
	 * 流通兑换查看
	 */
	public function view() {
		$this->index('view');
	}
	
}
?>