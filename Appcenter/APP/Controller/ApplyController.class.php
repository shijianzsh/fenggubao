<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 创客相关接口
// +----------------------------------------------------------------------
namespace APP\Controller;

use Common\Controller\ApiController;
use V4\Model\AccountIncomeModel;
use V4\Model\AccountModel;
use V4\Model\AccountRecordModel;
use V4\Model\ApplyModel;
use V4\Model\Currency;
use V4\Model\CurrencyAction;
use V4\Model\OrderModel;
use V4\Model\PaymentMethod;

class ApplyController extends ApiController {

	public $member = null;

	public function __construct() {
		parent::__construct();
		$user_id = intval( I( 'post.uid' ) );
		if ( ! $user_id ) {
			$this->myApiPrint( '非法操作！' );
		}

		$this->member = M( 'member' )->where( 'id=' . $user_id )->find();
		if ( ! $this->member ) {
			$this->myApiPrint( '非法操作！' );
		}

		if ( $this->member['is_lock'] != '0' || $this->member['is_blacklist'] != '0' ) {
			$this->myApiPrint( '非法操作！' );
		}

	}


	public function vipBefore() {

		if ( $this->member['level'] != '1' ) {
			$this->myApiPrint( '你的身份不允许申请个人代理！' );
		}

		$data                 = [];
		$data['title']        = '申请VIP';
		$data['balance_cash'] = sprintf( '%.2f', AccountModel::getInstance()->getBalance( $this->member['id'], 'cash' ) );

		$data['agreement'] = htmlspecialchars_decode( html_entity_decode( M( 'agreement' )->where( 'id=1' )->getField( 'makertxt' ) ?: '' ) );

		$data['amount'] = sprintf( '%.2f', $this->CFG['buy_gift_amount_2'] ?: 0 );

		$product = M( 'product p' )->field( 'p.id, p.`name`, p.img, p.price' )
		                           ->join( 'left join zc_product_affiliate a on a.product_id = p.id' )
		                           ->where( [
			                           'p.status'            => 0,
			                           'p.manage_status'     => 1,
			                           'a.affiliate_deleted' => 0,
			                           'a.block_id'          => 4
		                           ] )
		                           ->order( 'p.id desc' )
		                           ->find();

		$product['price_id'] = M( 'product_price' )->where( [ 'product_id' => $product['id'] ] )->order( 'price_tag asc' )->limit( 1 )->getField( 'price_id' );

		//$product['img']  = C( 'LOCAL_HOST' ) . $product['img'];
		$data['product'] = $product;

		//加载收货地址信息
		$data['hasaddr'] = 0;
		$addr            = M( 'address' )->where( 'uid=' . $this->member['id'] )->order( 'is_default desc' )->find();
		if ( $addr ) {
			$data['addr']    = $addr;
			$data['hasaddr'] = 1;
		}

//		$data['agreement_url'] = C( 'LOCAL_HOST' ) . U( 'H5/Agreement/index?type=maker' );

		$this->myApiPrint( 'OK', 400, $data );


	}


	public function serviceBefore() {
		$current_lang = getCurrentLang(true);
		
		if ( $this->member['role'] == '3' ) {
			$this->myApiPrint( '你已经是区域合伙人,不需要重复申请！' );
		}

		$data                 = [];
		$data['title']        = '申请区域合伙人';
		$data['balance_cash'] = sprintf( '%.2f', AccountModel::getInstance()->getBalance( $this->member['id'], 'cash' ) );

		$data['agreement'] = htmlspecialchars_decode( html_entity_decode( M( 'agreement' )->where( 'id=1' )->getField( 'warranttxt'.$current_lang ) ?: '' ) );

//		prize_service_consume_bai_2

		$data['amount'] = sprintf( '%.2f', $this->CFG['apply_service_amount'] ?: 0 );


		$this->myApiPrint( 'OK', 400, $data );

	}

	public function service() {
		$current_lang = getCurrentLang();
		
		//$payway = intval( I( 'post.payway' ) ); // 支付方式: 1=现金积分; 2=微信;
		$province = $this->post['province'];
		$city = $this->post['city'];
		$country = $this->post['country'];
		
		//核验实名认证
		$map_certification = [
			'user_id' => $this->member['id'],
			'certification_status' => 2
		];
		$certification = M('certification')->where($map_certification)->find();
		if (!$certification) {
			$this->myApiPrint('请先实名认证', 300);
		}
		
		
//		if ( ! $payway ) {
//			//$this->myApiPrint( '非法操作' );
//		}
		if ($current_lang == 'zh-cn') {
			if ( !validateExtend($province, 'CHS') || !validateExtend($city, 'CHS') || !validateExtend($country, 'CHS') ) {
				$this->myApiPrint( '请选择省市区' );
			}
		}
		
		//判断是否已提交了省级申请
		$wherekey['uid'] = array('eq', $this->member['id']);
		$wherekey['apply_level'] = array('eq', 4);
		$wherekey['status'] = array('neq', 2);
		$apply_info = M('apply_service_center')->where($wherekey)->field('id')->order('id desc')->find();
		if ($apply_info) {
			$this->myApiPrint('对不起，你已经申请了省级合伙人，不能再申请区代合伙人！', 300);
		}
        
        //判断对应地区是否还能申请
        $map_apply_exists['mem.province'] = array('eq', $province);
        $map_apply_exists['mem.city'] = array('eq', $city);
        $map_apply_exists['mem.country'] = array('eq', $country);
        $map_apply_exists['ase.apply_level'] = array('eq', 3);
        $map_apply_exists['ase.status'] = array('neq', 2);
        $apply_exists = M('Member')->alias('mem')
        	->join('join __APPLY_SERVICE_CENTER__ ase ON ase.uid=mem.id')
        	->where($map_apply_exists)
        	->field('mem.id')
        	->find();
        if ($apply_exists) {
        	$this->myApiPrint('对不起，该地区已不能再申请');
        }

		$apply_info = M( 'apply_service_center' )->where( [
			'uid'         => $this->member['id'],
			'apply_level' => 3,
		] )->field( 'id,status' )->order( 'id desc' )->find();

		if ( $apply_info ) {
			if ( $apply_info['status'] == 0 ) {
				$this->myApiPrint( '对不起，你已经申请了区域合伙人，正在审核中...！', 300 );
			}
			//如果之前申请的已驳回, 则自动清除之前的申请
			if ( $apply_info['status'] == 2 ) {
				M( 'ApplyServiceCenter' )->where( [ 'id' => $apply_info['id'] ] )->delete();
			}
		}

		M()->startTrans();
		
		$result1 = M( 'apply_service_center' )->add( [ 'uid' => $this->member['id'], 'apply_level' => 3, 'get_time' => time() ] );

		if ( !$result1 ) {
			M()->rollback();
			$this->myApiPrint( '申请失败01' );
		}
		
		//保存省市区
		$map_member['id'] = array('eq', $this->member['id']);
		$data_member = [
			'province' => $province,
			'city' => $city,
			'country' => $country
		];
		$result2 = M('Member')->where($map_member)->save($data_member);
		if ( !$result2 && $current_lang == 'zh-cn') {
			M()->rollback();
			$this->myApiPrint( '申请失败03' );
		}

		
		M()->commit();
		$this->myApiPrint( '申请成功', 400 );
	}


}