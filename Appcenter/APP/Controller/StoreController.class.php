<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 店铺申请状态,创客分享二维码H5注册接口
// +----------------------------------------------------------------------
namespace APP\Controller;
use Common\Controller\ApiController;
use V4\Model\OrderModel;
use V4\Model\PaymentMethod;
use V4\Model\Currency;
use V4\Model\AccountRecordModel;
use V4\Model\CurrencyAction;
use V4\Model\RewardModel;

class StoreController extends ApiController {
	
	/**
	 * 店铺申请状态
	 * @param uid 会员ID
	 */
	public function get_shop_apply_status() {
		$uid = I('post.uid');
		 
		$store = M('store');
		$where['uid'] = $uid;
		$store_arr = $store->field('store_img,address,store_name,message')->where('uid='.$uid.' and manage_status != 11')->order('id desc')->find();
		if (empty($store_arr)) {
			$this->myApiPrint('此会员没有审请店铺信息！', 300);
		}
		 
		$sql = 'find_in_set(id,(select repath from zc_member where id='.$uid.')) and (level=4 or level=5)';
		$company = M('member')
			->field('nickname truename,loginname telphone,phone,province,city,country,adress address')
			->where($sql)
			//->order('id desc')
			->order('relevel desc')
			->find();
		
		$data['store_img'] = $store_arr['store_img'];
		$data['address'] = $store_arr['address'];
		$data['store_name'] = $store_arr['store_name'];
		$data['message'] = empty($store_arr['message']) ? '' : $store_arr['message'];
		$data['truename'] =$company['truename'];
		$data['telphone'] = empty($company['phone']) ? $company['telphone'] : $company['phone'];
		$data['phone'] = C('KEFU_PHONE');
		$data['company_address'] = $company['province'].$company['city'].$company['country'].$company['adress'];
		
		$this->myApiPrint('查询成功！', 400,$data);
	}
	
	/**
	 * 创客分享二维码H5注册
	 * @param uid 会员ID
	 */
	public function get_register_info(){
		$uid = I('post.uid');
		
		if (empty($uid)) {
			$this->myApiPrint('数据错误！', 300);
		}
		
		$member = M('Member')->where('id='.intval($uid))->find();
		if (empty($member)) {
			$this->myApiPrint('此会员不存在！', 300);
		}
		
		$url = C('LOCAL_HOST').U('H5/Index/index/recommer/'.base64_encode($member['loginname']));
		$data['url'] = $url;
		$this->myApiPrint('生成成功', 400, $data);
	}
	
	/**
	 * 商家-众彩买单
	 * Enter description here ...
	 */
	public function order_maidan(){
		$storeid = intval(I('post.id'));
	    $pn = I('post.page');
        if($pn < 1){
            $pn = 1;
        }
        $ps = '10';
        
        $post = verify_order_maidan($storeid);
        

		$wherekey['storeid']=$storeid ;
		$wherekey['exchangeway']=2;
		
		$countsql = 'select SUM(o.goldcoin) as goldcoin, SUM(floor(((`o`.`goldcoin` / `c`.`conditions`) * `c`.`reward`)) * `o`.`producttype`) as gife from zc_orders as o '
			.' left join zc_member as b on b.id = o.uid '
			.' left join zc_preferential_way as c on c.store_id= o.storeid '
			.' left join zc_product as d on d.id = o.productid '
			.' where o.storeid='.$storeid .' and o.exchangeway = 2';
		$res = M()->query($countsql); 
		$coin = $res[0]['goldcoin'];  //总额
		$gife = $res[0]['gife'];     //增币总额
        $return['totalcoin'] = intval($coin);
        $return['totalgife'] = intval($gife);
        
        
        //加载分页数据
        $om = new OrderModel();
        $list = $om->getStoreOrderList($storeid, $pn, $ps);
		
		$return['page'] = $list['page'];
		$return['data'] = $list['data'];
		$return['totalrecord'] = $list['count'];
		$return['count0'] = 0;
		$return['count1'] = 0;
		$return['count2'] = 0;
		$return['count3'] = 0;
		$return['count4'] = 0;
		$this->myApiPrint('查询成功！', 400, $return);
	}

	/**
	 * 买单详情
	 * Enter description here ...
	 */
	public function order_maidaninfo(){
		$orderid = intval(I('post.orderid'));
		
		$info = M()->query('SELECT s.store_name, s.store_img, o.amount goldcoin, o.order_number, o.time, m.loginname, m.img, o.order_status as `status`, o.id, o.comment from zc_orders as o ' 
					.' left join zc_store as s on s.id = o.storeid '
					.' left join zc_member as m on m.id = o.uid '
					.' where o.exchangeway=2 and o.id='.$orderid);
		if($info){
			//对空的值进行处理
			foreach ($info[0] as $k=>$v){
				if(trim($v) == ''){
					$info[0][$k]='';
				}
			}
			$info[0]['img'] = $info[0]['img'].c('USER_ORDERINFO_SIZE');
			$info[0]['store_img'] = $info[0]['store_img'].c('USER_ORDERINFO_SIZE');
			$this->myApiPrint('查询成功！', 400, $info[0]);
		}else{
			$this->myApiPrint('查询失败！');
		}
	}
	
	/**
	 * 修改头像
	 * Enter description here ...
	 */
	public function modify_header(){
		$uid = I('post.uid');
		
		//处理图片
		$upload_config = array (
			'file' => $_FILES['store_img'],
			'exts' => array('jpg','png','gif','jpeg'),
			'path' => 'store/'. date('Ymd')
		);
		$Upload = new \Common\Controller\UploadController($upload_config);
		$info = $Upload->upload();
		if (!empty($info['error'])) {
			$this->myApiPrint('请重新上传图片！',300,(object)$result);
		} else {
			$data['store_img'] = $info['data']['url'];
		}
		$res = M('store')->where('uid='.$uid)->save($data);
		if($res !== false){
			$this->myApiPrint('更新成功！', 400, '');
		}else{
			$this->myApiPrint('更新失败！');
		}
	}
	
	
	/**
	 * 输入兑换码兑换订单
	 * Enter description here ...
	 */
	public function checkno(){
		$id = intval($_POST['id']); //订单id
		$storeid = intval($_POST['storeid']); //店铺id
		$cknum = $_POST['exchangeno']; //兑换码
		
		//1.验证参数
		$post = verify_redeem_code($id, $storeid, $cknum);
		$amount = $post['order']['amount'];
		
        $rm = new RewardModel();
        $arm = new AccountRecordModel();
        
        if($post['order']['is_super'] == 0){
            //2.计算利润
            $profitsData = $rm->getProfitsByOrder($amount, $post['pw']['reward']);
            //3.计算赠送商超券
            $rewardData = $rm->getColorCoinByMoney($post['buyer'], $post['seller'], $profitsData['profits'], $post['store']);
            
            $action = CurrencyAction::CashGoldCoinConsumeBackToMerchant;
        }else{
            //2.重新计算利润
            $profitsData = $rm->getProfitsBySpecialOrder($amount, C('PARAMETER_CONFIG.COLORCOIN_PAY_BAI'));
            
            $action = CurrencyAction::CashColorCoinConsumeBackToMerchant;
        }
        M()->startTrans();
		//1.查询订单
// 	    $where['id'] = $id;
// 	    $where['storeid'] = $storeid;
// 	    $where['chknum'] = $cknum;
// 	    $where['order_status'] = array('neq', 4);
// 	    $order = M('orders')->lock(true)->where($where)->find();
// 	    if (!$order) {
// 	        $this->myApiPrint('订单不存在，兑换失败！');
// 	    }
        
        //5.更新订单已完成状态
		$res1 = M('orders')->where('id='.$id)->save(array('order_status'=>4));
        //6.返现给商家
        $res2 = $arm->add($post['seller']['id'], Currency::Cash, $action, $profitsData['return_cash'], $arm->getRecordAttach($post['buyer']['id'],$post['buyer']['nickname'],$post['buyer']['img'], $post['order']['order_number']), '销售商品['.$post['order']['productname'].']返现');

        $res4 = true;
        //7.计算平台利润，公让宝支付记录平台利润和收益
        if($post['order']['is_super'] == 0){
	        $om = new OrderModel();
	        $res4 = $om->sharebonus($post['buyer']['id'], $profitsData['profits'], $post['seller']['id'], $order['order_number'], Currency::GoldCoin);
        }
                
        if($res1 !== false && $res2 !== false  && $res4 !== false){
            M()->commit();
            
            //推送消息
            $jpush = new \APP\Controller\SysController();
            $jpush->pushafterpay($post['seller']['id'], $post['buyer']['id'], date('Y-m-d H:i:s', time()), $profitsData['return_cash']);
            
            $this->myApiPrint('兑换成功', 400);
        }else{
            M()->rollback();
            $this->myApiPrint('兑换失败');
        }
        
	}
	
	
	
	/**
	 * 获取协议
	 * Enter description here ...
	 */
	public function getaggrements(){
		$id = $_POST['id'];
		$field = '';
		if($id == '1'){  //收益说明
			$field = 'incometxt';
		}elseif($id == '2'){  //创客协议
			$field = 'makertxt';
		}elseif($id == '3'){  //用户协议
			$field = 'usertxt';
		}elseif($id == '4'){  //商家升级协议
			$field = 'storeuptxt';
		}elseif($id == '5'){  //授权书
            $field = 'warranttxt';
        }elseif($id == '6'){  //巴蜀公益
            $field = 'benefittxt';
        }elseif($id == '7'){  //巴蜀公益url
            $field = 'benefittxt_url';
        }elseif($id == '8'){  //公司文化
            $field = 'culturetxt';
        }elseif($id == '9'){  //隐私政策
            $field = 'privacytxt';
        }elseif($id == '10'){  //隐私政策地址
            $field = 'privacytxt_url';
        }else{
			$this->myApiPrint('What are you doing?');
		}
		$msg = M('agreement')->where('id=1')->getField($field);

		$this->myApiPrint('查询成功', 400, htmlspecialchars_decode(html_entity_decode($msg)));
		
	}
	
}
?>