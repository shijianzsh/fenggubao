<?php
namespace V4\Model;

use V4\Model\Currency;
use V4\Model\CurrencyAction;
use V4\Model\AccountRecordModel;
use V4\Model\AccountModel;

/**
 * 钱包模型
 *
 */
class WalletModel extends BaseModel {
	
	private $Wallet;
	private $wallet_type;
	
	/**
	 * 初始化
	 * 
	 * @param boolean $debug 是否开启调试模式(默认false)
	 * @param string $wallet_type 钱包类型(澳交所:AJS, 中网云:ZWY, AGX:AOGEX, GRC钱包:SLU),默认ZWY
	 */
	public function __construct($wallet_type='ZWY') {
		$this->wallet_type = $wallet_type;
		
		switch ($wallet_type) {
			case 'ZWY':
				Vendor('btc.btc_client');
				break;
			case 'AJS':
				Vendor('btc.ajs_client');
				break;
			case 'AGX':
				Vendor('btc.ajs_client');
				break;
			case 'SLU':
				Vendor('btc.slu_client');
				break;
			default:
				die('未知钱包类型');
		}
	}
	
	/**
	 * 创建钱包地址
	 * 
	 * @param string $username 用户名(默认空)
	 */
	public function getNewAddress($username='') {
		switch ($this->wallet_type) {
			case 'ZWY':
				return \Api_Rpc_Client::getNewAddressForUser($username);
				break;
			case 'AJS':
				return \Api_Rpc_Client_AJS::getNewAddressForUser($username);
				break;
			case 'AGX':
				return \Api_Rpc_Client_AJS::getNewAddressForUser($username);
				break;
			case 'SLU':
				return \Api_Rpc_Client_SLU::getNewAddressForUser($username);
				break;
		}
		
// 		return \Api_Rpc_Client::getNewAddressForUser($username);
	}
	
	/**
	 * 获取当前主钱包余额
	 */
	public function getBalance() {
		$balance = 0;
		
		switch ($this->wallet_type) {
			case 'ZWY':
				$balance = \Api_Rpc_Client::getBalance();
				break;
			case 'AJS':
				$balance = \Api_Rpc_Client_AJS::getBalance();
				break;
			case 'AGX':
				$balance = \Api_Rpc_Client_AJS::getBalance();
				break;
			case 'SLU':
				$balance = \Api_Rpc_Client_SLU::getBalance();
				break;
		}
		
// 		$balance = \Api_Rpc_Client::getBalance();
		
		return $balance;
	}
	
	/**
	 * 转账
	 * 
	 * @param string $wallet_address 钱包地址
	 * @param double $amount 金额
	 */
	public function sendToUserAddress($wallet_address, $amount) {
		switch ($this->wallet_type) {
			case 'ZWY':
				return \Api_Rpc_Client::sendToUserAddress($wallet_address, $amount);
				break;
			case 'AJS':
				return \Api_Rpc_Client_AJS::sendToUserAddress($wallet_address, $amount);
				break;
			case 'AGX':
				return \Api_Rpc_Client_AJS::sendToUserAddress($wallet_address, $amount);
				break;
			case 'SLU':
				return \Api_Rpc_Client_SLU::sendToUserAddress($wallet_address, $amount);
				break;
		}
		
// 		return \Api_Rpc_Client::sendToUserAddress($wallet_address, $amount);
	}
	
	/**
	 * 验证钱包地址
	 * 
	 * @param string $wallet_address 钱包地址
	 */
	public function validateUserAddress($address) {
		switch ($this->wallet_type) {
			case 'ZWY':
				return \Api_Rpc_Client::validateUserAddress($address);
				break;
			case 'AJS':
				return \Api_Rpc_Client_AJS::validateUserAddress($address);
				break;
			case 'AGX':
				return \Api_Rpc_Client_AJS::validateUserAddress($address);
				break;
			case 'SLU':
				return \Api_Rpc_Client_SLU::validateUserAddress($address);
				break;
		}
		
// 		return \Api_Rpc_Client::validateUserAddress($address);
	}
	
	/**
	 * 备份钱包
	 * 
	 * @param string $destination 生成的备份文件路径及名称
	 */
	public function backupUserWallet($destination) {
		switch ($this->wallet_type) {
			case 'ZWY':
				\Api_Rpc_Client::backupUserWallet($destination);
				break;
			case 'AJS':
				\Api_Rpc_Client_AJS::backupUserWallet($destination);
				break;
			case 'AGX':
				\Api_Rpc_Client_AJS::backupUserWallet($destination);
				break;
			case 'SLU':
				\Api_Rpc_Client_SLU::backupUserWallet($destination);
				break;
		}
		
// 		\Api_Rpc_Client::backupUserWallet($destination);
	}
	
	/**
	 * 获取交易详情
	 */
	public function getTransactionByTxid($txid) {
		switch ($this->wallet_type) {
			case 'ZWY':
				return \Api_Rpc_Client::getTransactionByTxid($txid);
				break;
			case 'AJS':
				return \Api_Rpc_Client_AJS::getTransactionByTxid($txid);
				break;
			case 'AGX':
				return \Api_Rpc_Client_AJS::getTransactionByTxid($txid);
				break;
			case 'SLU':
				return \Api_Rpc_Client_SLU::getTransactionByTxid($txid);
				break;
		}
		
// 		return \Api_Rpc_Client::getTransactionByTxid($txid);
	}
	
	/**
	 * 获取主钱包地址(ZWY,AJS,SLU)
	 */
	public function getMasterWalletAddress() {
		switch ($this->wallet_type) {
			case 'ZWY':
				return \BtcConfig::MASTER_ADDRESS;
				break;
			case 'AJS':
				return \AoJSConfig::MASTER_ADDRESS;
				break;
			case 'AGX':
				return \AoJSConfig::MASTER_ADDRESS;
				break;
			case 'SLU':
				return \SluConfig::GRC_ADDRESS;
				break;
		}
		
		return false;
	}
	
	/**
	 * 获取指定钱包余额 [仅支持SLU平台]
	 * 
	 * @param string $address 钱包地址
	 */
	public function getAddressBalance($address) {
		$balance = 0;
	
		switch ($this->wallet_type) {
			case 'SLU':
				$balance = \Api_Rpc_Client_SLU::getAddressBalance($address);
				break;
		}
	
		return $balance;
	}
	
	/**
	 * 给指定地址用户转入SLU [仅支持SLU平台]
	 * 
	 * @param string $address 钱包地址
	 * @param double $amount 金额
	 */
	public function sendToUserAddressBySLU($address, $amount) {
		switch ($this->wallet_type) {
			case 'SLU':
				return \Api_Rpc_Client_SLU::sendToUserAddressBySLU($address, $amount);
				break;
		}
		
		return false;
	}
	
	/**
	 * 由指定钱包地址转至另一指定钱包地址 [仅支持SLU平台]
	 * 
	 * @param string $address 接收转账的钱包地址
	 * @param double $amount 金额
	 * @param string $senderaddress 发起转账的钱包地址
	 */
	public function sendToUserAddressByAddress($address, $amount, $senderaddress) {
		switch ($this->wallet_type) {
			case 'SLU':
				return \Api_Rpc_Client_SLU::sendToUserAddressByAddress($address, $amount, $senderaddress);
				break;
		}
		
		return false;
	}

}