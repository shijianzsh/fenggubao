<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 摇一摇相关接口
// +----------------------------------------------------------------------
namespace APP\Controller;
use Common\Controller\ApiController;
use V4\Model\OrderModel;
use V4\Model\PaymentMethod;
use V4\Model\Currency;
use V4\Model\AccountRecordModel;
use V4\Model\CurrencyAction;
use V4\Model\MemberModel;
use V4\Model\AccountModel;

class ShakeController extends ApiController {
	
	private $goldcoin_name;
	
	public function __construct() {
		parent::__construct();
		
		$this->goldcoin_name = C('Coin_Name');  	
	}
	
	/**
	 * 摇一摇页面
	 * @param uid 会员ID
	 */
	public function index() {
		$uid = intval(I('post.uid'));
		
		if ($uid==0) {
			$this->myApiPrint('会员不存在！');
		}
		
		$params = M('parameter','g_')->field('b7,b8,b9')->find();
		//今日剩余次数
		$where3 = 'user_id='.$uid." and FROM_UNIXTIME(log_addtime,'%Y%m%d')=FROM_UNIXTIME(unix_timestamp(),'%Y%m%d')";
	    $todayShakeTimes = M('shake_log')->where($where3)->count();
	    //获取会员今日分享增加的次数
	    $todayshare = M('shake_addtimes')->where('uid='.$uid.' and useday = \''.date('Y-m-d').'\'')->find();
	    $return['residue'] = strval($params['b7'] - $todayShakeTimes + intval($todayshare['times']));
		if($return['residue'] < 1){
			$return['residue'] = 0;
		}
		//今日所得金币
        $w1['user_id'] = $uid;
        $w1['_string'] = "FROM_UNIXTIME(records_addtime,'%Y%m%d') = FROM_UNIXTIME(UNIX_TIMESTAMP(),'%Y%m%d')";
    	$todayshake = M('shake_records')->field('sum(records_cash) as records_cash, sum(records_credits) as records_credits')->where($w1)->find();
        if($todayshake['records_cash']){
            $return['today_cash'] = sprintf('%.2f', $todayshake['records_cash']);
            $return['today_points'] = sprintf('%.2f', $todayshake['records_credits']);
        }else{
        	$return['today_cash'] = '0.00';
        	$return['today_points'] = '0.00';
        }
        
        //余额
        $am = new AccountModel();
        $return['cash'] = $am->getCashBalance($uid);
        $return['points'] = $am->getBalance($uid, Currency::Credits);
		
		$this->myApiPrint('查询成功！',400,$return);
	}
	
	/**
	 * 增加摇一摇次数
	 * 每天摇中同一商家的次数：b10，
	 * 每次分享朋友圈后可增加摇一摇的次数：b11
	 * Enter description here ...
	 */
	public function inctimes(){
		$uid = intval(I('post.uid'));
		if($uid <1){
			$this->myApiPrint('参数错误',300,'');
		}
		//加载参数
		$parameter = M('parameter','g_')->field('b10, b11')->find();
		
		$tt = D('shake_addtimes')->where('uid='.$uid.' and useday = \''.date('Y-m-d').'\'')->find();
		if($tt){
			//每天只增加一次
			$this->myApiPrint('您今日赠送机会已经获取！',400,'');
		}else{
			$vo['uid'] = $uid;
			$vo['times'] = $parameter['b11'];
			$vo['useday'] = date('Y-m-d');
			$vo['addtime'] = time();
			$res = D('shake_addtimes')->add($vo);
			if($res !== false){
				$this->myApiPrint('恭喜你成功获取'.$parameter['b11'].'次摇一摇机会！',400, $vo);
			}else{
				$this->myApiPrint('处理失败',300,'');
			}
		}
	}
	
	/**
	 * 摇一摇动作
	 * @param uid 会员ID
	 */
    public function shake() {
        $uid = intval(I('post.uid'));
        $lng = I('post.lng');
        $lat = I('post.lat');
        
        //1.系统参数b7摇奖次数，b8摇奖命中，b9摇奖不中, b10同一个奖中奖次数,b11分享后增加的次数，shake_n默认不中图片
        $params = M('parameter','g_')->find();
        
        $user = M('member')->where('is_lock=0 and is_blacklist=0')->find($uid);
        //2.验证数据
        //返回数据
        $result = array();
        $result['title'] = C('APP_TITLE');
        $result['content'] = '邀请您免费加入'.C('APP_TITLE').'APP，天天看广告、签到、摇红包赢现金，惊喜不断，分享就可以创业！';
        $url= C('LOCAL_HOST').U('H5/Index/index/recommer/'.base64_encode($user['loginname']));
        $result['h5url'] = $url;
        $result['residue'] = 0;       //剩余摇摇机会次数
        $result['today_cash'] = 0;
        $result['today_points'] = 0;  //今日所得数
        $result['cash'] = 0;
        $result['points'] = 0;        //账户余额
        $result['store_id'] = 0;
        $result['shake_img'] = $params['shake_n'];
        $return = verify_shake($uid, $lng, $lat, $params, $result);
        
        //后台单独设置的人无收益
        $member = verify_user($uid);
        if($member['affiliate_income_disable'] == 1){
        	$this->myApiPrint('很抱歉，您这次没有摇中，继续加油！', 304, $result);
        }
        
        $mm = new MemberModel();
        if(empty($return)){
        	$result = $mm->getShakeRestTimes($uid, $result);
        	$this->myApiPrint('你今日摇奖机会已经用完！', 302, $result);
        }
        $result = $return;
       
                
        //3.获得中奖概率
        $rd = $mm->get_rand(array($params['b8'], $params['b9']));
        if($rd == 1){
        	$mm->addShakeRecord($uid);
        	$result = $mm->getShakeRestTimes($uid, $result);
        	$this->myApiPrint('很抱歉，您这次没有摇中！', 304, $result);
        }
        
        
        //4.获取摇一摇
        M()->startTrans();
        $shake = $mm->getShakePool($uid, $params, $lat, $lng);
        if(!$shake){
        	//没有摇一摇了
        	M()->rollback();
        	$mm->addShakeRecord($uid);
        	$result = $mm->getShakeRestTimes($uid, $result);
        	$this->myApiPrint('很抱歉，您这次没有摇中！.', 304, $result);
        }else{
        	M()->commit();
        	$result = $mm->getShakeRestTimes($uid, $result);
        	$result['shake_img'] = $shake['shake_img'];
        	$result['store_id'] = M('store')->where('uid='.$shake['user_id'].' and manage_status=1')->order('id desc')->getField('id');
        	$this->myApiPrint($shake['msg'], 400, $result);
        }
        
    }
    
    
    
	
	
	/**
	 * 摇一摇发布记录
	 * Enter description here ...
	 */
	public function shakelist(){
		$uid = intval(I('post.uid'));
		$page = I('post.page');
		$everyPage = '10';
		
		$page = intval(I('post.page')) - 1;
		$page = $page>0 ? $page*$everyPage : 0;
		
		$st='user_id = '.$uid;
		$totalPage = M('shake')->where($st)->count();
		$pageString = $page . ',' . $everyPage;
		
		$data = M('shake')->field('shake_id id, shake_times times, shake_addtime addtime, shake_amount/shake_times as goldcoin, shake_amount totalcoin, shake_img img, shake_ranges ranges, user_id uid')
			->where($st)
			->order('shake_id desc')
			->limit($pageString)
			->select();
		foreach ($data as $k=>$v){
			$tt = M('shake_records')->where('shake_id = '.$v['id'])->count();
			$data[$k]['used'] = intval($tt);
			$data[$k]['img'] = $v['img'].c('USER_SHANKELIST_SIZE');
			$data[$k]['goldcoin'] = sprintf('%.2f', $v['goldcoin']);
			$data[$k]['totalcoin'] = sprintf('%.2f', $v['totalcoin']);
		}
		
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
	 * 摇一摇中奖列表
	 * Enter description here ...
	 */
	public function shakewin(){
		$id = intval(I('post.id')); //摇一摇id
		$page = I('post.page');
		$everyPage = '10';
		$page = intval(I('post.page')) - 1;
		$page = $page>0 ? $page*$everyPage : 0;

		/*回购列表*/
		//当月实际
		$month = date('Ym')*100;
		$list = M('shake_refund')->field('refund_addtime,refund_amount')->where('shake_id = '.$id.' and refund_tag > '.$month)->order('refund_tag desc')->select();
		foreach ($list as $k=>$v){
			$list[$k]['refund_addtime'] = date('Y-m-d',$v['refund_addtime']);
			$list[$k]['refund_amount'] = sprintf('%.2f', $v['refund_amount']);
		}
		
		$page1['totalPage'] = floor(($totalPage - 1) / 10) + 1;
		$page1['everyPage'] = $everyPage;
		$data1['data'] = $list;
		$data1['page'] = $page1;
		
		/*中奖列表
		
		
		$st = 'p.shake_id =  '.$id;
		$totalPage = M('shake_records p')->where($st)->count();
		$pageString = $page . ',' . $everyPage;
		
		$data = M('shake_records p')
			->field('p.records_cash, p.records_credits, p.records_addtime, CONCAT(m.img,\''.C('USER_SHANKEINFO_SIZE').'\') as img, m.nickname')
			->join('left join zc_member as m on m.id =p.user_id ')
			->where($st)
			->order('p.records_id desc')
			->limit($pageString)
			->select();
		
		if (empty($data)) {
			$page1['totalPage'] = floor(($totalPage - 1) / 10) + 1;
			$page1['everyPage'] = $everyPage;
			$data1['page'] = $page1;
			$this->myApiPrint('查无更多数据！',400,$data1);
		}
		foreach ($data as $i=>$row){
			foreach ($row as $k=>$v){
				if(trim($v) == ''){
					$data[$i][$k]='';
				}
			}
		}
		$page1['totalPage'] = floor(($totalPage - 1) / 10) + 1;
		$page1['everyPage'] = $everyPage;
		$data1['data'] = $data;
		$data1['page'] = $page1;
		
		*/
		$this->myApiPrint('查询成功！', 400, $data1);
		
	}
	
	
	/**
	 * 附近的商家列表
	 */
	public function nearMerchants(){
		//经纬度、范围
		$page = intval(I('post.page'));
		$lng = I('post.lng');
		$lat = I('post.lat');
		$range = empty($_POST['range'])?100:intval($_POST['range']); //公里
		
		$param = M('g_parameter',null)->find();
		//分页
		$ps = 10;
		if($page < 1){
			$page = 1;
		}
		$limit = ($page-1)*$ps . ','.$ps;
		
		//条件
		$whereStringlike = ' a.id>0 and m.is_lock = 0 and a.manage_status=1 and a.status=0 and k.shake_status=2 ';
		
		$distancexp = '(6371 * acos( cos( radians(a.latitude) ) * cos( radians( '.$lat.' ) ) * cos( radians( '.$lng.' ) - radians(a.longitude) ) + sin( radians(a.latitude) ) * sin( radians( '.$lat.' ) ) ) )';
		if($range > 0){
			$whereStringlike .= ' and '.$distancexp.' < '.$range;
		}
		$sql = 'SELECT a.store_type, a.store_supermarket, a.give_points_total,a.id,a.fid,a.store_name,a.address,a.score,a.person_consumption,a.attention,a.pay_type, b.conditions as conditions,CONCAT(a.store_img,\''.C('SEARCH_STORELIST_SIZE').'\') as img,b.reward, a.latitude, a.longitude, '.$distancexp.' as distance, a.month_consumption FROM zc_shake as k '
				.' left join zc_member as m on m.id = k.user_id and m.is_lock = 0 '
				.' left join zc_store as a on a.uid = m.id '
				.' left join zc_preferential_way as b on b.store_id=a.id '
				.'	where '.$whereStringlike
				.'	ORDER BY  distance asc'
				.'  limit '.$limit;
		$data = M()->query($sql);
		
		foreach ($data as $k=>$v){
			//换算赠送公让宝
			$data[$k]['reward'] = $v['reward']*$param['points_member'];
				
			$data[$k]['month_consumption'] = intval($v['month_consumption']);
			if(empty($v['distance'])){
				$data[$k]['distance'] = -1;
			}else{
				$data[$k]['distance'] = intval($data[$k]['distance']*1000);
			}
				
			//剩余公让宝
			$d_tag = 'points_merchant_max_day_'.$v['store_type'];
			$w_tag = 'points_merchant_max_week_'.$v['store_type'];
			$expr_point = C('PARAMETER_CONFIG.MERCHANT')[$d_tag]==0?C('PARAMETER_CONFIG.MERCHANT')[$w_tag]:C('PARAMETER_CONFIG.MERCHANT')[$d_tag];  //商家的今日/本周可赠公让宝上限
			$rest = $expr_point - $v['give_points_total'];
			if($expr_point > 0){
				if($rest > 0){
					$data[$k]['store_current_max_points'] = '本周剩余可赠最高丰谷宝'.sprintf('%.2f',$rest);
				}else{
					$data[$k]['store_current_max_points'] = '本周剩余可赠最高丰谷宝0';
				}
			}else{
				$data[$k]['store_current_max_points'] = '';
			}
				
			$data[$k]['store_reward'] = '店内消费'.$data[$k]['conditions'].'元赠送'.$data[$k]['reward'].'丰谷宝';
		}
		
		$this->myApiPrint('获取成功', 400, $data);
	}
	
}
?>