<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 云网通相关接口
// +----------------------------------------------------------------------
namespace APP\Controller;

use Common\Controller\ApiController;
use V4\Model\LockModel;
use V4\Model\GoldcoinPricesModel;
use V4\Model\AccountModel;
use V4\Model\Currency;
use V4\Model\CurrencyAction;
use V4\Model\AccountRecordModel;
use V4\Model\Tag;
use V4\Model\ProcedureModel;
use Common\Model\Sys\GrbTradeModel;
use V4\Model\MiningModel;
use V4\Model\WalletModel;

class YwtController extends ApiController
{

    /**
     * 资产和单价
     *
     * @method POST
     *
     * @param int $user_id 用户ID
     * @param string wallet_type 钱包类型(AJS:澳交所,ZWY:中网云)
     */
    public function index()
    {
        $AccountModel = new AccountModel();
        $LockModel = new LockModel();

        $user_id = $this->post['user_id'];
        $wallet_type = empty($this->post['wallet_type']) ? C('GRB_PRICE_TYPE') : $this->post['wallet_type'];

        if (!validateExtend($user_id, 'NUMBER')) {
            $this->myApiPrint('参数格式有误', 300);
        }

        //静态收益
        $consume_info = M('Consume')->where("user_id={$user_id}")->field('income_amount')->find();

        $data['income_amount_static'] = !$consume_info ? 0 : $consume_info['income_amount'];
        $data['income_amount_static'] = sprintf('%.4f份', $data['income_amount_static']);

        //通证汇总
        /* $income_account = $AccountModel->getFieldsValues('account_goldcoin_income,account_bonus_income', "user_id={$user_id} and account_tag=0");
        $income_lock = $LockModel->getInfo('lock_amount', "user_id={$user_id}");
        $income_lock_amount = !$income_lock ? 0 : $income_lock['lock_amount'];
        $data['income_amount'] = $income_account['account_goldcoin_income'] + $income_account['account_bonus_income'] + $income_lock_amount; */
        $income_amount = $this->getUserIncome($user_id);

        $data['income_amount'] = sprintf('%.4f份', $income_amount);

        //流通资产
        $data['amount_liutong'] = $AccountModel->getBalance($user_id, Currency::GoldCoin);
        $data['amount_liutong'] = sprintf('%.4f份', $data['amount_liutong']);
        
        //GRC购物积分
        $amount_credits = $AccountModel->getBalance($user_id, Currency::Credits);
        $amount_credits_str = sprintf("\r\nGRC购物积分: %.4f份", $amount_credits); //第三行
        
        //计算流通资产中已锁定的金额
        $frozen_goldcoin = M('frozen_fund')->where('frozen_status = 1 and  user_id=' . $user_id)->sum('frozen_goldcoin');
        $frozen_goldcoin_str = '';
        
        //计算第一行右侧需补空格个数(以第三行比第一行显示的多余字节为依据)
        $amount_liutong = sprintf("%.4f份", $data['amount_liutong']+$frozen_goldcoin); //第一行

        //计算第二行右侧需补空格个数(以第三行比第二行显示的多余字节为依据)
        if ($frozen_goldcoin > 0) {
        	$frozen_goldcoin_str = sprintf("\r\n(含冻结%.4f份)", $frozen_goldcoin); //第二行
        }
        
        $data['amount_liutong'] = $amount_liutong. $frozen_goldcoin_str;

        //锁定通证
        /* 旧锁定通证 */
//        $LockModel = new LockModel();
//        $map_lock['user_id'] = array('eq', $user_id);
//        $lock_info = $LockModel->getInfo('*', $map_lock);
//        $data['amount_lock_old'] = sprintf('%.4f份', $lock_info['lock_amount']);

        $data['amount_lock_old'] = $AccountModel->getBalance($user_id, Currency::Supply);
        $data['amount_lock_old'] = sprintf('%.4f份', $data['amount_lock_old']);

        /* 新锁定通证 */
        $data['amount_lock'] = $AccountModel->getBalance($user_id, Currency::Bonus);
        $data['amount_lock'] = sprintf('%.4f份', $data['amount_lock']);

        //总份数
        /* 含旧锁定通证 */
        $data['amount_score_have_old'] = sprintf('%.4f', $data['amount_liutong'] + $data['amount_lock'] + $data['amount_lock_old']);
        /* 不含旧锁定通证 */
        $data['amount_score'] = sprintf('%.4f份', $data['amount_liutong'] + $data['amount_lock']);

        //公让宝实时单价和更新时间
        $GoldcoinPricesModel = new GoldcoinPricesModel();
        $data['price'] = $GoldcoinPricesModel->getInfo('amount, uptime', '', 'id desc', $wallet_type);
        $data['price']['amount'] = sprintf('%.4f份', $data['price']['amount']);
        $data['price']['uptime'] = empty($data['price']['uptime']) ? time() : $data['price']['uptime'];

        //是否为区域合伙人(role=3)或省级合伙人(role=4)
        $member = M('Member')->where('id=' . $user_id)->find();
        switch ($member['role']) {
            case '3':
                $data['apply_quyuhehuoren'] = '0';
                $data['apply_quyuhehuoren_notice'] = '你已是区域合伙人';

                $data['apply_shengjihehuoren'] = '0';
                $data['apply_shengjihehuoren_notice'] = '你已是区域合伙人，不能再申请省级合伙人';
                break;
            case '4':
                $data['apply_quyuhehuoren'] = '0';
                $data['apply_quyuhehuoren_notice'] = '你已是省级合伙人，不能再申请区域合伙人';

                $data['apply_shengjihehuoren'] = '0';
                $data['apply_shengjihehuoren_notice'] = '你已是省级合伙人';
                break;
            default:
                $data['apply_quyuhehuoren'] = '1';
                $data['apply_quyuhehuoren_notice'] = '';

                $data['apply_shengjihehuoren'] = '1';
                $data['apply_shengjihehuoren_notice'] = '';
        }
        $data['is_quyuhehuoren'] = $member['role'] == '3' ? '1' : '0';
        $data['is_shengjihehuoren'] = $member['role'] == '4' ? '1' : '0';

        $data['need_active'] = $data['amount_lock_old'] >= 1;
//        $data['need_active'] = false;

        $data['active_member_txt'] = '解锁锁定资产';

        $this->myApiPrint('查询成功', 400, $data);
    }

    /**
     * 锁定通证明细
     *
     * @method POST
     *
     * @param int $user_id 用户ID
     * @param int $page 当前页面(空则默认为1)
     * @param int $type 查询类型(0支出,1收入,2全部,空则默认为2)
     * @param int $month 查询月份(格式:201802,空则默认为当前月)
     *
     */
    public function lockQueueList()
    {
        $user_id = $this->post['user_id'];
        $page = $this->post['page'];
        $page = $page > 1 ? $page : 1;
        $type = $this->post['type'];
        $month = empty($this->post['month']) ? Tag::getMonth() : $this->post['month'];

        if (!validateExtend($user_id, 'NUMBER')) {
            $this->myApiPrint('参数格式有误', 300);
        }

        /* $LockModel = new LockModel();
        $data = $LockModel->getQueueList('*', $page, 10);
        $data = $data['list']; */

        $AccountRecordModel = new AccountRecordModel();
        $data = $AccountRecordModel->getPageList($user_id, Currency::Bonus, $month, $page, $type, 10);
        $list = $data['list'];

        foreach ($list as $k => $v) {
            $list[$k]['record_remark'] = CurrencyAction::getLabel($v['record_action']);

            $tag = $type == 1 ? '+' : '';
            $list[$k]['record_amount'] = sprintf('%s%.4f', $tag, $v['record_amount']);

            $obj = json_decode($v['record_attach'], true);
            $attach = $AccountRecordModel->initAtach($obj, Currency::Bonus, $month, $v['record_id'], $v['record_action']);
            $list[$k]['from_name'] = $AccountRecordModel->getFinalName($attach['from_uid'], $attach['from_name']);
            $list[$k]['from_pic'] = $attach['pic'];
            
            //转入转出记录添加用户信息
            $in_out_action = [109,152, 209,252, 318,360, 405,452, 605,652, 718,760, 808,852];
            if (in_array($v['record_action'], $in_out_action)) {
            	$from_loginname = M('Member')->where('id='.$attach['from_uid'])->getField('loginname');
            	if ($v['record_amount'] > 0) {
            		$list[$k]['record_remark'] = '收到'. $attach['from_name']. "({$from_loginname})". $list[$k]['record_remark'];
            	} else {
            		$list[$k]['record_remark'] = $list[$k]['record_remark']. $attach['from_name']. "({$from_loginname})";
            	}
            }
        }

        $this->myApiPrint('查询成功', 400, $list);
    }

    /**
     * 个人业绩、消费金额、收益份额等信息
     *
     * @method POST
     *
     * @param int $user_id 用户ID
     */
    public function personalRelated()
    {
//		$this->myApiPrint('无数据', 300);
		
        $AccountModel = new AccountModel();
        $LockModel = new LockModel();
        $MiningModel = new MiningModel();
        $GoldcoinPricesModel = new GoldcoinPricesModel();

        $user_id = $this->post['user_id'];

        if (!validateExtend($user_id, 'NUMBER')) {
            $this->myApiPrint('参数格式有误', 300);
        }
        
        //公让宝实时单价
        $goldcoin_price_info = $GoldcoinPricesModel->getInfo('amount');
        $goldcoin_price = $goldcoin_price_info['amount'];
        
        $consume_info = M('Consume')->where("user_id={$user_id}")->field('amount,amount_old,income_amount,is_out,dynamic_out,dynamic_worth,static_worth')->find();
        $consume_bak_info = M('ConsumeBak')->where("user_id={$user_id}")->field('sum(amount) amount')->find();

        //总业绩 -> 团队总业绩
        $performance_info = M('Performance')->where("user_id={$user_id} and performance_tag=0")->field('performance_amount')->find();
        $data['performance_amount'] = !$performance_info ? 0 : $performance_info['performance_amount'];
        $data['performance_amount'] = sprintf('%.2f', $data['performance_amount']);
        
        //静态已丰收收益
//         $static_info = M('Mining')->where("user_id={$user_id} and tag=0")->field('amount')->find();
//         $data['income_amount_static'] = !$static_info ? 0 : $static_info['amount'];
//         $data['income_amount_static'] = $data['income_amount_static'] * $goldcoin_price;
        $data['income_amount_static'] = sprintf('%.2f', $consume_info['static_worth']);
        
        //动态已丰收收益
//         $consume_info = M('Consume')->where("user_id={$user_id}")->field('income_amount')->find();
//         $data['income_amount_static'] = !$consume_info ? 0 : $consume_info['income_amount'];
//         $data['income_amount_static'] = $data['income_amount_static'] * $goldcoin_price;
//         $data['income_amount_static'] = $data['income_amount_static'] - $data['income_amount_dynamic'];
        $data['income_amount_dynamic'] = sprintf('%.2f', $consume_info['dynamic_worth']);

        //通证汇总
        $data['income_amount'] = sprintf('%.2f', $this->getUserIncome($user_id));

        //总消费金额
        $map_order = array('uid' => array('eq', $user_id), 'order_status' => array('in', '1,3,4'));
        $order_amount = M('Orders')->where($map_order)->sum('amount');
        $data['consume_amount'] = sprintf('%.2f', $order_amount);

        //总贡献业绩PV、收益份额、是否出局
        $data['consume_pv'] = sprintf('%.2f', $consume_info['amount']);
        $data['consume_pv_all'] = sprintf('%.2f', $consume_info['amount'] + $consume_bak_info['amount']);
        $data['income_portion'] = floor($consume_info['amount'] / ( $this->CFG['performance_portion_base'] / 2 )) / 2;
		$data['portion'] = $MiningModel->getPortionNumber($user_id);
        $data['is_out'] = !$consume_info ? 0 : $consume_info['is_out'];
        $data['dynamic_out'] = !$consume_info ? 0 : $consume_info['dynamic_out'];
        
   		$out_bei = M('ConsumeRule')->alias('cr')->join('left join __CONSUME__ c ON c.level=cr.level')->where('c.user_id='.$user_id)->getField('cr.out_bei');
   		$out_bei = $out_bei ? $out_bei : 2;
   		
        //还剩余出局价值
        $data['all_rest'] = $consume_info['amount'] * $out_bei - $data['income_amount_dynamic'] - $data['income_amount_static'];
        $data['all_rest'] = sprintf('%.2f', ($data['all_rest']>0 ? $data['all_rest'] : 0));
        
        $return['list'] = [
	        ['label' => '团队总业绩', 'value' => $data['performance_amount']],
	        ['label' => '自己总消费', 'value' => $data['consume_amount']],
	        ['label' => '自己总贡献值', 'value' => $data['consume_pv_all']],
	        ['label' => '自己本轮贡献值', 'value' => $data['consume_pv']],
	        ['label' => '丰收份额', 'value' => $data['income_portion']],
	        ['label' => '动态已丰收价值', 'value' => $data['income_amount_dynamic']],
	        ['label' => '静态已丰收价值', 'value' => $data['income_amount_static']],
	        ['label' => '还剩余出局价值', 'value' => $data['all_rest']],
	        ['label' => '动+静是否出局', 'value' => ($data['is_out'] || $data['dynamic_out']) ? '是' : '否'],
        ];
        $return['notice'] = ""; //"总丰收不等于静态收益加动态收益";

        $this->myApiPrint('查询成功', 400, $return);
    }

    /**
     * 定时丰收锁定通证[定时任务]
     */
    public function releaseLockTask()
    {
        //丰收开关
        $mine_switch = $this->CFG['mine_switch'];
        if ($mine_switch == '关闭') {
            exit;
        }

        //判断节假日条件
        $date_validate = getDateStatus();
        if (!empty($date_validate)) {
            exit;
        }

        //调用丰收存储过程
        $ProcedureModel = new ProcedureModel();

        $result1 = $ProcedureModel->execute('Release_oldLock', '', '@error');
        if (!$result1) {
            $this->logWrite('定时执行丰收锁定通证任务失败:01', 1);
            exit;
        }

        $result2 = $ProcedureModel->execute('Release_lock', '', '@error');
        if (!$result2) {
            $this->logWrite('定时执行丰收锁定通证任务失败:02', 1);
            exit;
        }
    }
    
    /**
     * 流通兑换平台明细
     *
     * @method POST
     *
     * @param int $user_id 用户ID
     * @param int $page 当前页面(空则默认为1)
     * @param int $month 查询月份(格式:201802,空则默认为当前月)
     *
     */
    public function grbTradeDetails()
    {
    	$GrbTradeModel = new GrbTradeModel();
    	
    	$user_id = $this->post['user_id'];
    	$page = $this->post['page'];
    	$page = $page > 1 ? $page : 1;
    	$month = empty($this->post['month']) ? Tag::getMonth() : $this->post['month'];
    
    	if (!validateExtend($user_id, 'NUMBER')) {
    		$this->myApiPrint('参数格式有误', 300);
    	}

    	$where['user_id'] = array('eq', $user_id);
    	$where['_string'] = " from_unixtime(addtime, '%Y%m')='{$month}' ";

		$data = $GrbTradeModel->getList('amount,addtime,status', $page, 10, $where);
		$list = $data['list'];
		foreach ($list as $k=>$v) {
			$list[$k]['status_cn'] = C('FIELD_CONFIG')['trade']['status'][$v['status']];
		}
    
    	$this->myApiPrint('查询成功', 400, $list);
    }

    /**
     * 获取用户通证汇总
     *
     * @param int $user_id 用户ID
     */
    private function getUserIncome($user_id = 0)
    {
        $LockModel = new LockModel();

        if (empty($user_id)) {
            return 0;
        }

//        $income_amount_AccountIncome = M('AccountIncome')->where("income_tag=0 and user_id={$user_id}")->getField('income_total');

//        $income_amount_AccountIncome = !$income_amount_AccountIncome ? 0 : $income_amount_AccountIncome;

//        $income_amount_LockTotalAmount = $LockModel->getInfo('total_amount', "user_id={$user_id}");
//        $income_amount_LockTotalAmount = !$income_amount_LockTotalAmount ? 0 : $income_amount_LockTotalAmount['total_amount'];

//        $income_amount_MiningAmount = M('Mining')->where("user_id={$user_id} and tag=" . Tag::getDay())->field('amount')->find();
//        $income_amount_MiningAmount = !$income_amount_MiningAmount ? 0 : $income_amount_MiningAmount['amount'];

//        $income_amount = $income_amount_AccountIncome + $income_amount_LockTotalAmount + $income_amount_MiningAmount;


        $income_amount = M('consume')->where(['user_id'=>$user_id])->getField('income_amount');
        $income_amount = $income_amount + M('consume_bak')->where(['user_id'=>$user_id])->sum('income_amount');
        return $income_amount;
    }

}

?>