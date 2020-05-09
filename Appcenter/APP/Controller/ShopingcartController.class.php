<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 购物车
// +----------------------------------------------------------------------
namespace APP\Controller;

use Common\Controller\ApiController;
use V4\Model\AccountModel;
use V4\Model\Image;
use V4\Model\Currency;
use V4\Model\OrderModel;
use V4\Model\AccountRecordModel;
use V4\Model\CurrencyAction;
use V4\Model\GoldcoinPricesModel;
use V4\Model\EnjoyModel;
use V4\Model\ProductModel;
use V4\Model\MiningModel;
use ZhongWYApi;

/**
 * 购物车
 * @author jay
 * 测试店铺id=24143
 */
class ShopingcartController extends ApiController
{

    /**
     * 我的购物车
     */
    public function index()
    {
    	$current_lang = getCurrentLang(true);
    	
    	$ProductModel = new ProductModel();
    	
    	$user_id = intval(I('post.user_id'));
    	
    	$where = [
    		'c.user_id' => ['eq', $user_id],
    		'p.status' => ['eq', 0],
    		'p.manage_status' => ['eq', 1],
    		's.status' => ['eq', 0],
    		's.manage_status' => ['eq', 1]
    	];
    	$field_name = 'p.`name'.$current_lang.'` as name';
    	$field_block_name = 'b.block_name'.$current_lang.' as block_name';
    	$field_store_name = 's.store_name'.$current_lang.' as store_name';
       	$field = 'c.cart_id, c.cart_quantity, c.cart_attr
    			, m.price_cash, m.price_goldcoin, m.price_points, m.performance_bai_cash
    			, a.affiliate_freight
    			, p.id as product_id, '.$field_name.', p.img, p.price, p.storeid
    			, '.$field_store_name.'
    			, b.block_id, '.$field_block_name;
        $list = M('shopping_cart c')->field($field)
        	->join('left join zc_product as p on p.id = c.product_id')
            ->join('left join zc_product_affiliate a on a.product_id = p.id')
            ->join('left join zc_block b on b.block_id = a.block_id')
            ->join('left join zc_product_price m on c.price_id = m.price_id')
            ->join('left join zc_store as s on s.id = p.storeid')
            ->where($where)
            ->order('s.id asc, c.cart_id asc')
            ->select();

        //组合数据
        $cartlist = array();
        $sid = 0;
        foreach ($list as $k => $v) {
            //修改price字段，
            $v['price'] = sprintf('￥%.2f元', $v['price_cash']);
//            if ($v['price_goldcoin'] > 0) {
//                $v['price'] .= sprintf('%.2f', $v['price_goldcoin']) . '公让宝';
//            }

            // $v['img'] = Image::url($v['img']);
            
            //PV
            $pv = $v['price_cash'] * $v['performance_bai_cash']  / 100;
            $v['pv'] = (String)$pv;
            $v['pv_str'] = sprintf('%.0f', $v['performance_bai_cash']). '%业绩';
            
            //公让宝代理专区商品不显示PV + 价格显示为公让宝
            if ($v['block_id'] == C('GRB_EXCHANGE_BLOCK_ID')) {
            	$v['pv'] = '';
            	$v['price'] = sprintf('%.2f', $v['price_cash']). '份丰谷宝';
            }
            
            //组合店铺
            $cartlist[$v['storeid']]['items'][] = $v;
            $cartlist[$v['storeid']]['store_name'] = $v['store_name'];
            $cartlist[$v['storeid']]['storeid'] = $v['storeid'];
        }

        $cartlist = array_values($cartlist);
        $this->myApiPrint('获取成功', 400, $cartlist);
    }


    /**
     * 添加商品到购物车
     */
    public function add()
    {
        $cart['user_id'] = intval(I('post.user_id'));
        $cart['product_id'] = intval(I('post.product_id'));
        $cart['price_id'] = intval(I('post.price_id'));
        $cart['cart_quantity'] = intval(I('post.cart_quantity'));
        $cart['cart_attr'] = I('post.cart_attr');
        $cart['cart_addtime'] = time();
        
        //验证商品状态
        $product = M('product')->where(['id' => $cart['product_id'], 'manage_status' => 1, 'status' => 0])->find();
        if (!$product) {
            $this->myApiPrint('商品不存在');
        }
        
        //验证商家状态
        $map_store = [
        	'id' => ['eq', $product['storeid']],
        	'status' => ['eq', 0],
        	'manage_status' => ['eq', 1] 
        ];
        $store = M('Store')->where($map_store)->find();
        if (!$store) {
        	$this->myApiPrint('商家不存在');
        }

        $user = M('member')->field('level,role,star')->where(['is_lock' => 0, 'is_blacklist' => 0])->find($cart['user_id']);
        if (!$user) {
            $this->myApiPrint('无权限操作');
        }
        if ($product['exchangenum'] + $cart['cart_quantity'] >= $product['totalnum']) {
            $this->myApiPrint('商品库存不足');
        }
        $price = M('product_price')->where(['price_id' => $cart['price_id']])->find();
        if (!$price) {
            $this->myApiPrint('商品不存在');
        }
        
        //判断该商品所属板块是否只允许会员购买
        $product_affiliate = M('ProductAffiliate')->where('product_id='.$cart['product_id'])->field('block_id')->find();
        $block_info = M('Block')->where('block_id='.$product_affiliate['block_id'])->field('block_only_member')->find();
        $user_level = $user['level'];
        if ($block_info['block_only_member'] && $user_level != '2') {
        	$this->myApiPrint('请先购买大礼包区商品，激活账号');
        }
        $ex['user_id'] = $cart['user_id'];
        $ex['product_id'] = $cart['product_id'];
//      $ex['cart_attr']  = $cart['cart_attr'];
        $ex['price_id'] = $cart['price_id'];
        // 2020/4/22 慧文 start
        if ($product_affiliate['block_id'] == 4) {
            // 代理区的产品在购物车中只能有一个
            $where = [
                'a.user_id' => ['eq', $cart['user_id']],
                'p.status' => ['eq', 0],
                'p.manage_status' => ['eq', 1],
                'b.block_id' => ['eq', 4],
            ];
            $find = M('shopping_cart')->alias('a')
                ->join('LEFT JOIN __PRODUCT__ as p on p.id = a.product_id')
                ->join('LEFT JOIN __PRODUCT_AFFILIATE__ b on a.product_id = b.product_id')
                ->where($where)->find();
            if ($find) {
                $this->myApiPrint('代理区的产品在购物车中已存在,不能添加多个!');
            }
            // 判断当前的等级
            if ($user_level == 1 && $product['buy_level'] > 1) { //体验用户
                $this->myApiPrint('您无法购买此等级商品!');
            }
            if ($user_level >= 2) {
                // 初次购买 buy_level = 1 的产品不做判断
                // if ($product['buy_level'] == 1) {
                //     $where['a.user_id'] = $cart['user_id'];
                //     $where['b.buy_level'] = 1;
                //     $count = M('record_history')->alias('a')->join('__PRODUCT__ b ON a.product_id = b.id')->where($where)->count();
                //     if ($count >= 1) {
                //         $this->check_add_query($product, $cart);
                //     }
                // } else {
                    $this->check_add_query($product, $cart);
                // }
            }
        }
        // 2020/4/22 end
        //$consume_amount = M('Consume')->where(['user_id' => $cart['user_id']])->getField('amount');
        // $consume_amount = $consume_info['amount'];
        // edit end
        // if ($consume_amount >= 5000) {
        //     $cc_amount = M('consume c')->field('amount')
        //         ->join('left join zc_member as m on c.user_id = m.id')
        //         ->where(['m.reid' => $cart['user_id']])
        //         ->sum('amount');
        //     if ($cc_amount < $consume_amount * 2) {
        //         $this->myApiPrint(sprintf('直推业绩不满%.2f, 不能再次报单', $consume_amount * 2));
        //     }
        // }

        //验证数据是否存在
        $isexists = M('shopping_cart')->where($ex)->find();
        if ($isexists) {
            // 判断当前产品是否设置了购买限制
            $up['cart_id'] = $isexists['cart_id'];
            $up['user_id'] = $isexists['user_id'];
            $res = M('shopping_cart')->where($up)->save(array('cart_quantity' => array('exp', 'cart_quantity+' . $cart['cart_quantity'])));
        } else {
            $res = M('shopping_cart')->add($cart);
        }
        if ($res !== false) {
            $this->myApiPrint('加入成功', 400);
        } else {
            $this->myApiPrint('加入失败');
        }
    }

    /**
     * 代理产品加购的判断
     * @param $product
     * @param $cart
     */
    protected function check_add_query($product, $cart)
    {
        // 消费信息
        $consume_info = M('Consume')->where(['user_id'=>$cart['user_id']])->field('level,amount,amount_old,income_amount,is_out,dynamic_out,dynamic_worth,static_worth')->find();
        $consume_bak_info = M('ConsumeBak')->where(['user_id'=>$cart['user_id']])->field('sum(amount) amount')->find();
        // 总业绩值
        $consume_pv_all = sprintf('%.2f', $consume_info['amount'] + $consume_bak_info['amount']);
        // 出局倍数
        $out_bei = M('ConsumeRule')->alias('cr')->join('left join __CONSUME__ c ON c.level=cr.level')->where('c.user_id='.$cart['user_id'])->getField('cr.out_bei');
        $out_bei = $out_bei ? $out_bei : 2;
        // 出局剩余价值
        $dynamic_out = $consume_info['amount'] * $out_bei - $consume_info['static_worth'] - $consume_info['dynamic_worth'];
        // 购买逻辑的判断
        if ($consume_pv_all < 5000 && $product['buy_level'] > 1) {
            // 只能购买 1000 和 一次 5000
            $this->myApiPrint('尚未解锁购买此产品的购买权!');
        }
        if ($consume_pv_all >= 5000 && $consume_pv_all < 15000) {
            // 判断剩余价值
            if (!empty($dynamic_out) && $dynamic_out > 0) {
                $this->myApiPrint('您的出局价值还剩'.$dynamic_out.',不允许再次购买!');
            }
            if ($product['buy_level'] != 1 && $product['buy_level'] != 2) {
                $this->myApiPrint('您无法购买此等级商品');
            }
        } elseif ($consume_pv_all >= 15000 && $consume_pv_all < 45000) {
            // 判断剩余价值
            if (!empty($dynamic_out) && $dynamic_out > 0) {
                $this->myApiPrint('您的出局价值还剩'.$dynamic_out.',不允许再次购买!');
            }
            if ($product['buy_level'] != 2 && $product['buy_level'] != 3) {
                $this->myApiPrint('您无法购买此等级商品');
            }
        } elseif ($consume_pv_all >= 45000) {
            // 判断剩余价值
            if (!empty($dynamic_out) && $dynamic_out > 0) {
                $this->myApiPrint('您的出局价值还剩'.$dynamic_out.',不允许再次购买!');
            }
            if ($product['buy_level'] != 3) {
                $this->myApiPrint('您无法购买此等级商品');
            }
        }
    }

    /**
     * 更新购物车
     */
    public function update()
    {
        $user_id = intval(I('post.user_id'));
        $cart_id = intval(I('post.cart_id'));
        $cart_quantity = intval(I('post.cart_quantity'));  //数量

        //验证数据是否存在
        $ex['user_id'] = $user_id;
        $ex['cart_id'] = $cart_id;
        $cart = M('shopping_cart')->where($ex)->find();
        if ($cart) {
            $res = M('shopping_cart')->where($ex)->save(array('cart_quantity' => $cart_quantity));
            $this->myApiPrint('更新成功', 400);
        } else {
            $this->myApiPrint('更新失败,记录不存在');
        }
    }


    /**
     * 删除商品
     */
    public function delete()
    {
        $user_id = intval(I('post.user_id'));
        $cartIds = I('post.cartIds');
        $cartIds = explode(',', $cartIds);  //字符串转数组

        //验证数据是否存在
        foreach ($cartIds as $k => $v) {
            if (intval($v) == 0) {
                continue;
            }
            $ex['user_id'] = $user_id;
            $ex['cart_id'] = $v;
            $res = M('shopping_cart')->where($ex)->delete();
        }
        $this->myApiPrint('删除成功', 400);
    }


    /**
     * 购物车-确认页面 [结算订单页面]
     * $cartIds购物车id， 逗号分隔
     */
    public function confirm()
    {	
    	$OrderModel = new OrderModel();
    	
        $user = getUserInBashu();
        $user_id = $user['id'];
        $cartIds = I('post.cartIds');  //购物车id， 逗号分隔

        $data = $this->getItem($user, 0, 0, '', '', $cartIds, true);
		

        //加载收货地址信息
        $data['hasaddr'] = 0;
        $addr = M('address')->where('uid=' . $user_id)->order('is_default desc')->find();
        if ($addr) {
            $data['addr'] = $addr;
            $data['hasaddr'] = 1;
        }

        //默认加个自提选项
        /*
        $data['pickup'] = array(
            'id' => 0,
            'label' => '自提',
            'info' => '上门自提，将不会发货，不走物流'
        );
        */
        //提示
        $data['notice'] = '丰谷宝余额不足系统自动将差额转换为现金积分去支付';

        $data['zhongwy_switch'] = $this->CFG['zhongwy_switch'] === '开启';
        
        //再次判断支付开关
        if (C('PAY_METHOD_MUST_HT')) {
        	$data['zhongwy_switch'] = false;
        }
        
        //折扣优惠前金额 + 优惠金额 + 优惠后总PV值
        if ($data['discount'] > 0) { 
        	foreach ($data['items'] as $k=>$v) {
        		$data['items'][$k]['pv'] = $data['items'][$k]['pv_old'];
        		$data['items'][$k]['price'] = $data['items'][$k]['price_old'];
        	}
        	
        	$data['yunfei'] = $data['yunfei_old']==0 ? '免运费' : $data['yunfei_old'].'元';
        	$data['yunfei_old'] = $data['yunfei_old']==0 ? '免运费' : $data['yunfei_old'].'元';
        	$data['pay_amount_old'] = sprintf('%.2f', $data['pay_amount'] / $data['discount'] * 10).'元';
        	$data['discount_amount'] = sprintf('%.2f', '-'. $data['pay_amount_old'] * (1 - $data['discount'] / 10)).'元';
        	
        	$data['discount'] = $data['discount'].'折';
        } else {
        	foreach ($data['items'] as $k=>$v) {
        		$data['items'][$k]['price'] = $data['items'][$k]['price_old'];
        	}
        	
        	$data['yunfei'] = $data['yunfei']==0 ? '免运费' : $data['yunfei'].'元';
        	$data['yunfei_old'] = $data['yunfei_old']==0 ? '免运费' : $data['yunfei_old'].'元';
        	$data['pay_amount_old'] = sprintf('%.2f', $data['pay_amount']).'元';
        	$data['discount_amount'] = sprintf('%.2f', 0);
        	$data['discount'] = '无折扣';
        }
        
        //可组合支付币种类型
        $OrderModel = new OrderModel();
        if ($data['block_id'] == C('GRB_EXCHANGE_BLOCK_ID')) { //公让宝兑换专区特殊处理
			
        	$data['combined'] = $OrderModel->CombinedCurrencyByGrbExchange($user_id, $data['block_id'], $data['total_cash'], $data['total_freight']);
        	$data['combined']['goldcoin']['is_must'] = true;
        	if ($data['combined']['goldcoin']['balance'] < $data['total_cash']) {
        		$this->myApiPrint('丰谷宝余额不足');
        	}
			
        } else {
        	$data['combined'] = $OrderModel->CombinedCurrency($user_id, $data['block_id'], $data['total_fee']);
        	
        	//需强制按板块设置比例使用公让宝抵扣(除特价区外)
        	if ($data['block_id'] != C('TEJIA_BLOCK_ID')) {
        		$data['combined']['goldcoin']['is_must'] = true;
        		
        		if ($data['combined']['goldcoin']['percent'] > 0) {
        			if ($data['combined']['goldcoin']['balance'] < $data['combined']['goldcoin']['percent_amount_original']) {
        				$this->myApiPrint('丰谷宝余额不足，需'.$data['combined']['goldcoin']['percent_amount_original'].'份丰谷宝');
        			}
        		}
        	}
        	
        	if (isset($data['combined']['goldcoin']['percent'])) {
        		$percent = $data['combined']['goldcoin']['percent']=='0.00' ? '0.00' : $data['combined']['goldcoin']['percent'];
        		$data['combined']['goldcoin']['title'] .= "({$percent}%)";
        	}
        	
        	$data['pay_amount'] .= '元';
        }
        
        //大礼包区强制必须使用公让宝组合支付
        $data['gift_package_block_pay_notice'] = '';
        if ($data['block_id'] == C('GIFT_PACKAGE_BLOCK_ID')) {
        	$data['combined']['goldcoin']['is_must'] = true;
        	$data['gift_package_block_pay_notice'] = '大礼包区兑换方式为50%现金积分+50%丰谷宝通证'; //大礼包区支付方式提示说明文字
        	
        	//公让宝余额不足时不能支付
//         	if ($data['combined']['goldcoin']['balance'] < $data['combined']['goldcoin']['percent_amount_original']) {
//         		$this->myApiPrint('丰谷宝余额不足');
//         	}
        }
        
        //微信支付和充值每日限额提示
        $data['third_all_max_amount_explain'] = '微信每日支付充值最大限额为'.$this->CFG['third_all_max_amount'].'元';
        
        //支付密码弹出框文字数组
        $data['pay_password_show'] = [];
        if ($data['combined']['goldcoin']['percent_amount'] > 0) {
        	$data['pay_password_show'][] = ['label' => '丰谷宝抵扣：', 'value' => $data['combined']['goldcoin']['percent_amount'].'份'];
        }
        if ($data['combined']['goldcoin']['pay_amount'] > 0) {
        	$data['pay_password_show'][] = ['label' => '现金积分支付：', 'value' => $data['combined']['goldcoin']['pay_amount'].'元'];
        }
        
        $data['combined']['goldcoin']['pay_amount'] .= '元';
		$this->myApiPrint('请求成功', 400, $data);
    }


    /**
     * 购物车结算订单，提交订单 [提交订单按钮操作触发]
     */
    public function checkout()
    {
    	$current_lang = getCurrentLang();
    	
    	$om = new OrderModel();
    	
        //$this->myApiPrint('库存不足');
        //23:30之后不能下单
//        $timestr = date('Y-m-d', time()) . ' 23:30';
//        if (time() > strtotime($timestr)) {
//            $this->myApiPrint('每日23:30之后不能下单！');
//        }

        $user_id = intval(I('post.user_id'));
        $addr_id = intval(I('post.addr_id')); //收货地址id
        $payway = intval(I('post.payway'));   //1:现金积分, 2:微信, 3:支付宝, 4:第三方-微信, 5:第三方-支付宝, 6:公让宝, 7:银行卡, 8:提货券, 9:兑换券, 10:报单币
        $cartIds = I('post.cartIds');         //购物车id， 逗号分隔

        $province = $this->post['province'];
        $city = $this->post['city'];
        $country = $this->post['country'];
        
        $remark = $this->post['remark']; //备注说明

        if ($current_lang == 'zh-cn') {
        	if (!validateExtend($province, 'CHS') || !validateExtend($city, 'CHS') || !validateExtend($country, 'CHS')) {
        		$this->myApiPrint('请选择省市区');
        	}
        }

        $user = M('member')->where('id = ' . $user_id)->find();
        if (empty($user) || $user['is_lock'] > 0 || $user['is_blacklist'] > 0) {
            $this->myApiPrint('账号异常，禁止兑换');
        }

        $address = $om->getCheckoutAddr($user_id, $addr_id);
        if (empty($address)) {
            $this->myApiPrint('收货地址不存在');
        }
        //把省市区加入address
        $address['province'] = $province;
        $address['city'] = $city;
        $address['country'] = $country;

        //购物车信息
        $data = $this->getItem($user, 0, 0, '', '', $cartIds);
        if ($data['pay_amount'] < 0) {
            $this->myApiPrint('付款金额计算超时');
        }
        
        $data['remark'] = $remark;
        
        $payway = empty($payway) ? 1 : $payway;
        $data['payway'] = $payway;
        
        //大礼包专区：公让宝余额不足时不能支付
        if ($data['block_id'] == C('GIFT_PACKAGE_BLOCK_ID')) {
        	$combined_info = $om->CombinedCurrency($user['id'], $data['block_id'], $data['total_fee']);
	        if ($combined_info['goldcoin']['balance'] < $combined_info['goldcoin']['percent_amount_original']) {
	        	$this->myApiPrint('丰谷宝余额不足');
	        }
        }

        M()->startTrans();
        
        //微信+现金积分 只下单，不支付
        $order = $om->build($data, $user, $address);
        
        //冻结货币
        $am = new AccountModel();
        $res2 = $am->frozenRefund($user_id, $order['id'], $data, '商城购物冻结资金');
        
        //清空购物车
        $res3 = $this->clearAll($user_id, $cartIds);
        
        //检测未实名认证则将收货地址信息记入实名认证信息中
        $result4 = true;
        $map_certification = [
        	'user_id' => ['eq', $user_id],
        	'certification_status' => ['neq', 1]
        ];
        $certification_exists = M('certification')->where($map_certification)->find();
        if (!$certification_exists) {
        	$data_certification = [
        		'user_id' => $user_id,
        		'province' => $province,
        		'city' => $city,
        		'country' => $country,
        		'certification_status' => 0,
        		'certification_addtime' => time(),
        		'certification_uptime' => time()
        	];
        	$result4 = M('Certification')->add($data_certification);
        }
        
        if ($order !== false && $res2 !== false && $res3 !== false && $result4 !== false) {
            M()->commit();
            $this->myApiPrint('下单成功，请30分钟完成付款！', 400, ['order_id' => $order['id']]);
        } else {
            M()->rollback();
            $this->myApiPrint('下单失败', 300);
        }
    }


    /**
     * 微信+现金积分立即支付
     */
    public function paynow()
    {
    	$om = new OrderModel();
    	$GoldcoinPricesModel = new GoldcoinPricesModel();
    	$am = new AccountModel();
    	
        $order_id = intval(I('post.order_id'));
        $user_id = intval(I('post.user_id'));
        $payway = intval(I('post.payway'));   //支付方式  1:现金积分, 2:微信, 3:支付宝, 4:第三方-微信, 5:第三方-支付宝, 6:公让宝, 7:银行卡, 8:提货券, 9:兑换券, 10:报单币
        $payway_combined = intval(I('post.payway_combined')); //组合支付方式 (此方式只支持有可支付比例配置):币种类型和payway一致【默认不启用】
        
        //虚拟币支付方式ID
        $virtual_currency = [6, 8, 9, 10];
        
        //组合支付类型转换为币种
        $payway_combined_currency = false;
        if ($payway_combined) {
        	switch ($payway_combined) {
        		case '6' :
        			$payway_combined_currency = Currency::GoldCoin;
        			
        			//主支付方式不能为非现金的虚拟币
        			if (in_array($payway, $virtual_currency)) {
        				$this->myApiPrint('主支付方式不能和丰谷宝进行组合支付');
        			}
        			break;
        		default:
        			$this->myApiPrint('暂不支持该币种组合支付');
        	}
        }

        $where['o.id'] = $order_id;
        $where['o.uid'] = $user_id;
        $order = M('orders o')->field('o.*, a.affiliate_pay')
            ->join('left join zc_order_affiliate a on a.order_id = o.id')
            ->where($where)
            ->find();
        if (empty($order)) {
            $this->myApiPrint('订单不存在');
        }
        if ($order['time'] < time() - 1800) {
            //更新已过期
            M()->execute(' UPDATE `zc_orders` AS o, `zc_frozen_fund` AS ff '
                . 'SET '
                . '  o.`order_status`   = 2, '
                . '  ff.`frozen_status` = 0,  '
                . '  o.`cancel_reason`  = \'系统自动取消\', '
                . '  o.`cancel_descp`   = \'超时未付款，系统自动取消\', '
                . '  o.`cancel_time`    =  ' . time()
                . ' WHERE o.`id` = ff.`order_id` '
                . '   AND o.`order_status` = 0 and o.uid = ' . $user_id
                . '   AND o.`time` < ' . (time() - 1800));
            $this->myApiPrint('订单超过30分钟未付款自动取消，请重新下单.');
        }
        if ($order['order_status'] != 0) {
            $this->myApiPrint('订单状态异常');
        }
        
        //公让宝兑换专区必须使用公让宝组合支付
        if ($order['producttype'] == C('GRB_EXCHANGE_BLOCK_ID')) {
        	if (empty($payway_combined) || $payway_combined != '6') {
        		$this->myApiPrint('丰谷宝兑换专区商品需勾选丰谷宝支付');
        	}
        }
        
        M()->startTrans();
        
        if ($payway == 2) { //微信支付
//             $this->myApiPrint('');

            //生成流水记录
            $res1 = true;
            $pinfo = M('orders_pay_info')->where(array('uid' => $user_id, 'order_number' => $order['order_number']))->find();
            if (!$pinfo) {
                $res1 = M('orders_pay_info')->add(array('uid' => $user_id, 'order_number' => $order['order_number']));
            }
            
            $res2 = M('orders')->where('id=' . $order_id)->save(array('amount_type' => 2));
            
            //组合支付
            $result_combined = $om->orderCombined($order_id, $payway_combined_currency);
            if (!$result_combined['status']) {
            	M()->rollback();
            	$this->myApiPrint($result_combined['error']);
            } else {
            	$result_combined = $result_combined['status'];
            }
            
            if ($res1 === false || $res2 === false || $result_combined === false) {
                M()->rollback();
                $this->myApiPrint('订单状态异常，支付失败');
            }
            
            M()->commit();
            
			//重新获取订单需支付金额(因优惠折扣等计算会影响到最终支付金额)
			$affiliate_pay = M('OrderAffiliate')->where('order_id='.$order_id)->getField('affiliate_pay');
			
			//判断每日限额
			$third_all_max_amount = $this->CFG['third_all_max_amount'];
			if ($affiliate_pay >= $third_all_max_amount) {
				$this->myApiPrint('微信支付金额已超今日限额，请使用现金积分支付');
			} else {
				$user_third_all_amount_by_today = getUserThirdAmountByToday($user_id, '2');
				if (($user_third_all_amount_by_today + $affiliate_pay) > $third_all_max_amount) {
					$this->myApiPrint('微信支付金额已超今日限额，请使用现金积分支付');
				}
			}
            
            $signStr = $om->getWxpaySign($order['order_number'], $affiliate_pay, 'Notify/shopping');
            $returndata = $om->format_return('返回成功', 400, $signStr);
            $this->ajaxReturn($returndata);
            exit;
        } elseif ($payway == 3) { //支付宝
//             $this->myApiPrint('请使用现金积分支付');
           $res1 = true;
           $pinfo = M('orders_pay_info')->where(array('uid' => $user_id, 'order_number' => $order['order_number']))->find();
           if (!$pinfo) {
               $res1 = M('orders_pay_info')->add(array('uid' => $user_id, 'order_number' => $order['order_number']));
           }
           $res2 = M('orders')->where('id=' . $order_id)->save(array('amount_type' => 3));
           
           //组合支付
           $result_combined = $om->orderCombined($order_id, $payway_combined_currency);
           if (!$result_combined['status']) {
	           	M()->rollback();
	           	$this->myApiPrint($result_combined['error']);
           } else {
           		$result_combined = $result_combined['status'];
           }
           
           if ($res1 === false || $res2 === false || $result_combined === false) {
               M()->rollback();
               $this->myApiPrint('订单状态异常，支付失败');
           }
           
           M()->commit();
           
           //重新获取订单需支付金额(因优惠折扣等计算会影响到最终支付金额)
           $affiliate_pay = M('OrderAffiliate')->where('order_id='.$order_id)->getField('affiliate_pay');
           
           $signStr = $om->getAlipaySign($order['order_number'], $affiliate_pay, 'Notify/shopping');
           $returndata = $om->format_return('返回成功', 400, $signStr);
           $this->ajaxReturn($returndata);
           $this->myApiPrint('订单状态异常，支付失败');
        } elseif ($payway == 4) { // 第三方-微信
            if ($this->CFG['zhongwy_switch'] !== '开启') {
                $this->myApiPrint('请使用其他支付方式');
            }
            $res1 = true;
            $pinfo = M('orders_pay_info')->where(array('uid' => $user_id, 'order_number' => $order['order_number']))->find();
            if (!$pinfo) {
                $res1 = M('orders_pay_info')->add(array('uid' => $user_id, 'order_number' => $order['order_number']));
            }
            $res2 = M('orders')->where('id=' . $order_id)->save(array('amount_type' => 5));
            
            //组合支付
           	$result_combined = $om->orderCombined($order_id, $payway_combined_currency);
           	if (!$result_combined['status']) {
           		M()->rollback();
           		$this->myApiPrint($result_combined['error']);
           	} else {
           		$result_combined = $result_combined['status'];
           	}
           
            if ($res1 === false || $res2 === false || $result_combined === false) {
                M()->rollback();
                $this->myApiPrint('订单状态异常，支付失败');
            }
            
            M()->commit();
            
            //重新获取订单需支付金额(因优惠折扣等计算会影响到最终支付金额)
            $affiliate_pay = M('OrderAffiliate')->where('order_id='.$order_id)->getField('affiliate_pay');
            
            //判断每日限额
            $third_all_max_amount = $this->CFG['third_all_max_amount'];
            if ($affiliate_pay >= $third_all_max_amount) {
            	$this->myApiPrint('单笔金额已超过今日最大限额');
            } else {
            	$user_third_all_amount_by_today = getUserThirdAmountByToday($user_id, '2');
            	if (($user_third_all_amount_by_today + $affiliate_pay) > $third_all_max_amount) {
            		$this->myApiPrint('累计支付充值金额已超过今日最大限额');
            	}
            }
            
            Vendor("ZhongWY.ZhongWY#Api");
            $data = ZhongWYApi::pay($order['order_number'], 'weixin_wap', $affiliate_pay, U('ZhongWY/notify', ['payway' => 'wechat'], '', true));
            $this->myApiPrint('返回成功', 400, $data);
            exit;
        } elseif ($payway == 5) { // 第三方-支付宝
            if ($this->CFG['zhongwy_switch'] !== '开启') {
                $this->myApiPrint('请使用其他支付方式');
            }
            $res1 = true;
            $pinfo = M('orders_pay_info')->where(array('uid' => $user_id, 'order_number' => $order['order_number']))->find();
            if (!$pinfo) {
                $res1 = M('orders_pay_info')->add(array('uid' => $user_id, 'order_number' => $order['order_number']));
            }
            $res2 = M('orders')->where('id=' . $order_id)->save(array('amount_type' => 4));
            
            //组合支付
            $result_combined = $om->orderCombined($order_id, $payway_combined_currency);
            if (!$result_combined['status']) {
            	M()->rollback();
            	$this->myApiPrint($result_combined['error']);
            } else {
            	$result_combined = $result_combined['status'];
            }
            
            if ($res1 === false || $res2 === false || $result_combined === false) {
                M()->rollback();
                $this->myApiPrint('订单状态异常，支付失败');
            }
            
            M()->commit();
            
            //重新获取订单需支付金额(因优惠折扣等计算会影响到最终支付金额)
            $affiliate_pay = M('OrderAffiliate')->where('order_id='.$order_id)->getField('affiliate_pay');
            
            Vendor("ZhongWY.ZhongWY#Api");
            $data = ZhongWYApi::pay($order['order_number'], 'alipay_wap_new_pay', $affiliate_pay, U('ZhongWY/notify', ['payway' => 'alipay'], '', true));
            $this->myApiPrint('返回成功', 400, $data);
            exit;
        } elseif ($payway == 6) {//公让宝
        	//获取公让宝应支付金额并记入订单affiliate_pay字段
        	//$order['affiliate_pay'] = $GoldcoinPricesModel->getGrbByRmb($order['amount']);
        	//$result = M('order_affiliate')->where('order_id='.$order_id)->save(['affiliate_pay' => $order['affiliate_pay']]);
        	
        	//验证公让宝余额
        	if (!$om->compareBalance($user_id, Currency::GoldCoin, $order['affiliate_goldcoin'])) {
        		M()->rollback();
        		$this->myApiPrint('丰谷宝余额不足');
        	}
        	
        	//判断现金积分余额
        	if (!$om->compareBalance($user_id, Currency::Cash, $order['affiliate_pay'])) {
        		M()->rollback();
        		$this->myApiPrint('现金积分余额不足');
        	}
        	
        	$user = M('Member')->find($user_id);
        	
        	$up['order_status'] = 1;
            $up['pay_time'] = time();
            $up['amount_type'] = 6;
            $res1 = M('orders')->where('id=' . $order_id)->save($up);
            $res2 = $om->shoppingpay($user, $order, 'goldcoin');
            
            if ($res1 !== false && $res2 !== false && $result !== false) {
                M()->commit();
                $this->myApiPrint('支付成功', 400);
            } else {
                M()->rollback();
                $this->myApiPrint('订单状态异常，支付失败。');
            }
        } elseif ($payway == 7) { //银行卡支付
        	//验证是否已绑定银行卡
        	$bank_bind = M('BankBind')->where("user_id=".$user_id)->find();
        	if (!$bank_bind) {
        		M()->rollback();
        		$this->myApiPrint('银行卡未绑定');
        	}
        	
        	if ($this->CFG['zhongwy_switch'] !== '开启') {
        		$this->myApiPrint('请使用其他支付方式');
        	}
        	
        	$res1 = true;
        	
        	$pinfo = M('orders_pay_info')->where(array('uid' => $user_id, 'order_number' => $order['order_number']))->find();
        	if (!$pinfo) {
        		$res1 = M('orders_pay_info')->add(array('uid' => $user_id, 'order_number' => $order['order_number']));
        	}
        	
        	$res2 = M('orders')->where('id=' . $order_id)->save(array('amount_type' => 7));
        	
        	if ($res1 === false || $res2 === false) {
        		M()->rollback();
        		$this->myApiPrint('订单状态异常，支付失败');
        	}
        	
        	M()->commit();
        	
        	Vendor("ZhongWY.ZhongWY#Api");
        	
        	//重新获取订单需支付金额(因优惠折扣等计算会影响到最终支付金额)
        	$affiliate_pay = M('OrderAffiliate')->where('order_id='.$order_id)->getField('affiliate_pay');
        	
        	$data = ZhongWYApi::pay($order['order_number'], 'bank_card', $affiliate_pay, U('ZhongWY/notify', ['payway' => 'bank'], '', true));
        	$this->myApiPrint('返回成功', 400, $data);
        } elseif ($payway == 8) { //提货券
        	//验证余额
        	if (!$om->compareBalance($user_id, Currency::ColorCoin, $order['affiliate_pay'])) {
        		M()->rollback();
        		$this->myApiPrint('余额不足');
        	}
        	 
        	$user = M('Member')->find($user_id);
        	 
        	$up['order_status'] = 1;
        	$up['pay_time'] = time();
        	$up['amount_type'] = 8;
        	$res1 = M('orders')->where('id=' . $order_id)->save($up);
        	$res2 = $om->shoppingpay($user, $order, 'colorcoin');
        	if ($res1 !== false && $res2 !== false) {
        		M()->commit();
        		$this->myApiPrint('支付成功', 400);
        	} else {
        		M()->rollback();
        		$this->myApiPrint('订单状态异常，支付失败。');
        	}
        } elseif ($payway == 9) { //兑换券
        	//验证余额
        	if (!$om->compareBalance($user_id, Currency::Enroll, $order['affiliate_pay'])) {
        		M()->rollback();
        		$this->myApiPrint('余额不足');
        	}
        	
        	$user = M('Member')->find($user_id);
        	
        	$up['order_status'] = 1;
        	$up['pay_time'] = time();
        	$up['amount_type'] = 9;
        	$res1 = M('orders')->where('id=' . $order_id)->save($up);
        	$res2 = $om->shoppingpay($user, $order, 'enroll'); //此处依然使用cash,在shoppingpay中再使用amount_type类型来做对应类型支付
        	if ($res1 !== false && $res2 !== false) {
        		M()->commit();
        		$this->myApiPrint('支付成功', 400);
        	} else {
        		M()->rollback();
        		$this->myApiPrint('订单状态异常，支付失败。');
        	}
        } elseif ($payway == 10) { //报单币支付
        	$user = M('member')->find($user_id);
        	$up['order_status'] = 1;
        	$up['pay_time'] = time();
        	$up['amount_type'] = 10;
        	$res1 = M('orders')->where('id=' . $order_id)->save($up);
        	
        	//组合支付
        	$result_combined = $om->orderCombined($order_id, $payway_combined_currency);
        	if (!$result_combined['status']) {
        		M()->rollback();
        		$this->myApiPrint($result_combined['error']);
        	} else {
        		$result_combined = $result_combined['status'];
        	}
        	
        	//重新获取订单需支付金额(因优惠折扣等计算会影响到最终支付金额)
        	$affiliate_pay = M('OrderAffiliate')->where('order_id='.$order_id)->getField('affiliate_pay');
        	
        	//验证余额
        	if (!$om->compareBalance($user_id, Currency::Supply, $affiliate_pay)) {
        		M()->rollback();
        		$this->myApiPrint('余额不足');
        	}
        	
        	$res2 = $om->shoppingpay($user, $order, 'supply');
        	
        	if ($res1 !== false && $res2 !== false && $result_combined !== false) {
        		M()->commit();
        		$this->myApiPrint('支付成功', 400);
        	} else {
        		M()->rollback();
        		$this->myApiPrint('订单状态异常，支付失败。');
        	}
        } else { //现金积分支付
            //$this->myApiPrint('请使用微信支付');
            
            $user = M('member')->find($user_id);
            $up['order_status'] = 1;
            $up['pay_time'] = time();
            $up['amount_type'] = 1;
            $res1 = M('orders')->where('id=' . $order_id)->save($up);
            
            //组合支付
            $result_combined = $om->orderCombined($order_id, $payway_combined_currency);
            if (!$result_combined['status']) {
            	M()->rollback();
            	$this->myApiPrint($result_combined['error']);
            } else {
            	$result_combined = $result_combined['status'];
            }
            
            //重新获取订单需支付金额(因优惠折扣等计算会影响到最终支付金额)
            $affiliate_pay = M('OrderAffiliate')->where('order_id='.$order_id)->getField('affiliate_pay');
            
            //验证余额
            if (!$om->compareBalance($user_id, Currency::Cash, $affiliate_pay)) {
            	M()->rollback();
            	$this->myApiPrint('余额不足');
            }
            
            $res2 = $om->shoppingpay($user, $order, 'cash');
            
            if ($res1 !== false && $res2 !== false && $result_combined !== false) {
                M()->commit();
                $this->myApiPrint('支付成功', 400);
            } else {
                M()->rollback();
                $this->myApiPrint('订单状态异常，支付失败。');
            }
        }
    }

    /**
     * 结算订单时显示订单PV值及弹窗提示
     * 
     * @param int $order_id 订单ID
     */
    public function showPVbeforePay() {
    	$OrderModel = new OrderModel();
    	
    	$order_id = $this->post['order_id'];
    	
    	if (!validateExtend($order_id, 'NUMBER')) {
    		$this->myApiPrint('参数格式有误');
    	}
    	
    	$data = $OrderModel->getOrderPV($order_id);
    	
    	$this->myApiPrint('查询成功', 400, $data);
    }

    /**
     * 支付前获取信息 [收银台]
     */
    public function prepay()
    {
    	$OrderModel = new OrderModel();
    	$EnjoyModel = new EnjoyModel();
    	
        $order_id = intval(I('post.order_id'));
        $user = getUserInBashu();
        $user_id = $user['id'];

        $where['o.id'] = $order_id;
        $where['o.uid'] = $user_id;
        $order = M('orders o')->field('o.*, a.affiliate_goldcoin, a.affiliate_pay, a.affiliate_freight')
            ->join('left join zc_order_affiliate a on a.order_id = o.id')
            ->where($where)
            ->find();
        if (empty($order)) {
            $this->myApiPrint('订单不存在');
        }
        if ($order['order_status'] != 0) {
            $this->myApiPrint('订单状态异常');
        }

        //订单折扣
        $discount = $OrderModel->getUserDiscount($user_id, $order['producttype'])['discount'];
        if ($discount > 0) {
        	$amount_discount = $order['amount'] * $discount / 10;
        } else {
        	$amount_discount = $order['amount'];
        }
        
        //订单公让宝抵扣比例
        $goldcoin_percent = $OrderModel->getUserDiscount($user_id, $order['producttype'])['goldcoin_percent'];

        //获取账户余额
        $am = new AccountModel();
        $balance = $am->getAllBalance($user_id, 0, true);
        
        //优惠折扣
        $balance['discount'] = $discount.'折';
        $balance['pay_amount'] = sprintf('%.2f', $amount_discount).'元';
        $balance['amount'] = sprintf('%.2f', $amount_discount).'元';
        
        //订单运费
        $balance['freight'] = $order['affiliate_freight']==0 ? '免运费' : sprintf('%.2f', $order['affiliate_freight']).'元';
        
        //可组合支付币种类型
        if ($order['producttype'] == C('GRB_EXCHANGE_BLOCK_ID')) { //公让宝兑换专区特殊处理
        	$balance['combined'] = $OrderModel->orderCombinedCurrencyByGrbExchange($order_id);
        	$balance['combined']['goldcoin']['is_must'] = true;
        	
        	if ($balance['combined']['goldcoin']['balance'] < $order['affiliate_goldcoin']) {
        		$this->myApiPrint('丰谷宝余额不足');
        	}
        	
        	$balance['amount'] = sprintf('%.2f份丰谷宝', $amount_discount). '+'. sprintf('%.2f元', $order['affiliate_freight']);
        	$balance['pay_amount'] = sprintf('%.2f元', $order['affiliate_freight']);
        } else {
        	$balance['combined'] = $OrderModel->orderCombinedCurrency($order_id);
        	
        	//需强制按板块设置比例使用公让宝抵扣(除特价区外)
        	if ($order['producttype'] != C('TEJIA_BLOCK_ID')) {
        		$balance['combined']['goldcoin']['is_must'] = true;
        	
        		if ($balance['combined']['goldcoin']['percent'] > 0) {
        			if ($balance['combined']['goldcoin']['balance'] < $balance['combined']['goldcoin']['percent_amount_original']) {
        				$this->myApiPrint('丰谷宝余额不足，需'.$balance['combined']['goldcoin']['percent_amount_original'].'份丰谷宝');
        			}
        		}
        	}
        	
        	if (isset($balance['combined']['goldcoin']['percent'])) {
        		$percent = $balance['combined']['goldcoin']['percent']=='0.00' ? '0.00' : $balance['combined']['goldcoin']['percent'];
        		$balance['combined']['goldcoin']['title'] .= "({$percent}%)";
        	}
        }
        
        //大礼包区强制必须使用公让宝组合支付
        $balance['gift_package_block_pay_notice'] = '';
        if ($order['producttype'] == C('GIFT_PACKAGE_BLOCK_ID')) {
        	$balance['combined']['goldcoin']['is_must'] = true;
        	$balance['gift_package_block_pay_notice'] = '大礼包区兑换方式为50%现金积分+50%丰谷宝通证'; //大礼包区支付方式提示说明文字
        	
        	//公让宝余额不足时不能支付
        	if ($balance['combined']['goldcoin']['balance'] < $balance['combined']['goldcoin']['percent_amount_original']) {
        		$this->myApiPrint('丰谷宝余额不足');
        	}
        }
        
        //微信支付和充值每日限额提示
        $balance['third_all_max_amount_explain'] = '微信每日支付充值最大限额为'.$this->CFG['third_all_max_amount'].'元';
        
        //折扣优惠前金额  + 优惠金额
        $balance['pay_amount_old'] = sprintf('%.2f', $order['amount']).'元';
        if ($discount > 0) {
        	$balance['discount_amount'] = sprintf('%.2f', $balance['pay_amount'] - $order['amount']).'元';
        } else {
        	$balance['discount_amount'] = sprintf('%.2f', 0);
        } 
        
        //获取订单PV
        $pv_data = $OrderModel->getOrderPV($order_id);
        $balance = array_merge($balance, $pv_data);
        
        //赠送澳洲SKN股数 [已取消赠送，故此处统一为0]
//         $balance['enjoy_give'] = $EnjoyModel->consumeGive($order_id, false);
        $balance['enjoy_give'] = 0;
        
        //支付密码弹出框文字数组
        $balance['pay_password_show'] = [];
        if ($balance['combined']['goldcoin']['percent_amount'] > 0) {
        	$balance['pay_password_show'][] = ['label' => '丰谷宝抵扣：', 'value' => $balance['combined']['goldcoin']['percent_amount'].'份'];
        }
        if ($balance['combined']['goldcoin']['pay_amount'] > 0) {
        	$balance['pay_password_show'][] = ['label' => '现金积分支付：', 'value' => $balance['combined']['goldcoin']['pay_amount'].'元'];
        }
        
        $this->myApiPrint('获取成功', 400, $balance);
    }

    /**
     * 清空购物车
     */
    private function clearAll($user_id, $cartIds)
    {
        $where['user_id'] = $user_id;
        $ids = explode(',', $cartIds);
        $in = '';
        foreach ($ids as $k => $v) {
            if (intval($v) > 0) {
                $in .= $v . ',';
            }
        }
        if ($in != '') {
            $in = substr($in, 0, strlen($in) - 1);
            $where['cart_id'] = array('exp', 'IN (' . $in . ')');
        }

        return M('shopping_cart')->where($where)->delete();
    }


    /**
     * 立即购买页面
     */
    public function buyindex()
    {
        $user = getUserInBashu();
        $user_id = $user['id'];
        $product_id = intval(I('post.product_id'));  //商品id
        $price_id = intval(I('post.price_id'));      //商品price_id
        $cart_quantity = getInt(I('post.cart_quantity'), 1);
        $cart_attr = getString(I('post.cart_attr'), '常规');

        $data = $this->getItem($user, $product_id, $price_id, $cart_quantity, $cart_attr);

        //加载收货地址信息
        $data['hasaddr'] = 0;
        $addr = M('address')->where('uid=' . $user_id)->order('is_default desc')->find();
        if ($addr) {
            $data['addr'] = $addr;
            $data['hasaddr'] = 1;
        }
        
        //提货券和兑换券余额
        $AccountModel = new AccountModel();
        $balance['colorcoin'] = $AccountModel->getBalance($user_id, Currency::ColorCoin);
        $balance['colorcoin'] = sprintf('%.4f', $balance['colorcoin']);
        $balance['enroll'] = $AccountModel->getBalance($user_id, Currency::Enroll);
        $balance['enroll'] = sprintf('%.4f', $balance['enroll']);
        $data['balance'] = $balance;

        //提示
        $data['notice'] = '丰谷宝余额不足系统自动将差额转换为现金积分去支付';

        $this->myApiPrint('ok', 400, $data);

    }


    private function getProductOne($product_id, $price_id)
    {
    	$current_lang = getCurrentLang(true);
    	
        $field_name = 'p.`name'.$current_lang.'` as name';
		$field_block_name = 'b.block_name'.$current_lang.' as block_name';
		$field = 'p.id, p.storeid, '.$field_name.', p.img, p.price, p.totalnum, p.exchangenum
				, '.$field_block_name.', b.block_freight, b.block_freight_collect
				, a.block_id, ifnull(a.affiliate_freight, \'\') product_freight, a.affiliate_freight_collect
				, m.*';
		$product = M('product p')->field($field)
			->join('left join zc_product_affiliate a on a.product_id = p.id')
            ->join('left join zc_block b on b.block_id = a.block_id')
            ->join('left join zc_product_price m on m.product_id = p.id and m.price_id=' . $price_id)
            ->where(['p.id' => $product_id, 'p.status' => 0, 'p.manage_status' => 1])
            ->select();

        return $product;
    }

    private function getProductAny($carids)
    {
    	$current_lang = getCurrentLang(true);
    	
        $field_name = 'p.`name'.$current_lang.'` as name';
		$field_block_name = 'b.block_name'.$current_lang.' as block_name';
		$field = 'c.cart_quantity, c.cart_attr
				, p.id, p.storeid, '.$field_name.', p.img, p.price, p.totalnum, p.exchangenum
				, '.$field_block_name.', b.block_freight, b.block_freight_collect
				, a.block_id, ifnull(a.affiliate_freight, \'\') product_freight, a.affiliate_freight_collect
				, m.*';
		$product = M('shopping_cart c')->field($field)
			->join('left join zc_product p on c.product_id = p.id')
            ->join('left join zc_product_affiliate a on a.product_id = p.id')
            ->join('left join zc_block b on b.block_id = a.block_id')
            ->join('left join zc_product_price m on m.price_id=c.price_id and m.product_id = p.id')
            ->where(['c.cart_id' => ['in', $carids], 'p.status' => 0, 'p.manage_status' => 1])
            ->select();

        return $product;
    }

    /**
     * 立即购买的商品信息-返回订单确认页面
     * @param $user
     * @param $product_id
     * @param $price_id
     * @param $cart_quantity 购买数量
     * @param $cart_attr
     * @param string $carids 购物车ID
     * @param boolean $use_discount 是否启用折扣优惠进行相关数据的处理(默认false)
     *
     * @return mixed
     */
    private function getItem($user, $product_id, $price_id, $cart_quantity, $cart_attr, $carids = '', $use_discount = false)
    {
    	$OrderModel = new OrderModel();
    	$MiningModel = new MiningModel();
    	
        if ($carids == '') {
            $data['items'] = $this->getProductOne($product_id, $price_id);
        } else {
            $data['items'] = $this->getProductAny($carids);
        }
        //2.获取购物车商品信息

        if (!$data['items']) {
            $this->myApiPrint('空空的，什么都没有！');
        }
        
        //判断商品库存是否足够
        foreach ($data['items'] as $k => $v) {
            if ($v['exchangenum'] + $cart_quantity > $v['totalnum']) {
                $this->myApiPrint('商品库存不足');
            }
            if (!$v['price_id']) {
                $this->myApiPrint('商品价格不存在');
            }
            
            //当为谷聚金板块商品时判断单次最低提货数量
            if ($v['block_id'] == C('GJJ_BLOCK_ID')) {
            	$gjj_exchange_min = $this->CFG['gjj_exchange_min'];
            	if ($cart_quantity < $gjj_exchange_min) {
            		$this->myApiPrint('谷聚金商品单次最低提货数量为'.$gjj_exchange_min);
            	}
            }
            
            //不管是否启用优惠use_discount,此处都记录优惠前的price_cash,用于计算优惠前的总PV值
            $data['items'][$k]['price_cash_old'] = $v['price_cash'];
            $data['items'][$k]['product_freight_old'] = $v['product_freight'];
            
            //折扣优惠分析处理
            $discount = $OrderModel->getUserDiscount($user['id'], $v['block_id'])['discount'];
            if ($discount > 0) {
            	$data['items'][$k]['discount'] = $discount;
            	 
            	if ($use_discount) {
            		$data['items'][$k]['price_cash'] = $v['price_cash'] * $discount / 10;
            		$data['items'][$k]['product_freight'] = $v['product_freight'] * $discount / 10;
            	}
            }

            $v['product_freight'] = $v['product_freight'] ?: 0;
            $data['items'][$k]['yunfei_old'] = '免运费';
            if ($v['product_freight'] > 0) {
                $data['items'][$k]['yunfei'] = sprintf('%.2f元', $v['product_freight'] * $v['cart_quantity']);
                $data['items'][$k]['yunfei_old'] = sprintf('%.2f元', $v['product_freight_old'] * $v['cart_quantity']);
            } else {
                $data['items'][$k]['yunfei'] = '免运费';
            }

            $data['items'][$k]['img'] = Image::url($data['items'][$k]['img'], 'oss');
            $data['items'][$k]['cart_quantity'] = $v['cart_quantity'] ? $v['cart_quantity'] : $cart_quantity;
            $data['items'][$k]['cart_attr'] = $v['cart_attr'] ? $v['cart_attr'] : $cart_attr;
            
            //公让宝抵扣比例
            $goldcoin_percent = $OrderModel->getUserDiscount($user['id'], $v['block_id'])['goldcoin_percent'];
            $data['items'][$k]['goldcoin_percent'] = $goldcoin_percent;
        }
        
        $data = $this->getGoumaiJiage($data, $user);
        
        //判断该商品所属板块是否只允许会员购买
        $block_info = M('Block')->where('block_id='.$data['block_id'])->field('block_only_member')->find();
        $user_level = M('Member')->where('id='.$user['id'])->getField('level');
        if ($block_info['block_only_member'] && $user_level != '2') {
        	$this->myApiPrint('请先购买大礼包区商品，激活账号');
        }
        
        //获取购物车总PV
        $data['pv_data'] = [
	        'pv' => $data['total_pv'],
	        'msg' => $OrderModel->getMsgByPV($data['total_pv'])
        ];
        
        //获取购物车优惠前总PV
        $data['pv_data_old'] = [
        	'pv' => $data['total_pv_old'],
        	'msg' => $OrderModel->getMsgByPV($data['total_pv_old'])
        ];
        
        //判断该用户有效农场数是否已超过或加此订单后累计会超过50个(如果超过50个则不能报单)
        $portion_info = $MiningModel->getPortionNumber($user['id'], true);
        $portion_enabled = $portion_info['enabled']; //当前用户农场个数
        $portion_order = floor( $data['total_pv'] / ( $this->CFG['performance_portion_base'] / 2 ) ) / 2;
        if ($data['block_id'] == C('GIFT_PACKAGE_BLOCK_ID')) { //仅针对大礼包区进行限制
	        if ($portion_enabled + $portion_order > C('BAODAN_FARM_ENABLED_NUMBER')) {
	        	if ($portion_enabled >= C('BAODAN_FARM_ENABLED_NUMBER')) {
	        		$this->myApiPrint('你的农场数已经达到上限');
	        	} else {
	        		$baodan_amount_surplus = ( C('BAODAN_FARM_ENABLED_NUMBER') - $portion_enabled ) * $this->CFG['performance_portion_base'];
	        		$this->myApiPrint('你最多还能消费'.$baodan_amount_surplus.'元');
	        	}
	        }
        }
        
        //判断该订单符合未满XX元自动增加XX元运费规则
        if ($data['block_freight_increase']) {
        	$data['pv_data']['msg'] = $data['block_freight_increase']. PHP_EOL. $data['pv_data']['msg'];
        }
        
        //支付总额对应公让宝支付金额
        $GoldcoinPricesModel = new GoldcoinPricesModel();
        $data['pay_grb'] = $GoldcoinPricesModel->getGrbByRmb($data['pay_amount']);
        $data['pay_grb'] = sprintf('%.4f', $data['pay_grb']);

        return $data;
    }

    private function getGoumaiJiage($data, $user)
    {
        //合计金额
        $data['total_fee'] = 0;               //订单总金额
        $data['total_products_amount'] = 0;   //商品总金额
        $data['total_cash'] = 0;
        $data['total_freight'] = 0;            //运费总金额。同一个商品 数量*运费
        $data['total_freight_old'] = 0; //优惠前
        $data['storeid'] = 0;                  //店铺id
        $data['block_id'] = 0; //板块ID
        $data['total_pv'] = 0; //总PV
        $data['total_pv_old'] = 0; //优惠前总PV
        $data['discount'] = 0; //订单折扣
        $data['goldcoin_percent'] = 0; //公让宝抵扣比例
        $data['block_freight_increase'] = false; //是否符合订单不满XX元自动增加YY元运费
        $data['enjoy_give'] = 0; //赠送澳洲SKN股数个数
        
        $storecount = 0; //用于判断是否多商家
        $block_count = 0; //用于判断是否多板块
        
        foreach ($data['items'] as $k => $item) {
            //验证-不能多商家一起下单
            if ($item['storeid'] != $data['storeid']) {
                $data['storeid'] = $item['storeid'];
                $storecount++;
            }
            
            //验证 - 不能多板块一起下单
            if ($item['block_id'] != $data['block_id']) {
            	$data['block_id'] = $item['block_id'];
            	$block_count++;
            }
            
            //订单优惠折扣
            if ($k == 0) {
           		$data['discount'] = $item['discount'];
            }
            
            //订单公让宝抵扣比例
            if ($k == 0) {
            	$data['goldcoin_percent'] = $item['goldcoin_percent'];
            }
            
            $all_cash = $item['price_cash'] * $item['cart_quantity'];
            $all_cash_old = $item['price_cash_old'] * $item['cart_quantity'];
            
            //提货券和兑换券特殊处理
            if ($item['block_id'] == C('GJJ_BLOCK_ID')) {
            	$all_cash = $item['cart_quantity'];
            }
            
            $data['total_cash'] += $all_cash;

            $data['total_freight'] += $item['product_freight'] * 1 * $item['cart_quantity'];
            $data['total_freight_old'] += $item['product_freight_old'] * 1 * $item['cart_quantity'];
            
            $data['total_products_amount'] += $all_cash;

            $data['items'][$k]['price'] = sprintf('￥%.2f元', $data['items'][$k]['price']);
            $data['items'][$k]['price_old'] = sprintf('￥%.2f元', $item['price_cash_old']);

            //获取商品PV
            $pv = sprintf('%.0f', $item['price_cash'] * $item['performance_bai_cash'] * 0.01);
            $data['items'][$k]['pv'] = $pv;
            $data['items'][$k]['pv_str'] = sprintf('%.0f', $item['performance_bai_cash']). '%业绩';
            $data['total_pv'] += $pv * $item['cart_quantity'];
			
			//获取商品优惠前总PV
			$pv_old = sprintf('%.0f', $item['price_cash_old'] * $item['performance_bai_cash'] * 0.01);
			$data['items'][$k]['pv_old'] = $pv_old;
			$data['items'][$k]['pv_old_str'] = sprintf('%.0f', $item['performance_bai_cash']). '%业绩';
			$data['total_pv_old'] += $pv_old * $item['cart_quantity'];
			
			//公让宝兑换专区特殊处理(最终支付金额为运费金额,商品金额即为公让宝支付金额)
			if ($item['block_id'] == C('GRB_EXCHANGE_BLOCK_ID')) {
				$data['total_products_amount'] = 0;
				$data['items'][$k]['price'] = sprintf('%.2f份丰谷宝', $data['items'][$k]['price_cash']);
				$data['items'][$k]['price_old'] = sprintf('%.2f份丰谷宝', $item['price_cash_old']);
				$data['items'][$k]['pv'] = "";
				$data['items'][$k]['pv_old'] = "";
			}
        }
        
        $data['total_pv'] = sprintf('%.0f', $data['total_pv']);
        $data['total_pv_old'] = sprintf('%.0f', $data['total_pv_old']);
        
        if ($storecount > 1) {
            $this->myApiPrint('不能多商家一起下单');
        }
        if ($block_count > 1) {
        	$this->myApiPrint('不能多板块一起下单');
        }
        
        //订单累计总金额
        $data['total_fee'] = $data['total_products_amount'] + $data['total_freight'];
        
        //实际需要支付现金积分总金额
        $data['pay_amount'] = $data['total_products_amount'];  //最后加运费
        $data['pay_amount'] = $data['pay_amount'] + $data['total_freight'];
        
        //板块相关数据
        $block_info = M('Block')->where('block_id='.$data['block_id'])->field('block_freight_order_amount,block_freight_increase_amount,block_enjoy_order_amount,block_enjoy_give_amount')->find();
        
        //订单不满XX元自动增加YY元运费
        if ($data['total_fee'] < $block_info['block_freight_order_amount']) {
        	$data['total_freight'] += $block_info['block_freight_increase_amount'];
        	$data['pay_amount']  += $block_info['block_freight_increase_amount'];
        	$data['total_fee']  += $block_info['block_freight_increase_amount'];
        	
        	$data['total_freight_old'] += $block_info['block_freight_increase_amount'];
        	
        	$block_info['block_freight_order_amount'] = sprintf('%.2f', $block_info['block_freight_order_amount']);
        	$block_info['block_freight_increase_amount'] = sprintf('%.2f', $block_info['block_freight_increase_amount']);
        	$data['block_freight_increase'] = "订单不满{$block_info['block_freight_order_amount']}元，自动增加运费{$block_info['block_freight_increase_amount']}元！";
        }
        
        //赠送澳洲SKN股数 [已取消赠送，故此处注释掉]
//         $data['enjoy_give'] = floor($data['total_pv'] / $block_info['block_enjoy_order_amount']) * $block_info['block_enjoy_give_amount'];
        
        //公让宝兑换专区特殊处理(最终支付金额为运费金额,商品金额即为公让宝支付金额)
        if ($data['block_id'] == C('GRB_EXCHANGE_BLOCK_ID')) {
        	$data['pay_amount'] = sprintf('%.2f', $data['total_cash']). "份丰谷宝+". sprintf('%.2f元', $data['pay_amount']);
        	$data['total_fee'] = $data['pay_amount']; 
        } else {
        	$data['pay_amount'] = sprintf('%.2f', $data['pay_amount']);
        	$data['total_fee'] = sprintf('%.2f', $data['total_fee']);
        }

        //运费
        $data['yunfei'] = sprintf('%.2f', $data['total_freight']);
        if ($data['total_freight'] == 0) {
        	$data['yunfei'] = '免运费';
        }
        $data['yunfei_old'] = sprintf('%.2f', $data['total_freight_old']);
        if ($data['total_freight_old'] == 0) {
        	$data['yunfei_old'] = '免运费';
        }

        $data['total_fee'] = $data['total_fee'];
        $data['pay_amount'] = $data['pay_amount'];
        $data['total_products_amount'] = sprintf('%.2f', $data['total_products_amount']);
        $data['total_cash'] = sprintf('%.2f', $data['total_cash']);
        $data['total_freight'] = sprintf('%.2f', $data['total_freight']);
        $data['total_freight_old'] = sprintf('%.2f', $data['total_freight_old']);
        
        //返回余额信息
        $am = new AccountModel();
        $balance = $am->getAllBalance($user['id'], 0, true);
        $data['balance'] = $balance;

        return $data;
    }


    /**
     * 立即购买，提交订单
     */
    public function checkout2()
    {
        //23:30之后不能下单
        $timestr = date('Y-m-d', time()) . ' 23:30';
        if (time() > strtotime($timestr)) {
            $this->myApiPrint('每日23:30之后不能下单！');
        }
        $addr_id = intval(I('post.addr_id')); //收货地址id
        $product_id = intval(I('post.product_id'));
        $price_id = intval(I('post.price_id'));
        $cart_quantity = getInt(I('post.cart_quantity'), 1);
        $cart_attr = getString(I('post.cart_attr'), '常规');
  
        $payway = intval(I('post.payway'));   // 1:现金积分, 2:微信, 3:支付宝, 4:第三方-微信, 5:第三方-支付宝, 6:公让宝, 7:银行卡, 8:提货券, 9:兑换券, 10:报单币
        $payway = empty($payway) ? 1 : $payway;
        
        //非必须
        $province = $this->post['province'];
        $city = $this->post['city'];
        $country = $this->post['country'];
        $remark = $this->post['remark']; //备注说明

        $user = getUserInBashu();
        $user_id = $user['id'];
        
        $om = new OrderModel();
        $address = $om->getCheckoutAddr($user_id, $addr_id);
        if (empty($address)) {
            $this->myApiPrint('收货地址不存在');
        }

        //购物车信息
        $data = $this->getItem($user, $product_id, $price_id, $cart_quantity, $cart_attr);
        if ($data['pay_amount'] < 0) {
            $this->myApiPrint('付款金额计算超时');
        }
        
        //把省市区加入address
        $address['province'] = $province;
        $address['city'] = $city;
        $address['country'] = $country;
        
        $data['remark'] = $remark;
        
        M()->startTrans();
        
        $data['payway'] = $payway;
        
        //微信+现金积分 只下单，不支付
        $order = $om->build($data, $user, $address);
        
        $am = new AccountModel();
        $res2 = $am->frozenRefund($user_id, $order['id'], $data, '商城购物冻结资金');

        $res11 = true;
        $res12 = true;
        $msg = '下单成功，请30分钟完成付款！';
        
        //如果不需要支付现金积分，直接更新订单状态
//         if ($data['pay_amount'] == 0) {
//             $up['order_status'] = 1;
//             $up['pay_time'] = time();
//             $up['amount_type'] = 1; //现金积分支付类型（1现金积分、4支付宝、5微信）
//             $res11 = M('orders')->where('id=' . $order['id'])->save($up);
//             $res12 = $om->shoppingpay($user, $order, 'cash');
//             $msg = '恭喜你购买成功！';
//         }
        
        //检测未实名认证则将收货地址信息记入实名认证信息中
        $result4 = true;
        $map_certification = [
	        'user_id' => ['eq', $user_id],
	        'certification_status' => ['neq', 1]
        ];
        $certification_exists = M('certification')->where($map_certification)->find();
        if (!$certification_exists) {
        	$data_certification = [
	        	'user_id' => $user_id,
	        	'province' => $province,
	        	'city' => $city,
	        	'country' => $country,
	        	'certification_status' => 0,
	        	'certification_addtime' => time(),
	        	'certification_uptime' => time()
        	];
        	if (!empty($province) && !empty($city) && !empty($country)) {
        		$result4 = M('Certification')->add($data_certification);
        	}
        }
        
        if ($order !== false && $res2 !== false && $res11 !== false && $res12 !== false && $result4 !== false) {
            M()->commit();
            $this->myApiPrint($msg, 400, ['order_id' => $order['id']]);
        } else {
            M()->rollback();
            $this->myApiPrint('下单失败', 300);
        }
    }
    
    /**
     * 封装可用支付和组合支付方式
     * 
     * @method POST
     * 
     * @param int product_id 产品ID
     * @param int cart_id 购物车ID
     * @param int order_id 订单ID
     * 
     */
    public function payMethod() {
    	$product_id = $this->post['product_id'];
    	$cart_id = $this->post['cart_id'];
    	$order_id = $this->post['order_id'];
    	
    	$pay_main = [
    		['id' => '1', 'name' => 'cash', 'title' => '现金积分', 'icon' => ''],
    		['id' => '2', 'name' => 'wxpay', 'title' => '微信', 'icon' => '',],
    		['id' => '3', 'name' => 'alipay', 'title' => '支付宝', 'icon' => ''],
    		['id' => '4', 'name' => 'wxpay_zwg', 'title' => '第三方-微信', 'icon' => ''],
    		['id' => '5', 'name' => 'alipay_zwg', 'title' => '第三方-支付宝', 'icon' => ''],
    		['id' => '6', 'name' => 'goldcoin', 'title' => '丰谷宝', 'icon' => ''],
    		['id' => '7', 'name' => 'bank', 'title' => '银行卡', 'icon' => ''],
    		['id' => '8', 'name' => 'colorcoin', 'title' => '提货券', 'icon' => ''],
    		['id' => '9', 'name' => 'enroll', 'title' => '兑换券', 'icon' => ''],
    		['id' => '10', 'name' => 'supply', 'title' => '报单币', 'icon' => ''],
    	];
    	
    	$pay_combined = [
    		['id' => '6', 'name' => 'goldcoin', 'title' => '丰谷宝', 'icon' => ''],
    	];
    	
    	$data = [
    		'pay_main' => $pay_main,
    		'pay_combined' => $pay_combined
    	];
    	
    	$this->myApiPrint('查询成功', 400, $data);
    }
    
}

?>