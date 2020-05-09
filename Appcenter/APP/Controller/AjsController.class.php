<?php
// +----------------------------------------------------------------------
// | 澳交所钱包操作
// +----------------------------------------------------------------------
namespace APP\Controller;

use Common\Controller\ApiController;
use V4\Model\TransactionsModel;
use V4\Model\GoldcoinPricesModel;

class AjsController extends ApiController
{
    public function __construct($request = '')
    {
        parent::__construct($request);
        Vendor('btc.ajs_client');
//         Vendor('AoJS.AoJS#Api');
    }

    public function index()
    {
        $this->myApiPrint('查询成功', 400, null);
    }

    // 导入转入交易
    public function importReceives()
    {
        $data = \Api_Rpc_Client_AJS::getLatestReceived(10000);
        $transactionsM = new TransactionsModel();
        $this->myApiPrint('导入成功', 400, [
            'count' => $transactionsM->import($data)
        ]);
    }
    
    /**
     * 获取实时单价
     */
    public function price() {
    	 
    	if ($this->CFG['ajs_switch'] !== '开启') {
    		$this->myApiPrint('对接功能暂时关闭');
    	}
    
    	if (C('AJS_PRICE_GET_NOT_CLOSED')) { //只获取收盘价的情况下下午6点后数据将不再获取
    		if (date('H') >= 18) {
    			$this->myApiPrint('操作成功', 400);
    		}
    	}
    	
//     	$price = \AoJSApi::getPrice();

    	//直接获取配置参数实时单价
    	$price = $this->CFG['ajs_price'];
    
    	if ($price !== false) {
    		$GoldcoinPricesModel = new GoldcoinPricesModel();
    		$GoldcoinPricesModel->add($price, 'AJS', C('AJS_PRICE_MIN'));
    	}
    
    	$this->myApiPrint('操作成功', 400, $price);
    }
    
}