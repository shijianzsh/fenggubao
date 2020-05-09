<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | H5注册的
// +----------------------------------------------------------------------
namespace H5\Controller;

use Think\Controller;

//use Common\Controller\PushController;

class AgreementController extends Controller {


	public function __construct() {
		parent::__construct();
	}

	public function index() {

		$field = I( 'get.type' );

		$title   = '';
		$content = '';
		$fields  = [
			'income'  => '收益说明',
			'maker'   => '创客协议',
			'user'    => '用户协议',
			'storeup' => '商家升级协议',
			'warrant' => '授权书',
			'benefit' => '巴蜀公益',
			'culture' => '公司文化',
			'privacy' => '隐私政策',
		];
		if ( isset( $fields[ $field ] ) ) {
			$title   = $fields[ $field ];
			$content = htmlspecialchars_decode(html_entity_decode(M( 'agreement' )->where( 'id=1' )->getField( $field . 'txt' )));
		}
		$this->assign( 'title', $title );
		$this->assign( 'content', $content );
		$this->display();
	}

}