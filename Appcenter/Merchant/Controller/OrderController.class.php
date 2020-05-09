<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 商家订单管理
// +----------------------------------------------------------------------

namespace Merchant\Controller;
use Common\Controller\AuthController;

class OrderController extends AuthController {
	
	public function index() {
		$where = [
			'o.producttype' => ['neq', C('GJJ_BLOCK_ID')]
		];
		$this->orderBase($where);
		
		$this->display();
	}
	
	/**
	 * 谷聚金订单列表
	 */
	public function gjj() {
		$where = [
			'o.producttype' => ['eq', C('GJJ_BLOCK_ID')]
		];
		$this->orderBase($where);
		
		$this->display();
	}
	
	/**
	 * 订单列表基础数据
	 * 
	 * @param array $extend_where 筛选条件
	 */
	private function orderBase($extend_where) {
		$merchant_uid = session('admin_mid');
		// 		$exchangeway = $this->get['exchangeway']; //兑换类型
		$start_time = $this->get['start_time'];
		$end_time = $this->get['end_time'];
		$kw = $this->get['kw']; //订单号
		$page = $this->get['p']>0 ? $this->get['p'] : 1;
		$order_status = $this->get['order_status'];
		$time_type = $this->get['time_type'];
		$userid = $this->get['userid'];
		$amount_type = $this->get['amount_type'];
		
		if (empty($merchant_uid)) {
			$this->error('登录超时,请重新登录');
		}
		if (!empty($exchangeway) && !validateExtend($exchangeway, 'NUMBER')) {
			$this->error('兑换类型格式有误');
		}
		
		$where = [];
		
		//获取用户ID
		if (!empty($userid)) {
			$map_member = [
				'loginname' => array('eq', $userid),
				'username' => array('eq', $userid),
				'_logic' => 'or'
			];
// 			$member_info = M('Member')->where($map_member)->field('id')->find();
			$member_info = M('Member')->where($map_member)->getField('id',true);
			if (!$member_info) {
				$this->error('用户账户不存在');
			}
			$member_info_id = implode(',', $member_info);
			$where['o.uid'] = array('in', $member_info_id);
		}
		
		//获取商家
		$map_store['uid'] = array('eq', $merchant_uid);
		$map_store['status'] = array('eq', '0');
		$map_store['manage_status'] = array('eq', '1');
		$storeid = M('store')->where($map_store)->order('id desc')->getField('id');
		if (!$storeid) {
			$this->error('店铺不存在');
		}
		
		$where['o.storeid'] = $storeid;
		
		//兑换方式
		// 		if ($exchangeway != '') {
		// 			$where['o.exchangeway'] = array('eq', $exchangeway);
		// 		}
		
		//支付方式
		if (!empty($amount_type)) {
			$where['o.amount_type'] = array('eq', $amount_type);
		}
		
		//日期筛选
		if (!empty($start_time)) {
			$start_time = strtotime($start_time);
		}
		if (!empty($end_time)) {
			$end_time = strtotime($end_time.' 23:59:59');
		}
		if (!empty($start_time) && !empty($end_time)) {
			$time_condition = array(array('egt', $start_time),array('elt', $end_time),'and');
		} else {
			if (!empty($start_time)) {
				$time_condition = array('egt', $start_time);
			}
			if (!empty($end_time)) {
				$time_condition = array('elt', $end_time);
			}
		}
		switch ($time_type) {
			case '1':
				$where['o.time'] = $time_condition;
				break;
			case '2':
				$where['o.pay_time'] = $time_condition;
				break;
			case '3':
				$where['aff.affiliate_sendtime'] = $time_condition;
				break;
			case '4':
				$where['aff.affiliate_completetime'] = $time_condition;
				break;
		}
		
		//关键词
		if (!empty($kw)) {
			$where['o.order_number'] = array('like', '%'.$kw.'%');
		}
		
		//订单状态
		if ($order_status != '') {
			$where['o.order_status'] = array('eq', $order_status);
		}
		
		//扩展筛选条件
		if (is_array($extend_where) && count($extend_where)>0) {
			$where = array_merge($where, $extend_where);
		}
		
		//分页
		$count = M('orders o')
		->join('left join zc_order_affiliate aff on aff.order_id=o.id')
		->where($where)
		->count();
		$this->Page($count, 20, $this->get);
		
		//查询列表
		$datalist = M('orders o')
		->field('o.*, aff.affiliate_goldcoin, aff.affiliate_cash, aff.affiliate_pay,
					m.loginname, m.nickname, m.username')
							->join('join zc_store as s on s.id = o.storeid')
							->join('join zc_member as m on m.id = o.uid')
							->join('left join zc_order_affiliate aff on aff.order_id=o.id')
							->where($where)->order('o.id desc')->page($page,20)->select();
		foreach ($datalist as $k=>$v){
			//付款状态
			$order_status_cn = C('FIELD_CONFIG')['orders']['order_status'][$v['order_status']];
			if ($v['end_time'] != '0' && time() > $v['end_time'] && $v['order_status'] == '0') {
				$order_status_cn = '已过期';
			}
			$datalist[$k]['order_status_cn'] = $order_status_cn;
				
			//买家信息
			if (!empty($v['uid'])) {
				$member_info = M('Member')->where('id='.$v['uid'])->field('loginname,nickname')->find();
				$datalist[$k]['loginname'] = $member_info['loginname'];
				$datalist[$k]['nickname'] = $member_info['nickname'];
			}
				
			//获取买家申请取消订单信息
			$datalist[$k]['cancel'] = M('OrderCancel')->where("order_id={$v[id]} and cancel_status=0")->field('cancel_id')->find();
				
			//获取订单附属表信息
			$datalist[$k]['affiliate'] = M('OrderAffiliate')->where('order_id='.$v[id])->find();
				
			//支付类型中文
			$datalist[$k]['amount_type_cn'] = C('FIELD_CONFIG')['orders']['amount_type'][$v['amount_type']];
		}
		$this->assign("datalist", $datalist);
		
		//获取兑换类型列表
		$this->assign("exchangewaydata", C('FIELD_CONFIG')['orders']['exchangeway']);
	}
	
	/**
	 * 保存物流发货信息
	 */
	public function saveExpress() {
		$data = $this->post;
		
		if (!validateExtend($data['id'], 'NUMBER')) {
			$this->error('参数格式有误');
		}
		if (empty($data['express_name']) || empty($data['affiliate_trackingno'])) {
			$this->error('请填写正确的物流信息');
		}
		
		M()->startTrans();
		
		//确认该订单是否存在
		$order_info = M('Orders')->where('id='.$data['id'])->field('uid,order_number,order_status')->find();
		if (!$order_info) {
			$this->error('该订单已不存在');
		}
		
		//只有已付款订单才能发货
		if ($order_info['order_status'] != '1') {
			$this->error('只有已付款订单才能进行发货操作');
		}
		
		$data['affiliate_sendtime'] = time(); //订单附表发货时间
		$result1 = M('OrderAffiliate')->where('order_id='.$data['id'])->save($data);
		
		$data_orders = [
			'order_status' => 3,
			'delivertime' => time() //订单主表发货时间
		];
		$result2 = M('Orders')->where('id='.$data['id'])->save($data_orders);
		
		if ($result1 === false || $result2 === false) {
			$this->error('保存失败');
		}
		
		M()->commit();
		
		//推送已发货信息给买家
		$registration_id = M('login')
			->alias('log')
			->where('log.uid='.$order_info['uid'])
			->order('log.id desc')
			->getField('registration_id', true);
		$ids['all'] = $registration_id;
		$content = '您在'.C('APP_TITLE').'购买的商品已发货，请注意查收！';
		$extraparams['target'] = 'common_alert';
		$extraparams['msg'] = '您在'.C('APP_TITLE').'购买的商品[订单编号:'.$order_info['order_number'].']已发货，请注意查收！物流详情可在订单列表中查看！';
		$this->push($ids, $content, $extraparams);
		
		$this->success('保存成功', '', false, "成功保存订单[ID:{$data[id]}]的物流单号信息");
	}
	
	/**
	 * 订单取消申请审核操作
	 * 
	 * @param int $order_id 订单ID
	 * @param int $type 审核类型(1:驳回,2:通过)
	 */
	public function orderCancel() {
		$OrderCancel = M('OrderCancel');
		
		$order_id = $this->get['order_id'];
		$type = $this->get['type'];
		
		if (!validateExtend($order_id, 'NUMBER')) {
			$this->error('参数格式有误');
		}
		if ($type != 1 && $type != 2) {
			$this->error('审核类型不存在');
		}
		
		M()->startTrans();
		
		$result = true;
		$result1 = true;
		
		//验证订单状态和申请取消订单状态是否正常
		$order_info = M('Orders')->where('id='.$order_id)->field('order_status')->find();
		$order_cancel_info = M('OrderCancel')->where('order_id='.$order_id)->field('cancel_status')->find();
		if ($order_info['order_status'] == 1 && $order_cancel_info['cancel_status'] == 0) {
			switch ($type) {
				case '1':
					$title = "驳回取消申请";
					$data_cancel['cancel_status'] = 1;
					$data_cancel['cancel_uptime'] = time();
					$result = M('OrderCancel')->where('order_id='.$order_id)->save($data_cancel);
					break;
				case '2':
					$title = "确认取消申请";
					$result = M()->execute(C('ALIYUN_TDDL_MASTER')."call CancelOrder({$order_id},@error)");
					$result = empty($result) ? $result : false;
					
					//针对商城订单:恢复订单对应商品库存
					if ($order_info['exchangeway'] == '1') {
						$order_product_list = M('OrderProduct')
							->where('order_id='.$order_id)
							->field('product_id,product_quantity')
							->select();
						if ($order_product_list) {
							foreach ($order_product_list as $k=>$v) {
								$result1 = M('Product')->where('id='.$v['product_id'])->setDec('exchangenum', $v['product_quantity']);
								if ($result1 === false) {
									break;
								}
							}
						}
					}
					
					break;
			}
		} else {
			M()->rollback();
			$this->error('订单状态或申请取消订单状态异常');
		}
		
		if ($result === false || $result1 === false) {
			M()->rollback();
			$this->error('操作失败');
		} else {
			M()->commit();
			$this->success($title.'操作成功', '', false, "成功操作{$title}[订单ID:{$order_id}]");
		}
	}
	
	/**
	 * 订单修改运费
	 * 
	 * @param int $order_id 订单ID
	 * @param int $order_freight 修改后运费价格
	 */
	public function orderFreightModify() {
	    $mid = session('admin_mid');
	    $order_id = $this->post['order_id'];
	    $order_freight = $this->post['order_freight'];
	    
	    M()->startTrans();
	    
	    if (!validateExtend($order_id, 'NUMBER') || !validateExtend($order_freight, 'MONEY') || $order_freight<0) {
	        $this->error('参数格式有误');
	    }
	    
        $order_all_info = M('Orders')
           ->alias('ord')
           ->join('join __ORDER_AFFILIATE__ aff ON aff.order_id=ord.id')
           ->join('join __STORE__ sto ON sto.id=ord.storeid')
           ->where('ord.id='.$order_id)
           ->field('sto.uid,ord.order_status,ord.amount,aff.affiliate_cash,aff.affiliate_pay,aff.affiliate_freight')
           ->lock(true)
           ->find();
        if (!$order_all_info) {
            $this->error('订单信息异常');
        }
        
        //只有订单状态为0未付款时才能修改运费
        if ($order_all_info['order_status'] != '0') {
            $this->error('当前订单状态不能修改运费');
        }
        
        //判断操作者是否为商家本人
        if ($mid != $order_all_info['uid']) {
            $this->error('非该商家无权修改运费');
        }
        
        //计算新旧运费差值
        $diff = $order_all_info['affiliate_freight'] - $order_freight;
        
        $data_orders = [
            'amount' => $order_all_info['amount'] - $diff
        ];
        $res1 = M('Orders')->where('id='.$order_id)->save($data_orders);
        
        $data_affiliate = [
            'affiliate_cash' => $order_all_info['affiliate_cash'] - $diff,
            'affiliate_pay'  => $order_all_info['affiliate_pay'] - $diff,
            'affiliate_freight' => $order_freight
        ];
        $res2 = M('OrderAffiliate')->where('order_id='.$order_id)->save($data_affiliate);
        
        if ($res1 === false || $res2 === false) {
            M()->rollback();
            $this->error('修改失败');
        } else {
            M()->commit();
            $this->success('保存成功', '', false, "成功修改订单[ID:{$order_id}]的运费价格为[{$order_freight}元]");
        }
	}
	
	/**
	 * 导出订单列表
	 */
public function indexExportAction() {
		$merchant_uid = session('admin_mid');
// 		$exchangeway = $this->get['exchangeway']; //兑换类型
		$start_time = $this->get['start_time'];
		$end_time = $this->get['end_time'];
		$kw = $this->get['kw']; //订单号
		$page = $this->get['p']>0 ? $this->get['p'] : 1;
		$order_status = $this->get['order_status'];
		$time_type = $this->get['time_type'];
		$amount_type = $this->get['amount_type'];
		
		if (empty($merchant_uid)) {
			$this->error('登录超时,请重新登录');
		}
// 		if (!empty($exchangeway) && !validateExtend($exchangeway, 'NUMBER')) {
// 			$this->error('兑换类型格式有误');
// 		}
		
		$where = [];
		
		//获取商家
		$map_store['uid'] = array('eq', $merchant_uid);
		$map_store['status'] = array('eq', '0');
		$map_store['manage_status'] = array('eq', '1');
		$storeid = M('store')->where($map_store)->order('id desc')->getField('id');
		if (!$storeid) {
			$this->error('店铺不存在');
		}
		
		$where['o.storeid'] = $storeid;
		
		//兑换方式
// 		if ($exchangeway != '') {
// 			$where['o.exchangeway'] = array('eq', $exchangeway);
// 		}

		//支付类型
		if (!empty($amount_type)) {
			$where['o.amount_type'] = array('eq', $amount_type);
		}

		//日期筛选
		if (!empty($start_time)) {
			$start_time = strtotime($start_time);
		}
		if (!empty($end_time)) {
			$end_time = strtotime($end_time.' 23:59:59');
		}
		if (!empty($start_time) && !empty($end_time)) {
			$time_condition = array(array('egt', $start_time),array('elt', $end_time),'and');
		} else {
			if (!empty($start_time)) {
				$time_condition = array('egt', $start_time);
			}
			if (!empty($end_time)) {
				$time_condition = array('elt', $end_time);
			}
		}
		switch ($time_type) {
			case '1':
				$where['o.time'] = $time_condition;
				break;
			case '2':
				$where['o.pay_time'] = $time_condition;
				break;
			case '3':
				$where['aff.affiliate_sendtime'] = $time_condition;
				break;
			case '4':
				$where['aff.affiliate_completetime'] = $time_condition;
				break;
		}
		
		//关键词
		if (!empty($kw)) {
			$where['o.order_number'] = array('like', '%'.$kw.'%');
		}
		
		//订单状态
		if ($order_status != '') {
			$where['o.order_status'] = array('eq', $order_status);
		}
		
		//查询列表
		$data = M('orders o')
			->field('o.*, 
					m.loginname, m.nickname')
			->join('join zc_store as s on s.id = o.storeid')
			->join('join zc_member as m on m.id = o.uid')
			->join('left join zc_order_affiliate aff on aff.order_id=o.id')
			->where($where)->order('o.id desc');
		
		//分页
// 		if (empty($where['o.time']) && $time_type>0) {
// 			$data = $data->page($page,20);
// 		}
		
		$data = $data->select();
		
		$export_data = array();
		foreach ($data as $k=>$v) {
			//付款状态
			$order_status_cn = C('FIELD_CONFIG')['orders']['order_status'][$v['order_status']];
			if ($v['end_time'] != '0' && time() > $v['end_time'] && $v['order_status'] == '0') {
				$order_status_cn = '已过期';
			}
			
			//买家信息
			if (!empty($v['uid'])) {
				$member_info = M('Member')->where('id='.$v['uid'])->field('loginname,nickname')->find();
			}
			
			//获取订单附属表信息
			$affiliate = M('OrderAffiliate')->where('order_id='.$v[id])->find();
			
			//商品
			$product_list = '';
			$product = M('OrderProduct')
				->alias('opr')
				->join('join __PRODUCT__ pro ON pro.id=opr.product_id')
				->join('join __PRODUCT_AFFILIATE__ aff ON aff.product_id=opr.product_id')
				->join('join __BLOCK__ blo ON blo.block_id=aff.block_id')
				->field('opr.*,pro.name product_name,blo.block_name')
				->where('opr.order_id='.$v['id'])
				->order('opr.oproduct_id asc')
				->select();
			foreach ($product as $k1=>$v1) {
				//$product_list .= "名称：{$v1['product_name']}，单价：{$v1['product_price']}，数量：{$v1['product_quantity']}，属性：{$v1['product_attr']}，板块：{$v1['block_name']}".PHP_EOL;
				$product_list .= "名称：{$v1['product_name']}，数量：{$v1['product_quantity']}，属性：{$v1['product_attr']}".PHP_EOL;
			}
			
			//收货地址
			$address = $affiliate['affiliate_city']=='请选择区域信息' ? '' : $affiliate['affiliate_city'];
			$address .= ' '. $affiliate['affiliate_address'];
			
			//支付类型
			$amount_type_cn = C('FIELD_CONFIG')['orders']['amount_type'][$v['amount_type']];
			
			$vo = [
				$v['order_number'],
				$v['amount'],
				$member_info['nickname'].'['.$member_info['loginname'].']',
				empty($v['time']) ? '' : date('Y-m-d H:i:s', $v['time']),
				empty($v['pay_time']) ? '' : date('Y-m-d H:i:s', $v['pay_time']),
				empty($affiliate['affiliate_sendtime']) ? '' : date('Y-m-d H:i:s', $affiliate['affiliate_sendtime']),
				empty($affiliate['affiliate_completetime']) ? '' : date('Y-m-d H:i:s', $affiliate['affiliate_completetime']),
				$order_status_cn,
				$amount_type_cn,
				$affiliate['affiliate_freight'],
				$product_list,
				$affiliate['affiliate_consignee'],
				$affiliate['affiliate_phone'],
				$address,
				empty($affiliate['express_name']) ? '' : $affiliate['express_name'],
				empty($affiliate['affiliate_trackingno']) ? '' : $affiliate['affiliate_trackingno'],
				$v['content'],
				$v['merchant_remark']
			];
				
			$export_data[] = $vo;
		}
		
		$head_array = array('订单号', '金额', '买家', '下单时间', '付款时间', '发货时间', '完成时间', '订单状态', '支付类型', '运费', '商品', '收货人', '收货电话', '收货地址', '物流公司', '物流单号', '备注信息', '商家备注');
		$file_name .= "导出订单列表数据-".date('Y-m-d');
		$file_name = iconv("utf-8", "gbk", $file_name);
		$return = $this->xlsExport($file_name, $head_array, $export_data);
		!empty($return['error']) && $this->error($return['error']);
		
		$this->logWrite("导出订单列表数据-".date('Y-m-d'));
	}
	
	/**
	 * 保存订单商家备注
	 */
	public function saveMerchantRemark() {
		$id = $this->post['id'];
		$merchant_remark = safeString($this->post['merchant_remark'], 'trim');
		
		if (!validateExtend($id, 'NUMBER')) {
			$this->error('参数格式有误');
		}
		
		$data = [
			'merchant_remark' => $merchant_remark
		];
		
		$result = M('Orders')->where('id='.$id)->save($data);
		
		if ($result === false) {
			$this->error('保存失败');
		}
		
		$this->success('保存成功', '', false, "保存订单商家备注信息[ID:{$id}]");
	}
	
}
?>

