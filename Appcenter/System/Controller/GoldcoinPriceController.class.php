<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 公让宝实时价格管理
// +----------------------------------------------------------------------
namespace System\Controller;
use Common\Controller\AuthController;
use V4\Model\GoldcoinPricesModel;

class GoldcoinPriceController extends AuthController {

	/**
	 * 实时价格管理
	 */
	public function index() {
		$GoldcoinPricesModel = new GoldcoinPricesModel();
		
		$info = $GoldcoinPricesModel->getInfo('amount,uptime');
		$this->assign('info', $info);
		
		$this->display();
	}
	
	/**
	 * 保存最新价格
	 */
	public function save() {
		$amount = $this->post['amount'];
		
		if (!validateExtend($amount, 'MONEY')) {
			$this->error('价格格式有误');
		}
		
		$data = [
			'amount' => $amount,
			'amount_original' => $amount,
			'uptime' => time(),
			'type' => C('GRB_PRICE_TYPE')
		];
		
		$result = M('GoldcoinPrices')->add($data);
		if (!$result || $result == null) {
			$this->error('设置失败,请稍后重试');
		}
		
		$this->success('设置成功', '', false, "成功设置新公让宝实时价格[ID:{$result}]");
	}
	
}
?>