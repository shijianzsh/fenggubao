<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 店铺管理
// +----------------------------------------------------------------------
namespace Shop\Controller;
use Common\Controller\AuthController;
use V4\Model\OrderModel;
use V4\Model\CustomerServiceModel;

class StoreController extends AuthController {
	
	/**
	 * 店铺列表
	 */
	public function storeList() {
		$Store = D('Admin/Store');
		
		$map = array();
		
		//排除非当前管理员的下线,除了超管和小管理员
		$is_small_super = $this->isSmallSuperManager();
		if (!$is_small_super && session("admin_id")!=1) {
			$map['repath'] = array('like', "%,".session("admin_mid").",%");
			$map = array_merge($map, $this->filterMember(session('admin_mid'), true, '', $map));
		}
		
		$store_name = $this->get['store_name'];
		$type = $this->get['type'];
		$province = $this->get['province'];
		$city = $this->get['city'];
		$country = $this->get['country'];
		$store_activity = $this->get['store_activity'];
		$store_status = $this->get['store_status'];
		$store_type = $this->get['store_type'];
		$store_supermarket = $this->get['store_supermarket'];
		
		if (!empty($store_activity) && !validateExtend($store_activity, 'NUMBER')) {
			$this->error('店铺活动参数有误');
		}
		if (!empty($store_status) && !validateExtend($store_status, 'NUMBER')) {
			$this->error('店铺状态参数有误');
		}
		
		if (!empty($store_name)) {
			$map_1['sto.store_name'] = array('like', "%{$store_name}%");
			$map_1['sto.phone'] = array('eq', $store_name);
			$map_1['mem.loginname'] = array('eq', $store_name);
			$map_1['mem.nickname'] = array('eq', $store_name);
			$map_1['mem.username'] = array('eq', $store_name);
			$map_1['_logic'] = 'or';
			$map['_complex'] = $map_1;
		}
		
		//区域选择
		if (!empty($province)) {
			$map['sto.province'] = array('eq', $province);
		}
		if (!empty($city)) {
			$map['sto.city'] = array('eq', $city);
		}
		if (!empty($country)) {
			$map['sto.country'] = array('eq', $country);
		}
		$tpl = 'storeList';
		//店铺审核状态
		switch ($type) {
			case '0': //未审核
				$map['sto.manage_status'] = array('eq', 0);
				break;
			case '1': //审核通过
				$map['sto.manage_status'] = array('eq', 1);
				break;
			case '2': //驳回
				$map['sto.manage_status'] = array('eq', 2);
				break;
			case '3': //申请注销
				$map['sto.manage_status'] = array('eq', 10);
				$tpl = 'storeList3';
				break;
			case '4': //已注销
				$map['sto.manage_status'] = array('eq', 11);
				$tpl = 'storeList3';
				break;
		}
		
		//店铺状态
		switch ($store_status) {
			case '1': //正常
				$map['sto.status'] = array('eq', 0);
				break;
			case '2': //冻结
				$map['sto.status'] = array('eq', 1);
				break;
		}
		
		//店铺活动状态
		switch ($store_activity) {
			case '1': //未审
				$map['prw.manage_status'] = array('eq', 0);
				break;
			case '2': //已审已启用
				$map['prw.manage_status'] = array('eq', 1);
				$map['prw.status'] = array('eq', 0);
				break;
			case '3': //已审未启用
				$map['prw.manage_status'] = array('eq', 1);
				$map['prw.status'] = array('eq', 1);
				break;
			case '4': //已驳回
				$map['prw.manage_status'] = array('eq', 2);
				break;
		}
		//排序
		$orderby = 'sto.date_created desc,sto.id desc';
		
		//店铺类型
		if (validateExtend($store_type, 'NUMBER')) {
			$map['sto.store_type'] = array('eq', $store_type);
		}
		
		//商超类型
		if (validateExtend($store_supermarket, 'NUMBER')) {
			$map['sto.store_supermarket'] = array('eq', $store_supermarket);
		}
		
		$count = $Store->alias('sto');
		$count = $count->join('LEFT JOIN __MEMBER__ mem ON mem.id=sto.uid');
		$count = $count->join('LEFT JOIN __PREFERENTIAL_WAY__ prw ON prw.store_id=sto.id');
		$count = empty($map) ? $count : $count->where($map);
		$count = $count->count();
		$limit = $this->Page($count, 20, $this->get);
		
		
		$list = $Store->getNewList($map, true, '', false, $limit, $orderby);
		$this->assign('list', $list);
		$this->display($tpl);
	}
	
	/**
	 * 店铺编辑UI
	 */
	public function storeModify() {
		$Store = D('Admin/Store');
		
		$id = $this->get['id'];
		
		if (!validateExtend($id, 'NUMBER')) {
			$this->error('参数有误');
		}
		
		$map['sto.id'] = array('eq', $id);
		$info = $Store->getList($map, false);
		$info['store_img'] = str_replace('_sm', '', $info['store_img']);
		
		//环境图片
		$carousel1 = json_decode($info['evn_img'], true);
		$info['carousel1'] = '';
		foreach ($carousel1 as $k=>$v){
			$info['carousel1'][] = $v;
		}
		
		//升级认证图片
		$store_upgrade = json_decode($info['store_upgrade'], true);
		$info['store_upgrade'] = '';
		foreach ($store_upgrade as $k=>$v){
			$info['store_upgrade'][] = $v;
		}
		
		//获取用户客服配置信息
		$info['affiliate'] = M('UserAffiliate')->where('user_id='.$info['uid'])->find();
		
		$this->assign('info', $info);
		
		$zk = D('preferential_way')->where('store_id='.$info['id'])->find();
		$zk['discount'] = ($zk['conditions']-$zk['reward'])/$zk['conditions']*10;
		$this->assign('zk', $zk);
		
		//获取客服平台列表
		$CustomerServiceModel = new CustomerServiceModel();
		$platform_list = $CustomerServiceModel->platform;
		$customer_service_platform = M('CustomerServicePlatform')->order('platform_id asc')->select();
		foreach ($customer_service_platform as $k=>$v) {
			$customer_service_platform[$k]['platform_name'] = $platform_list[$v['platform_name']];
		}
		$this->assign('customer_service_platform', $customer_service_platform);
		
		$this->display();
	}
	
	/**
	 * 店铺编辑保存
	 */
	public function storeSave() {
		$Store = D('Admin/Store');
		
		//设置折扣正常范围内的最大和最小值
		$discount_max = 9.5;
		$discount_min = 5;
		
		$data = $this->post;
		
		M()->startTrans();
		
		//获取老discount值和老折扣表数据
		$old_discount = M('Store')->where('id='.$data['id'])->field('discount')->find();
		$preferentialway_info = M('PreferentialWay')->where('store_id='.$data['id'])->field('conditions,reward')->find();
		if (!$old_discount || !$preferentialway_info) {
			$this->error('保存失败:店铺老折扣数据或折扣表数据不存在');
		}
		$preferentialway_discount = ($preferentialway_info['conditions'] - $preferentialway_info['reward'])/10;
		
		if ($data['discount'] <= $discount_max && $data['discount'] >= $discount_min){
			//判断折扣表数据是否大于新discount值,如果大于新discount值,则保存折扣表数据
			if ($preferentialway_discount > $data['discount']) {
				$data['discount'] = $preferentialway_discount;
			} else {
				$data_pw['pname'] = $data['pname'];
				$data_pw['conditions'] = 100;
				$data_pw['reward'] = round(100-($data['discount']*10));
				$data_pw['manage_status'] = 1;
				$PreferentialWay = M('PreferentialWay');
				if ($PreferentialWay->where('store_id='.$data['id'])->save($data_pw) === false) {
					$this->error('保存失败:01');
				}
			}
			unset($data['pname']);
		} else {
			//判断老discount字段值是否为0或在值在非正常范围内
			if ($old_discount['discount'] == '0' || ($old_discount['discount'] <= $discount_max && $old_discount['discount'] >= $discount_min)) {
				//判断折扣表数据是否在正常范围内
				if ($preferentialway_discount <= $discount_max && $preferentialway_discount >= $discount_min) {
					//如果折扣表数据为正常范围内,则同步折扣表数据至discount字段
					$data['discount'] = $preferentialway_discount;
				} else {
					$this->error('保存失败:原始活动折扣数据在非合理折扣数据范围内,请修改为合理的活动折扣');
				}
			} else {
				//老折扣数据在合理范围内,则舍弃非合理的新折扣数据
				unset($data['discount']);
			}
		}
		//上传单个图片
		$upload_config = array (
			'file' => 'multi',
			'exts' => array('jpg','png','gif','jpeg'),
			'path' => 'shop/'. date('Ymd'),
		);
		$Upload = new \Common\Controller\UploadController($upload_config);
		$upload_info = $Upload->upload();
		if (empty($upload_info['error'])) {
			foreach ($upload_info['data'] as $k=>$v){
				$data[$k] = $v['url'];
			}
		}
		//营业时间格式处理
		$data['start_time'] = strtotime(date('Y-m-d '). $data['start_time']);
		$data['end_time'] = strtotime(date('Y-m-d '). $data['end_time']);
		
		//环境图片
		foreach($_POST['photo'] as $k=>$v){
			if($v != ''){
				$navimg['pic'.($k+1)]=$v;
			}
		}
		foreach($_POST['upimg'] as $k=>$v){
			if($v != ''){
				$upimg['pic'.($k+1)]=$v;
			}
		}
		$data['evn_img'] = json_encode($navimg,JSON_UNESCAPED_SLASHES);
		$data['store_upgrade'] = json_encode($upimg,JSON_UNESCAPED_SLASHES);
		
		//处理用户附属表信息
		$data_affiliate = $data['affiliate'];
		unset($data['affiliate']);
		if (empty($data_affiliate['service_platform_id'])) {
		    $data_affiliate['service_platform_id'] = null;
		    $data_affiliate['service_platform_config'] = null;
		}
		//判断该用户的附属信息是否存在
		$affiliate_info = M('UserAffiliate')->where('user_id='.$data['uid'])->field('affiliate_id')->find();
		if ($affiliate_info) {
			$result1 = M('UserAffiliate')->where('user_id='.$data['uid'])->save($data_affiliate);
		} else {
			$data_affiliate['user_id'] = $data['uid'];
			$result1 = M('UserAffiliate')->add($data_affiliate);
		}
		
		$result2 = $Store->where('id='.$data['id'])->save($data);
		
		if ($result1 === false || $result2 === false) {
			M()->rollback();
			$this->error('保存失败');
		} else {
			M()->commit();
			$this->success('保存成功', '', false, "编辑店铺:{$this->post['store_name']}[ID:{$this->post['id']}]");
		}
	}
	
	/**
	 * 店铺审核通过
	 */
	public function storePass() {
		$Store = D('Admin/Store');
		
		$id = $this->get['id'];
		
		if (!validateExtend($id, 'NUMBER')) {
			$this->error('参数有误');
		}
		
		M()->startTrans();
		
		$map['sto.id'] = array('eq', $id);
		$info = $Store->getList($map, false, 'sto.store_name,mem.loginname,mem.id mid');
		
		$data['manage_status'] = 1;
		$data['status'] = 0;
		$result1 = $Store->alias('sto')->where($map)->save($data);
		
		//同步会员表是否已开店字段状态
		$data_member['store_flag'] = 1;
		$map_member['id'] = array('eq', $info['mid']);
		$result2 = M('Member')->where($map_member)->save($data_member);
		
		//同步添加对应管理员用户
		$manager_data = array(
			'uid' => $info['mid'],
			'group_id' => array(C('ROLE_MUST_LIST.merchant')),
			'type' => 'merchant',
		);
		$SystemManager = new \Admin\Controller\AjaxController();
		$result3 = $SystemManager->memberAddAsyn($manager_data);
		
		$result4 = M('preferential_way')->where('store_id='.$id)->save(array('manage_status'=>1));
		
		//给商家推送通知
		$result5 = pushQueue('您的店铺申请已审核通过', 'myshop_index', array('status'=>'1'), $info['mid']);
		
		if ($result1 === false || $result2 === false || $result3 === false || $result4 === false || $result5 === false) {
			M()->rollback();
			$this->error('操作失败，请稍后重试');
		} else {
			M()->commit();
			$this->success('已审核通过', '', false, "审核通过店铺:{$info['store_name']}[ID:{$id}]");
		}
	}
	
	/**
	 * 店铺审核注销
	 */
	public function storeout() {
		$Store = D('Admin/Store');

		$id = $this->get['id'];

		if (!validateExtend($id, 'NUMBER')) {
			$this->error('参数有误');
		}
		
		M()->startTrans();

		$map['sto.id'] = array('eq', $id);
		$info = $Store->getList($map, false, 'sto.store_name,mem.loginname,mem.id mid');
		
		$data['manage_status'] = 11;
		$data['status'] = 0;
		$result1 = $Store->alias('sto')->where($map)->save($data);
		
		//同步会员表是否已开店字段状态
		$data_member['store_flag'] = 0;
		$map_member['id'] = array('eq', $info['mid']);
		$result2 = M('Member')->where($map_member)->save($data_member);
		
		//同步删除对应管理员用户
		$SystemManager = new \Admin\Controller\AjaxController();
		$result3 = $SystemManager->memberDeleteAsyn($info['mid'], 'merchant');
		
		$result4 = M('preferential_way')->where('store_id='.$id)->save(array('manage_status'=>3));
		
		if ($result1 === false || $result2 === false || $result3 === false || $result4 === false) {
			M()->rollback();
			$this->error('操作失败，请稍后重试');
		} else {
			M()->commit();
			$this->success('已审核通过注销', '', false, "审核通过店铺注销:{$info['store_name']}[ID:{$id}]");
		}
	}
	
	/**
	 * 驳回店铺注销申请
	 */
	public function storeoutReject() {
		$Store = D('Admin/Store');
	
		$id = $this->get['id'];
	
		if (!validateExtend($id, 'NUMBER')) {
			$this->error('参数有误');
		}
		
		M()->startTrans();
	
		$map['sto.id'] = array('eq', $id);
		$info = $Store->getList($map, false, 'sto.store_name,mem.loginname,mem.id mid');
		
		$data['manage_status'] = 1;
		$data['status'] = 0;
		$result1 = $Store->alias('sto')->where($map)->save($data);
		
		//同步会员表是否已开店字段状态
		$data_member['store_flag'] = 1;
		$map_member['id'] = array('eq', $info['mid']);
		$result2 = M('Member')->where($map_member)->save($data_member);
		
		if ($result1 === false || $result2 === false) {
			M()->rollback();
			$this->error('操作失败，请稍后重试');
		} else {
			M()->commit();
			$this->success('店铺注销申请已驳回', '', false, "驳回店铺注销申请:{$info['store_name']}[ID:{$id}]");
		}
	}
	
	/**
	 * 店铺驳回
	 */
	public function storeReject() {
		$Store = D('Admin/Store');
		
		$id = $this->get['id'];
		$message = urldecode($this->get['reason']);
		
		if (!validateExtend($id, 'NUMBER')) {
			$this->error('参数有误');
		}
		if (empty($message)) {
			$this->error('请填写驳回原因');
		}
		
		M()->startTrans();
		
		$map['sto.id'] = array('eq', $id);
		$info = $Store->getList($map, false, 'sto.store_name,mem.loginname,mem.id mid');
		
		$data['manage_status'] = 2;
		$data['message'] = $message;
		$result1 = $Store->alias('sto')->where($map)->save($data);
		
		//同步会员表是否已开店字段状态
		$data_member['store_flag'] = 0;
		$map_member['id'] = array('eq', $info['mid']);
		$result2 = M('Member')->where($map_member)->save($data_member);
		
		//同步删除对应管理员用户
		$SystemManager = new \Admin\Controller\AjaxController();
		$result3 = $SystemManager->memberDeleteAsyn($info['mid'], 'merchant');
		
		//活动驳回
		$result4 = M('preferential_way')->where('store_id='.$id)->save(array('manage_status'=>2));
		
		//给商家推送通知
		$result5 = pushQueue('您的店铺申请由于不符合条件已被驳回', 'myshop_index', array('status'=>'0'), $info['mid']);
		
		if ($result1 === false || $result2 === false || $result3 === false || $result4 === false || $result5 === false) {
			M()->rollback();
			$this->error('操作失败，请稍后重试');
		} else {
			M()->commit();
			$this->success('已驳回', '', false, "驳回店铺:{$info['store_name']}[ID:{$id}]");
		}
	}
	
	/**
	 * 店铺冻结
	 */
	public function storeForzen() {
		$Store = D('Admin/Store');
		
		$id = $this->get['id'];
		
		if (!validateExtend($id, 'NUMBER')) {
			$this->error('参数有误');
		}
		
		M()->startTrans();
		
		$map['sto.id'] = array('eq', $id);
		$info = $Store->getList($map, false, 'sto.store_name,mem.loginname,mem.id mid');
		
		$data['status'] = 1;
		$result1 = $Store->alias('sto')->where($map)->save($data);
		
		//同步删除对应管理员用户
		$SystemManager = new \Admin\Controller\AjaxController();
		$result2 = $SystemManager->memberDeleteAsyn($info['mid'], 'merchant');
		
		if ($result1 === false || $result2 === false) {
			M()->rollback();
			$this->error('操作失败，请稍后重试');
		} else {
			M()->commit();
			$this->success('已冻结', '', false, "冻结店铺:{$info['store_name']}[ID:{$id}]");
		}
	}
	
	/**
	 * 店铺解冻
	 */
	public function storeThaw() {
		$Store = D('Admin/Store');
		
		$id = $this->get['id'];
		
		if (!validateExtend($id, 'NUMBER')) {
			$this->error('参数有误');
		}
		
		M()->startTrans();
		
		$map['sto.id'] = array('eq', $id);
		$info = $Store->getList($map, false, 'sto.store_name,mem.loginname,mem.id mid');
		
		$data['status'] = 0;
		$result1 = $Store->alias('sto')->where($map)->save($data);
		
		//同步添加对应管理员用户
		$manager_data = array(
			'member_id' => $info['mid'],
			'group_id' => array(C('ROLE_MUST_LIST.merchant')),
			'type' => 'merchant',
		);
		$SystemManager = new \Admin\Controller\AjaxController();
		$result2 = $SystemManager->memberAddAsyn($manager_data);
		
		if ($result1 === false || $result2 === false) {
			M()->rollback();
			$this->error('操作失败，请稍后重试');
		} else {
			M()->commit();
			$this->success('已解冻', '', false, "解冻店铺:{$info['store_name']}[ID:{$id}]");
		}
	}
	
	/**
	 * 店铺删除
	 */
	public function storeDelete() {
		$this->error('该功能已停用');
		
		$Store = M('Store');
		$Member = M('Member');
		
		$id = $this->get['id'];
		
		if (!validateExtend($id, 'NUMBER')) {
			$this->error('参数格式有误');
		}
		
		M()->startTrans();
		
		$map['id'] = array('eq', $id);
		$info = $Store->where($map)->lock(true)->field('uid,store_name')->find();
		$result1 = $Store->where($map)->delete();
		
		$map_member['id'] = array('eq', $info['uid']);
		$data_member = array(
			'store_flag' => 0
		);
		$result2 = $Member->where($map_member)->save($data_member);
		
		//同步删除对应管理员用户
		$SystemManager = new \Admin\Controller\AjaxController();
		$result3 = $SystemManager->memberDeleteAsyn($info['loginname'], 'merchant');
		
		if ($result1 === false || $result2 === false || $result3 === false) {
			M()->rollback();
			$this->error('操作失败，请稍后重试');
		} else {
			M()->commit();
			$this->success("删除成功", U('Store/storeList'), false, "删除店铺:{$info['store_name']}[ID:{$id}]");
		}
	}
	
	/**
	 * 店铺详情
	 */
	public function storeDetail() {
		$Store = D('Admin/Store');
		
		$id = $this->get['id'];
		
		if (!validateExtend($id, 'NUMBER')) {
			$this->error('参数有误');
		}
		
		$map['sto.id'] = array('eq', $id);
		$info = $Store->getList($map, false);
		$info['store_img'] = str_replace('_sm', '', $info['store_img']);
		
		$this->assign('info', $info);
		$zk = D('preferential_way')->where('store_id='.$info['id'])->find();
		$zk['discount'] = ($zk['conditions']-$zk['reward'])/$zk['conditions']*10;

		$this->assign('info', $info);
		$this->assign('zk', $zk);
		$this->display();
	}
	
	/**
	 * 店铺订单列表
	 */
	public function storeOrderList() {
		$Orders = M('Orders');
		
		$id = $this->get['id'];
		$order_number = $this->get['order_number'];
		$time_min = $this->get['time_min'];
		$time_max = $this->get['time_max'];
// 		$exchangeway = $this->get['exchangeway']; //兑换类型
		$page = $this->get['p']>0 ? $this->get['p'] : 1;
		$order_status = $this->get['order_status'];
		$amount_type = $this->get['amount_type'];
		
		if (!validateExtend($id, 'NUMBER')) {
			$this->error('参数有误');
		}
		
		$where = ' 1=1 ';
		
		//订单号
		if (!empty($order_number)) {
			$where .= " and order_number='{$order_number}' ";
		}
		
		//时间范围
		if (!empty($time_min)) {
			$time_min = strtotime($time_min);
		} else {
			$time_min = strtotime(date('Ym01'));
		}
		if (!empty($time_max)) {
			$time_max = strtotime($time_max.' 23:59:59');
		} else {
			$time_max = strtotime(date('Y-m-d 23:59:59'));
		}
		if (date('Ym', $time_min) != date('Ym', $time_max)) {
			$this->error('选择日期必须为同一个月');
		}
		$where .= " and time>='{$time_min}' and time<='{$time_max}' ";
		
		//兑换方式
// 		if ($exchangeway != '') {
// 			$where .= " and exchangeway={$exchangeway} ";
// 		}

		//支付类型
		if (!empty($amount_type)) {
			$where .= " and amount_type={$amount_type} ";
		}
		
		$where .= " and storeid={$id} ";
		
		//订单状态
		if ($order_status != '') {
			$where .= " and order_status={$order_status} ";
		}
		
		$count = $Orders->where($where)->count();
		$this->Page($count, 20, $this->get);
		
		$list = $Orders->where($where)->order('id desc')->page($page, 20)->select();
		foreach ($list as $k=>$v){
			//付款状态
			$order_status_cn = C('FIELD_CONFIG')['orders']['order_status'][$v['order_status']];
			if ($v['end_time'] != '0' && time() > $v['end_time'] && $v['order_status'] == '0') {
				$order_status_cn = '已过期';
			}
			$list[$k]['order_status_cn'] = $order_status_cn;
			
			//买家信息
			if (!empty($v['uid'])) {
				$member_info = M('Member')->where('id='.$v['uid'])->field('loginname,nickname,username')->find();
				$list[$k]['loginname'] = $member_info['loginname'];
				$list[$k]['nickname'] = $member_info['nickname'];
				$list[$k]['username'] = $member_info['username'];
			}
			
			//订单状态中文
			$list[$k]['amount_type_cn'] = C('FIELD_CONFIG')['orders']['amount_type'][$v['amount_type']];
		}
		$this->assign('list', $list);
		
		//获取兑换类型列表
		$this->assign("exchangewaydata", C('FIELD_CONFIG')['orders']['exchangeway']);
		
		$this->display();
	}
	
	/**
	 * 店铺活动列表
	 */
	public function storePreferentialWayList() {
		$PreferentialWay = D('Admin/PreferentialWay');
		
		$map = array();
		
		//排除非当前管理员的下线,除了超管和小管理员
		$is_small_super = $this->isSmallSuperManager();
		if (!$is_small_super && session("admin_id")!=1) {
			$map['mem.repath'] = array('like', "%,".session("admin_mid").",%");
			$map = array_merge($map, $this->filterMember(session('admin_mid'), true, 'mem', $map));
		}
		
		$pname = $this->get['pname'];
		$type = $this->get['type'];
		
		if (!empty($pname)) {
			$map['_string'] = " (pre.pname like '%{$pname}%' or sto.store_name like '%{$pname}%') ";
		}
		
		switch ($type) {
			case '0': //未审核
				$map['pre.manage_status'] = array('eq', 0);
				break;
			case '1': //审核通过
				$map['pre.manage_status'] = array('eq', 1);
				break;
			case '2': //驳回
				$map['pre.manage_status'] = array('eq', 2);
				break;
		}
		
		$count = $PreferentialWay->alias('pre');
		$count = $count->join('LEFT JOIN __STORE__ sto ON sto.id=pre.store_id');
		$count = $count->join('JOIN __MEMBER__ mem ON mem.id=sto.uid');
		$count = empty($map) ? $count : $count->where($map);
		$count = $count->count();
		$limit = $this->Page($count, 20, $this->get);
		
		$list = $PreferentialWay->getList($map, true, '', false, $limit);
		$this->assign('list', $list);
		$this->display();
	}
	/**
	 * 店铺活动详情
	 */
	public function storePreferentialWayDetail() {
		$id=I('get.id');
		$preferential_way=M("preferential_way")->alias('as pre');
		$list=$preferential_way->join("zc_store AS sto ON sto.id = pre.store_id")->where("pre.id='$id'")->find();
		//echo $preferential_way->_sql();//dump($list);
		$this->assign('info',$list);
		$this->display();
	}
	
	/**
	 * 店铺活动审核通过
	 */
	public function storePreferentialWayPass() {
		$PreferentialWay = D('Admin/PreferentialWay');
	
		$id = $this->get['id'];
	
		if (!validateExtend($id, 'NUMBER')) {
			$this->error('参数有误');
		}
	
		$map['pre.id'] = array('eq', $id);
		$data['manage_status'] = 1;
		if ($PreferentialWay->alias('pre')->where($map)->save($data) === false) {
			$this->error('操作失败');
		} else {
			$info = $PreferentialWay->getList($map, false, 'pre.pname,sto.store_name');
			$this->success('已审核通过', '', false, "审核通过店铺[{$info['store_name']}]的活动:{$info['pname']}[ID:{$id}]");
		}
	}

	/**
	 * 店铺活动驳回
	 */
	public function storePreferentialWayReject() {
		$PreferentialWay = D('Admin/PreferentialWay');
	
		$id = $this->get['id'];
	
		if (!validateExtend($id, 'NUMBER')) {
			$this->error('参数有误');
		}
	
		$map['pre.id'] = array('eq', $id);
		$data['manage_status'] = 2;
		if ($PreferentialWay->alias('pre')->where($map)->save($data) === false) {
			$this->error('操作失败');
		} else {
			$info = $PreferentialWay->getList($map, false, 'pre.pname,sto.store_name');
			$this->success('已驳回', '', false, "驳回店铺[{$info['store_name']}]的活动:{$info['pname']}[ID:{$id}]");
		}
	}
	
	/**
	 * 批量设置店铺类型
	 */
	public function batchStoreType() {
		$Store = M('Store');
		
		$store_config = C('FIELD_CONFIG.store');
		
		$store_id_list = $this->post['id'];
		$store_type = $this->post['store_type'];
		
		if (empty($store_id_list)) {
			exit('请选择要操作的商家店铺');
		}
		
		//判断要操作的字段和对应值
		$field = explode('_', $store_type);
		$field_key = 'store_'.$field[0];
		$field_value = $field[1]; 
		
		$data_store = array();
		$log_content = '';
		
		//判断要设置的对应类型是否存在
		if (!array_key_exists($field_value, $store_config[$field_key])) {
			exit('操作的类型不存在');
		}
		
		//设置对应类型的值
		$data_store[$field_key] = $field_value;
		
		//日志记录内容
		$log_content = "批量修改商家类型为[{$field_value}][{$store_config[$field_key][$field_value]}]";
		
		$map_store['id'] = array('in', implode(',', $store_id_list));
		if ($Store->where($map_store)->save($data_store) === false) {
			exit('批量修改商家类型失败');
		} else {
			$this->logWrite($log_content);
			exit;
		}
	}
	
	/**
	 * 一键分配商家管理员账号
	 */
	public function batchManager() {
		//保证全部执行完成
		set_time_limit(0);
		ignore_user_abort(true);
		
		$Member = M('Member');
	
		if (session('admin_id') != 1) {
			$this->error('无操作权限');
		}
	
		$role_must_list = C('ROLE_MUST_LIST');
		$Manager = new \Admin\Controller\AjaxController();
	
		$error = false;
		$merchant = $Member
			->alias('mem')
			->join('join __STORE__ sto ON sto.uid=mem.id')
			->join('left join __MANAGER__ man ON man.uid=mem.id')
			->where('mem.store_flag=1 and mem.is_lock=0 and sto.status=0 and sto.manage_status=1')
			->field('mem.loginname,mem.nickname,mem.username,mem.id mid,man.id')
			->group('mem.id')
			->select();
		foreach ($merchant as $k=>$list) {
			//过滤掉已经分配过商家管理员角色的
			if (isset($list['id'])) {
				$map_group_access['group_id'] = array('eq', $role_must_list['merchant']);
				$map_group_access['uid'] = array('eq', $list['id']);
				$group_acess_info = M('AuthGroupAccess')->where($map_group_access)->find();
				if ($group_acess_info) {
					continue;
				}
			}
			
			$group_id = array($role_must_list['merchant']);
				
			$manager_data = array(
				'member_id' => $list['mid'],
				'group_id' => $group_id,
				'type' => 'merchant',
			);
				
			$status = $Manager->memberAddAsyn($manager_data);
			if (!$status) {
				$error .= "{$merchant['username']}[{$merchant['loginname']}][{$merchant['nickname']}]分配商家管理员账号失败.\r\n";
			}
		}
	
		if ($error) {
			$this->error("部分商家管理员账号分配失败:\r\n{$error}", U('Store/storeList', array('type'=>1)), 20);
		} else {
			$this->success('账号已全部分配成功', U('Store/storeList', array('type'=>1)), false, "一键分配商家管理员账号");
		}
		
		exit;
	}
	
}
?>