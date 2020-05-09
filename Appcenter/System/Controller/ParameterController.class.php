<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 奖项设置管理
// +----------------------------------------------------------------------
namespace System\Controller;
use Common\Controller\AuthController;

class ParameterController extends AuthController {
	
	public function index() {
		$data = M('parameter', 'g_')->find(1);
		$this->assign('data', $data);
		$this->display();
	}
	
	public function parameterSave() {
		$data = I('post.data', '', 'strip_tags');
		
		if (!validateExtend($data['id'], 'NUMBER')) {
			$this->error('参数格式有误');
		}
		
		$data['copyright'] = $_POST['data']['copyright'];
		$data['unlockmsg'] = $_POST['data']['unlockmsg'];
		$data['ulist'] = trim(str_replace('，', ',', $data['ulist']),',');
		
		//分红特殊用户帐号列表处理
		$data['bonus_special_member'] = safeString($data['bonus_special_member'], 'trim_space');
		$data['bonus_special_member'] = trim(str_replace('，', ',', $data['bonus_special_member']), ',');
		
		//回本特殊用户帐号列表处理
		$data['bonus_special_refund_member'] = safeString($data['bonus_special_refund_member'], 'trim_space');
		$data['bonus_special_refund_member'] = trim(str_replace('，', ',', $data['bonus_special_refund_member']), ',');
		
		//每周星期几可点击广告处理
		$data['ad_click_enable_week'] = implode(',', $data['ad_click_enable_week']);
		
		M()->startTrans();
		
		$parameter_info = M('parameter', 'g_')->find(1);
		$param_data = json_decode($parameter_info['extend_data'], true);
		
		$param_data['CASHTOGOLDCOIN']['cash_goldcoin_min'] = $_POST['data']['cash_goldcoin_min'];
		$param_data['CASHTOGOLDCOIN']['cash_goldcoin_bei'] = $_POST['data']['cash_goldcoin_bei'];
		$param_data['CASHTOGOLDCOIN']['cash_min'] = $_POST['data']['cash_min'];
		$param_data['CASHTOGOLDCOIN']['cash_bei'] = $_POST['data']['cash_bei'];
		$param_data['PARAMETER_CONFIG']['COLORCOIN_MSG']['instruction'] = $_POST['data']['color_trans_instruction'];
		$param_data['PARAMETER_CONFIG']['COLORCOIN_MSG']['rule'] = $_POST['data']['color_trans_rule'];
		$param_data['PARAMETER_CONFIG']['WITHDRAW_MSG']['instruction'] = $_POST['data']['cash_withdraw_instruction'];
		$param_data['PARAMETER_CONFIG']['WITHDRAW_MSG']['rule'] = $_POST['data']['cash_withdraw_rule'];
		$param_data['PARAMETER_CONFIG']['WITHDRAW_BANK_MSG']['instruction'] = $_POST['data']['cash_withdraw_bank_instruction'];
		$param_data['PARAMETER_CONFIG']['WITHDRAW_BANK_MSG']['rule'] = $_POST['data']['cash_withdraw_bank_rule'];
		$param_data['PARAMETER_CONFIG']['WITHDRAW_BANK_MSG']['bind'] = $_POST['data']['cash_withdraw_bank_bind'];
		$param_data['PARAMETER_CONFIG']['WITHDRAW_ALIPAY_MSG']['instruction'] = $_POST['data']['cash_withdraw_alipay_instruction'];
		$param_data['PARAMETER_CONFIG']['WITHDRAW_ALIPAY_MSG']['rule'] = $_POST['data']['cash_withdraw_alipay_rule'];
		$param_data['PARAMETER_CONFIG']['GOLDZHUANCASH']['instruction'] = $_POST['data']['cash_trans_instruction'];
		$param_data['PARAMETER_CONFIG']['GOLDZHUANCASH']['rule'] = $_POST['data']['cash_trans_rule'];
		$param_data['PARAMETER_CONFIG']['POINTS']['service_company_points'] = $_POST['data']['service_company_points'];
		$param_data['PARAMETER_CONFIG']['POINTS']['service_company_points_clear'] = $_POST['data']['service_company_points_clear'];
		$param_data['PARAMETER_CONFIG']['zhuan_color_fee'] = $_POST['data']['zhuan_color_fee'];
		$param_data['PARAMETER_CONFIG']['ONLINE_CLASSROOM'] = $_POST['data']['online_classroom'];
		$param_data['PARAMETER_CONFIG']['MERCHANT']['points_merchant_max_day_1'] = $_POST['data']['points_merchant_max_day_1'];
		$param_data['PARAMETER_CONFIG']['MERCHANT']['points_merchant_max_week_1'] = $_POST['data']['points_merchant_max_week_1'];
		$param_data['PARAMETER_CONFIG']['MERCHANT']['points_merchant_max_day_2'] = $_POST['data']['points_merchant_max_day_2'];
		$param_data['PARAMETER_CONFIG']['MERCHANT']['points_merchant_max_week_2'] = $_POST['data']['points_merchant_max_week_2'];
		$param_data['PARAMETER_CONFIG']['MERCHANT']['points_merchant_max_day_3'] = $_POST['data']['points_merchant_max_day_3'];
		$param_data['PARAMETER_CONFIG']['MERCHANT']['points_merchant_max_week_3'] = $_POST['data']['points_merchant_max_week_3'];
		$param_data['PARAMETER_CONFIG']['REG_MONEY_ENABLE'] = $_POST['data']['reg_money_enable'];
		$param_data['PARAMETER_CONFIG']['REG_CONDITION_DESCRIPTION'] = $_POST['data']['reg_condition_description'];
		$param_data['PARAMETER_CONFIG']['COLORCOIN_PAY_INSTRUCTION'] = $_POST['data']['colorcoin_pay_instruction'];
		$param_data['PARAMETER_CONFIG']['COLORCOIN_PAY_BAI'] = $_POST['data']['colorcoin_pay_bai'];
		
		//分时间段丰收参数
		$param_data['PARAMETER_CONFIG']['BONUS']['share_bonus_section'] = $_POST['data']['share_bonus_section'];
		
		//第三方货币互转参数
		$param_data['PARAMETER_CONFIG']['THIRD_CURRENCY_TRANSFER'] = $_POST['data']['third_currency_transfer'];
		$param_data['PARAMETER_CONFIG']['THIRD_CURRENCY_TRANSFER_MIN'] = $_POST['data']['third_currency_transfer_min'];
		$param_data['PARAMETER_CONFIG']['THIRD_CURRENCY_TRANSFER_BEI'] = $_POST['data']['third_currency_transfer_bei'];
		$param_data['PARAMETER_CONFIG']['THIRD_CURRENCY_TRANSFER_OUT_BAI'] = $_POST['data']['third_currency_transfer_out_bai'];
		$param_data['PARAMETER_CONFIG']['THIRD_CURRENCY_TRANSFER_IN_BAI'] = $_POST['data']['third_currency_transfer_in_bai'];
		$param_data['PARAMETER_CONFIG']['THIRD_CURRENCY_TRANSFER_DESCRIPTION'] = $_POST['data']['third_currency_transfer_description'];
		
		//回购参数
		$param_data['PARAMETER_CONFIG']['BONUS_BUYBACK_TIME'] = $_POST['data']['bonus_buyback_time'];
		
		//扩展参数(原文件形式参数)
		$data['extend_data'] = json_encode($param_data, JSON_UNESCAPED_UNICODE);
		
		//只能有广告收益的特殊用户参数
		$result2 = true;
		$result3 = true;
		if (empty($data['profits_only_ad_member'])) { //视为清空所有特殊用户
			$data_2['affiliate_income_disable'] = 0;
			$result_2 = M('UserAffiliate')->where('affiliate_income_disable=1')->save($data_2);
		} else {
			//格式化并获取用户ID信息
			$data['profits_only_ad_member'] = safeString($data['profits_only_ad_member'], 'trim_space');
			$data['profits_only_ad_member'] = trim(str_replace('，', ',', $data['profits_only_ad_member']), ',');
			$map_member['loginname'] = preg_match('/,/', $data['profits_only_ad_member']) ? array('in', $data['profits_only_ad_member']) : array('eq', $data['profits_only_ad_member']);
			$member_id_list = M('Member')->where($map_member)->field('id')->select();
			
			if (!empty($member_id_list)) {
				//先初始化已禁止状态为未禁止
				$data_2 = ['affiliate_income_disable' => 0];
				$result_2 = M('UserAffiliate')->where('affiliate_income_disable=1')->save($data_2);
				
				//修改或新增用户状态为已禁止
				foreach ($member_id_list as $k=>$v) {
					$count = M('UserAffiliate')->where('user_id='.$v['id'])->count();
					if ($count == 0) {
						$data_3 = ['user_id' => $v['id'], 'affiliate_income_disable' => 1];
						$result_3 = M('UserAffiliate')->add($data_3);
					} else {
						$data_3 = ['affiliate_income_disable' => 1];
						$result_3 = M('UserAffiliate')->where('user_id='.$v['id'])->save($data_3);
					}
					if ($result_3 === false) {
						break;
					}
				}
			}
		}
		
		$result_1 = M('parameter','g_')->save($data);
		
		if ($result_1 === false || $result_2 === false || $result_3 === false) {
			M()->rollback();
			$this->error('保存失败');
		}
		
		/*
		$pubinfo = M('parameter','g_')->find();
		session('pubinfo',$pubinfo);
		*/

		M()->commit();
		$this->success('操作成功', '', false, '操作了平台参数设置');
	}
	
	/**
	 * APP过渡页
	 */
	public function mustRead() {
		$this->display();
	}
	
	/**
	 * APP过渡页保存
	 */
	public function mustReadSave() {
		$parameter_info = M('parameter', 'g_')->find(1);
		
		$param_data = json_decode($parameter_info['extend_data'], true);
		$param_data['PARAMETER_CONFIG']['LOADING_MUST_READ'] = $_POST['data']['loading_must_read'];
		$param_data['PARAMETER_CONFIG']['LOADING_MUST_READ_en'] = $_POST['data']['loading_must_read_en'];
		$param_data['PARAMETER_CONFIG']['LOADING_MUST_READ_ko'] = $_POST['data']['loading_must_read_ko'];
		$param_data['PARAMETER_CONFIG']['LOADING_MUST_READ_OPEN'] = $_POST['data']['loading_must_read_open'];
		
		$data = array();
		$data['extend_data'] = json_encode($param_data, JSON_UNESCAPED_UNICODE);
		
		if (M('parameter','g_')->where('id=1')->save($data) === false) {
			$this->error('保存失败');
		} else {
			$this->success('保存成功', '', false, '修改了APP过渡页参数');
		}
	}
	
}
?>