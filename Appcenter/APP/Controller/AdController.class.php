<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 广告
// +----------------------------------------------------------------------
namespace APP\Controller;

use Common\Controller\ApiController;
use V4\Model\AccountFinanceModel;
use V4\Model\MemberModel;
use V4\Model\Tag;


class AdController extends ApiController {

//	public function adlist() {
//		$page = intval( I( 'post.page' ) );
//		$user = getUserInBashu();
//		//总业绩
//		$allincome      = M( 'performance' )->where( [ 'user_id' => $user['id'], 'performance_tag' => '0' ] )->find();
//		$return['yeji'] = sprintf( '%.2f', $allincome['performance_amount'] );
//		//当月业绩
//		$income                 = M( 'performance' )->where( [
//			'user_id'         => $user['id'],
//			'performance_tag' => Tag::getMonth()
//		] )->find();
//		$return['month_income'] = sprintf( '%.2f', $income['performance_amount'] );
//		//通证汇总
//		$return['income_total'] = '0';
//		$return['xinzeng']      = '0';
//		$renshu                 = M( 'member' )->where( [
//			'repath' => [
//				'like',
//				$user['repath'] . $user['id'] . ',%'
//			]
//		] )->count();
//		$return['huiyuan']      = $renshu;
//		//今日是否签到
//		$return['checkin']  = 0;
//		$ckwhere['user_id'] = $user['id'];
//		$ckwhere['_string'] = "FROM_UNIXTIME(checkin_addtime,'%Y%m%d') = FROM_UNIXTIME(UNIX_TIMESTAMP(),'%Y%m%d')";
//		$checkin            = M( 'account_checkin' )->where( $ckwhere )->order( 'checkin_id desc' )->find();
//		if ( $checkin ) {
//			$return['checkin'] = 1;
//		}
//		//公告
////        $news = M('flash_news')->where(['type'=>2])->order('flash_id desc')->limit(5)->select();
////        foreach($news as $k=>$v){
////            $news[$k]['flash_link'] = C( 'LOCAL_HOST' ) . 'APP/Index/getFlashNewsDetail/flash_id/'.$v['flash_id'];
////        }
////        $return['news'] = $news;
//		//加载广告
//		$adlist = M( 'ad' )->field( 'ad_id, user_id, ad_title, ad_amount, ad_image, ad_type, ad_link' )
//		                   ->where( 'ad_status=2' )->order( 'ad_id desc' )->limit( 10 )->page( $page )->select();
//		//判断广告是否可点
//		foreach ( $adlist as $k => $v ) {
//			$adlist[ $k ]['enable'] = 1;
//		}
//		$return['adlist'] = $adlist;
//		$this->myApiPrint( '查询成功', 400, $return );
//	}

	/**
	 * 广告丰收系统
	 */
	public function adlist() {
		$page = intval( I( 'post.page' ) ) ?: 1;
		$uid  = intval( I( 'post.uid' ) );
		$user = M( 'member' )->where( [ 'id' => [ 'eq', $uid ] ] )->find();
		if ( empty( $user ) ) {
			$this->myApiPrint( '此用户不存在！' );
		}

		//加载广告
		$adlist = M( 'ad' )->field( 'ad_id, user_id, ad_title, ad_amount, ad_image, ad_type, ad_link' )
		                   ->where( 'ad_status=2' )
		                   ->order( 'ad_id desc' )
		                   ->limit( 10 )
		                   ->page( $page )
		                   ->select();

		//判断广告是否可点
		$vwhere['user_id'] = $uid;
		$vwhere['_string'] = "FROM_UNIXTIME(view_addtime,'%Y%m%d') = FROM_UNIXTIME(UNIX_TIMESTAMP(),'%Y%m%d')";
		foreach ( $adlist as $k => $v ) {
			$adlist[ $k ]['ad_image'] = C( 'LOCAL_HOST' ) . substr( $adlist[ $k ]['ad_image'], 1 );
			$adlist[ $k ]['enable']   = 1;
			$vwhere['ad_id']          = $v['ad_id'];
			$view                     = M( 'ad_view' )->where( $vwhere )->find();
			if ( $view ) {
				$adlist[ $k ]['enable'] = 0;
			}
		}

		$data['adlist'] = $adlist;

		$data['notice'] = '';

		//今日是否签到
		$ckwhere['user_id'] = $uid;
		$ckwhere['_string'] = "FROM_UNIXTIME(checkin_addtime,'%Y%m%d') = FROM_UNIXTIME(UNIX_TIMESTAMP(),'%Y%m%d')";
		$checkin            = M( 'account_checkin' )->where( $ckwhere )->order( 'checkin_id desc' )->find();
		$data['checkin']    = $checkin ? 1 : 0;

		$this->myApiPrint( '加载成功', 400, $data );
	}

}

?>