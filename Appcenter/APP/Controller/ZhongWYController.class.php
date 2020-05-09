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

use Api_Rpc_Client;
use Common\Controller\ApiController;
use V4\Model\LockModel;
use V4\Model\GoldcoinPricesModel;
use V4\Model\AccountModel;
use V4\Model\Currency;
use V4\Model\OrderModel;
use V4\Model\EnjoyModel;
use V4\Model\SettingsModel;
use V4\Model\TransactionsModel;
use ZhongWYApi;

class ZhongWYController extends ApiController
{
    private $logfolder = 'zhongwy';

    public function __construct($request = '')
    {
        parent::__construct($request);
        
        Vendor('btc.btc_client');
        Vendor('ZhongWY.ZhongWY#Api');
    }


    public function index()
    {
    	if ($this->wallet_type == 'ZWY') {
        	$data = ZhongWYApi::pay('2C18032578256394', 'alipay_wap_new_pay', '1000', U('ZhongWY/notify', ['payway' => 'alipay'], '', true));
    	}
    	
        $this->myApiPrint('查询成功', 400, $data);
    }
    
    // 导入转入交易
    public function importReceives()
    {
    	$data = Api_Rpc_Client::getLatestReceived(10000);
    	$transactionsM = new TransactionsModel();
    	$this->myApiPrint('导入成功', 400, ['count' => $transactionsM->import($data, 'ZWY')]);
    }

    /**
     * 获取实时单价
     */
    public function price()
    {
		if ($this->CFG['zhongwy_switch'] !== '开启') {
			$this->myApiPrint('对接功能暂时关闭');
		}

        $price = ZhongWYApi::getPrice();

		//直接获取配置参数实时单价
//    	$price = $this->CFG['zhongwy_price'];

        if ($price) {
//            $GoldcoinPricesModel = new GoldcoinPricesModel();
//            $GoldcoinPricesModel->add($price, 'ZWY', C('AJS_PRICE_MIN'));
            SettingsModel::getInstance()->saveValue('zhongwy_trade_grb_rate', $price);
       	}
       	
        $this->myApiPrint('操作成功', 400, $price);
    }

    public function notify()
    {
    	$om = new OrderModel();
    	$EnjoyModel = new EnjoyModel();
    	
        $body = file_get_contents('php://input');
        $this->recordLogWrite($this->logfolder, $body . PHP_EOL);
        $body = json_decode($body, true);
        if (!isset($body['code']) || $body['code'] != '0000') {
            $this->recordLogWrite($this->logfolder, '支付失败（未知错误）' . PHP_EOL);
            die('success');
        }
        $params = [
            'code' => $body['code'],
            'msg' => $body['msg'],
            'payed' => $body['payed'] ? 'true' : 'false',
            'orderNo' => $body['orderNo'],
            'outOrderNo' => $body['outOrderNo'],
            'price' => $body['price'],
            'notifyUrl' => $body['notifyUrl'], // U('ZhongWY/notify', ['payway' => $_GET['payway']], '', true),
        ];
        if (!ZhongWYApi::check($body['sign'], $params)) {
            $this->recordLogWrite($this->logfolder, '签名错误' . PHP_EOL);
            die('success');
        }
        if (!$body['payed']) {
            $this->recordLogWrite($this->logfolder, '支付失败[' . $params['payed'] . ']' . PHP_EOL);
            die('success');
        }
        $orders = $this->validateOrder($params['outOrderNo']);
        $user = M('member')->find($orders['uid']);

        M()->startTrans();
        
        //订单主表状态
        $res1 = $om->updateOrder($params['outOrderNo'], 1);
        $res2 = $om->updateOrderpayinfo($params['outOrderNo'], $params['orderNo'], 'SUCCESS', $params['price'] * 100, $params['price'] * 100, time());
        $res3 = $om->shoppingpay($user, $orders, $_GET['payway']);
        
        //组合支付扣除
        $result_combined = $om->orderCombinedComplete($orders['id']);
        
        //赠送澳洲SKN股数
//         $result_enjoy = $EnjoyModel->consumeGive($orders['id']);
        $result_enjoy = true;
        
        if ($res1 !== false && $res2 !== false && $res3 !== false && $result_combined !== false && $result_enjoy !== false) {
            M()->commit();
            $this->recordLogWrite($this->logfolder, '支付成功' . PHP_EOL);
            die('success');
        } else {
            M()->rollback();
            $this->recordLogWrite($this->logfolder, '支付失败' . PHP_EOL);
            die('success');
        }
    }

    /**
     * 验证订单信息
     *
     * @param string $method
     * @param number $orderstatus 1=已付款,4=已完成
     *
     * @return NULL
     */
    private function validateOrder($orderNo)
    {
        $orders = M('orders')->where('order_number=\'' . $orderNo . '\'')->find();
        if (!$orders) {
            $this->recordLogWrite($this->logfolder, '订单不存在' . PHP_EOL);
            die('success');
        }
        if ($orders['order_status'] != 0) {
            $this->recordLogWrite($this->logfolder, '订单已处理' . PHP_EOL);
            die('success');
        }

        //查询流水记录
        $payinfo = M('orders_pay_info')->where(array('order_number' => $orderNo))->find();
        if (!$payinfo) {
            $this->recordLogWrite($this->logfolder, '订单无支付记录' . PHP_EOL);
            die('success');
        }
        if ($payinfo['trade_status'] != '') {
            $this->recordLogWrite($this->logfolder, '订单已处理' . PHP_EOL);
            die('success');
        }
        return $orders;
    }

    /**
     * 获取支付方式
     * 
     * @param string $type 钱包类型(澳交所:AJS, 中网云:ZWY, SLU:SLU)
     */
    public function getSwitch($type)
    {
        $data = [];
        
        $type = empty($type) ? C('GRB_PRICE_TYPE') : $type;
        
        switch ($type) {
        	case 'AJS':
        		$data['zhongwy_switch'] = $this->CFG['ajs_switch'] === '开启';
        		break;
        	case 'ZWY':
        		$data['zhongwy_switch'] = $this->CFG['zhongwy_switch'] === '开启';
        		break;
        	case 'ZWY':
        		$data['zhongwy_switch'] = $this->CFG['slu_switch'] === '开启';
        		break;
        }
        
        //再次判断支付开关
        if (C('PAY_METHOD_MUST_HT')) {
        	$data['zhongwy_switch'] = false;
        }
        
        $this->myApiPrint('获取成功', 400, $data);
    }

}