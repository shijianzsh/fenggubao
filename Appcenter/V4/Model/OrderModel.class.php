<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/5
 * Time: 11:18
 */

namespace V4\Model;

/**
 * Class OrderModel
 * @package V4\Model
 * 订单相关
 */
class OrderModel
{

    private static $_instance;

    /**
     * 单例-获取new对象
     * Enter description here ...
     */
    public static function getInstance()
    {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * 生成16位订单号（待优化）
     *
     * 订单号必须唯一，以下做法只能尽可能
     */
    private function build_order_no_core()
    {
        $year_code = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');

        return $year_code[intval(date('Y')) - 2016] .
            strtoupper(dechex(date('m'))) . date('d') .
            substr(time(), -5) . substr(microtime(), 2, 5) . (($f = intval(rand(0, 99))) < 10 ? '0' . $f : $f);
    }

    /**
     * 生成订单号
     * Enter description here ...
     *
     * @param $type ='ALI', 'WX', 'BANK', 'OTHER'
     */
    public function createOrderNo($type = 'WX')
    {
        $allow_type = array('ALI', 'WX', 'BANK', 'ZALI', 'ZWX', 'OTHER');
        $order_no = $this->build_order_no_core();

        if (in_array($type, $allow_type)) {
            $order_no_change = function () use ($type, $order_no) {
                $padding = '';
                if (strlen($type) < 5) {
                    $padding_num = 5 - strlen($type);
                    $microtime = microtime();
                    $padding = substr($microtime, 2, $padding_num);
                }

                return $type . $padding . $order_no;
            };
            $order_no = $order_no_change();
        }

        return $order_no;
    }

    /**
     * 微信支付签名
     *
     * @param $order_num 订单号，由系统生成
     * @param $amount 支付金额
     * @param $subject 订单标题
     * @param $body 订单描述
     * @param $append_url 回调url
     */
    public function getWxpaySign($order_num, $amount, $append_url, $subject = '订单', $body = '')
    {
        $amount = $amount ? $amount : 0.01;

        Vendor("WxPay.WxPay#Api"); //微信支付基础组件
        $append_url = U($append_url, '', '', true);

        //数据处理
        $amount = sprintf('%.2f', $amount);
        $amount = sprintf('%d', $amount * 100);

        // 微信预支付订单
        $WxPay = new \WxPayUnifiedOrder();
        $WxPay->SetBody(C('APP_TITLE'));
        $WxPay->SetAttach(C('APP_TITLE') . $subject);
        $WxPay->SetOut_trade_no($order_num);
        $WxPay->SetTotal_fee($amount);
        $WxPay->SetTime_start(date("YmdHis"));
        $WxPay->SetTime_expire(date("YmdHis", time() + 600));
        $WxPay->SetNotify_url($append_url);
        $WxPay->SetTrade_type("APP");
        $wx_pay_return = \WxPayApi::unifiedOrder($WxPay);

        if ($wx_pay_return['return_code'] == 'SUCCESS') {
            $time = time();
            $return_data = array(
                'appid' => $wx_pay_return['appid'],
                'partnerid' => $wx_pay_return['mch_id'],
                'prepayid' => $wx_pay_return['prepay_id'],
                'package' => 'Sign=WXPay',
                'noncestr' => $wx_pay_return['nonce_str'],
                'timestamp' => (String)$time
            );

            //生成传给APP的签名
            $WxPR = \WxPayResults::InitFromArray($return_data, true);
            $sign = $WxPR->MakeSign();
            $return_data['sign'] = $sign;

            //返回预支付兑换回话标识(该值有效期2小时)和签名,用于APP端调用
            return $return_data;
        } else {
            return $wx_pay_return;
        }
    }

    /**
     * 支付宝签名
     *
     * @param  $order_num 订单号，由系统生成
     * @param $amount 支付金额
     * @param $subject 订单标题
     * @param $body 订单描述
     * @param $append_url 回调url
     */
    public function getAlipaySign($orderNo, $amount, $notify_url)
    {
        Vendor('Alipay.AliPay#Api');

        $amount = sprintf('%.2f', $amount);

        //参数
        $param['timeout_express'] = "30m";
        $param['product_code'] = "QUICK_MSECURITY_PAY";
        $param['total_amount'] = $amount;
        $param['body'] = C('APP_TITLE');
        $param['subject'] = C('APP_TITLE');
        $param['out_trade_no'] = $orderNo;

        $append_url = U($notify_url, '', '', true);

        $aliPay = new \AliPay($param);

        return $aliPay->createOrder($append_url);
    }


    /**
     * 创建订单
     *
     * @param $uid
     * @param $money
     * @param string $paymentmethod 支付方式
     * @param int $status 0=未付款；1=已付款；2=取消；3=已发货；4=已完成
     * @param $storeid
     * @param $productname
     * @param $comment
     * @param $dutypay
     * @param $productid
     * @param type $exchangeway 订单类型(0=兑换商品(默认)；1=送货上门； 2=买单；3=非商城，如充值、申请创客) 4vip
     * @param $num
     * @param $viptype 1=微代；2vip；3尊贵
     * 
     * @param $chknum 谷聚金 - 用于充值提货券记录实时提货券价格(默认0)
     *
     * @return string $orderNo 订单号
     */
    public function create($uid, $money, $paymentmethod = 'cash', $status = 0, $storeid = 0, $productname = '', $comment = '', $dutypay = 0, $productid = 0, $exchangeway = 2, $num = 1, $starttime = 0, $endtime = 0, $enroll = 0, $viptype = 0, $chknum=0)
    {
        //返回信息
        $data = array();
        $orderNo = '';
        
        //根据支付类型组合数据
        if ($paymentmethod == PaymentMethod::Cash) {
            $orderNo = $this->createOrderNo('OTHER');
            $data['amount_type'] = 1;
        } elseif ($paymentmethod == PaymentMethod::GoldCoin) {
            $orderNo = $this->createOrderNo('OTHER');
            $data['amount_type'] = 6;
        } elseif ($paymentmethod == PaymentMethod::ColorCoin) {
//             $orderNo = $this->createOrderNo('OTHER');
//             $data['amount_type'] = 6;
			return '';
        } elseif ($paymentmethod == PaymentMethod::Wechat) {
            $orderNo = $this->createOrderNo('WX');
            $data['amount_type'] = 2;
            //生成流水记录
            M('orders_pay_info')->add(array('uid' => $uid, 'order_number' => $orderNo));
        } elseif ($paymentmethod == PaymentMethod::Alipay) {
            $orderNo = $this->createOrderNo('ALI');
            $data['amount_type'] = 3;
            //生成流水记录
            M('orders_pay_info')->add(array('uid' => $uid, 'order_number' => $orderNo));
        } else {
            return '';
        }

        //现场兑换
        if ($exchangeway == 0) {
            $data['chknum'] = $this->createExchangeNo();
        }
        
        //提货券记录单价
        if ($num == 2) {
        	$data['chknum'] = $chknum;
        }

        $data['uid'] = $uid;
        $data['storeid'] = $storeid;
        $data['goldcoin'] = 0;
        $data['amount'] = $money;
        $data['time'] = time();
        $data['productid'] = $productid;
        $data['productname'] = $productname;
//		$data['status']       = - 1; //废弃的字段，下一步删掉
        $data['order_status'] = $status;
//		if ( $status == 4 ) {
//			$data['pay_time'] = time();
//		}
        $data['exchangeway'] = $exchangeway;
        $data['num'] = $num;
        $data['comment'] = $comment;
        $data['dutypay'] = $dutypay;
        $data['start_time'] = $starttime;
        $data['end_time'] = $endtime;
        $data['order_enroll_amount'] = $enroll; //注册币数量
        if ($exchangeway == 4) {
            $data['producttype'] = $viptype;
        }
        $data['order_number'] = $orderNo;
        $res = M('orders')->add($data);

        if ($res !== false) {
            return $orderNo;
        } else {
            return '';
        }
    }


    /**
     * 买单订单
     *
     * @param unknown $uid
     * @param unknown $money
     * @param unknown $goldcoin
     * @param string $paymentmethod
     * @param number $status
     * @param number $storeid
     * @param number $exchangeway
     *
     * @return string
     */
    public function consumeOrder($uid, $money, $goldcoin, $paymentmethod = 'cash', $status = 0, $storeid = 0, $exchangeway = 2)
    {
        //返回信息
        $data = array();
        $orderNo = '';
        //根据支付类型组合数据
        if ($paymentmethod == PaymentMethod::Cash) {
            $orderNo = $this->createOrderNo('OTHER');
            $data['amount_type'] = 1;
        } elseif ($paymentmethod == PaymentMethod::GoldCoin) {
            $orderNo = $this->createOrderNo('OTHER');
            $data['amount_type'] = 2;
        } elseif ($paymentmethod == PaymentMethod::ColorCoin) {
            $orderNo = $this->createOrderNo('OTHER');
            $data['amount_type'] = 6;
        } elseif ($paymentmethod == PaymentMethod::Wechat) {
            $orderNo = $this->createOrderNo('WX');
            $data['amount_type'] = 5;
            //生成流水记录
            M('orders_pay_info')->add(array('uid' => $uid, 'order_number' => $orderNo));
        } elseif ($paymentmethod == PaymentMethod::Alipay) {
            $orderNo = $this->createOrderNo('Ali');
            $data['amount_type'] = 4;
            //生成流水记录
            M('orders_pay_info')->add(array('uid' => $uid, 'order_number' => $orderNo));
        } else {
            return '';
        }

        $data['uid'] = $uid;
        $data['order_number'] = $orderNo;
        $data['storeid'] = $storeid;
        $data['goldcoin'] = $goldcoin;
        $data['amount'] = $money;
        $data['time'] = time();
        $data['productid'] = 0;
        $data['productname'] = '买单';
        $data['status'] = -1; //废弃的字段，下一步删掉
        $data['order_status'] = $status;
        if ($status == 4) {
            $data['pay_time'] = time();
        }
        $data['exchangeway'] = $exchangeway;
        $data['num'] = 1;
        $data['comment'] = '';
        $data['dutypay'] = 0;
        $data['start_time'] = 0;
        $data['end_time'] = 0;
        $res = M('orders')->add($data);
        if ($res !== false) {
            return array('id' => $res, 'orderNo' => $orderNo);
        } else {
            return false;
        }
    }


    /**
     * 生产兑换码
     * Enter description here ...
     */
    private function createExchangeNo()
    {
        $chknum = '';
        while (true) {
            for ($i = 0; $i < 14; $i++) {
                $chknum .= strval(rand(0, 9));
            }
            $chk = M('orders')
                ->field('id,storeid,chknum')
                ->where("storeid='" . $data1['storeid'] . "' and chknum='" . $chknum . "'")
                ->find();
            if (empty($chk)) {
                break;
            }
        }

        return $chknum;
    }


    /**
     * 验证账号余额是否充足.false=余额不足
     * Enter description here ...
     *
     * @param $uid
     * @param $currency
     * @param $money
     */
    public function compareBalance($uid, $currency, $money)
    {
        $am = new AccountModel();
        $balance = $am->getBalance($uid, $currency);
        if ($balance < $money) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * 为特定人员提供的格式化函数
     */
    public function format_return($msg = "返回成功", $msg_code = 400, $data)
    {
        $result = array();
        $result['code'] = $msg_code;
        $result['msg'] = $msg;
        $foo['productDetails'] = $data;
        $result['result'] = $foo;

        return $result;
    }


    /**
     * 买单（现金积分/公让宝/商超券/)
     * Enter description here ...
     *
     * @param $post
     * @param $amount
     * @param $duty_pay
     * @param $cashType
     * @param $comment
     *
     * @return boolean;
     */
    public function consumeByVirtualCurrency($post, $amount, $duty_pay, $cashType, $comment)
    {
        $res4 = true;
        $res7 = true;
        $om = new OrderModel();
        $rm = new RewardModel();
        $params = M('g_parameter', null)->find();
        //1.计算利润
        $profitsData = $rm->getProfitsByOrder($amount, $post['pw']['reward']);
        //2.计算双方奖励-公让宝
        $rewardData = $rm->getColorCoinByMoney($post['buyer'], $post['seller'], $profitsData['profits'], $post['store']);
        //4.记录明细-同步扣除账户资金
        $arm = new AccountRecordModel();
        if ($cashType == 'cash') {
            //5.创建订单
            $res1 = $om->create($post['buyer']['id'], $amount, PaymentMethod::Cash, 4, $post['store']['id'], $post['store']['store_name'], $comment, $duty_pay, 1, 2);

            //返现给商家
            $res3 = $arm->add($post['seller']['id'], $cashType, CurrencyAction::CashConsumeBackToMerchant, $profitsData['return_cash'], $arm->getRecordAttach($post['buyer']['id'], $post['buyer']['nickname'], $post['buyer']['img'], $res1), mb_substr($post['buyer']['nickname'], 0, 1, 'utf-8') . '**买单返现');

            //买家明细
            if ($duty_pay != 1) {
                $res2 = $arm->add($post['buyer']['id'], $cashType, CurrencyAction::CashConsume, -$amount, $arm->getRecordAttach($post['seller']['id'], $post['store']['store_name'], $post['store']['store_img'], $res1), '店铺买单');
                //赠送买家、商家公让宝
                $res4 = $rm->assignColorCoin($post['buyer'], $post['seller'], $rewardData, $post['store'], $res1);
            } else {
                $res2 = $arm->add($post['buyer']['id'], $cashType, CurrencyAction::CashDutyConsume, -$amount, $arm->getRecordAttach($post['seller']['id'], $post['store']['store_name'], $post['store']['store_img'], $res1), '店铺买单');
                //责任消费金额累加
                M()->execute('INSERT IGNORE INTO `zc_dutyconsume`(`user_id`, `dutyconsume_income_enable`, `dutyconsume_uptime`) VALUES(' . $post['buyer']['id'] . ', 1, UNIX_TIMESTAMP())');
                M('dutyconsume')->where('user_id = ' . $post['buyer']['id'])->save(array(
                    'dutyconsume_complete_amount' => array(
                        'exp',
                        'dutyconsume_complete_amount+' . $amount
                    )
                ));
                //赠送积分
                $creditsnum = floor($amount * $params['duty_consume_credits_bai']) / 100;
                $res4 = $arm->add($post['buyer']['id'], Currency::Credits, CurrencyAction::CreditsDutyConsumeAdd, $creditsnum, $arm->getRecordAttach(1, '平台'), '责任消费赠送积分');

                M()->execute(C('ALIYUN_TDDL_MASTER') . "call Bonus_dutyconsume(" . $post['buyer']['id'] . ", " . $amount . ")");
            }

            //现金积分消费不记录平台毛利润

        } elseif ($cashType == 'goldcoin') {
            //5.创建订单
            $res1 = $om->create($post['buyer']['id'], $amount, PaymentMethod::GoldCoin, 4, $post['store']['id'], $post['store']['store_name'], $comment, $duty_pay, 1, 2);

            //资金明细
            $res2 = $arm->add($post['buyer']['id'], $cashType, CurrencyAction::GoldCoinConsume, -$amount, $arm->getRecordAttach($post['seller']['id'], $post['store']['store_name'], $post['store']['store_img'], $res1), '店铺买单');
            $res3 = $arm->add($post['seller']['id'], Currency::Cash, CurrencyAction::CashGoldCoinConsumeBackToMerchant, $profitsData['return_cash'], $arm->getRecordAttach($post['buyer']['id'], $post['buyer']['nickname'], $post['buyer']['img'], $res1), mb_substr($post['buyer']['nickname'], 0, 1, 'utf-8') . '**买单返现');

            //计算平台利润
            $res7 = $this->sharebonus($post['buyer']['id'], $profitsData['profits'], $post['seller']['id'], $res1, $cashType);

        } elseif ($cashType == 'colorcoin') {
            //【商超券-什么都不送】
            //1.重新计算利润
            $profitsData = $rm->getProfitsBySpecialOrder($amount, C('PARAMETER_CONFIG.COLORCOIN_PAY_BAI'));

            //5.创建订单
            $res1 = $om->create($post['buyer']['id'], $amount, PaymentMethod::ColorCoin, 4, $post['store']['id'], $post['store']['store_name'], $comment, $duty_pay, 1, 2);

            //资金明细
            $res2 = $arm->add($post['buyer']['id'], $cashType, CurrencyAction::ColorCoinConsume, -$amount, $arm->getRecordAttach($post['seller']['id'], $post['store']['store_name'], $post['store']['store_img'], $res1), '店铺买单');
            $res3 = $arm->add($post['seller']['id'], Currency::Cash, CurrencyAction::CashColorCoinConsumeBackToMerchant, $profitsData['return_cash'], $arm->getRecordAttach($post['buyer']['id'], $post['buyer']['nickname'], $post['buyer']['img'], $res1), mb_substr($post['buyer']['nickname'], 0, 1, 'utf-8') . '**买单返现');

            //商超券消费不记录平台毛利润
        }
        if ($res1 != '' && $res2 !== false && $res3 !== false && $res4 !== false && $res7 !== false) {
            //推送消息
            $jpush = new \APP\Controller\SysController();
            $jpush->pushafterpay($post['seller']['id'], $post['buyer']['id'], date('Y-m-d H:i:s', time()), $profitsData['return_cash']);

            return true;
        } else {
            return false;
        }

    }

    /**
     * 微信/支付宝 买单 回调处理业务
     * Enter description here ...
     *
     * @param $post
     * @param $amount
     * @param $duty_pay
     * @param $payMethod 支付方式
     *
     * @return boolean;
     */
    public function consumeByWechatAndAlipay($post, $params, $payway)
    {
        $parameter = M('g_parameter', null)->find(1);
        $order = $post['order'];
        $arm = new AccountRecordModel();
        //1.更新订单
        $res1 = $this->updateOrder($params['out_trade_no'], 4);
        $res2 = $this->updateOrderInfo($params, $order['amount']);

        //2.资金明细
        if ($payway == PaymentMethod::Wechat) {
            $res3 = $arm->add($post['buyer']['id'], Currency::Cash, CurrencyAction::WechatPayRecharge, $order['amount'], $arm->getRecordAttach(1, '管理员'), '微信支付在线充值');
        } else {
            $res3 = $arm->add($post['buyer']['id'], Currency::Cash, CurrencyAction::AliPayRecharge, $order['amount'], $arm->getRecordAttach(1, '管理员'), '支付宝在线充值');
        }
        $res4 = $arm->add($post['buyer']['id'], Currency::Cash, CurrencyAction::WechatConsume, -$order['amount'], $arm->getRecordAttach($post['seller']['id'], $post['store']['store_name'], $post['store']['store_img'], $params['out_trade_no']), '微信支付,店铺买单');
        if ($order['goldcoin'] > 0) {
            $arm->add($post['buyer']['id'], Currency::GoldCoin, CurrencyAction::GoldCoinConsume, -$order['goldcoin'], $arm->getRecordAttach($post['seller']['id'], $post['store']['store_name'], $post['store']['store_img'], $params['out_trade_no']), '微信支付,店铺买单');
        }

        //给商家钱
        $totalfee = $order['amount'] + $order['goldcoin'];
        $rm = new RewardModel();
        $profitsData = $rm->getProfitsByOrder($totalfee, $post['pw']['reward']);
        $lirun = $profitsData['profits'] * $parameter['v51_system_proftis_bai'] / 100;
        if ($post['store']['store_supermarket'] == 1) {
            //自营
            $mchamount = ($order['amount'] - $lirun) * C('PARAMETER_CONFIG.COLORCOIN_PAY_BAI') / 100;
        } else {
            $mchamount = ($order['amount'] - $lirun) * $parameter['v51_shop_custom_pay_bai'] / 100;
        }
        $res5 = true;
        if ($mchamount > 0) {
            $res5 = $arm->add($post['seller']['id'], Currency::Cash, CurrencyAction::CashConsumeBackToMerchant, $mchamount, $arm->getRecordAttach($post['buyer']['id'], $post['buyer']['nickname'], $post['buyer']['img'], $params['out_trade_no']), mb_substr($post['buyer']['nickname'], 0, 1, 'utf-8') . '**买单返现');
        }
        //2.解冻
        $res6 = M('frozen_fund')->where('order_id = ' . $order['id'])->save(array('frozen_status' => 0, 'frozen_uptime' => time()));

        //平台毛利润
        $res7 = true;
        if ($lirun > 0) {
            $data_profits_bonus['profits'] = $lirun;
            $data_profits_bonus['order_number'] = $order['order_number'];
            $data_profits_bonus['date_created'] = time();
            $res7 = M('profits_bonus')->add($data_profits_bonus);
        }

        //吊起存储过程-收益
        $pm = new ProcedureModel();
        $res9 = $pm->execute('V51_Event_consume', $order['id'], '@error');

        if ($res1 !== false && $res2 !== false && $res3 !== false && $res4 !== false && $res5 !== false && $res7 !== false && $res6 !== false && $res9) {
            //推送消息
            $jpush = new \APP\Controller\SysController();
            $jpush->pushafterpay($post['seller']['id'], $post['buyer']['id'], date('Y-m-d H:i:s', time()), $mchamount);

            return true;
        } else {
            return false;
        }

    }

    /**
     * 更新订单表状态
     * Enter description here ...
     *
     * @param string $out_trade_no 订单号
     * @param int $status 订单状态0=未付款；1=已付款；2=取消；3=已发货；4=已完成
     */
    public function updateOrder($out_trade_no, $order_status = 4, $pay_status = '成功')
    {
        //针对商城订单减去对应商品库存
        $order_info = M('Orders')->where("order_number='{$out_trade_no}'")->field('id,exchangeway')->find();
        if ($order_info['exchangeway'] == '1' && $order_status == '1') {
            $order_product_list = M('OrderProduct')
                ->where('order_id=' . $order_info['id'])
                ->field('product_id,product_quantity')
                ->select();
            if ($order_product_list) {
                foreach ($order_product_list as $k => $v) {
                    $result = M('Product')->where('id=' . $v['product_id'])->setInc('exchangenum', $v['product_quantity']);
                    if ($result === false) {
                        return false;
                        break;
                    }
                }
            }
        }

        //更新订单信息
        $data['pay_status'] = $pay_status;
        $data['order_status'] = $order_status;
        $data['pay_time'] = time();

        return M('orders')->where(array('order_number' => $out_trade_no))->save($data);
    }

    public function updateOrderpayinfo($out_trade_no, $trade_no, $trade_status, $total_amount, $receipt_amount, $gmt_payment)
    {
        // 更新付款状态
        $data = array();
        $data['trade_no'] = $trade_no;
        $data['trade_status'] = $trade_status;
        $data['total_amount'] = $total_amount;
        $data['receipt_amount'] = $receipt_amount;
        $data['gmt_payment'] = $gmt_payment;

        return M('orders_pay_info')->where(array('order_number' => $out_trade_no))->save($data);
    }


    /**
     * 更新订单流水记录
     * Enter description here ...
     *
     * @param $params
     * @param $amount
     */
    public function updateOrderInfo($params, $amount)
    {
        $data['trade_no'] = $params['transaction_id'];
        $data['trade_status'] = $params['result_code'];
        $data['total_amount'] = $amount;
        $data['receipt_amount'] = $params['cash_fee'];
        $data['gmt_payment'] = $params['time_end'];

        return M('orders_pay_info')->where(array('order_number' => $params['out_trade_no']))->save($data);
    }

    /**
     * 给上线收益，记录平台利润
     * Enter description here ...
     *
     * @param int $uid 大于0表示有3个奖，=0表示只计算平台利润
     * @param int $profits
     * @param int $sellerId
     * @param string $out_trade_no
     */
    public function sharebonus($uid = 0, $profits, $sellerId, $out_trade_no, $currency)
    {
        if ($profits > 0) {
            if ($uid > 0) {
                M()->execute(C('ALIYUN_TDDL_MASTER') . "call Bonus_repeat(" . $uid . "," . $profits . ")"); //实体消费奖
                M()->execute(C('ALIYUN_TDDL_MASTER') . "call Bonus_marchant(" . $sellerId . "," . $profits . ")");
            }
            //公让宝消费记录平台毛利润
            if (Currency::GoldCoin == $currency) {
                $data_profits_bonus['profits'] = $profits;
                $data_profits_bonus['order_number'] = $out_trade_no;
                $data_profits_bonus['date_created'] = time();

                return M('profits_bonus')->add($data_profits_bonus);
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * 生成流水号
     */
    public function build_serial_num()
    {
        return date('Ymd') .
            substr(time(), -5) .
            substr(microtime(), 2, 5) .
            (($f = intval(rand(0, 99))) < 10 ? '0' . $f : $f);
    }

    /**
     * 申请提现
     * Enter description here ...
     *
     * @param $by 0支付宝 1微信 2银行卡
     * @param $uid
     * @param $amount
     * @param $commission 手续费
     * @param $content
     * @param $config 参数配置
     */
    public function withdrawByWechatAndCard($by, $uid, $amount, $commission, $content, $rule)
    {
        $withhold_total_amount = $amount + $commission;
        //获取余额
        $am = new AccountModel();
        $balance = $am->getCashBalance($uid);
        //余额不足
        if ($balance - $withhold_total_amount < 0) {
            return false;
        }

        if ($by == 0) {
            $bystr = '支付宝';
            //获取提现账号信息
            $uinfo = M('user_affiliate')->where('user_id = ' . $uid)->find();
            $about['account'] = $uinfo['alipay_account'];
            $about['head'] = $uinfo['alipay_avatar'];
            $about['username'] = $uinfo['alipay_nick_name'];
            $wdata['receiver_about'] = json_encode($about, JSON_UNESCAPED_UNICODE);
        } elseif ($by == 1) {
            $bystr = '微信';
            $uinfo = M('member')->where('id = ' . $uid)->find();
            $weixin_info = unserialize($uinfo['weixin']);
            $about['account'] = $weixin_info['openid'];
            $about['head'] = $weixin_info['headimgurl'];
            $about['username'] = $weixin_info['nickname'];
            $wdata['receiver_about'] = json_encode($about, JSON_UNESCAPED_UNICODE);
        } elseif ($by == 2) {
            $bystr = '银行卡';
            $uinfo = M('withdraw_bankcard')->where('uid = ' . $uid)->find();
            $about['account'] = $uinfo['inacc'];
            $about['head'] = '';
            $about['username'] = $uinfo['inaccname'];
            $wdata['receiver_about'] = json_encode($about, JSON_UNESCAPED_UNICODE);
        }

        //提交申请记录
        $withdraw_no = $this->build_serial_num();
        $wdata['serial_num'] = $withdraw_no;
        $wdata['tiqu_type'] = $by;
        $wdata['uid'] = $uid;
        $wdata['add_time'] = time();
        $wdata['amount'] = $amount;
        $wdata['commission'] = $commission;
        $wdata['content'] = $content;
        $wdata['receiver_acount'] = $rule['nickname'];
        $wdata['receiver'] = $rule['nickname'];
        $wdata['current_account_cash'] = $balance - $withhold_total_amount;
        $res1 = M('withdraw_cash')->add($wdata);

        //插入明细
        $arm = new AccountRecordModel();
        $res2 = $arm->add($uid, Currency::Cash, CurrencyAction::CashTixian, -$amount, $arm->getRecordAttach($uid, $rule['wx']['nickname'], $rule['wx']['img'], $withdraw_no), $bystr . '提现');
        $res3 = $arm->add($uid, Currency::Cash, CurrencyAction::CashTixianShouxufei, -$commission, $arm->getRecordAttach(1, '管理员', '', $withdraw_no), $bystr . '提现扣除手续费');
        if ($res1 !== false && $res2 !== false && $res3 !== false) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * 兑换订单退款
     * Enter description here ...
     *
     * @param unknown_type $product
     * @param unknown_type $order
     * @param unknown_type $cancel_reason
     * @param unknown_type $cancel_descp
     */
    public function cancenExchangeOrder($product, $order, $cancel_reason, $cancel_descp)
    {
        //减去已兑商品数量,增加会员对应公让宝
        $da1['exchangenum'] = $product['exchangenum'] - $order['num'];
        $da1['exchangeuse'] = $product['exchangeuse'] + $order['num'];

        //订单信息
        $da['cancel_reason'] = $cancel_reason;
        $da['cancel_descp'] = $cancel_descp;
        $da['cancel_time'] = date('Y-m-d H:i:s');
        $da['order_status'] = 2;

        //更新商品+订单状态
        $res1 = M('orders')->where('id=' . $order['id'])->save($da);
        $res2 = M('product')->where('id=' . $product['id'])->save($da1);

        //退款
        $arm = new AccountRecordModel();
        if ($order['amount_type'] == 2) {
            //退公让宝
            $res3 = $arm->add($order['uid'], Currency::GoldCoin, CurrencyAction::GoldCoinCancelOrder, $order['amount'], $arm->getRecordAttach(1, '管理员', '', $order['order_number']), '取消兑换订单[' . $order['productname'] . ']退款');
        } elseif ($order['amount_type'] == 6) {
            //退商超券
            $res3 = $arm->add($order['uid'], Currency::ColorCoin, CurrencyAction::ColorConinCancelOrder, $order['amount'], $arm->getRecordAttach(1, '管理员', '', $order['order_number']), '取消兑换订单[' . $order['productname'] . ']退款');
        } else {
            return false;
        }

        if ($res1 !== false && $res2 !== false && $res3 !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 删除订单
     * Enter description here ...
     *
     * @param unknown_type $order_id
     */
    public function delOrder($order_id, $u_id)
    {
        return M('orders')->where('id=' . $order_id . ' and uid=' . $u_id)->save(array('is_del' => 1));
    }


    /**
     * 获取订单列表-只显示买单
     * Enter description here ...
     *
     * @param int $order_status
     * @param int $uid
     * @param int $pn
     * @param int $ps
     */
    public function getOrderByList($order_status, $uid, $pn = 1, $ps = 10)
    {
        $st = ' o.dutypay=0 and  o.exchangeway =2 and o.uid = ' . $uid;
        switch ($order_status) {
            case '0':
                $st .= ' and o.`order_status` >=0 ';
                break;
            case '1': //待评价
                $st .= ' and o.`order_status` = 4 and o.iscontent = 0 ';
                break;
            case '2': //已完成
                $st .= ' and o.`order_status` = 4 and o.iscontent = 1 ';
                break;
            case '3': //未完成
                $st .= ' and o.`order_status` in (1,3) ';
                break;
            case '4': //退款
                $st .= ' and o.`order_status` = 2 ';
                break;
            default:
                ajax_return('查无此状态！');
        }
        
        //记录总数
        $count = M('orders o')->where($st)->count();


        $data = M('orders o')
            ->field('o.id, o.order_number, o.productname, o.`order_status`, o.iscontent, IFNULL(o.chknum,\'\') chknum, o.comment_time, o.storeid, o.exchangeway, o.time, o.amount, o.goldcoin, o.num, o.score, o.start_time, o.end_time, o.dutypay, s.store_name, s.store_img, p.img as productimg')
            ->join('left join zc_store as s on s.id = o.storeid')
            ->join('left join zc_product as p on p.id = o.productid')
            ->where($st)
            ->order('o.id desc')
            ->limit(($pn - 1) * $ps . ',' . $ps)
            ->select();

        foreach ($data as $k => $v) {
            $data[$k]['outtime'] = 0;
            if ($v['exchangeway'] == 2) {
                //到店消费买单
                $data[$k]['productimg'] = $v['store_img'];
            } elseif ($v['exchangeway'] == 0) {
                //兑换订单-验证时间是否过期
                if ($v['end_time'] < time()) {
                    $data[$k]['outtime'] = 1;
                }
                $data[$k]['productimg'] = $v['productimg'];
            }
            //兼容2.0版兑换订单金额没有插入amount字段
            if ($v['amount'] * 1 > 0) {
                $data[$k]['goldcoin'] = $v['amount'];
            }
            $data[$k]['goldcoin'] = sprintf('%.2f', $data[$k]['goldcoin']);
            //兼容没有图片
            if ($data[$k]['productimg'] == '') {
                $data[$k]['productimg'] = '/Uploads/head_sculpture/logo.png';
            }
            $data[$k]['productimg'] .= c('USER_ORDERLIST_SIZE');
            
            //订单PV
            $pv_data = $this->getOrderPV($v['id']);
            $data[$k] = array_merge($data[$k], $pv_data);
        }

        $page['totalPage'] = ceil($count / $ps);
        $page['everyPage'] = $ps;
        $return['page'] = $page;
        $return['data'] = $data;
        $return['count0'] = 0;
        $return['count1'] = 0;
        $return['count2'] = 0;
        $return['count3'] = 0;
        $return['count4'] = 0;

        return $return;
    }

    /**
     * 店铺订单列表
     * Enter description here ...
     *
     * @param $order_status
     * @param $uid
     * @param $pn
     * @param $ps
     */
    public function getStoreOrderList($storeid, $pn = 1, $ps = 10)
    {
        $st = ' o.storeid=' . $storeid . ' and o.exchangeway = 2';
        //记录总数
        $count = M('orders o')->where($st)->count();

        $data = M('orders o')
            ->field('b.loginname, b.nickname, b.img, c.pname, o.order_number, o.storeid, o.goldcoin, o.producttype, o.`order_status`, o.exchangeway, o.time, o.uid, o.productname, floor(((`o`.`goldcoin` / `c`.`conditions`) * `c`.`reward`)) * `o`.`producttype` AS `gife`, o.comment')
            ->join(' left join zc_member as b on b.id = o.uid ')
            ->join(' left join zc_preferential_way as c on c.store_id= o.storeid ')
            ->join(' left join zc_product as d on d.id = o.productid ')
            ->where($st)
            ->order('o.id desc')
            ->limit(($pn - 1) * $ps . ',' . $ps)
            ->select();


        $page['totalPage'] = ceil($count / $ps);
        $page['everyPage'] = $ps;
        $return['page'] = $page;
        $return['data'] = $data;
        $return['count'] = $count;

        return $return;
    }

    /**
     * 店铺兑换订单列表
     * Enter description here ...
     *
     * @param int $order_status
     * @param int $uid
     * @param int $pn
     * @param int $ps
     */
    public function getOrderByStoreid($order_status, $storeid, $pn = 1, $ps = 10)
    {
        $st = ' o.exchangeway =0 and o.storeid = ' . $storeid;
        switch ($order_status) {
            case '0':

                break;
            case '1'://未使用
                $st .= ' and o.`order_status` = 1 ';
                break;
            case '2': //已使用
                $st .= ' and o.`order_status` = 4 and o.iscontent = 0 ';
                break;
            case '3': //已完成
                $st .= ' and o.`order_status` = 4 and o.iscontent = 1  ';
                break;
            case '4': //取消
                $st .= ' and o.`order_status` = 2 ';
                break;
            default:
                ajax_return('查无此状态！');
        }
        //记录总数
        $count = M('orders o')->where($st)->count();


        $data = M('orders o')
            ->field('o.id, o.order_number, o.productname, o.`order_status`, o.return_gold, o.iscontent, IFNULL(o.chknum,\'\') chknum, o.comment_time, o.storeid, o.exchangeway, o.time, o.amount, o.goldcoin, o.num, o.score, o.start_time, o.end_time, o.dutypay, p.img as productimg, m.loginname, m.nickname, m.img')
            ->join('left join zc_product as p on p.id = o.productid')
            ->join('left join zc_member as m on m.id = o.uid')
            ->where($st)
            ->order('o.id desc')
            ->limit(($pn - 1) * $ps . ',' . $ps)
            ->select();

        foreach ($data as $k => $v) {
            if ($v['order_status'] == 0) {
                $data[$k]['status_cn'] = '未付款';
            } elseif ($v['order_status'] == 1) {
                $data[$k]['status_cn'] = '已付款';
            } elseif ($v['order_status'] == 2) {
                $data[$k]['status_cn'] = '已取消';
            } elseif ($v['order_status'] == 3) {
                $data[$k]['status_cn'] = '已发货';
            } elseif ($v['order_status'] == 4) {
                $data[$k]['status_cn'] = '已完成';
            } else {
                $data[$k]['status_cn'] = '-';
            }
            $data[$k]['outtime'] = 0;
            //兑换订单-验证时间是否过期
            if ($v['end_time'] < time()) {
                $data[$k]['outtime'] = 1;
                $data[$k]['status_cn'] = '已过期';
            }
            $data[$k]['productimg'] = $v['productimg'];

            //兼容2.0版兑换订单金额没有插入amount字段
            if ($v['amount'] * 1 > 0) {
                $data[$k]['goldcoin'] = $v['amount'];
            }
            $data[$k]['goldcoin'] = sprintf('%.2f', $data[$k]['goldcoin']);
            //兼容没有图片
            if ($data[$k]['productimg'] == '') {
                $data[$k]['productimg'] = '/Uploads/head_sculpture/logo.png';
            }
            $data[$k]['productimg'] .= c('USER_ORDERLIST_SIZE');
        }

        $page['totalPage'] = ceil($count / $ps);
        $page['everyPage'] = $ps;
        $return['page'] = $page;
        $return['data'] = $data;

        return $return;
    }

    /**
     * 根据条件查询订单列表
     * Enter description here ...
     *
     * @param int $user_id
     * @param array $dayarray
     */
    public function getWeekDutyPayAmount($user_id, $dayarray)
    {
        $where['uid'] = $user_id;
        $where['time'] = array(array('egt', $dayarray[6]), array('elt', $dayarray[7]));
        $where['dutypay'] = 1;
        $where['order_status'] = 4;
        $list = M('orders')->where($where)->order('id desc')->getField('amount', true);

        return array_sum($list);
    }

    /**
     * 责任消费接口
     */
    public function dutyconsume2($user_id, $amount)
    {
        $parameter = M('g_parameter', null)->find();
        //1.创建订单
        $res1 = $this->create($user_id, $amount, PaymentMethod::Cash, 4, 0, '责任消费', '', 1, 0, 2);

        //2.消费记录
        $arm = new AccountRecordModel();
        $res2 = $arm->add($user_id, Currency::Cash, CurrencyAction::CashDutyConsume, -$amount, $arm->getRecordAttach(1, '系统'), '现金积分责任消费');

        //获得积分
        $credits = $amount * $parameter['duty_consume_exchange_bei'];
        $res3 = $arm->add($user_id, Currency::Credits, CurrencyAction::CreditsDutyConsumeExchane, $credits, $arm->getRecordAttach(1, '系统'), '责任消费兑换积分');

        //责任消费金额累加
        $res4 = M('dutyconsume')->where('user_id = ' . $user_id)->save(array(
            'dutyconsume_complete_amount' => array('exp', 'dutyconsume_complete_amount+' . $amount),
            'dutyconsume_income_enable' => 1
        ));

        M()->execute(C('ALIYUN_TDDL_MASTER') . "call Bonus_dutyconsume(" . $user_id . ", " . $amount . ")");
        if ($res1 != '' && $res2 !== false && $res3 !== false && $res4 !== false) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * 创建商城订单
     *
     * @param unknown $data
     * @param unknown $user
     * @param unknown $address
     * @param number $block_id (1=折扣区, 2=免费区, 3=特供区
     */
    public function build($data, $user, $address)
    {
        $params = M('g_parameter', null)->find();
        //1.创建订单
        $order_no = $this->createOrderNo('BS');
        $order['order_number'] = $order_no;
        $order['storeid'] = $data['storeid'];
        $order['uid'] = $user['id'];
        $order['productid'] = 0;
        $order['productname'] = $user['truename'] . '-' . $user['loginname'] . '的订单';
        $order['exchangeway'] = 1;
        $order['time'] = time();
        $order['goldcoin'] = 0;
        $order['amount'] = $data['total_fee'];
        $order['amount_type'] = $data['payway']; //现金积分支付类型(1:现金积分, 2:微信, 3:支付宝, 4:第三方-微信, 5:第三方-支付宝, 6:公让宝, 7:银行卡, 8:提货券, 9:兑换券, 10:报单币)
        $order['order_status'] = 0;
        $order['producttype'] = $data['items'][0]['block_id'];  //区域id

        //加入省市区
        $order['province'] = $address['province'];
        $order['city'] = $address['city'];
        $order['country'] = $address['country'];
        
        //加入备注说明
        $order['content'] = $data['remark']?:'';
        
        //计算添加订单折扣
        $order['discount'] = $this->getUserDiscount($user['id'], $data['block_id'])['discount'];

        $order_id = M('orders')->add($order);

        //2.商品明细
        $batch = true;
        foreach ($data['items'] as $k => $item) {
            $vo = array();
            $vo['product_id'] = $item['product_id'];
            $vo['product_quantity'] = $item['cart_quantity'];
//			$vo['product_attr']             = $item['cart_attr'];
            $vo['product_freight'] = $item['product_freight'] ?: 0;
//			$vo['product_freight_collect']  = $item['affiliate_freight_collect'];
            $vo['price_cash'] = $item['price_cash'];
//			$vo['price_goldcoin']           = $item['price_goldcoin'];
//			$vo['give_points']              = $item['give_points'];
//			$vo['give_goldcoin']            = $item['give_goldcoin'];
            $vo['performance_bai_cash'] = $item['performance_bai_cash'];
//			$vo['performance_bai_goldcoin'] = $item['performance_bai_goldcoin'];
//			$vo['performance_bai_points']   = $item['performance_bai_points'];
            $vo['order_id'] = $order_id;
            $res = M('order_product')->add($vo);
            // 此记录使用于代理区加入购物车判断
            $record_data['user_id'] = $user['id'];
            $record_data['product_id'] = $item['product_id'];
            $record_data['ctime'] = time();
            $ret = M('record_history')->add($record_data);
            if ($res === false || $ret === false) {
                $batch = false;
                break;
            }
        }
        if (!$batch) {
            return false;
        }

        /** 3.订单附属
         *  当现金积分支付affiliate_cash是支付金额，affiliate_pay=0
         *  当微信支付 affiliate_pay是支付金额， affiliate_cash=0
         */
        $af['order_id'] = $order_id;
        $af['affiliate_credits'] = 0;
        $af['affiliate_supply'] = 0;
        $af['affiliate_goldcoin'] = 0;
        $af['affiliate_freight'] = $data['total_freight'] ?: 0;
        $af['affiliate_cash'] = $data['pay_amount']; //($paytype == 1)?$data['pay_amount']:0;
        
        switch ($data['payway']) {
        	case '6':
        		$af['affiliate_goldcoin'] = $data['total_cash'];
        		$af['affiliate_pay'] = $data['pay_amount'];
        		break;
        	case '7': //对银行卡支付金额增加随机数
        		$rand_code = randCode(2,1);
        		$af['affiliate_pay'] = $data['pay_amount'] + $rand_code/100;
        		break;
        	default:
        		$af['affiliate_pay'] = $data['pay_amount'];
        }
        
        //公让宝兑换专区特殊处理
        if ($data['block_id'] == C('GRB_EXCHANGE_BLOCK_ID')) {
        	$af['affiliate_goldcoin'] = $data['total_cash'];
        	$af['affiliate_pay'] = $data['total_freight'];
        	$af['affiliate_cash'] = $data['total_freight'];
        	$af['affiliate_pay'] = $data['total_freight'];
        }
        
        $af['affiliate_consignee'] = $address['consignee'];
        $af['affiliate_phone'] = $address['phone'];
        $af['affiliate_city'] = $address['city_address'];
        $af['affiliate_address'] = $address['address'];
        $af['affiliate_postcode'] = $address['postcode'];
        $af['affiliate_pickup'] = 0;
        
        //自提订单+发货时间
        if (intval($address['pickup']) == 1) {
            $af['affiliate_sendtime'] = time();
        }
        $afId = M('order_affiliate')->add($af);
        if ($order_id !== false && $afId !== false) {
            $order['id'] = $order_id;

            return $order;
        } else {
            return false;
        }
    }

    /**
     * 现金积分支付
     *
     * @param unknown $user
     */
    public function cashpay($data, $user)
    {
        $arm = new AccountRecordModel();
        $res2 = true;
        $res3 = true;
        $res4 = true;
        $res5 = true;
        //1.扣除现金积分
        $res1 = $arm->add($user['id'], Currency::Cash, CurrencyAction::CashXiaofei, -$data['pay_amount'], $arm->getRecordAttach(1, '平台', '', $data['order_id']), '商城下单');

        if ($res1 !== false && $res4 !== false) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 公让宝支付
     *
     * @param unknown $user
     */
    public function goldcoinpay($data, $user)
    {
    	$arm = new AccountRecordModel();
    	$res2 = true;
    	$res3 = true;
    	$res4 = true;
    	$res5 = true;
    	
    	$res1 = $arm->add($user['id'], Currency::GoldCoin, CurrencyAction::GoldCoinByXiaofei, -$data['domiciled_goldcoin'], $arm->getRecordAttach(1, '平台', '', $data['order_id']), '商城下单');
    	$res7 = $arm->add($user['id'], Currency::Cash, CurrencyAction::CashXiaofei, -$data['affiliate_pay'], $arm->getRecordAttach(1, '平台', '', $data['order_id']), '商城下单');
    	
    	//商家收款
    	$res6 = true;
    	$user_store = M('order_affiliate')
    		->alias('aff')
    		->join('join __ORDERS__ o ON o.id=aff.order_id')
    		->join('join __STORE__ sto ON sto.id=o.storeid')
    		->where('aff.affiliate_id='.$data['affiliate_id'])
    		->field('sto.uid')
    		->find();
    	if ($user_store) {
    		$res6 = $arm->add($user_store['uid'], Currency::GoldCoin, CurrencyAction::GoldCoinMerchantReceived, $data['domiciled_goldcoin'], $arm->getRecordAttach($user['id'], $user['loginname'], '', $data['order_id']), '商城订单收款');
    	} else {
    		$res6 = false;
    	}
    	
    	if ($res1 !== false && $res4 !== false && $res6 !== false && $res7 !== false) {
    		return true;
    	} else {
    		return false;
    	}
    }
    
    /**
     * 提货券支付
     *
     * @param unknown $user
     */
    public function colorcoinpay($data, $user)
    {
    	$arm = new AccountRecordModel();
    	$res2 = true;
    	$res3 = true;
    	$res4 = true;
    	$res5 = true;
    	 
    	$res1 = $arm->add($user['id'], Currency::ColorCoin, CurrencyAction::colorcoinByXiaofei, -$data['affiliate_pay'], $arm->getRecordAttach(1, '平台', '', $data['order_id']), '商城下单');
    	if ($res1 !== false && $res4 !== false) {
    		return true;
    	} else {
    		return false;
    	}
    }
    
    /**
     * 兑换券支付
     *
     * @param unknown $user
     */
    public function enrollpay($data, $user)
    {
    	$arm = new AccountRecordModel();
    	$res2 = true;
    	$res3 = true;
    	$res4 = true;
    	$res5 = true;
    	 
    	$res1 = $arm->add($user['id'], Currency::Enroll, CurrencyAction::enrollByXiaofei, -$data['affiliate_pay'], $arm->getRecordAttach(1, '平台', '', $data['order_id']), '商城下单');
    	if ($res1 !== false && $res4 !== false) {
    		return true;
    	} else {
    		return false;
    	}
    }
    
    /**
     * 报单币支付
     *
     * @param unknown $user
     */
    public function supplypay($data, $user)
    {
    	$arm = new AccountRecordModel();
    	
    	$res2 = true;
    	$res3 = true;
    	$res4 = true;
    	$res5 = true;
    	
    	//1.扣除报单币
    	$res1 = $arm->add($user['id'], Currency::Supply, CurrencyAction::SupplyXiaofei, -$data['pay_amount'], $arm->getRecordAttach(1, '平台', '', $data['order_id']), '商城下单');
    
    	if ($res1 !== false && $res4 !== false) {
    		return true;
    	} else {
    		return false;
    	}
    }

    /**
     * 1=现金积分支付,2=微信回调,
     *
     * @param unknown $user
     * @param unknown $order
     */
    public function shoppingpay($user, $order, $payway = 'wechat')
    {
    	$EnjoyModel = new EnjoyModel();
    	
        //1.查询订单附属表
        $order_affiliate = M('order_affiliate')->field('order_id, affiliate_credits as domiciled_credits, affiliate_supply as domiciled_supply, affiliate_goldcoin as domiciled_goldcoin, affiliate_colorcoin as domiciled_colorcoin, affiliate_cash as pay_amount, affiliate_pay, affiliate_id, affiliate_freight')
            ->where('order_id = ' . $order['id'])->find();
        //1.扣款
        $res1 = true;
        $res4 = true;
        $result_combined = true;
        
        //解冻
        $frozen_info = M('frozen_fund')->where('order_id='.$order['id'].' and frozen_status=1')->find();
        if (!$frozen_info) {
        	$res3 = true;
        } else {
        	$res3 = M('frozen_fund')->where('order_id = ' . $order['id'])->save(array('frozen_status' => 0, 'frozen_uptime' => time()));
        }
        
        $arm = new AccountRecordModel();
        if ($payway == PaymentMethod::Wechat) {
            $res1 = $arm->add($user['id'], Currency::Cash, CurrencyAction::CashXiaofeiChongzhiWeixin, $order_affiliate['pay_amount'], $arm->getRecordAttach($user['id'], $user['nickname'], $user['img'], $order['id']), '微信支付在线充值');
            //根据支付方式，把另一个金额=0
            //$res4 = M('order_affiliate')->where('order_id = '.$order['id'])->save(array('affiliate_cash'=>0));
        } elseif ($payway == PaymentMethod::Alipay) {
            $res1 = $arm->add($user['id'], Currency::Cash, CurrencyAction::CashXiaofeiChongzhiZhifubao, $order_affiliate['pay_amount'], $arm->getRecordAttach($user['id'], $user['nickname'], $user['img'], $order['id']), '支付宝在线充值');
            //根据支付方式，把另一个金额=0
            //$res4 = M('order_affiliate')->where('order_id = '.$order['id'])->save(array('affiliate_cash'=>0));
        } elseif ($payway == PaymentMethod::Bank) {
        	$res1 = $arm->add($user['id'], Currency::Cash, CurrencyAction::CashXiaofeiChongzhiBank, $order_affiliate['pay_amount'], $arm->getRecordAttach($user['id'], $user['nickname'], $user['img'], $order['id']), '银行卡在线充值');
        } else {
            //$res4 = M('order_affiliate')->where('order_id = '.$order['id'])->save(array('affiliate_pay'=>0));
        }

        if ($payway == 'goldcoin' || $order['amount_type'] == '6') {
        	$res2 = $this->goldcoinpay($order_affiliate, $user);
        } elseif ($payway == 'colorcoin' || $order['amount_type'] == '8') {
        	$res2 = $this->colorcoinpay($order_affiliate, $user);
        } elseif ($payway == 'enroll' || $order['amount_type'] == '9') {
        	$res2 = $this->enrollpay($order_affiliate, $user);
        } elseif ($payway == 'supply' || $order['amount_type'] == '10') {
        	$res2 = $this->supplypay($order_affiliate, $user);
        	
        	//组合支付扣除
        	$result_combined = $this->orderCombinedComplete($order['id']);
        } else {
        	$res2 = $this->cashpay($order_affiliate, $user);
        	
        	//组合支付扣除
        	$result_combined = $this->orderCombinedComplete($order['id']);
        }
        
        //同步订单商品已售数量
        $result_product = true;
        $order_product_list = M('OrderProduct')->where('order_id='.$order['id'])->field('product_id,product_quantity')->select();
        foreach ($order_product_list as $k=>$v) {
        	$result_product = M('Product')->where('id='.$v['product_id'])->setInc('exchangenum', $v['product_quantity']);
        	if ($result_product === false) {
        		break;
        	}
        }
        
        $pm = new ProcedureModel();
        $res = $pm->execute('Event_paid', $order['id'], '@error');
        if (!$res) {
            return false;
        }
        
        //赠送澳洲SKN股数
//      $result_enjoy = $EnjoyModel->consumeGive($order['id']);
        $result_enjoy = true;
        
        if ($res1 !== false && $res2 !== false && $res3 !== false && $res4 !== false && $result_combined !== false && $result_enjoy !== false && $result_product !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取商城订单
     *
     * @param int $tag 0全部；1代发货；2待收货；3已完成；4退款
     * @param int $user_id 用户ID
     * @param int $pn 当前页数
     * @param mixed $block_id 指定板块ID(默认false)
     * @param mixed $no_block_id 排除指定板块的ID(默认false)[与参数$block_id只能启用一个]
     */
    public function getOrderList($tag, $user_id, $pn, $block_id=false, $no_block_id=false)
    {
    	$current_lang = getCurrentLang(true);
    	
        if ($pn < 1) {
            $pn = 1;
        }
        $where['o.uid'] = $user_id;
        $where['o.exchangeway'] = 1;
        if ($tag == 1) {
            $where['o.order_status'] = 1;
        } elseif ($tag == 2) {
            $where['o.order_status'] = 3;
        } elseif ($tag == 3) {
            $where['o.order_status'] = 4;
        } elseif ($tag == 4) {
            $where['o.order_status'] = 2;
        }
        
        //筛选指定板块的订单
        if ($block_id) {
        	$where['o.producttype'] = ['eq', $block_id];
        }
        
        //排除指定板块的订单
        if ($no_block_id) {
        	$where['o.producttype'] = ['neq', $no_block_id];
        }
        
        //列表
        $field_store_name = 's.store_name'.$current_lang.' as store_name';
        $field = $field_store_name."
        		, o.storeid, o.id, o.uid, o.producttype, FROM_UNIXTIME(o.time, '%Y-%m-%d %H:%i') as addtime, o.amount, o.amount_type, o.order_status, o.producttype block_id, o.discount
        		, ifnull(c.cancel_status, -1) cancel
        		, a.affiliate_trackingno, a.affiliate_freight, a.affiliate_pickup, a.affiliate_credits, a.affiliate_supply, a.affiliate_goldcoin, a.affiliate_cash, a.affiliate_pay, a.affiliate_consignee, a.affiliate_phone, a.affiliate_city, a.affiliate_address";
        $list = M('orders o')
            ->field($field)
            ->join('left join zc_order_cancel as c on c.order_id = o.id')
            ->join('left join zc_order_affiliate a on a.order_id = o.id')
            ->join('left join zc_store s on s.id = o.storeid')
            ->where($where)
            ->order('o.id desc')
            ->limit(($pn - 1) * 10, 10)
            ->select();
        //加载商品
        foreach ($list as $k => $v) {
        	//优惠折扣
        	if ($v['discount'] > 0) {
        		$v['affiliate_pay'] = sprintf('%.2f', $v['amount'] * $v['discount'] / 10);
        		$list[$k]['affiliate_pay'] = $v['affiliate_pay'];
        	}
        	
        	$field_name = 'vp.`name'.$current_lang.'` as `name`';
            $products = M('order_product op')
                ->field('vp.id, '.$field_name.', vp.img, vp.price, ifnull(pc.comment_id, 0) as comment_id, op.*')
                ->join('left join zc_product as vp on vp.id = op.product_id')
                ->join('left join zc_product_comment as pc on pc.product_id = op.product_id and pc.order_id = op.order_id')
                ->where('op.order_id = ' . $v['id'])
                ->select();
            foreach ($products as $aa => $bb) {
                $products[$aa]['yunfei'] = sprintf('%.2f', $bb['product_freight'] * $bb['product_quantity']) . '元';
                if ($bb['product_freight'] == 0 && $bb['product_freight_collect'] == 0) {
                    $products[$aa]['yunfei'] = '免运费';
                } elseif ($bb['product_freight_collect'] == 1) {
                    $products[$aa]['yunfei'] = '到付';
                }
                $products[$aa]['price'] = sprintf('￥%.2f元', ($bb['price_cash'] * $bb['product_quantity']));
                
                //公让宝兑换专区特殊处理
                if ($v['block_id'] == C('GRB_EXCHANGE_BLOCK_ID')) {
                	$products[$aa]['price'] = sprintf('%.2f份公让宝', ($bb['price_cash'] * $bb['product_quantity']));
                }
            }
            
            $list[$k]['kuaidi100'] = 'https://m.kuaidi100.com/result.jsp?nu=' . $v['affiliate_trackingno'];
            $products = Image::formatList($products, 'img');
            $list[$k]['items'] = $products;
            $list[$k]['affiliate_freight'] = sprintf('%.2f', $v['affiliate_freight']);
            $list[$k]['yunfei'] = '￥' . $v['affiliate_freight'] . '元';
            if ($list[$k]['affiliate_freight'] * 1 == 0) {
                $list[$k]['yunfei'] = '免运费';
            }
            
            $list[$k]['pricestr'] = sprintf('实付: ￥%.2f元', $v['affiliate_pay']);
            
            //公让宝兑换专区特殊处理
            if ($v['block_id'] == C('GRB_EXCHANGE_BLOCK_ID')) {
            	$list[$k]['pricestr'] = sprintf('总计：%.2f份公让宝', $v['affiliate_goldcoin']). '+'. sprintf('%.2f元', $v['affiliate_pay']);
            }
            
            //订单PV
            $pv_data = $this->getOrderPV($v['id']);
            $list[$k] = array_merge($list[$k], $pv_data);
        }

        $return['list'] = $list;

        //查询全部的总数
        //if($tag == 0){
        //总数
        unset($where['o.order_status']);
        $return['count0'] = M('orders o')
            ->field("s.store_name, o.storeid, o.id, FROM_UNIXTIME(o.time, '%Y-%m-%d %H:%i') as addtime, o.amount, o.amount_type, o.order_status, ifnull(c.cancel_status, -1) cancel, a.affiliate_trackingno, a.affiliate_freight, a.affiliate_pickup")
            ->where($where)
            ->count();
        $where['o.order_status'] = 1;
        $return['count1'] = M('orders o')
            ->field("s.store_name, o.storeid, o.id, FROM_UNIXTIME(o.time, '%Y-%m-%d %H:%i') as addtime, o.amount, o.amount_type, o.order_status, ifnull(c.cancel_status, -1) cancel, a.affiliate_trackingno, a.affiliate_freight, a.affiliate_pickup")
            ->where($where)
            ->count();
        $where['o.order_status'] = 3;
        $return['count2'] = M('orders o')
            ->field("s.store_name, o.storeid, o.id, FROM_UNIXTIME(o.time, '%Y-%m-%d %H:%i') as addtime, o.amount, o.amount_type, o.order_status, ifnull(c.cancel_status, -1) cancel, a.affiliate_trackingno, a.affiliate_freight, a.affiliate_pickup")
            ->where($where)
            ->count();
        $where['o.order_status'] = 4;
        $return['count3'] = M('orders o')
            ->field("s.store_name, o.storeid, o.id, FROM_UNIXTIME(o.time, '%Y-%m-%d %H:%i') as addtime, o.amount, o.amount_type, o.order_status, ifnull(c.cancel_status, -1) cancel, a.affiliate_trackingno, a.affiliate_freight, a.affiliate_pickup")
            ->where($where)
            ->count();
        $where['o.order_status'] = 2;
        $return['count4'] = M('orders o')
            ->field("s.store_name, o.storeid, o.id, FROM_UNIXTIME(o.time, '%Y-%m-%d %H:%i') as addtime, o.amount, o.amount_type, o.order_status, ifnull(c.cancel_status, -1) cancel, a.affiliate_trackingno, a.affiliate_freight, a.affiliate_pickup")
            ->where($where)
            ->count();

        return $return;
    }


    /**
     * 获取商家的商城订单--预留
     *
     * @param unknown $tag 0全部；1代发货；2待收货；3已完成；4退款
     */
    public function getMchOrderList($tag, $store_id, $pn)
    {
        if ($pn < 1) {
            $pn = 1;
        }
        $where['o.storeid'] = $store_id;
        $where['o.exchangeway'] = 1;
        if ($tag == 1) {
            $where['o.order_status'] = 1;
        } elseif ($tag == 2) {
            $where['o.order_status'] = 3;
        } elseif ($tag == 3) {
            $where['o.order_status'] = 4;
        } elseif ($tag == 4) {
            $where['o.order_status'] = 2;
        }
        $list = M('orders o')
            ->field("o.id, FROM_UNIXTIME(o.time, '%Y-%m-%d %H:%i') as addtime, o.amount, o.amount_type, o.order_status, ifnull(c.cancel_status, -1) cancel, o.order_number, m.nickname, m.img, a.affiliate_trackingno, a.affiliate_freight, a.affiliate_pickup")
            ->join('left join zc_order_cancel as c on c.order_id = o.id')
            ->join('left join zc_order_affiliate a on a.order_id = o.id')
            ->join('left join zc_member as m on m.id = o.uid')
            ->where($where)
            ->order('o.id desc')
            ->limit(($pn - 1) * 10, 10)
            ->select();
        //加载商品
        foreach ($list as $k => $v) {
            $products = M('order_product op')
                ->field('vp.id, vp.`name`, vp.img, vp.price, op.*')
                ->join('left join zc_view_product as vp on vp.id = op.product_id')
                ->where('op.order_id = ' . $v['id'])
                ->select();
            $list[$k]['items'] = $products;
        }
        $return['list'] = $list;

        //查询全部的总数
        //if($tag == 0){
        //总数
        unset($where['o.order_status']);
        $return['count0'] = M('orders o')
            ->field("s.store_name, o.storeid, o.id, FROM_UNIXTIME(o.time, '%Y-%m-%d %H:%i') as addtime, o.amount, o.amount_type, o.order_status, ifnull(c.cancel_status, -1) cancel, a.affiliate_trackingno, a.affiliate_freight, a.affiliate_pickup")
            ->where($where)
            ->count();
        $where['o.order_status'] = 1;
        $return['count1'] = M('orders o')
            ->field("s.store_name, o.storeid, o.id, FROM_UNIXTIME(o.time, '%Y-%m-%d %H:%i') as addtime, o.amount, o.amount_type, o.order_status, ifnull(c.cancel_status, -1) cancel, a.affiliate_trackingno, a.affiliate_freight, a.affiliate_pickup")
            ->where($where)
            ->count();
        $where['o.order_status'] = 3;
        $return['count2'] = M('orders o')
            ->field("s.store_name, o.storeid, o.id, FROM_UNIXTIME(o.time, '%Y-%m-%d %H:%i') as addtime, o.amount, o.amount_type, o.order_status, ifnull(c.cancel_status, -1) cancel, a.affiliate_trackingno, a.affiliate_freight, a.affiliate_pickup")
            ->where($where)
            ->count();
        $where['o.order_status'] = 4;
        $return['count3'] = M('orders o')
            ->field("s.store_name, o.storeid, o.id, FROM_UNIXTIME(o.time, '%Y-%m-%d %H:%i') as addtime, o.amount, o.amount_type, o.order_status, ifnull(c.cancel_status, -1) cancel, a.affiliate_trackingno, a.affiliate_freight, a.affiliate_pickup")
            ->where($where)
            ->count();
        $where['o.order_status'] = 2;
        $return['count4'] = M('orders o')
            ->field("s.store_name, o.storeid, o.id, FROM_UNIXTIME(o.time, '%Y-%m-%d %H:%i') as addtime, o.amount, o.amount_type, o.order_status, ifnull(c.cancel_status, -1) cancel, a.affiliate_trackingno, a.affiliate_freight, a.affiliate_pickup")
            ->where($where)
            ->count();

//     	}else{
//     		$return['count0'] = 0;
//     		$return['count1'] = 0;
//     		$return['count2'] = 0;
//     		$return['count3'] = 0;
//     		$return['count4'] = 0;
//     	}
        return $return;
    }

    /**
     * 结算订单获取收货地址信息
     *
     * @param unknown $user_id
     * @param unknown $addr_id = 0 表示自提
     */
    public function getCheckoutAddr($user_id, $addr_id = 0)
    {
        if ($addr_id == 0) {
            return array(
                'id' => 0,
                'uid' => $user_id,
                'consignee' => '',
                'phone' => '',
                'city_address' => '',
                'address' => '',
                'postcode' => '',
                'pickup' => 1
            );
        } else {
            return M('address')->where('uid = ' . $user_id . ' and id = ' . $addr_id)->find();
        }
    }
    
    /**
     * 计算订单PV值和弹窗提示
     * 
     * @param int $order_id 订单ID
     */
    public function getOrderPV($order_id) {
    	$order = M('Orders')->where('id='.$order_id)->field('producttype,uid')->find();
    	
    	$pv = M('OrderProduct')->where('order_id='.$order_id)->sum('price_cash * product_quantity * performance_bai_cash * 0.01');
    	
    	//计算折扣优惠
    	$discount = $this->getUserDiscount($order['uid'], $order['producttype'])['discount'];
    	if ($discount > 0) {
    		$pv = $pv * $discount / 10;
    	}
    	 
    	$pv = sprintf('%.2f', $pv);
    	
    	$msg = $this->getMsgByPV($pv);
    	 
    	$data = [
	    	'pv' => $pv,
	    	'msg' => $msg
    	];
    	 
    	return $data;
    }
    
    /**
     * 获取指定PV对应弹窗提示
     * 
     * @param double $pv PV值
     */
    public function getMsgByPV($pv) {
    	$performancePortionBase = M('Settings')->where("settings_code='performance_portion_base'")->getField('settings_value');
    	$portion = sprintf('%.1f', $pv / $performancePortionBase);
    	
    	if ($portion < 1) {
    		$need_pv = sprintf('%.0f', $performancePortionBase - $pv);
    		$msg = "该订单只有{$pv}PV业绩值，还差{$need_pv}业绩值才送您一个新农场，您是否继续支付？";
    	} else {
    		$msg = "该订单有{$pv}PV业绩值，恭喜您获赠{$portion}个新农场，您是否继续支付？";
    	}
    	
    	return $msg;
    }
    
    /**
     * 获取用户对应板块商品折扣优惠
     * 
     * @param int $user_id 用户ID
     * @param int $block_id 板块ID
     * 
     * @return array 折扣优惠和公让宝抵扣比例
     */
    public function getUserDiscount($user_id, $block_id) {
    	$discount = 0; //0:无折扣
    	$goldcoin_percent = 0; //0:不支持公让宝抵扣
    	
    	//获取折扣优惠比例
    	$member_level = M('Member')->where('id='.$user_id)->getField('level');
    	if ($member_level == 2) { //level不等于2不能享受折扣优惠
    		$user_role_star = 1;
    		
    		$user_role_info = M('Consume')->field('level')->where('user_id='.$user_id.' and level=5')->find();
    		if ($user_role_info) {
    			$user_role_star = 5;
    		}
    		
    		$discount = M('block')->where('block_id='.$block_id)->find();
    		$discount = $discount['block_discount_'.$user_role_star];
    	}
    	
    	
    	//获取公让宝抵扣比例
    	$goldcoin_percent = M('block')->where('block_id='.$block_id)->getField('block_goldcoin_percent');
    	
    	$data = [
    		'discount' => $discount,
    		'goldcoin_percent' => $goldcoin_percent
    	];
    	
    	return $data;
    }
    
    /**
     * 购物车可组合支付类型信息
     * 
     * @param int $user_id 用户ID
     * @param int $block_id 板块ID
     * @param double $amount 支付金额
     */
    public function CombinedCurrency($user_id, $block_id, $amount) {
    	$AccountModel = new AccountModel();
    	$GoldcoinPricesModel = new GoldcoinPricesModel();
    	
    	$block_info = M('Block')->where('block_id='.$block_id)->find();
    	 
    	//公让宝
    	$goldcoin_percent_amount_original = $amount * $block_info['block_goldcoin_percent'] / 100;
    	$goldcoin_percent_amount_original = $GoldcoinPricesModel->getGrbByRmb($goldcoin_percent_amount_original);
    	$goldcoin_balance = $AccountModel->getBalance($user_id, Currency::GoldCoin);
    	$goldcoin_percent_amount = $goldcoin_percent_amount_original > $goldcoin_balance ? $goldcoin_balance : $goldcoin_percent_amount_original;
    	
    	//大礼包区强制显示原本抵扣金额
    	if ($block_id == C('GIFT_PACKAGE_BLOCK_ID')) {
    		$goldcoin_percent_amount = $goldcoin_percent_amount_original;
    	}
    	
    	$data = [
	    	'goldcoin' => [
	    		'title' => Currency::getLabel(Currency::GoldCoin).'抵扣',
	    		'price' => $GoldcoinPricesModel->getInfo('amount')['amount'],
		    	'percent' => $block_info['block_goldcoin_percent'],
		    	'percent_amount_original' => sprintf('%.4f', $goldcoin_percent_amount_original), //折扣优惠后兵转换为公让宝的原本抵扣金额
		    	'percent_amount' => sprintf('%.4f', $goldcoin_percent_amount), //折扣优惠后并转换为公让宝的可抵扣金额
 		    	'balance' => $goldcoin_balance, //公让宝余额
    			'pay_amount' => sprintf('%.2f', $amount - $GoldcoinPricesModel->getRmbByGrb($goldcoin_percent_amount)), //组合抵扣后剩余待支付金额
    			'buy_show' => sprintf('%.4f', $goldcoin_percent_amount).'份('.$GoldcoinPricesModel->getInfo('amount')['amount'].'元/份)',
    			'is_must' => false, //是否必须使用该组合支付
	    	]
    	];
    	
    	//英文版特殊处理
    	$current_lang = getCurrentLang();
    	if ($current_lang == 'en') {
    		$data['goldcoin']['buy_show'] = sprintf('%.4f', $goldcoin_percent_amount).'份';
    	}
    	
    	return $data;
    }
    
    /**
     * [公让宝兑换专区专用] 购物车可组合支付类型信息 
     *
     * @param int $user_id 用户ID
     * @param int $block_id 板块ID
     * @param double $amount 公让宝支付金额
     * @param double $amount_pay 现金支付金额
     */
    public function CombinedCurrencyByGrbExchange($user_id, $block_id, $amount, $amount_pay) {
    	$AccountModel = new AccountModel();
    	$GoldcoinPricesModel = new GoldcoinPricesModel();
    	 
    	$block_info = M('Block')->where('block_id='.$block_id)->find();
    
    	//公让宝
    	$goldcoin_percent_amount_original = $amount * $block_info['block_goldcoin_percent'] / 100;
    	$goldcoin_balance = $AccountModel->getBalance($user_id, Currency::GoldCoin);
    	$goldcoin_percent_amount = $goldcoin_percent_amount_original > $goldcoin_balance ? $goldcoin_balance : $goldcoin_percent_amount_original;
    	 
    	$data = [
	    	'goldcoin' => [
		    	'title' => Currency::getLabel(Currency::GoldCoin).'支付',
		    	'price' => $GoldcoinPricesModel->getInfo('amount')['amount'],
		    	'percent' => $block_info['block_goldcoin_percent'],
		    	'percent_amount_original' => sprintf('%.4f', $goldcoin_percent_amount_original), //折扣优惠后兵转换为公让宝的原本抵扣金额
		    	'percent_amount' => sprintf('%.4f', $goldcoin_percent_amount), //折扣优惠后并转换为公让宝的可抵扣金额
		    	'balance' => $goldcoin_balance, //公让宝余额
		    	'pay_amount' => sprintf('%.2f', $amount_pay), //组合抵扣后剩余待支付金额
		    	'buy_show' => sprintf('%.4f', $goldcoin_percent_amount).'份',
		    	'is_must' => false, //是否必须使用该组合支付
	    	]
    	];
    	 
    	return $data;
    }
    
    /**
     * 订单可组合支付类型信息
     * 
     * @param int $order_id 订单ID
     */
    public function orderCombinedCurrency($order_id) {
    	$AccountModel = new AccountModel();
    	$GoldcoinPricesModel = new GoldcoinPricesModel();
    	
    	$order_info = M('Orders')
    		->alias('o')
    		->join('join __ORDER_AFFILIATE__ aff ON aff.order_id=o.id')
    		->where('o.id='.$order_id)
    		->field('o.producttype as block_id, o.amount, o.uid, o.discount')
    		->find();
    	 
    	$block_info = M('Block')->where('block_id='.$order_info['block_id'])->find();
    	
    	$discount = empty($order_info['discount']) ? 1 : $order_info['discount'] / 10;
    	
    	//折扣后金额
    	$amount = $discount>0 ? $order_info['amount'] * $discount : $order_info['amount'];
    	
    	//公让宝
    	$goldcoin_percent_amount_original = $amount * $block_info['block_goldcoin_percent'] / 100;
    	$goldcoin_percent_amount_original = $GoldcoinPricesModel->getGrbByRmb($goldcoin_percent_amount_original);
    	$goldcoin_balance = $AccountModel->getBalance($order_info['uid'], Currency::GoldCoin);
    	$goldcoin_percent_amount = $goldcoin_percent_amount_original > $goldcoin_balance ? $goldcoin_balance : $goldcoin_percent_amount_original;

    	$data = [
	    	'goldcoin' => [
	    		'title' => Currency::getLabel(Currency::GoldCoin).'抵扣',
	    		'price' => $GoldcoinPricesModel->getInfo('amount')['amount'],
		    	'percent' => $block_info['block_goldcoin_percent'],
		    	'percent_amount_original' => sprintf('%.4f', $goldcoin_percent_amount_original), //折扣优惠后兵转换为公让宝的原本抵扣金额
		    	'percent_amount' => sprintf('%.4f', $goldcoin_percent_amount), //折扣优惠后并转换为公让宝的可抵扣金额
		    	'balance' => $goldcoin_balance, //公让宝余额
				'pay_amount' => sprintf('%.2f', $amount - $GoldcoinPricesModel->getRmbByGrb($goldcoin_percent_amount)), //组合抵扣后剩余待支付金额
				'buy_show' => sprintf('%.4f', $goldcoin_percent_amount).'份('.$GoldcoinPricesModel->getInfo('amount')['amount'].'元/份)',
				'is_must' => false, //是否必须使用该组合支付
	    	]
    	];
    	
    	//英文版特殊处理
    	$current_lang = getCurrentLang();
    	if ($current_lang == 'en') {
    		$data['goldcoin']['buy_show'] = sprintf('%.4f', $goldcoin_percent_amount).'份';
    	}
    	
    	return $data;
    }
    
    /**
     * [公让宝兑换专区专用] 订单可组合支付类型信息
     *
     * @param int $order_id 订单ID
     */
    public function orderCombinedCurrencyByGrbExchange($order_id) {
    	$AccountModel = new AccountModel();
    	$GoldcoinPricesModel = new GoldcoinPricesModel();
    	 
    	$order_info = M('Orders')
	    	->alias('o')
	    	->join('join __ORDER_AFFILIATE__ aff ON aff.order_id=o.id')
	    	->where('o.id='.$order_id)
	    	->field('o.producttype as block_id, o.amount, o.uid, o.discount, aff.affiliate_goldcoin, aff.affiliate_pay')
	    	->find();
    
    	$block_info = M('Block')->where('block_id='.$order_info['block_id'])->find();
    	 
    	$discount = empty($order_info['discount']) ? 1 : $order_info['discount'] / 10;
    	 
    	//折扣后金额
    	$amount = $discount>0 ? $order_info['affiliate_goldcoin'] * $discount : $order_info['affiliate_goldcoin'];
    	 
    	//公让宝
    	$goldcoin_percent_amount_original = $amount * $block_info['block_goldcoin_percent'] / 100;
    	$goldcoin_balance = $AccountModel->getBalance($order_info['uid'], Currency::GoldCoin);
    	$goldcoin_percent_amount = $goldcoin_percent_amount_original > $goldcoin_balance ? $goldcoin_balance : $goldcoin_percent_amount_original;
    
    	$data = [
	    	'goldcoin' => [
		    	'title' => Currency::getLabel(Currency::GoldCoin).'支付',
		    	'price' => $GoldcoinPricesModel->getInfo('amount')['amount'],
		    	'percent' => $block_info['block_goldcoin_percent'],
		    	'percent_amount_original' => sprintf('%.4f', $goldcoin_percent_amount_original), //折扣优惠后兵转换为公让宝的原本抵扣金额
		    	'percent_amount' => sprintf('%.2f', $goldcoin_percent_amount), //折扣优惠后并转换为公让宝的可抵扣金额
		    	'balance' => $goldcoin_balance, //公让宝余额
		    	'pay_amount' => sprintf('%.2f', $order_info['affiliate_pay']), //组合抵扣后剩余待支付金额
		    	'buy_show' => sprintf('%.2f', $goldcoin_percent_amount).'份',
		    	'is_must' => false, //是否必须使用该组合支付
	    	]
    	];
    	 
    	return $data;
    }
    
    /**
     * 订单组合支付下单相关操作 
     * 
     * @param int $order_id 订单ID
     * @param string $combined_currency 组合支付方式
     * 
     * @return array ['status'=>boolean, 'error'=>string]
     */
    public function orderCombined($order_id, $combined_currency) {
    	$AccountModel = new AccountModel();
    	$GoldcoinPricesModel = new GoldcoinPricesModel();
    	
    	$return = [
    		'status' => false,
    		'error' => ''
    	];
    	
//     	if ($combined_currency === false) {
//     		$return['status'] = true;
//     		return $return;
//     	}

    	//获取订单的板块ID
    	$block_id = M('Orders')->where('id='.$order_id)->getField('producttype');
    	if ($block_id == C('GRB_EXCHANGE_BLOCK_ID')) {
    		$data = $this->orderCombinedCurrencyByGrbExchange($order_id);
    	} else {
    		$data = $this->orderCombinedCurrency($order_id);
    	}
    	
    	$result_percent_order = true;
    	$result_percent_frozen = true;
    	$result_discount = true;
    	
    	//获取订单和附属表信息
    	$order_info = M('Orders')
	    	->alias('o')
	    	->join('join __ORDER_AFFILIATE__ aff ON aff.order_id=o.id')
	    	->field('o.discount, o.uid, o.amount, o.producttype, aff.*')
	    	->where('o.id='.$order_id)
	    	->find();
    	
    	$percent_amount = 0;
    	if ($combined_currency) {
    		$combined = $data[$combined_currency];
    		
    		//检测支付比例
    		if ($combined['percent'] == 0) {
    			$return['error'] = '暂不支持该币种的组合支付';
    			return $return;
    		}
    	
    		//检测是否有余额
    		if ($combined['balance'] <= 0) {
    			$return['error'] = '公让宝余额不足';
    			return $return;
    		}
    	
    		//转可支付金额为公让宝金额，并记入订单affiliate_goldcoin字段
    		if ($combined_currency == Currency::GoldCoin) {
	    		$data_percent_order = [];
	    		
	    		//转换为公让宝金额
	    		$data_percent_order['affiliate_'.$combined_currency] = $combined['percent_amount'];
	    		
	    		//记录公让宝实时价格
	    		$grb_price = $GoldcoinPricesModel->getInfo('amount');
	    		$data_percent_order['affiliate_'.$combined_currency.'_price'] = $grb_price['amount'];
	    		
	    		$result_percent_order = M('order_affiliate')->where('order_id='.$order_id)->save($data_percent_order);
	    		
	    		//冻结货币
	    		$data_frozen['domiciled_goldcoin'] = $combined['percent_amount'];
	    		$result_percent_frozen = $AccountModel->frozenRefund($order_info['uid'], $order_id, $data_frozen, '商城购物冻结组合支付资金');
	    		
	    		$percent_amount = $combined['percent_amount'];
    		}
    	}
    	
    	$data_affiliate = [];
    	
    	//折扣优惠后各币种数值 (组合币种的优惠后金额已在orderCombinedCurrency方法中计算)
    	$discount = $order_info['discount'] > 0 ? $order_info['discount'] / 10 : 1;
    	//公让宝兑换专区除外
    	if ($block_id != C('GRB_EXCHANGE_BLOCK_ID')) {
	    	if ($order_info['affiliate_cash'] > 0) {
	    		$data_affiliate['affiliate_cash'] = $order_info['amount'] * $discount - $GoldcoinPricesModel->getRmbByGrb($percent_amount);
	    	}
	    	if ($order_info['affiliate_freight'] > 0) {
	    		$data_affiliate['affiliate_freight'] = $order_info['affiliate_freight'] * $discount;
	    	}
	    	if ($order_info['affiliate_pay'] > 0) {
	    		$data_affiliate['affiliate_pay'] = $order_info['amount'] * $discount - $GoldcoinPricesModel->getRmbByGrb($percent_amount);
	    	}
    	}
    	
    	//订单不满XX元自动增加YY元运费
//     	$block_info = M('Block')->where('block_id='.$order_info['producttype'])->field('block_freight_order_amount,block_freight_increase_amount')->find();
//     	if ($order_info['amount'] < $block_info['block_freight_order_amount']) {
//     		$data_affiliate['affiliate_freight'] += $block_info['block_freight_increase_amount'];
//     		$data_affiliate['affiliate_cash'] += $block_info['block_freight_increase_amount'];
//     		$data_affiliate['affiliate_pay'] += $block_info['block_freight_increase_amount'];
//     	}
    	
    	if (count($data_affiliate) > 0) {
    		$result_discount = M('order_affiliate')->where('order_id='.$order_id)->save($data_affiliate);
    	}
    	
    	if ($result_percent_order !== false && $result_percent_frozen !== false && $result_discount !== false) {
    		$return['status'] = true;
    	}
    	
    	return $return;
    }
    
    /**
     * 订单组合支付完成相关操作
     * 
     * @param int $order_id 订单ID
     */
    public function orderCombinedComplete($order_id) {
    	$arm = new AccountRecordModel();
    	
    	$result_combined = true;
    	$result_combined_frozen = true;
    	$result_store = true;
    	
    	$order_info = M('Orders')
    		->alias('o')
    		->join('join __ORDER_AFFILIATE__ aff ON aff.order_id=o.id')
    		->join('join __STORE__ sto ON sto.id=o.storeid')
    		->field('o.uid, aff.affiliate_goldcoin, sto.uid store_uid')
    		->where('o.id='.$order_id)
    		->find();
    	
    	if (!empty($order_info['affiliate_goldcoin'])) { //公让宝
    		//解冻金额
    		$frozen_info = M('frozen_fund')->where('order_id='.$order_id.' and frozen_status=1')->find();
    		if ($frozen_info) {
    			$result_combined_frozen = M('frozen_fund')->where('order_id='.$order_id)->save(array('frozen_status' => 0, 'frozen_uptime' => time()));
    		}
    		
    		//扣除余额 添加明细
    		$result_combined = $arm->add($order_info['uid'], Currency::GoldCoin, CurrencyAction::GoldCoinByXiaofei, -$order_info['affiliate_goldcoin'], $arm->getRecordAttach(1, '平台', '', $order_id), '商城下单组合支付');
    		
    		//商家收款
    		$result_store = $arm->add($order_info['store_uid'], Currency::GoldCoin, CurrencyAction::GoldCoinMerchantReceived, $order_info['affiliate_goldcoin'], $arm->getRecordAttach($order_info['uid'], '', '', $order_id), '商城订单收款');
    	}
    	    	
    	if ($result_combined !== false && $result_combined_frozen !== false) {
    		return true;
    	} else {
    		return false;
    	}
    }
    
}
