<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 账户明细接口2.0  
// +----------------------------------------------------------------------
namespace APP\Controller;

use Common\Controller\ApiController;
use V4\Model\Image;
use V4\Model\OrderModel;
use V4\Model\PaymentMethod;
use V4\Model\MemberModel;
use V4\Model\AccountRecordModel;
use V4\Model\CurrencyAction;
use V4\Model\AccountModel;
use V4\Model\AccountFinanceModel;
use V4\Model\Currency;
use V4\Model\WithdrawModel;

class Hack2Controller extends ApiController
{

    private $goldcoin_name;

    public function __construct()
    {
        parent::__construct();

        $this->goldcoin_name = C('Coin_Name');
    }

//	/**
//	 * 货币明细详情
//	 * Enter description here ...
//	 */
//	public function record_info() {
//		$record_id = intval( I( 'post.id' ) );
//		$month     = intval( I( 'post.month' ) );
//		$type      = intval( I( 'post.type' ) );  //1.现金; 2丰谷宝； 3商超券； 4特供券； 5商城积分
//
//		//验证参数
//		$suffix = verify_cash_list( 1, $month );
//
//
//		if ( $type == 1 ) {
//			$currency = Currency::Cash;
//		} elseif ( $type == 2 ) {
//			$currency = Currency::GoldCoin;
//		} elseif ( $type == 3 ) {
//			$currency = Currency::ColorCoin;
//		} elseif ( $type == 4 ) {
//			$currency = Currency::Supply;
//		} elseif ( $type == 5 ) {
//			$currency = Currency::Credits;
//		}
//		//查询记录
//		$arm    = new AccountRecordModel();
//		$record = $arm->getById( $record_id, $currency, $suffix );
//		if ( $record ) {
//			//转换附件信息
//			$attach = json_decode( $record['record_attach'], true );
//
//			$info              = array();
//			$info['amount']    = sprintf( '%.2f', $record['record_amount'] );
//			$info['action']    = $record['record_remark'];
//			$info['addtime']   = $record['record_addtime'];
//			$info['order_no']  = $attach['serial_num'];
//			$info['from_name'] = $attach['from_name'];
//			$info['pic']       = $attach['pic'];
//
//			foreach ( $info as $k => $v ) {
//				if ( empty( $v ) || $v == '' ) {
//					$info[ $k ] = '';
//				}
//			}
//			$this->myApiPrint( '查询成功！', 400, $info );
//
//		} else {
//			$this->myApiPrint( '对不起，用户数据不正确！', 300, $info );
//		}
//	}

    /**
     * 现金明细
     *
     * @param uid 会员id
     * @param page 分页参数（page=1）
     * @param tag 1收入；0支出
     * @param month 格式201707
     */
    public function cash_details()
    {
        $uid = intval(I('post.uid'));
        $month = I('post.month');
        $tag = intval(I('post.tag')) ?: '0';  //1=收入  0=支出*/
        $pn = intval(I('post.page')) ?: 1;

        //验证参数
        $month_suffix = verify_cash_list($uid, $month, $tag);

        //加载数据
        $arm = new AccountRecordModel();
        $data = $arm->getPageList($uid, Currency::Cash, $month_suffix, $pn, $tag);

        $tagstr = '';
        if ($tag == 1) {
            $tagstr = '+';
        }
        $wm = new WithdrawModel();
        //处理数据
        $return = array();
        foreach ($data['list'] as $k => $v) {
            if ($v['record_amount'] < 0.01 && $v['record_amount'] > -0.01) {
                continue;
            }
            $row = array();
            $row['suffix'] = $month;
            $row['amount'] = $tagstr . sprintf('%.2f', $v['record_amount']);
            $row['id'] = $v['record_id'];
            $row['addtime'] = $v['record_addtime'];
            $row['addtime2'] = date('Y-m-d H:i:s', $v['record_addtime']);
            $row['status'] = '已完成';
            $row['action'] = CurrencyAction::getLabel($v['record_action']);

            //处理附件信息
            $obj = json_decode($v['record_attach'], true);
            $attach = $arm->initAtach($obj, Currency::Cash, $month_suffix, $v['record_id'], $v['record_action']);
            $row['from_name'] = $arm->getFinalName($attach['from_uid'], $attach['from_name']);
            $row['from_pic'] = Image::url($attach['pic']);
            
            //转入转出记录添加用户信息
            $in_out_action = [109,152, 209,252, 318,360, 405,452, 605,652, 718,760, 808,852];
            if (in_array($v['record_action'], $in_out_action)) {
            	$from_loginname = M('Member')->where('id='.$attach['from_uid'])->getField('loginname');
            	if ($v['record_amount'] > 0) {
            		$row['action'] = '收到'. $attach['from_name']. "({$from_loginname})". $row['action'];
            	} else {
            		$row['action'] = $row['action']. $attach['from_name']. "({$from_loginname})";
            	}
            }
            
            //处理提现进度
            if ($v['record_action'] == CurrencyAction::CashTixian) {
                $row['status'] = $wm->getStatus($obj['serial_num']);
            }
            if ($v['record_action'] == CurrencyAction::CashTixianShouxufei) {
                $row['status'] = $wm->getStatus($obj['serial_num']);
            }
            $return[] = $row;
        }

        $data['list'] = $return;
        //fuck($return, $array2, $array3);

        $this->myApiPrint('查询成功！', 400, $data);
    }

    /**
     * 丰谷宝明细
     *
     * @param uid 会员ID=4076
     * @param page 分页参数（page=0显示1-10条数据）
     */
    public function goldcoin_details()
    {
        $uid = intval(I('post.uid'));
        $month = I('post.month');
        $tag = intval(I('post.tag'));  //1=收入  0=支出*/
        $pn = intval(I('post.page'));

        //验证参数
        $month_suffix = verify_cash_list($uid, $month, $tag);

        //加载数据
        $arm = new AccountRecordModel();
        $data = $arm->getPageList($uid, Currency::GoldCoin, $month_suffix, $pn, $tag);

        $tagstr = '';
        if ($tag == 1) {
            $tagstr = '+';
        }
        //处理数据
        $return = array();
        foreach ($data['list'] as $k => $v) {
            if ($v['record_amount'] < 0.01) {
                //continue;
            }
            $row = array();
            $row['suffix'] = $month;
            $row['amount'] = sprintf('%s%.4f', $tagstr, $v['record_amount']);
            $row['id'] = $v['record_id'];
            $row['addtime'] = $v['record_addtime'];
            $row['addtime2'] = date('Y-m-d H:i:s', $v['record_addtime']);
            $row['status'] = '已完成';
            $row['action'] = CurrencyAction::getLabel($v['record_action']);

            $obj = json_decode($v['record_attach'], true);
            $attach = $arm->initAtach($obj, Currency::GoldCoin, $month_suffix, $v['record_id'], $v['record_action']);
            $row['from_name'] = $arm->getFinalName($attach['from_uid'], $attach['from_name']);
            $row['from_pic'] = $attach['pic'];
            
            //转入转出记录添加用户信息
            $in_out_action = [109,152, 209,252, 318,360, 405,452, 605,652, 718,760, 808,852];
            if (in_array($v['record_action'], $in_out_action)) {
            	$from_loginname = M('Member')->where('id='.$attach['from_uid'])->getField('loginname');
            	if ($v['record_amount'] > 0) {
            		$row['action'] = '收到'. $attach['from_name']. "({$from_loginname})". $row['action'];
            	} else {
            		$row['action'] = $row['action']. $attach['from_name']. "({$from_loginname})";
            	}
            }
            
            //转让到公共市场明细增加第三方信息
            if ($v['record_action'] == CurrencyAction::GoldCoinTransferToGRB) {
            	$wallet_type_config = C('FIELD_CONFIG')['trade']['type'];
            	$wallet_type = isset($wallet_type_config[$obj['type']]) ? $wallet_type_config[$obj['type']] : 'unknown';
            	$row['action'] = $row['action']. "({$wallet_type})";
            }
            
            //针对record_action=151的明细: 若record_remark='转出到锁定通证',则返回字段的action='转出到锁定通证'
            if ($v['record_action'] == '151' && $v['record_remark'] == '转出到锁定通证') {
            	$row['action'] = '转出到锁定通证';
            }
            //针对record_action=101的明细: 若record_remark='恢复锁定通证',则返回字段的action='恢复锁定通证'
            if ($v['record_action'] == '101' && $v['record_remark'] == '恢复锁定通证') {
            	$row['action'] = '恢复锁定通证';
            }
            
            $return[] = $row;
        }

        $data['list'] = $return;
        //fuck($return, $array2, $array3);

        $this->myApiPrint('查询成功！', 400, $data);
    }

    /**
     * 积分明细
     */
    public function points_details()
    {
        $uid = intval(I('post.uid'));
        $month = I('post.month');
        $tag = intval(I('post.tag'));  //1=收入  0=支出*/
        $pn = intval(I('post.page'));

        //验证参数
        $month_suffix = verify_cash_list($uid, $month, $tag);

        //加载数据
        $arm = new AccountRecordModel();
        $data = $arm->getPageList($uid, Currency::Points, $month_suffix, $pn, $tag);

        $tagstr = '';
        if ($tag == 1) {
            $tagstr = '+';
        }
        //处理数据
        $return = array();
        foreach ($data['list'] as $k => $v) {
            if ($v['record_amount'] < 0.01) {
                continue;
            }
            $row = array();
            $row['suffix'] = $month;
            $row['amount'] = $tagstr . sprintf('%.2f', $v['record_amount']);
            $row['id'] = $v['record_id'];
            $row['addtime'] = $v['record_addtime'];
            $row['addtime2'] = date('Y-m-d H:i:s', $v['record_addtime']);
            $row['status'] = '已完成';
            $row['action'] = CurrencyAction::getLabel($v['record_action']);

            $obj = json_decode($v['record_attach'], true);
            $attach = $arm->initAtach($obj, Currency::Enroll, $month_suffix, $v['record_id'], $v['record_action']);
            $row['from_name'] = $arm->getFinalName($attach['from_uid'], $attach['from_name']);
            $row['from_pic'] = $attach['pic'];
            $return[] = $row;
        }

        $data['list'] = $return;
        //fuck($return, $array2, $array3);

        $this->myApiPrint('查询成功！', 400, $data);
    }
    
    /**
     * 报单币明细
     *
     * @param uid 会员ID=4076
     * @param page 分页参数（page=0显示1-10条数据）
     */
    public function supply_details()
    {
    	$uid = intval(I('post.uid'));
    	$month = I('post.month');
    	$tag = intval(I('post.tag'));  //1=收入  0=支出*/
    	$pn = intval(I('post.page'));
    
    	//验证参数
    	$month_suffix = verify_cash_list($uid, $month, $tag);
    
    	//加载数据
    	$arm = new AccountRecordModel();
    	$data = $arm->getPageList($uid, Currency::Supply, $month_suffix, $pn, $tag);
    
    	$tagstr = '';
    	if ($tag == 1) {
    		$tagstr = '+';
    	}
    	//处理数据
    	$return = array();
    	foreach ($data['list'] as $k => $v) {
    		if ($v['record_amount'] < 0.01) {
    			//continue;
    		}
    		$row = array();
    		$row['suffix'] = $month;
    		$row['amount'] = sprintf('%s%.4f', $tagstr, $v['record_amount']);
    		$row['id'] = $v['record_id'];
    		$row['addtime'] = $v['record_addtime'];
    		$row['addtime2'] = date('Y-m-d H:i:s', $v['record_addtime']);
    		$row['status'] = '已完成';
    		$row['action'] = CurrencyAction::getLabel($v['record_action']);
    
    		$obj = json_decode($v['record_attach'], true);
    		$attach = $arm->initAtach($obj, Currency::Supply, $month_suffix, $v['record_id'], $v['record_action']);
    		$row['from_name'] = $arm->getFinalName($attach['from_uid'], $attach['from_name']);
    		$row['from_pic'] = $attach['pic'];
    		$return[] = $row;
    	}
    
    	$data['list'] = $return;
    
    	$this->myApiPrint('查询成功！', 400, $data);
    }
    
    /**
     * 澳洲SKN股数明细
     *
     * @param uid 会员ID=4076
     * @param page 分页参数（page=0显示1-10条数据）
     */
    public function enjoy_details()
    {
    	$uid = intval(I('post.uid'));
    	$month = I('post.month');
    	$tag = intval(I('post.tag'));  //1=收入  0=支出*/
    	$pn = intval(I('post.page'));
    
    	//验证参数
    	$month_suffix = verify_cash_list($uid, $month, $tag);
    
    	//加载数据
    	$arm = new AccountRecordModel();
    	$data = $arm->getPageList($uid, Currency::Enjoy, $month_suffix, $pn, $tag);
    
    	$tagstr = '';
    	if ($tag == 1) {
    		$tagstr = '+';
    	}
    	//处理数据
    	$return = array();
    	foreach ($data['list'] as $k => $v) {
    		if ($v['record_amount'] < 0.01) {
    			//continue;
    		}
    		$row = array();
    		$row['suffix'] = $month;
    		$row['amount'] = sprintf('%s%.4f', $tagstr, $v['record_amount']);
    		$row['id'] = $v['record_id'];
    		$row['addtime'] = $v['record_addtime'];
    		$row['addtime2'] = date('Y-m-d H:i:s', $v['record_addtime']);
    		$row['status'] = '已完成';
    		$row['action'] = CurrencyAction::getLabel($v['record_action']);
    
    		$obj = json_decode($v['record_attach'], true);
    		$attach = $arm->initAtach($obj, Currency::Enjoy, $month_suffix, $v['record_id'], $v['record_action']);
    		$row['from_name'] = $arm->getFinalName($attach['from_uid'], $attach['from_name']);
    		$row['from_pic'] = $attach['pic'];
    		$return[] = $row;
    	}
    
    	$data['list'] = $return;
    
    	$this->myApiPrint('查询成功！', 400, $data);
    }


    /**
     * 提现明细
     */
    public function withdraw()
    {
        $uid = intval(I('post.uid'));
        $pn = intval(I('post.page'));
        if ($pn < 1) {
            $pn = 1;
        }
        //1.验证数据
        $user = verify_user($uid);
        //查询
        $wm = new WithdrawModel();
        $list = $wm->getListByUserId($uid, $pn);

        $this->myApiPrint('查询成功！', 400, $list);

    }


    /**
     * 充值明细
     */
    public function refill()
    {
        $uid = intval(I('post.uid'));
        $month = I('post.month');  //201707
        $pn = intval(I('post.page'));

        //验证参数
        $month_suffix = verify_cash_list($uid, $month);

        //加载数据
        $arm = new AccountRecordModel();
        $where = ' and record_action = ' . CurrencyAction::CashChongzhi;
        $data = $arm->getPageList($uid, Currency::Cash, $month, $pn, 2, 10, $where);

        $tagstr = '';
        if ($tag == 1) {
            $tagstr = '+';
        }
        $member = M('member')->field('img,nickname')->find($uid);
        //处理数据
        $return = array();
        foreach ($data['list'] as $k => $v) {
            $row = array();
            $row['suffix'] = $month;
            $row['amount'] = $tagstr . $v['record_amount'];
            $row['id'] = $v['record_id'];
            $row['addtime'] = $v['record_addtime'];
            $row['addtime2'] = date('Y-m-d H:i:s', $v['record_addtime']);
            $row['status'] = '已完成';
            $row['action'] = $v['record_remark'];

            $obj = json_decode($v['record_attach'], true);
            $attach = $arm->initAtach($obj, Currency::Cash, $month_suffix, $v['record_id'], $v['record_action']);
            $row['from_name'] = $member['nickname'];
            $row['from_pic'] = $member['img'];
            $return[] = $row;
        }

        $data['list'] = $return;
        //fuck($return, $array2, $array3);

        $this->myApiPrint('查询成功！', 400, $data);

    }


    /**
     * 丰收点明细 1=增加
     * Enter description here ...
     */
    public function bonuslist()
    {
        $uid = intval(I('post.uid'));
        $month = I('post.month');
        $pn = intval(I('post.page'));

        //验证参数
        verify_cash_list($uid, $month);

        //加载数据
        $arm = new AccountRecordModel();
        $data = $arm->getPageList($uid, Currency::Bonus, $month, $pn);

        //处理数据
        $return = array();
        foreach ($data['list'] as $k => $v) {
            $row = array();
            $tag = ($v['record_amount'] > 0) ? '+' : '';
            $row['amount'] = $tag . $v['record_amount'];
            $row['id'] = $v['record_id'];
            $row['addtime'] = $v['record_addtime'];
            $row['addtime2'] = date('Y-m-d H:i:s', $v['record_addtime']);
            $row['status'] = '已完成';
            //$row['action'] = $v['record_remark'];
            $row['action'] = CurrencyAction::getLabel($v['record_action']);

            $obj = json_decode($v['record_attach'], true);
            $attach = $arm->initAtach($obj, Currency::Bonus, $month, $v['record_id'], $v['record_action']);
            $row['from_name'] = $arm->getFinalName($attach['from_uid'], $attach['from_name']);
            $row['from_pic'] = $attach['pic'];
            $return[] = $row;
        }

        $data['list'] = $return;
        //fuck($return, $array2, $array3);

        $this->myApiPrint('查询成功！', 400, $data);
    }


//	/**
//	 * 丰收积分明细 1=增加
//	 * Enter description here ...
//	 */
//	public function pointslist() {
//		$uid   = intval( I( 'post.uid' ) );
//		$month = I( 'post.month' );
//		$pn    = intval( I( 'post.page' ) );
//
//		//验证参数
//		verify_cash_list( $uid, $month );
//		//加载数据
//		$arm  = new AccountRecordModel();
//		$data = $arm->getPageList( $uid, Currency::Points, $month, $pn );
//
//		//处理数据
//		$return = array();
//		foreach ( $data['list'] as $k => $v ) {
//			$row             = array();
//			$tag             = ( $v['record_amount'] > 0 ) ? '+' : '';
//			$row['amount']   = $tag . $v['record_amount'];
//			$row['id']       = $v['record_id'];
//			$row['addtime']  = $v['record_addtime'];
//			$row['addtime2'] = date( 'Y-m-d H:i:s', $v['record_addtime'] );
//			$row['status']   = '已完成';
//			//$row['action'] = $v['record_remark'];
//			$row['action'] = CurrencyAction::getLabel( $v['record_action'] );
//
//			$obj              = json_decode( $v['record_attach'], true );
//			$attach           = $arm->initAtach( $obj, Currency::Points, $month, $v['record_id'], $v['record_action'] );
//			$row['from_name'] = $arm->getFinalName( $attach['from_uid'], $attach['from_name'] );
//			$row['from_pic']  = $attach['pic'];
//			$return[]         = $row;
//		}
//
//		$data['list'] = $return;
//		//fuck($return, $array2, $array3);
//
//		$this->myApiPrint( '查询成功！', 400, $data );
//	}


//	/**
//	 * 我-责任消费页面
//	 * Enter description here ...
//	 */
//	public function dutypay_index() {
//		$this->myApiPrint( '接口暂停使用', 300 );
//		$uid = intval( I( 'post.uid' ) );
//		if ( $uid < 1 ) {
//			$this->myApiPrint( '参数错误' );
//		}
//		$parameter = M( 'parameter', 'g_' )->find();
//		$member    = M( 'member' )->find( $uid );
//		if ( ! $member ) {
//			$this->myApiPrint( '参数不存在' );
//		}
//
//		M()->execute( 'INSERT IGNORE INTO `zc_dutyconsume`(`user_id`, `dutyconsume_income_enable`, `dutyconsume_uptime`) VALUES(' . $uid . ', 1, UNIX_TIMESTAMP())' );
//		$consume = M( 'dutyconsume' )->where( 'user_id = ' . $uid )->find();
//
//		//累计收益
//		//$data['totalpofits'] = sprintf('%.4f', $consume['dutyconsume_lasttotal_income']);
//		$data['totalpofits'] = '0.00';
//		//上周收益
//		$data['prev_week_totalcash'] = sprintf( '%.4f', $consume['dutyconsume_lastweek_income'] );
//		$data['week_dutypay_cash']   = sprintf( '%.4f', $consume['dutyconsume_need_amount'] );
//		$data['week_dytypay_count']  = sprintf( '%.4f', $consume['dutyconsume_complete_amount'] );
//		$rest                        = $consume['dutyconsume_need_amount'] - $consume['dutyconsume_complete_amount'];
//		$data['week_dutypay_rest']   = sprintf( '%.2f', $rest > 0 ? ceil( $rest * 100 ) / 100 : 0 );
//
//		$this->myApiPrint( '查询成功！', 400, $data );
//	}
//
//
//	/**
//	 * 去责任消费页面
//	 */
//	public function dutyconsume() {
//		$user_id = intval( I( 'post.user_id' ) );
//
//		$member = M( 'member' )->where( 'id=' . $user_id )->find();
//		if ( $member['level'] <= 2 ) {
//			$this->myApiPrint( '您的等级不支持责任消费' );
//		}
//
//		//获取余额
//		$om      = new AccountModel();
//		$balance = $om->getItemByUserId( $user_id );
//
//		$data['goldcoin']  = sprintf( '%.2f', $balance['account_goldcoin_balance'] );
//		$data['cash']      = sprintf( '%.2f', $balance['account_cash_balance'] );
//		$data['colorcoin'] = sprintf( '%.2f', $balance['account_colorcoin_balance'] );
//		//提示说明
//		$data['notice'] = M( 'g_parameter', null )->where( 'id=1' )->getField( 'duty_consume_exchange_explain' );
//		//责任消费金额
//		$consume        = M( 'dutyconsume' )->where( 'user_id = ' . $user_id )->find();
//		$rest           = $consume['dutyconsume_need_amount'] - $consume['dutyconsume_complete_amount'];
//		$data['amount'] = sprintf( '%.2f', $rest > 0 ? ceil( $rest * 100 ) / 100 : 0 );
//
//		$this->myApiPrint( '获取成功', 400, $data );
//	}


    /**
     * 冻结明细
     *
     */
    public function frozen_details()
    {
        $uid = intval(I('post.uid'));
        $curreny = I('post.curreny');  //货币*/
        $pn = intval(I('post.page'));

        $where['frozen_status'] = 1;
        $where['user_id'] = $uid;
        $where['frozen_' . $curreny] = array('gt', 0);
        $list = M('frozen_fund')->where($where)->field('frozen_' . $curreny . ' as amount, frozen_addtime as addtime, frozen_remark as action')->limit(10)->page($pn)->order('frozen_id desc')->select();

        $arm = new AccountRecordModel();
        foreach ($list as $k => $v) {
            $list[$k]['suffix'] = '';
            $list[$k]['status'] = '已完成';
            $attach = $arm->initAtach(array(), '', '', 0, '');
            $list[$k]['from_pic'] = $attach['pic'];
            $list[$k]['from_name'] = $attach['from_name'];
        }

        $data['paginator'] = array('totalPage' => 0, 'everyPage' => 10);
        $data['list'] = $list;
        $this->myApiPrint('查询成功！', 400, $data);
    }


    /**
     * 业绩结束记录
     */
    public function yejijiesuanjilu()
    {
        $user = getUserInBashu();
        $page = intval(I('post.page'));
        if ($page < 1) {
            $page = 1;
        }
        $ps = 10;
        $list = M('performance_reward_record p')
            ->field('m.id, m.role, m.img, m.is_partner, m.star, p.user_level `level`, p.user_star, p.user_reward_amount, p.record_status, p.record_addtime')
            ->join('left join zc_member m on m.id = p.user_id')
            ->order('p.record_id desc')
            ->limit($ps)->page($page)
            ->where(['user_id' => $user['id'], 'record_status' => 3])
            ->select();
        foreach ($list as $k => $v) {
            $list[$k]['dengji'] = getrole($v);
            $list[$k]['shijian'] = date('m-d H:i:s', $v['record_addtime']);
            $list[$k]['user_reward_amount'] = sprintf('%.2f', $v['user_reward_amount']);
            $list[$k]['img'] = Image::url($v['img']);
            if ($v['record_status'] == 0) {
                $list[$k]['status'] = '未执行';
            } elseif ($v['record_status'] == 1) {
                $list[$k]['status'] = '执行中';
            } elseif ($v['record_status'] == 2) {
                $list[$k]['status'] = '已失败';
            } elseif ($v['record_status'] == 3) {
                $list[$k]['status'] = '已完成';
            }
        }
        $this->myApiPrint('获取成功', 400, $list);
    }


    /**
     * 加权分红记录
     */
    public function jiaquanfenhongjilu()
    {
        $user = getUserInBashu();
        $page = intval(I('post.page'));
        if ($page < 1) {
            $page = 1;
        }
        $ps = 10;
        $list = M('performance_bonus_record p')
            ->field('m.id, p.user_role role, p.user_star star, m.img, p.user_is_partner is_partner, m.star, p.user_level `level`, p.user_star, p.record_bonus_amount user_reward_amount, p.record_status, p.record_addtime')
            ->join('left join zc_member m on m.id = p.user_id')
            ->order('p.record_id desc')
            ->where(['user_id' => $user['id'], 'record_status' => 3])
            ->limit($ps)->page($page)
            ->select();
        foreach ($list as $k => $v) {
            $list[$k]['dengji'] = getrole($v);
            $list[$k]['shijian'] = date('m-d H:i:s', $v['record_addtime']);
            $list[$k]['user_reward_amount'] = sprintf('%.2f', $v['user_reward_amount']);
            $list[$k]['img'] = Image::url($v['img']);
            if ($v['record_status'] == 0) {
                $list[$k]['status'] = '未执行';
            } elseif ($v['record_status'] == 1) {
                $list[$k]['status'] = '执行中';
            } elseif ($v['record_status'] == 2) {
                $list[$k]['status'] = '已失败';
            } elseif ($v['record_status'] == 3) {
                $list[$k]['status'] = '已完成';
            }
        }
        $this->myApiPrint('获取成功', 400, $list);
    }


}

?>