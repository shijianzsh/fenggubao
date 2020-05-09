<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 提示信息相关接口
// +----------------------------------------------------------------------
namespace APP\Controller;

use Common\Controller\ApiController;
use V4\Model\AccountModel;

class CoinController extends ApiController {

	/**
	 * 彩分兑换现金提示信息
	 *
	 * @param uid 会员ID
	 */
	public function colorcoin_msg() {
		$where['id'] = I( 'post.uid' );

		if ( empty( $where['id'] ) ) {
			$this->myApiPrint( '数据错误！', 300 );
		}

		$am                    = new AccountModel();
		$cash                  = $am->getColorCoinBalance( $where['id'] );
		$parameter             = M( 'parameter', 'g_' )->field( 'zhuan_color_min,zhuan_color_bei,rate_color' )->find();
		$colorcoin_msg         = C( 'PARAMETER_CONFIG.COLORCOIN_MSG' );
		$search                = array( '{%zhuan_color_min%}', '{%zhuan_color_bei%}', '{%color_rate%}', '{%colorcoin%}' );
		$replace               = array( $parameter['zhuan_color_min'], $parameter['zhuan_color_bei'], $parameter['rate_color'], $colorcoin );
		$colorcoin_msg['rule'] = str_replace( $search, $replace, $colorcoin_msg['rule'] );
		$this->myApiPrint( '查询成功！', 400, $colorcoin_msg );
	}

	/**
	 * 现金提现提示信息
	 *
	 * @param uid 会员ID
	 */
	public function withdraw_msg() {
		$current_lang = getCurrentLang(true);
		
		$uid = $this->post['uid'];
		$way = empty( $_POST['way'] ) ? 0 : intval( $_POST['way'] );  //0微信 1银行卡 2支付宝

		if ( ! validateExtend( $uid, 'NUMBER' ) ) {
			$this->myApiPrint( '数据错误！', 300 );
		}

		$map_member['id'] = array( 'eq', $uid );
		$member_info      = M( 'Member' )->where( $map_member )->field( 'store_flag' )->find();
		$am               = new AccountModel();
		$cash             = $am->getCashBalance( $uid );

		$parameter = $this->CFG;

		$search  = array( '{%withdraw_amount_min%}', '{%withdraw_amount_max%}', '{%withdraw_amount_bei%}', '{%withdraw_fee%}', '{%withdraw_day_amount_max%}', '{%withdraw_day_number_max%}', '{%withdraw_week_enabled_day%}', '{%withdraw_day_enabled_hour_start%}', '{%withdraw_day_enabled_hour_end%}', '{%cash%}');
		$replace = array(
			$parameter['withdraw_amount_min'],
			$parameter['withdraw_amount_max'],
			$parameter['withdraw_amount_bei'],
			$parameter['withdraw_fee'],
			$parameter['withdraw_day_amount_max'],
			$parameter['withdraw_day_number_max'],
			$parameter['withdraw_week_enabled_day'],
			$parameter['withdraw_day_enabled_hour_start'],
			$parameter['withdraw_day_enabled_hour_end'],
			$cash,
		);
		$withdraw_msg = [
			'instruction' => $parameter['withdraw_description'.$current_lang],
			'rule' => $parameter['withdraw_rule'.$current_lang]
		];

		$withdraw_msg['rule'] = str_replace( $search, $replace, $withdraw_msg['rule'] );
		if ( $this->app_common_data['platform'] == 'android' && $way == 0 ) {
			$res                = $withdraw_msg;
			$res['rule']        = $withdraw_msg['instruction'];
			$res['instruction'] = $withdraw_msg['rule'];
			$this->myApiPrint( '查询成功！', 400, $res );
		} else {
			$this->myApiPrint( '查询成功！', 400, $withdraw_msg );
		}
	}

	/**
	 * 现金转公让宝提示信息
	 */
	public function cash_to_goldcoin_msg() {
		$where['id'] = I( 'post.uid' );

		if ( empty( $where['id'] ) ) {
			$this->myApiPrint( '数据错误！', 300 );
		}

		$am   = new AccountModel();
		$cash = $am->getCashBalance( $uid );

		$parameter            = C( 'CASHTOGOLDCOIN' );
		$withdraw_msg         = C( 'PARAMETER_CONFIG.GOLDZHUANCASH' );
		$search               = array( '{%cash_goldcoin_min%}', '{%cash_goldcoin_bei%}', '{%cash%}' );
		$replace              = array( $parameter['cash_goldcoin_min'], $parameter['cash_goldcoin_bei'], $cash );
		$withdraw_msg['rule'] = str_replace( $search, $replace, $withdraw_msg['rule'] );
		$this->myApiPrint( '查询成功！', 400, $withdraw_msg );
	}

	/**
	 * 提现参数
	 */
	public function tiqu_parameter() {
		$data = M( 'parameter', 'g_' )
			->field( 'tiqu_cash_min,tiqu_cash_bei,tiqu_color_min,tiqu_color_bei,tiqu_color_per,tiqu_fee,tiqu_rule' )
			->find();
		$this->myApiPrint( '查询成功！', 400, $data );
	}

	/**
	 * 充值提示信息
	 */
	public function recharge_rule() {
		$current_lang = getCurrentLang(true);
		
		$data = M('Settings')->where("settings_code='recharge_description".$current_lang."'")->field('settings_value as bank_withdraw_rule')->find();
		$this->myApiPrint( '查询成功！', 400, $data );
	}

}

?>