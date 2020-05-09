<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 店铺相关接口 
// +----------------------------------------------------------------------
namespace APP\Controller;
use Common\Controller\ApiController;
use V4\Model\AccountModel;
use V4\Model\PaymentMethod;
use V4\Model\Currency;
use V4\Model\AccountRecordModel;
use V4\Model\CurrencyAction;
use V4\Model\ShakeModel;
use V4\Model\OrderModel;
use V4\Model\BaseModel;

class ShopController extends ApiController {
	
	/**
	 * 我的店铺
	 * @param uid 会员ID
	 */
	public function myShop() {
		$uid = I('post.uid');
		
		if ($uid == "") {
			$this->myApiPrint('非法访问！');
		}
		
		$wherekey['uid'] = $uid;
		$wherekey['manage_status'] = '1';
		//$wherekey['status'] = '0';
		$data = M('store')->field('id as store_id,store_img,store_name,address store_address,attention,fid,status,store_supermarket')->where($wherekey)->order('id desc')->find();
		
		$wh['storeid'] = $data['store_id'];
		$wh['favorite'] = '1';
		$da = M('favorite_store')->where($wh)->count();
		if (empty($data)) {
			$this->myApiPrint('此用户不存在！');
		} elseif ($data['status'] == '1') {
			$this->myApiPrint('店铺已被冻结');
		} else {
			unset($data['status']);
		}
		$data['favorite'] = $da;
		
		
		//商城订单
		$owc['storeid'] = $data['store_id'];
		$owc['exchangeway'] = 1;
		$data['shangchengcount'] = M('orders')->where($owc)->count();
		$owc['exchangeway'] = 2;
		$data['maidancount'] = M('orders')->where($owc)->count();
		
		$this->myApiPrint('查询成功', 400, $data);
	}
	
	/**
	 * 我的店铺粉丝
	 * @param storeid 店铺ID
	 */
	public function myFavoriteS() {
		$storeid = I('post.storeid');
		
		if ($storeid == "") {
			$this->myApiPrint('非法访问！');
		}
		
		$everyPage = '10';
		
		$page = intval(I('post.page')) - 1;
		$page = $page>0 ? $page*$everyPage : 0;
		
		$wherekey['a.storeid'] = $storeid;
		$wherekey['a.favorite'] = '1';
		$totalPage = M('orders_store')->where($st)->count();
		$pageString = $page.','.$everyPage;
		
		$data = M('favorite_store a')
			->field('b.img,b.nickname,b.loginname phone')
			->join('zc_member b on b.id=a.uid')
			->where($wherekey)
			->limit($pageString)
			->select();
		if (empty($data)) {
			$this->myApiPrint('找不到数据！',400);
		}
		
		$page1['totalPage'] = floor(($totalPage-1)/10)+1;
		$page1['everyPage'] = $everyPage;
		$data1['data'] = $data;
		$data1['page'] = $page1;
		
		$this->myApiPrint('查询成功！',400,$data1);
	}
	
	/**
	 * 我的商品粉丝
	 * @param productid 商品ID
	 */
	public function myFavoriteP() {
		$productid = I('post.productid');
		
		if ($productid == "") {
			$this->myApiPrint('非法访问！');
		}
		
		$everyPage='10';
		
		$page = intval(I('post.page')) - 1;
		$page = $page>0 ? $page*$everyPage : 0;
		
		$wherekey['a.productid'] = $productid;
		$wherekey['a.favorite'] = '1';
		$totalPage = M('orders_store')->where($st)->count();
		$pageString = $page.','.$everyPage;
		
		$data = M('favorite_product a')
			->field('b.img,b.nickname,b.loginname phone')
			->join('zc_member b on b.id=a.uid')
			->where($wherekey)
			->limit($pageString)
			->select();
		if (empty($data)) {
			$this->myApiPrint('找不到数据！',400);
		}
		
		$page1['totalPage'] = floor(($totalPage-1)/10)+1;
		$page1['everyPage'] = $everyPage;
		$data1['data'] = $data;
		$data1['page'] = $page1;
		
		$this->myApiPrint('查询成功！',400,$data1);
	}
	
	/**
	 * 发货订单列表
	 * @param storeid 店铺ID
	 * @param status 订单状态
	 * @param page 分页
	 */
	public function deliver_list() {
		$storeid = I('post.storeid');
		$status = I('post.status');
		$page = I('post.page');
		
		if (($storeid == '') || ($status == '')) {
			$this->myApiPrint('数据错误！', 300);
		}
		
		$everyPage = '10';
		
		$page = intval(I('post.page')) - 1;
		$page = $page>0 ? $page*$everyPage : 0;
		
		switch ($status) {
			case '10':
				$st = 'storeid=' . $storeid . ' and status>10 and status<=13';
				break;
			case '11':
				$st = 'storeid=' . $storeid . ' and status=11';
				break;
			case '12':
				$st = 'storeid=' . $storeid . ' and status=12';
				break;
			case '13':
				$st = 'storeid=' . $storeid . ' and status=13';
				break;
			default:
				$this->myApiPrint('查无此状态！');
		}
		
		$totalPage = M('orders_store')->where($st)->count();
		$pageString = $page . ',' . $everyPage;
		
		$data = M('orders_store')
			->field('id,store_img,store_name,productname,num,goldcoin,start_time,end_time,time,status')
			->where($st)
			->order('id desc')
			->limit($pageString)
			->select();
		
		if (empty($data)) {
			$page1['totalPage'] = floor(($totalPage - 1) / 10) + 1;
			$page1['everyPage'] = $everyPage;
			$data1['page'] = $page1;
			$this->myApiPrint('查无更多数据！',400,$data1);
		}
		$page1['totalPage'] = floor(($totalPage - 1) / 10) + 1;
		$page1['everyPage'] = $everyPage;
		$data1['data'] = $data;
		$data1['page'] = $page1;
		
		$this->myApiPrint('查询成功！', 400, $data1);
	}
	
	/**
	 * 发货订单确认
	 * @param orderid 订单ID
	 */
	public function deliver_confirm() {
		$orderid = I('post.orderid');
		
		if ($orderid == '') {
			$this->myApiPrint('数据错误！', 300);
		}
		
		$st['id'] = $orderid;
		$data = M('orders')->where($st)->find();
		if (empty($data)) {
			$this->myApiPrint('查无更多数据！',400,$data);
		}
		if ($data['status']!='11'){
			$this->myApiPrint('状态不正确！',300,$data['status']);
		}
		
		$wh['status'] = '12';
		$res = M('orders')->where($st)->save($wh);
		if($res !== false){
			$this->myApiPrint('确认成功！', 400,'');
		}else{
			$this->myApiPrint('确认失败！');
		}
	}
	
	/**
	 * 收货订单列表
	 * @param uid 会员ID
	 * @param status 订单状态
	 * @param page 分页
	 */
	public function receipt_list() {
		$uid = I('post.uid');
		$status = I('post.status');
		$page = I('post.page');
		
		if (($uid == '') || ($status == '')) {
			$this->myApiPrint('数据错误！', 300, $pg);
		}
		
		$everyPage = '10';
		
		$page = intval(I('post.page')) - 1;
		$page = $page>0 ? $page*$everyPage : 0;
		
		switch ($status) {
			case '10':
				$st = 'uid=' . $uid . ' and status>10 and status<=13';
				break;
			case '11':
				$st = 'uid=' . $uid . ' and status=11';
				break;
			case '12':
				$st = 'uid=' . $uid . ' and status=12';
				break;
			case '13':
				$st = 'uid=' . $uid . ' and status=13';
				break;
			default:
				$this->myApiPrint('查无此状态！');
		}
		
		$totalPage = M('orders_store')->where($st)->count();
		$pageString = $page . ',' . $everyPage;
		
		$data = M('orders_store')
			->field('id,store_img,store_name,iscontent,productname,num,goldcoin,start_time,end_time,time,status')
			->where($st)
			->order('id desc')
			->limit($pageString)
			->select();
		if (empty($data)) {
			$page1['totalPage'] = floor(($totalPage - 1) / 10) + 1;
			$page1['everyPage'] = $everyPage;
			$data1['page'] = $page1;
			$this->myApiPrint('查无更多数据！',400,$data1);
		}
		
		$page1['totalPage'] = floor(($totalPage - 1) / 10) + 1;
		$page1['everyPage'] = $everyPage;
		$data1['data'] = $data;
		$data1['page'] = $page1;
		
		$this->myApiPrint('查询成功！', 400, $data1);
	}
	
	
	/**
	 * 兑换订单列表
	 * @param storeid 店铺ID
	 * @param status 订单状态0.全部 1.待评价 2.已完成3.未完成 4.退款
	 * @param page 分页
	 */
	public function exchange_list() {
		$storeid = I('post.storeid');
        $order_status = intval(I('post.status'));  //0.全部 1.待评价 2.已完成3.未完成 4.退款
        $pn = I('post.page');
        if($pn < 1){
            $pn = 1;
        }
        
        verify_order_maidan($storeid);
        
        $om = new OrderModel();
        $data = $om->getOrderByStoreid($order_status, $storeid, $pn);
        
        $this->myApiPrint('查询成功！', 400, $data);
	}
	
	/**
	 * 兑换商品详情
	 * @param order_id 订单ID
	 */
	public function exchange_details() {
		$order_id = intval(I('post.order_id'));
		
		if ($order_id < 1) {
			$this->myApiPrint('数据错误！');
		}
		
		$sql = 'select o.id, o.storeid, o.order_number, o.productid, o.productname, o.aid, o.num, o.start_time, o.end_time, o.`comment`, o.exchangeway,p.userule, b.store_name, b.address, b.phone, o.time, o.order_status as `status`, CONCAT(p.img,\''.C('USER_ORDERINFO_SIZE').'\') as productimg, o.amount, o.goldcoin, m.loginname, o.time as pay_time, o.chknum,`d`.`consignee` AS `consignee`,`d`.`phone` AS `myphone`,`d`.`city_address` AS `city_address`,`d`.`address` AS `myaddress`,`d`.`postcode` AS `postcode` from zc_orders as o ' 
			.' left join zc_product as p on p.id = o.productid '
			.' left join zc_store as b on b.id = o.storeid '
			.' left join zc_member as m on m.id = o.uid '
			.' left join zc_address as d on d.id = o.aid '
			.' where o.exchangeway=0 and o.id = '.$order_id;
		$data1 = M()->query($sql);
		if (empty($data1)) {
			$this->myApiPrint('没有数据！', 400, NULL);
		}
		//对空的值进行处理
		foreach ($data1[0] as $k=>$v){
			if(trim($v) == ''){
				$data1[0][$k]='';
			}
		}
		//验证 兑换 订单是否过期
		$data1[0]['outtime'] = 0;
		if($data1[0]['exchangeway'] == 0){
			if($data1[0]['end_time'] < time()){
				$data1[0]['outtime'] = 1;
			}
		}
		$this->myApiPrint('查询成功！', 400, $data1[0]);
	}
	
	/**
	 * 兑换商品删除
	 * @param order_id 订单ID
	 */
	public function exchange_delete() {
		$order_id = I('post.order_id');
	
		if (empty($order_id)) {
			$this->myApiPrint('数据错误！');
		}
	
		$wherekey['a.id'] = $order_id;
		M('orders')->where($wherekey)->delete();
		
		$this->myApiPrint('删除完成！', 400, '');
	}
	
	/**
	 * 进入发布活动页面查询已发布的活动
	 * Enter description here ...
	 */
	public function actives_find(){
		$storeid = empty(I('post.storeid'))?-1:intval(I('post.storeid'));
		$info = M('preferential_way')->where('store_id='.$storeid)->find();
		if($info){
			$info['discount'] = ($info['conditions']-$info['reward'])/$info['conditions']*10;
			unset($info['post_time']);
			unset($info['id']);
			$this->myApiPrint('查询成功！', 400, $info);
		}else{
			$this->myApiPrint('您还未发布活动。', 300, "");
		}
		
	}
	
	/**
	 * 发布活动
	 * @param storeid
	 * @param pname
	 * @param discount
	 * @param start_time
	 * @param end_time
	 */
	public function actives_save()
	{
		$hh = date('H',time());
		if($hh < 9 || $hh > 10){
			$this->myApiPrint('每日上午9-10点可以修改！');exit;
		}
		
		$storeid = I('post.storeid');
		$pname = I('post.pname');  //活动标题
		$discount = I('post.discount');  //折扣
		$discount = str_replace('折', '', $discount);
		$status = 0;  //是否启用
		//$start_time = I('post.start_time');
		//$end_time = I('post.end_time');
		
		//验证信息
		if($discount == ''){
			$this->myApiPrint('信息必填！');exit;
		}
		$data['store_id'] = $storeid;
		$data['pname'] = $pname;
		$data['discount'] = $discount;
		$data['status'] = 0;
		$data['conditions'] = 100;
		//$data['start_time'] = strtotime('2016-12-12');
		//$data['end_time'] = strtotime('2030-16-16');
		$data['post_time'] = time();
		//验证折扣
        if(intval($data['discount']) < 5){
        	$this->myApiPrint('平台的店铺折扣最低5折', 300);
        }
        //处理图片
		$upload_config = array (
			'file' => $_FILES['img'],
			'exts' => array('jpg','png','gif','jpeg'),
			'path' => 'activity/'. date('Ymd')
		);
		$Upload = new \Common\Controller\UploadController($upload_config);
		$info = $Upload->upload();
		if (!empty($info['error'])) {
			//$this->myApiPrint('活动图片上传失败，请重新上传图片！',300,(object)$result);
		} else {
			$data['img'] = '';//$info['data']['url'];
		}
		
		if(is_numeric($data['discount']) && $data['discount'] < 10 && $data['discount'] > 1){
			$data['reward'] = round(100-($data['discount']*10));
			//$data['manage_status'] = 0;  //修改后未审核
		}else{
			$this->myApiPrint('数据错误！');
			exit;
		}
		//判断是否发布过，没有加新建，否则替换
		$hasinfo = M('preferential_way')->where('store_id='.$storeid)->find();
		if($hasinfo){
			$data['manage_status'] = 1;
			M('preferential_way')->where('store_id='.$data['store_id'])->save($data);
		}else{
			//if (empty($info['img']['savename'])) {
				//$this->myApiPrint('图片上传失败，请重新上传！');
			//}
			$data['manage_status'] = 1;
			M('preferential_way')->where('store_id='.$data['store_id'])->add($data);
		}
		D('store')->where('id='.$storeid)->save(array('discount'=>$discount));
		$this->myApiPrint('发布完成！', 400, '');
	}
	
	/**
	 * 发布活动记录
	 * @param storeid 店铺ID
	 */
	public function activity_record() {
		$storeid = I('post.storeid');
		
		if ($storeid == "") {
			$this->myApiPrint('数据错误！');
		}
		
		$wherekey['a.id'] = $storeid;
		$data = M('store a')
			->field('a.store_img,a.store_name,b.id,a.score,b.start_time,b.end_time,a.attention,b.conditions,b.reward,a.address,b.status')
			->join('zc_preferential_way b on b.store_id=a.id')
			->where($wherekey)
			->order('a.id desc')
			->select();
		
		$this->myApiPrint('查询完成！', 400, $data);
	}
	
	/**
	 * 查询账户余额情况
	 * Enter description here ...
	 */
	public function search_account(){
		$user_id = I('post.uid');
		$am = new AccountModel();
		$info = $am->getAccount($user_id, $am->get3BalanceFields());
		if($info){
			$data['goldcoin'] = $info['account_goldcoin_balance'];
			$data['colorcoin'] = $info['account_colorcoin_balance'];
			$data['cash'] = $info['account_cash_balance'];
			$this->myApiPrint('查询完成！', 400, $info);
		}else{
			$this->myApiPrint('没有数据！', 400);exit;
		}
	}
	
	/**
	 * 发布摇一摇,只能用现金积分
	 * @param uid 会员id
	 * @param time 次数
	 * @param amount 总金额
	 * @param userange 范围
	 */
	public function shake_save()
	{
		$user_id = I('post.uid');
		$shake_times = intval(I('post.times'));      //次数
		$one_amount = I('post.amount');         //单次金额
		$shake_ranges = intval(I('post.userange'));  //距离,使用范围
		$shake_amount = $one_amount * $shake_times;  //计算总金额
		
		$params = M('g_parameter', null)->find(1);
		//1.验证参数
		$post = verify_shake_save($user_id, $shake_times, $one_amount, $shake_ranges, $params);
		
		//2.判断余额
	    $currency = Currency::Cash;
	    $currencyaction = CurrencyAction::CashPushShake;
	    
        $am = new OrderModel();
        if(!$am->compareBalance($user_id, $currency, $shake_amount)){
            $this->myApiPrint('余额不足！');
        }
		
        //3.上传文件
        $pic = uploadImg('shake', 'img');
		
		//开启事务
		M()->startTrans(); 
	    $sm = new ShakeModel();
	    $res = $sm->publish_shake($post, $user_id, $shake_amount, $shake_ranges, $shake_times, $pic, $currency, $currencyaction);
		if (!$res) {
			M()->rollback(); 
			$this->myApiPrint('发布失败！');
		} else {
			M()->commit(); 
			$this->myApiPrint('发布完成！', 400, '');
		}
		
	}
	
	/**
	 * 发布商品>
	 * @param storeid
	 * @param product_name
	 * @param product_img //图片需要单独保存
	 * @param product_price
	 * @param product_kind
	 * @param product_total
	 * @param first_menu
	 * @param second_menu
	 * @param exchange_way
	 * @param start_time
	 * @param end_time
	 * @param product_content
	 * @param product_userule
	 * @param activity_id
	 */
	public function product_save()
	{
		$storeid = I('post.storeid');
		$name = I('post.product_name');
		$price = I('post.product_price'); //单价
		$totalnum = I('post.product_total'); //数量
		$sm_id = intval(I('post.second_menu')); //二级菜单id
		$start_time = I('post.start_time'); //有效期
		$end_time = I('post.end_time');
		$content = I('post.product_content'); //商品介绍
		$userule = I('post.product_userule'); //使用规则
		$exchangeway = I('post.exchange_way'); //兑换方式
		$type = intval(I('post.type')); //商超商品类型
		
		$data['storeid'] = $storeid;
		$data['name'] = $name;
		$data['price'] = $price;
		$data['totalnum'] = $totalnum;
		$data['typeid'] = $sm_id;
		$data['start_time'] = intval($start_time);
		$data['end_time'] = intval($end_time);
		$data['content'] = $content;
		$data['userule'] = $userule;
		$data['is_super'] = $type;
		$data['create_time'] = time();
		$data['status'] = 1; //下架状态
		$data['exchangeway'] = $exchangeway;
		
		if ($name == '' || $content == '' || $price == '') {
			$this->myApiPrint('数据错误！');
			exit;
		}
		if(intval($start_time) < 1 || intval($end_time) < 1 ){
			$this->myApiPrint('请选择时间！');
		}
		if(intval($start_time) > intval($end_time)){
			$this->myApiPrint('开始时间必须小于结束时间！');
		}
		if(intval($end_time) < time()){
			$this->myApiPrint('结束时间必须大于当前时间！');
		}
		
		$store_supermarket = M('store')->where('id='.$data['storeid'])->getField('store_supermarket');
		if($store_supermarket == 0 && $type > 0){
		    $data['is_super'] = 0;
		}
		
		$Product = M('Product');
		M()->startTrans();
		
		//同一店铺同一商品名称保持唯一
		$map_product['storeid'] = array('eq', $storeid);
		$map_product['name'] = array('eq', $name);
		$product_info = $Product->where($map_product)->lock(true)->field('id')->find();
		if ($product_info) {
			$this->myApiPrint('该商品已存在，请勿重复提交', 300);
		}
		
		//处理图片
		$upload_config = array (
			'file' => 'multi',
			'exts' => array('jpg','png','gif','jpeg'),
			'path' => 'product/'. date('Ymd')
		);
		$Upload = new \Common\Controller\UploadController($upload_config);
		$info = $Upload->upload();
		if (!empty($info['error'])) {
			$this->myApiPrint('图片上传失败', 300);
		} else {
			if ($info['data']['logo']) {
				$data['img'] = $info['data']['logo']['url'];
			} else {
				$this->myApiPrint('必须上传商品封面图片');
			}
			
			//第一张轮播图片
			if ($info['data']['photo1']) {
				$photo1 = $info['data']['photo1']['url'];
				$data['carousel1']['pic1'] = $photo1;
			} else {
				if(isset($_POST['photo1']) && $_POST['photo1'] != ''){
					$data['carousel1']['pic1'] = $_POST['photo1'];
				}
			}
			if($data['carousel1']['pic1']==''){
				$this->myApiPrint('必须上传商品轮播图');
			}
			
			if ($info['data']['photo2']) {
				$photo2 = $info['data']['photo2']['url'];
				$data['carousel1']['pic2'] = $photo2;
			}else{
				if(isset($_POST['photo2']) && $_POST['photo2'] != ''){
					$data['carousel1']['pic2'] = $_POST['photo2'];
				}
			}
			if ($info['data']['photo3']) {
				$photo3 = $info['data']['photo3']['url'];
				$data['carousel1']['pic3'] = $photo3;
			}else{
				if(isset($_POST['photo3']) && $_POST['photo3'] != ''){
					$data['carousel1']['pic3'] = $_POST['photo3'];
				}
			}
			if ($info['data']['photo4']) {
				$photo4 = $info['data']['photo4']['url'];
				$data['carousel1']['pic4'] = $photo4;
			}else{
				if(isset($_POST['photo4']) && $_POST['photo4'] != ''){
					$data['carousel1']['pic4'] = $_POST['photo4'];
				}
			}

			$data['carousel1'] = json_encode($data['carousel1'], JSON_UNESCAPED_SLASHES);
			
			$flag = $Product->add($data);
			if ($flag) {
				M()->commit();
				$this->myApiPrint('发布完成！', 400, '');
			} else {
				M()->rollback();
				$this->myApiPrint('提交失败，请稍后重试', 300);
			}
		}
		
	}
	
	
	/**
	 * 修改商品接口
	 * Enter description here ...
	 */
	public function product_update()
	{
		
		$id = I('post.id');
		$storeid = I('post.storeid');
		$name = I('post.product_name');
		$price = I('post.product_price');     //单价
		$totalnum = I('post.product_total');   //数量
		$sm_id = I('post.second_menu');      //二级菜单id
		$start_time = I('post.start_time'); //有效期
		$end_time = I('post.end_time');
		$content = I('post.product_content');   //商品介绍
		$userule = I('post.product_userule');   //使用规则
		$exchangeway = I('post.exchange_way');  //兑换方式
		$type = intval(I('post.type')); //商超商品类型
		
		$wherekey['id'] = $id;
		$wherekey['storeid'] = $storeid;
		
		$data['name'] = $name;
		$data['price'] = $price;
		$data['totalnum'] = $totalnum;
		$data['typeid'] = intval($sm_id);
		$data['start_time'] = $start_time;
		$data['end_time'] = $end_time;
		$data['content'] = $content;
		$data['userule'] = $userule;
		$data['is_super'] = $type;
		$data['status'] = 1;        //下架状态
		if ($name == '' || $content == '' || $price == '') {
			$this->myApiPrint('数据错误！');
			exit;
		}
		//对商品兑换时间进行判断
		if(intval($start_time) < 1 || intval($end_time) < 1 ){
			$this->myApiPrint('时间错误！');
		}
		if(intval($start_time) > intval($end_time)){
			$this->myApiPrint('数据错误！');
		}
		if(intval($end_time) < time()){
			$this->myApiPrint('数据错误！');
		}
		
	    $store_supermarket = M('store')->where('id='.$storeid)->getField('store_supermarket');
        if($store_supermarket == 0 && $type > 0){
            $data['is_super'] = 0;
        }
        
		$data['exchangeway'] = $exchangeway;
		$data['manage_status'] = 0; //未状态
		//处理图片
		$upload_config = array (
			'file' => 'multi',
			'exts' => array('jpg','png','gif','jpeg'),
			'path' => 'product/'. date('Ymd')
		);
		$Upload = new \Common\Controller\UploadController($upload_config);
		$info = $Upload->upload();
		
		//加载之前上传的图片信息  根目录：DOCUMENT_ROOT
		$details = M('product')->where($wherekey)->find();
		$imglist = json_decode($details['carousel1'], true);
		if (!empty($info['error'])) {
			//没有上传图片就不动
		} else {
			if ($info['data']['logo']) {
				$logo = $info['data']['logo']['url'];
				//makeWater($logo);
				//获取缩略图
				$data['img'] = $logo;
			}
			$carousel1 = array();
			//修图图片
			if ($info['data']['photo1']) {
				$photo1 = $info['data']['photo1']['url'];
				$carousel1['pic1'] = $photo1;
				//删除老图片
				//deloldimg($imglist['pic1']);
				//图片加水印
				//makeWater($photo1);
			} 
			if ($info['data']['photo2']) {
				$photo2 = $info['data']['photo2']['url'];
				$carousel1['pic2'] = $photo2;
				//删除老图片
				//deloldimg($imglist['pic2']);
				//图片加水印
				//makeWater($photo2);
			}
			if ($info['data']['photo3']) {
				$photo3 = $info['data']['photo3']['url'];
				$carousel1['pic3'] = $photo3;
				//删除老图片
				//deloldimg($imglist['pic3']);
				//图片加水印
			//	makeWater($photo3);
			}
			if ($info['data']['photo4']) {
				$photo4 = $info['data']['photo4']['url'];
				$carousel1['pic4'] = $photo4;
				//删除老图片
				//deloldimg($imglist['pic4']);
				//图片加水印
				//makeWater($photo4);
			}
			
		}
		//没有上传图片就不动
		if(isset($_POST['photo1']) && $_POST['photo1'] != ''){
			$carousel1['pic1'] = $_POST['photo1'];
		}
		if(isset($_POST['photo2']) && $_POST['photo2'] != ''){
			$carousel1['pic2'] = $_POST['photo2'];
		}
		if(isset($_POST['photo3']) && $_POST['photo3'] != ''){
			$carousel1['pic3'] = $_POST['photo3'];
		}
		if(isset($_POST['photo4']) && $_POST['photo4'] != ''){
			$carousel1['pic4'] = $_POST['photo4'];
		}
		$data['carousel1'] = json_encode($carousel1, JSON_UNESCAPED_SLASHES);
		
		$flag = M('product')->where($wherekey)->save($data);
		if ($flag !== false) {
			$this->myApiPrint('编辑成功！', 400, '');
		} else {
			$this->myApiPrint('提交失败，数据库错误', 300);
		}
	}

	
	/**
	 * 我的商品2.0  
	 * @param storeid 店铺ID
	 */
	public function product_list() {
		$storeid = I('post.storeid');

		if ($storeid == "") {
			$this->myApiPrint('数据错误！');
		}
		
		$wherekey['b.storeid'] = $storeid;
		$wherekey['b.affiliate_deleted'] = 0;
		//$wherekey['a.manage_status'] = '1';
		//$wherekey['a.status'] = '0';
		//$wherekey['b.manage_status'] = '1';
		//$wherekey['b.status'] = '0';
		
		//获取分页
		$page = intval(I('post.page')) - 1;
		$page = $page>0 ? $page*10 : 0;
		$totalPage = M('view_product b')->where($wherekey)->count();
		$everyPage = '10';
		$pageString = $page.','.$everyPage;
		
		$page1['totalPage'] = floor(($totalPage-1)/$everyPage)+1;
		$page1['everyPage'] = $everyPage;
		
		
		$data = M('view_product b')
			->field('b.id productid, CONCAT(b.img,\''.C('USER_PRODUCTLIST_SIZE').'\') as product_img,b.name product_name,b.totalnum product_totalnum,b.exchangenum product_exchangenum,
		  b.price product_price,b.start_time product_start_time,b.end_time product_end_time,b.status,b.exchangeway, b.manage_status')
			->where($wherekey)
			->order('b.id desc')->limit($pageString)
			->select();

		$i=0;
		while ($data[$i]['productid']!='') {
			$wh1['productid'] = $data[$i]['productid'];
			$wh1['favorite'] = '1';
			
			$da = M('favorite_product')
				->field('favorite')
				->where($wh1)
				->count();
			$data[$i]['product_attention'] = $da;
			
			$i++;
		}
		$data1['page'] = $page1;
		$data1['data'] = $data;
		
		$this->myApiPrint('查询完成！', 400, $data1);
	}
	
	/**
	 * 查看商品详情
	 * Enter description here ...
	 */
	public function productDetails(){
		$wherekey['id'] = I('post.id');            //商品id
		$wherekey['storeid'] =I('post.storeid');  //店铺id
		
		$details = M('product')->where($wherekey)->find();
		if ($details=="") {
			$this->myApiPrint('此商品不存在！');
		}
		
		//转换图片
		$photos = json_decode($details['carousel1'], true);
		$banners = '';
		foreach ($photos as $k=>$v){
			if(trim($v) != ''){
				$banners .= $v.',';
			}
		}
		if($banners != ''){
			$banners = substr($banners, 0, strlen($banners)-1);
		}
		$details['carousel1'] = $banners;
		//获取分类
		$producttype = M()->query('select f.fm_name, s.sm_name from zc_second_menu as s left join zc_first_menu as f on s.fm_id = f.fm_id where s.sm_id='.$details['typeid']);
		$details['producttype'] = $producttype[0]['fm_name'].' '.$producttype[0]['sm_name'];
		$this->myApiPrint('查询完成！', 400, $details);
	}
	
	
	/**
	 * 商品上架
	 * Enter description here ...
	 */
	public function product_onsale(){
		$where['id'] = I('post.id');      //商品id
		$where['storeid'] = I('post.storeid');  //店铺id
		//验证参数
		foreach ($where as $k=>$v) {
			if (empty($v)) {
				$this->myApiPrint('数据错误！');
				exit;
			}
		}
		//验证商品
		$info = M('product')->where($where)->find();
		if($info){
			if($info['manage_status'] !=1 ){
				$this->myApiPrint('商品正在审核！');exit;
			}
			//更新状态
			M('product')->where($where)->save(array('status'=>0));
			$this->myApiPrint('处理成功！', 400, null);
		}else{
			$this->myApiPrint('没有找到商品！');
			exit;
		}
	}
	
	/**
	 * 商品下架
	 * Enter description here ...
	 */
	public function product_offsale(){
		$where['id'] = I('post.id');      //商品id
		$where['storeid'] =  I('post.storeid');  //店铺id
		//验证参数
		foreach ($where as $k=>$v) {
			if (empty($v)) {
				$this->myApiPrint('数据错误！');
				exit;
			}
		}
		//验证商品
		$info = M('product')->where($where)->find();
		if($info){
			if($info['manage_status'] !=1 ){
				$this->myApiPrint('商品正在审核！');exit;
			}
			//更新状态
			M('product')->where($where)->save(array('status'=>1));
			$this->myApiPrint('处理成功！', 400, null);
		}else{
			$this->myApiPrint('没有找到商品！');
			exit;
		}
	}
	
	
	/**
	 * 发布商品记录
	 * @param storeid 店铺ID
	 */
	public function product_record() {
		$storeid = I('post.storeid');

		if ($storeid == "") {
			$this->myApiPrint('数据错误！');
		}
		
		$wherekey['a.id'] = $storeid;
		$wherekey['a.manage_status'] = '1';
		$wherekey['a.status'] = '0';
		$wherekey['b.manage_status'] = '1';
		$wherekey['b.status'] = '0';
		$data = M('store a')
			->field('b.id productid,b.img product_img,b.name product_name,a.score store_score,a.attention product_attention,b.totalnum product_totalnum,b.exchangenum product_exchangenum,
		  b.price product_price,b.start_time product_start_time,b.end_time product_end_time,b.status,b.exchangeway, b.is_super')
			->join('zc_product b on b.storeid=a.id')
			->where($wherekey)
			->order('b.id desc')
			->select();

		$i=0;
		while ($data[$i]['productid']!='') {
			$wh1['productid'] = $data[$i]['productid'];
			$wh1['favorite'] = '1';
			
			$da = M('favorite_product')
				->field('favorite')
				->where($wh1)
				->count();
			$data[$i]['product_attention'] = $da;
			
			$i++;
		}
		
		$this->myApiPrint('查询完成！', 400, $data);
	}
	
	
	/**
	 * 店铺评论列表(买单的才评论店铺exchangeway=2）
	 * @param storeid 店铺id
	 */
	public function storecomment_list() {
		$storeid = I('post.storeid');
		
		if (empty($storeid)) {
			$this->myApiPrint('数据错误！');
		}
		
		$page = intval(I('post.page')) - 1;
		$page = $page>0 ? $page*10 : 0;
		
		$wherekey = ' a.storeid='.$storeid.' and a.`exchangeway` = 2 ';
		$totalPage = M('orders a')->join('zc_member b')->where($wherekey)->count();
		$everyPage = '10';
		$pageString = $page.','.$everyPage;
		
		$page1['totalPage'] = floor(($totalPage-1)/10)+1;
		$page1['everyPage'] = $everyPage;
		
		$comment = M('orders a')
			->field('a.id,a.productid,a.content,a.uid,a.score,a.comment_time,b.nickname,b.img')
			->join('zc_member b')
			->where($wherekey)
			->limit($pageString)
			->select();
		
		if ($comment=="") {
			$this->myApiPrint('查不到数据！',400,$data1);
		}
		foreach ($comment as $k=>$v){
			$comment[$k]['nickname'] = mb_substr($v['nickname'], 0, 1, 'utf-8').'**';
		}
		$data1['page'] = $page1;
		$data1['data'] = $comment;
		
		$this->myApiPrint('查询成功！',400,$data1);
	}
	
	/**
	 * 店铺设置页面
	 * @param storeid 店铺ID
	 */
	public function myStoreSet() {
		$storeid = I('post.storeid');

		if ($storeid == "") {
			$this->myApiPrint('数据错误！');
		}
		
		$wherekey['a.id'] = $storeid;
		$wherekey['a.manage_status'] = '1';
		$wherekey['a.status'] = '0';
		$data = M('store a')
			->field('b.loginname member_phone,a.id, a.province,a.city, a.country, a.person_consumption,a.start_time store_start_time,a.end_time store_end_time,a.service store_service,a.phone store_phone,a.content store_content, a.store_name, a.address, a.evn_img, a.longitude, a.latitude')
			->join('zc_member b on b.id=a.uid')
			->where($wherekey)
			->find();
		if (empty($data)) {
			$this->myApiPrint('会员或店铺不存在！');
		}
		
		//店铺介绍富文本转换
		$data['store_content'] = html_entity_decode($data['store_content']);

		//转换图片
		if(trim($data['evn_img']) != '' && strlen($data['evn_img']) > 10){
			$photos = json_decode($data['evn_img'], true);
			$banners = '';
			foreach ($photos as $k=>$v){
				if(trim($v) != ''){
					$banners .= $v.',';
				}
			}
			$banners = substr($banners, 0, strlen($banners)-1);
			$data['evn_img'] = $banners;
		}else{
			$data['evn_img'] = '';
		}
		
		//$data['member_token'] = myDes_encode($strToken, $phone);
		$this->myApiPrint('查询完成！', 400, $data);
	}
	
	/**
	 * 保存店铺设置
	 * @param
	 */
	public function myStoreSet_save()
	{
		$uid = I('post.uid');
		$storeid = I('post.storeid');
		
		$consumption = I('post.person_consumption');
		$start_time = I('post.store_start_time');
		$end_time = I('post.store_end_time');
		$service = I('post.store_service');
		$phone = I('post.store_phone');
		$province = I('post.province');
		$city = I('post.city');
		$country = I('post.country');
		$content = I('post.content');
		$store_name = I('post.store_name');        //店铺名称
		$store_address = I('post.store_address');  //店铺地址
		$latitude = I('post.latitude');  //店铺纬度
		$longitude = I('post.longitude');  //店铺经度

		$delimgs = I('post.delimgs');  //删除的图片逗号分割

		$wherekey['id'] = $storeid;
		$wherekey['uid'] = $uid;
		
		$data['province'] = $province;
		$data['city'] = $city;
		$data['country'] = $country;
		$data['phone'] = $phone;
		$data['person_consumption'] = $consumption;
		if(strlen($start_time) == 10){
			$data['start_time'] = intval($start_time);
			$data['end_time'] = intval($end_time);
		}else{
			$data['start_time'] = strtotime('2017-01-01')+$start_time;
			$data['end_time'] = strtotime('2017-01-01')+$end_time;
		}
		$data['content'] = $content;
		//新增
		$data['latitude'] = $latitude;
		$data['longitude'] = $longitude;
		$data['address'] = $store_address;
		$data['store_name'] = $store_name;
		
		if($phone == '' || $store_name == '' || $content==''){
			$this->myApiPrint('数据错误！');
		}
		/*
		foreach ($data as $k=>$v) {
			if (empty($v)) {
				$this->myApiPrint('数据错误！');
				exit;
			}
		}*/
		
		$data['service'] = $service;
		//处理图片
		$upload_config = array (
			'file' => 'multi',
			'exts' => array('jpg','png','gif','jpeg'),
			'path' => 'business_licence/'. date('Ymd')
		);
		$Upload = new \Common\Controller\UploadController($upload_config);
		$info = $Upload->upload();
		
		//加载之前上传的图片信息
		$details = M('store')->where($wherekey)->find();
		$imglist = json_decode($details['evn_img'], true);
		if (!empty($info['error'])) {
			
		} else {
			
			$carousel1 = array();
			//第一张图片
			if ($info['data']['photo1']) {
				$photo1 = $info['data']['photo1']['url'];
				$carousel1['pic1'] = $photo1;
				//删除老图片
				//deloldimg($imglist['pic1']);
				//图片加水印
				//makeWater($photo1);
			}
			if ($info['data']['photo2']) {
				$photo2 = $info['data']['photo2']['url'];
				$carousel1['pic2'] = $photo2;
				//删除老图片
				//deloldimg($imglist['pic2']);
				//图片加水印
				//makeWater($photo2);
			}
			if ($info['data']['photo3']) {
				$photo3 = $info['data']['photo3']['url'];
				$carousel1['pic3'] = $photo3;
				//删除老图片
				//deloldimg($imglist['pic3']);
				//图片加水印
			//	makeWater($photo3);
			}
			if ($info['data']['photo4']) {
				$photo4 = $info['data']['photo4']['url'];
				$carousel1['pic4'] = $photo4;
				//删除老图片
				//deloldimg($imglist['pic4']);
				//图片加水印
				//makeWater($photo4);
			}
			
		}
		//没有上传图片就不动
		if(isset($_POST['photo1']) && $_POST['photo1'] != ''){
			$carousel1['pic1'] = $_POST['photo1'];
		}
		if(isset($_POST['photo2']) && $_POST['photo2'] != ''){
			$carousel1['pic2'] = $_POST['photo2'];
		}
		if(isset($_POST['photo3']) && $_POST['photo3'] != ''){
			$carousel1['pic3'] = $_POST['photo3'];
		}
		if(isset($_POST['photo4']) && $_POST['photo4'] != ''){
			$carousel1['pic4'] = $_POST['photo4'];
		}
		$data['evn_img'] = json_encode($carousel1, JSON_UNESCAPED_SLASHES);
		
		M('store')->where($wherekey)->save($data);
		
		$this->myApiPrint('设置完成！', 400, $data);
	}
	
	/**
	 * 商铺申请函数
	 *
	 * 申请用户必须为创客，要能实现图片上传
	 *
	 * @param uid  店铺所有者id
	 * @param store_name 商户名称
	 * @param phone 商业电话（营业电话）
	 * @param province 省
	 * @param city 市
	 * @param country 县
	 * @param address 店铺地址
	 * @param bus_lice_num 统一社会信用代码（营业执照号码）
	 * @param content 店铺简介
	 * @param fid 一级分类
	 *
	 * @param longitude 经度
	 * @param latitude 纬度
	 *
	 * @param img 执照图片参数（jpg,jepg,png格式）
	 * @param back 店铺背景参数（jpg,jepg,png格式）
	 */
	public function shop_apply() {
		//记录session用于重复请求数据
		if($this->app_common_data['uid'] != ''){
	        $unique_sessionkey = CONTROLLER_NAME.'_'.ACTION_NAME.'_'.$this->app_common_data['uid'];
	        if(session($unique_sessionkey) != ''){
	        	$this->myApiPrint('正在处理，请等待！', 300);
	        }else{
	        	session($unique_sessionkey, time());
	        }
		}
		
		$param_arr['date_created'] = time();
		$param_arr['uid'] = $_POST['uid'];
        $param_arr['store_name'] = urldecode($_POST['store_name']);
        $param_arr['phone'] = $_POST['phone'];
        $param_arr['province'] = urldecode($_POST['province']);
        $param_arr['city'] = urldecode($_POST['city']);
        $param_arr['country'] = urldecode($_POST['country']);
        $param_arr['address'] = urldecode($_POST['address']);
        $param_arr['bus_lice_num'] = $_POST['bus_lice_num'];
        $param_arr['content'] = urldecode($_POST['content']);
        $param_arr['retel'] = $_POST['reusername']; //推荐人用户名/id
        $param_arr['discount'] = $_POST['discount']; //折扣
        $param_arr['fid'] = $_POST['fid']; //一级id
        $param_arr['sid'] = $_POST['sid']; //二级id
		$param_arr['start_time'] = strtotime('2017-01-01 08:00');
        $param_arr['end_time'] = strtotime('2017-01-01 22:00');
        $param_arr['longitude'] = $_POST['longitude'] ? $_POST['longitude'] : 1;
        $param_arr['latitude'] = $_POST['latitude'] ? $_POST['latitude'] : 1;
        
        //验空
        foreach ($param_arr as $k => $v) {
            if (empty($v)) {
                $this->myApiPrint($k . ' 为空', 300);
            }
        }
        //验证折扣
        if (intval($_POST['discount']) < 5) {
        	$this->myApiPrint('平台的店铺折扣最低5折', 300);
        }
        if (intval($param_arr['discount']) < 1 || intval($param_arr['discount']) >= 10) {
        	$this->myApiPrint('请正确填写折扣', 300);
        }
        if (round(100-($param_arr['discount']*10)) < 1) {
        	$this->myApiPrint('请正确填写折扣', 300);
        }
        //验证推荐店铺手机号
        $member = M('member')->find($param_arr['uid']);
		$remember = M('member')->where('loginname = \''.$param_arr['retel'].'\'')->find();
        if (!$member) {
        	$this->myApiPrint('账号不存在', 300);
        }
		if (!$remember) {
        	$this->myApiPrint('推荐人账号不存在', 300);
        }
        if ($member['username'] == trim($param_arr['retel'])) {
        	$this->myApiPrint('推荐人账号不能为自己的账号', 300);
        }
        //如果申请店铺的会员已是服务中心或区域合伙人，则提示当前身份不能申请商家
        if ($member['role'] > 0) {
        	$this->myApiPrint('当前身份不能申请商家', 300);
        }

		M()->startTrans();
        $store = M('store');
		
        $storeentity = $store->lock(true)->where('uid=' . $param_arr['uid'])->order('id desc')->find();
        if ($storeentity['manage_status'] == 1) {
        	 $this->myApiPrint('该用户已是商家', 300);
        } elseif ($storeentity['manage_status'] == 0 && $storeentity['id'] > 1) {
        	  $this->myApiPrint('该用户申请的商家正在审核中', 300);
        } elseif($storeentity['manage_status'] == 10) {
        	 $this->myApiPrint('您的店铺已申请注销，正在审核中', 300);
        }
        
        //处理图片
		$upload_config = array (
			'file' => 'multi',
			'exts' => array('jpg','png','gif','jpeg'),
			'path' => 'business_licence/'. date('Ymd')
		);
		$Upload = new \Common\Controller\UploadController($upload_config);
		$info = $Upload->upload();
		if (!empty($info['error'])) {
            $this->myApiPrint('图片上传失败', 300, $Upload->getError());
        } else {
            //执照
            if($info['data']['img']) {
                $img_save_path = $info['data']['img']['url'];
				$param_arr ['lice_img']= $img_save_path;
            } else {
                $this->myApiPrint('必须上传执照图片');
            }
            //logo    
            if ($info['data']['back']) {
                $img_save_path = $info['data']['back']['url']; //图片保存的相对路径
                $param_arr['store_img'] = $img_save_path; //去掉相对路径前的 . 号
            }
            
            $carousel1 = array();
       		if ($info['data']['photo1']) {
				$photo1 = $info['data']['photo1']['url'];
				$carousel1['pic1'] = $photo1;
			}else{
		        if(isset($_POST['photo1']) && $_POST['photo1'] != ''){
					$carousel1['pic1'] = $_POST['photo1'];
				}
			}
			if($carousel1['pic1'] == ''){
				$this->myApiPrint('必须上传环境图片');
			}
        	if ($info['data']['photo2']) {
				$photo1 = $info['data']['photo2']['url'];
				$carousel1['pic2'] = $photo1;
			}else{
		        if(isset($_POST['photo2']) && $_POST['photo2'] != ''){
					$carousel1['pic2'] = $_POST['photo2'];
				}
			}
       		if ($info['data']['photo3']) {
				$photo3 = $info['data']['photo3']['url'];
				$carousel1['pic3'] = $photo3;
			}else{
		        if(isset($_POST['photo3']) && $_POST['photo3'] != ''){
					$carousel1['pic3'] = $_POST['photo3'];
				}
			}
       		if ($info['data']['photo4']) {
				$photo4 = $info['data']['photo4']['url'];
				$carousel1['pic4'] = $photo4;
			}else{
		        if(isset($_POST['photo4']) && $_POST['photo4'] != ''){
					$carousel1['pic4'] = $_POST['photo4'];
				}
			}
			$param_arr['evn_img'] = json_encode($carousel1, JSON_UNESCAPED_SLASHES);
			$param_arr['add_tag'] = date('YmdHi');
            //判断商家是否有申请记录
            if ($storeentity['manage_status'] == 2) {
            	$id = $storeentity['id'];
            }
       	 	if ($id) {
       	 		$param_arr['manage_status']=0;
           	 	$flag = $store->where('id='.$id)->save($param_arr);
        	} else {   
        		$param_arr['date_created'] = time();
           		$flag = BaseModel::InsertIgnoreData('zc_store', $param_arr);
           		$id = $flag;
        	}
            
            if ($flag){
            	//插入折扣
            	$zk['store_id'] = $id;
				$zk['pname'] = $param_arr['store_name'];
				$zk['discount'] = $_POST['discount'];
				$zk['status'] = 0;
				$zk['conditions'] = 100;
				$zk['post_time'] = time();
				$zk['reward'] = round(100-($zk['discount']*10));
				$zk['manage_status'] = 1;
				
	            $hasinfo = M('preferential_way')->where('store_id='.$id)->find();
				if ($hasinfo) {
					$flag2 = M('preferential_way')->where('store_id='.$id)->save($zk);
				} else {
					$flag2 = M('preferential_way')->where('store_id='.$id)->add($zk);
				}
				if ($flag2 !== false && $flag !== flase) {
					M()->commit();
                	$this->myApiPrint('提交成功，请等待审核', 400);
				} else {
					M()->rollback();
					$this->myApiPrint('提交失败，申请失败', 300);
				}
            } else {
            	M()->rollback();
                $this->myApiPrint('提交失败，请稍后重试', 300);
            }
        }
	}
	
	
	
    public function android_shop_apply() {
        //记录session用于重复请求数据
        if($this->app_common_data['uid'] != ''){
            $unique_sessionkey = CONTROLLER_NAME.'_'.ACTION_NAME.'_'.$this->app_common_data['uid'];
            if(session($unique_sessionkey) != ''){
                $this->myApiPrint('正在处理，请等待！', 300);
            }else{
                session($unique_sessionkey, time());
            }
        }
        
        $param_arr['date_created'] = time();
        $param_arr['uid'] = $_POST['uid'];
        $param_arr['store_name'] = $_POST['store_name'];
        $param_arr['phone'] = $_POST['phone'];
        $param_arr['province'] = $_POST['province'];
        $param_arr['city'] = $_POST['city'];
        $param_arr['country'] = $_POST['country'];
        $param_arr['address'] = $_POST['address'];
        $param_arr['bus_lice_num'] = $_POST['bus_lice_num'];
        $param_arr['content'] = $_POST['content'];
        $param_arr['retel'] = $_POST['reusername']; //推荐人手机号
        $param_arr['discount'] = $_POST['discount']; //折扣
        $param_arr['fid'] = $_POST['fid']; //一级id
        $param_arr['sid'] = $_POST['sid']; //二级id
        $param_arr['start_time'] = strtotime('2017-01-01 08:00');
        $param_arr['end_time'] = strtotime('2017-01-01 22:00');
        $param_arr['retel'] = mb_substr($param_arr['retel'], 0, 11, 'utf-8');
        $param_arr['longitude'] = $_POST['longitude'] ? $_POST['longitude'] : 1;
        $param_arr['latitude'] = $_POST['latitude'] ? $_POST['latitude'] : 1;
        
        //验空
        foreach ($param_arr as $k => $v) {
            if (empty($v)) {
                $this->myApiPrint($k . ' 为空', 300);
            }
        }
        //验证折扣
        if (intval($_POST['discount']) < 5) {
            $this->myApiPrint('平台的店铺折扣最低5折', 300);
        }
        if (intval($param_arr['discount']) < 1 || intval($param_arr['discount']) >= 10) {
            $this->myApiPrint('请正确填写折扣', 300);
        }
        if (round(100-($param_arr['discount']*10)) < 1) {
            $this->myApiPrint('请正确填写折扣', 300);
        }
        //验证推荐店铺手机号
        $member = M('member')->find($param_arr['uid']);
        $remember = M('member')->where('loginname = \''.$param_arr['retel'].'\'')->find();
        if (!$member) {
            $this->myApiPrint('账号不存在', 300);
        }
        if (!$remember) {
            $this->myApiPrint('推荐人账号不存在', 300);
        }
        if ($member['username'] == trim($param_arr['retel'])) {
            $this->myApiPrint('推荐人手机号不能为自己的账号', 300);
        }
        //如果申请店铺的会员已是服务中心或区域合伙人，则提示当前身份不能申请商家
        if ($member['role'] > 2) {
            $this->myApiPrint('当前身份不能申请商家', 300);
        }

        M()->startTrans();
        $store = M('store');
        
        $storeentity = $store->lock(true)->where('uid=' . $param_arr['uid'])->order('id desc')->find();
        if ($storeentity['manage_status'] == 1) {
             $this->myApiPrint('该用户已是商家', 300);
        } elseif ($storeentity['manage_status'] == 0 && $storeentity['id'] > 1) {
              $this->myApiPrint('该用户申请的商家正在审核中', 300);
        } elseif($storeentity['manage_status'] == 10) {
             $this->myApiPrint('您的店铺已申请注销，正在审核中', 300);
        }
        
        //处理图片
        $param_arr ['lice_img'] = I('post.lice_img');
        $param_arr ['store_img'] = I('post.logo');
        $carousel1['pic1'] = I('post.photo1');
        if(I('post.photo2') != ''){
            $carousel1['pic2'] = I('post.photo2');
        }
        if(I('post.photo3') != ''){
            $carousel1['pic3'] = I('post.photo3');
        }
        if(I('post.photo4') != ''){
            $carousel1['pic4'] = I('post.photo4');
        }
        if(empty($param_arr ['store_img']) || $param_arr ['store_img'] == ''){
            $this->myApiPrint('请上传logo', 300);
        }
        if(empty($param_arr ['lice_img']) || $param_arr ['lice_img'] == ''){
            $this->myApiPrint('请上传营业执照图片', 300);
        }
        if(empty($carousel1 ['pic1']) || $carousel1 ['pic1'] == ''){
            $this->myApiPrint('请上传环境图片', 300);
        }
        
        
        $param_arr['evn_img'] = json_encode($carousel1, JSON_UNESCAPED_SLASHES);
        $param_arr['add_tag'] = date('YmdHi');
        //判断商家是否有申请记录
        if ($storeentity['manage_status'] == 2) {
            $id = $storeentity['id'];
        }
        if ($id) {
            $param_arr['manage_status']=0;
            $flag = $store->where('id='.$id)->save($param_arr);
        } else {   
            $param_arr['date_created'] = time();
            $flag = BaseModel::InsertIgnoreData('zc_store', $param_arr);
            $id = $flag;
        }
        
        if ($flag){
            //插入折扣
            $zk['store_id'] = $id;
            $zk['pname'] = $param_arr['store_name'];
            $zk['discount'] = $_POST['discount'];
            $zk['status'] = 0;
            $zk['conditions'] = 100;
            $zk['post_time'] = time();
            $zk['reward'] = round(100-($zk['discount']*10));
            $zk['manage_status'] = 1;
            
            $hasinfo = M('preferential_way')->where('store_id='.$id)->find();
            if ($hasinfo) {
                $flag2 = M('preferential_way')->where('store_id='.$id)->save($zk);
            } else {
                $flag2 = M('preferential_way')->where('store_id='.$id)->add($zk);
            }
            if ($flag2 !== false && $flag !== flase) {
                M()->commit();
                $this->myApiPrint('提交成功，请等待审核', 400);
            } else {
                M()->rollback();
                $this->myApiPrint('提交失败，申请失败', 300);
            }
        } else {
            M()->rollback();
            $this->myApiPrint('提交失败，请稍后重试', 300);
        }
    }
	
	
	//注销店铺
	public function shop_out(){
		$storeid = I('post.storeid');
		$uid = I('post.uid');
		$beizhu = I('post.beizhu');
		
		$store = M('store')->where('id='.$storeid.' and uid='.$uid)->find();
		if($store){
			M('store')->where('id='.$storeid.' and uid='.$uid)->save(array('manage_status'=>10, 'beizhu'=>$beizhu, 'cancel_time'=>time()));
			$this->myApiPrint('提交成功，请等待审核', 400);
		}else{
		    $this->myApiPrint('没有找到数据', 300);
		}
	}
	
	
	
	/**
	 * 店铺支付获取店铺信息及会员现金余额
	 * @param storeid 店铺ID
	 * @param uid 会员ID
	 */
	public function get_shop_info_n_cash() {
		$storeid = $_POST['storeid'];
		$uid = I('post.uid');
	
		$store_arr = M('store')->where(array('id' => $storeid , 'manage_status' => 1))->find();
		if (empty($store_arr)) {
			$this->myApiPrint('店铺获取信息失败', 300);
		}
		
		$member_info = M('member')->where(array('id' => $uid))->find();
		if (!$member_info) {
			$this->myApiPrint('会员信息获取失败', 300);
		}
		$data['store_img'] = $store_arr['store_img'];
		$data['address'] = $store_arr['address'];
		$data['store_name'] = $store_arr['store_name'];
		
		$pw = M('preferential_way')->where('store_id= '.$storeid)->find();
		$pm = M('g_parameter', null)->find(1);
		$data['conditions'] = $pw['conditions'];
		$data['reward'] = $pw['reward'];
		$data['store_reward'] = '店内消费'.$data['conditions'].'元可使用'.$data['reward'].'元丰谷宝抵扣现金';
		
		//查询余额
		$am = new AccountModel();
		$balance = $am->getAccount($uid, $am->get3BalanceFields());
        $data['cash'] = $balance['account_cash_balance'];
        $data['goldcoin'] = $balance['account_goldcoin_balance'];
        $data['colorcoin'] = $balance['account_colorcoin_balance'];
		
		//是否可用商超券
		$data['use_colorcoin'] = intval($store_arr['store_supermarket']).'';
		
		
		//判断会员类型买单的时候是否显示‘责任消费’的字段
		//$consume = M('dutyconsume')->where('user_id = '.$uid)->find();
		//if($consume['dutyconsume_need_amount']-$consume['dutyconsume_complete_amount']>0){
			//$data['allowanceuser'] = 1;
		//}else{
			$data['allowanceuser'] = 0;
		//}
		
		//商超提示
		$data['super_store_intro'] = C('PARAMETER_CONFIG.COLORCOIN_PAY_INSTRUCTION');
		$this->myApiPrint('获取成功', 400, $data);
	}
	
}
?>