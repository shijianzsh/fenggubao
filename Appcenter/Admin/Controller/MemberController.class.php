<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 团队会员管理
// +----------------------------------------------------------------------
namespace Admin\Controller;

use Common\Controller\AuthController;
use V4\Model\AccountIncomeModel;
use V4\Model\AccountRecordModel;
use V4\Model\Currency;
use V4\Model\AccountModel;
use V4\Model\AccountFinanceModel;
use V4\Model\WithdrawModel;
use V4\Model\CurrencyAction;
use V4\Model\UserModel;
use V4\Model\ProcedureModel;
use Common\Model\Sys\PerformanceModel;
use Common\Model\Sys\PerformanceRuleModel;
use V4\Model\GjjModel;
use V4\Model\MiningModel;

class MemberController extends AuthController {

	private $level_cn; //会员级别对应中文

	public function __construct() {
		parent::__construct();

		$this->level_cn = C( 'MEMBER_LEVEL' );
	}

	/**
	 * 个人代理列表
	 */
	public function memberList() {
		$member    = M( "member" );
		
		$searchKey = array();

		$userid = ( isset( $_GET ) && count( $_GET ) > 0 ) ? I( 'get.userid' ) : '';
		
		$wallet_type = empty($this->get['wallet_type']) ? 'ZWY' : $this->get['wallet_type'];
		$wallet_address = $this->get['wallet_address'];

		if ( $userid != "" ) {
			if ( ! validateExtend( $userid, 'NUMBER' ) && ! validateExtend( $userid, 'CHS' ) && ! validateExtend( $userid, 'USERNAME' ) ) {
				$this->error( '会员账号格式有误' );
			}

			$searchKey_temp['zc_member.loginname'] = array( 'eq', $userid );
			$searchKey_temp['zc_member.truename']  = array( 'eq', $userid );
			$searchKey_temp['zc_member.nickname']  = array( 'eq', $userid );
			$searchKey_temp['zc_member.username']  = array( 'eq', $userid );
			$searchKey_temp['_logic']              = 'OR';
			$searchKey['_complex']                 = $searchKey_temp;
		}
		
		if (!empty($wallet_address)) {
			switch ($wallet_type) {
				case 'ZWY':
					$map_user_affiliate['zhongwy_wallet_address'] = ['eq', $wallet_address];
					break;
				case 'AJS':
					$map_user_affiliate['wallet_address'] = ['eq', $wallet_address];
					$map_user_affiliate['wallet_address_2'] = ['eq', $wallet_address];
					$map_user_affiliate['_logic'] = 'or';
					break;
				case 'SLU':
					$map_user_affiliate['slu_wallet_address'] = ['eq', $wallet_address];
					break;
			}
			$user_id_list = M('UserAffiliate')->where($map_user_affiliate)->getField('user_id', true);
			if (!empty($user_id_list)) {
				$searchKey['zc_member.id'] = ['in', implode(',', $user_id_list)];
			}
		}

		if ( I( 'get.time_min' ) != "" && I( 'get.time_max' ) != "" ) {
			$searchKey ['zc_member.reg_time'] = array(
				'between',
				array( strtotime( I( 'get.time_min' ) . ' 0:0:0' ), strtotime( I( 'get.time_max' ) . ' 23:59:59' ) )
			);
		} elseif ( I( 'get.time_min' ) != "" ) {
			$searchKey ['zc_member.reg_time'] = array( 'EGT', strtotime( I( 'get.time_min' ) . ' 0:0:0' ) );
		} elseif ( I( 'get.time_max' ) != "" ) {
			$searchKey ['zc_member.reg_time'] = array( 'ELT', strtotime( I( 'get.time_max' ) . ' 23:59:59' ) );
		}

		//激活日期
		if ( I( 'get.jtime_min' ) != "" && I( 'get.jtime_max' ) != "" ) {
			$searchKey ['zc_member.open_time'] = array(
				'between',
				array( strtotime( I( 'get.jtime_min' ) . ' 0:0:0' ), strtotime( I( 'get.jtime_max' ) . ' 23:59:59' ) )
			);
		} elseif ( I( 'get.jtime_min' ) != "" ) {
			$searchKey ['zc_member.open_time'] = array( 'EGT', strtotime( I( 'get.jtime_min' ) . ' 0:0:0' ) );
		} elseif ( I( 'get.jtime_max' ) != "" ) {
			$searchKey ['zc_member.open_time'] = array( 'ELT', strtotime( I( 'get.jtime_max' ) . ' 23:59:59' ) );
		}

		$searchKey['zc_member.level'] = array( 'in', array( '2', '5' ) );
		//身份类型
		$search_level = $this->get['search_level'];
		switch ( $search_level ) {
			case 'formal':
				$searchKey['zc_member.level'] = array( 'eq', 2 );
				break;
			case 'service':
				$searchKey['zc_member.role'] = array( 'eq', 3 );
				break;
			case 'company':
				$searchKey['zc_member.role'] = array( 'eq', 4 );
				break;
			case 'blacklist':
				$searchKey['zc_member.is_blacklist'] = array( 'gt', 0 );
				break;
			case 'lock':
				$searchKey['zc_member.is_lock'] = array('eq', 1);
				break;
		}

		$this->assign( 'search_level', $search_level );

		$whereSql['_string'] = ' zc_member.id>1 and zc_member.level<99';

		//判断当前管理员是否具有小管理员权限
		$is_small_super = $this->isSmallSuperManager();
		$this->assign( 'is_small_super', $is_small_super );

		if ( ! $is_small_super ) {
// 			//筛选级别
// 			if ( session( 'admin_level' ) == 99 ) {
// 				$whereSql['zc_member.repath'] = array( 'like', '%1%' );
// 			} else {
// 				$whereSql['zc_member.repath'] = array( 'like', '%' . session( 'admin_mid' ) . '%' );
// 			}
// 			if ( session( 'admin_level' ) == 3 || session( 'admin_level' ) == 4 ) {
// 				$whereSql = array_merge( $whereSql, $this->filterMember( session( 'admin_mid' ), true, 'zc_member', $whereSql ) );
// 			}
		}

		$count = $member
			->where( $searchKey )
			->where( $whereSql )
			->count();

		$page  = new \Think\Page( $count, 20, $this->get );
		$show  = $page->show();
		$_info = $member
			->where( $searchKey )->where( $whereSql )
			->field( 'zc_member.*' )
			->order( 'zc_member.open_time desc,zc_member.reg_time desc,zc_member.id desc' )
			->limit( $page->firstRow . ',' . $page->listRows )
			->group( 'zc_member.id' )
			->select();

		$AccountModel = new AccountModel();
		$Gjj = new GjjModel();
		foreach ( $_info as $k => $v ) {

			//获取账户资金余额
			$account_info = $AccountModel->getItemByUserId( $v['id'], $AccountModel->get5BalanceFields() . ',account_cash_expenditure,account_cash_income' );
			$_info[ $k ]  = ! empty( $account_info ) ? array_merge( $v, $account_info ) : $v;

			//当为区域合伙人身份时，判断是否存在自动结算回本记录或者获取自动结算回本信息
//			$service_clearing_info                  = M( 'ServiceClearing' )->where( 'user_id=' . $v['id'] )->field( 'clearing_status' )->find();
//			$_info[ $k ]['service_clearing_status'] = $service_clearing_info ? $service_clearing_info['clearing_status'] : null;

			//当为银卡代理时，获取自动结算回本信息
//			$micro_vip_clearing_info   = M( 'MicroVipClearing' )->where( 'user_id=' . $v['id'] )->field( 'clearing_status' )->find();
//			$micro_vip_clearing_status = '无';
//			switch ( $micro_vip_clearing_info['clearing_status'] ) {
//				case '0':
//					$vip_clearing_status = '未开启回本';
//					break;
//				case '1':
//					$vip_clearing_status = '回本中';
//					break;
//				case '2':
//					$vip_clearing_status = '完成回本';
//					break;
//			}
//			$_info[ $k ]['micro_vip_clearing_status']    = $micro_vip_clearing_info['clearing_status'];
//			$_info[ $k ]['micro_vip_clearing_status_cn'] = $micro_vip_clearing_status;

			//当为金卡代理时，获取自动结算回本信息
//			$vip_clearing_info   = M( 'VipClearing' )->where( 'user_id=' . $v['id'] )->field( 'clearing_status' )->find();
//			$vip_clearing_status = '无';
//			switch ( $vip_clearing_info['clearing_status'] ) {
//				case '0':
//					$vip_clearing_status = '未开启回本';
//					break;
//				case '1':
//					$vip_clearing_status = '回本中';
//					break;
//				case '2':
//					$vip_clearing_status = '完成回本';
//					break;
//			}
//			$_info[ $k ]['vip_clearing_status']    = $vip_clearing_info['clearing_status'];
//			$_info[ $k ]['vip_clearing_status_cn'] = $vip_clearing_status;

			//当为钻卡代理时，获取自动结算回本信息
//			$honour_vip_clearing_info   = M( 'HonourVipClearing' )->where( 'user_id=' . $v['id'] )->field( 'clearing_status' )->find();
//			$honour_vip_clearing_status = '无';
//			switch ( $honour_vip_clearing_info['clearing_status'] ) {
//				case '0':
//					$honour_vip_clearing_status = '未开启回本';
//					break;
//				case '1':
//					$honour_vip_clearing_status = '回本中';
//					break;
//				case '2':
//					$honour_vip_clearing_status = '完成回本';
//					break;
//			}
//			$_info[ $k ]['honour_vip_clearing_status']    = $honour_vip_clearing_info['clearing_status'];
//			$_info[ $k ]['honour_vip_clearing_status_cn'] = $honour_vip_clearing_status;

			//获取用户所属计划
//			$_info[ $k ]['plan_type'] = M( 'UserPlan' )->where( 'user_id=' . $v['id'] )->getField( 'plan_type' );
//			switch ( intval( $_info[ $k ]['plan_type'] ) ) {
//				case '0':
//					$_info[ $k ]['plan_type_cn'] = 'A计划';
//					break;
//				case '1':
//					$_info[ $k ]['plan_type_cn'] = 'B计划';
//					break;
//			}

			//再次判断是否为商家
			$map_store                  = [];
			$map_store['uid']           = array( 'eq', $v['id'] );
			$map_store['status']        = array( 'eq', 0 );
			$map_store['manage_status'] = array( 'eq', 1 );
			$store_info                 = M( 'Store' )->where( $map_store )->find();
			$_info[ $k ]['store_flag']  = $store_info ? '1' : '0';
			$find_gjj_role = $Gjj->getGjjRoles($v['id']);
            $_info[$k]['gjj_role'] = implode('<br />',$find_gjj_role);

			//判断当前用户是否为大中华区身份
			$map_gjj_region = [
				'user_id' => ['eq', $v['id']],
				'role' => ['eq', 5],
				'audit_status' => ['eq', 1]
			];
			$gjj_region = $Gjj->getInfo('id', $map_gjj_region);
			$_info[$k]['gjj_is_region'] = $gjj_region ? true : false;
			
			//获取钱包地址
			$_info[$k]['wallet_address'] = M('UserAffiliate')->where('user_id='.$v['id'])->field('wallet_address,wallet_address_2,zhongwy_wallet_address,slu_wallet_address')->find();
		}
		$this->assign( 'info', $_info );

		$this->assign( 'admin_level', session( 'admin_level' ) );
		$this->assign( "page", $show );
		$this->display( 'memberList' );
	}

	/**
	 * 体验会员列表
	 */
	public function memberListNot() {
		$member    = M( "member" );
		$searchKey = array();

		$wallet_type = empty($this->get['wallet_type']) ? 'ZWY' : $this->get['wallet_type'];
		$wallet_address = $this->get['wallet_address'];

		if ( I( 'get.userid' ) != "" ) {

			if ( ! validateExtend( I( 'get.userid' ), 'NUMBER' ) && ! validateExtend( I( 'get.userid' ), 'CHS' ) && ! validateExtend( $this->get['userid'], 'USERNAME' ) ) {
				$this->error( '会员账号格式有误' );
			}

			$searchKey_temp['mem.loginname'] = array( 'eq', I( 'get.userid' ) );
			$searchKey_temp['mem.truename']  = array( 'eq', I( 'get.userid' ) );
			$searchKey_temp['mem.nickname']  = array( 'eq', I( 'get.userid' ) );
			$searchKey_temp['mem.username']  = array( 'eq', I( 'get.userid' ) );
			$searchKey_temp['_logic']        = 'OR';
			$searchKey['_complex']           = $searchKey_temp;
		}
		
		if (!empty($wallet_address)) {
			switch ($wallet_type) {
				case 'ZWY':
					$map_user_affiliate['zhongwy_wallet_address'] = ['eq', $wallet_address];
					break;
				case 'AJS':
					$map_user_affiliate['wallet_address'] = ['eq', $wallet_address];
					$map_user_affiliate['wallet_address_2'] = ['eq', $wallet_address];
					$map_user_affiliate['_logic'] = 'or';
					break;
				case 'SLU':
					$map_user_affiliate['slu_wallet_address'] = ['eq', $wallet_address];
					break;
			}
			$user_id_list = M('UserAffiliate')->where($map_user_affiliate)->getField('user_id', true);
			if (!empty($user_id_list)) {
				$searchKey['zc_member.id'] = ['in', implode(',', $user_id_list)];
			}
		}

		if ( I( 'get.time_min' ) != "" && I( 'get.time_max' ) != "" ) {
			$searchKey ['mem.reg_time'] = array(
				'between',
				array( strtotime( I( 'get.time_min' ) . ' 0:0:0' ), strtotime( I( 'get.time_max' ) . ' 23:59:59' ) )
			);
		} elseif ( I( 'get.time_min' ) != "" ) {
			$searchKey ['mem.reg_time'] = array( 'EGT', strtotime( I( 'get.time_min' ) . ' 0:0:0' ) );
		} elseif ( I( 'get.time_max' ) != "" ) {
			$searchKey ['mem.reg_time'] = array( 'ELT', strtotime( I( 'get.time_max' ) . ' 23:59:59' ) );
		}
		$searchKey['mem.level'] = array( 'in', array( '1' ) );
		$whereSql['_string']    = ' mem.id>1 and mem.level<99 and mem.loginname>10000000000';

		$searchKey['mem.level'] = array( 'in', array( '1' ) );
		//身份类型
		$search_level = $this->get['search_level'];
		switch ( $search_level ) {
//			case 'try':
//				$searchKey['mem.level'] = array( 'eq', 1 );
//				break;
//			case 'formal':
//				$searchKey['mem.level'] = array( 'eq', 2 );
//				break;
//			case 'maker':
//				$searchKey['mem.level'] = array( 'eq', 5 );
//				break;
			case 'partner':
				$searchKey['mem.is_partner'] = array( 'eq', 1 );
				break;
			case 'service':
				$searchKey['mem.role'] = array( 'eq', 3 );
				break;
			case 'company':
				$searchKey['mem.role'] = array( 'eq', 4 );
				break;
			case 'blacklist':
				$searchKey['mem.is_blacklist'] = array( 'gt', 0 );
				break;
			case 'lock':
				$searchKey['mem.is_lock'] = array('eq', 1);
				break;
		}


		//判断当前管理员是否具有小管理员权限
		$is_small_super = $this->isSmallSuperManager();
		$this->assign( 'is_small_super', $is_small_super );
		if ( ! $is_small_super ) {
// 			//筛选级别 如果下线出现同级或高级的会员,则不获取该会员及其下级的会员信息
// 			if ( session( 'admin_level' ) == 99 ) {
// 				$whereSql['mem.repath'] = array( 'like', '%1%' );
// 			} else {
// 				$whereSql['mem.repath'] = array( 'like', '%' . session( 'admin_mid' ) . '%' );
// 			}
// 			if ( session( 'admin_level' ) == 3 || session( 'admin_level' ) == 4 ) {
// 				$whereSql = array_merge( $whereSql, $this->filterMember( session( 'admin_mid' ), true, 'mem', $whereSql ) );
// 			}
		}

		$count = $member
			->alias( 'mem' )
			->where( $searchKey )
			->where( $whereSql )
			->count();

		$page  = new \Think\Page( $count, 20, $this->get );
		$show  = $page->show();
		$_info = $member
			->alias( 'mem' )
			->join( 'join __MEMBER__ mem1 on mem1.id=mem.reid' )
			->field( 'mem.*,mem1.loginname p_loginname,mem.nickname p_nickname' )
			->where( $searchKey )
			->where( $whereSql )
			->order( 'reg_time desc,id desc' )
			->limit( $page->firstRow . ',' . $page->listRows )
			->select();

		$AccountModel = new AccountModel();
		foreach ( $_info as $k => $v ) {
			//获取账户资金余额
			$account_info = $AccountModel->getItemByUserId( $v['id'], $AccountModel->get5BalanceFields() . ',account_cash_expenditure,account_cash_income' );
			$_info[ $k ]  = ! empty( $account_info ) ? array_merge( $v, $account_info ) : $v;

			//再次判断是否为商家
			$map_store                  = [];
			$map_store['uid']           = array( 'eq', $v['id'] );
			$map_store['status']        = array( 'eq', 0 );
			$map_store['manage_status'] = array( 'eq', 1 );
			$store_info                 = M( 'Store' )->where( $map_store )->find();
			$_info[ $k ]['store_flag']  = $store_info ? '1' : '0';
			
			//获取钱包地址
			$_info[$k]['wallet_address'] = M('UserAffiliate')->where('user_id='.$v['id'])->field('wallet_address,wallet_address_2,zhongwy_wallet_address,slu_wallet_address')->find();
		}
		$this->assign( 'info', $_info );

		$this->assign( 'admin_level', session( 'admin_level' ) );
		$this->assign( "page", $show );
		$this->display();
	}

	/**
	 * 推荐关系
	 */
	public function tree() {
		$str       = '';
		$sess_auth = $this->get( 'sess_auth' );

		$uid   = $sess_auth['admin_mid'];
		$level = $sess_auth['admin_level'];

		//判断是否为小管理员,若为小管理员,则显示全部推荐关系
		$is_small_super_manager = $this->isSmallSuperManager();
// 		if ( $is_small_super_manager ) {
			$init = M( 'member' )->where( 'reid=0 and level=99' )->find();
			$uid  = $init['id'];
// 		}

		if ( $uid ) {
			$data      = M( 'member' )->where( 'id=' . $uid )->find();
			$level_cur = empty( $data['role'] ) ? $data['level'] : $data['role'];
			$level_cn  = '[身份:' . getLevelName( $data['level'], $data['star'] ) . ']';
			$level_cn  .= $data['is_partner'] == '1' ? '[合伙人]' : '';
			$level_cn  .= $data['store_flag'] == '1' ? '[商]' : '';
			$level_cn  .= ! empty( $data['role'] ) ? '[' . getRoleName( $data['role'] ) . ']' : '';

			$str .= '<li><a href="javascript:;" uid="' . $uid . '" level="' . $level_cur . '" onclick="getTree(this);">' . $data['username'] . '</a>[手机:' . $data['loginname'] . '][姓名:' . $data['truename'] . ']' . $level_cn . '</li>';
		}

		$this->assign( 'tree1', $str );
		$this->display();
	}

	/**
	 * 异步获取推荐关系
	 */
	public function getTreeByAsyn() {
		$uid       = I( 'post.uid' );
		$level     = I( 'post.level' );
		$top_level = session( 'admin_level' );

		if ( ! is_numeric( $uid ) ) {
			exit( '' );
		}
		$level = empty( $level ) ? false : $level;

		//判断是否为小管理员,若为小管理员,则设置top_level=99
		$is_small_super_manager = $this->isSmallSuperManager();
// 		if ( $is_small_super_manager ) {
			$top_level = 99;
// 		}

		$Member = M( 'member' );

		$reid_repath = $Member->where( 'id=' . $uid )->getField( 'repath' );

		//$where['reid'] = array('eq', $uid);
		$where['loginname'] = array( 'gt', 10000000000 );
		$where['repath']    = array( 'eq', $reid_repath . $uid . ',' );

		//针对top_level是否等于6的情况进行判断处理
		if ( $top_level == 5 || $top_level == 6 || $top_level == 7 ) {
			$where['level'] = array( array( 'elt', 2 ), array( 'eq', $top_level ), 'or' );
		} elseif ( $top_level <= 2 ) {
			$where['level'] = array( 'elt', $top_level );
		} else {
			$where['level'] = array( array( 'elt', $top_level ), array( 'eq', 7 ), 'or' );
		}

		$list = $Member->where( $where )->select();

		if ( $level == $top_level && $uid != session( 'admin_mid' ) && $top_level != 99 ) {
			exit( '111' );
		}

		$li_html = '<ul>';
		if ( $list ) {
			foreach ( $list as $k => $data ) {
				$level_cur = empty( $data['role'] ) ? $data['level'] : $data['role'];
				$level_cn  = '[身份:' . getLevelName( $data['level'], $data['star'] ) . ']';
				$level_cn  .= $data['is_partner'] == '1' ? '[合伙人]' : '';
				$level_cn  .= $data['store_flag'] == '1' ? '[商]' : '';
				$level_cn  .= ! empty( $data['role'] ) ? '[' . getRoleName( $data['role'] ) . ']' : '';

				$li_html .= '<li><a href="javascript:;" uid="' . $data['id'] . '" level="' . $level_cur . '" onclick="getTree(this);">' . $data['username'] . '</a>[手机:' . $data['loginname'] . '][姓名:' . $data['truename'] . ']' . $level_cn . '</li>';
			}
			$li_html .= '</ul>';
			echo $li_html;
		} else {
			exit( '' );
		}
	}

	/**
	 * 搜索推荐关系
	 */
	public function searchAccountByAsyn() {
		$account   = I( 'post.account' );
		$top_level = session( 'admin_level' );
		$top_id    = session( 'admin_mid' );

		if ( ! validateExtend( $account, 'NUMBER' ) && ! validateExtend( $account, 'CHS' ) && ! validateExtend( $account, '/^([0-9a-zA-Z])+$/', true ) ) {
			exit( '会员账号格式有误' );
		}

		//判断是否为小管理员,若为小管理员,则设置top_level=99
		$is_small_super_manager = $this->isSmallSuperManager();
// 		if ( $is_small_super_manager ) {
			$top_level = 99;
			$top_id    = 1;
// 		}
		$Member             = M( 'member' );
		$where['loginname'] = array( 'eq', $account );
		$where['truename']  = array( 'eq', $account );
		$where['nickname']  = array( 'eq', $account );
		$where['username']  = array( 'eq', $account );
		$where['_logic']    = 'OR';
		$map['_complex']    = $where;

		$map['loginname'] = array( 'gt', 10000000000 );

		//排除当前登陆用户的上级或不在同一条线上的用户
// 		$map['level'] = array('elt', $top_level);
		//针对top_level是否等于6的情况进行判断处理
		if ( $top_level == 5 || $top_level == 6 || $top_level == 7 ) {
			$map['level'] = array( array( 'elt', 2 ), array( 'eq', $top_level ), 'or' );
		} elseif ( $top_level <= 2 ) {
			$map['level'] = array( 'elt', $top_level );
		} else {
			$map['level'] = array( array( 'elt', $top_level ), array( 'eq', 6 ), 'or' );
		}
		$map['_string'] = 'find_in_set(' . $top_id . ',repath)';
		$list    = $Member->where( $map )->select();
		$li_html = '<ul>';
		if ( $list ) {
			foreach ( $list as $k => $data ) {
				$level_cur = empty( $data['role'] ) ? $data['level'] : $data['role'];
				$level_cn  = '[身份:' . getLevelName( $data['level'], $data['star'] ) . ']';
				$level_cn  .= $data['is_partner'] == '1' ? '[合伙人]' : '';
				$level_cn  .= $data['store_flag'] == '1' ? '[商]' : '';
				$level_cn  .= ! empty( $data['role'] ) ? '[' . getRoleName( $data['role'] ) . ']' : '';

				$li_html .= '<li><a href="javascript:;" uid="' . $data['id'] . '" level="' . $level_cur . '" onclick="getTree(this);">' . $data['username'] . '</a>[手机:' . $data['loginname'] . '][姓名:' . $data['truename'] . ']' . $level_cn . '</li>';
			}
			$li_html .= '</ul>';
			echo $li_html;
		} else {
			exit( '' );
		}
	}

	/**
	 * 会员信息详情
	 */
	public function memberModify() {
		$id = I( 'get.id' );

		$extend = I( 'get.extend' );
		$id     = ! empty( $extend ) ? $extend : $id;

		if ( $id == "" ) {
			$this->error( '数据错误' );
		}

		$_info = M( 'member' )
			->alias( 'mem' )
			->join( 'left join __WITHDRAW_BANKCARD__ wib ON wib.uid=mem.id' )
			->where( "mem.id=" . $id )
			->field( 'mem.*,wib.uid,wib.inaccname,wib.inacc,wib.inaccbank,wib.inaccadd,wib.bankcode,wib.bankcode_max,wib.bank_pcd' )
			->find();

		if ( empty( $_info ) ) {
			$this->error( '数据错误' );
		}
		if ( ! empty( $_info['weixin'] ) ) {
			$_info['weixin'] = unserialize( $_info['weixin'] );
		}
		$this->assign( 'info', $_info );

		//获取所有银行名称信息
		$bank_list = M( 'Bank' )->field( 'bank' )->order( 'id asc' )->select();
		$this->assign( 'bank_list', $bank_list );

		$this->display();
	}

	/**
	 * 保存会员信息
	 */
	public function memberSave() {
		$member           = M( "member" );
		$WithdrawBankcard = M( 'WithdrawBankcard' );

		M()->startTrans();

		$data = I( 'post.data' );
		$data = trimarray( $data );

		if ( ! validateExtend( $data['id'], 'NUMBER' ) ) {
			$this->error( '数据错误' );
		}

		foreach ( $data as $k => $v ) {
			if ( empty( $v ) ) {
				unset( $data[ $k ] );
			}
		}
		
		if (!empty($data['truename'])) {
			$data['username'] = $data['truename'];
			$data['nickname'] = $data['truename'];
		}
		
		//验证手机号是否已存在
		$loginname = $data['loginname'];
		if (empty($loginname)) {
			$this->error('手机号不能为空');
		}
		$map_loginname = [
			'loginname' => ['eq', $loginname],
			'id' => ['neq', $data['id']]
		];
		$loginname_exists = M('Member')->where($map_loginname)->find();
		if ($loginname_exists) {
			$this->error('手机号已被其他用户使用');
		}

		if ( $data['password'] != "" ) {
			if ( $data['password'] != $data['repassword'] ) {
				$this->error( '登陆密码和确认登陆密码不一致' );
			}
			$data['password'] = md5( $data['password'] );
		} else {
			unset( $data['password'] );
		}
		if ( $data['safe_password'] != "" ) {
			if ( $data['safe_password'] != $data['resafe_password'] ) {
				$this->error( '安全密码和确认安全密码不一致' );
			}
			$data['safe_password'] = md5( $data['safe_password'] );
		} else {
			unset( $data['safe_password'] );
		}

		//银行卡
		$result1 = true;
		if ( isset( $data['bank'] ) ) {
			$map_bank['uid'] = array( 'eq', $data['id'] );
			$bank_data       = array(
				'inacc'        => $data['bank']['inacc'],
				'inaccbank'    => $data['bank']['inaccbank'],
				'bankcode'     => $data['bank']['bankcode'],
				'bankcode_max' => $data['bank']['bankcode_max']
			);
			unset( $data['bank'] );

			$result1 = $WithdrawBankcard->where( $map_bank )->save( $bank_data );
		}

		$result2 = M( 'member' )->save( $data );

		if ( $result1 === false || $result2 === false ) {
			M()->rollback();
			$this->error( '修改失败' );
		} else {
			M()->commit();

			$log_data = $member->field( 'loginname,nickname,username' )->where( 'id=' . $data['id'] )->find();
			$this->success( '操作成功', '', false, '修改' . $log_data['username'] . '[' . $log_data['loginname'] . '][' . $log_data['nickname'] . ']会员个人资料' );
		}
	}

	/**
	 * 激活开通会员
	 */
	public function memberOpen() {
		$uid = I( 'get.id' );

		if ( ! is_numeric( $uid ) ) {
			$this->error( '数据错误' );
		}

		$member = M( 'member' );

		$Info  = $member->where( "id=" . $uid )->find();
		$level = $member->where( 'id=' . $Info['reid'] )->getField( 'level' );
		if ( $level < 2 ) {
			$this->error( '你的上级还不是创客用户！' );
		}
		if ( $Info['is_pass'] == 1 ) {
			$this->error( '不需要重复激活' );
		}

		$where['id'] = $Info['id'];

		$udata1['is_pass']   = 1;
		$udata1['level']     = 2;
		$udata1['open_time'] = time();

		$member->where( $where )->save( $udata1 );

		$where_r['id'] = $Info['reid'];
		$member->where( $where_r )->setInc( 'recount', 1 ); //推荐人数加1
		#M()->execute(C('ALIYUN_TDDL_MASTER') . 'call recommand_bonus (' . $Info['id'] . ',@msg)');

		//操作记录
		$where_log['id'] = array( 'eq', $uid );
		$log_data        = $member->field( 'loginname,nickname,username' )->where( $where_log )->find();
		$this->success( '操作成功', '', false, '开通升级' . $log_data['username'] . '[' . $log_data['loginname'] . '][' . $log_data['nickname'] . ']为创客用户' );
	}

	/**
	 * 删除会员
	 */
	public function memberDelete() {
		$id = I( 'get.id', '' );

		if ( ! is_numeric( $id ) ) {
			$this->error( '数据错误' );
		}

		$member = M( "member" );
		$uInfo  = $member->where( "id=" . $id )->find();
		if ( empty( $uInfo ) ) {
			$this->error( '数据错误' );
		}
		if ( $uInfo['is_pass'] == 1 ) {
			$this->error( '不删除已经审核的会员' );
		}

		$where_m['repath'] = array( 'like', "%," . $id . ",%" );
		$row               = $member->where( $where_m )->select();
		if ( $row ) {
			$this->error( '不能删除有下级的体验会员 ！' );
			exit;
		}

		//操作记录
		$where_log['id'] = array( 'eq', $id );
		$log_data        = $member->field( 'loginname,nickname,username' )->where( $where_log )->find();
		$member->where( "id=" . $id )->delete();
		$this->success( '操作成功', '', false, '未激活会员列表删除' . $log_data['username'] . '[' . $log_data['loginname'] . '][' . $log_data['nickname'] . ']会员' );
	}

	/**
	 * 会员锁定与解锁
	 */
	public function memberLock() {

		$id  = I( 'get.id' );
		$b   = I( 'get.b' );
		$tid = I( 'get.tid' );

		if ( ! is_numeric( $id ) ) {
			$this->error( '数据错误' );
		}
		if ( $id == '1' ) {
			$this->error( "不能对管理员操作" );
		}

		$member = M( 'member' );
		$data   = $member->where( "id=" . $id )->find();
		if ( empty( $data ) ) {
			$this->error( '数据错误' );
		}

		if ( $data['is_lock'] == 1 ) {
			$_u_d['is_lock']          = 0;
			$_u_d['last_unlock_time'] = time();
			$member->where( "id=" . $data['id'] )->save( $_u_d );

			//操作记录
			$where_log['id'] = array( 'eq', $data['id'] );
			$log_data        = $member->field( 'loginname,nickname,username' )->where( $where_log )->find();
			$log_content     = '已解锁' . $log_data['username'] . '[' . $log_data['loginname'] . '][' . $log_data['nickname'] . ']为会员';
			if ( $b == '1' ) {
				M( "app_unlock" )->where( "id=" . $tid )->setField( 'pass_time', time() );
			}
		} else {
			$member->where( "id=" . $data['id'] )->setField( 'is_lock', 1 );

			//操作记录
			$where_log['id'] = array( 'eq', $data['id'] );
			$log_data        = $member->field( 'loginname,nickname,username' )->where( $where_log )->find();
			$log_content     = '已锁定' . $log_data['username'] . '[' . $log_data['loginname'] . '][' . $log_data['nickname'] . ']为会员';
		}

		$this->success( '操作成功', '', false, $log_content );
	}

	/**
	 * 会员等级更改处理
	 */
	public function memberGrade() {
		$member          = M( 'member' );
		$ServiceClearing = M( 'ServiceClearing' );

		$uid     = I( 'get.id' );
		$service = intval( I( 'get.service' ) );
		$company = intval( I( 'get.company' ) );

		$count = M( 'member' )->where( 'id=' . $uid )->count();
		if ( $count == 0 ) {
			$this->error( '数据错误！' );
			exit;
		}

		$level = M( 'member' )->where( 'id=' . $uid )->getField( 'level' );
		//$loginname = M('member')->where('id='.$uid)->getField('loginname');

		//获取积分相关的配置参数
		$service_company_points_clear = C( 'PARAMETER_CONFIG.POINTS' )['service_company_points_clear']; //2:取消服务/区域合伙人身份时,自动扣除对应分红股

		M()->startTrans();

		//取消服务中心
		if ( $service == 1 ) {

			$whereSql['id'] = $uid;
			$result         = M( 'member' )->where( $whereSql )->setField( 'role', '' );
			if ( ! $result ) {
				$this->error( '取消服务中心失败！', U( 'Admin/Member/memberList' ) );
				exit;
			}

			//同步删除对应管理员用户
			$SystemManager = new \Admin\Controller\AjaxController();
			$result0       = $SystemManager->memberDeleteAsyn( $uid, 'service' );

			if ( $result === false || $result0 === false ) {
				$this->error( '取消服务中心失败,请稍后重试', U( 'Admin/Member/memberList' ) );
			}

			M()->commit();

			//操作记录
			$where_log['id'] = array( 'eq', $uid );
			$log_data        = $member->field( 'loginname,nickname,username' )->where( $where_log )->find();
			$this->success( '取消服务中心成功！', U( 'Admin/Member/memberList' ), false, '会员列表取消' . $log_data['username'] . '[' . $log_data['loginname'] . '][' . $log_data['nickname'] . ']会员的服务中心，降级为创客用户' );
			exit;

		}

		//设置服务中心
		if ( $service == 2 ) {

			$where['id']  = $uid;
			$data['role'] = 3;
			$data['menu'] = M( 'parameter', 'g_' )->where( 'id=1' )->getField( 'service_auth' );
			$result       = M( 'member', 'zc_' )->where( $where )->save( $data );

			//同步添加对应管理员用户
			$manager_data  = array(
				'uid'      => $uid,
				'group_id' => array( C( 'ROLE_MUST_LIST.service' ) ),
				'type'     => 'service',
			);
			$SystemManager = new \Admin\Controller\AjaxController();
			$result1       = $SystemManager->memberAddAsyn( $manager_data );

			if ( $result === false || $result1 === false ) {
				$this->error( '设置服务中心失败,请稍后重试！', U( 'Admin/Member/memberList' ) );
				exit;
			}

			M()->commit();

			//操作记录
			$where_log['id'] = array( 'eq', $uid );
			$log_data        = $member->field( 'loginname,nickname,username' )->where( $where_log )->find();
			$this->success( '设置服务中心成功！', U( 'Admin/Member/memberList' ), false, '会员列表升级' . $log_data['username'] . '[' . $log_data['loginname'] . '][' . $log_data['nickname'] . ']会员为服务中心' );
			exit;

		}

		//取消区域合伙人
		if ( $company == 1 ) {

			$whereSql['id'] = $uid;
			$result         = M( 'member' )->where( $whereSql )->setField( 'role', '' );

			//同步删除对应管理员用户
			$SystemManager = new \Admin\Controller\AjaxController();
			$result0       = $SystemManager->memberDeleteAsyn( $uid, 'agent' );

			//同步更新定时结算状态(更新为未激活)
			$service_clearing_info = $ServiceClearing->where( 'user_id=' . $uid )->find();
			if ( $service_clearing_info ) {
				$data_service_celaring = [
					'clearing_status' => 0,
					'clearing_uptime' => time()
				];
				$result2               = $ServiceClearing->where( 'user_id=' . $uid )->save( $data_service_celaring );
			}

			if ( $result === false || $result0 === false ) {
				$this->error( '取消区域合伙人失败,请稍后重试！', U( 'Admin/Member/memberList' ) );
				exit;
			}

			M()->commit();

			//操作记录
			$where_log['id'] = array( 'eq', $uid );
			$log_data        = $member->field( 'loginname,nickname' )->where( $where_log )->find();
			$this->success( '取消区域合伙人成功！', U( 'Admin/Member/memberList' ), false, '会员列表取消' . $log_data['username'] . '[' . $log_data['loginname'] . '][' . $log_data['nickname'] . ']会员的区域合伙人，降级为创客用户' );
			exit;

		}

		//设置区域合伙人
		if ( $company == 2 ) {

			$where['id']  = $uid;
			$data['role'] = 4;
			$data['menu'] = M( 'parameter', 'g_' )->where( 'id=1' )->getField( 'service_auth' );
			$result       = M( 'member', 'zc_' )->where( $where )->save( $data );

			//同步添加对应管理员用户
			$manager_data  = array(
				'uid'      => $uid,
				'group_id' => array( C( 'ROLE_MUST_LIST.agent' ) ),
				'type'     => 'agent',
			);
			$SystemManager = new \Admin\Controller\AjaxController();
			$result1       = $SystemManager->memberAddAsyn( $manager_data );

			if ( $result === false || $result1 === false ) {
				$this->error( '设置区域合伙人失败,请稍后重试！', U( 'Admin/Member/memberList' ) );
				exit;
			}

			M()->commit();

			//操作记录
			$where_log['id'] = array( 'eq', $uid );
			$log_data        = $member->field( 'loginname,nickname' )->where( $where_log )->find();
			$this->success( '设置区域合伙人成功！', U( 'Admin/Member/memberList' ), false, '会员列表升级' . $log_data['username'] . '[' . $log_data['loginname'] . '][' . $log_data['nickname'] . ']会员为区域合伙人' );
			exit;

		}

		$this->error( '操作失败！' );
		exit;
	}

	/**
	 * 会员明细
	 */
	public function memberBonusInfo() {
		$where = '';
		$type  = 2; //收支类型(0支出,1收入,2全部)

		$WithdrawModel = new WithdrawModel();
		$MiningModel = new MiningModel();

		//变量
		$uid           = $this->get['uid'];
		$page          = $this->get['p'] > 0 ? $this->get['p'] : 1;
		$page          = $this->get['action']=='exportData' ? false : $page;
		$balance_type  = $this->get['balance_type'];
		$start_time    = $this->get['start_time'];
		$end_time      = $this->get['end_time'];
		$trade_status  = $this->get['trade_status'];
		$bonus_type    = $this->get['bonus_type'];
		$currency_type = empty( $this->get['member_cash'] ) ? 'cash' : $this->get['member_cash'];
		$this->assign( 'member_cash', $currency_type );
		//验证变量
		if ( empty( $uid ) ) {
			$this->error( 'UID参数格式有误' );
		}

		//对变量进行处理
		$start_time = ! empty( $start_time ) ? strtotime( $start_time . ' 00:00:00' ) : strtotime( date( 'Y-m-01' ) );
		$end_time   = ! empty( $end_time ) ? strtotime( $end_time . ' 23:59:59' ) : time();

		//针对用户账户明细[收入支出]筛选条件进行处理
		if ( $balance_type == 'income' ) {
			$type = 1;
		} elseif ( $balance_type == 'expense' ) {
			$type = 0;
		}

		//日期筛选
		$month = date( 'Ym' );
		if ( ! empty( $start_time ) ) {
			$where .= " and record_addtime>='{$start_time}' ";
			$month = date( 'Ym', $start_time );
		} else {
			$where .= " and record_addtime>='" . strtotime( date( 'Ym' ) . '01' ) . "' ";
		}
		if ( ! empty( $end_time ) ) {
			$where .= " and record_addtime<='{$end_time}' ";
			$month = date( 'Ym', $start_time );
		} else {
			$where .= " and record_addtime<='" . strtotime( date( 'Ymd' ) . ' 23:59:59' ) . "' ";
		}
		if ( date( 'Ym', $start_time ) != date( 'Ym', $end_time ) ) {
			$this->error( '查询日期必须在同一个月' );
		}


		//针对用户账户明细[收支类型]筛选条件进行处理
		if ( ! empty( $bonus_type ) ) {
			$where .= " and record_action='{$bonus_type}' ";
		}

		//获取配置参数
		$parameter = M( 'parameter', 'g_' )->where( 'id=1' )->find();
		$this->assign( 'parameter', $parameter );

		$member_cash = empty( $_GET['member_cash'] ) ? 'goldcoin' : $_GET['member_cash'];  //账户类型

		$currency = '';
		switch ( $currency_type ) {
			case 'cash':
				$currency = Currency::Cash;
				break;
			case 'colorcoin':
				$currency = Currency::ColorCoin;
				break;
			case 'points':
				$currency = Currency::Points;
				break;
			case 'bonus':
				$currency = Currency::Bonus;
				break;
			case 'enroll':
				$currency = Currency::Enroll;
				break;
			case 'credits':
				$currency = Currency::Credits;
				break;
			case 'supply':
				$currency = Currency::Supply;
				break;
			case 'enjoy':
				$currency = Currency::Enjoy;
				break;
//			case 'redelivery':
//				$currency = Currency::Redelivery;
//				break;
			default:
				$currency = Currency::GoldCoin;
		}

		$AccountRecord = new AccountRecordModel();
		$data          = $AccountRecord->getPageList( $uid, $currency, $month, $page, $type, 25, $where, false );

		$list = $data['list'];
		$export_data = []; //导出数据
		foreach ( $list as $k => $v ) {
			$attach      = json_decode( $v['record_attach'], true );
			$attach_init = $AccountRecord->initAtach( $attach, $currency, $month, $v['record_id'], $v['record_action'] );
			if ( ! isset( $attach_init['from_uid'] ) || $attach_init['from_uid'] == '1' ) {
				$list[ $k ]['from_name'] = $attach_init['from_name'];
			} else {
				$member_from_info = M( 'Member' )->where( 'id=' . $attach_init['from_uid'] )->field( 'loginname,nickname,username' )->find();
				if ( $member_from_info ) {
					$list[ $k ]['from_name'] = $member_from_info['username'] . '[' . $member_from_info['nickname'] . '][' . $member_from_info['loginname'] . ']';
				} else {
					$list[ $k ]['from_name'] = '该用户已不存在';
				}
			}

			//对收支类型文字说明进行处理
			$list[ $k ]['record_remark'] = CurrencyAction::getLabel( $v['record_action'] );
			
			//针对record_action=151的明细: 若record_remark='转出到锁定通证',则返回字段的action='转出到锁定通证'
			if ($v['record_action'] == '151' && $v['record_remark'] == '转出到锁定通证') {
				$list[$k]['record_remark'] = '转出到锁定通证';
			}
			//针对record_action=101的明细: 若record_remark='恢复锁定通证',则返回字段的action='恢复锁定通证'
			if ($v['record_action'] == '101' && $v['record_remark'] == '恢复锁定通证') {
				$list[$k]['record_remark'] = '恢复锁定通证';
			}

			//对提现进行状态的进度处理
//			if ( ( $v['record_action'] == CurrencyAction::CashTixian || $v['record_action'] == CurrencyAction::CashTixianShouxufei ) && ! empty( $attach['serial_num'] ) ) {
//				$withdraw_status             = $WithdrawModel->getStatus( $attach['serial_num'] );
//				$list[ $k ]['record_remark'] .= "[{$withdraw_status}]";
//			}

			//导出数据封装
			$export_data[] = [
				$v['record_id'],
				$list[$k]['from_name'],
				$v['record_amount']>0 ? $v['record_amount'] : '0.0000',
				$v['record_amount']<0 ? $v['record_amount'] : '0.0000',
				$v['record_balance'],
				$v['record_remark'],
				date('Y-m-d H:i', $v['record_addtime'])
			];
		}
		$this->assign( "datalist", $list );

		$this->Page( $data['paginator']['totalPage'] * $data['paginator']['everyPage'], $data['paginator']['everyPage'], $this->get );
		
		//导出功能
		if ($this->get['action'] == 'exportData') {
			$head_array = array('ID', '来源', '收入', '支出', '停留余额', '收支类型', '时间');
			$this->exportData($head_array, $export_data);
		}

		//查询账户余额
		$AccountModel = new AccountModel();
		$account      = $AccountModel->getItemByUserId( $uid, $AccountModel->get5BalanceFields() );
		$this->assign( "account", $account );

		//查询用户信息
		$member_info = M( 'Member' )->where( 'id=' . $uid )->field( 'nickname,loginname,username' )->find();
		$this->assign( "member_info", $member_info );

		//累计现金币收益
		$AccountIncomeModel = new AccountIncomeModel();
		$total_income       = $AccountIncomeModel->getItemByUserId( $uid );
		$this->assign( 'total_income', $total_income );
		
		//农场数
		$portion = $MiningModel->getPortionNumber($uid);
		$this->assign('portion', $portion);
		
		//出局状态
		$consume = M('Consume')->field('is_out,dynamic_out')->where('user_id='.$uid)->find();
		$out_status = ($consume['is_out'] || $consume['dynamic_out']) ? '已出局' : '未出局';
		$this->assign('out_status', $out_status);
		
		$this->display();
	}

	/**
	 * 会员资金变动明细
	 */
	public function memberFinanceInfo() {
		$RecordAccountChange = M( 'RecordAccountChange' );

		$uid        = $this->get['uid'];
		$start_time = $this->get['start_time'];
		$end_time   = $this->get['end_time'];

		if ( empty( $uid ) ) {
			$this->error( 'UID参数格式有误' );
		}

		$map_change = array();

		$map_change['uid'] = array( 'eq', $uid );
		if ( ! empty( $start_time ) ) {
			$map_change['time'][] = array( 'egt', strtotime( $start_time . ' 00:00:00' ) );
		}
		if ( ! empty( $end_time ) ) {
			$map_change['time'][] = array( 'elt', strtotime( $end_time . ' 23:59:59' ) );
		}
		if ( count( $map_change['time'] ) > 0 ) {
			if ( isset( $map_change['time'][1] ) ) {
				$map_change['time'][] = 'and';
			} else {
				$map_change['time'] = $map_change['time'][0];
			}
		}

		$count = $RecordAccountChange->where( $map_change )->count();
		$limit = $this->Page( $count, 20, $this->get );

		//获取资金变动数据
		$list = M( 'RecordAccountChange' )->where( $map_change )->limit( $limit )->order( 'time desc,id desc' )->select();

		//对数据进行处理
		$list_new = array();
		$key_list = array( 'goldcoin', 'colorcoin', 'cash', 'points', 'bonus' );
		foreach ( $list as $k => $v ) {
			foreach ( $key_list as $k1 => $v1 ) {
				$change = sprintf( '%.4f', $v[ 'new_' . $v1 ] - $v[ 'old_' . $v1 ] );
				if ( $change > 0 ) {
					$list_new[ $k ][ $v1 ]['value'] = '+' . $change;
				} else {
					$list_new[ $k ][ $v1 ]['value'] = $change;
				}
				$list_new[ $k ][ $v1 ]['old'] = $v[ 'old_' . $v1 ];
				$list_new[ $k ][ $v1 ]['new'] = $v[ 'new_' . $v1 ];
			}
			$list_new[ $k ]['time'] = $v['time'];
		}
		$this->assign( 'list', $list_new );

		//查询账户余额
		$account = M( 'member' )->field( 'loginname,nickname,username,goldcoin,colorcoin,cash,points,bonus' )->find( $uid );
		$this->assign( "account", $account );

		$this->display();
	}

	/**
	 * 超级管理员身份模拟切换到其他账户一键登录
	 */
	public function superLogin() {
		$Member  = M( 'Member' );
		$Manager = D( 'Manager' );

		$member_id = $this->get['member_id'];

		if ( ! is_numeric( $member_id ) ) {
			$this->error( '参数格式有误' );
		}

		//判断当前账号是否为小管理员
		$is_small_super_manager = $this->isSmallSuperManager();

		$sess_auth = $this->get( 'sess_auth' );
		if ( $sess_auth['admin_id'] != 1 && ! $is_small_super_manager ) {
			$this->error( '非超级管理员/小管理员无权使用一键登录其他账号功能' );
		} else {
			$map_current['mem.id'] = array( 'eq', $sess_auth['admin_id'] );
			$current_manager       = $Manager->getMemberList( $map_current, false, 'm.nickname,m.loginname,m.username' );
		}

		$map_member['id'] = array( 'eq', $member_id );
		$member_info      = $Member->where( $map_member )->field( 'loginname,level,role,password,nickname,username' )->find();
		if ( ! $member_info ) {
			$this->error( '该账号已不存在' );
		} elseif ( $member_info['role'] <= 0 ) {
			$this->error( '该账号当前身份无权登录' );
		}

		//模拟登录
		$login_data   = array(
			'username' => $member_info['loginname'],
			'password' => $member_info['password'],
		);
		$login_status = $Manager->checkExist( $login_data );
		if ( $login_status['error'] === false ) {
			session( 'admin_super_login', $sess_auth['admin_id'] );
			$this->success( '登录成功', U( 'Admin/Index/index' ), false, "管理员{$current_manager['loginname']}[{$current_manager['nickname']}]成功以{$member_info['loginname']}[{$member_info['nickname']}]身份登录后台" );
		} else {
			$this->error( $login_status['data'] );
		}
	}

	/**
	 * 一键分配服务中心和区域合伙人管理员账号
	 */
	public function batchManager() {
		$this->error( '此功能已禁用' );
		//保证全部执行完成
		set_time_limit( 0 );
		ignore_user_abort( true );

		$Member = M( 'Member' );

		if ( session( 'admin_id' ) != 1 ) {
			$this->error( '无操作权限' );
		}

		$role_must_list = C( 'ROLE_MUST_LIST' );
		$Manager        = new \Admin\Controller\AjaxController();

		$error        = false;
		$serviceAgent = $Member
			->alias( 'mem' )
			->join( 'left join __MANAGER__ man ON man.uid=mem.id' )
			->where( 'mem.level>2 and mem.level<5 and mem.is_lock=0' )
			->field( 'mem.loginname,mem.level,mem.nickname,mem.username,mem.id mid,man.id' )
			->select();

		$type      = '';
		$role_name = '';
		foreach ( $serviceAgent as $k => $list ) {
			switch ( $list['level'] ) {
				case 3:
					$group_id  = array( $role_must_list['service'] );
					$role_name = '服务中心';
					$type      = 'service';
					break;
				case 4:
					$group_id  = array( $role_must_list['agent'] );
					$role_name = '区域合伙人';
					$type      = 'agent';
					break;
				default:
					$error .= "{$serviceAgent['loginname']}[{$serviceAgent['nickname']}]分配管理员账号非法.\r\n";
					continue;
			}

			//过滤掉已经分配过商家管理员角色的
			if ( isset( $list['id'] ) ) {
				$map_group_access['group_id'] = $group_id[0];
				$map_group_access['uid']      = array( 'eq', $list['id'] );
				$group_acess_info             = M( 'AuthGroupAccess' )->where( $map_group_access )->find();
				if ( $group_acess_info ) {
					continue;
				}
			}

			$manager_data = array(
				'uid'      => $list['mid'],
				'group_id' => $group_id,
				'type'     => $type,
			);

			$status = $Manager->memberAddAsyn( $manager_data );
			if ( ! $status ) {
				$error .= "{$serviceAgent['loginname']}[{$serviceAgent['nickname']}]分配[{$role_name}]管理员账号失败.\r\n";
			}
		}

		if ( $error ) {
			$this->error( "部分账号分配失败:\r\n{$error}", U( 'Member/memberList' ), 20 );
		} else {
			$this->success( '账号已全部分配成功', U( 'Member/memberList' ), false, "一键分配服务中心和区域合伙人管理员账号" );
		}

		exit;
	}

	/**
	 * 会员加入/移出黑名单
	 */
	public function memberBlackList() {
		$Member = M( 'member' );

		$id_list        = $this->post['id'];
		$blacklist_type = $this->post['blacklist_type'];

		if ( empty( $id_list ) ) {
			exit( '请选择要操作的用户' );
		}

		//判断要设置的黑名单类型是否存在
		$blacklist_type_config = C( 'FIELD_CONFIG.member' )['is_blacklist'];
		if ( ! array_key_exists( $blacklist_type, $blacklist_type_config ) ) {
			exit( '操作的黑名单类型不存在' );
		}

		M()->startTrans();
		$map_member['id'] = array( 'in', implode( ',', $id_list ) );
		$data_member      = array(
			'is_blacklist' => $blacklist_type,
		);
		if ( $Member->where( $map_member )->save( $data_member ) === false ) {
			exit( '批量设置用户黑名单类型失败' );
		} else {
			M()->commit();
			$this->logWrite( "批量设置用户黑名单类型为[{$blacklist_type_config[$blacklist_type]}黑名单]" );
			exit;
		}
	}

	/**
	 * 会员累计收益明细
	 */
	public function memberIncomeInfo() {
		$uid = $this->get['uid'];

		if ( ! validateExtend( $uid, 'NUMBER' ) ) {
			$this->error( 'UID参数格式有误' );
		}

		//查询账户
		$account = M( 'member' )->field( 'loginname,nickname,username' )->find( $uid );
		$this->assign( "account", $account );

		$AccountFinanceModel = new AccountFinanceModel();

		//累计通证汇总+累计当月通证汇总
		$income['total_income'] = $AccountFinanceModel->getItemByUserId( $uid, 'finance_total' );
		$income['month_income'] = $AccountFinanceModel->getItemByUserId( $uid, 'finance_total', date( 'Ym' ) );
		$this->assign( 'income', $income );

		//月收益列表
		$income_month_list = $AccountFinanceModel->getListByUserId( $uid, '*', date( 'Ym' ) );
		$this->assign( 'list', $income_month_list );

		$this->display();
	}

	/**
	 * 业绩查询
	 */
	public function performance() {
		if ( $_POST ) {
			$this->performanceAction();
			$this->logWrite( "操作了业绩查询" );
		}

		$this->display();
	}

	/**
	 * 业绩查询执行
	 */
	private function performanceAction() {
		$Member = M( 'Member' );
		$Orders = M( 'Orders' );

		$phone1   = $this->post['phone1'];
		$phone2   = $this->post['phone2'];
		$time_min = $this->post['time_min'];
		$time_max = $this->post['time_max'];

		if ( ! validateExtend( $phone1, 'MOBILE' ) ) {
			$this->error( '主号格式有误', U( __CONTROLLER__ . '/performance' ) );
		}
		if ( ! empty( $phone2 ) ) {
			$phone2_arr = preg_match( '/\s/', $phone2 ) ? explode( ' ', $phone2 ) : array( $phone2 );
			foreach ( $phone2_arr as $ph ) {
				if ( ! validateExtend( $ph, 'MOBILE' ) ) {
					$this->error( '截止号格式有误', U( __CONTROLLER__ . '/performance' ) );
				}
			}

			$phone2 = preg_replace( '/\s/', ',', $phone2 );
		}

		$time_min = empty( $time_min ) ? null : strtotime( $time_min . ' 00:00:00' );
		$time_max = empty( $time_max ) ? null : strtotime( $time_max . ' 23:59:59' );

		$map           = [];
		$map_repath    = [];
		$assign_phone1 = [];
		$assign_phone2 = [];

		//查询日期
		if ( ! empty( $time_min ) ) {
			$map['ord.time'][] = array( 'egt', $time_min );
		}
		if ( ! empty( $time_max ) ) {
			$map['ord.time'][] = array( 'elt', $time_max );
		}
		if ( count( $map['ord.time'] ) > 0 ) {
			if ( count( $map['ord.time'] ) > 1 ) {
				$map['ord.time'][] = 'and';
			} else {
				$map['ord.time'] = $map['ord.time'][0];
			}
		}

		//查询$phone1的ID
		$map_phone1['loginname'] = array( 'eq', $phone1 );
		$phone1_info             = $Member->where( $map_phone1 )->field( 'id,nickname' )->find();
		if ( ! $phone1_info ) {
			$this->error( '主号不存在', U( __CONTROLLER__ . '/performance' ) );
		}
		$map_repath['mem.repath'][] = array( 'like', "%,{$phone1_info['id']},%" );
		$assign_phone1              = [ $phone1 => $phone1_info['nickname'] ];

		//查询$phone2的ID[列表]
		if ( ! empty( $phone2 ) ) {
			$map_phone2['loginname'] = array( 'in', $phone2 );
			$phone2_info             = $Member->where( $map_phone2 )->field( 'id,nickname,loginname,repath' )->select();
			foreach ( $phone2_info as $k => $ph ) {
				$map_repath['mem.repath'][] = array( 'notlike', "%,{$ph['id']},%" );
				$map_repath['mem.id'][]     = array( 'neq', $ph['id'] );

				//查询该帐号是否与主号为同一条线
				$is_line         = preg_match( '/(^|,)' . $phone1_info['id'] . '($|,)/', $ph['repath'] ) ? true : false;
				$assign_phone2[] = [ $ph['loginname'] => [ 'nickname' => $ph['nickname'], 'is_line' => $is_line ] ];

			}
			$map_repath['mem.repath'][] = 'and';
			$map_repath['mem.id'][]     = 'and';
		} else {
			$map_repath['mem.repath'] = $map_repath['mem.repath'][0];
		}

		$temp['_complex'] = $map_repath;
		$temp['mem.id']   = array( 'eq', $phone1_info['id'] );
		$temp['_logic']   = 'or';

		$map['_complex'] = $temp;

		//查询兑换额
		$map['ord.order_status'] = array( 'eq', 4 );
		$map['ord.amount_type']  = array( 'in', '1,5' );
		$map['ord.dutypay']      = array( 'eq', 0 );
		$trade_money             = $Member
			->alias( 'mem' )
			->join( 'join __ORDERS__ ord ON ord.uid=mem.id' )
			->join( 'join __PROFITS_BONUS__ prb ON prb.order_number=ord.order_number' )
			->where( $map )
			->sum( 'ord.amount-prb.profits' );
		$this->assign( 'profits_money', sprintf( '%.2f', $trade_money ) );

		$this->assign( 'assign_phone1', $assign_phone1 );
		$this->assign( 'assign_phone2', $assign_phone2 );
	}

	/**
	 * 导出账户明细
	 */
	public function memberBonusInfoExportAction() {
		$where = '';
		$type  = 2; //收支类型(0支出,1收入,2全部)

		//变量
		$uid           = $this->get['uid'];
		$page          = $this->get['p'] > 0 ? $this->get['p'] : 1;
		$balance_type  = $this->get['balance_type'];
		$start_time    = $this->get['start_time'];
		$end_time      = $this->get['end_time'];
		$trade_status  = $this->get['trade_status'];
		$bonus_type    = $this->get['bonus_type'];
		$currency_type = $this->get['member_cash'];

		//验证变量
		if ( empty( $uid ) ) {
			$this->error( 'UID参数格式有误' );
		}

		//对变量进行处理
		$start_time = ! empty( $start_time ) ? strtotime( $start_time . ' 00:00:00' ) : strtotime( date( "Y-m-01" ) );
		$end_time   = ! empty( $end_time ) ? strtotime( $end_time . ' 23:59:59' ) : time();

		//针对用户账户明细[收入支出]筛选条件进行处理
		if ( $balance_type == 'income' ) {
			$type = 1;
		} elseif ( $balance_type == 'expense' ) {
			$type = 0;
		}

		//日期筛选
		$month = date( 'Ym' );
		if ( ! empty( $start_time ) ) {
			$where .= " and record_addtime>='{$start_time}' ";
			$month = date( 'Ym', $start_time );
		} else {
			$where .= " and record_addtime>='" . strtotime( date( 'Ym' ) . '01' ) . "' ";
		}
		if ( ! empty( $end_time ) ) {
			$where .= " and record_addtime<='{$end_time}' ";
			$month = date( 'Ym', $start_time );
		} else {
			$where .= " and record_addtime<='" . strtotime( date( 'Ymd' ) . ' 23:59:59' ) . "' ";
		}
		if ( date( 'Ym', $start_time ) != date( 'Ym', $end_time ) ) {
			$this->error( '查询日期必须在同一个月' );
		}

		//不启用分页
		$page = false;

		//针对用户账户明细[收支类型]筛选条件进行处理
		if ( ! empty( $bonus_type ) ) {
			$where .= " and record_action='{$bonus_type}' ";
		}

		//获取配置参数
		$parameter = M( 'parameter', 'g_' )->where( 'id=1' )->find();
		$this->assign( 'parameter', $parameter );

		$member_cash = empty( $_GET['member_cash'] ) ? 'cash' : $_GET['member_cash'];  //账户类型

		$currency = '';
		switch ( $currency_type ) {
			case 'goldcoin':
				$currency = Currency::GoldCoin;
				break;
			case 'colorcoin':
				$currency = Currency::ColorCoin;
				break;
			case 'points':
				$currency = Currency::Points;
				break;
			case 'bonus':
				$currency = Currency::Bonus;
				break;
			case 'enroll':
				$currency = Currency::Enroll;
				break;
			case 'credits':
				$currency = Currency::Credits;
				break;
			case 'supply':
				$currency = Currency::Supply;
				break;
			case 'enjoy':
				$currency = Currency::Enjoy;
				break;
			default:
				$currency = Currency::Cash;
		}

		$AccountRecord = new AccountRecordModel();
		$data          = $AccountRecord->getPageList( $uid, $currency, $month, $page, $type, 10, $where );

		$list = $data['list'];
		foreach ( $list as $k => $v ) {
			$attach                  = json_decode( $v['record_attach'], true );
			$attach                  = $AccountRecord->initAtach( $attach, $currency, $month, $v['record_id'], $v['record_action'] );
			$list[ $k ]['from_name'] = $attach['from_name'] . ( empty( $attach['loginname'] ) ? '' : "[{$attach['loginname']}]" );
		}

		$export_data = [];
		foreach ( $list as $k => $v ) {
			$export_data[ $k ] = [
				$v['record_id'],
				$v['from_name'],
				$v['record_amount'] > 0 ? $v['record_amount'] : 0,
				$v['record_amount'] < 0 ? $v['record_amount'] : 0,
				$v['record_remark'],
				$v['record_balance'],
				date( 'Y-m-d H:i:s', $v['record_addtime'] ),
			];
		}

		$head_array = array( '序号', '来源', '收入', '支出', '收支类型', '停留余额', '时间' );
		$file_name  .= '账户管理数据-' . date( 'Y-m-d' );
		$file_name = iconv( "utf-8", "gbk", $file_name );
		$return    = $this->xlsExport( $file_name, $head_array, $export_data );
		! empty( $return['error'] ) && $this->error( $return['error'] );

		$this->logWrite( "导出账户[ID:{$uid}]的明细数据" );
	}

	/**
	 * 用户业绩
	 */
	public function memberPerformanceInfo() {
		$uid = $this->get['uid'];

		//查询用户信息
		if ($uid > 0) {
			$member_info = M( 'Member' )->where( 'id=' . $uid )->field( 'nickname,loginname' )->find();
		} else {
			$member_info = [
				'nickname' => '系统',
				'loginname' => '平台'
			];
		}
		$this->assign( "member_info", $member_info );
		
		//总业绩
		$performance_score = M('Performance')->where("user_id={$uid} and performance_tag=0")->getField('performance_amount');
		$this->assign('performance_score', $performance_score);

		//每月业绩列表
		$performanceList = M( 'Performance' )->where( "user_id={$uid}" )->order( 'performance_tag desc' )->select();

		foreach ( $performanceList as $key => $item ) {
			if ( $item['performance_tag'] < 201800 ) {
				$this->assign( 'performance_' . $item['performance_tag'], $item['performance_amount'] );
				unset( $performanceList[ $key ] );
			}
		}

		$this->assign( 'list', $performanceList );

		$this->display();
	}
	
	/**
	 * 业绩明细
	 */
	public function memberPerformanceDetails() {
		$id = $this->get['id'];
		$page = empty($this->get['page']) ? 1 : $this->get['page'];
		
		if (!validateExtend($id, 'NUMBER')) {
			$this->error('参数格式有误');
		}
		
		$performance_info = M('Performance')->where("performance_id={$id}")->field('performance_tag,user_id')->find();
		if (!$performance_info) {
			$this->error('数据获取失败');
		}
		$this->assign('performance_tag', $performance_info['performance_tag']);
		
		$PerformanceModel = new PerformanceModel();
		
		$where['user_id'] = array('eq', $performance_info['user_id']);
		$data = $PerformanceModel->getList($performance_info['performance_tag'], '', $where, $page, 20);
		
		$list = $data['list'];
		$this->assign('list', $list);
		
		$this->Page($data['paginator']['totalRows'], $data['paginator']['everyPage'], $this->get);
		
		$this->display();
	}
	
}
?>