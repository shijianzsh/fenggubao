<?php
// +----------------------------------------------------------------------
// | 中网云转入申请审核管理 
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Common\Controller\AuthController;
use Common\Model\Sys\GrbTradeModel;
use V4\Model\Currency;
use V4\Model\CurrencyAction;
use V4\Model\AccountModel;
use V4\Model\AccountRecordModel;
use V4\Model\WalletModel;
use V4\Model\TransactionsModel;

class ZwyTransactionController extends AuthController {
	
	private $ZwyTransactionModel;
	
	public function __construct($request='') {
		parent::__construct(false, $request);
		
		$this->ZwyTransactionModel = new TransactionsModel();
	}
	
	/**
	 * 中网云转入申请管理
	 * 
	 * @param string $tpl 模板名称
	 */
	public function index($tpl='') {
		$userid = $this->get['userid'];
		$time_min = empty($this->get['time_min']) ? strtotime(date('Y-m-01 00:00:00')) : strtotime($this->get['time_min']);
		$time_max = empty($this->get['time_max']) ? time() : strtotime($this->get['time_max'].' 23:59:59');
		$date_type = empty($this->get['date_type']) ? 'created_time' : $this->get['date_type'];
		$status = empty($this->get['status']) ? 0 : $this->get['status'];
		$page = empty($this->get['p']) ? 1 : $this->get['p'];
		$page = $this->get['action']=='exportData' ? false : $page;
		$type = 'ZWY';
		
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
		
		$data = $this->ZwyTransactionModel->getList('*', $page, 20, $where);
		
		//整合数据
		$list = $data['list'];
		$export_data = []; //导出数据
		foreach ($list as $k=>$v) {
			$list[$k]['user'] = M('Member')->where('id='.$v['user_id'])->field('loginname,username')->find();
			
			//字段对应中文转换
			$list[$k]['status_cn'] = C('FIELD_CONFIG')['transactions']['status'][$v['status']];
			$list[$k]['type_cn'] = C('FIELD_CONFIG')['transactions']['status'][$v['type']];
			$list[$k]['category_cn'] = C('FIELD_CONFIG')['transactions']['status'][$v['category']];
			
			//组装导出数据
			$export_data[] = [
				$v['id'],
				$list[$k]['user']['loginname'].'['.$list[$k]['user']['username'].']',
				$v['amount'],
				$v['address'],
				$list[$k]['status_cn'],
				empty($v['created_time']) ? '' : date('Y-m-d H:i:s', $v['created_time']),
				empty($v['timereceived']) ? '' : date('Y-m-d H:i:s', $v['timereceived']),
				$v['remark']
			];
		}
		$this->assign('list', $list);
		
		$this->Page($data['paginator']['totalRows'], $data['paginator']['everyPage'], $this->get);
		
		//导出功能
		if ($this->get['action'] == 'exportData') {
			$head_array = array('ID', '用户', '金额', '钱包地址', '处理状态', '录入时间', '确认时间', '备注');
			$this->exportData($head_array, $export_data);
		}
		
		//合计转入金额
		$map_score = [
			'type' => ['eq', 'ZWY'],
			'status' => ['eq', 1],
			'category' => ['eq', 'receive'],
		];
		$score_receive = $this->ZwyTransactionModel->getInfo('sum(amount) amount', $map_score);
		$score_reward = $this->ZwyTransactionModel->getInfo('sum(reward_amount) reward_amount', $map_score);
		$this->assign('score_receive', $score_receive['amount']);
		$this->assign('score_reward', $score_reward['reward_amount']);
		
		$this->display();
	}
	
	/**
	 * 中网云转入操作
	 */
	public function tradeAction() {
		set_time_limit(0);
		ignore_user_abort(false);
		
		$checkId = $this->post['id'];
		$checkList = is_array( $checkId ) ? $checkId : array( $checkId );
		
		foreach ($checkList as $k=>$v) {
			M()->startTrans();
			
			$data = M('transactions')->where('id='.$v)->find();
			
			if (!$data) {
				continue;
			}
			
			//再次判断状态
			if ($data['status'] != '0') {
				continue;
			}
			
			//奖励金额
			$reward_bai = M('Settings')->where("settings_code='zhongwy_received_bai'")->getField('settings_value');
			$reward_bai = $reward_bai ? $reward_bai : 0;
			$reward_amount = $data['amount'] * $reward_bai / 100;
			
			//更改状态
			$data_trade = [
				'status' => 1,
				'timereceived' => time(),
				'reward_amount' => $reward_amount
			];
			$result1 = M('transactions')->where('id='.$v)->save($data_trade);
			
			//添加明细
			$AccountRecordModel = new AccountRecordModel();
			$result2 = $AccountRecordModel->add($data['user_id'], Currency::GoldCoin, CurrencyAction::GoldCoinReceievdToGRB, $data['amount'], $AccountRecordModel->getRecordAttach($data['user_id'],'','','',$data['account'],'','',['transactions_id'=>$v]));

			//添加奖励明细
			$result3 = $AccountRecordModel->add($data['user_id'], Currency::GoldCoin, CurrencyAction::GoldCoinReceievdToGRBReward, $reward_amount, $AccountRecordModel->getRecordAttach($data['user_id'],'','','',$data['account'],'','',['transactions_id'=>$v]));
			
			if ($result1 === false || !$result2 || !$result3) {
				M()->rollback();
				continue;
			}
			
			M()->commit();
		}
		
		exit;
	}
	
	/**
	 * 中网云转入退款操作
	 */
	public function tradeBack() {
		set_time_limit(0);
		ignore_user_abort(false);
		
		$checkId = $this->post['id'];
		$checkList = is_array( $checkId ) ? $checkId : array( $checkId );
		$remark = empty($this->post['remark']) ? '后台驳回' : $this->post['remark'];
		
		foreach ($checkList as $k=>$v) {
			$data = M('transactions')->where('id='.$v)->find();
			if (!$data) {
				continue;
			}
			
			//判断兑换状态
			if ($data['status'] != '0') {
				continue;
			}
			
			$data['remark'] = $remark;;
			
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
		
		//更改兑换状态
		$data_trade = [
			'status' => 2,
			'timereceived' => time(),
			'remark' => $data['remark']
		];
		$result2 = M('transactions')->where('id='.$data['id'])->save($data_trade);
		
		if ($result2 === false) {
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
	 * 详情查看
	 */
	public function getDetails() {
		layout(false);
		
		$id = $this->post['id'];
		
		if (!validateExtend($id, 'NUMBER')) {
			exit('参数格式有误');
		}
		
		$info = M('transactions')->where('id='.$id)->find();
		if (!$info) {
			exit('对应信息不存在');
		}
		
		$info['user'] = M('Member')->where('id='.$info['user_id'])->field('loginname,username')->find();
		
		//字段对应中文转换
		$info['status_cn'] = C('FIELD_CONFIG')['transactions']['status'][$info['status']];
		$info['type_cn'] = C('FIELD_CONFIG')['transactions']['type'][$info['type']];
		$info['category_cn'] = C('FIELD_CONFIG')['transactions']['category'][$info['category']];
		
		$this->assign('info', $info);
		$html = $this->fetch();
		
		exit($html);
	}
	
}
?>