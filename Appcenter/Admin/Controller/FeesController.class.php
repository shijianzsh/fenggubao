<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 税费管理
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Common\Controller\AuthController;
use V4\Model\AccountRecordModel;
use V4\Model\CurrencyAction;
use V4\Model\Currency;

class FeesController extends AuthController {
    
    private $AccountRecordModel;
	
	public function __construct() {
		parent::__construct();
		
		C('TOKEN_ON', false);
		
		$this->AccountRecordModel = new AccountRecordModel();
	}
	
	/**
	 * 个人所得税列表
	 */
	public function personProfits() {
	    $type = $this->get['type'];
	    
	    //默认cash
	    $type = empty($type) ? 'cash' : $type;
	    
	    switch ($type) {
	        case 'cash':
	            $currency = Currency::Cash;
	            $currency_action = CurrencyAction::CashGerenshuodeShui;
	            break;
//	        case 'goldcoin':
//	            $currency = Currency::GoldCoin;
//	            $currency_action = CurrencyAction::GoldCoinTax;
//	            break;
//	        case 'redelivery':
//	            $currency = Currency::Redelivery;
//	            $currency_action = CurrencyAction::RedeliveryTax;
//	            break;
	    }
	    
	    $data = $this->base('person_profits', $currency, $currency_action);
	    
	    $list = $data['list'];
	    foreach ($list as $k=>$v) {
	        //用户账号信息
	        $member_info = M('Member')->where('id='.$v['user_id'])->field('loginname,nickname,username')->find();
	        $list[$k]['loginname'] = $member_info['loginname'];
	        $list[$k]['nickname'] = $member_info['nickname'];
	        $list[$k]['username'] = $member_info['username'];
	    }
	    $this->assign('list', $list);
	     
	    $this->Page($data['paginator']['totalPage']*$data['paginator']['everyPage'], $data['paginator']['everyPage'], $this->get);
	    
	    $this->assign('total', $data['total']);
	    
	    //导出功能
	    if ($this->get['action'] == 'export') {
	        $export_data = array();
	        foreach ($list as $k=>$v) {
	            $vo = [
	                $v['record_id'],
	                $v['username'].'[手机号:'.$v['loginname'].'][姓名:'.$v['nickname'].']',
	                $v['record_remark'],
	                $v['record_amount'],
	                date('Y-m-d H:i:s', $v['record_addtime'])
	            ];
	            $export_data[] = $vo;
	        }
	         
	        $export_date = empty($this->get['time_min']) ? date('Y-m-d') : $this->get['time_min'];
	        $head_array = array('编号', '用户账号', '类型', '金额', '操作时间');
	        $file_name = "导出个人所得税查询数据-".$export_date;
	        $file_name = iconv("utf-8", "gbk", $file_name);
	        $return = $this->xlsExport($file_name, $head_array, $export_data);
	        !empty($return['error']) && $this->error($return['error']);
	         
	        $this->logWrite("导出个人所得税查询数据-".$export_date);
	         
	        exit;
	    }
	     
	    $this->display();
	}
	
	/**
	 * 平台管理费列表
	 */
	public function systemManage() {
	    $type = $this->get['type'];
	    
	    //默认cash
	    $type = empty($type) ? 'cash' : $type;
	    
	    switch ($type) {
	        case 'cash':
	            $currency = Currency::Cash;
	            $currency_action = CurrencyAction::CashPingtaiGuanliFei;
	            break;
//	        case 'goldcoin':
//	            $currency = Currency::GoldCoin;
//	            $currency_action = CurrencyAction::GoldCoinManagementFee;
//	            break;
//	        case 'redelivery':
//	            $currency = Currency::Redelivery;
//	            $currency_action = CurrencyAction::RedeliveryManagementFee;
//	            break;
	    }
	    
	    $data = $this->base('system_manage', $currency, $currency_action);
	    
	    $list = $data['list'];
	    foreach ($list as $k=>$v) {
	        //用户账号信息
	        $member_info = M('Member')->where('id='.$v['user_id'])->field('loginname,nickname,username')->find();
	        $list[$k]['loginname'] = $member_info['loginname'];
	        $list[$k]['nickname'] = $member_info['nickname'];
	        $list[$k]['username'] = $member_info['username'];
	    }
	    $this->assign('list', $list);
	     
	    $this->Page($data['paginator']['totalPage']*$data['paginator']['everyPage'], $data['paginator']['everyPage'], $this->get);
	    
	    $this->assign('total', $data['total']);
	    
	    //导出功能
	    if ($this->get['action'] == 'export') {
	        $export_data = array();
	        foreach ($list as $k=>$v) {
	            $vo = [
	                $v['record_id'],
	                $v['username'].'[手机号:'.$v['loginname'].'][姓名:'.$v['nickname'].']',
	                $v['record_remark'],
	                $v['record_amount'],
	                date('Y-m-d H:i:s', $v['record_addtime'])
	            ];
	            $export_data[] = $vo;
	        }
	    
	        $export_date = empty($this->get['time_min']) ? date('Y-m-d') : $this->get['time_min'];
	        $head_array = array('编号', '用户账号', '类型', '金额', '操作时间');
	        $file_name = "导出个人所得税查询数据-".$export_date;
	        $file_name = iconv("utf-8", "gbk", $file_name);
	        $return = $this->xlsExport($file_name, $head_array, $export_data);
	        !empty($return['error']) && $this->error($return['error']);
	    
	        $this->logWrite("导出个人所得税查询数据-".$export_date);
	    
	        exit;
	    }
	     
	    $this->display();
	}
	
	/**
	 * 通用基础方法
	 * 
	 * @param string $fees_type 税费类型
	 * @param string $currency 货币类型
	 * @param int $currency_action 货币操作类型
	 * 
	 * @return array
	 */
	private function base($fees_type, $currency, $currency_action) {
	    $fees_type_list = ['person_profits', 'system_manage'];
	    
	    if (!in_array($fees_type, $fees_type_list)) {
	        $this->error('税费类型非法');
	    }
	    
	    $userid = $this->get['userid'];
	    $time_min = $this->get['time_min'];
	    $time_max = $this->get['time_max'];
	    $page = $this->get['p']>0 ? $this->get['p'] : 1;
	    
	    if (!empty($userid) && !validateExtend($userid, 'MOBILE') && !validateExtend($userid, 'CHS') && !validateExtend($userid, 'USERNAME')) {
	        $this->error('用户账号格式有误');
	    }
	    
	    $where = '';
	    $month = date('Ym');
	    
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
	    
	    $where .= " and record_action=".$currency_action;
	    
	    //数据列表
	    $data = $this->AccountRecordModel->getListByAllUser($currency, $month, $page, 20, $where);
	    
	    //总转账金额
	    $total = $this->AccountRecordModel->getFieldsValues($currency, $month, "sum(record_amount) total", " 1 ".$where);
	    $data['total'] = abs($total['total']);
	    
	    return $data;
	}
	
}
?>