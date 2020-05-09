<?php
// +----------------------------------------------------------------------
// | SLU钱包操作
// +----------------------------------------------------------------------
namespace APP\Controller;

use Common\Controller\ApiController;
use V4\Model\TransactionsModel;
use V4\Model\GoldcoinPricesModel;
use V4\Model\WalletModel;
use V4\Model\AccountRecordModel;
use V4\Model\Currency;
use V4\Model\CurrencyAction;

class SluController extends ApiController
{
    public function __construct($request = '')
    {
        parent::__construct($request);
        Vendor('btc.slu_client');
        Vendor('Slu.Slu#Api');
    }

    public function index()
    {
        $this->myApiPrint('查询成功', 400, null);
    }

    // 导入转入交易
    public function importReceives($offset=0, $count=0)
    {
        $data = \Api_Rpc_Client_SLU::getLatestReceived(100,$offset);
        
        $transactionsM = new TransactionsModel();
        $current_count = $transactionsM->import($data['list'], 'SLU');
        $current_count = intval($current_count);
        
        $count += $current_count;
        
        if ($data['count'] > $offset*100) {
        	$this->importReceives($offset+1, $count);
        } else {
        	$this->myApiPrint('导入成功', 400, ['count' => $count]);
        }
    }
    
    /**
     * 获取实时单价
     */
    public function price() {
    
    	if ($this->CFG['slu_switch'] !== '开启') {
    		$this->myApiPrint('对接功能暂时关闭');
    	}
    	 
    	$price = \SluApi::getPrice();
    
    	if ($price !== false) {
    		$GoldcoinPricesModel = new GoldcoinPricesModel();
    		$GoldcoinPricesModel->add($price, 'SLU', C('SLU_PRICE_MIN'));
    	}
    
    	$this->myApiPrint('操作成功', 400, $price);
    }
    
    /**
     * 转入操作[定时任务]
     */
    public function tradeActionQueue() {
    	$TransactionsModel = M('transactions');
    
    	M()->startTrans();
    		
    	//拉取在队列中的ID最小的一条转账记录数据
    	$data = $TransactionsModel->where("is_queue=1 AND `type`='SLU'")->order('id asc, timereceived asc')->find();
    	if (!$data) {
    		exit('1');
    	}
    
    	//判断处理状态
    	if ($data['status'] != '3') {
    		$data_trade = [
    			'is_queue' => 0
    		];
    		$TransactionsModel->where('id='.$data['id'])->save($data_trade);
    		M()->commit();
    
    		exit('2');
    	}
    
    	//五分钟内处理过的数据直接过滤掉
    	if (time() - $data['timereceived'] < 60*5) {
    		exit('3');
    	}
    
    	//实例化模型
    	$WalletModel = new WalletModel('SLU');
    
    	//判断用户钱包地址GRC金额和SLU金额是否足够
    	$balance = $WalletModel->getAddressBalance($data['address']);
    	if ($balance['grc'] < $data['amount']) {
    		$data_trade = [
    			'timereceived' => time()
    		];
    		$TransactionsModel->where('id='.$data['id'])->save($data_trade);
    		M()->commit();
    			
    		exit('4');
    	}
    	if ($balance['slu'] < 0.1) {
    		$txid = $WalletModel->sendToUserAddressBySLU($data['address'], 0.1);
    		
    		$data_trade = [
    			'timereceived' => time()
    		];
    		$TransactionsModel->where('id='.$data['id'])->save($data_trade);
    		M()->commit();
    		
    		exit('5');
    	}
    
    	//[1]把用户转入金额发放到用户账户中
    
    	//计算奖励金额
    	$reward_bai = M('Settings')->where("settings_code='slu_received_bai'")->getField('settings_value');
    	$reward_bai = $reward_bai ? $reward_bai : 0;
    	$reward_amount = $data['amount'] * $reward_bai / 100;
    		
    	//更改转入记录状态
    	$data_trade = [
	    	'status' => 1,
	    	'timereceived' => time(),
	    	'is_queue' => 0,
	    	'reward_amount' => $reward_amount
    	];
    	$result1 = M('transactions')->where('id='.$data['id'])->save($data_trade);
    		
    	//添加明细
    	$AccountRecordModel = new AccountRecordModel();
    	$result2 = $AccountRecordModel->add($data['user_id'], Currency::GoldCoin, CurrencyAction::GoldCoinReceievdToGRB, $data['amount'], $AccountRecordModel->getRecordAttach($data['user_id'],'','','',$data['account'],'','',['transactions_id'=>$data['id']]));
    
    	//添加奖励明细
    	$result3 = $AccountRecordModel->add($data['user_id'], Currency::GoldCoin, CurrencyAction::GoldCoinReceievdToGRBReward, $reward_amount, $AccountRecordModel->getRecordAttach($data['user_id'],'','','',$data['account'],'','',['transactions_id'=>$data['id']]));
    	
    	if ($result1 === false || !$result2 || !$result3) {
    		M()->rollback();
    		exit('6');
    	}
    	
    	//[2]把用户钱包地址金额转入指定接收钱包地址
    	
    	//转账
    	$result_transfer = $WalletModel->sendToUserAddressByAddress(\SluConfig::IMPORT_RECEIVE_ADDRESS, $data['amount'], $data['address']);
    	
    	if ($result_transfer) {
    		$data_trade = [
    			'transfer_out_txid' => $result_transfer
    		];
    		M('transactions')->where('id='.$data['id'])->save($data_trade);
    	} else {
    		M()->rollback();
    		exit('7');
    	}
    		
    	M()->commit();
    
    	exit('success');
    }
    
}