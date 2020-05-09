<?php
namespace V4\Model;

use V4\Model\Currency;
use V4\Model\CurrencyAction;
use V4\Model\AccountRecordModel;
use V4\Model\AccountModel;

/**
 * 澳洲SKN股数模型
 *
 */
class EnjoyModel extends BaseModel {
	
	private $AccountRecordModel;
	
	public function __construct() {
		$this->AccountRecordModel = new AccountRecordModel();
	}

	/**
	 * 买商品赠送澳洲SKN股数
	 * 
	 * @param int $order_id 订单ID
	 * @param boolean $update 是否执行赠送(默认true)
	 * 
	 * @return mixed boolean/string
	 */
	public function consumeGive($order_id, $update=true) {
// 		$order_info = M('Orders')
// 			->alias('o')
// 			->join('join __ORDER_AFFILIATE__ aff ON aff.order_id=o.id')
// 			->join('join __BLOCK__ b ON b.block_id=o.producttype')
// 			->field('o.uid, o.order_number, aff.affiliate_pay, b.block_enjoy_order_amount, b.block_enjoy_give_amount')
// 			->where('o.id='.$order_id)
// 			->find();

		$field = "o.uid
				, b.block_enjoy_order_amount, b.block_enjoy_give_amount
				, sum(op.price_cash * op.product_quantity * op.performance_bai_cash * 0.01 * if(ifnull(o.`discount`, 0) = 0, 10, o.`discount`) * 0.1) performance_amount";
		$order_info = M('Orders')
			->alias('o')
			->join('left join __ORDER_PRODUCT__ op ON o.id=op.order_id')
			->join('join __BLOCK__ b ON b.block_id=o.producttype')
			->where('o.id='.$order_id)
			->field($field)
			->find();
		
		if (!$order_info) {
			return false;
		}
		
		$give = floor($order_info['performance_amount'] / $order_info['block_enjoy_order_amount']) * $order_info['block_enjoy_give_amount'];
		
		if ($update) {
			if ($give > 0) {
				$result = $this->AccountRecordModel->add($order_info['uid'], Currency::Enjoy, CurrencyAction::EnjoyByConsumeGive, $give, $this->AccountRecordModel->getRecordAttach( 1, '', '', $order_id), '消费赠送澳洲SKN股数');
			} else {
				return true;
			}
		} else {
			$result = $give;
		}
		
		return $result;
	}
	
	/**
	 * 签到赠送
	 * 
	 * @param int $user_id 用户ID
	 * @param int $amount 赠送金额
	 * 
	 * @return boolean
	 */
	public function signinGive($user_id, $amount) {
		if ($amount == 0) {
			return true;
		}
		
		$result = $this->AccountRecordModel->add($user_id, Currency::Enjoy, CurrencyAction::EnjoySignIn, $amount, $this->AccountRecordModel->getRecordAttach(1, '系统'), '签到送澳洲SKN股数');
		
		return $result;
	}
	
	/**
	 * 分享朋友圈赠送
	 * 
	 * @param int $user_id 用户ID
	 * @param int $amount 赠送金额
	 * 
	 * @return boolean
	 */
	public function shareGive($user_id, $amount) {
		if ($amount == 0) {
			return true;
		}
		
		$result = $this->AccountRecordModel->add($user_id, Currency::Enjoy, CurrencyAction::EnjoyShare, $amount, $this->AccountRecordModel->getRecordAttach(1, '系统'), '分享送澳洲SKN股数');
		
		return $result;
	}
	
	/**
	 * 丰收消耗
	 * 
	 * @param int $user_id 用户ID
	 * @param int $amount 消耗金额
	 *
	 * @return boolean
	 */
	public function miningUse($user_id, $amount) {
		if ($amount == 0) {
			return true;
		}
		
		$result = $this->AccountRecordModel->add($user_id, Currency::Enjoy, CurrencyAction::EnjoyMining, -$amount, $this->AccountRecordModel->getRecordAttach(1, '系统'), '丰收消耗澳洲SKN股数');
		
		return $result;
	}
	
	/**
	 * 提现消耗
	 * 
	 * @param int $user_id 用户ID
	 * @param int $amount 消耗金额
	 */
	public function tixianUse($user_id, $amount) {
		if ($amount == 0) {
			return true;
		}
		
		$result = $this->AccountRecordModel->add($user_id, Currency::Enjoy, CurrencyAction::EnjoyTixian, -$amount, $this->AccountRecordModel->getRecordAttach(1, '系统'), '提现消耗澳洲SKN股数');
		
		return $result;
	}
	
	/**
	 * 转赠消耗
	 * 
	 * @param int $user_id 用户ID
	 * @param int $amount 消耗金额
	 */
	public function transferUse($user_id, $amount) {
		if ($amount == 0) {
			return true;
		}
		
		$result = $this->AccountRecordModel->add($user_id, Currency::Enjoy, CurrencyAction::EnjoyTransfer, -$amount, $this->AccountRecordModel->getRecordAttach(1, '系统'), '转赠消耗澳洲SKN股数');
		
		return $result;
	}
	
	/**
	 * 转让到公共市场消耗
	 * 
	 * @param int $user_id 用户ID
	 * @param int $amount 消耗金额
	 */
	public function thirdUse($user_id, $amount) {
		if ($amount == 0) {
			return true;
		}
		
		$result = $this->AccountRecordModel->add($user_id, Currency::Enjoy, CurrencyAction::EnjoyTransferToGRB, -$amount, $this->AccountRecordModel->getRecordAttach(1, '系统', '', '', '', '', '', ['platform'=>'澳交所']), '转让到公共市场消耗澳洲SKN股数');
		
		return $result;
	}

}