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
use Common\Controller\UploadController;
use V4\Model\LockModel;
use V4\Model\OrderModel;
use V4\Model\PaymentMethod;
use V4\Model\MemberModel;
use V4\Model\AccountRecordModel;
use V4\Model\CurrencyAction;
use V4\Model\AccountModel;
use V4\Model\Currency;
use V4\Model\AccountFinanceModel;
use V4\Model\ProcedureModel;
use V4\Model\SettingsModel;

class HackController extends ApiController {

	private $goldcoin_name;

	public function __construct() {
		parent::__construct();

		$this->goldcoin_name = C( 'Coin_Name' );
	}


	/**
	 * 获取协议
	 */
	public function getAgreement() {
		$field     = getString( I( 'post.field' ), '*' );
		$agreement = M( 'agreement' )->field( $field )->find();
		$this->myApiPrint( '获取成功', 400, $agreement );
	}

	/**
	 * 申请创客展示信息
	 *
	 * @param uid 会员ID
	 */
	public function hack() {
        $this->myApiPrint('暂未开放');
		$uid = I( 'post.uid' );

		$where['id'] = $uid;
		$member      = M( 'member' )->where( $where )->find();
		if ( empty( $member ) ) {
			$this->myApiPrint( '用户不存在，非法访问！' );
		}

//        if (M('orders')->where(['uid' => $uid, 'start_time' => [['egt', strtotime(date('Y-m-d'))], ['lt', strtotime(date('Y-m-d', strtotime('+1 day')))]], 'exchangeway' => 3])->count()) {
//            $this->myApiPrint('你今天已经激活一次了');
//        }

//        if ($member['is_tt'] == 1) {
//            $this->myApiPrint('你已经解锁，无需重复操作', 300);
//        }

//        if ($member['level'] != 1) {
//            $this->myApiPrint('你的身份异常，不能申请！', 300);
//        }
        $balance = floatval(AccountModel::getInstance()->getBalance($uid, Currency::Supply));
        $unlock_assets_fee = floatval(SettingsModel::getInstance()->get('unlock_assets_fee')) * 0.01;

        $options = [];

        $pay_per_25 = $balance * $unlock_assets_fee * 0.25;
        if ($pay_per_25 >= 0.01) {
            $options[] = [
                'label' => sprintf('激活%s(%.2f)锁定资产，需支付￥%.2f元', '25%', $balance * 0.25, $pay_per_25),
                'value' => '25',
                'pay_amount' => sprintf('￥%.2f元', $pay_per_25),
                'isSelect' => false
            ];
        }
        $pay_per_50 = $balance * $unlock_assets_fee * 0.5;
        if ($pay_per_50 >= 0.01) {
            $options[] = [
                'label' => sprintf('激活%s(%.2f)锁定资产，需支付￥%.2f元', '50%', $balance * 0.5, $pay_per_50),
                'value' => '50',
                'pay_amount' => sprintf('￥%.2f元', $pay_per_50),
                'isSelect' => false
            ];
        }
        $pay_per_75 = $balance * $unlock_assets_fee * 0.75;
        if ($pay_per_75 >= 0.01) {
            $options[] = [
                'label' => sprintf('激活%s(%.2f)锁定资产，需支付￥%.2f元', '75%', $balance * 0.75, $pay_per_75),
                'value' => '75',
                'pay_amount' => sprintf('￥%.2f元', $pay_per_75),
                'isSelect' => false
            ];
        }
        $pay_per_100 = $balance * $unlock_assets_fee * 1;
        if ($pay_per_100 >= 0.01) {
            $options[] = [
                'label' => sprintf('激活%s(%.2f)锁定资产，需支付￥%.2f元', '100%', $balance * 1, $pay_per_100),
                'value' => '100',
                'pay_amount' => sprintf('￥%.2f元', $pay_per_100),
                'isSelect' => false
            ];
        }

        if (!$options) {
            $this->myApiPrint('你没有需要解锁的资产', 300);
        }

        $lastOption = end($options);
        $lastOption['isSelect'] = true;
        $options[count($options) - 1] = $lastOption;

        $data = [
            'agreement' => SettingsModel::getInstance()->get('unlock_assets_agreement'),
            'lock_balance' => sprintf('锁定资产余额：%.4f份',$balance),
            'options' => $options,
            'pay_amount' => $lastOption['pay_amount']
        ];

        $this->myApiPrint('查询成功！', 400, $data);
	}

	/**
	 * 申请成为正式会员
	 *
	 * @param uid 会员ID
	 * @param id_card 身份证号
	 * @param province 省
	 * @param city 市
	 * @param address 详细地址
	 * @param agreement 阅读协议(1:已阅读)
	 * @param apply_coin_class 申请币种（1 表示108公让宝,2表示108现金,3 代表公让宝+现金(公让宝优先),4表示支付宝支付,5代表微信支付）0不需要支付
	 * @param subject 订单标题（可选）,默认为“创客申请订单”
	 * @param body 订单描述（可选）,默认为“申请成为创客”
	 */
	public function hack_apply() {
        $data['id'] = intval(I('post.uid'));
        $data['fee'] = intval(I('post.fee'));
        if (!$data['fee']) $data['fee'] = 100;

		/**验证数据**/
		$member = M( 'member' )->where( 'id=' . $data['id'] )->find();
		if ( empty( $member ) ) {
			$this->myApiPrint( '此用户信息错误或不存在！', 300, $member );
		}

//        if ($member['is_tt'] == 1) {
//            $this->myApiPrint('你已经解锁，无需重复操作', 300);
//        }
        if (M('orders')->where(['uid' => $member['id'], 'start_time' => ['gt', time() - 300], 'exchangeway' => 3])->count()) {
            $this->myApiPrint('你的操作太频繁，请稍后再试。');
        }

        $balance = floatval(AccountModel::getInstance()->getBalance($member['id'], Currency::Supply));
        $unlock_amount = $balance * $data['fee'] * 0.01;
        $pay_amount = $unlock_amount * floatval(SettingsModel::getInstance()->get('unlock_assets_fee')) * 0.01;
        if ($pay_amount < 0.01) {
            $this->myApiPrint('你没有需要解锁的资产', 300);
        }

        $om = new OrderModel();

//        $pay_amount = 0.01;
//        $data['fee'] = 25;

        // 生成微信支付订单
        $orderNo = $om->create($data['id'], $pay_amount, PaymentMethod::Wechat, 0, 0, '激活锁定资产', '', $data['fee'], 0, 3, 0, time());
        if ($orderNo == '') {
            $this->myApiPrint('申请失败', 300);
        }

        // 生成签名
        $signStr = $om->getWxpaySign($orderNo, $pay_amount, 'Notify/hack_apply');
        if (isset($signStr['return_code']) && $signStr['return_code'] == 'FAIL') {
            $this->myApiPrint('支付失败：' . $signStr['return_msg'], 300);
        }

        $returndata = $om->format_return('返回成功', 400, $signStr);
        $this->ajaxReturn($returndata);

	}


	/**
	 * 免费申请
	 * Enter description here ...
	 */
	private function hack_free( $data, $member ) {
		//1、验证是否完成1推3
		$childsnum = M( 'member' )->where( 'is_lock = 0 and reid = ' . $member['id'] )->count();
		if ( $childsnum < 3 ) {
			$this->myApiPrint( '没有完成1推3，暂时无法申请！', 300, 1 );
		}
		//2、验证消费丰收股
		/* $am = new AccountModel();
		 $bonus = $am->getBalance($member['id'], Currency::Bonus);
		 if($bonus == 0){
			 $this->myApiPrint('消费没有达到1个丰收点，暂时无法申请！',300);
		 }*/

		M()->startTrans();

		//升级创客正式会员
		$res1 = M( 'member' )->where( 'id=' . $data['id'] )->save( array( 'level' => 2 ) );
		$res2 = M( 'member' )->where( 'id=' . $member['reid'] )->setInc( 'recount', 1 );
		if ( $res1 !== false && $res2 !== false ) {
			M()->commit();
			$this->myApiPrint( '恭喜您，申请创客成功！', 400 );
		} else {
			M()->rollback();
			$this->myApiPrint( '申请创客失败,处理异常！', 300 );
		}
	}


	/**
	 * 我推荐的会员
	 *
	 * @param uid 会员ID
	 * @param level 获取的推荐会员级别(空/2: 获取非体验会员数据, 1: 获取体验会员数据)
	 * @param page 分页参数（page=0显示1-10条数据）
	 */
	public function recommand_hacker() {
		$uid   = I( 'post.uid' );
		$level = I( 'post.level' );
		$level = ! is_numeric( $level ) ? 2 : ( $level > 1 ? 2 : 1 );

		$wherekey['id'] = $uid;
		$m              = M( 'member' )->where( $wherekey )->select();
		if ( empty( $m ) ) {
			$this->myApiPrint( '对不起，用户信息不存在！' );
		}

		$post_page = intval( I( 'post.page' ) );
		if ( $post_page > 0 ) {
			$post_page = $post_page * 10;
		}
		if ( $level > 1 ) {
			$where['level'] = array( 'egt', $level );
		} else {
			$where['level'] = array( 'eq', $level );
		}

		//$where['repath'] = array('like', '%,'.$uid.',%');
		$where['reid'] = $uid;
		$totalPage     = M( 'member' )->where( $where )->count();
		$everyPage     = '10';
		$pageString    = $post_page . ',' . $everyPage;

		$member = M( 'member' )
			->field( 'id uid,loginname member_phone,img,nickname,level,reg_time, son_count' )
			->where( $where )
			->limit( $pageString )
			->order( 'son_count desc, reg_time desc' )
			->select();
		foreach ( $member as $k => $v ) {
			if ( $v['level'] == 1 ) {
				$member[ $k ]['nickname'] = $member[ $k ]['nickname'] . '(体验)';
			} elseif ( $v['level'] == 2 ) {
				$member[ $k ]['nickname'] = $member[ $k ]['nickname'] . '(创客)';
			} elseif ( $v['level'] == 3 ) {
				$member[ $k ]['nickname'] = $member[ $k ]['nickname'] . '(服务中心)';
			} elseif ( $v['level'] == 4 ) {
				$member[ $k ]['nickname'] = $member[ $k ]['nickname'] . '(区域合伙人)';
			}

			if ( $v['son_count'] > 0 && intval( I( 'post.page' ) ) == 0 && $k <= 2 ) {
				$member[ $k ]['nickname'] = $member[ $k ]['nickname'] . '[分享人数:' . $v['son_count'] . ']';
			}
		}
		if ( empty( $member ) ) {
			$this->myApiPrint( '对不起，你还没有推荐任何人，要加油哦！', 400, $member );
		}
		$data = $member;

		$this->myApiPrint( '我直接推荐的会员！', 400, $data );
	}

	/**
	 * 申请服务中心3/运营中心4、合伙人5
	 *
	 * @param uid 会员ID
	 * @param photo1 身份证正面
	 * @param photo2 身份证反面
	 * @param photo3 营业执照
	 * @param photo4 公司形象照
	 */
	public function service_center() {
		$uid     = intval( I( 'post.uid' ) );
		$type    = intval( I( 'post.type' ) );
		$roles   = [ 'role3' => '服务中心', 'role4' => '省级合伙人', 'role5' => '合伙人' ];
		$roletxt = $roles[ 'role' . $type ];
		if ( $type != 3 && $type != 4 && $type != 5 ) {
			$this->myApiPrint( '申请类型错误' );
		}
		$where['id'] = $uid;
		$m           = M( 'member' )->where( $where )->count();
		if ( $m == 0 ) {
			$this->myApiPrint( '对不起，用户信息不存在！' );
		}

		$member = M( 'member' )->where( $where )->find();
		if ( $member['level'] == 1 ) {
			$this->myApiPrint( '对不起，你是体验会员，不能申请！' );
		} elseif ( $member['role'] == 3 && $type == 3 ) {
			$this->myApiPrint( '对不起，你已经是服务中心，无须再申请服务中心！' );
		} elseif ( $member['role'] == 4 && $type == 4 ) {
			$this->myApiPrint( '对不起，你已经是省级合伙人，无须再申请省级合伙人！' );
		} elseif ( $member['is_partner'] == 1 && $type == 5 ) {
			$this->myApiPrint( '对不起，你已经是合伙人，无须再申请合伙人！' );
		}
		//验证是不是商家
		$store = M( 'store' )->where( 'uid = ' . $uid )->find();
		if ( $member['store_flag'] == 1 && $store['id'] > 0 ) {
			if ( $store['manage_status'] == 0 ) {
				$this->myApiPrint( '对不起，你正在申请商家，不能申请' . $roletxt . '！' );
			} elseif ( $store['manage_status'] == 1 ) {
				$this->myApiPrint( '对不起，你是商家，不能申请' . $roletxt . '！' );
			} elseif ( $store['manage_status'] == 10 ) {
				$this->myApiPrint( '对不起，你的店铺尚未注销，不能申请' . $roletxt . '！' );
			} elseif ( $store['status'] == 1 ) {
				$this->myApiPrint( '对不起，你的店铺被冻结，不能申请' . $roletxt . '！' );
			}
		}
		M()->startTrans();
		$wherekey['uid']         = array( 'eq', $uid );
		$wherekey['apply_level'] = array( 'eq', $type );
		$apply_info              = M( 'apply_service_center' )->where( $wherekey )->field( 'id,status' )->order( 'id desc' )->find();
		if ( $apply_info ) {
			if ( $apply_info['status'] == 0 ) {
				$this->myApiPrint( '对不起，你已经申请了' . $roletxt . '，正在审核中。。。，不能再申请' . $roletxt . '！', 300 );
			}
			//如果之前申请的已驳回,则自动清除之前的申请
			if ( $apply_info['status'] == 2 ) {
				$map_apply['id'] = array( 'eq', $apply_info['id'] );
				M( 'ApplyServiceCenter' )->where( $map_apply )->delete();
			}
		}
		//处理图片
		$upload_config = array(
			'file' => 'multi',
			'exts' => array( 'jpg', 'png', 'gif', 'jpeg' ),
			'path' => 'service/' . date( 'Ymd' )
		);
		$Upload        = new UploadController( $upload_config );
		$info          = $Upload->upload();
		if ( ! empty( $info['error'] ) ) {
			M()->rollback();
			$this->myApiPrint( '申请失败，请重新上传图片！', 300, (object) $result );
		} else {
			$data['uid'] = $uid;
			for ( $i = 1; $i <= 3; $i ++ ) {
				if ( empty( $info['data'][ 'photo' . $i ]['savename'] ) ) {
					M()->rollback();
					$this->myApiPrint( '请上传完整的图片！' );
				}
			}
			$data['img1'] = $info['data']['photo1']['url'];
			$data['img2'] = $info['data']['photo2']['url'];
			$data['img3'] = $info['data']['photo3']['url'];
			$img4         = '';
			if ( $info['data']['photo4_1'] ) {
				$img4 .= $info['data']['photo4_1']['url'] . ',';
			}
			if ( $info['data']['photo4_2'] ) {
				$img4 .= $info['data']['photo4_2']['url'] . ',';
			}
			if ( $info['data']['photo4_3'] ) {
				$img4 .= $info['data']['photo4_3']['url'] . ',';
			}
			if ( $info['data']['photo4_4'] ) {
				$img4 .= $info['data']['photo4_4']['url'] . ',';
			}
			if ( $img4 != '' ) {
				$img4 = substr( $img4, 0, strlen( $img4 ) - 1 );
			}
			$data['img4']        = $img4;
			$data['get_time']    = time();
			$data['apply_level'] = $type;
			$restag              = M( 'apply_service_center' )->add( $data );
			//验证结果
			if ( $restag === false ) {
				M()->rollback();
				$this->myApiPrint( '申请失败，异常！' );
			}
			M()->commit();
			$result['img1'] = $data['img1'];
			$result['img2'] = $data['img2'];
			$result['img3'] = $data['img3'];
			$result['img4'] = $data['img4'];

			$this->myApiPrint( '申请' . $roletxt . '成功，正在审核中。。。！', 400, $result );
		}
	}


	/**
	 * 申请回购
	 */
	public function askback() {
		$uid = intval( I( 'post.uid' ) );

		//1.获取用户
		$where['id']      = $uid;
		$where['is_lock'] = 0;
		$user             = M( 'member' )->field( "id, FROM_UNIXTIME(reg_time,'%Y%m') as reg_time" )->where( $where )->find();
		if ( empty( $user ) ) {
			$this->myApiPrint( '用户不存在！' );
		}

		//2.验证是否已经提交
		$ww['user_id']        = $uid;
		$ww['buyback_status'] = array( 'eq', 2 );
		$ask                  = M( 'buyback' )->where( $ww )->find();
		if ( $ask ) {
			$this->myApiPrint( '您已完成回购，请勿重复操作！' );
		}

		//3.获取时间范围
		$flag     = false;
		$backtime = explode( "\r\n", C( 'PARAMETER_CONFIG.BONUS_BUYBACK_TIME' ) );
		foreach ( $backtime as $val ) {
			if ( $val == $user['reg_time'] ) {
				$flag = true;
			}
		}
		//获取账户丰收点
		$am           = new AccountModel();
		$bonusbalance = $am->getBalance( $uid, Currency::Bonus );

		//4.计算回购金额
		$afm   = new AccountFinanceModel();
		$money = $afm->getBackMoney( $uid );
		if ( $money['total_notget_profits'] <= 0 ) {
			if ( $bonusbalance <= 0 ) {
				$this->myApiPrint( '回购金额必须大于0' );
			} else {
				$money['total_notget_profits'] = $bonusbalance;
			}
		}
		$arm = new AccountRecordModel();
		if ( $flag ) {
			M()->startTrans();

			//4.提交申请
			$back['user_id']         = $uid;
			$back['buyback_amount']  = $money['total_notget_profits'];
			$back['buyback_status']  = 2;
			$back['buyback_addtime'] = time();
			$back['buyback_uptime']  = time();
			$res1                    = M( 'buyback' )->add( $back );

			//2.清空丰收点
			$res2 = $arm->add( $uid, Currency::Enroll, CurrencyAction::ENrollBuyBackAdd, $money['total_notget_profits'], $arm->getRecordAttach( 1, '管理员' ), '回购增加注册币' );

			if ( $bonusbalance > 0 ) {
				$res3 = $arm->add( $uid, Currency::Bonus, CurrencyAction::BonusBuyBack, - $bonusbalance, $arm->getRecordAttach( 1, '管理员' ), '回购丰收点清零' );
			} else {
				$res3 = true;
			}
			if ( $res1 !== false && $res2 !== false && $res3 !== false ) {
				M()->commit();
				$this->myApiPrint( '回购成功！', 400 );
			} else {
				M()->rollback();
				$this->myApiPrint( '回购失败' );

			}
		} else {
			$this->myApiPrint( '暂未符合回购要求' );
		}
	}


	/**
	 * 金卡代理申请界面
	 */
	public function vip_applay_index() {
		$uid  = intval( I( 'post.uid' ) );
		$user = M( 'member' )->where( 'id=' . $uid . ' and is_blacklist=0 and is_lock = 0' )->find();
		if ( empty( $user ) ) {
			$this->myApiPrint( '用户不存在' );
		}
		if ( $user['level'] == 6 ) {
			$this->myApiPrint( '您已经是金卡代理了' );
		}
		$user['fees'] = 0;
		$params       = M( 'g_parameter', null )->find();
		//注册说明
		//$return['plan'] = array(
		//		array('plan_type'=>0, 'name'=>$params['plana_name'], 'describe'=>$params['plana_explain']),
		//		array('plan_type'=>1, 'name'=>$params['planb_name'], 'describe'=>$params['planb_explain']),
		//);
		//if($params['planb_switch'] == 0){
		$return['plan'] = array(
			array( 'plan_type' => 0, 'name' => $params['plana_name'], 'describe' => $params['vip_apply_intro'] ),
		);
		//}
		$return['plan_label'] = '选择方案';
		//原来已交过137的补差额
		if ( $user['level'] == 5 ) {
			$params['vip_apply_amount'] = ceil( ( $params['vip_apply_amount'] - $params['v51_micro_vip_apply_amount'] ) * 100 ) / 100;
		} else {
			$params['vip_apply_amount'] = ceil( ( $params['vip_apply_amount'] - $user['fees'] ) * 100 ) / 100;
		}
		$return['amount']      = $params['vip_apply_amount'] * 1;
		$return['enroll_fill'] = ceil( $params['vip_apply_amount'] * $params['vip_apply_use_enroll_bai'] ) / 100;

		//查询余额
		$om      = new AccountModel();
		$balance = $om->getAccount( $uid, $om->get5BalanceFields() );

		$return['enroll'] = sprintf( '%.2f', $balance['account_enroll_balance'] ) * 1;
		$return['cash']   = sprintf( '%.2f', $balance['account_cash_balance'] ) * 1;

		$this->myApiPrint( '获取成功', 400, $return );
	}

	/**
	 * 申请金卡代理
	 * $uid        用户id
	 * $payway     支付方式1=现金；2=微信， 驳回后申请=0: 3=微信
	 * $useenroll  是否使用注册币1=使用；0不使用
	 */
	public function vip_apply() {
		$plan_type = 0;//intval(I('post.plan_type'));
		$user_id   = intval( I( 'post.uid' ) );
		$payway    = intval( I( 'post.payway' ) );
		$useenroll = intval( I( 'post.useenroll' ) );
		if ( $plan_type == 1 ) {
			$this->myApiPrint( 'B方案已暂停，请选择A方案！' );
		}

		/**验证数据**/
		$user         = verify_vipapply( $user_id );
		$user['fees'] = 0;
		$am           = new AccountModel();
		$mm           = new MemberModel();

		$params = M( 'g_parameter', null )->find();
		if ( $plan_type == 1 && $params['planb_switch'] == 0 ) {
			$this->myApiPrint( '方案未开启' );
		}


		$vip_apply_amount = $params['vip_apply_amount'];
		//原来已交过137的补差额
		if ( $user['level'] == 5 ) {
			$params['vip_apply_amount'] = ceil( ( $params['vip_apply_amount'] - $params['v51_micro_vip_apply_amount'] ) * 100 ) / 100;
		} else {
			$params['vip_apply_amount'] = $params['vip_apply_amount'] - $user['fees'];
		}
		//计算支付金额
		$enrll_amount = 0;
		if ( $useenroll == 1 ) {
			$maxenroll     = ceil( $params['vip_apply_amount'] * $params['vip_apply_use_enroll_bai'] ) / 100;
			$enrollbalance = $am->getBalance( $user_id, Currency::Enroll );
			if ( $enrollbalance > $maxenroll ) {
				$enrll_amount = $maxenroll;
			} else {
				$enrll_amount = $enrollbalance;
			}
		}
		$amount = $params['vip_apply_amount'] - $enrll_amount;
		if ( $amount < 0 ) {
			$this->myApiPrint( 'no' );
		}
		//3.验证余额
		$om = new OrderModel();
		if ( $payway == 1 && ! $om->compareBalance( $user_id, Currency::Cash, $amount ) ) {
			$this->myApiPrint( '余额不足' );
		}

		M()->startTrans();
		//删除责任消费统计
		M( 'dutyconsume' )->where( 'user_id = ' . $user_id )->delete();
		if ( $payway == 1 ) {
			$res1 = $mm->apply_vip( $user_id, $amount, $params, $enrll_amount );
			$res2 = $mm->vippic( $user_id, $payway, $plan_type );
			$res3 = $mm->vipclear( $user_id );
			//$res4 = $mm->user_play($vip_apply_amount, $params['planb_out_bei'], $user_id, $plan_type);
			//吊起存储过程
			$pm   = new ProcedureModel();
			$res5 = $pm->execute( 'V51_Event_apply', $user_id, '@error' );
			if ( $res1 !== false && $res2 !== false && $res3 !== false && $res5 ) {
				M()->commit();
				$this->myApiPrint( '申请成功', 400 );
			} else {
				M()->rollback();
				if ( $res2 == - 1 ) {
					$this->myApiPrint( '请上传3张图片' );
				}
				$this->myApiPrint( '申请失败' );
			}
		} elseif ( $payway == 2 ) {
			M()->rollback();
			$this->myApiPrint( '微信支付或提现功能维护中' );
			if ( $res2 == - 1 ) {
				M()->rollback();
				$this->myApiPrint( '请上传3张图片' );
			}
			//微信支付
			$res2    = $mm->vippic( $user_id, $payway, $plan_type );
			$orderNo = $om->create( $user_id, $amount, PaymentMethod::Wechat, 0, 0, '申请金卡代理', '', 0, 0, 4, 0, 0, 0, $enrll_amount, 2 );
			if ( $res2 === false || $orderNo == '' ) {
				M()->rollback();
				$this->myApiPrint( '申请失败', 300 );
			}

			M()->commit();
			//生成签名
			$signStr    = $om->getWxpaySign( $orderNo, $amount, 'Notify/vip_apply' );
			$returndata = $om->format_return( '返回成功', 400, $signStr );
			$this->ajaxReturn( $returndata );
			exit;
		} elseif ( $payway == 3 ) {
			if ( $res2 == - 1 ) {
				M()->rollback();
				$this->myApiPrint( '请上传3张图片' );
			}
			//支付宝支付
			$res2    = $mm->vippic( $user_id, $payway, $plan_type );
			$orderNo = $om->create( $user_id, $amount, PaymentMethod::Alipay, 0, 0, '申请金卡代理', '', 0, 0, 4, 0, 0, 0, $enrll_amount, 2 );
			if ( $res2 === false || $orderNo == '' ) {
				M()->rollback();
				$this->myApiPrint( '申请失败', 300 );
			}

			M()->commit();
			//生成签名
			$signStr    = $om->getAlipaySign( $orderNo, $amount, 'Notify/vip_apply' );
			$returndata = $om->format_return( '返回成功', 400, $signStr );
			$this->ajaxReturn( $returndata );
			exit;
		}

	}


	/**
	 * 钻卡代理申请界面
	 */
	public function honouredvip_applay_index() {
		$uid  = intval( I( 'post.uid' ) );
		$user = M( 'member' )->where( 'id=' . $uid . ' and is_blacklist=0 and is_lock = 0' )->find();
		if ( empty( $user ) ) {
			$this->myApiPrint( '用户不存在' );
		}
		if ( $user['level'] == 7 ) {
			$this->myApiPrint( '您已经是钻卡代理了' );
		}

		//注册说明
		$params = M( 'g_parameter', null )->find();
		//是否支付过首付
		$firstpay = M( 'user_affiliate' )->where( 'user_id = ' . $uid )->find();
		if ( $firstpay && $firstpay['honour_vip_unpaid_amount'] > 0 ) {
			$return['amount']       = $firstpay['honour_vip_unpaid_amount'];
			$return['first_amount'] = $params['honour_vip_apply_first_amount'];
			$return['describe']     = $params['honour_vip_apply_intro'];
			if ( $user['level'] == 6 ) {
				$return['enroll_fill'] = ( $params['honour_vip_apply_amount'] - $params['vip_apply_amount'] ) * $params['honour_vip_apply_use_enroll_bai'] / 100 - ( $params['honour_vip_apply_first_amount'] - $params['vip_apply_amount'] );
			} else {
				$return['enroll_fill'] = ( $params['honour_vip_apply_amount'] - $params['vip_apply_amount'] ) * $params['honour_vip_apply_use_enroll_bai'] / 100;
			}
			$return['renew'] = 1;  //补交

			if ( $params['honour_vip_apply_amount'] == $params['honour_vip_apply_first_amount'] ) {
				$return['enroll_fill'] = 0;
			}
		} else {
			//获取开通金额
			$mm              = new MemberModel();
			$return          = $mm->getZGvipMoney( $user, $params );
			$return['renew'] = 0;
		}

		$return['plan'] = array(
			array( 'plan_type' => 0, 'name' => $params['plana_name'], 'describe' => $params['honour_vip_apply_intro'] ),
		);

		$return['plan_label'] = '选择方案';
		$return['describe']   = $params['honour_vip_apply_intro'];
		//查询余额
		$om      = new AccountModel();
		$balance = $om->getAccount( $uid, $om->get5BalanceFields() );

		$return['enroll'] = sprintf( '%.2f', $balance['account_enroll_balance'] ) * 1;
		$return['cash']   = sprintf( '%.2f', $balance['account_cash_balance'] ) * 1;

		if ( $return['enroll_fill'] > $return['amount'] ) {
			$return['enroll_fill'] = $return['amount'];
		}

		$this->myApiPrint( '获取成功', 400, $return );
	}


	/**
	 * 申请钻卡代理
	 * $uid        用户id
	 * $payway     支付方式1=现金；2=微信， 驳回后申请=0；3=支付宝
	 * $useenroll  是否使用注册币1=使用；0不使用
	 */
	public function honouredvip_apply() {
		$plan_type = 0;//intval(I('post.plan_type'));
		$user_id   = intval( I( 'post.uid' ) );
		$payway    = intval( I( 'post.payway' ) );
		$useenroll = intval( I( 'post.useenroll' ) );
		if ( $plan_type == 1 ) {
			$this->myApiPrint( 'B方案已暂停，请选择A方案！' );
		}
		//是否支付过首付，还是续费
		$firstpay = M( 'user_affiliate' )->where( 'user_id = ' . $user_id )->find();
		if ( $firstpay && $firstpay['honour_vip_unpaid_amount'] > 0 ) {
			$this->honoure_vip_renew( $user_id, $payway, $firstpay );
			exit;
		}
		/**验证数据**/
		$user = verify_honouredvipapply( $user_id, $plan_type );
		$am   = new AccountModel();
		$mm   = new MemberModel();

		$params = M( 'g_parameter', null )->find();
		if ( $plan_type == 1 && $params['planb_switch'] == 0 ) {
			$this->myApiPrint( '方案未开启' );
		}
		$return = $mm->getZGvipMoney( $user, $params );

		//计算支付金额
		$enrll_amount = 0;
		if ( $useenroll == 1 ) {
			$maxenroll     = $return['enroll_fill'];
			$enrollbalance = $am->getBalance( $user_id, Currency::Enroll );
			if ( $enrollbalance > $maxenroll ) {
				$enrll_amount = $maxenroll;
			} else {
				$enrll_amount = $enrollbalance;
			}
		}

		if ( $enrll_amount > $return['amount'] ) {
			$enrll_amount = $return['amount'];
		}

		$amount = $return['amount'] - $enrll_amount;

		//3.验证余额
		$om = new OrderModel();
		if ( $payway == 1 && ! $om->compareBalance( $user_id, Currency::Cash, $amount ) ) {
			$this->myApiPrint( '余额不足' );
		}

		M()->startTrans();
		if ( $payway == 1 ) {
			$res1 = $mm->apply_honoureVip( $user, $amount, $params, $enrll_amount, $plan_type );
			$res2 = $mm->honoureVippic( $user_id, $payway, $plan_type );

			if ( $res1 !== false && $res2 !== false ) {
				M()->commit();
				$this->myApiPrint( '申请成功', 400 );
			} else {
				M()->rollback();
				if ( $res2 == - 1 ) {
					$this->myApiPrint( '请上传3张图片' );
				}
				$this->myApiPrint( '申请失败' );
			}
		} elseif ( $payway == 2 ) {
			M()->rollback();
			$this->myApiPrint( '微信支付或提现功能维护中' );
			if ( $res2 == - 1 ) {
				M()->rollback();
				$this->myApiPrint( '请上传3张图片' );
			}
			//微信支付
			$res2    = $mm->honoureVippic( $user_id, $payway, $plan_type );
			$orderNo = $om->create( $user_id, $amount, PaymentMethod::Wechat, 0, 0, '申请钻卡代理', '', 0, 0, 4, 0, 0, 0, $enrll_amount, 3 );
			if ( $res2 === false || $orderNo == '' ) {
				M()->rollback();
				$this->myApiPrint( '申请失败', 300 );
			}

			M()->commit();
			//生成签名
			$signStr    = $om->getWxpaySign( $orderNo, $amount, 'Notify/honoureVip_apply' );
			$returndata = $om->format_return( '返回成功', 400, $signStr );
			$this->ajaxReturn( $returndata );
			exit;
		} elseif ( $payway == 3 ) {
			if ( $res2 == - 1 ) {
				M()->rollback();
				$this->myApiPrint( '请上传3张图片' );
			}
			//支付宝支付
			$res2    = $mm->honoureVippic( $user_id, $payway, $plan_type );
			$orderNo = $om->create( $user_id, $amount, PaymentMethod::Alipay, 0, 0, '申请钻卡代理', '', 0, 0, 4, 0, 0, 0, $enrll_amount, 3 );
			if ( $res2 === false || $orderNo == '' ) {
				M()->rollback();
				$this->myApiPrint( '申请失败', 300 );
			}

			M()->commit();
			//生成签名
			$signStr    = $om->getAlipaySign( $orderNo, $amount, 'Notify/honoureVip_apply' );
			$returndata = $om->format_return( '返回成功', 400, $signStr );
			$this->ajaxReturn( $returndata );
			exit;
		}

	}


	/**
	 * 续费钻卡代理
	 *
	 * @param unknown $user_id
	 * @param unknown $payway
	 */
	private function honoure_vip_renew( $user_id, $payway, $firstpay ) {
		exit;
		//3.验证余额
		$om = new OrderModel();
		if ( $payway == 1 && ! $om->compareBalance( $user_id, Currency::Cash, $firstpay['honour_vip_unpaid_amount'] ) ) {
			$this->myApiPrint( '余额不足' );
		}
		M()->startTrans();
		if ( $payway == 1 ) {
			$mm   = new MemberModel();
			$res1 = $mm->honourVipRenew( $user_id, $firstpay );
			$res2 = $mm->honoureVipclear( $user_id );
			//吊起存储过程
			$pm   = new ProcedureModel();
			$res5 = $pm->execute( 'V51_Event_apply', $user_id, '@error' );
			if ( $res1 !== false && $res2 !== false && $res5 ) {
				M()->commit();
				$this->myApiPrint( '支付成功', 400 );
			} else {
				M()->rollback();
				$this->myApiPrint( '支付失败' );
			}
		} elseif ( $payway == 2 ) {
			M()->rollback();
			$this->myApiPrint( '微信支付或提现功能维护中' );
			//微信
			$orderNo = $om->create( $user_id, $firstpay['honour_vip_unpaid_amount'], PaymentMethod::Wechat, 0, 0, '续费钻卡代理', '', 0, 0, 4, 0, 0, 0, 0, 3 );
			if ( $orderNo == '' ) {
				M()->rollback();
				$this->myApiPrint( '支付失败', 300 );
			}

			M()->commit();
			//生成签名
			$signStr    = $om->getWxpaySign( $orderNo, $firstpay['honour_vip_unpaid_amount'], 'Notify/honoureVip_renew' );
			$returndata = $om->format_return( '返回成功', 400, $signStr );
			$this->ajaxReturn( $returndata );
			exit;
		} elseif ( $payway == 3 ) {
			//支付宝
			$orderNo = $om->create( $user_id, $firstpay['honour_vip_unpaid_amount'], PaymentMethod::Alipay, 0, 0, '续费钻卡代理', '', 0, 0, 4, 0, 0, 0, 0, 3 );
			if ( $orderNo == '' ) {
				M()->rollback();
				$this->myApiPrint( '支付失败', 300 );
			}

			M()->commit();
			//生成签名
			$signStr    = $om->getAlipaySign( $orderNo, $firstpay['honour_vip_unpaid_amount'], 'Notify/honoureVip_renew' );
			$returndata = $om->format_return( '返回成功', 400, $signStr );
			$this->ajaxReturn( $returndata );
			exit;
		}
	}


	/**
	 * 钻卡代理
	 * B计划复投页面
	 */
	public function vip_redelivery_index() {
		$user_id = intval( I( 'post.uid' ) );

		//验证数据？
		$user = verify_vip_redelivery( $user_id );

		//余额
		$am      = new AccountModel();
		$balance = $am->getItemByUserId( $user_id, 'account_redelivery_balance,account_cash_balance', 0 );
		//复投次数
		$mm              = new MemberModel();
		$return['round'] = $mm->getNextPlan( $user_id, 1 );

		$params = M( 'g_parameter', null )->find();
		//说明
		$return['describe'] = $params['planb_redelivery_explain'];
		//复投总金额
		if ( $user['level'] == 6 ) {
			$return['apply_amount'] = $params['vip_apply_amount'];
		} elseif ( $user['level'] == 7 ) {
			$return['apply_amount'] = $params['honour_vip_apply_amount'];
		}
		$return['redelivery'] = sprintf( '%.2f', $balance['account_redelivery_balance'] );
		$return['cash']       = sprintf( '%.2f', $balance['account_cash_balance'] );
		//待支付
		$return['needpay_amount'] = $return['apply_amount'] - $return['redelivery'];
		$this->myApiPrint( '获取成功', 400, $return );
	}


	/**
	 * 钻卡代理
	 * B计划复投
	 */
	public function vip_redelivery() {
		$user_id = intval( I( 'post.uid' ) );

		//验证数据？
		$user   = verify_vip_redelivery( $user_id );
		$params = M( 'g_parameter', null )->find();
		$am     = new AccountModel();
		//复投总金额
		$redelivery = $am->getBalance( $user_id, Currency::Redelivery );
		if ( $user['level'] == 6 ) {
			$apply_amount = $params['vip_apply_amount'] - $redelivery;
			$reaction     = CurrencyAction::RedeliveryApplyVIP;
			$cashaction   = CurrencyAction::CashRedeliveryVIP;
		} elseif ( $user['level'] == 7 ) {
			$apply_amount = $params['honour_vip_apply_amount'] - $redelivery;
			$reaction     = CurrencyAction::RedeliveryApplyHonourVIP;
			$cashaction   = CurrencyAction::CashRedeliveryHonourVIP;
		}

		//余额验证
		$om = new OrderModel();
		if ( ! $om->compareBalance( $user_id, Currency::Cash, $apply_amount ) ) {
			$this->myApiPrint( '余额不足' );
		}

		M()->startTrans();
		$mm                    = new MemberModel();
		$arm                   = new AccountRecordModel();
		$res1                  = $arm->add( $user_id, Currency::Cash, $cashaction, - $apply_amount, $arm->getRecordAttach( 1, '系统' ), '现金复投' );
		$res2                  = $arm->add( $user_id, Currency::Redelivery, $reaction, - $redelivery, $arm->getRecordAttach( 1, '系统' ), '复投扣币' );
		$redata['plan_out']    = 0;
		$redata['plan_round']  = $mm->getNextPlan( $user_id, 1 );
		$redata['plan_uptime'] = time();
		$res3                  = M( 'user_plan' )->where( 'user_id = ' . $user_id . ' and plan_type = 1' )->save( $redata );

		if ( $res1 !== false && $res2 !== false && $res3 !== false ) {
			M()->commit();
			$this->myApiPrint( '复投成功', 400 );
		} else {
			M()->rollback();
			$this->myApiPrint( '支付失败' );
		}


	}


	/**
	 * 银卡代申请界面
	 */
	public function v_applay_index() {
		$uid  = intval( I( 'post.uid' ) );
		$user = M( 'member' )->where( 'id=' . $uid . ' and is_blacklist=0 and is_lock = 0' )->find();
		if ( empty( $user ) ) {
			$this->myApiPrint( '用户不存在' );
		}
		if ( $user['level'] == 5 ) {
			$this->myApiPrint( '您已经是银卡代理了' );
		}
		$user['fees'] = 0;
		$params       = M( 'g_parameter', null )->find();

		$return['describe']   = $params['v51_micro_vip_apply_intro'];
		$return['plan_label'] = '';
		//原来已交过137的补差额
		$params['v51_micro_vip_apply_amount'] = ceil( ( $params['v51_micro_vip_apply_amount'] - $user['fees'] ) * 100 ) / 100;
		$return['amount']                     = $params['v51_micro_vip_apply_amount'] * 1;
		$return['enroll_fill']                = ceil( $params['v51_micro_vip_apply_amount'] * $params['v51_micro_vip_apply_use_enroll_bai'] ) / 100;

		//查询余额
		$om      = new AccountModel();
		$balance = $om->getAccount( $uid, $om->get5BalanceFields() );

		$return['enroll'] = sprintf( '%.2f', $balance['account_enroll_balance'] ) * 1;
		$return['cash']   = sprintf( '%.2f', $balance['account_cash_balance'] ) * 1;

		$this->myApiPrint( '获取成功', 400, $return );
	}


	/**
	 * 申请银卡代理
	 * $uid        用户id
	 * $payway     支付方式1=现金；2=微信， 驳回后申请=0
	 * $useenroll  是否使用注册币1=使用；0不使用
	 */
	public function v_vip_apply() {
		$user_id   = intval( I( 'post.uid' ) );
		$payway    = intval( I( 'post.payway' ) );
		$useenroll = intval( I( 'post.useenroll' ) );

		/**验证数据**/
		$user         = verify_v_vipapply( $user_id );
		$user['fees'] = 0;
		$am           = new AccountModel();
		$mm           = new MemberModel();

		$params = M( 'g_parameter', null )->find();

		$vip_apply_amount = $params['v51_micro_vip_apply_amount'];
		//原来已交过137的补差额
		$params['v51_micro_vip_apply_amount'] = $params['v51_micro_vip_apply_amount'] - $user['fees'];

		//计算支付金额
		$enrll_amount = 0;
		if ( $useenroll == 1 ) {
			$maxenroll     = ceil( $params['v51_micro_vip_apply_amount'] * $params['v51_micro_vip_apply_use_enroll_bai'] ) / 100;
			$enrollbalance = $am->getBalance( $user_id, Currency::Enroll );
			if ( $enrollbalance > $maxenroll ) {
				$enrll_amount = $maxenroll;
			} else {
				$enrll_amount = $enrollbalance;
			}
		}
		$amount = $params['v51_micro_vip_apply_amount'] - $enrll_amount;
		if ( $amount < 0 ) {
			$this->myApiPrint( '123' );
		}
		//3.验证余额
		$om = new OrderModel();
		if ( $payway == 1 && ! $om->compareBalance( $user_id, Currency::Cash, $amount ) ) {
			$this->myApiPrint( '余额不足' );
		}

		M()->startTrans();
		//删除责任消费统计
		M( 'dutyconsume' )->where( 'user_id = ' . $user_id )->delete();
		if ( $payway == 1 ) {
			$res1 = $mm->apply_v_vip( $user_id, $amount, $params, $enrll_amount );
			$res2 = $mm->v_vippic( $user_id, $payway, $plan_type );
			$res3 = $mm->v_vipclear( $user_id );
			//吊起存储过程
			$pm   = new ProcedureModel();
			$res5 = $pm->execute( 'V51_Event_apply', $user_id, '@error' );
			if ( $res1 !== false && $res2 !== false && $res3 !== false && $res5 ) {
				M()->commit();
				$this->myApiPrint( '申请成功', 400 );
			} else {
				M()->rollback();
				if ( $res2 == - 1 ) {
					$this->myApiPrint( '请上传3张图片' );
				}
				$this->myApiPrint( '申请失败' );
			}
		} elseif ( $payway == 2 ) {
			M()->rollback();
			$this->myApiPrint( '微信支付或提现功能维护中' );
			//微信支付
			$res2    = $mm->v_vippic( $user_id, $payway, $plan_type );
			$orderNo = $om->create( $user_id, $amount, PaymentMethod::Wechat, 0, 0, '申请银卡代理', '', 0, 0, 4, 0, 0, 0, $enrll_amount, 1 );
			if ( $res2 === false || $orderNo == '' ) {
				M()->rollback();
				$this->myApiPrint( '申请失败', 300 );
			}

			M()->commit();
			//生成签名
			$signStr    = $om->getWxpaySign( $orderNo, $amount, 'Notify/v_apply' );
			$returndata = $om->format_return( '返回成功', 400, $signStr );
			$this->ajaxReturn( $returndata );
			exit;
		} elseif ( $payway == 3 ) {

			//支付宝支付
			$res2    = $mm->v_vippic( $user_id, $payway, $plan_type );
			$orderNo = $om->create( $user_id, $amount, PaymentMethod::Alipay, 0, 0, '申请银卡代理', '', 0, 0, 4, 0, 0, 0, $enrll_amount, 1 );
			if ( $res2 === false || $orderNo == '' ) {
				M()->rollback();
				$this->myApiPrint( '申请失败', 300 );
			}

			M()->commit();
			//生成签名
			$signStr    = $om->getAlipaySign( $orderNo, $amount, 'Notify/v_apply' );
			$returndata = $om->format_return( '返回成功', 400, $signStr );
			$this->ajaxReturn( $returndata );
			exit;
		}

	}


}

?>