<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 财务管理
// +----------------------------------------------------------------------
namespace Admin\Controller;

use Common\Controller\AuthController;
use V4\Model\FinanceModel;
use V4\Model\AccountFinanceModel;
use V4\Model\AccountRecordModel;
use V4\Model\Currency;
use V4\Model\CurrencyAction;
use V4\Model\Tag;
use V4\Model\AccountModel;
use V4\Model\AwardFinanceModel;
use V4\Model\LockModel;
use V4\Model\ProcedureModel;

class FinanceController extends AuthController {

	protected $cgbPay;

	public function __construct() {
		parent::__construct();

		Vendor( "CgbPay.CgbPay#Api" ); //广发银企直联基础组件
		$this->cgbPay = new \CgbPayApi();

		C( 'TOKEN_ON', false );
	}

	/**
	 * 拨比
	 */
	public function ratio() {
		$time_min = $this->get['time_min'];
		$time_max = $this->get['time_max'];
		$page     = $this->get['p'] > 0 ? $this->get['p'] : 1;

		$where = '';

		if ( ! empty( $time_min ) ) {
			$time_min = date( 'Ymd', strtotime( $time_min ) );
			$where    .= " and finance_tag>='{$time_min}' ";
		}
		if ( ! empty( $time_max ) ) {
			$time_max = date( 'Ymd', strtotime( $time_max ) );
			$where    .= " and finance_tag<='{$time_max}' ";
		}

		$FinanceModel = new FinanceModel();
		$data         = $FinanceModel->getPageList( '
		    finance_profits,
		    finance_profits_colorcoin,
		    finance_maker,
		    finance_expenditure,
		    finance_withdraw_fee,
			finance_applymicrovip,
		    finance_applyvip,
			finance_applyhonourvip,
		    finance_tax_colorcoin,
		    (finance_tax_goldcoin+finance_tax_colorcoin+finance_tax_cash+finance_tax_enroll+finance_tax_supply+finance_tax_credits) finance_tax,
		    finance_managefee_colorcoin,
		    (finance_managefee_goldcoin+finance_managefee_colorcoin+finance_managefee_cash+finance_managefee_enroll+finance_managefee_supply+finance_managefee_credits) finance_managefee,
		    finance_tag
		', date( 'Ymd' ), $page, 10, $where );

		$_info = $data['list'];
		$this->assign( 'info', $_info );

		$this->Page( $data['paginator']['totalPage'] * $data['paginator']['everyPage'], $data['paginator']['everyPage'], $this->get );

		//统计
		if ( empty( $where ) ) {
			$_total = $FinanceModel->getItem( '
			    finance_profits,
			    finance_profits_colorcoin,
			    finance_maker,
			    finance_expenditure,
			    finance_withdraw_fee,
				finance_applymicrovip,
			    finance_applyvip,
				finance_applyhonourvip,
			    finance_tax_colorcoin,
			    (finance_tax_goldcoin+finance_tax_colorcoin+finance_tax_cash+finance_tax_enroll+finance_tax_supply+finance_tax_credits) finance_tax,
			    finance_managefee_colorcoin,
		        (finance_managefee_goldcoin+finance_managefee_colorcoin+finance_managefee_cash+finance_managefee_enroll+finance_managefee_supply+finance_managefee_credits) finance_managefee
			' );
		} else {
			$_total = $FinanceModel->getFieldsValues( '
			    sum(finance_profits) finance_profits,
			    sum(finance_profits_colorcoin) finance_profits_colorcoin,
			    sum(finance_maker) finance_maker,
			    sum(finance_expenditure) finance_expenditure,
			    sum(finance_withdraw_fee) finance_withdraw_fee,
				sum(finance_applymicrovip) finance_applymicrovip,
			    sum(finance_applyvip) finance_applyvip,
				sum(finance_applyhonourvip) finance_applyhonourvip,
			    sum(finance_tax_colorcoin) finance_tax_colorcoin,
			    sum(finance_tax_goldcoin+finance_tax_colorcoin+finance_tax_cash+finance_tax_enroll+finance_tax_supply+finance_tax_credits) finance_tax,
			    sum(finance_managefee_colorcoin) finance_managefee_colorcoin,
			    sum(finance_managefee_goldcoin+finance_managefee_colorcoin+finance_managefee_cash+finance_managefee_enroll+finance_managefee_supply+finance_managefee_credits) finance_managefee
			', '1 ' . $where );
		}
		$this->assign( 'total', $_total );

		$this->display();
	}

	/**
	 * 奖金记录
	 */
	public function bonus() {
		$sqlkey = ' 1 ';

		$b_time = $this->get['time_min'];
		$e_time = $this->get['time_max'];
		$page   = $this->get['p'] > 0 ? $this->get['p'] : 1;

		if ( ! empty( $b_time ) ) {
			$b_time = date( 'Ymd', strtotime( $b_time ) );
		} else {
			$b_time = date( 'Ym' ) . '01';
		}
		if ( ! empty( $e_time ) ) {
			$e_time = date( 'Ymd', strtotime( $e_time ) );
		} else {
			$e_time = date( 'Ymd' );
		}
		if ( ! empty( $b_time ) && ! empty( $e_time ) ) {
			if ( substr( $b_time, 0, 6 ) != substr( $e_time, 0, 6 ) ) {
				$this->error( '筛选日期需在同一个月' );
			}
		}
		$sqlkey .= " and income_tag>='{$b_time}' and income_tag<='{$e_time}' ";

		$AwardFinanceModel = new AwardFinanceModel();
		$fields            = '*';
		$data              = $AwardFinanceModel->getPageList( $page, 20, $fields, $sqlkey );

		$list = $data['list'];
		$this->assign( 'list', $list );

		$this->Page( $data['paginator']['totalPage'] * $data['paginator']['everyPage'], $data['paginator']['everyPage'], $this->get );

		$this->display();
	}

	/**
	 * 日奖金记录
	 */
	public function bonusDay() {
		$sqlkey = ' and mem.id>1 and mem.level<99 ';

		$day  = $this->get['day'];
		$page = $this->get['p'] > 0 ? $this->get['p'] : 1;

		if ( empty( $day ) ) {
			$this->error( '参数有误' );
		}

		$tag_day = date( 'Ymd', $day );
		$sqlkey  .= " and acf.finance_tag='{$tag_day}' ";

		//排除非当前管理员的下线,除了超管和小管理员
		$is_small_super = $this->isSmallSuperManager();
		if ( ! $is_small_super && session( "admin_id" ) != 1 ) {
			if ( session( 'admin_level' ) == 3 ) {
				$filter_member = $this->filterMember( session( 'admin_mid' ), false, 'mem', array( 'mem.repath' => "(mem.repath like '%," . session( 'admin_mid' ) . ",%' or mem.id=" . session( 'admin_mid' ) . ")" ), 'string' );
				$sqlkey        .= " and {$filter_member['mem.repath']} and {$filter_member['mem.id']}  ";
			}
			if ( session( 'admin_level' ) == 4 ) {
				$filter_member = $this->filterMember( session( 'admin_mid' ), false, 'mem', array( 'mem.repath' => "(mem.repath like '%," . session( 'admin_mid' ) . ",%' or mem.id=" . session( 'admin_mid' ) . ")" ), 'string' );
				$sqlkey        .= " and {$filter_member['mem.repath']} and {$filter_member['mem.id']}  ";
			}
		}

		//排除全部奖项为空的用户
		$sqlkey .= " and (
		      acf.finance_cash_marketsubsidy > 0
		      or acf.finance_cash_companysubsidy > 0
		      or acf.finance_cash_uniondelivery > 0
		      or acf.finance_cash_merchant > 0
		      or acf.finance_cash_dutyconsume > 0
		      or acf.finance_cash_repeat > 0
		    ) ";

		$AccountFinanceModel = new AccountFinanceModel();
		$fields              = [
			'acf.user_id',
			'acf.finance_tag',
			'sum(acf.finance_cash_marketsubsidy) finance_cash_marketsubsidy',
			'sum(acf.finance_cash_companysubsidy) finance_cash_companysubsidy',
			'sum(acf.finance_cash_uniondelivery) finance_cash_uniondelivery',
			'sum(acf.finance_cash_merchant) finance_cash_merchant',
			'sum(acf.finance_cash_dutyconsume) finance_cash_dutyconsume',
			'sum(acf.finance_cash_repeat) finance_cash_repeat',
			'sum(acf.finance_cash_marketsubsidy+acf.finance_cash_companysubsidy+acf.finance_cash_uniondelivery+acf.finance_cash_merchant+acf.finance_cash_dutyconsume+acf.finance_cash_repeat) finance_sum',
		];
		$data                = $AccountFinanceModel->getListByAllUser( $fields, date( 'Ym', $day ), $page, 20, $sqlkey, 'acf.user_id' );

		$list = $data['list'];
		foreach ( $list as $k => $v ) {
			$member_info = M( 'Member' )->where( 'id=' . $v['user_id'] )->field( 'loginname,nickname' )->find();
			if ( $member_info ) {
				$list[ $k ] = array_merge( $v, $member_info );
			}
		}
		$this->assign( 'list', $list );

		$this->Page( $data['paginator']['totalPage'] * $data['paginator']['everyPage'], $data['paginator']['everyPage'], $this->get );

		$this->display();
	}

	/**
	 * 奖金明细
	 */
	public function bonusInfo() {
		//暂不启用
		$this->error( '暂未启用' );
	}

	/**
	 * 提现管理
	 */
	public function withdraw() {
		$searchKey = [];

		//判断当前管理员是否具有小管理员权限
		$is_small_super = $this->isSmallSuperManager();
		$this->assign( 'is_small_super', $is_small_super );

		$money     = $this->get['money'];
		$userid    = $this->get['userid'];
		$is_pass   = $this->get['is_pass'];
		$time_min  = $this->get['time_min'];
		$time_max  = $this->get['time_max'];
		$tiqu_type = intval( $this->get['tiqu_type'] );

		$is_submit        = $this->get['is_submit'];
		$page             = $this->get['p'] > 0 ? $this->get['p'] : 1;
		$search_date_type = $this->get['search_date_type'];

		if ( validateExtend( $money, 'MONEY' ) ) {
			$searchKey['wc.money'] = array( 'eq', $money );
		}
		if ( ! empty( $userid ) && ! validateExtend( I( 'get.userid' ), 'NUMBER' ) && ! validateExtend( I( 'get.userid' ), 'CHS' ) && ! validateExtend( I( 'get.userid' ), 'USERNAME' ) ) {
			$this->error( '会员账号格式有误' );
		}

		if ( ! empty( $userid ) ) {
			$map_member['loginname'] = array( 'like', '%' . $userid . '%' );
			$map_member['nickname']  = array( 'like', '%' . $userid . '%' );
			$map_member['username']  = array( 'like', '%' . $userid . '%' );
			$map_member['_logic']    = 'OR';
			$member_info             = M( 'member', 'zc_' )->where( $map_member )->field( 'id' )->select();
			if ( ! $member_info ) {
				$this->error( '用户不存在！' );
			} else {
				foreach ( $member_info as $k => $list ) {
					$uid[] = $list['id'];
				}
				$uid = implode( ',', $uid );
			}
			$searchKey['wc.uid'] = array( 'in', $uid );
		}

		if ( ! empty( $is_pass ) ) {
			$searchKey['wc.is_pass'] = array( 'eq', $is_pass );
		}

		//时间类型筛选
		$time_column = 'wc.add_time';
		if ( $search_date_type == '2' ) {
			$time_column = 'wc.submit_flag';
		} elseif ( $search_date_type == '3' ) {
			$time_column = 'wc.finish_time';
		}
		if ( ! empty( $time_min ) && ! empty( $time_max ) ) {
			$searchKey[ $time_column ] = array(
				'between',
				array( strtotime( $time_min ), strtotime( $time_max . ' 23:59:59' ) )
			);
		} elseif ( ! empty( $time_min ) ) {
			$searchKey[ $time_column ] = array( 'EGT', strtotime( $time_min ) );
		} elseif ( ! empty( $time_max ) ) {
			$searchKey[ $time_column ] = array( 'ELT', strtotime( $time_max . ' 23:59:59' ) );
		}

		// 强行设置为银行卡提现
		$tiqu_type = 2;

		//提现方式筛选条件
		switch ( $tiqu_type ) {
			case '0':
				$searchKey['wc.tiqu_type'] = array( 'eq', 0 );
				break;
			case '1':
				$searchKey['wc.tiqu_type'] = array( 'eq', 1 );
				break;
			case '2':
				$searchKey['wc.tiqu_type'] = array( 'eq', 2 );
				break;
			default:
				$searchKey['wc.tiqu_type'] = array( 'eq', 1 );
		}

		$this->assign( 'tiqu_type', $tiqu_type );

		switch ( $is_submit ) {
			case 'S':
				$searchKey['wc.status'] = array( 'eq', 'S' );
				break;
			case 'F':
				$searchKey['wc.status'] = array( 'eq', 'F' );
				break;
			case 'N':
				$searchKey['wc.status']      = array( 'eq', '0' );
				$searchKey['wc.submit_flag'] = array( 'eq', '0' );
				break;
			case 'TS':
				$searchKey['wc.status'] = array( 'eq', 'TS' );
				break;
			case 'TF':
				$searchKey['wc.status'] = array( 'eq', 'TF' );
				break;
			case 'L':
				$searchKey['wc.status']      = array( 'eq', '0' );
				$searchKey['wc.submit_flag'] = array( 'neq', '0' );
				break;
			case 'W':
				$searchKey['wc.status'] = array( 'eq', 'W' );
				break;
			default:
				//$searchKey['wc.submit_flag'] = array( 'eq', '0' );
		}
		$this->assign( 'success_status', $is_submit );

		//当 [提交状态]为成功转账/失败处理  + [用户帐号]为空 + [日期]为空  时,则默认不调取全部数据
		if ( ( I( 'get.is_submit' ) == 'S' || I( 'get.is_submit' ) == 'F' ) && I( 'get.userid' ) == '' && I( 'get.time_min' ) == '' && I( 'get.time_max' ) == '' ) {
			//$searchKey['wc.id'] = array( 'eq', 0 );
		}

		$withdraw = M( 'WithdrawCash' );
		$count    = $withdraw
			->alias( 'wc' )
			->where( $searchKey )
			->join( 'left join zc_member m on m.id=wc.uid' )
			->field( 'wc.id' )
			->count();

		$_info = $withdraw
			->alias( 'wc' )
			->join( 'left join zc_member m on m.id=wc.uid' )
			->join( 'left join zc_user_affiliate aff on aff.user_id=wc.uid' )
			->field( 'wc.*
					,m.store_flag,m.level,m.loginname,m.nickname,m.is_blacklist,m.username
					,aff.alipay_account' )
			->where( $searchKey )
			->order( 'unix_timestamp(wc.finish_time) desc,wc.id desc' )
			->page( $page, 20 )
			->select();

		//关联查询绑定银行卡信息
		if ( $tiqu_type == '2' ) {
			foreach ( $_info as $k => $v ) {
// 				$bind_bank_info = M( 'WithdrawBankcard' )->where( 'uid=' . $v['uid'] )->field( 'inaccname,inacc,inaccbank,inaccadd' )->find();
				$bind_bank_info = M('BankBind')->where('user_id='.$v['uid'])->field('`name` inaccname,`cardNo` inacc, `bankName` inaccbank, `bankAddress` inaccadd')->find();
				if ($bind_bank_info) {
					$_info[$k] = array_merge($v, $bind_bank_info);
				}
			}
		}
		$this->assign( 'info', $_info );

		$this->Page( $count, 20, $this->get );

		//统计
		$total_withdraw = $withdraw
			->alias( 'wc' )
			->join( 'join zc_member m on m.id=wc.uid' )
			->where( $searchKey )
			->field( "sum(wc.amount) amount, sum(wc.commission) commission" )
			->find();
		$this->assign( 'total_withdraw', $total_withdraw );

		$this->display();
	}

	/**
	 * 后台充值
	 */
	public function memberCash() {
		$MemberAccount = M( 'member_account' );

		//判断当前管理员是否具有小管理员权限
		$is_small_super = $this->isSmallSuperManager();
		$this->assign( 'is_small_super', $is_small_super );

		$searchKey = array();

		$username = $this->get['username'];
		$time_min = $this->get['time_min'];
		$time_max = $this->get['time_max'];

		if ( ! empty( $username ) ) {
			if ( ! validateExtend( $username, 'NUMBER' ) && ! validateExtend( $username, 'CHS' ) && ! validateExtend( $username, 'USERNAME' ) ) {
				$this->error( '会员账号格式有误' );
			}

			$map_member['mem.loginname'] = array( 'eq', $username );
			$map_member['mem.nickname']  = array( 'eq', $username );
			$map_member['mem.username']  = array( 'eq', $username );
			$map_member['_logic']        = 'OR';
			$searchKey['_complex']       = $map_member;
		}

		if ( ! empty( $time_min ) ) {
			$searchKey['ma.ac_post_time'][] = array( 'egt', strtotime( $time_min . ' 00:00:00' ) );
		}
		if ( ! empty( $time_max ) ) {
			$searchKey['ma.ac_post_time'][] = array( 'elt', strtotime( $time_max . '23:59:59' ) );
		}

		//对特殊用户的部分明细进行隐藏处理
		$special_member = []; //[3140,3141,4031,94951];
		if ( ! empty( $map_member ) ) {
			$uid = M( 'Member' )->alias( 'mem' )->where( $map_member )->getField( 'id' );
			if ( in_array( $uid, $special_member ) ) {
				$searchKey['ma.ac_post_time'][] = array( 'elt', strtotime( '2017-07-23 00:00:00' ) );
			}
		} else {
			$searchKey['ma.ac_uid'] = array( 'not in', implode( ',', $special_member ) );
		}

		if ( count( $searchKey['ma.ac_post_time'] ) == 1 ) {
			$searchKey['ma.ac_post_time'] = $searchKey['ma.ac_post_time'][0];
		}

		$count = $MemberAccount
			->alias( 'ma' )
			->join( 'left join __MEMBER__ mem ON mem.id=ma.ac_uid' )
			->where( $searchKey )
			->count();
		$limit = $this->Page( $count, 20, $this->get );

		//整合数据
		$export_data = []; //导出数据
		$m_account = $MemberAccount
			->alias( 'ma' )
			->join( 'left join __MEMBER__ mem ON mem.id=ma.ac_uid' )
			->where( $searchKey )
			->order( 'ma.ac_post_time desc' )
			->limit( $limit )
			->group( 'ma.ac_id' )
			->select();
		foreach ($m_account as $k=>$v) {
			$ac_type_cn = '';
			if ($v['ac_type'] == 300 || $v['ac_type'] == 350) {
				$ac_type_cn = '现金币';
			} elseif ($v['ac_type'] == 101 || $v['ac_type'] == 151) {
				$ac_type_cn = '丰谷宝';
			} elseif ($v['ac_type'] == 201 || $v['ac_type'] == 251) {
				$ac_type_cn = '锁定通证';
			} elseif ($v['ac_type'] == 501 || $v['ac_type'] == 551) {
				$ac_type_cn = '矿池';
			} elseif ($v['ac_type'] == 401 || $v['ac_type'] == 451) {
				$ac_type_cn = '提货券';
			} elseif ($v['ac_type'] == 601 || $v['ac_type'] == 651) {
				$ac_type_cn = '兑换券';
			} elseif ($v['ac_type'] == 10001 || $v['ac_type'] == 10002) {
				$ac_type_cn = '农场';
			} elseif ($v['ac_type'] == 700 || $v['ac_type'] == 750) {
				$ac_type_cn = '报单币';
			}  elseif ($v['ac_type'] == 800 || $v['ac_type'] == 850) {
				$ac_type_cn = '澳洲SKN股份';
			} elseif ($v['ac_type'] == 901 || $v['ac_type'] == 951) {
				$ac_type_cn = 'GRC购物积分';
			}
			
			//组装导出数据
			$export_data[] = array(
				$v['username'],
				$v[ loginname ] . '[' . $v[ nickname ] . ']',
				$ac_type_cn,
				$v['ac_money'],
				date( 'Y-m-d H:i:s', $v['ac_post_time'] ),
				$v['beizhu']
			);
		}
		
		//导出功能
		if ($this->get['action'] == 'exportData') {
			$head_array = array( '用户名', '用户账号', '帐号类型', '操作金额', '操作时间', '备注说明' );
			$this->exportData($head_array, $export_data);
		}

		$admin_acount['add_cash']     = $MemberAccount->where( 'ac_type=300' )->sum( 'ac_money' );
		$admin_acount['dec_cash']     = $MemberAccount->where( 'ac_type=350' )->sum( 'ac_money' );
		$admin_acount['add_goldcoin'] = $MemberAccount->where( 'ac_type=101' )->sum( 'ac_money' );
		$admin_acount['dec_goldcoin'] = $MemberAccount->where( 'ac_type=151' )->sum( 'ac_money' );
		$admin_acount['add_bonus']     = $MemberAccount->where( 'ac_type=201' )->sum( 'ac_money' );
		$admin_acount['dec_bonus']     = $MemberAccount->where( 'ac_type=251' )->sum( 'ac_money' );
		$admin_acount['add_points']     = $MemberAccount->where( 'ac_type=501' )->sum( 'ac_money' );
		$admin_acount['dec_points']     = $MemberAccount->where( 'ac_type=551' )->sum( 'ac_money' );
		$admin_acount['add_colorcoin']     = $MemberAccount->where( 'ac_type=401' )->sum( 'ac_money' );
		$admin_acount['dec_colorcoin']     = $MemberAccount->where( 'ac_type=451' )->sum( 'ac_money' );
		$admin_acount['add_enroll']     = $MemberAccount->where( 'ac_type=601' )->sum( 'ac_money' );
		$admin_acount['dec_enroll']     = $MemberAccount->where( 'ac_type=651' )->sum( 'ac_money' );
		$admin_acount['add_portion']     = $MemberAccount->where( 'ac_type=10001' )->sum( 'ac_money' );
		$admin_acount['dec_portion']     = $MemberAccount->where( 'ac_type=10002' )->sum( 'ac_money' );
		$admin_acount['add_supply']     = $MemberAccount->where( 'ac_type=700' )->sum( 'ac_money' );
		$admin_acount['dec_supply']     = $MemberAccount->where( 'ac_type=750' )->sum( 'ac_money' );
		$admin_acount['add_enjoy']     = $MemberAccount->where( 'ac_type=800' )->sum( 'ac_money' );
		$admin_acount['dec_enjoy']     = $MemberAccount->where( 'ac_type=850' )->sum( 'ac_money' );
		$admin_acount['add_credits']     = $MemberAccount->where( 'ac_type=901' )->sum( 'ac_money' );
		$admin_acount['dec_credits']     = $MemberAccount->where( 'ac_type=951' )->sum( 'ac_money' );

		foreach ( $admin_acount as $k => $v ) {
			$admin_acount[ $k ] = empty( $v ) ? 0 : $v;
		}

		$this->assign( 'admin_acount', $admin_acount );
		$this->assign( 'account', $m_account );
		
		//获取平台(uid=1)的账户信息(针对矿池)
		$points_member_info = M('Member')->where('id=1')->field('loginname')->find();
		$this->assign('points_member_info', $points_member_info);

		$this->display();
	}

	/**
	 * 后台充值操作
	 */
	public function memberCashCheck() {
		$member    = M( 'member' );
		//$Parameter = M( 'Parameter', 'g_' );

		//获取当前1丰收点需积分的数额配置参数
// 		$points_to_bonus = $Parameter->where( 'id=1' )->getField( 'points_to_bonus' );

		$member_cash = I( 'post.member_cash' );
		$crease      = I( 'post.crease' );
		$money       = sprintf( '%.4f', $this->post['money'] );
		$username    = $this->post['userid'];

//		if (!validateExtend($username, 'USERNAME')) {
//			$this->error('用户名格式有误');
//		}

		$map_member['loginname'] = array( 'eq', $username );
		$uid                     = $member->where( $map_member )->getField( 'id' );
		if ( ! $uid ) {
			$this->error( '此用户不存在！' );
			exit;
		}
		
		$AccountModel          = new AccountModel();
		$account               = $AccountModel->getItemByUserId( $uid, $AccountModel->get5BalanceFields() );
		$account['cash']       = $account['account_cash_balance'];
		$account['goldcoin']   = $account['account_goldcoin_balance'];
		$account['colorcoin']  = $account['account_colorcoin_balance'];
		$account['points']     = $account['account_points_balance'];
		$account['bonus']      = $account['account_bonus_balance'];
		$account['enroll']     = $account['account_enroll_balance'];
		$account['supply']     = $account['account_supply_balance'];
		$account['enjoy']      = $account['account_enjoy_balance'];
		$account['credits']    = $account['account_credits_balance'];
	// 		$account['redelivery'] = $account['account_redelivery_balance'];
		
		if ( ! validateExtend( $money, 'MONEY' ) ) {
			$this->error( '输入金额不对！' );
			exit;
		}
		if ( $money <= 0 ) {
			$this->error( '操作金额不能少于等于0！' );
			exit;
		}

		//现金币账户
		if ( $member_cash == "cash" ) {
			if ( $crease == "increase" ) {
				$ac_type = 300;
			}
			if ( $crease == "decrease" ) {
				if ( $money > $account['cash'] ) {
					$this->error( '减去金额不能大于账户余额！' );
					exit;
				}
				$ac_type = 350;
			}
		}

		//公让宝账户
		if ( $member_cash == "goldcoin" ) {
			if ( $crease == "increase" ) {
				$ac_type = 101;
			}
			if ( $crease == "decrease" ) {
				if ( $money > $account['goldcoin'] ) {
					$this->error( '减去金额不能大于账户余额！' );
					exit;
				}
				$ac_type = 151;
			}
		}
		
		//锁定通证
		if ( $member_cash == 'bonus' ) {
			if ( $crease == 'increase' ) {
				$ac_type = 201;
			}
			if ( $crease == 'decrease' ) {
				if ( $money > $account['bonus'] ) {
					$this->error( '减去金额不能大于账户余额！' );
					exit;
				}
				$ac_type = 251;
			}
		}

		//提货券
		if ($member_cash=="colorcoin") {
			if ($crease=="increase") {
				$ac_type = 401;
			}
			if ($crease=="decrease") {
				if ($money>$account['colorcoin']) {
					$this->error('减去金额不能大于账户余额！');
					exit;
				}
				$ac_type = 451;
			}
		}

		//矿池
		if ( $member_cash == 'points' ) {
			//只能给平台(uid=1)进行矿池充值操作
			if ($uid != 1) {
				$this->error('矿池充值操作只能在指定账户上执行');
			}
			
			if ( $crease == 'increase' ) {
				$ac_type = 501;
			}
			if ( $crease == 'decrease' ) {
				if ( $money > $account['points'] ) {
					$this->error( '减去金额不能大于账户余额！' );
					exit;
				}
				$ac_type = 551;
			}
		}

		//兑换券
		if ($member_cash == 'enroll') {
			if ($crease == 'increase') {
				$ac_type = 601;
			}
			if ($crease == 'decrease') {
				if ($money > $account['enroll']) {
					$this->error('减去金额不能大于账户余额！');
					exit;
				}
				$ac_type = 651;
			}
		}
		
		//农场
		if ($member_cash == 'portion') {
			if ($crease == 'increase') {
				$ac_type = 10001;
			}
			if ($crease == 'decrease') {
				$ac_type = 10002;
			}
		}

		//报单币账户
		if ( $member_cash == "supply" ) {
			if ( $crease == "increase" ) {
				$ac_type = 700;
			}
			if ( $crease == "decrease" ) {
				if ( $money > $account['supply'] ) {
					$this->error( '减去金额不能大于账户余额！' );
					exit;
				}
				$ac_type = 750;
			}
		}
		
		//澳洲SKN股份账户
		if ( $member_cash == "enjoy" ) {
			if ( $crease == "increase" ) {
				$ac_type = 800;
			}
			if ( $crease == "decrease" ) {
				if ( $money > $account['enjoy'] ) {
					$this->error( '减去金额不能大于账户余额！' );
					exit;
				}
				$ac_type = 850;
			}
		}
		
		//GRC购物积分
		if ($member_cash == 'credits') {
			if ($crease == 'increase') {
				$ac_type = 901;
			}
			if ($crease == 'decrease') {
				if ($money > $account['credits']) {
					$this->error('减去金额不能大于账户余额！');
					exit;
				}
				$ac_type = 951;
			}
		}

		$AccountRecordModel = new AccountRecordModel();

		M()->startTrans();

		$data['ac_money']     = $money;
		$data['ac_uid']       = $uid;
		$data['ac_type']      = $ac_type;
		$data['ac_op_id']     = session( 'admin_mid' );
		$data['ac_post_time'] = time();
		$data['beizhu']       = $this->post['beizhu'];
		$row                  = M( 'member_account' )->add( $data );

		$exchange = 1;

		switch ( $ac_type ) {
			case 300:
				$m_row     = $AccountRecordModel->add( $uid, Currency::Cash, CurrencyAction::CashChongzhi, $money, $AccountRecordModel->getRecordAttach( 1, '' ), '系统增加现金币' );
				$coin_name = '增加现金币' . $money;
				break;
			case 350:
				$m_row     = $AccountRecordModel->add( $uid, Currency::Cash, CurrencyAction::CashHOutaikoukuan, '-' . $money, $AccountRecordModel->getRecordAttach( 1, '' ), '系统减去现金币' );
				$coin_name = '减去现金币' . $money;
				break;
				
			case 101:
				$m_row     = $AccountRecordModel->add( $uid, Currency::GoldCoin, CurrencyAction::GoldCoinByChongzhi, $money, $AccountRecordModel->getRecordAttach( 1, '' ), '系统增加丰谷宝' );
				$coin_name = '增加丰谷宝' . $money;
				break;
			case 151:
				$m_row     = $AccountRecordModel->add( $uid, Currency::GoldCoin, CurrencyAction::GoldCoinByHOutaiKoukuan, '-' . $money, $AccountRecordModel->getRecordAttach( 1, '' ), '系统减去丰谷宝' );
				$coin_name = '减去丰谷宝' . $money;
				break;
				
			case 201:
				$m_row     = $AccountRecordModel->add( $uid, Currency::Bonus, CurrencyAction::BonusByChongzhi, $money, $AccountRecordModel->getRecordAttach( 1, '' ), '系统增加锁定通证' );
				$coin_name = '增加锁定通证' . $money;
				break;
			case 251:
				$m_row     = $AccountRecordModel->add( $uid, Currency::Bonus, CurrencyAction::BonusByHOutaiKoukuan, '-' . $money, $AccountRecordModel->getRecordAttach( 1, '' ), '系统减去锁定通证' );
				$coin_name = '减去锁定通证' . $money;
				break;
				
			case 501:
				$m_row     = $AccountRecordModel->add( $uid, Currency::Points, CurrencyAction::PointsByChongzhi, $money, $AccountRecordModel->getRecordAttach( 1, '' ), '系统增加矿池' );
				$coin_name = '增加矿池' . $money;
				break;
			case 551:
				$m_row     = $AccountRecordModel->add( $uid, Currency::Points, CurrencyAction::PointsByHOutaiKoukuan, '-' . $money, $AccountRecordModel->getRecordAttach( 1, '' ), '系统减去矿池' );
				$coin_name = '减去矿池' . $money;
				break;
				
			case 401:
				$m_row = $AccountRecordModel->add($uid, Currency::ColorCoin, CurrencyAction::colorcoinByChongZhi, $money, $AccountRecordModel->getRecordAttach(1, ''), '系统增加提货券');
				$coin_name = '增加提货券'.$money;
				break;
			case 451:
				$m_row = $AccountRecordModel->add($uid, Currency::ColorCoin, CurrencyAction::colorcoinByHOutaiKoukuan, '-'.$money, $AccountRecordModel->getRecordAttach(1, ''), '系统减去提货券');
				$coin_name = '减去提货券'.$money;
				break;
				
			case 601:
				$m_row = $AccountRecordModel->add($uid, Currency::Enroll, CurrencyAction::enrollByChongZhi, $money, $AccountRecordModel->getRecordAttach(1, ''), '系统增加兑换券');
				$coin_name = '增加兑换券'.$money;
				break;
			case 651:
				$m_row = $AccountRecordModel->add($uid, Currency::Enroll, CurrencyAction::enrollByHOutaiKoukuan, '-'.$money, $AccountRecordModel->getRecordAttach(1, ''), '系统减去兑换券');
				$coin_name = '减去兑换券'.$money;
				break;
				
			case 10001:
				$consume_info = M('Consume')->where('user_id='.$uid)->field('id')->find();
				if (!$consume_info) {
					$this->error('该用户未查询到消费数据，暂时不能增加农场');
				}
				$m_row = M('Consume')->where('user_id='.$uid)->setInc('machine_amount', $money);
				$coin_name = '增加农场'.$money;
				break;
			case 10002:
				$consume_info = M('Consume')->where('user_id='.$uid)->field('id')->find();
				if (!$consume_info) {
					$this->error('该用户未查询到消费数据，暂时不能减去农场');
				}
				$m_row = M('Consume')->where('user_id='.$uid)->setDec('machine_amount', $money);
				$coin_name = '减去农场'.$money;
				break;
				
			case 700:
				$m_row     = $AccountRecordModel->add( $uid, Currency::Supply, CurrencyAction::SupplyChongzhi, $money, $AccountRecordModel->getRecordAttach( 1, '' ), '系统增加报单币' );
				$coin_name = '增加报单币' . $money;
				break;
			case 750:
				$m_row     = $AccountRecordModel->add( $uid, Currency::Supply, CurrencyAction::SupplyHOutaikoukuan, '-' . $money, $AccountRecordModel->getRecordAttach( 1, '' ), '系统减去报单币' );
				$coin_name = '减去报单币' . $money;
				break;
				
			case 800:
				$m_row     = $AccountRecordModel->add( $uid, Currency::Enjoy, CurrencyAction::EnjoyChongzhi, $money, $AccountRecordModel->getRecordAttach( 1, '' ), '系统增加澳洲SKN股份' );
				$coin_name = '增加澳洲SKN股份' . $money;
				break;
			case 850:
				$m_row     = $AccountRecordModel->add( $uid, Currency::Enjoy, CurrencyAction::EnjoyHOutaikoukuan, '-' . $money, $AccountRecordModel->getRecordAttach( 1, '' ), '系统减去澳洲SKN股份' );
				$coin_name = '减去澳洲SKN股份' . $money;
				break;
				
			case 901:
				$m_row = $AccountRecordModel->add($uid, Currency::Credits, CurrencyAction::CreditsByChongzhi, $money, $AccountRecordModel->getRecordAttach(1, ''), '系统增加GRC购物积分');
				$coin_name = '增加GRC购物积分'.$money;
				break;
			case 951:
				$m_row = $AccountRecordModel->add($uid, Currency::Credits, CurrencyAction::CreditsByHOutaiKoukuan, '-'.$money, $AccountRecordModel->getRecordAttach(1, ''), '系统减去GRC购物积分');
				$coin_name = '减去GRC购物积分'.$money;
				break;
		}

		if ( $row === false || $m_row === false ) {
			M()->rollback();
			$this->error( '操作失败！', U( 'Admin/Finance/memberCash' ) );
			exit;
		}

		M()->commit();

		//操作记录
		$where_log['id'] = array( 'eq', $uid );
		$log_data        = $member->field( 'loginname,nickname,username' )->where( $where_log )->find();
		$this->success( '操作成功！', U( 'Admin/Finance/memberCash' ), false, '平台充值为用户' . $log_data['loginname'] . '[用户名:' . $log_data['username'] . '][姓名:' . $log_data['nickname'] . ']' . $coin_name );
		exit;
	}

	/**
	 * 后台充值记录搜索
	 */
	public function memberCashSearch() {
		$username = I( 'post.username' );

		if ( $username ) {

			if ( ! validateExtend( $username, 'NUMBER' ) && ! validateExtend( $username, 'CHS' ) ) {
				$this->error( '会员账号格式有误' );
			}

			$map_member['loginname'] = array( 'eq', $username );
			$map_member['truename']  = array( 'eq', $username );
			$map_member['nickname']  = array( 'eq', $username );
			$map_member['username']  = array( 'eq', $username );
			$map_member['_logic']    = 'OR';
			$count                   = M( 'member' )->where( $map_member )->count();
			if ( $count == 0 ) {
				exit( '此会员不存在！' );
			} else {
				$wherekey['b.ac_uid'] = M( 'member' )->where( $map_member )->getField( 'id' );

				$app_unlock = M( 'member_account b' );

				$perpage              = 10;
				$return['list']       = $app_unlock
					->field( 'a.loginname,a.nickname,a.username,b.ac_type,b.ac_money,from_unixtime(b.ac_post_time) post_time' )
					->join( 'zc_member a on a.id=b.ac_uid ' )
					->where( $wherekey )
					->order( 'b.ac_post_time desc' )
					->page( $_POST['page'] . ',' . $perpage )
					->select();
				$count                = $app_unlock->where( $wherekey )->count();
				$return['count_page'] = ceil( $count / $perpage );

				$this->ajaxReturn( $return );
			}
		} else {
			exit( '请输入要查询的用户名！' );
		}
	}

	/**
	 * 货币转换记录
	 */
	public function exchange() {
		$searchKey = array();

		if ( I( 'get.money' ) != "" ) {
			$searchKey['money'] = array( 'eq', I( 'get.money' ) );
		}
		if ( I( 'get.out_account' ) != "" ) {
			$searchKey['out_account'] = array( 'eq', I( 'get.out_account' ) );
		}
		if ( I( 'get.in_account' ) != "" ) {
			$searchKey['in_account'] = array( 'eq', I( 'get.in_account' ) );
		}
		if ( I( 'get.userid' ) != "" ) {

			if ( ! validateExtend( I( 'get.userid' ), 'NUMBER' ) && ! validateExtend( I( 'get.userid' ), 'CHS' ) && ! validateExtend( I( 'get.userid' ), 'USERNAME' ) ) {
				$this->error( '会员账号格式有误' );
			}

			$map_member['loginname'] = array( 'eq', I( 'get.userid' ) );
			$map_member['truename']  = array( 'eq', I( 'get.userid' ) );
			$map_member['nickname']  = array( 'eq', I( 'get.userid' ) );
			$map_member['username']  = array( 'eq', I( 'get.userid' ) );
			$map_member['_logic']    = 'OR';
			$uid                     = M( 'member', 'zc_' )->where( $map_member )->getField( 'id' );
			if ( $uid == "" ) {
				$this->error( '会员编号不存在！' );
			}
			$searchKey['uid'] = array( 'eq', $uid );
		}

		if ( I( 'get.time_min' ) != "" && I( 'get.time_max' ) != "" ) {
			$searchKey['time'] = array(
				'between',
				array( strtotime( I( 'get.time_min' ) . ' 0:0:0' ), strtotime( I( 'get.time_max' ) . ' 23:59:59' ) )
			);
		} elseif ( I( 'get.time_min' ) != "" ) {
			$searchKey['time'] = array(
				'EGT',
				strtotime( I( 'get.time_min' ) . ' 0:0:0' )
			);
		} elseif ( I( 'get.time_max' ) != "" ) {
			$searchKey['time'] = array(
				'ELT',
				strtotime( I( 'get.time_max' ) . ' 23:59:59' )
			);
		}

		$exchange = M( 'color_cash', 'zc_' );
		$count    = $exchange->where( $searchKey )->count();
		$page     = new \Think\Page( $count, 20, $this->get );
		$show     = $page->show();
		$_info    = $exchange
			->where( $searchKey )
			->order( 'id desc' )
			->limit( $page->firstRow . ',' . $page->listRows )
			->select();

		$this->assign( 'info', $_info );
		$this->assign( 'page', $show );
		$this->display();
	}

	/**
	 * 微信充值记录
	 */
	public function recharge() {
		$searchKey = '';

		$FinanceModel = new FinanceModel();

		$time_min = $this->get['time_min'];
		$time_max = $this->get['time_max'];
		$page     = $this->get['p'] > 0 ? $this->get['p'] : 1;
		$type     = empty( $this->get['type'] ) ? 'WX' : $this->get['type'];

		//日期筛选
		if ( empty( $time_min ) ) {
			$time_min = date( 'Ym' ) . '01';
		} else {
			$time_min = date( 'Ymd', strtotime( $time_min ) );
		}
		if ( empty( $time_max ) ) {
			$time_max = date( 'Ymd' );
		} else {
			$time_max = date( 'Ymd', strtotime( $time_max ) );
		}
		if ( substr( $time_min, 0, 6 ) != substr( $time_max, 0, 6 ) ) {
			$this->error( '搜索日期必须在同一个月' );
		}
		$searchKey .= " and finance_tag>='{$time_min}' and finance_tag<='{$time_max}' ";

		//类型筛选
		$column = '';
		switch ( $type ) {
			case 'WX':
				$column = 'finance_recharge';
				break;
			case 'ALI':
				$column = 'finance_recharge_alipay';
				break;
		}

		$data = $FinanceModel->getPageList( $column . ' as finance_recharge,finance_tag', $time_max, $page, 20, $searchKey );

		$_info = $data['list'];
		$this->assign( 'info', $_info );

		$this->Page( $data['paginator']['totalPage'] * $data['paginator']['everyPage'], $data['paginator']['everyPage'], $this->get );

		//充值总额
		//$total_recharge = $FinanceModel->getItem('finance_recharge', Tag::getMonth());
		$total_recharge = $FinanceModel->getFieldsValues( 'sum(' . $column . ') finance_recharge', " 1 {$searchKey} " );
		$total_recharge = $total_recharge ? $total_recharge['finance_recharge'] : 0;
		$this->assign( 'total_recharge', $total_recharge );

		$this->display();
	}

	/**
	 * 会员转账记录
	 */
	public function transfer() {
		$time_min      = $this->get['time_min'];
		$time_max      = $this->get['time_max'];
		$out_loginname = $this->get['out_loginname'];
		$in_loginname  = $this->get['in_loginname'];
		$transfer_type = $this->get['transfer_type'];
		$page          = $this->get['p'] > 0 ? $this->get['p'] : 1;
		$page          = $this->get['action']=='exportData' ? false : $page;

		$searchKey = '';

		$time_min = empty( $time_min ) ? strtotime( date( 'Y-m' ) . '-01' ) : strtotime( $time_min );
		$time_max = empty( $time_max ) ? time() : strtotime( $time_max . ' 23:59:59' );

		if ( date( 'Ym', $time_min ) != date( 'Ym', $time_max ) ) {
			$this->error( '搜索日期必须为同一个月' );
		}

		$searchKey .= " and record_addtime>='{$time_min}' and record_addtime<='{$time_max}' ";

		$Member = M( 'member' );

		//转入/出用户信息
		$user_id_list = [];
		if ( ! empty( $out_loginname ) ) {
			$whereSql['username'] = array( 'eq', $out_loginname );
			$uid                  = $Member->where( $whereSql )->getField( 'id' );
			if ( ! $uid ) {
				$this->error( '转出账户用户不存在' );
			}
			$user_id_list[] = $uid;
		}
		if ( ! empty( $in_loginname ) ) {
			$whereSql['username'] = array( 'eq', $in_loginname );
			$uid                  = $Member->where( $whereSql )->getField( 'id' );
			if ( ! $uid ) {
				$this->error( '转入账户用户不存在' );
			}
			$user_id_list[] = $uid;
		}
		if ( count( $user_id_list ) > 1 ) {
			$searchKey .= " and (user_id={$user_id_list[0]} or user_id={$user_id_list[1]}) ";
		} elseif ( count( $user_id_list ) == 1 ) {
			$searchKey .= " and user_id={$user_id_list[0]} ";
		}

		//转账类型
		$currency = '';
		switch ( $transfer_type ) {
			case 'cash':
				$searchKey .= " and record_action=".CurrencyAction::CashTransfer;
				$currency  = Currency::Cash;
				break;
			case 'goldcoin':
				$searchKey .= " and record_action=".CurrencyAction::GoldCoinTransfer;
				$currency  = Currency::GoldCoin;
				break;
			case 'bonus':
				$searchKey .= " and record_action=".CurrencyAction::BonusTransfer;
				$currency  = Currency::Bonus;
				break;
			default:
				$searchKey .= " and record_action=".CurrencyAction::CashTransfer;
				$currency  = Currency::Cash;
		}

		$AccountRecordModel = new AccountRecordModel();
		$data               = $AccountRecordModel->getListByAllUser( $currency, date( 'Ym', $time_min ), $page, 20, $searchKey );

		$_info = $data['list'];
		$list  = [];
		$export_data = []; //导出数据
		foreach ( $_info as $k => $v ) {
			$attach   = json_decode( $v['record_attach'], true );
			$attach   = $AccountRecordModel->initAtach( $attach, $currency, date( 'Ym', $time_min ), $v['record_id'], $v['record_action'] );
			$from_uid = $attach['from_uid'];

			//获取转出用户信息
			$out_user_id     = $v['user_id'];
			$out_member_info = M( 'Member' )->where( 'id=' . $out_user_id )->field( 'loginname,nickname,username' )->find();

			//获取转入用户信息
			$in_user_id     = $from_uid;
			$in_member_info = M( 'Member' )->where( 'id=' . $in_user_id )->field( 'loginname,nickname,username' )->find();

			$list[ $k ] = [
				'record_amount'  => abs( $v['record_amount'] ),
				'out_loginname'  => $out_member_info['loginname'],
				'out_nickname'   => $out_member_info['nickname'],
				'out_username'   => $out_member_info['username'],
				'in_loginname'   => $in_member_info['loginname'],
				'in_nickname'    => $in_member_info['nickname'],
				'in_username'    => $in_member_info['username'],
				'record_addtime' => $v['record_addtime'],
			];
			
			//导出数据封装
			$export_data[] = [
				$list[$k]['record_amount'],
				$list[$k]['out_loginname'].'['.$list[$k]['out_nickname'].']',
				$list[$k]['in_loginname'].'['.$list[$k]['in_nickname'].']',
				date('Y-m-d H:i:s', $list[$k]['record_addtime'])
			];
		}
		$this->assign( 'info', $list );

		$this->Page( $data['paginator']['totalPage'] * $data['paginator']['everyPage'], $data['paginator']['everyPage'], $this->get );
		
		//导出功能
		if ($this->get['action'] == 'exportData') {
			$head_array = array('转账金额', '转出账户', '接收账户', '转账时间');
			$this->exportData($head_array, $export_data);
		}

		$this->display();
	}

	/**
	 * 微信手动批量退款
	 */
	public function drawbackWX() {
		$this->drawbackBase( 'WX' );
	}

	/**
	 * 银行卡手动批量退款
	 */
	public function drawbackBANK() {
		$this->drawbackBase( 'BANK' );
	}

	/**
	 * 支付宝手动批量退款
	 */
	public function drawbackALI() {
		$this->drawbackBase( 'ALI' );
	}

	/**
	 * 手动批量退款处理操作
	 *
	 * @param $type string 退款类型
	 */
	private function drawbackBase( $type = 'WX' ) {
		$member      = M( 'member' );
		$drawModel   = M( 'withdraw_cash' );
		$TixianQueue = M( 'TixianQueue' );

		$AccountRecordModel = new AccountRecordModel();

		$checkList = I( 'post.id' );

		$checkList = is_array( $checkList ) ? $checkList : array( $checkList );

		$return      = array();
		$tixian_type = 1;
		$about       = '微信';

		switch ( $type ) {
			case 'ALI':
				$tixian_type = 0;
				$about       = '支付宝';
			case 'WX':
				$tixian_type = 1;
				$about       = '微信';
				break;
			case 'BANK':
				$tixian_type = 2;
				$about       = '银行卡';
		}

		foreach ( $checkList as $key => $val ) {
			if ( empty( $val ) ) {
				continue;
			}

			$member->startTrans();
			$drawModel->startTrans();
			$TixianQueue->startTrans();

			//检测此时该提现申请是否已被加入提现队列,已加入还未执行则强制删除,删除失败则不执行此提现申请的手动退款操作
			$map_tixian_queue['wcid'] = array( 'eq', $val );
			$map_tixian_queue['type'] = array( 'eq', $tixian_type );
			$tixian_info              = $TixianQueue->where( $map_tixian_queue )->lock( true )->find();
			if ( $tixian_info ) {
				if ( $TixianQueue->where( $map_tixian_queue )->delete() === false ) {
					continue;
				}
			}

			//遍历ID
			$where['id'] = $val;
			$withdraw    = $drawModel->field( 'uid,serial_num,receiver_acount,amount,commission,status' )->lock( true )->where( $where )->find();

			//再次检测提现状态是否符合退款条件
			if ( $withdraw['status'] != '0' && $withdraw['status'] != 'W' ) {
				continue;
			}

			//银行卡提现额外判断当前处理状态是否已经提交至银行,若已提交至银行则不能进行退款操作
			if ( $tixian_type == '2' && $withdraw['status'] == 'W' ) {
				continue;
			}

			$map_member['id'] = $withdraw['uid'];
			$member_info      = $member->where( $map_member )->lock( true )->field( 'loginname' )->find();
			if ( $member_info ) {

				//退回现金币
				$data_record = [
					'serial_num' => $withdraw['serial_num'],
				];
				$record_1    = $AccountRecordModel->add( $withdraw['uid'], Currency::Cash, CurrencyAction::CashTixianTuikuan, $withdraw['amount'], json_encode( $data_record ), '现金币提现退款' );
				$record_2    = $AccountRecordModel->add( $withdraw['uid'], Currency::Cash, CurrencyAction::CashTixianTuikuanShouxufei, $withdraw['commission'], json_encode( $data_record ), '现金币提现手续费退款' );
				if ( $record_1 === false || $record_2 === false ) {
					$TixianQueue->rollback();

					$return[] = "{$withdraw['receiver']}[申请提现{$withdraw['amount']}]手动退款失败:退回现金币失败";

					continue;
				}

				//添加退款成功至提现申请表
				$data_withdraw = array(
					'status'       => 'TS',
					'failure_code' => '手动退款成功',
					'finish_time'  => time(),
				);
				if ( $drawModel->where( $where )->save( $data_withdraw ) === false ) {
					$TixianQueue->rollback();
					$member->rollback();

					//添加退款失败至提现申请表
					$data_withdraw = array(
						'status'       => 'TF',
						'failure_code' => '手动退款失败',
						'finish_time'  => time(),
					);
					if ( $drawModel->where( $where )->save( $data_withdraw ) !== false ) {
						$drawModel->commit();
					}

					$return[] = "{$withdraw['receiver']}[申请提现{$withdraw['amount']}]手动退款失败:添加手动退款失败状态至申请表失败";

					continue;
				}

				$member->commit();
				$drawModel->commit();
				$TixianQueue->commit();

			} else {
				$return[] = "{$withdraw['receiver']}[申请提现{$withdraw['amount']}]手动退款失败:该会员已不存在";
				continue;
			}

		}

		if ( empty( $return ) ) {
			$return[] = '全部退款成功！';
		}

		//操作记录
		$this->logWrite( '操作了平台提现退款功能' );

		$this->ajaxReturn( $return );
		exit;
	}

	/**
	 * 批量解锁
	 */
	public function applyUnlock() {
		$withdraw = M( 'WithdrawCash' );

		$checkList = $this->post['id'];

		if ( empty( $checkList ) ) {
			$this->error( '请选择要解锁项' );
		}

		$where['id']          = array( 'in', implode( ',', $checkList ) );
		$where['submit_flag'] = array( 'neq', 0 );
		$data['submit_flag']  = 0;
		if ( $withdraw->where( $where )->save( $data ) === false ) {
			$this->error( '解锁失败' );
		}

		$this->logWrite( "操作了平台提现解锁功能" );

		$return   = array();
		$return[] = '解锁成功！';
		$this->ajaxReturn( $return );

		exit;
	}

	/**
	 * 第三方支付记录
	 * 恩杰
	 * Enter description here ...
	 */
	public function trade() {
		$where = ' 1=1 ';

		$userid      = $this->get['userid'];
		$time_min    = $this->get['time_min'];
		$time_max    = $this->get['time_max'];
// 		$exchangeway = $this->get['exchangeway'];
		$page        = $this->get['p'] > 0 ? $this->get['p'] : 1;
		$page        = $this->get['action']=='exportData' ? false : $page;
// 		$type        = $this->get['type'];
		$amount_type = $this->get['amount_type'];

		if ( ! empty( $userid ) && ! validateExtend( $userid, 'MOBILE' ) ) {
			$this->error( '用户账号格式有误' );
		}

		$where .= " and ord.order_status in (1,3,4) ";

		//兑换类型
		if (!empty($amount_type) && validateExtend($amount_type, 'NUMBER')) {
			$where .= " and ord.amount_type={$amount_type} ";
		}


		//账号筛选
		if ( ! empty( $userid ) ) {
			$map_member['loginname'] = array( 'eq', $userid );
			$map_member['nickname']  = array( 'eq', $userid );
			$map_member['username']  = array( 'eq', $userid );
			$map_member['_logic']    = 'or';
			$member_info             = M( 'Member' )->where( $map_member )->field( 'id' )->find();
			if ( ! $member_info ) {
				$this->error( '用户账号不存在' );
			}
			$where .= " and ord.uid={$member_info[id]} ";
		}

		//日期筛选
		if ( ! empty( $time_min ) ) {
			$time_min = strtotime( $time_min );
		} else {
			$time_min = strtotime( date( 'Ym' ) . '01' );
		}
		if ( ! empty( $time_max ) ) {
			$time_max = strtotime( $time_max . ' 23:59:59' );
		} else {
			$time_max = strtotime( date( 'Ymd' ) . ' 23:59:59' );
		}
		$where .= " and ord.time>='{$time_min}' and ord.time<='{$time_max}' ";

		//订单类型筛选
// 		if ( validateExtend( $exchangeway, 'NUMBER' ) ) {
// 			$where .= " and ord.exchangeway='{$exchangeway}' ";
// 		}

		//总兑换额
		$total = M( 'Orders' )
			->alias( 'ord' )
			->join( 'left join __ORDER_AFFILIATE__ ora ON ora.order_id=ord.id' )
			->where( $where )
			->field( '
					sum(ord.amount) amount,
					sum(ora.affiliate_credits+ora.affiliate_supply+ora.affiliate_goldcoin+ora.affiliate_colorcoin+ora.affiliate_cash+ora.affiliate_freight) amount_other
				' )
			->find();
		$this->assign( "total", $total );

		//总毛利润
		$total_profits = M( 'ProfitsBonus' )
			->alias( 'prb' )
			->join( 'join __ORDERS__ ord ON ord.order_number=prb.order_number' )
			->where( $where )
			->sum( 'prb.profits' );
		$this->assign( 'total_profits', $total_profits );

		//分页插件
		$count = M( 'Orders' )->alias( 'ord' )->where( $where )->count();
		$limit = $this->Page( $count, 20, $this->get );

		$datalist = M( 'Orders' )
			->alias( 'ord' )
			->where( $where )
			->join( 'left join __ORDER_AFFILIATE__ ora ON ora.order_id=ord.id' )
			->field( 'ord.*,ora.affiliate_pay' )
			->order( 'ord.id desc' )
			->limit( $limit )
			->select();
		
		$export_data = []; //导出数据
		foreach ( $datalist as $k => $v ) {
			//获取买家信息
			$member_info = M( 'Member' )->where( 'id=' . $v['uid'] )->field( 'loginname,nickname,username' )->find();
			if ( $member_info ) {
				$datalist[ $k ] = array_merge( $v, $member_info );
			}

			//获取商家信息
			$store_info = M( 'Store' )->where( 'id=' . $v['storeid'] )->field( 'store_name' )->find();
			if ( $store_info ) {
				$datalist[ $k ] = array_merge( $datalist[ $k ], $store_info );
			}

			//获取单笔毛利润
			$profits_info = M( 'ProfitsBonus' )->where( "order_number='" . $v['order_number'] . "'" )->field( 'profits' )->find();
			if ( $profits_info ) {
				$datalist[ $k ] = array_merge( $datalist[ $k ], $profits_info );
			}
			
			//支付类型
			$datalist[$k]['amount_type_cn'] = C('FIELD_CONFIG')['orders']['amount_type'][$v['amount_type']];
			
			//导出数据封装
			$export_data[] = [
				$v['loginname'].'['.$v['username'].']',
				$v['store_name'].'['.$v['productname'].']',
				$v['order_number'],
				$datalist[$k]['amount_type_cn'],
				$v['amount'],
				$v['affiliate_pay']>0 ? $v['affiliate_pay'] : $v['amount'],
				date('Y-m-d H:i:s', $v['time']),
				date('Y-m-d H:i:s', $v['pay_time']),
				$v['profits']
			];
		}
		$this->assign( 'datalist', $datalist );
		
		//导出功能
		if ($this->get['action'] == 'exportData') {
			$head_array = array('用户账户', '商家信息', '订单号', '总金额', '支付类型', '支付金额', '创建时间', '付款时间', '毛利润');
			$this->exportData($head_array, $export_data);
		}

		$this->display();
	}

	/**
	 * 现金币公让宝兑换
	 * Enter description here ...
	 */
	public function trade22() {
		$where = ' 1=1 ';

		$type     = $this->get['type'];
		$userid   = $this->get['userid'];
		$time_min = $this->get['time_min'];
		$time_max = $this->get['time_max'];
		$page     = $this->get['p'] > 0 ? $this->get['p'] : 1;
		$page     = $this->get['action']=='exportData' ? false : $page;

		if ( ! empty( $userid ) && ! validateExtend( $userid, 'MOBILE' ) ) {
			$this->error( '用户账号格式有误' );
		}

		//货币类型
		$where_type = '';
		$type       = ( empty( $type ) || $type == '1' ) ? 'cash' : $type;
		switch ( $type ) {
			case 'cash':
				$where_type = " ord.amount_type=1 ";
				$type_title = '现金币';
				break;
			case 'goldcoin':
				$where_type = " ord.amount_type=6 ";
				$type_title = '丰谷宝';
				break;
//			case 'colorcoin':
//				$where_type = " (ord.amount_type=6 or ora.affiliate_colorcoin>0) ";
//				$type_title = '商超券';
//				break;
//			case 'credits':
//				$where_type = " ora.affiliate_credits>0 ";
//				$type_title = '商城积分';
//				break;
//			case 'supply':
//				$where_type = " ora.affiliate_supply>0 ";
//				$type_title = '特供券';
//				break;
		}
		$this->assign( 'type_title', $type_title );
		$type_current = 'ora.affiliate_' . $type;

		$where .= " and ord.order_status in (1,3,4) ";

		//账号筛选
		if ( ! empty( $userid ) ) {
			$map_member['loginname'] = array( 'eq', $userid );
			$map_member['nickname']  = array( 'eq', $userid );
			$map_member['username']  = array( 'eq', $userid );
			$map_member['_logic']    = 'or';
			$member_info             = M( 'Member' )->where( $map_member )->field( 'id' )->find();
			if ( ! $member_info ) {
				$this->error( '用户账号不存在' );
			}
			$where .= " and ord.uid={$member_info[id]} ";
		}

		//日期筛选
		if ( ! empty( $time_min ) ) {
			$time_min = strtotime( $time_min );
		} else {
			$time_min = strtotime( date( 'Ym' ) . '01' );
		}
		if ( ! empty( $time_max ) ) {
			$time_max = strtotime( $time_max . ' 23:59:59' );
		} else {
			$time_max = strtotime( date( 'Ymd' ) . ' 23:59:59' );
		}
		$where .= " and ord.time>='{$time_min}' and ord.time<='{$time_max}' ";

		$where_all = $where . " and " . $where_type;

		//针对现金币特殊处理关联查询
		$join = $type == 'cash' ? 'left join' : 'left join';

		//总兑换额
		$total = M( 'Orders' )
			->alias( 'ord' )
			->join( "{$join} __ORDER_AFFILIATE__ ora ON ora.order_id=ord.id" )
			->where( $where_all )
			->field( "
					sum(ord.amount) amount,
					sum({$type_current}) amount_current
				" )
			->find();
		$this->assign( "total", $total );

		//总毛利润
		$total_profits = M( 'ProfitsBonus' )
			->alias( 'prb' )
			->join( 'join __ORDERS__ ord ON ord.order_number=prb.order_number' )
			->join( "{$join} __ORDER_AFFILIATE__ ora ON ora.order_id=ord.id" )
			->where( $where_all )
			->sum( 'prb.profits' );
		$this->assign( 'total_profits', $total_profits );

		//分页插件
		$count = M( 'Orders' )
			->alias( 'ord' )
			->join( "{$join} __ORDER_AFFILIATE__ ora ON ora.order_id=ord.id" )
			->where( $where_all )
			->count();
		$limit = $this->Page( $count, 20, $this->get );

		$datalist = M( 'Orders' )
			->alias( 'ord' )
			->join( "{$join} __ORDER_AFFILIATE__ ora ON ora.order_id=ord.id" )
			->where( $where_all )
			->field( "ord.*,{$type_current} amount_current" )
			->order( 'ord.id desc' )
			->limit( $limit )
			->select();
		
		$export_data = []; //导出数据
		foreach ( $datalist as $k => $v ) {
			//获取买家信息
			if ( ! empty( $v['uid'] ) ) {
				$member_info = M( 'Member' )->where( 'id=' . $v['uid'] )->field( 'loginname,nickname,username' )->find();
				if ( $member_info ) {
					$datalist[ $k ] = array_merge( $v, $member_info );
				}
			}

			//获取商家信息
			if ( ! empty( $v['storeid'] ) ) {
				$store_info = M( 'Store' )->where( 'id=' . $v['storeid'] )->field( 'store_name' )->find();
				if ( $store_info ) {
					$datalist[ $k ] = array_merge( $datalist[ $k ], $store_info );
				}
			}

			//获取单笔毛利润
			$profits_info = M( 'ProfitsBonus' )->where( "order_number='" . $v['order_number'] . "'" )->field( 'profits' )->find();
			if ( $profits_info ) {
				$datalist[ $k ] = array_merge( $datalist[ $k ], $profits_info );
			}
			
			$export_data[] = [
				"{$v['loginname']}[{$v['username']}]",
				$v['store_name'].'['.$v['productname'].']',
				$v['order_number'],
				$v['amount'],
				$v['amount_current'],
				date('Y-m-d H:i:s', $v['time']),
				date('Y-m-d H:i:s', $v['pay_time']),
				$v['profits']
			];
		}
		$this->assign( 'datalist', $datalist );
		
		//导出功能
		if ($this->get['action'] == 'exportData') {
			$head_array = array('用户账户', '商家信息', '订单号', '总金额', '现金币支付金额', '创建时间', '付款时间', '毛利润');
			$this->exportData($head_array, $export_data);
		}

		$this->display();
	}

	/**
	 * 微信提现入队
	 *
	 * 此处功能已替代APP/PayController中的wc_deposit_weixin()方法的功能,故wc_deposit_weixin()方法已弃用
	 */
	public function wxTixianQueue() {
		$TixianQueue  = M( 'TixianQueue' );
		$WithdrawCash = M( 'WithdrawCash' );

		$ids = I( 'post.id' );

		$ids = is_array( $ids ) ? $ids : array( $ids );
		foreach ( $ids as $id ) {
			if ( empty( $id ) ) {
				continue;
			}

			//过滤数据：该条提现信息status必须等于0才能进行提现操作
			$filter_withdraw_cash['id']     = array( 'eq', $id );
			$filter_withdraw_cash['status'] = array( 'neq', 0 );
			$filter_withdraw_cash_info      = $WithdrawCash->where( $filter_withdraw_cash )->field( 'id' )->find();
			if ( $filter_withdraw_cash_info ) {
				continue;
			}

			$data['wcid'] = $id;
			$data['type'] = 1;
			$map['wcid']  = array( 'eq', $id );
			$count        = $TixianQueue->where( $map )->count();
			if ( $count == 0 ) {
				if ( $TixianQueue->create( $data, '', true ) ) {
					$TixianQueue->add();
				}
			}

			$map_withdraw['id']           = array( 'eq', $id );
			$data_withdraw['submit_flag'] = time();
			$WithdrawCash->where( $map_withdraw )->save( $data_withdraw );
		}

		$this->logWrite( "微信提现入队操作已完成[" . implode( ',', $ids ) . "]" );

		exit;
	}

	/**
	 * 银行卡提现入队
	 */
	public function bankcardTixianQueue() {
		$WithdrawCash = M( 'WithdrawCash' );
		$TixianQueue  = M( 'TixianQueue' );

		$ids = $this->post['id'];

		//加入提现队列
		foreach ( $ids as $id ) {
			if ( empty( $id ) ) {
				continue;
			}

			//过滤数据：该条提现信息status必须等于0才能进行提现操作
			$filter_withdraw_cash['id']     = array( 'eq', $id );
			$filter_withdraw_cash['status'] = array( 'neq', 0 );
			$filter_withdraw_cash_info      = $WithdrawCash->where( $filter_withdraw_cash )->field( 'id' )->find();
			if ( $filter_withdraw_cash_info ) {
				continue;
			}

			$data['wcid'] = $id;
			$data['type'] = 2;
			$map['wcid']  = array( 'eq', $id );
			$count        = $TixianQueue->where( $map )->count();
			if ( $count == 0 ) {
				if ( $TixianQueue->create( $data, '', true ) ) {
					$TixianQueue->add();
				}
			}

			$map_withdraw['id']           = array( 'eq', $id );
			$data_withdraw['submit_flag'] = time();
			$WithdrawCash->where( $map_withdraw )->save( $data_withdraw );
		}

		$this->logWrite( "银行卡提现入队操作已完成[" . implode( ',', $ids ) . "]" );

		exit;
	}

	/**
	 * 查询银行卡账户余额
	 */
	public function getBankAccountBalance() {
		$data = array(
			'tranCode' => '0001',
			'entSeqNo' => date( 'Ymd' ) . explode( ' ', microtime() )[1],
			'account'  => \CgbPayConfig::outAcc,
		);

		$data_xml = $this->cgbPay->getXmlData( $data );
		$result   = $this->cgbPay->postXmlCurl( $data_xml );
		$result   = $this->cgbPay->getArrayData( $result );

		$return = 'null';
		if ( $result ) {
			$return = json_encode( $result );
		}

		echo $return;

		exit;
	}

	/**
	 * 支付宝提现入队
	 */
	public function aliTixianQueue() {
		$TixianQueue  = M( 'TixianQueue' );
		$WithdrawCash = M( 'WithdrawCash' );

		$ids = I( 'post.id' );

		$ids = is_array( $ids ) ? $ids : array( $ids );
		foreach ( $ids as $id ) {
			if ( empty( $id ) ) {
				continue;
			}

			//过滤数据：该条提现信息status必须等于0才能进行提现操作
			$filter_withdraw_cash['id']     = array( 'eq', $id );
			$filter_withdraw_cash['status'] = array( 'neq', 0 );
			$filter_withdraw_cash_info      = $WithdrawCash->where( $filter_withdraw_cash )->field( 'id' )->find();
			if ( $filter_withdraw_cash_info ) {
				continue;
			}

			$data['wcid'] = $id;
			$data['type'] = 0;
			$map['wcid']  = array( 'eq', $id );
			$count        = $TixianQueue->where( $map )->count();
			if ( $count == 0 ) {
				if ( $TixianQueue->create( $data, '', true ) ) {
					$TixianQueue->add();
				}
			}

			$map_withdraw['id']           = array( 'eq', $id );
			$data_withdraw['submit_flag'] = time();
			$WithdrawCash->where( $map_withdraw )->save( $data_withdraw );
		}

		$this->logWrite( "支付宝提现入队操作已完成[" . implode( ',', $ids ) . "]" );

		exit;
	}

	/**
	 * 提现列表导出功能
	 */
	public function withdrawExportAction() {
		$searchKey = [];

		//判断当前管理员是否具有小管理员权限
		$is_small_super = $this->isSmallSuperManager();
		$this->assign( 'is_small_super', $is_small_super );

		$mark             = $this->get['mark'];
		$idlist           = $this->get['check_val'];
		$money            = $this->get['money'];
		$userid           = $this->get['userid'];
		$is_pass          = $this->get['is_pass'];
		$time_min         = $this->get['time_min'];
		$time_max         = $this->get['time_max'];
		$tiqu_type        = $this->get['tiqu_type'];
		$is_submit        = $this->get['is_submit'];
		$page             = $this->get['p'] > 0 ? $this->get['p'] : 1;
		$export_name      = '';
		$export_type      = '';
		$search_date_type = $this->get['search_date_type'];

		$searchKey['wc.id'] = [ 'in', $idlist ];
		if ( validateExtend( $money, 'MONEY' ) ) {
			$searchKey['wc.money'] = array( 'eq', $money );
		}
		if ( ! empty( $userid ) && ! validateExtend( I( 'get.userid' ), 'NUMBER' ) && ! validateExtend( I( 'get.userid' ), 'CHS' ) ) {
			$this->error( '会员账号格式有误' );
		}

		if ( ! empty( $userid ) ) {
			$map_member['loginname'] = array( 'eq', $userid );
			$map_member['nickname']  = array( 'eq', $userid );
			$map_member['username']  = array( 'eq', $userid );
			$map_member['_logic']    = 'OR';
			$member_info             = M( 'member', 'zc_' )->where( $map_member )->field( 'id' )->select();
			if ( ! $member_info ) {
				$this->error( '用户不存在！' );
			} else {
				foreach ( $member_info as $k => $list ) {
					$uid[] = $list['id'];
				}
				$uid = implode( ',', $uid );
			}
			$searchKey['wc.uid'] = array( 'in', $uid );
		}

		if ( ! empty( $is_pass ) ) {
			$searchKey['wc.is_pass'] = array( 'eq', $is_pass );
		}

		//时间类型筛选
		$time_column = 'wc.add_time';
		if ( $search_date_type == '2' ) {
			$time_column = 'wc.submit_flag';
		} elseif ( $search_date_type == '3' ) {
			$time_column = 'wc.finish_time';
		}
		if ( ! empty( $time_min ) && ! empty( $time_max ) ) {
			$searchKey[ $time_column ] = array(
				'between',
				array( strtotime( $time_min ), strtotime( $time_max . ' 23:59:59' ) )
			);
		} elseif ( ! empty( $time_min ) ) {
			$searchKey[ $time_column ] = array( 'EGT', strtotime( $time_min ) );
		} elseif ( ! empty( $time_max ) ) {
			$searchKey[ $time_column ] = array( 'ELT', strtotime( $time_max . ' 23:59:59' ) );
		}

		//提现方式筛选条件
		switch ( $tiqu_type ) {
			case '0':
				$searchKey['wc.tiqu_type'] = array( 'eq', 0 );
				break;
			case '1':
				$searchKey['wc.tiqu_type'] = array( 'eq', 1 );
				break;
			case '2':
				$searchKey['wc.tiqu_type'] = array( 'eq', 2 );
				break;
			default:
				$searchKey['wc.tiqu_type'] = array( 'eq', 1 );
		}

		$searchKey['wc.status'] = array( 'eq', '0' );
		switch ( $is_submit ) {
			case 'S':
				$searchKey['wc.status'] = array( 'eq', 'S' );
				$export_name            = '成功转账';
				break;
			case 'F':
				$searchKey['wc.status'] = array( 'eq', 'F' );
				$export_name            = '失败处理';
				break;
			case 'N':
				$searchKey['wc.status']      = array( 'eq', '0' );
				$searchKey['wc.submit_flag'] = array( 'eq', '0' );
				$export_name                 = '还未提交';
				break;
			case 'TS':
				$searchKey['wc.status'] = array( 'eq', 'TS' );
				$export_name            = '退款成功';
				break;
			case 'TF':
				$searchKey['wc.status'] = array( 'eq', 'TF' );
				$export_name            = '退款失败';
				break;
			case 'L':
				$searchKey['wc.status']      = array( 'eq', '0' );
				$searchKey['wc.submit_flag'] = array( 'neq', '0' );
				$export_name                 = '锁定账户';
				break;
			case 'W':
				$searchKey['wc.status'] = array( 'eq', 'W' );
				$export_name            = '银行处理中(银企专用)';
				break;
			default:
				$searchKey['wc.submit_flag'] = array( 'eq', '0' );
				$export_name                 = '还未提交';
		}

		switch ( $this->get['tiqu_type'] ) {
			case '0':
				$export_type = '支付宝';
			case '1':
				$export_type = '微信';
				break;
			case '2':
				$export_type = '银行卡';
				break;
			default:
				$export_type = '未知';
		}

		//当 [提交状态]为成功转账/失败处理  + [用户帐号]为空 + [日期]为空  时,则默认不调取全部数据
		if ( ( I( 'get.is_submit' ) == 'S' || I( 'get.is_submit' ) == 'F' ) && I( 'get.userid' ) == '' && I( 'get.time_min' ) == '' && I( 'get.time_max' ) == '' ) {
			$searchKey['wc.id'] = array( 'eq', 0 );
		}

		$withdraw = M( 'WithdrawCash' );
		$count    = $withdraw
			->alias( 'wc' )
			->where( $searchKey )
			->join( 'left join zc_member m on m.id=wc.uid' )
			->field( 'wc.id' )
			->count();

		$data = $withdraw
			->alias( 'wc' )
			->join( 'left join zc_member m on m.id=wc.uid' )
			->join( 'left join zc_user_affiliate aff on aff.user_id=wc.uid' )
			->field( 'wc.*
					,m.store_flag,m.level,m.loginname,m.nickname,m.is_blacklist,m.username
					,aff.alipay_account' )
			->where( $searchKey )
			->order( 'unix_timestamp(wc.finish_time) desc,wc.id desc' )
			->page( $page, 20 )
			->select();

		//关联查询绑定银行卡信息
		if ( $tiqu_type == '2' ) {
			foreach ( $data as $k => $v ) {
// 				$bind_bank_info = M( 'WithdrawBankcard' )->where( 'uid=' . $v['uid'] )->field( 'inaccname,inacc,inaccbank,inaccadd' )->find();
				$bind_bank_info = M('BankBind')->where('user_id='.$v['uid'])->field('`name` inaccname,`cardNo` inacc, `bankName` inaccbank, `bankAddress` inaccadd')->find();
				if ( $bind_bank_info ) {
					$data[ $k ] = array_merge( $v, $bind_bank_info );
				}
			}
		}

		$export_data = array();
		if ( $tiqu_type == '1' ) {
			foreach ( $data as $k => $v ) {
				$shang   = $v[ store_flag ] == '1' ? '[商]' : '';
				$tiyan   = $v['level'] == '1' ? '[体验]' : '';
				$black   = $v['is_blacklist'] != '0' ? C( 'FIELD_CONFIG.member' )['is_blacklist'][ $v['is_blacklist'] ] . '黑名单' : '';
				$success = $fail = '';
				if ( $v[ status ] == 'S' ) {
					$success = '微信提现成功';
				} elseif ( $v[ status ] == 'F' ) {
					$success = '微信提现失败';
					$fail    = '已退款，失败原因：' . $v['failure_code'];
				} elseif ( $v[ status ] == 'TS' ) {
					$success = '已手动退款成功';
				} elseif ( $v[ status ] == 'TF' ) {
					$fail = '手动退款失败';
				} elseif ( empty( $v[ status ] ) && ! empty( $v[ submit_flag ] ) ) {
					$success = '已锁定，等待微信确认';
				}

				$vo = array(
					$v['username'],
					$v['loginname'] . '[' . $v[ nickname ] . ']' . $shang . $tiyan . $black,
					$v[ ali_inner_serial_num ],
					date( 'Y-m-d H:i:s', $v[ add_time ] ),
					$v[ serial_num ],
					$v[ receiver_acount ],
					$v[ receiver ],
					$v[ amount ],
					$v[ commission ],
					$success,
					$fail,
					empty( $v[ finish_time ] ) ? '' : ( strlen( $v[ finish_time ] ) == 10 ? date( 'Y-m-d H:i:s', $v[ finish_time ] ) : date( 'Y-m-d H:i:s', strtotime( $v[ finish_time ] ) ) )
				);

				$export_data[] = $vo;
			}

			$head_array = array(
				'用户名',
				'用户账号',
				'微信流水号',
				'申请时间',
				'序列号',
				'微信昵称',
				'真实姓名',
				'提现金额',
				'手续费',
				'是否通过',
				'失败原因',
				'完成时间'
			);
		} elseif ( $tiqu_type == '2' ) {
			foreach ( $data as $k => $v ) {
				$shang   = $v[ store_flag ] == '1' ? '[商]' : '';
				$tiyan   = $v['level'] == '1' ? '[体验]' : '';
				$black   = $v['is_blacklist'] != '0' ? C( 'FIELD_CONFIG.member' )['is_blacklist'][ $v['is_blacklist'] ] . '黑名单' : '';
				$success = $fail = '';
				if ( $v[ status ] == 'S' ) {
					$success = '银行卡提现成功';
				} elseif ( $v[ status ] == 'F' ) {
					$success = '银行卡提现失败';
					$fail    = '已退款，失败原因：' . $v[ failure_code ];
				} elseif ( $v[ status ] == 'TS' ) {
					$success = '已手动退款成功';
				} elseif ( $v[ status ] == 'TF' ) {
					$fail = '手动退款失败';
				} elseif ( $v[ status ] == 'W' ) {
					$success = '已进入提现队列，等待银行处理中';
				} elseif ( empty( $v[ status ] ) && ! empty( $v[ submit_flag ] ) ) {
					$success = '已锁定，等待进入提现队列';
				}
				//下线打开，mark=1表示标记成功
				if ( $mark == 1 ) {
					M( 'withdraw_cash' )->where( [ 'id' => $v['id'] ] )->save( [ 'status' => 'S', 'failure_code' => '', 'finish_time' => time() ] );
					$success     = '银行卡提现成功';
					$export_name = '线下打款成功';
				}

				$vo = array(
					$v['username'],
					$v['loginname'] . '[' . $v[ nickname ] . ']' . $shang . $tiyan . $black,
					$v[ ali_inner_serial_num ],
					date( 'Y-m-d H:i:s', $v[ add_time ] ),
					$v[ serial_num ],
					$v[ inaccname ],
					$v[ inacc ],
					$v[ inaccbank ],
					$v[ inaccadd ],
					$v[ commission ],
					$v[ amount ],
					$success,
					$fail,
					empty( $v[ finish_time ] ) ? '' : ( strlen( $v[ finish_time ] ) == 10 ? date( 'Y-m-d H:i:s', $v[ finish_time ] ) : date( 'Y-m-d H:i:s', strtotime( $v[ finish_time ] ) ) )
				);

				$export_data[] = $vo;
			}

			$head_array = array(
				'用户名',
				'用户账号',
				'网银流水号',
				'申请时间',
				'序列号',
				'收款人',
				'收款账号',
				'收款银行',
				'收款银行地址',
				'手续费',
				'提现金额',
				'是否通过',
				'失败原因',
				'完成时间'
			);
		} elseif ( $tiqu_type == '0' ) {
			foreach ( $data as $k => $v ) {
				$shang   = $v[ store_flag ] == '1' ? '[商]' : '';
				$tiyan   = $v['level'] == '1' ? '[体验]' : '';
				$black   = $v['is_blacklist'] != '0' ? C( 'FIELD_CONFIG.member' )['is_blacklist'][ $v['is_blacklist'] ] . '黑名单' : '';
				$success = $fail = '';
				if ( $v[ status ] == 'S' ) {
					$success = '支付宝提现成功';
				} elseif ( $v[ status ] == 'F' ) {
					$success = '支付宝提现失败';
					$fail    = '已退款，失败原因：' . $v[ failure_code ];
				} elseif ( $v[ status ] == 'TS' ) {
					$success = '已手动退款成功';
				} elseif ( $v[ status ] == 'TF' ) {
					$fail = '手动退款失败';
				} elseif ( $v[ status ] == 'W' ) {
					$success = '已进入提现队列，等待支付宝处理中';
				} elseif ( empty( $v[ status ] ) && ! empty( $v[ submit_flag ] ) ) {
					$success = '已锁定，等待进入提现队列';
				}

				$vo = array(
					$v['username'],
					$v['loginname'] . '[' . $v[ nickname ] . ']' . $shang . $tiyan . $black,
					$v[ ali_inner_serial_num ],
					date( 'Y-m-d H:i:s', $v[ add_time ] ),
					$v[ serial_num ],
					$v[ alipay_account ],
					$v[ commission ],
					$v[ amount ],
					$success,
					$fail,
					empty( $v[ finish_time ] ) ? '' : ( strlen( $v[ finish_time ] ) == 10 ? date( 'Y-m-d H:i:s', $v[ finish_time ] ) : date( 'Y-m-d H:i:s', strtotime( $v[ finish_time ] ) ) )
				);

				$export_data[] = $vo;
			}

			$head_array = array( '用户名', '用户账号', '网银流水号', '申请时间', '序列号', '收款账号', '手续费', '提现金额', '是否通过', '失败原因', '完成时间' );
		}

		$file_name .= "导出[{$export_type}]提现列表[{$export_name}]数据-" . date( 'Y-m-d' ) . "[第{$page}页]";
		$file_name = iconv( "utf-8", "gbk", $file_name );
		$return    = $this->xlsExport( $file_name, $head_array, $export_data );
		! empty( $return['error'] ) && $this->error( $return['error'] );

		$this->logWrite( "导出[{$export_type}]提现列表[{$export_name}]数据-" . date( 'Y-m-d' ) . "[第{$page}页]" );
	}

	/**
	 * 后台充值导出功能
	 */
	public function memberCashExportAction() {
		$app_unlock = M( 'member_account' );

		$searchKey = array();

		$username = $this->get['username'];
		$time_min = $this->get['time_min'];
		$time_max = $this->get['time_max'];

		if ( ! empty( $username ) ) {
			if ( ! validateExtend( $username, 'NUMBER' ) && ! validateExtend( $username, 'CHS' ) ) {
				$this->error( '会员账号格式有误' );
			}

			$map_member['mem.loginname'] = array( 'eq', $username );
			$map_member['mem.nickname']  = array( 'eq', $username );
			$map_member['mem.username']  = array( 'eq', $username );
			$map_member['_logic']        = 'OR';
			$searchKey['_complex']       = $map_member;
		}
		if ( ! empty( $time_min ) ) {
			$searchKey['ac_post_time'][] = array( 'egt', strtotime( $time_min . ' 00:00:00' ) );
		}
		if ( ! empty( $time_max ) ) {
			$searchKey['ac_post_time'][] = array( 'elt', strtotime( $time_max . '23:59:59' ) );
		}
		if ( count( $searchKey['ac_post_time'] ) == 1 ) {
			$searchKey['ac_post_time'] = $searchKey['ac_post_time'][0];
		}

		$no_page = true;
		if ( empty( $time_max ) && empty( $time_min ) ) {
			$no_page = false;
		}

		$limit = '';
		if ( ! $no_page ) {
			$p         = $this->get['p'];
			$p         = $p <= 1 ? 1 : $p;
			$perpage   = 20;
			$p_current = ( $p - 1 ) * $perpage;
			$limit     = $p_current . ',' . $perpage;
		} else {
			if ( ( strtotime( $time_max . '23:59:59' ) - strtotime( $time_min . ' 00:00:00' ) ) > 31 * 24 * 3600 ) {
				$this->assign( 'closeWin', 1 );
				$this->error( '按天导出数据时筛选日期最多为31天' );
			}
			$p = 'ALL';
		}

		$data = $app_unlock
			->alias( 'ma' )
			->join( 'left join __MEMBER__ mem ON mem.id=ma.ac_uid' )
			->where( $searchKey )
			->order( 'ma.ac_post_time desc' )
			->limit( $limit )
			->group( 'ma.ac_id' )
			->select();

		$export_data = array();
		foreach ( $data as $k => $v ) {
			$ac_type_list = array(
				'51' => '现金币',
				'52' => '丰谷宝',
				'53' => '商超券',
				'54' => '积分',
				'58' => '丰收点',
				'67' => '转账'
			);
			foreach ( $ac_type_list as $k1 => $v1 ) {
				if ( preg_match( '/' . $k1 . '$/', $v['ac_type'] ) ) {
					$ac_type  = $v1;
					$ac_money = preg_match( '/^-/', $v['ac_type'] ) ? '-' : '+';
					$ac_money = $ac_money . $v['ac_money'];
					break;
				}
			}

			//针对v5.0新增币种的处理
			switch ( $v['ac_type'] ) {
				case '613':
					$ac_type  = '注册币';
					$ac_money = '+' . $ac_money;
					break;
				case '606':
					$ac_type  = '注册币';
					$ac_money = '-' . $ac_money;
					break;
				case '712':
					$ac_type  = '特供券';
					$ac_money = '+' . $ac_money;
					break;
				case '702':
					$ac_type  = '特供券';
					$ac_money = '-' . $ac_money;
					break;
				case '813':
					$ac_type  = '商城积分';
					$ac_money = '+' . $ac_money;
					break;
				case '802':
					$ac_type  = '商城积分';
					$ac_money = '-' . $ac_money;
					break;
				case '912':
					$ac_type  = '乐享币';
					$ac_money = '+' . $ac_money;
					break;
				case '902':
					$ac_type  = '乐享币';
					$ac_money = '-' . $ac_money;
					break;
			}

			$vo = array(
				$v['username'],
				$v[ loginname ] . '[' . $v[ nickname ] . ']',
				$ac_type,
				$ac_money,
				date( 'Y-m-d H:i:s', $v['ac_post_time'] ),
				$v['beizhu']
			);

			$export_data[] = $vo;
		}


		$head_array = array( '用户名', '用户账号', '帐号类型', '操作金额', '操作时间', '备注说明' );
		$file_name  .= "导出后台充值记录列表数据-" . date( 'Y-m-d' ) . "[第{$p}页]";
		$file_name  = iconv( "utf-8", "gbk", $file_name );
		$return     = $this->xlsExport( $file_name, $head_array, $export_data );
		! empty( $return['error'] ) && $this->error( $return['error'] );

		$this->logWrite( "导出后台充值记录列表数据-" . date( 'Y-m-d' ) . "[第{$p}页]" );
	}

	/**
	 * 拨比导出功能
	 */
	public function ratioExportAction() {
		$time_min = $this->get['time_min'];
		$time_max = $this->get['time_max'];
		$page     = $this->get['p'] > 0 ? $this->get['p'] : 1;

		$where = '';

		if ( ! empty( $time_min ) ) {
			$time_min = date( 'Ymd', strtotime( $time_min ) );
			$where    .= " and finance_tag>='{$time_min}' ";
		}
		if ( ! empty( $time_max ) ) {
			$time_max = date( 'Ymd', strtotime( $time_max ) );
			$where    .= " and finance_tag<='{$time_max}' ";
		}

		$no_page = true;
		if ( empty( $time_max ) && empty( $time_min ) ) {
			$no_page = false;
		}
		$limit = '';
		if ( ! $no_page ) {
			$p = $page;
		} else {
			if ( ( strtotime( $time_max . '23:59:59' ) - strtotime( $time_min . ' 00:00:00' ) ) > 31 * 24 * 3600 ) {
				$this->assign( 'closeWin', 1 );
				$this->error( '按天导出数据时筛选日期最多为31天' );
			}
			$page = false;
			$p    = 'ALL';
		}

		$FinanceModel = new FinanceModel();
		$data         = $FinanceModel->getPageList( '
		    finance_profits,
		    finance_profits_colorcoin,
		    finance_maker,
		    finance_expenditure,
		    finance_withdraw_fee,
			finance_applymicrovip,
		    finance_applyvip,
			finance_applyhonourvip,
			finance_tax_colorcoin,
			(finance_tax_goldcoin+finance_tax_colorcoin+finance_tax_cash+finance_tax_enroll+finance_tax_supply+finance_tax_credits) finance_tax,
		    finance_managefee_colorcoin,
			(finance_managefee_goldcoin+finance_managefee_colorcoin+finance_managefee_cash+finance_managefee_enroll+finance_managefee_supply+finance_managefee_credits) finance_managefee
		    finance_tag
		', date( 'Ymd' ), $page, 10, $where );

		$data = $data['list'];

		$export_data = array();
		foreach ( $data as $k => $v ) {
			$profits                    = $v['finance_profits'] + $v['finance_maker'] + $v['finance_applymicrovip'] + $v['finance_applyvip'] + $v['finance_applyhonourvip'] + $v['finance_tax'] + $v['finance_managefee'];
			$profits_no_colorcoin       = $profits - $v['finance_profits_colorcoin'] - $v['finance_tax_colorcoin'] - $v['finance_managefee_colorcoin'];
			$profits_total              = $profits - $v['finance_expenditure'];
			$profits_total_no_colorcoin = $profits_no_colorcoin - $v['finance_expenditure'];
			$ratio                      = sprintf( '%.2f', ( $v['finance_expenditure'] ) / $profits * 100 ) . '%';
			$ratio_no_colorcoin         = sprintf( '%.2f', ( $v['finance_expenditure'] ) / $profits_no_colorcoin * 100 ) . '%';
			$vo                         = array(
				$v['finance_tag'],
				$profits,//."(不含商超券：{$profits_no_colorcoin})",
				$v['finance_expenditure'],
				$profits_total,//."(不含商超券：{$$profits_total_no_colorcoin})",
				$ratio,//."(不含商超券：{$ratio_no_colorcoin})"
			);

			$export_data[] = $vo;
		}


		$head_array = array( '日期', '总收入', '总支出', '总盈利', '拨出比率' );
		$file_name  .= "导出后台拨比查询数据-" . date( 'Y-m-d' ) . "[第{$p}页]";
		$file_name  = iconv( "utf-8", "gbk", $file_name );
		$return     = $this->xlsExport( $file_name, $head_array, $export_data );
		! empty( $return['error'] ) && $this->error( $return['error'] );

		$this->logWrite( "导出后台拨比查询数据-" . date( 'Y-m-d' ) . "[第{$p}页]" );
	}

}

?>