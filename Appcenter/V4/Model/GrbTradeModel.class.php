<?php
namespace V4\Model;

use V4\Model\Currency;
use V4\Model\CurrencyAction;
use V4\Model\AccountRecordModel;
use V4\Model\AccountModel;
use V4\Model\EnjoyModel;

/**
 * 公让宝流通兑换模型
 *
 */
class GrbTradeModel extends BaseModel {

	/**
	 * 提交至兑换审核表
	 * 
	 * @param array $data 流通兑换申请数据
	 * @param array $CFG 配置数组
	 * 
	 * @return boolean
	 */
	public function addTrade($data=array(), $CFG=array()) {
		$EnjoyModel = new EnjoyModel();
		
		if (empty($data)) {
			return false;
		}
		
		M()->startTrans();
		
		//添加明细
		$AccountRecordModel = new AccountRecordModel();
		$record_attach = json_encode(['address'=>$data['wallet_address'], 'fee'=>$data['fee'], 'type'=>$data['type']], JSON_UNESCAPED_UNICODE);
		$result1 = $AccountRecordModel->add($data['user_id'], Currency::GoldCoin, CurrencyAction::GoldCoinTransferToGRB, -$data['amount'], $record_attach);
		
		//停留金额
		$AccountModel = new AccountModel();
		$data['balance'] = $AccountModel->getGoldCoinBalance($data['user_id']);
		
		//添加申请
		$result2 = M('Trade')->add($data);
		
		//扣除澳洲SKN股数
		$result3 = $EnjoyModel->thirdUse($data['user_id'], $CFG['enjoy_third']);
		
		if (!$result1 || !$result2 || $result2 == null || !$result3 || $result3 == null) {
			M()->rollback();
			return false;
		}
		
		M()->commit();
		
		return true;
	}

}