<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 分红管理
// +----------------------------------------------------------------------
namespace System\Controller;
use Common\Controller\AuthController;
use V4\Model\AccountModel;
use V4\Model\FinanceModel;
use V4\Model\AccountRecordModel;
use V4\Model\Currency;
use V4\Model\CurrencyAction;

class BonusController extends AuthController {

	/**
	 * 今日分红
	 */
	public function bonusIndex() {
		$Profits = M('Profits');
		$Parameter = M('Parameter', 'g_');
		
		//获取站点关闭状态
		$is_close = $Parameter->field('is_close')->where('id=1')->find();
		$this->assign('is_close', $is_close['is_close']);
		
		//获取今日分红相关数据
		$is_created = true;
		$map_profits['date_created'] = array(array('egt', strtotime(date('Y-m-d 00:00:00'))), array('elt', strtotime(date('Y-m-d 23:59:59'))), 'and');
		$profits_info = $Profits->where($map_profits)->order('id desc')->find();
		if (!$profits_info) {
			//如果未有数据,则说明还未生成数据
			$is_created = false;
		} else {
			$profits_info['money'] = sprintf('%.4f', $profits_info['profits']*0.68);
		}
		$this->assign('info', $profits_info);
		$this->assign('is_created', $is_created);
		
		$this->display();
	}
	
	/**
	 * 每日分红
	 */
	public function bonusList() {
		$Profits = M('Profits');
		
		$count = $Profits->count();
		$limit = $this->Page($count, 20, $this->get);
		
		$list = $Profits->order('date_created desc')->limit($limit)->select();
		$this->assign('list', $list);
		
		$this->display();
	}
	
	/**
	 * 生成今日毛利润及分红股数
	 */
	public function profitsCreate() {
		$Profits = M('Profits');
		$ProfitsBonus = M('ProfitsBonus');
		$Member = M('Member');
		$Parameter = M('Parameter', 'g_');
		
		$AccountModel = new AccountModel();
		
		C('TOKEN_ON', false);
		
		$data = array();
		
		//判断是否已完成分红,若已完成分红,则不能再次生成毛利润及分红股数据
		$profits_info = $Profits->where("from_unixtime(date_created,'%Y%m%d')='".date('Ymd')."'")->field('id,share')->find();
		if ($profits_info['share'] > 0) {
			$this->error('今日分红已完成,不能再生成毛利润及分红股数据');
		}
		
		//今日总毛利润
		$map_profits_bonus['prb.date_created'] = array(array('egt', strtotime(date('Y-m-d 00:00:00'))), array('elt', strtotime(date('Y-m-d 23:59:59'))), 'and');
		$profits_bonus_info = $ProfitsBonus
			->alias('prb')
			->where($map_profits_bonus)
			->field('prb.profits')
			->group('prb.id')
			->select();
		if (!$profits_bonus_info) {
			//暂停此限制,无毛利润亦可分红
			//$this->error('今日还未产生毛利润');
			$data['profits'] = 0;
		}
		foreach ($profits_bonus_info as $k=>$v) {
			$data['profits'] += $v['profits'];
		}
		
		//今日总分红股数
//		$bonus_info = $AccountModel->getFieldsValues('sum(account_bonus_balance) bonus', 'account_tag=0', 0);
		$data['bonus'] = $AccountModel->getTotalBonus();
		
		//今日达到丰收点封顶值的总股数
		$member_bonus_max = $Parameter->where('id=1')->getField('member_bonus_max');
//		$bonus_max = $AccountModel->getFieldsValues('sum(account_bonus_balance) bonus', "account_bonus_balance>={$member_bonus_max} AND account_tag=0 ", 0);
                
		$data['bonus_max'] = $AccountModel->getExceedMaxBonus($member_bonus_max);
                
		$data['date_created'] = time();
		
		//生成:判断是否已生成,已生成则覆盖已生成的数据
		if ($profits_info) {
			
			if ($Profits->where('id='.$profits_info['id'])->save($data) === false) {
				$this->error('生成失败');
			}
			
		} else {
			
			if (!$Profits->create($data, '', true)) {
				$this->error($Profits->getError());
			} else {
				$id = $Profits->add();
				if (empty($id)) {
					$this->error('生成失败');
				}
			}
			
		}
		
		//关闭站点进行维护模式
		$data_parameter['is_close'] = 1;
		if ($Parameter->where('id=1')->save($data_parameter) === false) {
			$this->error('生成成功，但关闭站点进入维护模式失败，请去奖项设置中去手动关闭站点！');
		}
			
		$this->success("生成成功", '', "成功生成今日毛利润及分红股数");
		
	}
	
	/**
	 * 分红操作
	 */
	public function bonusShare() {
		set_time_limit(0);
		ignore_user_abort(true);
		
		$Profits = M('Profits');
		$Parameter = M('Parameter', 'g_');
		
		M()->startTrans();
		
		$money = $this->post['money'];
		$time = $this->post['time'];
		
		if (!validateExtend($money, 'MONEY')) {
			$this->error('分红金额格式有误');
		}
		
		if (!validateExtend($time, 'NUMBER') || strlen($time)!=8 || $time<date('Ymd')) {
			$this->error('时间参数格式有误');
		}
		
		//获取今日可分红金额
		$map_profits['date_created'] = array(array('egt', strtotime(date('Y-m-d 00:00:00'))), array('elt', strtotime(date('Y-m-d 23:59:59'))), 'and');
		$profits_info = $Profits->lock(true)->where($map_profits)->order('id desc')->find();
		if (!$profits_info) {
			$this->error('今日毛利润数据还未生成');
		}
		
		//判断分红金额是否大于今日可分红金额 
		if ($money > $profits_info['profits']*0.68) {
			//暂停此限制,无毛利润亦可分红
			//$this->error('分红金额不能大于今日可分红总金额');
		}
		
		//判断今日分红每股股数是否在正常范围内(小于等于之前最近一次的分红的每股股数的2倍)
		$yesterday_id = $Profits->where('id<'.$profits_info['id'])->field('max(id) yesterday_id')->find();
		$map_yesterday['id'] = array('eq', $yesterday_id['yesterday_id']);
		$map_yesterday['share'] = array('gt', 0);
		$yesterday_profits_info = $Profits->where($map_yesterday)->find();
		if ($yesterday_profits_info) {
			$yesterday_per_bonus = sprintf('%.4f', $yesterday_profits_info['share']/$yesterday_profits_info['bonus']);
			$today_per_bonus = sprintf('%.4f', $money/$profits_info['bonus']);
			if ($today_per_bonus > $yesterday_per_bonus*3) {
				$this->error('系统检测到每日分红每股股数大于之前最近一次分红的每股股数的3倍,请核对修改后再次尝试分红');
			}
		}
		
		//获取分红时间段配置参数
		$share_bonus_section_time = '';
		$share_bonus_section_time_arr = [];
		$share_bonus_section_money = '';
		$share_bonus_section_money_arr = [];
		$share_bonus_section = C('PARAMETER_CONFIG.BONUS')['share_bonus_section'];
		if (is_array($share_bonus_section) && count($share_bonus_section)>0) {
			foreach ($share_bonus_section as $k=>$v) {
				if (!empty($v['time'])) {
					$share_bonus_section_time_arr[] = $v['time'];
					$share_bonus_section_money_arr[] = empty($v['money']) ? '0' : $v['money'];
				}
			}
			$share_bonus_section_time = implode('-', $share_bonus_section_time_arr);
			$share_bonus_section_money = implode('-', $share_bonus_section_money_arr);
		}
		
		//同步实际分红金额至每日毛利润分红表
		$data_profits['share'] = $money;
		if ($Profits->where('id='.$profits_info['id'])->save($data_profits) === false) {
			M()->rollback();
			$this->error('分红失败,请稍后重试');
		} else {
			$share = function() use ($money, $time, $Profits, $Parameter, $share_bonus_section_time, $share_bonus_section_money) {
//				$status = M()->execute(C('ALIYUN_TDDL_MASTER') . "call bonus_action({$money}, '{$share_bonus_section_time}', '{$share_bonus_section_money}', @msg)");
				M()->execute(C('ALIYUN_TDDL_MASTER') . "call Bonus({$money}, '{$share_bonus_section_time}', '{$share_bonus_section_money}', @msg)");
                $result = M()->query("select @msg");                                                                                
                if ($result && is_array($result) && count($result) > 0 && isset($result[0]['@msg']) && $result[0]['@msg'] == '') {
                	M()->commit();//记录分红完成时间,便于给延迟开启系统提供时间依据
                	session('bonus_share_success_time', time());
                	$this->success('成功完成今日分红，当前站点处于维护模式，请手动开启站点。', U('Bonus/bonusList'), false, "成功完成今日分红");
                } else { //分红前出现异常                                                                                    
                    M()->rollback();
                    $this->error('分红失败：'.$result[0]['@msg']);
                }
			};
			
			//执行分红
			$share();
			exit;
		}
	}
	
	/**
	 * 日毛利润明细
	 */
	public function profitsDetail() {
		$ProfitsBonus = M('ProfitsBonus');
		$Profits = M('Profits');
		
		$id = $this->get['id'];
		$keyword = $this->get['keyword'];
		
		if (!validateExtend($id, 'NUMBER')) {
			$this->error('参数有误');
		}
		
		if (!empty($keyword)) {
			$map_search['mem1.loginname'] = array('eq', $keyword);
			$map_search['mem1.nickname'] = array('like', "%{$keyword}%");
			$map_search['mem2.loginname'] = array('eq', $keyword);
			$map_search['mem2.nickname'] = array('like', "%{$keyword}%");
			$map_search['_logic'] = 'or';
			$map_profits_bonus['_complex'] = $map_search;
		}
		
		//获取ID对应每日总毛利润的时间
		$map_profits['id'] = array('eq', $id);
		$profits_info = $Profits->where($map_profits)->field('date_created')->find();
		if (!$profits_info) {
			$this->error('相关数据不存在');
		}
		
		$map_profits_bonus['prb.date_created'][] = array('egt', strtotime(date('Y-m-d 00:00:00', $profits_info['date_created'])));
		$map_profits_bonus['prb.date_created'][] = array('elt', strtotime(date('Y-m-d 23:59:59', $profits_info['date_created'])));
		$map_profits_bonus['prb.date_created'][] = 'and';
		
		$count = $ProfitsBonus
			->alias('prb')
			->join('join __ORDERS__ ord ON ord.order_number=prb.order_number') //订单信息
			->join('join __STORE__ sto ON sto.id=ord.storeid') //店铺信息
			->join('left join __PRODUCT__ pro ON pro.id=ord.productid') //商品信息
			->join('join __MEMBER__ mem1 ON mem1.id=sto.uid') //商家信息
			->join('join __MEMBER__ mem2 ON mem2.id=ord.uid') //买家信息
			->where($map_profits_bonus)
			->count();
		$limit = $this->Page($count, 50, $this->get);
		
		$list = $ProfitsBonus
			->alias('prb')
			->join('join __ORDERS__ ord ON ord.order_number=prb.order_number') //订单信息
			->join('join __STORE__ sto ON sto.id=ord.storeid') //店铺信息
			->join('left join __PRODUCT__ pro ON pro.id=ord.productid') //商品信息
			->join('join __MEMBER__ mem1 ON mem1.id=sto.uid') //商家信息
			->join('join __MEMBER__ mem2 ON mem2.id=ord.uid') //买家信息
			->field('prb.id,prb.profits,prb.order_number,
					ord.time,ord.exchangeway,ord.goldcoin money,ord.amount_type,ord.pay_time,
					sto.store_name,mem1.loginname store_loginname,mem1.nickname store_nickname,
					pro.name product_name,pro.price product_price,
					mem2.loginname,mem2.nickname
					')
			->order('prb.date_created desc,prb.id desc')
			->group('prb.id')
			->where($map_profits_bonus)
			->limit($limit)
			->select();
		
		//数据处理
		$exchangeway = C('FIELD_CONFIG.orders')['exchangeway'];
		$amount_type = C('FIELD_CONFIG.orders')['amount_type'];
		foreach ($list as $k=>$v) {
			
			//兑换方式中文
			if (isset($v['exchangeway']) && isset($exchangeway[$v['exchangeway']])) {
				$list[$k]['exchangeway_cn'] = $exchangeway[$v['exchangeway']];
			}
			
			//支付类型
			if (isset($v['amount_type']) && isset($amount_type[$v['amount_type']])) {
				$list[$k]['amount_type_cn'] = $amount_type[$v['amount_type']];
			}
			
		}
		
		$this->assign('list', $list);
		
		$this->display();
	}
	
	/**
	 * 日分红明细
	 */
	public function bonusDetail() {
		$Profits = M('Profits');
		
		$AccountRecordModel = new AccountRecordModel();
		
		$where = '';
		
		$id = $this->get['id'];
		$keyword = $this->get['keyword']; //分红用户账号
		$bonus_type = $this->get['bonus_type']; //分红类型
		$page = $this->get['p']>0 ? $this->get['p'] : 1;
		
		if (!validateExtend($id, 'NUMBER')) {
			$this->error('参数有误');
		}
		
		if (!empty($keyword)) {
			$member_info = M('Member')->where('loginname='.$keyword)->field('id')->find();
			if (!$member_info) {
				$this->error('用户账号不存在');
			}
			$where .= " and user_id={$member_info['id']} ";
		}
		
		//获取ID对应每日总毛利润的时间
		$map_profits['id'] = array('eq', $id);
		$profits_info = $Profits->where($map_profits)->field('date_created')->find();
		if (!$profits_info) {
			$this->error('相关数据不存在');
		}
		
		//分红类型 + 货币类型
		$currency = Currency::Cash;
		$currency_action = CurrencyAction::CashBouns;
		switch ($bonus_type) {
			case 'cash':
				$currency = Currency::Cash;
				$currency_action = CurrencyAction::CashBouns;
				break;
			case 'goldcoin':
				$currency = Currency::GoldCoin;
				$currency_action = CurrencyAction::GoldCoinBonus;
				break;
			case 'colorcoin':
				$currency = Currency::ColorCoin;
				$currency_action = CurrencyAction::ColorCoinBonus;
				break;
		}
		
		//货币类型筛选
		$where .= " and record_action={$currency_action} ";
		
		//时间筛选
		$where .= " and record_addtime>='".strtotime(date('Y-m-d', $profits_info['date_created']))."' and record_addtime<='".strtotime(date('Y-m-d 23:59:59'), $profits_info['date_created'])."' ";
		
		$data = $AccountRecordModel->getListByAllUser($currency, date('Ym', $profits_info['date_created']), $page, 20, $where);
		
		$list = $data['list'];
		foreach ($list as $k=>$v) {
			//获取分红用户的账户信息
			$member_info = M('Member')->where('id='.$v['user_id'])->field('loginname,nickname')->find();
			if ($member_info) {
				$list[$k] = array_merge($v, $member_info);
			}
			
			//获取分红用户的股数信息
			$attach = json_decode($v['record_attach'], true);
			$list[$k]['bonus'] = isset($attach['bonus']) ? $attach['bonus'] : 0;
		}
		$this->assign('list', $list);
		
		$this->Page($data['paginator']['totalPage']*$data['paginator']['everyPage'], $data['paginator']['everyPage'], $this->get);
		
		$this->display();
	}
	
	/**
	 * 日分红失败明细
	 * 
	 * 搜集展示日分红失败的会员信息及未分红金额
	 */
	public function bonusFailDetail() {
		$Profits = M('Profits');
		$Member = M('Member');
		$Parameter = M('Parameter', 'g_');
		$BonusFail = M('BonusFail');
		
		$id = $this->get['id'];
		$keyword = $this->get['keyword'];
		
		if (!validateExtend($id, 'NUMBER')) {
			$this->error('参数有误');
		}
		
		if (!empty($keyword)) {
			$map_search['mem.loginname'] = array('eq', $keyword);
			$map_search['mem.nickname'] = array('like', "%{$keyword}%");
			$map_search['_logic'] = 'or';
			$map_bonus_fail['_complex'] = $map_search;
		}
		
		//获取ID对应每日总毛利润的相关数据
		$map_profits['id'] = array('eq', $id);
		$profits_info = $Profits->where($map_profits)->field('id,profits,bonus,share,date_created')->find();
		if (!$profits_info) {
			$this->error('相关数据不存在');
		}
		
		//计算每股金额
		$bonus_per = sprintf('%.4f', $profits_info['share']/$profits_info['bonus']);
		
		//获取每股分红至公让宝和现金比的比例参数配置
		$parameter_info = $Parameter->where('id=1')->field('bonus_to_goldcoin,bonus_to_cash')->find();
		
		$map_bonus_fail['bof.date_created'][] = array('egt', strtotime(date('Y-m-d 00:00:00', $profits_info['date_created'])));
		$map_bonus_fail['bof.date_created'][] = array('elt', strtotime(date('Y-m-d 23:59:59', $profits_info['date_created'])));
		$map_bonus_fail['bof.date_created'][] = 'and';
		
		$count = $BonusFail
			->alias('bof')
			->join('left join __MEMBER__ mem ON mem.id=bof.uid')
			->where($map_bonus_fail)
			->count();
		$limit = $this->Page($count, 50, $this->get);
		
		$list = $BonusFail
			->alias('bof')
			->join('left join __MEMBER__ mem ON mem.id=bof.uid')
			->where($map_bonus_fail)
			->field('bof.id,mem.loginname,mem.nickname,bof.bonus')
			->limit($limit)
			->select();
		
		//把未成功分红会员该分带公让宝和现金币金额加入会员信息中
		foreach ($list as $k=>$v) {
			$list[$k]['goldcoin'] = ($bonus_per*$v['bonus'])*($parameter_info['bonus_to_goldcoin']/100);
			$list[$k]['cash'] = ($bonus_per*$v['bonus'])*($parameter_info['bonus_to_cash']/100);
		}
		
		$this->assign('list', $list);
		$this->assign('bonus_per', $bonus_per);
		$this->assign('date_created', $profits_info['date_created']);
		
		$this->display();
	}
	
	/**
	 * 补发分红
	 * 
	 * 针对未分红成功的会员进行补发分红
	 */
	public function bonusRepeatShare() {
		$Member = M('Member');
		$Bonus = M('Bonus', 'g_');
		$Profits = M('Profits');
		$Parameter = M('Parameter', 'g_');
		$BonusFail = M('BonusFail');
		
		$AccountRecordModel = new AccountRecordModel();
		
		M()->startTrans();
		
		C('TOKEN_ON', false);
		
		$id = $this->get['id'];
		$time = $this->get['time'];
		
		if (!validateExtend($id, 'NUMBER')) {
			$this->error('参数格式有误');
		}
		if (!validateExtend($time, 'TIME_STAMP')) {
			$this->error('参数格式有误');
		}
		
		//检测对应失败信息是否存在
		$map_bonus_fail['id'] = array('eq', $id);
		$bonus_fail_info = $BonusFail->where($map_bonus_fail)->find();
		if (!$bonus_fail_info) {
			$this->error('对应分红失败信息已不存在');
		}
		
		//检测是否存在该账户
		$map_member['id'] = array('eq', $bonus_fail_info['uid']);
		$member_info = $Member->lock(true)->where($map_member)->field('id,bonus,loginname,nickname')->find();
		if (!$member_info) {
			$this->error('该会员帐户已不存在');
		}
		if ($member_info['bonus']<=0) {
			$this->error('该会员帐户暂无分红股,无法进行补发分红操作');
		}
		
		//获取ID对应每日总毛利润的相关数据
		$map_profits = " from_unixtime(date_created,'%Y%m%d')='".date('Ymd', $time)."' ";
		$profits_info = $Profits->lock(true)->where($map_profits)->field('id,profits,bonus,share')->find();
		if (!$profits_info) {
			$this->error('指定日期的毛利润数据不存在');
		}
		
		//计算每股金额
		$bonus_per = sprintf('%.4f', $profits_info['share']/$profits_info['bonus']);
		//每股金额小于等于0则停止补发分红操作
		if ($bonus_per<=0) {
			$this->error('补发分红失败：每股分红金额必须大于0！');
		}
		
		//获取每股分红至公让宝和现金比和商超券的比例参数配置
		$parameter_info = $Parameter->lock(true)->where('id=1')->field('bonus_to_goldcoin,bonus_to_cash,bonus_to_colorcoin')->find();
		
		//再次检测该会员指定日期是否存在分红(明细)
		$map_bonus = " and user_id={$bonus_fail_info['uid']} and record_addtime>='".strtotime(date('Y-m-d', $time))."' and record_addtime<='".strtotime(date('Y-m-d 23:59:59', $time))."' and record_action=".CurrencyAction::CashBouns;
		$bonus_info = $AccountRecordModel->getListByAllUser(Currency::Cash, date('Ym', $time), 1, 1, $map_bonus);
		if ($bonus_info) {
			$this->error('该会员当前日期已完成分红,无需补发分红');
		}
		
		//计算应分红公让宝+现金币金额+商超券金额
		$share_cash = ($bonus_per*$bonus_fail_info['bonus'])*($parameter_info['bonus_to_cash']/100);
		$share_goldcoin = ($bonus_per*$bonus_fail_info['bonus'])*($parameter_info['bonus_to_goldcoin']/100);
		$share_colorcoin = ($bonus_per*$bonus_fail_info['bonus'])*($parameter_info['bonus_to_colorcoin']/100);
		
		//补发分红+明细
		//明细附加参数
		$record_attach = [
			'bonus' => $bonus_fail_info['bonus'],
		];
		$add_1 = $AccountRecordModel->add($bonus_fail_info['uid'], Currency::Cash, CurrencyAction::CashBouns, $share_cash, json_encode($record_attach), '分红现金币');
		$add_2 = $AccountRecordModel->add($bonus_fail_info['uid'], Currency::GoldCoin, CurrencyAction::GoldCoinBonus, $share_goldcoin, json_encode($record_attach), '分红公让宝');
		$add_3 = $AccountRecordModel->add($bonus_fail_info['uid'], Currency::ColorCoin, CurrencyAction::ColorCoinBonus, $share_colorcoin, json_encode($record_attach), '分红商超券');
		if ($add_1 === false || $add_2 === false || $add_3 === false) {
			M()->rollback();
			$this->error('补发分红失败');
		}
		
		//删除对应分红失败记录
		if ($BonusFail->where($map_bonus_fail)->delete() === false) {
			M()->rollback();
			$this->error('删除分红失败记录失败');
		}
		
		M()->commit();
		
		$this->success('补发分红成功', '', false, "给{$member_info['nickname']}[{$member_info['loginname']}]补发".date('Y-m-d', $time)."的分红成功");
	}
	
	/**
	 * 系统维护页面
	 */
	public function siteStatus() {
		$data = M('parameter', 'g_')->where('id=1')->field('id,is_close,close_msg')->find();
		$this->assign('data', $data);
		
		//获取分红完成时间,若小于1分钟,则提示暂时不能开启站点
		$time = '';
		$bonus_share_success_time = session('bonus_share_success_time');
		if (!empty($bonus_share_success_time) && $bonus_share_success_time!==null) {
			if ((time()-$bonus_share_success_time)<100) {
				$time = time()-$bonus_share_success_time;
			}
		}
		$this->assign('time', $time);
		
		$this->display();
	}
	
	/**
	 * 保存系统维护配置
	 */
	public function siteStatusSave() {
		$data = $this->post['data'];
		
		if (M('parameter','g_')->save($data) === false) {
			$this->error('保存失败');
		} else {
			$bonus_share_success_time = session('bonus_share_success_time');
			if (!empty($bonus_share_success_time) && $bonus_share_success_time!==null && $data['is_close']=='0') {
				session('bonus_share_success_time', null);
			}
			
			$site_status = $data['is_close']=='0' ? '开启' : '关闭';
			$this->success("{$site_status}系统维护状态成功", U('Bonus/siteStatus'), false, "修改系统维护状态为[{$site_status}]");
		}
	}
	
	/**
	 * 日管理津贴明细
	 */
	public function gljtDetail() {
		$Profits = M('Profits');
		
		$AccountRecordModel = new AccountRecordModel();
		
		$where = '';
		
		$id = $this->get['id'];
		$keyword = $this->get['keyword']; //管理津贴用户账号
		$gljt_type = $this->get['gljt_type']; //管理津贴类型
		$page = $this->get['p']>0 ? $this->get['p'] : 1;
		
		if (!validateExtend($id, 'NUMBER')) {
			$this->error('参数有误');
		}
		
		if (!empty($keyword)) {
			$member_info = M('Member')->where('loginname='.$keyword)->field('id')->find();
			if (!$member_info) {
				$this->error('用户账号不存在');
			}
			$where .= " and user_id={$member_info['id']} ";
		}
		
		//获取ID对应每日总毛利润的时间
		$map_profits['id'] = array('eq', $id);
		$profits_info = $Profits->where($map_profits)->field('date_created')->find();
		if (!$profits_info) {
			$this->error('相关数据不存在');
		}
		
		//管理津贴类型 + 货币类型
		$currency = Currency::Cash;
		switch ($gljt_type) {
			case 'cash':
				$currency = Currency::Cash;
				$where .= " and record_action in (".CurrencyAction::CashServiceManage.",".CurrencyAction::CashStarMakerManage.") "; //货币类型筛选
				break;
			case 'goldcoin':
				$currency = Currency::GoldCoin;
				$where .= " and record_action in (".CurrencyAction::GoldCoinServiceManage.",".CurrencyAction::GoldCoinStarMakerManage.") "; //货币类型筛选
				break;
			case 'colorcoin':
				$currency = Currency::ColorCoin;
				$where .= " and record_action in (".CurrencyAction::ColorCoinServiceManage.",".CurrencyAction::ColorCoinStarMakerManage.") "; //货币类型筛选
				break;
		}
		
		//时间筛选
		$where .= " and record_addtime>='".strtotime(date('Y-m-d', $profits_info['date_created']))."' and record_addtime<='".strtotime(date('Y-m-d 23:59:59'), $profits_info['date_created'])."' ";
		
		$data = $AccountRecordModel->getListByAllUser($currency, date('Ym', $profits_info['date_created']), $page, 20, $where);
		
		$list = $data['list'];
		foreach ($list as $k=>$v) {
			//获取管理津贴用户的账户信息
			$member_info = M('Member')->where('id='.$v['user_id'])->field('loginname,nickname,level,star')->find();
			if ($member_info) {
				$list[$k] = array_merge($v, $member_info);
			}
		}
		$this->assign('list', $list);
		
		$this->Page($data['paginator']['totalPage']*$data['paginator']['everyPage'], $data['paginator']['everyPage'], $this->get);
		
		$this->display();
	}
	
}
?>