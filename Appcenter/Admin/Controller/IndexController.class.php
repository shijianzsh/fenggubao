<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 后台首页
// +----------------------------------------------------------------------

namespace Admin\Controller;

use Common\Controller\AuthController;
use V4\Model\AccountModel;
use V4\Model\FinanceModel;
use V4\Model\Tag;
use V4\Model\MiningModel;
use V4\Model\AccountRecordModel;
use V4\Model\Currency;

class IndexController extends AuthController
{

    private $allow_sl = array('one', 'two', 'three');

    public function index()
    {
        $AccountModel = new AccountModel();
        $MiningModel = new MiningModel();
        $AccountRecordModel = new AccountRecordModel();

        //判断是否具有小管理员身份
        $is_small_super_manager = $this->isSmallSuperManager();
        //针对特殊账号处理
        $is_small_super_manager = session('admin_mid') == 709 ? true : $is_small_super_manager;
        $this->assign('is_small_super_manager', $is_small_super_manager);
        $is_super = (session('admin_level') == 99 || $is_small_super_manager) ? true : false;
        
        //针对个人数据
        $_info = $AccountModel->getItemByUserId(session('admin_mid'), $AccountModel->get5BalanceFields());
        $_info['lock'] = M('lock')->where('tag=0 and user_id='.session('admin_mid'))->field('lock_amount')->find()['lock_amount'];
        $_info['total'] = $_info['account_goldcoin_balance'] + $_info['account_bonus_balance'] + $_info['lock'];
        $_info['performance'] = M('Performance')->where("performance_tag=0 and user_id=".session('admin_mid'))->getField('performance_amount');
        $_info['performance_yesterday'] = M('performance_'.Tag::getMonth())->where("performance_tag=".Tag::getYesterday()." and user_id=".session('admin_mid'))->getField('performance_amount');
        $_info['portion'] = $MiningModel->getPortionNumber(session('admin_mid'));
        $_info['liutong_1'] = M('account_goldcoin_201812')->where("record_action=153 and record_addtime<1546055082 and user_id=".session('admin_mid'))->sum('record_amount');
        $_info['liutong_2'] = M('Trade')->where("status=3 and user_id=".session('admin_mid'))->sum('amount');
        $_info['liutong'] = sprintf('%.4f', (abs($_info['liutong_1']) + $_info['liutong_2']));
        $_info['mining_yesterday'] = M('Mining')->where("user_id=".session('admin_mid')." and tag=" . Tag::getYesterday())->getField('amount');
        $_info['dynamic_income_yesterday'] = $AccountRecordModel->getFieldsValues(Currency::GoldCoin, Tag::getMonth(), "sum(record_amount) amount", "record_action in (104,105,108,117) and user_id=".session('admin_mid')." and FROM_UNIXTIME(record_addtime,'%Y%m%d') = FROM_UNIXTIME(UNIX_TIMESTAMP()-3600*24,'%Y%m%d')");
        $_info['dynamic_income_yesterday'] = $_info['dynamic_income_yesterday']['amount'];
        
        //针对平台数据
        $total_account = $AccountModel->getFieldsValues('
				sum(account_cash_balance) account_cash_balance,
				sum(account_goldcoin_balance) account_goldcoin_balance,
        		sum(account_bonus_balance) account_bonus_balance,
        		sum(account_supply_balance) account_supply_balance,
        		sum(account_enjoy_balance) account_enjoy_balance,
        		sum(account_credits_balance) account_credits_balance
			', "account_tag=0");
        
        //特殊处理
        $total_account['account_goldcoin_balance'] = $total_account['account_goldcoin_balance'] - 500000;
        $total_account['account_cash_balance'] = $total_account['account_cash_balance'] - 3000000;
        
        $total_account['lock'] = M('lock')->sum('lock_amount');
        $total_account['total'] = $total_account['account_goldcoin_balance'] + $total_account['account_bonus_balance'] + $total_account['lock'];
        $total_account['performance'] = M('Performance')->where("performance_tag=0 and user_id=0")->getField('performance_amount');
        $total_account['performance_yesterday'] = M('performance_'.substr(Tag::getYesterday(),0,6))->where("performance_tag=".Tag::getYesterday()." and user_id=0")->getField('performance_amount');
        $total_account['portion'] = $MiningModel->getPortionNumber();
        $total_account['liutong_1'] = M('account_goldcoin_201812')->where("record_action=153 and record_addtime<1546055082")->sum('record_amount');
        $total_account['liutong_2'] = M('Trade')->where("status=3")->sum('amount');
        $total_account['liutong'] = sprintf('%.4f', (abs($total_account['liutong_1']) + $total_account['liutong_2']));
        $total_account['mining_yesterday'] = M('Mining')->where("user_id=0 and tag=" . Tag::getYesterday())->getField('amount');
        $total_account['dynamic_income_yesterday'] = $AccountRecordModel->getFieldsValues(Currency::GoldCoin, Tag::getMonth(), "sum(record_amount) amount", "record_action in (104,105,108,117) and FROM_UNIXTIME(record_addtime,'%Y%m%d') = FROM_UNIXTIME(UNIX_TIMESTAMP()-3600*24,'%Y%m%d')");
        $total_account['dynamic_income_yesterday'] = $total_account['dynamic_income_yesterday']['amount'];
        
        //获取矿池余额
        /* 获取平台矿池账户余额 */
        $mining_account_amount = $AccountModel->getPointsBalance(1);
        /* 获取平台矿池产出总金额 */
        $mining_amount = M('Mining')->where("user_id=0 and tag=".Tag::getDay())->getField('amount');
        $total_points = $mining_account_amount - $mining_amount;
        
        //自适应身份显示对应数据
        $total_account = [
            'cash' => $is_super ? $total_account['account_cash_balance'] : $_info['account_cash_balance'],
            'goldcoin' => $is_super ? $total_account['account_goldcoin_balance'] : $_info['account_goldcoin_balance'],
            'bonus' => $is_super ? $total_account['account_bonus_balance'] : $_info['account_bonus_balance'],
            'points' => $is_super ? $total_points : 0,
            'supply' => $is_super ? $total_account['account_supply_balance'] : $_info['account_supply_balance'],
            'enjoy' => $is_super ? $total_account['account_enjoy_balance'] : $_info['account_enjoy_balance'],
            'credits' => $is_super ? $total_account['account_credits_balance'] : $_info['account_credits_balance'],
            'lock' => $is_super ? $total_account['lock'] : $_info['lock'],
            'total' => $is_super ? $total_account['total'] : $_info['total'],
            'performance' => $is_super ? $total_account['performance'] : $_info['performance'],
            'performance_yesterday' => $is_super ? $total_account['performance_yesterday'] : $_info['performance_yesterday'],
            'performance_uid' => $is_super ? 0 : session('admin_mid'),
            'portion' => $is_super ? $total_account['portion'] : $_info['portion'],
            'liutong' => $is_super ? $total_account['liutong'] : $_info['liutong'],
            'mining_yesterday' => $is_super ? $total_account['mining_yesterday'] : $_info['mining_yesterday'],
            'dynamic_income_yesterday' => $is_super ? $total_account['dynamic_income_yesterday'] : $_info['dynamic_income_yesterday'],
        ];
        $this->assign('total_account', $total_account);

        //计算平台体验会员,创客会员,服务中心,区域合伙人,商家人数
        
        // 体验会员
        $member_count['level1'] = M('Member')->where(['level' => ['eq', 1]])->count();

        // 个人代理
        $member_count['level2'] = M('Member')->where(['level' => ['eq', 2]])->count();

        // 区域合伙人
        $member_count['role3'] = M('Member')->where(['role' => ['eq', 3]])->count();

        // 省级合伙人
        $member_count['role4'] = M('Member')->where(['role' => ['eq', 4]])->count();
        
        //荣耀指数
        $star = M('Member')->field('star,count(id) star_count')->group('star')->select();
       	//V1特殊处理:需level=2,并且star为0或1
       	$star[1]['star_count'] = M('Member')->where('level=2 and star in(0,1)')->count();
        $member_count['star'] = $star;
        
        //星级
        $role_star = M('Consume')->field('level,count(user_id) level_count')->group('level')->select();
       	$member_count['role_star'] = $role_star;

        $this->assign('member_count', $member_count);


//        //获取最近一次系统自动统计的用户资金数据
//        $before_today = $FinanceModel->getItem($FinanceModel->get5FinanceFields() . ',finance_uptime');
//        //特殊处理
//        $before_today['finance_cash'] = $before_today['finance_cash'];
//        $before_today['finance_goldcoin'] = $before_today['finance_goldcoin'];
//        $before_today['finance_colorcoin'] = $before_today['finance_colorcoin'];
//        $this->assign('before_today', $before_today);


        if (session('admin_loginname') == '18955555555') {
        	$this->display('index_null');
        } else {
        	$this->display();
        }
    }

    /**
     * 验证安全密码UI
     */
    public function checkSafePassword()
    {
        $sl = $this->get['sl'];
        $sl = !in_array($sl, $this->allow_sl) ? $this->allow_sl[1] : $sl;
        $this->assign('sl', $sl);

        $title = '';
        switch ($sl) {
            case 'one':
                break;
            case 'two':
                $title = '二级';
                break;
            case 'three':
                $title = '三级';
                break;
        }
        $this->assign('title', $title);

        $this->display();
    }

    /**
     * 验证安全密码动作入口
     */
    public function checkSafePasswordIng()
    {
        $sl = $this->get['sl'];
        $sl = !in_array($sl, $this->allow_sl) ? $this->allow_sl[1] : $sl;

        switch ($sl) {
            case 'one':
                break;
            case 'two':
                $this->checkTwoSafePassword();
                break;
            case 'three':
                $this->checkThreeSafePassword();
                break;
        }
    }

    /**
     * 二级安全密码验证
     */
    private function checkTwoSafePassword()
    {
        $Member = M('Member');

        $safe_password = $this->post['safe_password'];
        $redirect = $this->post['redirect'];

        if (empty($safe_password)) {
            $this->error('请输入安全密码');
        }
        $redirect = empty($redirect) ? U('Admin / Index / index') : base64_decode($redirect);

        $map['id'] = array('eq', session('admin_mid'));
        $member_info = $Member->where($map)->field('safe_password')->find();

        if (!$member_info) {
            $this->error('登录超时或该用户已不存在');
        }
        if ($member_info['safe_password'] == md5($safe_password)) {
            session('session_safe_password', md5($safe_password));
            $this->success('验证通过', $redirect, false, "通过二级安全验证");
        } else {
            $this->error('安全密码错误,请重新输入');
        }
    }

    /**
     * 三级安全密码验证
     */
    private function checkThreeSafePassword()
    {
        $safe_password = $this->post['safe_password'];
        $redirect = $this->post['redirect'];

        if (empty($safe_password)) {
            $this->error('请输入安全密码');
        }
        $redirect = empty($redirect) ? U('Admin / Index / index') : base64_decode($redirect);

        if (md5($safe_password) == C('THREE_SAFE_PWD')) {
            session('session_three_safe_password', C('THREE_SAFE_PWD'));
            $this->success('验证通过', $redirect, false, "通过三级安全验证");
        } else {
            $this->error('安全密码错误,请重新输入');
        }
    }

}

?>