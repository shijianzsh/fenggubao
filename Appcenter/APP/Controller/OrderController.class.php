<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 订单相关https://m.kuaidi100.com/result.jsp?nu=887209408034489033
// +----------------------------------------------------------------------
namespace APP\Controller;

use Common\Controller\ApiController;

use V4\Model\Image;
use V4\Model\OrderModel;
use V4\Model\ProcedureModel;
use V4\Model\Currency;

/**
 * 商品相关
 * @author jay
 *
 */
class OrderController extends ApiController
{


    /**
     * 商城订单
     * 0全部；1代发货；2待收货；3已完成；4退款
     * $page 1开始
     */
    public function index()
    {
        $user_id = intval(I('post.user_id'));
        $tag = intval(I('post.tag'));
        $pn = intval(I('post.page'));

        $om = new OrderModel();
        $data = $om->getOrderList($tag, $user_id, $pn, false, C('GJJ_BLOCK_ID'));
        $this->myApiPrint('获取成功', 400, $data);
    }


    /**
     * 订单详情
     */
    public function info()
    {
    	$current_lang = getCurrentLang(true);
    	
    	$OrderModel = new OrderModel();
    	
        $id = intval(I('post.id'));
        $user_id = intval(I('post.user_id'));

        $where['o.id'] = $id;
        $where['o.uid'] = $user_id;
        $fields = 'o.id, o.order_number, o.producttype, o.time, o.order_status, ifnull(o.amount_type, 0) as amount_type, o.amount, o.discount, a.affiliate_credits, a.affiliate_supply, a.affiliate_goldcoin, a.affiliate_colorcoin, a.affiliate_cash, a.affiliate_freight, a.affiliate_pay, a.affiliate_consignee, a.affiliate_phone, a.affiliate_city, a.affiliate_address, a.affiliate_trackingno, a.affiliate_pickup , a.affiliate_goldcoin_price, ifnull(c.cancel_status, -1) cancel';
        $order = M('orders o')->field($fields)
            ->join('left join zc_order_affiliate a on a.order_id = o.id')
            ->join('left join zc_order_cancel as c on c.order_id = o.id')
            ->where($where)
            ->find();
        
        //支付方式对应中文
        $order['payway'] = C('FIELD_CONFIG')['orders']['amount_type'][$order['amount_type']];
        
        //优惠折扣
        if ($order['discount'] > 0) {
        	$order['amount_discount'] = sprintf('%.2f', $order['amount'] * $order['discount'] / 10).'元';
        	
        	$order['discount'] = $order['discount'].'折';
        	$order['discount_amount'] = sprintf('%.2f', $order['affiliate_pay'] - $order['amount']).'元';
        } else {
        	$order['amount_discount'] = $order['amount'].'元';
        	
        	$order['discount'] = '无折扣';
        	$order['discount_amount'] = '无折扣';
        }
        
        //公让宝抵扣比例
        $order['goldcoin_percent'] = $OrderModel->getUserDiscount($user_id, $order['producttype'])['goldcoin_percent'];
        $order['goldcoin_percent'] = $order['goldcoin_percent'].'%';
        $order['goldcoin_title'] = Currency::getLabel(Currency::GoldCoin).'抵扣';
        $order['buy_show'] = sprintf('%.4f', $order['affiliate_goldcoin']).'份('.$order['affiliate_goldcoin_price'].'元/份)'; 
        
        $order['paymoney'] = $order['affiliate_cash'];
        if ($order['affiliate_pay'] > 0) {
            $order['paymoney'] = $order['affiliate_pay'];
        }
        $order['paymoney'] = sprintf('%.2f', $order['paymoney']).'元';
        
        $order['kuaidi100'] = 'https://m.kuaidi100.com/result.jsp?nu=' . $order['affiliate_trackingno'];
        
        //加载商品
        $field_name = 'p.`name'.$current_lang.'` as name';
        $order['items'] = M('order_product op')
            ->field($field_name.', p.img, ifnull(pc.comment_id, 0) as comment_id, op.*')
            ->join('left join zc_product as p on p.id = op.product_id')
            ->join('left join zc_product_comment as pc on pc.product_id = op.product_id and pc.order_id = op.order_id')
            ->where('op.order_id = ' . $id)
            ->select();
        
        foreach ($order['items'] as $k => $v) {
            $order['items'][$k]['yunfei'] = sprintf('￥%.2f元', $v['product_freight'] * $v['product_quantity']);
            if ($v['product_freight'] * 1 == 0 && $v['product_freight_collect'] == 0) {
                $order['items'][$k]['yunfei'] = '免运费';
            } elseif ($v['product_freight_collect'] == 1) {
                $order['items'][$k]['yunfei'] = '到付';
            }
            $order['items'][$k]['price'] = '￥' . sprintf('%.2f', ($v['price_cash'] * $v['product_quantity'])) . '元';
            $order['items'][$k] = Image::formatItem($order['items'][$k], 'img');
            
            //公让宝兑换专区特殊处理
            if ($order['producttype'] == C('GRB_EXCHANGE_BLOCK_ID')) {
            	$order['items'][$k]['price'] = sprintf('%.2f份丰谷宝', ($v['price_cash'] * $v['product_quantity']));
            }
        }
        
        //公让宝兑换专区特殊处理
        if ($order['producttype'] == C('GRB_EXCHANGE_BLOCK_ID')) {
        	$order['amount'] = sprintf('%.2f份丰谷宝', $order['affiliate_goldcoin']). '+'. sprintf('%.2f元', $order['affiliate_pay']);
        	$order['amount_discount'] = sprintf('%.2f元', $order['affiliate_pay']);
        	
        	$order['discount'] = '0.00折';
        	$order['discount_amount'] = '无折扣';
        	
        	$order['goldcoin_percent'] = '';
        	$order['goldcoin_title'] = Currency::getLabel(Currency::GoldCoin).'支付';
        	$order['buy_show'] = sprintf('%.2f', $order['affiliate_goldcoin']).'份';
        }
        
        $order['yunfei'] = sprintf('%.2f', $order['affiliate_freight']) . '元';
        if ($order['affiliate_freight'] == 0) {
            $order['yunfei'] = '免费';
        }

        //收货地址封装
        $order['receive_address'] = $order['affiliate_city']. $order['affiliate_address'];
        
        //订单PV
        $pv_data = $OrderModel->getOrderPV($id);
        $order = array_merge($order, $pv_data);
        
        $this->myApiPrint('加载成功', 400, $order);
    }


    /**
     * 确认收货
     */
    public function confirmReceipt()
    {
        $user_id = intval(I('post.user_id'));
        $order_id = intval(I('post.order_id'));

        $where['id'] = $order_id;
        $where['uid'] = $user_id;
        $order = M('orders')->where($where)->find();
        if (!$order) {
            $this->myApiPrint('没有找到数据');
        }
        $user = M('member')->find($user_id);

        $order_affiliate = M('order_affiliate')->where('order_id=' . $order_id)->find();
        if ($order['order_status'] != 3 && $order_affiliate['affiliate_pickup'] == 0) {
            $this->myApiPrint('订单状态不支持确认收货');
        }
        if ($order['order_status'] != 1 && $order_affiliate['affiliate_pickup'] == 1) {
            $this->myApiPrint('订单状态不支持确认收货.');
        }
        M()->startTrans();
        $res1 = M('orders')->where($where)->save(array('order_status' => 4));
        $res2 = M('order_affiliate')->where('order_id=' . $order_id)->save(array('affiliate_completetime' => time()));
        //吊起存储过程-收益
        $pm = new ProcedureModel();
        $res5 = $pm->execute('Event_consume', $order['id'], '@error');

        if ($res1 !== false && $res2 !== false && $res5) {
            M()->commit();
            $this->myApiPrint('确认成功', 400);
        } else {
            M()->rollback();
            $this->myApiPrint('确认失败');
        }
    }

    /**
     * 取消订单,+退款申请一体的
     */
    public function cancel()
    {
        $user_id = intval(I('post.user_id'));
        $order_id = intval(I('post.order_id'));
        $reason = trim(I('post.reason'));
        $where['id'] = $order_id;
        $where['uid'] = $user_id;
        $order = M('orders')->where($where)->find();
        if (!$order) {
            $this->myApiPrint('没有找到数据');
        }
        if ($order['order_status'] != 1) {
            $this->myApiPrint('订单状态不支持取消');
        }
        if ($order['producttype'] == 2) {
            $this->myApiPrint('免费区订单不支持取消');
        }
        $cw['order_id'] = $order_id;
        $cancel = M('order_cancel')->where($cw)->find();
        if ($cancel) {
            if ($cancel['cancel_status'] != 1) {
                $this->myApiPrint('已申请取消，等待审核');
            }
        }
        //提交申请
        $vo['order_id'] = $order_id;
        $vo['cancel_status'] = 0;
        $vo['cancel_reason'] = $reason;
        $vo['cancel_addtime'] = time();

        $w2['order_id'] = $order_id;
        $cancel = M('order_cancel')->where($w2)->find();
        if ($cancel) {
            M('order_cancel')->where($w2)->save($vo);
        } else {
            M('order_cancel')->add($vo);
        }
        $this->myApiPrint('取消成功，请等待商家审核！', 400);
    }


    /**
     * 商家订单管理
     * 0全部；1代发货；2待收货；3已完成；4退款
     * $page 1开始
     */
    public function mchindex()
    {
        $store_id = intval(I('post.store_id'));
        $tag = intval(I('post.tag'));
        $pn = intval(I('post.page'));
        //验证数据
        $v['status'] = 0;
        $v['manage_status'] = 1;
        $v['id'] = $store_id;
        $store = M('store')->where($v)->find();
        if (empty($store)) {
            $this->myApiPrint('参数异常');
        }

        $om = new OrderModel();
        $data = $om->getMchOrderList($tag, $store_id, $pn);
        $this->myApiPrint('获取成功', 400, $data);
    }


    /**
     * 评价
     */
    public function comment()
    {
        $product_id = intval(I('post.product_id'));
        $order_id = intval(I('post.order_id'));
        $user_id = intval(I('post.user_id'));
        $comment_star = intval(I('post.comment_star'));
        $comment_content = trim(I('post.comment_content'));

        $where['o.id'] = $order_id;
        $where['o.uid'] = $user_id;
        $where['p.product_id'] = $product_id;
        //验证数据
        $order = M('orders o')->field('o.*')
            ->join('left join zc_order_product  p on p.order_id = o.id')
            ->where($where)->find();
        if (!$order) {
            $this->myApiPrint('参数异常');
        }

        if ($order['order_status'] != 4) {
            $this->myApiPrint('订单状态异常');
        }

        $pw['product_id'] = $product_id;
        $pw['order_id'] = $order_id;
        $pw['user_id'] = $user_id;
        $comment = M('product_comment')->where($pw)->find();
        if ($comment) {
            $this->myApiPrint('该商品已评论');
        }

        $pw['comment_star'] = $comment_star;
        $pw['comment_content'] = $comment_content;
        $pw['comment_addtime'] = time();
        $pw['comment_uptime'] = time();
        $pw['comment_status'] = 0;
        $res = M('product_comment')->add($pw);
        if ($res !== false) {
            $this->myApiPrint('评论成功', 400);
        } else {
            $this->myApiPrint('评论失败');
        }
    }

}

?>