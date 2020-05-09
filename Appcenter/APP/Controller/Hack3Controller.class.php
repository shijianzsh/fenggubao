<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 创客账户统计，明细。新增控制器，为了优化、兼容老版本
// +----------------------------------------------------------------------
namespace APP\Controller;

use Common\Controller\ApiController;
use V4\Model\AccountFinanceModel;
use V4\Model\OrderModel;
use V4\Model\PaymentMethod;
use V4\Model\MemberModel;
use V4\Model\AccountRecordModel;
use V4\Model\CurrencyAction;
use V4\Model\AccountModel;
use V4\Model\Currency;
use V4\Model\Tag;

class Hack3Controller extends ApiController {

	/**
	 * 会员展示首页
	 * Enter description here ...
	 */
	public function index() {
		$uid = intval( I( 'post.uid' ) );

		$user = M( 'member' )->where( 'id=' . $uid )->find();
		if ( empty( $user ) ) {
			$this->myApiPrint( '用户不存在，非法访问！' );
		}

		//我的总丰收
		$income_total         = M( 'account_income' )->where( [
			'user_id'         => $user['id'],
			'performance_tag' => 0
		] )->find();
		$data['income_total'] = sprintf( '%.2f', $income_total['income_cash_total'] );

		//我的总业绩
		$performance               = M( 'performance' )->where( [ 'user_id' => $uid, 'performance_tag' => 0 ] )->find();
		$data['performance_total'] = sprintf( '%.2f', getString( $performance['performance_amount'], 0 ) );
		//我的本月总业绩
		$performance_month         = M( 'performance' )->where( [
			'user_id'         => $uid,
			'performance_tag' => date( 'Ym' )
		] )->find();
		$data['performance_month'] = sprintf( '%.2f', getString( $performance_month['performance_amount'], 0 ) );

		// 业绩指数
		$data['star']  = $user['star'];
		$data['level'] = $user['level'];

		$data['allow_apply_company']   = '1';
		$data['disable_apply_company'] = '';
		$applyInfo_commpany            = M( 'apply_service_center' )->where( [
			'uid'         => [ 'eq', $uid ],
			'apply_level' => [ 'eq', 3 ],
		] )->field( 'status,reason' )->order( 'id desc' )->find();
		if ( $applyInfo_commpany ) {
			switch ( $applyInfo_commpany['status'] ) {
				case 0:
					$data['allow_apply_company']   = '0';
					$data['disable_apply_company'] = '审核中...';
					break;
				case 1:
					$data['allow_apply_company']   = '0';
					$data['disable_apply_company'] = '已经是区域合伙人';
					break;
				case 2:
					break;
			}
		}
		$store = M( 'store' )->where( 'manage_status=1 and uid=' . $uid )->find();
		if ( $store ) {
			$data['allow_apply_company']   = '0';
			$data['disable_apply_company'] = '商家不能申请区域合伙人';
		}
		$this->myApiPrint( '查询成功！', 400, $data );

	}


	/**
	 * 创客累计收益-月份列表
	 * Enter description here ...
	 */
	public function monthlyIncomeList() {
		$user_id = intval( I( 'post.uid' ) );

		//查询数据
		$afm  = new AccountFinanceModel();
		$list = $afm->getListMonthByUserId( $user_id, 'finance_id, user_id, finance_total, finance_tag' );

		//查询昨日通证汇总
		$afm                   = new AccountFinanceModel();
		$r3                    = $afm->getItemByUserId( $user_id, 'finance_total', 0 );
		$data['finance_total'] = getString( $r3['finance_total'], 0 );
		$data['list']          = $list;

		$this->myApiPrint( '查询成功！', 400, $data );
	}


	/**
	 * 创客累计收益-每日期列表
	 * Enter description here ...
	 */
	public function dayIncomeList() {
		$uid   = intval( I( 'post.uid' ) );
		$month = I( 'post.tag' );

		//验证参数
		verify_cash_list( $uid, $month );

		//查询数据
		$afm  = new AccountFinanceModel();
		$list = $afm->getListByUserId( $uid, 'finance_id, user_id, finance_tag, finance_total', $month );

		$this->myApiPrint( '查询成功！', 400, $list );
	}

	/**
	 * 创客累计收益-每日详情
	 * Enter description here ...
	 */
	public function dayIncomeDetail() {
		$uid        = intval( I( 'post.uid' ) );
		$finace_tag = I( 'post.tag' );
		if ( empty( $finace_tag ) ) {
			$finace_tag = date( "Ymd", strtotime( "-1 day" ) );
		}

		//查询数据
		$where['income_tag'] = $finace_tag;
		$where['user_id']    = $uid;
		$item                = M( 'account_income' )->where( $where )->find();

		$keys   = array(
			'income_cash_repeat'         => '实体消费奖',
			'income_cash_merchant'       => '商家联盟奖',
			'income_cash_uniondelivery'  => '联盟投放奖',
			'income_cash_shake'          => '摇中现金',
			'income_cash_viewad'         => '看广告获得现金',
			'income_cash_dutyconsume'    => '责任消费奖',
			'income_cash_marketsubsidy'  => '推广奖',
			'income_cash_companysubsidy' => '机构补贴奖',
			'income_cash_freesubsidy'    => '免费补贴奖',
		);
		$return = array();
		foreach ( $keys as $k => $v ) {
			$vo           = array();
			$vo['label']  = $v;
			$vo['amount'] = empty( $item[ $k ] ) ? '0.0000' : $item[ $k ];
			//if($k == 'finance_cash_marketsubsidy'){
			//	$vo['amount'] = $item['finance_cash_marketsubsidy']+$item['finance_cash_honour_marketsubsidy']+$item['finance_goldcoin_honour_marketsubsidy']+$item['finance_goldcoin_marketsubsidy'];
			//}
			$vo['amount'] = sprintf( '%.2f', $vo['amount'] );
			$return[]     = $vo;
		}
		unset( $item['finance_uptime'] );
		unset( $item['finance_tag'] );
		$this->myApiPrint( '查询成功！', 400, $return );
	}


	/**
	 * 获取推荐总人数
	 * Enter description here ...
	 */
	public function recommand_hackercount() {
		$uid   = I( 'post.uid' );
		$level = I( 'post.level' );
		$level = ! is_numeric( $level ) ? 2 : ( $level > 1 ? 2 : 1 );

		$wherekey['id'] = $uid;
		$m              = M( 'member' )->where( $wherekey )->select();
		if ( empty( $m ) ) {
			$this->myApiPrint( '对不起，用户信息不存在！' );
		}

		$where['repath'] = array( 'like', '%,' . $uid . ',%' );
		$total           = M( 'member' )->where( $where )->count();
		$this->myApiPrint( '查询成功！', 400, $total );
	}

	/**
	 * 我的分享
	 * 直推会员：手机号，姓名，业绩
	 */
	public function recommand_vip() {
		$uid  = I( 'post.uid' );
		$page = intval( I( 'post.page' ) );
		if ( $page < 1 ) {
			$page = 1;
		}
		$user = M( 'member m' )->field( 'm.truename, m.loginname, p.performance_amount, m.role, m.level, m.is_partner, m.star' )
		                       ->join( 'left join zc_performance p on p.user_id = m.id and p.performance_tag = 0' )
		                       ->where( [ 'm.reid' => $uid ] )
		                       ->order( 'm.id asc' )->limit( 10 )->page( $page )->select();
		foreach ( $user as $k => $v ) {
			$user[ $k ]['loginname']          = substr( $v['loginname'], 0, 3 ) . '****' . substr( $v['loginname'], 7);
			$user[ $k ]['truename']           = '*' . mb_substr( $v['truename'], 1, mb_strlen( $v['truename'], 'utf-8' ), 'utf-8' );
			$user[ $k ]['performance_amount'] = sprintf( '%.2f', $v['performance_amount'] );
			$user[ $k ]['truename']           .= ' [' . getrole( $v ) . ']';
		}
		$this->myApiPrint( '获取成功', 400, [ 'userlist' => $user ] );
	}


	/**
	 * 分享vip,区代人数列表
	 */
	public function childlist() {
		$page = intval( I( 'post.page' ) );
		$uid  = I( 'post.uid' );
		$tag  = I( 'post.tag' );   //1=VIP1, 2=VIP2, 3=区代1， 4=区代2
		if ( $tag == 1 ) {
			$field = 'children_1_vip_ids';
		} elseif ( $tag == 2 ) {
			$field = 'children_2_vip_ids';
		} elseif ( $tag == 3 ) {
			$field = 'children_1_company_ids';
		} elseif ( $tag == 4 ) {
			$field = 'children_2_company_ids';
		} else {
			$this->myApiPrint( '参数tag错误' );
		}


		$idstr = M( 'user_children' )->where( 'user_id = ' . $uid )->getField( $field );
		if ( $idstr ) {
			$sql  = "select loginname, nickname, img, `level` from zc_member where FIND_IN_SET(id,'$idstr')";
			$list = M()->query( $sql );
			foreach ( $list as $k => $v ) {
				$list[ $k ]['loginname'] = substr( $v['loginname'], 0, 3 ) . '***' . substr( $v['loginname'], 8, 10 );
				$list[ $k ]['nickname']  = mb_substr( $v['nickname'], 0, 1, 'utf-8' ) . '**';
				if ( $v['level'] == 5 ) {
					$list[ $k ]['class'] = '银卡代理';
				} elseif ( $v['level'] == 6 ) {
					$list[ $k ]['class'] = '金卡代理';
				} elseif ( $v['level'] == 7 ) {
					$list[ $k ]['class'] = '钻卡代理';
				}
			}
			$this->myApiPrint( '获取成功', 400, $list );
		} else {
			$this->myApiPrint( '没找到数据' );
		}
	}


	/**
	 * 全国丰收系统-优化版
	 * Enter description here ...
	 */
	public function bonussys() {
		$id   = I( 'post.uid' );
		$user = M( 'member' )->find( $id );
		if ( $user ) {
			$afm = new AccountFinanceModel();
			//总丰收情况
			$finace                        = $afm->getItemByUserId( $id, 'finance_goldcoin_bonus,finance_colorcoin_bonus,finance_cash_bonus' );
			$info['total_bonus_cash']      = sprintf( '%.2f', $finace['finance_cash_bonus'] * 1 );
			$info['total_bonus_goldcoin']  = sprintf( '%.2f', $finace['finance_goldcoin_bonus'] * 1 );
			$info['total_bonus_colorcoin'] = sprintf( '%.2f', $finace['finance_colorcoin_bonus'] * 1 );
			//昨日丰收情况
			$yfinace                      = $afm->getItemByUserId( $id, 'finance_goldcoin_bonus,finance_colorcoin_bonus,finance_cash_bonus', date( "Ymd", strtotime( "-1 day" ) ) );
			$info['yestoday_bonus_total'] = sprintf( '%.2f', $yfinace['finance_cash_bonus'] + $yfinace['finance_goldcoin_bonus'] + $yfinace['finance_colorcoin_bonus'] );

			//查询丰收点
			$am             = new AccountModel();
			$account        = $am->getItemByUserId( $id );
			$info['bonus']  = $account['account_bonus_balance'];
			$info['points'] = sprintf( '%.2f', $account['account_points_balance'] * 1 );
			//如果已经回购，积分显示0
			$buyback = M( 'buyback' )->where( 'user_id = ' . $id . ' and buyback_status = 2' )->find();
			if ( ! empty( $buyback ) ) {
				$info['points'] = sprintf( '%.2f', 0 );
			}

			//总消费额
			$info['total_expenditure'] = abs( $account['account_goldcoin_expenditure'] ) + abs( $account['account_colorcoin_expenditure'] ) + abs( $account['account_cash_expenditure'] );

			//最高丰收额
			$param                   = M( 'g_parameter', null )->field( 'points_to_bonus' )->find( 1 );
			$info['max_bonus_money'] = $param['points_to_bonus'] * $account['account_bonus_balance'] + $info['account_points_balance'] * 1;

			//今日管理津贴总额
			$yestoday_gljt         = $afm->getGljtAmount( $id, Tag::getYesterday() );
			$info['yestoday_gljt'] = sprintf( '%.2f', $yestoday_gljt );

			//格式化
			foreach ( $info as $k => $v ) {
				if ( empty( $v ) ) {
					$v = 0;
				}
				$info[ $k ] = $v . '';
			}

			$this->myApiPrint( '查询完成！', 400, $info );
		} else {
			$this->myApiPrint( '没有数据！', 400 );
			exit;
		}
	}


	/**
	 * 已丰收现金/丰谷宝/商超券明细
	 * Enter description here ...
	 */
	public function bonusdetail() {
		$uid   = intval( I( 'post.uid' ) );
		$month = I( 'post.month' );
		$type  = intval( I( 'post.type' ) );  //1=现金； 2代金；3商超*/
		$pn    = intval( I( 'post.page' ) );

		//验证参数
		verify_cash_list( $uid, $month );

		if ( $type == 1 ) {
			$currency = Currency::Cash;
			$where    = ' and record_action = 64';
		} elseif ( $type == 2 ) {
			$currency = Currency::GoldCoin;
			$where    = ' and record_action = 63';
		} else {
			$currency = Currency::ColorCoin;
			$where    = ' and record_action = 66';
		}

		//加载数据
		$arm  = new AccountRecordModel();
		$data = $arm->getPageList( $uid, $currency, $month, $pn, 2, 10, $where );

		//处理数据
		$return = array();
		foreach ( $data['list'] as $k => $v ) {
			$row            = array();
			$row['amount']  = '+' . $v['record_amount'];
			$row['id']      = $v['record_id'];
			$row['addtime'] = $v['record_addtime'];
			$row['status']  = '已完成';
			//$row['action'] = $v['record_remark'];
			$row['action'] = CurrencyAction::getLabel( $v['record_action'] );

			$obj              = json_decode( $v['record_attach'], true );
			$attach           = $arm->initAtach( $obj, $currency, $month, $v['record_id'], $v['record_action'] );
			$row['from_name'] = $arm->getFinalName( $attach['from_uid'], $attach['from_name'] );
			$row['from_pic']  = $attach['pic'];
			$return[]         = $row;
		}

		$data['list'] = $return;


		$this->myApiPrint( '查询完成！', 400, $data );
	}


}