<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/4
 * Time: 17:07
 */

namespace V4\Model;

use APP\Controller\AccountReceivedController;

/**
 * 会员相关
 * Class Currency
 * @package V4\Model
 */
class MemberModel {

    private static $_instance;

    /**
     * 单例-获取new对象
     * Enter description here ...
     */
    public static function getInstance() {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * 申请正式会员
     * @param type $cashType【cash=现金积分； goldcoin=公让宝；】
     * @param type $money
     * @param type $user
     * @param type $id_card
     */
    public function applyMaker($cashType, $money, $user, $param) {
        $msg = '1';
        //1.验证余额
        if (!OrderModel::getInstance()->compareBalance($user['id'], $cashType, $money)) {
            $msg = '余额不足！';
            return $msg;
        }
        //开启事务
        M()->startTrans();

        //2.创建订单
        $om = new OrderModel();
        $res1 = $om->create($user['id'], $money, $cashType, 4, 0, '开通创客', '', 0, 0, 3);

        //3.记录明细
        $arm = new AccountRecordModel();
        if ($cashType == 'goldcoin') {
            $res3 = $arm->add($user['id'], $cashType, CurrencyAction::GoldApplyMaker, -$money, $arm->getRecordAttach($user['id'], $user['nickname'], $user['img']), '开通创客');
        } elseif ($cashType == 'cash') {
            $res3 = $arm->add($user['id'], $cashType, CurrencyAction::CashApplyMaker, -$money, $arm->getRecordAttach($user['id'], $user['nickname'], $user['img']), '开通创客');
        } else {
            $msg = '支付类型错误！';
            return $msg;
        }

        //4.升级成正式会员
        $where['id'] = $user['id'];
        $param['level'] = 2;
        $param['open_time'] = time();
        $res4 = M('member')->where($where)->save($param);

        //5.推荐人增加人数
        $res5 = M('member')->where('id=' . $user['reid'])->setInc('recount', 1);

        if ($res1 != '' && $res3 !== false && $res4 !== false && $res5 !== false) {
            M()->commit();
        } else {
            M()->rollback();
            $msg = '申请失败！';
        }
        return $msg;
    }

    /**
     * 计算是否中奖
     * Enter description here ...
     * @param unknown_type $params
     */
    public function getShakePro($params) {
        $point = rand(1, 100);
        if ($point > 0 && $point <= $params['b8']) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取奖池一条记录
     * Enter description here ...
     * @param unknown_type $uid
     * @param unknown_type $params
     * @param unknown_type $lat
     * @param unknown_type $lng
     */
    public function getShakePool($uid, $params, $lat, $lng) {
        //1.排除筛选商家(排除今日已经要过的商家）
        $sql = 'select shake_id, count(*) tt from zc_shake_records '
                . '  where user_id=' . $uid . ' and ' . " FROM_UNIXTIME(records_addtime,'%Y%m%d')=FROM_UNIXTIME(unix_timestamp(),'%Y%m%d') "
                . '  group by shake_id '
                . '  HAVING tt >= ' . $params['b10'];
        $filter = M()->query($sql);
        $notin = '(';
        foreach ($filter as $row) {
            $notin .= $row['shake_id'] . ',';
        }
        if ($notin != '(') {
            $notin = substr($notin, 0, strlen($notin) - 1) . ') ';
        } else {
            $notin = '(0) ';
        }

        //查询摇一摇池中是否有没有摇中的值
        $rangewhere = ' and  (6371 * acos( cos( radians(shake_lat) ) * cos( radians( ' . $lat . ' ) ) * cos( radians( ' . $lng . ' ) - radians(shake_lng) ) + sin( radians(shake_lat) ) * sin( radians( ' . $lat . ' ) ) ) ) < shake_ranges ';
        $shakePoolRecord = M('shake')->lock(true)->where('shake_status=2 and shake_id not in ' . $notin . $rangewhere)->order('shake_amount/shake_times desc')->limit(1)->find();

        if ($shakePoolRecord) {
            //单次金额
            $shakeamount = floor($shakePoolRecord['shake_amount'] / $shakePoolRecord['shake_times'] * 100) / 100;
            //判断封顶
            $shake_amount_bai = M('shake_records')->field('sum(records_cash)+sum(records_credits) as top_amount')->where('shake_id=' . $shakePoolRecord['shake_id'])->find();
            $shake_top = $shake_amount_bai['top_amount'];
            $shake_top = empty($shake_top) ? 0 : $shake_top * 1;
            if ($shake_top > $shakePoolRecord['shake_amount'] * $params['shake_amount_bai'] / 100) {
                return null;
            }
            if ($shake_top + $shakeamount > $shakePoolRecord['shake_amount'] * $params['shake_amount_bai'] / 100) {
                $rest_amount = $shakePoolRecord['shake_amount'] * $params['shake_amount_bai'] / 100 - ($shake_top + $shakeamount);
                if ($rest_amount < 0) {
                    return null;
                }
                $shakeamount = $rest_amount;
            }

            $res2 = true;
            $res3 = true;
            $res4 = true;
            $res5 = true;
            $res6 = true;
            //计算概率.额余明细
            $arm = new AccountRecordModel();
            $sj = $this->get_rand(array($params['shake_cash_bai'], $params['shake_credits_bai']));
            if ($sj == 1) {
                $callparam = $uid.',\''. Currency::GoldCoin.'\','. CurrencyAction::GoldCoinShake.','. $shakeamount.',\'摇一摇收益\'';
                //记录操作
                $this->addShakeRecord($uid, $shakePoolRecord['shake_id'], true, 0, $shakeamount);
                $shakePoolRecord['msg'] = '恭喜您摇中' . sprintf('%.2f', $shakeamount) . '公让宝';
            } else {
            	$callparam = $uid.',\''. Currency::Cash.'\','. CurrencyAction::CashShake.','. $shakeamount.',\'摇一摇收益\'';
                
                //记录操作
                $this->addShakeRecord($uid, $shakePoolRecord['shake_id'], true, $shakeamount, 0);
                $shakePoolRecord['msg'] = '恭喜您摇中' . sprintf('%.2f', $shakeamount) . '元';
            }

            $pm = new ProcedureModel();
            $res9 = $pm->execute('V51_Event_income', $callparam, '@error');
            
            //查询该摇一摇已摇中记录
            $shakecount = M('shake_records')->where('shake_id=' . $shakePoolRecord['shake_id'])->count();
            $res4 = true;
            if ($shakePoolRecord['shake_times'] <= $shakecount) {
                $res4 = M('shake')->where('shake_id=' . $shakePoolRecord['shake_id'])->save(array('shake_status' => 3));
            }

            if ($res2 != false && $res3 != false && $res4 !== false && $res5 !== false && $res6 && $res9) {
                return $shakePoolRecord;
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    /**
     * 摇一摇记录
     * @param unknown $uid
     * @param number $shake_id
     * @param number $flag
     * @param number $records_cash
     * @param number $records_credits
     */
    public function addShakeRecord($uid, $shake_id = 0, $flag = false, $records_cash = 0, $records_credits = 0) {
        //摇一摇记录
        $log['user_id'] = $uid;
        $log['log_addtime'] = time();
        M('shake_log')->add($log);
        //中奖记录
        if ($flag) {
            $vo['shake_id'] = $shake_id;
            $vo['user_id'] = $uid;
            $vo['records_cash'] = $records_cash;
            $vo['records_credits'] = $records_credits;
            $vo['records_addtime'] = time();
            M('shake_records')->add($vo);
        }
    }

    /**
     * 获取用户今日摇一摇数据
     * Enter description here ...
     * @param $uid
     * @param $data
     */
    public function getShakeRestTimes($uid, $return) {
        //当日所得金币
        $w1['user_id'] = $uid;
        $w1['_string'] = "FROM_UNIXTIME(records_addtime,'%Y%m%d') = FROM_UNIXTIME(UNIX_TIMESTAMP(),'%Y%m%d')";
        $todayshake = M('shake_records')->field('sum(records_cash) as records_cash, sum(records_credits) as records_credits')->where($w1)->find();
        if ($todayshake['records_cash']) {
            $return['today_cash'] = sprintf('%.2f', $todayshake['records_cash']);
            $return['today_points'] = sprintf('%.2f', $todayshake['records_credits']);
            $return['content'] = '哇塞，我今天使用' . C('APP_TITLE') . '摇一摇中了' . $todayshake['records_cash'] . '元现金积分，' . $todayshake['records_credits'] . '个积分。亲们赶快来摇红包吧！';
        }

        //余额
        $am = new AccountModel();
        $return['cash'] = $am->getCashBalance($uid);
        $return['points'] = $am->getBalance($uid, Currency::Credits);
        $return['residue'] = $return['residue'] - 1;
        if ($return['residue'] < 0) {
            $return['residue'] = 0;
        }
        return $return;
    }

    /**
     * 摇奖成功返回数据
     * Enter description here ...
     * @param $record
     * @param $uid
     * @param $usemore
     * @param $result
     */
    public function getShakeSucc($record, $uid, $usemore, $result) {
        //1.更改摇奖池中该条数据为已摇中
        $sdata1['shake_flag'] = 1;
        $res1 = M('shake_public')->where('id=' . $record['id'])->save($sdata1);

        //2.用户增加公让宝
        $arm = new AccountRecordModel();
        $res2 = $arm->add($uid, Currency::GoldCoin, CurrencyAction::GoldCoinShake, $record['goldcoin'], $arm->getRecordAttach($record['uid'], $record['pubusername'], $record['img']), '摇一摇红包');

        //使用新增机会
        $res3 = true;
        if ($usemore) {
            $res3 = M()->execute('update zc_shake_addtimes set `times`=`times`-1 where uid=' . $uid . ' and useday = \'' . date('Y-m-d') . '\'');
        }

        $result['shake_img'] = $record['img'];
        $result['store_id'] = M('store')->where('uid=' . $record['uid'])->getField('id');
        $result['title'] = C('APP_TITLE');
        $result['h5url'] = C('LOCAL_HOST') . U('H5/Index/index', array('uid' => $uid, 'muid' => $record['uid'], 'shakeid' => $record['id'], 'sessionid' => '', 'version' => ''));

        if ($res1 !== false && $res2 !== false && $res3 !== false) {
            return $result;
        } else {
            return false;
        }
    }

    /**
     * 处理会员签到
     * @param unknown $uid
     */
    public function userCheckIn($uid, $config) {
    	$EnjoyModel = new EnjoyModel();
    	
        //签到记录
        $vo['user_id'] = $uid;
        $vo['checkin_addtime'] = time();
        $vo['checkin_amount'] = $config['enjoy_signin'];
        $res1 = M('account_checkin')->add($vo);
        
        //增加公让宝
//         $arm = new AccountRecordModel();
//         $res2 = $arm->add($uid, Currency::GoldCoin, CurrencyAction::GoldCoinCheckIn, $config['signin_give_goldcoin_amount'], $arm->getRecordAttach(1, '系统'), '签到送公让宝');

        //赠送澳洲SKN股数
        $res2 = $EnjoyModel->signinGive($uid, $config['enjoy_signin']);

        if ($res1 && $res2) {
            return $config['enjoy_signin'];
        } else {
            return 0;
        }
    }

    /**
     * 
     * @param unknown $user_id
     * @param unknown $ad_id
     * @param unknown $params
     */
    public function watchAd($user_id, $ad_id, $params, $ad) {
        //现金积分+代金
        if ($ad['view_result']['currenty'] == 'cash') {
            //观看记录
            $vo['ad_id'] = $ad_id;
            $vo['user_id'] = $user_id;
            $vo['view_cash'] = $ad['view_result']['cash'];
            $vo['view_goldcoin'] = 0;
            $vo['view_redelivery'] = 0;
            $vo['view_enjoy'] = 0;
            $vo['view_credits'] = 0;
            $vo['view_addtime'] = time();
            $res1 = M('ad_view')->add($vo);
            
            $callparam = $user_id.',\''. Currency::Cash.'\','. CurrencyAction::CashViewAd.','. $ad['view_result']['cash'].',\'看广告收益\'';
            $pm = new ProcedureModel();
            $res9 = $pm->execute('V51_Event_income', $callparam, '@error');

            if ($res1 !== false && $res9) {
                return true;
            } else {
                return false;
            }
        } else {
            $credits = $ad['view_result']['credits'];
            //观看记录
            $vo['ad_id'] = $ad_id;
            $vo['user_id'] = $user_id;
            $vo['view_cash'] = 0;
            $vo['view_goldcoin'] = $credits;
            $vo['view_credits'] = 0;
            $vo['view_redelivery'] = 0;
            $vo['view_enjoy'] = 0;
            $vo['view_addtime'] = time();
            $res1 = M('ad_view')->add($vo);
            
        	$callparam = $user_id.',\''. Currency::GoldCoin.'\','. CurrencyAction::GoldCoinViewAd.','. $credits.',\'看广告收益\'';
            $pm = new ProcedureModel();
            $res9 = $pm->execute('V51_Event_income', $callparam, '@error');
            
            if ($res1 !== false && $res9) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * 现金积分申请金卡代理处理。不记录毛利润，但要记录收益
     * @param unknown $user_id
     * @param unknown $amount
     * @param unknown $params
     */
    public function apply_vip($user_id, $amount, $params, $enroll) {
        $arm = new AccountRecordModel();
        //1.创建订单
        $om = new OrderModel();
        $res6 = $om->create($user_id, $amount, PaymentMethod::Cash, 4, 0, '开通金卡代理', '', 0, 0, 4);

        //2.扣除金额
        $res1 = $arm->add($user_id, Currency::Cash, CurrencyAction::CashApplyVIP, -$amount, $arm->getRecordAttach(1, '系统'), '申请金卡代理扣款');
        $res2 = true;
        if ($enroll > 0) {
            $res2 = $arm->add($user_id, Currency::Enroll, CurrencyAction::ENrollApplyVIP, -$enroll, $arm->getRecordAttach(1, '系统'), '申请金卡代理扣款');
        }

        //升级
        $res3 = M('member')->where('id = ' . $user_id)->save(array('roleid' => array('exp', '`level`'), 'level' => 6, 'open_time' => time()));

        if ($res1 !== false && $res2 !== false && $res3 !== false && $res6 != '') {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 现金积分申请银卡代理处理。不记录毛利润，但要记录收益
     * @param unknown $user_id
     * @param unknown $amount
     * @param unknown $params
     */
    public function apply_v_vip($user_id, $amount, $params, $enroll) {
        $arm = new AccountRecordModel();
        //1.创建订单
        $om = new OrderModel();
        $res6 = $om->create($user_id, $amount, PaymentMethod::Cash, 4, 0, '开通银卡代理', '', 0, 0, 4);

        //2.扣除金额
        $res1 = $arm->add($user_id, Currency::Cash, CurrencyAction::CashApplyMicroVIP, -$amount, $arm->getRecordAttach(1, '系统'), '申请银卡代理扣款');
        $res2 = true;
        if ($enroll > 0) {
            $res2 = $arm->add($user_id, Currency::Enroll, CurrencyAction::ENrollApplyMicroVIP, -$enroll, $arm->getRecordAttach(1, '系统'), '申请银卡代理扣款');
        }

        //升级
        $res3 = M('member')->where('id = ' . $user_id)->save(array('roleid' => array('exp', '`level`'), 'level' => 5, 'open_time' => time()));

        if ($res1 !== false && $res2 !== false && $res3 !== false && $res6 != '') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 现金积分申请钻卡代理处理。不记录毛利润，但要记录收益
     * @param unknown $user_id
     * @param unknown $amount
     * @param unknown $params
     */
    public function apply_honoureVip($user, $amount, $params, $enroll, $plan_type) {
        $user_id = $user['id'];
        $arm = new AccountRecordModel();
        //1.创建订单
        $om = new OrderModel();
        $res6 = $om->create($user_id, $amount, PaymentMethod::Cash, 4, 0, '开通钻卡代理', '', 0, 0, 4);

        //2.扣除金额
        $res1 = $arm->add($user_id, Currency::Cash, CurrencyAction::CashApplyHonourVIP, -$amount, $arm->getRecordAttach(1, '系统'), '申请钻卡代理扣款');
        $res2 = true;
        if ($enroll > 0) {
            $res2 = $arm->add($user_id, Currency::Enroll, CurrencyAction::ENrollApplyHonourVIP, -$enroll, $arm->getRecordAttach(1, '系统'), '申请钻卡代理扣款');
        }

        //交完钱-升级
        $res3 = true;
        $res5 = true;
        if ($params['honour_vip_apply_amount'] - $params['honour_vip_apply_first_amount'] == 0) {
            $res3 = M('member')->where('id = ' . $user_id)->save(array('roleid' => array('exp', '`level`'), 'level' => 7, 'open_time' => time()));
            $this->honoureVipclear($user_id);
            
            //吊起存储过程
            $pm = new ProcedureModel();
            $res5 = $pm->execute('V51_Event_apply', $user_id, '@error');
        }

        if ($res1 !== false && $res2 !== false && $res3 !== false && $res6 != '' && $res5) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 判断用户是否完成责任消费
     * @param unknown $user_id
     * @return \Think\mixed
     */
    public function isFinishDutyConsume($user_id) {
        M()->execute('INSERT IGNORE INTO `zc_dutyconsume`(`user_id`, `dutyconsume_income_enable`, `dutyconsume_uptime`) VALUES(' . $user_id . ', 1, UNIX_TIMESTAMP())');
        $consume = M('dutyconsume')->where('user_id = ' . $user_id)->find();
        return $consume['dutyconsume_income_enable'];
    }

    /**
     * 添加vip结算记录
     * @param unknown $user_id
     * @return \Think\mixed
     */
    public function vipclear($user_id) {
        $params = M('g_parameter', null)->find();
        $vip_clearing_info = M('vip_clearing')->where('user_id=' . $user_id)->find();
        if ($vip_clearing_info) {
            $data_vip_celaring = [
                'clearing_status' => 1,
                'clearing_uptime' => time()
            ];
            return M('vip_clearing')->where('user_id=' . $user_id)->save($data_vip_celaring);
        }

        $vo['user_id'] = $user_id;
        $vo['clearing_times'] = $params['vip_apply_return_month'];
        $vo['clearing_amount'] = $params['vip_apply_return_amount'];
        $vo['clearing_status'] = 1;
        $vo['clearing_addtime'] = time();
        return M('vip_clearing')->add($vo);
    }
    
    /**
     * 添加银卡代理结算记录
     * @param unknown $user_id
     * @return \Think\mixed
     */
    public function v_vipclear($user_id) {
        $params = M('g_parameter', null)->find();
        $vip_clearing_info = M('micro_vip_clearing')->where('user_id=' . $user_id)->find();
        if ($vip_clearing_info) {
            $data_vip_celaring = [
                'clearing_status' => 1,
                'clearing_uptime' => time()
            ];
            return M('micro_vip_clearing')->where('user_id=' . $user_id)->save($data_vip_celaring);
        }

        $vo['user_id'] = $user_id;
        $vo['clearing_times'] = $params['v51_micro_vip_apply_return_month'];
        $vo['clearing_amount'] = $params['v51_micro_vip_apply_return_amount'];
        $vo['clearing_status'] = 1;
        $vo['clearing_addtime'] = time();
        return M('micro_vip_clearing')->add($vo);
    }

    /**
     * 添加钻卡代理结算记录
     * @param unknown $user_id
     * @return \Think\mixed
     */
    public function honoureVipclear($user_id, $plan_id) {
        $params = M('g_parameter', null)->find();
        $vip_clearing_info = M('honour_vip_clearing')->where('user_id=' . $user_id)->find();
        if ($vip_clearing_info) {
            $data_vip_celaring = [
                'clearing_status' => 1,
                'clearing_uptime' => time()
            ];
            return M('honour_vip_clearing')->where('user_id=' . $user_id)->save($data_vip_celaring);
        }

        $vo['user_id'] = $user_id;
        $vo['clearing_times'] = $params['honour_vip_apply_return_month'];
        $vo['clearing_amount'] = $params['honour_vip_apply_return_amount'];
        $vo['clearing_status'] = 1;
        $vo['clearing_addtime'] = time();
        $res1 = M('honour_vip_clearing')->add($vo);
        return $res1;
    }

    /**
     * VIP计划
     * @param unknown $params
     * @param unknown $plan_id
     */
    public function user_play($amount, $planb_out_bei, $user_id, $plan_type, $round = 1) {
        if ($plan_type == 1) {
            $pp = M('user_plan')->where('user_id = ' . $user_id)->order('plan_id desc')->find();
            //B计划，插入记录
            $plan['user_id'] = $user_id;
            $plan['plan_type'] = 1;
            $plan['plan_amount'] = $amount;
            $plan['plan_out'] = 0;
            $plan['plan_uptime'] = time();
            if ($pp) {
                return M('user_plan')->where('plan_id = ' . $pp['plan_id'])->save($plan);
            } else {
                $plan['plan_addtime'] = time();
                return M('user_plan')->add($plan);
            }
        }
    }

    /**
     * 添加vip申请图片
     * @param unknown $user_id
     * @return \Think\mixed
     */
    public function vippic($user_id, $payway = 1, $plan_type) {
        $vipapply = M('vip_apply')->where('user_id = ' . $user_id)->order('apply_id desc')->find();
        $apply['user_id'] = $user_id;
        if ($payway >= 2) {
            $apply['apply_status'] = 0; //微信/支付宝未付款
        } elseif ($payway == 1) {
            $apply['apply_status'] = 3;
        } else {
            $apply['apply_status'] = 1;
        }
        $apply['apply_plan'] = $plan_type;
        $apply['apply_remark'] = '';
        $apply['apply_addtime'] = time();
        $apply['apply_uptime'] = time();
        if (empty($vipapply)) {
            return M('vip_apply')->add($apply);
        } else {
            return M('vip_apply')->where('apply_id=' . $vipapply['apply_id'])->save($apply);
        }
    }
    
    /**
     * 添加银卡代理申请图片
     * @param unknown $user_id
     * @return \Think\mixed
     */
    public function v_vippic($user_id, $payway = 1, $plan_type) {
        $vipapply = M('micro_vip_apply')->where('user_id = ' . $user_id)->order('apply_id desc')->find();
        $apply['user_id'] = $user_id;
        if ($payway >= 2) {
            $apply['apply_status'] = 0; //微信未付款
        } elseif ($payway == 1) {
            $apply['apply_status'] = 3;
        } else {
            $apply['apply_status'] = 1;
        }
        $apply['apply_remark'] = '';
        $apply['apply_addtime'] = time();
        $apply['apply_uptime'] = time();
        if (empty($vipapply)) {
            return M('micro_vip_apply')->add($apply);
        } else {
            return M('micro_vip_apply')->where('apply_id=' . $vipapply['apply_id'])->save($apply);
        }
    }

    /**
     * 添加vip申请图片
     * @param unknown $user_id
     * @return \Think\mixed
     */
    public function honoureVippic($user_id, $payway = 1, $plan_type) {
        $vipapply = M('honour_vip_apply')->where('user_id = ' . $user_id)->order('apply_id desc')->find();
        $apply['user_id'] = $user_id;
        if ($payway >= 2) {
            $apply['apply_status'] = 0; //微信未付款
        } elseif ($payway == 1) {
            $apply['apply_status'] = 3;
        } else {
            $apply['apply_status'] = 1;
        }
        $apply['apply_plan'] = $plan_type;
        $apply['apply_remark'] = '';
        $apply['apply_addtime'] = time();
        $apply['apply_uptime'] = time();
        if (empty($vipapply)) {
            return M('honour_vip_apply')->add($apply);
        } else {
            return M('honour_vip_apply')->where('apply_id=' . $vipapply['apply_id'])->save($apply);
        }
    }

    /**
     * 概率算法
     */
    public function get_rand($proArr) {
        $result = '';
        $proSum = array_sum($proArr);
        foreach ($proArr as $key => $proCur) {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $proCur) {
                $result = $key;
                break;
            } else {
                $proSum -= $proCur;
            }
        }

        unset($proArr);
        return $result;
    }

    /**
     * 获取会员开通钻卡代理的金额
     * @param unknown $user
     * @param unknown $params
     */
    public function getZGvipMoney($user, $params) {
    	$user['fees'] = 0;
        //是否分批支付，首付设置的金额
        if ($params['honour_vip_apply_first_amount'] < $params['honour_vip_apply_amount']) {
            $return['first_amount'] = $params['honour_vip_apply_first_amount'];
            $return['batchs'] = 1;
        } else {
            $return['first_amount'] = $params['honour_vip_apply_amount'];
            $return['batchs'] = 0;
        }
        //最终金额=首付-创客/vip支付的
        if ($user['level'] == 2) {
            $return['amount'] = ceil(($return['first_amount'] - $user['fees']) * 100) / 100;
        } elseif ($user['level'] == 5) {
            $return['amount'] = $return['first_amount'] - $params['v51_micro_vip_apply_amount'];
        } elseif ($user['level'] == 6) {
            $return['amount'] = $return['first_amount'] - $params['vip_apply_amount'];
        } else {
            $return['amount'] = $return['first_amount'];
        }

        //申请说明
        //允许使用的注册币数量
        if ($user['level'] != 6) {
            $return['enroll_fill'] = ceil($params['honour_vip_apply_amount'] * $params['honour_vip_apply_use_enroll_bai']) / 100;
        } else {
            $return['enroll_fill'] = ceil(($params['honour_vip_apply_amount'] - $params['vip_apply_amount']) * $params['honour_vip_apply_use_enroll_bai']) / 100;
        }
        return $return;
    }

    /**
     * 开通钻卡代理附属信息
     * @param unknown $user
     * @param unknown $params
     * @param unknown $firstamount
     */
    public function honourVipaffiliate($user, $params, $plantype) {
        //钻卡代理未付金额
        $data['user_id'] = $user['id'];
        $data['honour_vip_unpaid_amount'] = $params['honour_vip_apply_amount'] - $params['honour_vip_apply_first_amount'];
        $data['honour_vip_apply_addtime'] = time();

        //减去A方案vip、B方案VIP的补贴
        if ($user['level'] == 6) {
            $params['profits_market_subsidy_honour_vip_1_amount'] = $params['profits_market_subsidy_honour_vip_1_amount'] - $params['profits_market_subsidy_vip_1_amount'];
            $params['profits_market_subsidy_honour_vip_2_amount'] = $params['profits_market_subsidy_honour_vip_2_amount'] - $params['profits_market_subsidy_vip_2_amount'];
            $params['planb_profits_market_subsidy_honour_vip_1_amount'] = $params['planb_profits_market_subsidy_honour_vip_1_amount'] - $params['planb_profits_market_subsidy_vip_1_amount'];
            $params['planb_profits_market_subsidy_honour_vip_2_amount'] = $params['planb_profits_market_subsidy_honour_vip_2_amount'] - $params['planb_profits_market_subsidy_vip_2_amount'];
            $params['profits_framework_subsidy_honour_company_1_amount'] = $params['profits_framework_subsidy_honour_company_1_amount'] - $params['profits_framework_subsidy_company_1_amount'];
            $params['profits_framework_subsidy_honour_company_2_amount'] = $params['profits_framework_subsidy_honour_company_2_amount'] - $params['profits_framework_subsidy_company_2_amount'];
            $params['planb_profits_framework_subsidy_honour_company_1_amount'] = $params['planb_profits_framework_subsidy_honour_company_1_amount'] - $params['planb_profits_framework_subsidy_company_1_amount'];
            $params['planb_profits_framework_subsidy_honour_company_2_amount'] = $params['planb_profits_framework_subsidy_honour_company_2_amount'] - $params['planb_profits_framework_subsidy_company_2_amount'];
        }

        $marketdata = array();   //市场补贴
        //读取一层VIP
        $sql = 'SELECT zm.id AS user_id, (zmc.relevel - zm.relevel) AS depth, IFNULL(up.plan_round, 0) AS plan_round, IFNULL(up.plan_type, 0) AS plan_type
            FROM zc_member AS zm
            LEFT JOIN zc_member AS zmc ON FIND_IN_SET(zm.id, zmc.repath)
            LEFT JOIN zc_dutyconsume AS ds ON zm.id = ds.user_id
            LEFT JOIN `zc_user_affiliate` AS ua ON zm.id = ua.user_id
            LEFT JOIN `zc_user_plan` AS up ON zm.id = up.user_id
            WHERE zm.is_lock = 0
                AND zm.`level` in (6, 7)   # 是否是VIP或钻卡代理
                AND zmc.id = ' . $user['id'] . '
                AND IFNULL(ua.affiliate_income_disable, 0) = 0 # 是否禁止收益
                AND IFNULL(ds.dutyconsume_income_enable, 1) = 1 # 是否完成责任消费
                AND (IFNULL(up.plan_type, 0) = 0 OR (IFNULL(up.plan_type, 0) = 1 AND IFNULL(up.plan_out, 0) = 0)) # B计划用户是否出局
            ORDER BY depth
            LIMIT 1';
        $vip_parents = M()->query($sql);
        $vip_parent_id = 0;
        if ($vip_parents && count($vip_parents) > 0) {
            $vip_parent = $vip_parents[0];
            $item = [];
            $item['user_id'] = $vip_parent['user_id'];
            $vip_parent_id = $vip_parent['user_id'];
            if ($vip_parent['plan_type'] == 1) {
                $item['amount'] = $params['planb_profits_market_subsidy_honour_vip_1_amount'];
            } else {
                $item['amount'] = $params['profits_market_subsidy_honour_vip_1_amount'];
            }
            $marketdata[] = $item;
        }


        //读取二层钻卡代理
        $sql = 'SELECT zm.id AS user_id, (zmc.relevel - zm.relevel) AS depth, IFNULL(up.plan_round, 0) AS plan_round, IFNULL(up.plan_type, 0) AS plan_type
            FROM zc_member AS zm
            LEFT JOIN zc_member AS zmc ON FIND_IN_SET(zm.id, zmc.repath)
            LEFT JOIN zc_dutyconsume AS ds ON zm.id = ds.user_id
            LEFT JOIN `zc_user_plan` AS up ON zm.id = up.user_id
            LEFT JOIN `zc_user_affiliate` AS ua ON zm.id = ua.user_id
            WHERE zm.is_lock = 0
                AND zm.`level` in (7) 	# 是否 钻卡代理
                AND zmc.id = ' . $user['id'] . '
                AND zm.id <> ' . $vip_parent_id . '
                AND IFNULL(ua.affiliate_income_disable, 0) = 0 # 是否禁止收益
                AND IFNULL(ds.dutyconsume_income_enable, 1) = 1 # 是否完成责任消费
                AND (IFNULL(up.plan_type, 0) = 0 OR (IFNULL(up.plan_type, 0) = 1 AND IFNULL(up.plan_out, 0) = 0)) # B计划用户是否出局
            ORDER BY depth
            LIMIT 30';
        $vip_parents = M()->query($sql);
        $dai = 1;
        foreach ($vip_parents as $row) {
            $dai++;
            $item = [];
            $item['amount'] = 0;
            if ($row['plan_type'] == 1) {
                if ($dai == 2) {
                    $item['amount'] = $params['planb_profits_market_subsidy_honour_vip_2_amount'];
                } elseif ($params['planb_profits_market_subsidy_honour_vip_3_median_dai'] > 0 && $dai <= $params['planb_profits_market_subsidy_honour_vip_3_median_dai']) {
                    $item['amount'] = $params['planb_profits_market_subsidy_honour_vip_3_median_amount'];
                } elseif ($params['planb_profits_market_subsidy_honour_vip_median_max_dai'] > 0 && $dai <= $params['planb_profits_market_subsidy_honour_vip_median_max_dai']) {
                    $item['amount'] = $params['planb_profits_market_subsidy_honour_vip_median_max_amount'];
                }
            } else {
                if ($dai == 2) {
                    $item['amount'] = $params['profits_market_subsidy_honour_vip_2_amount'];
                } elseif ($params['profits_market_subsidy_honour_vip_3_median_dai'] > 0 && $dai <= $params['profits_market_subsidy_honour_vip_3_median_dai']) {
                    $item['amount'] = $params['profits_market_subsidy_honour_vip_3_median_amount'];
                } elseif ($params['profits_market_subsidy_honour_vip_median_max_dai'] > 0 && $dai <= $params['profits_market_subsidy_honour_vip_median_max_dai']) {
                    $item['amount'] = $params['profits_market_subsidy_honour_vip_median_max_amount'];
                }
            }
            if ($item['amount'] > 0) {
                $item['user_id'] = $row['user_id'];
                $marketdata[] = $item;
            }
        }



        //读取上两级区域合伙人
        $sql = 'SELECT zm.id AS user_id, (zmc.relevel - zm.relevel) AS depth, IFNULL(up.plan_round, 0) AS plan_round, IFNULL(up.plan_type, 0) AS plan_type
            FROM zc_member AS zm
            LEFT JOIN zc_member AS zmc ON FIND_IN_SET(zm.id, zmc.repath)
            LEFT JOIN zc_dutyconsume AS ds ON zm.id = ds.user_id
            LEFT JOIN `zc_user_plan` AS up ON zm.id = up.user_id
            LEFT JOIN `zc_user_affiliate` AS ua ON zm.id = ua.user_id
            WHERE zm.is_lock = 0
                AND zm.`role` in (4) 	# 是否 区域合伙人
                AND zmc.id = ' . $user['id'] . '
                AND IFNULL(ua.affiliate_income_disable, 0) = 0 # 是否禁止收益
                AND IFNULL(ds.dutyconsume_income_enable, 1) = 1 # 是否完成责任消费
                AND (IFNULL(up.plan_type, 0) = 0 OR (IFNULL(up.plan_type, 0) = 1 AND IFNULL(up.plan_out, 0) = 0)) # B计划用户是否出局
            ORDER BY depth
            LIMIT 2';
        $frameworkdata = array();  //机构补贴
        $framework_parents = M()->query($sql);
        $dai = 1;
        foreach ($framework_parents as $row) {
            $item = [];
            $item['user_id'] = $row['user_id'];
            if ($row['plan_type'] == 1) {
                $item['amount'] = $params['planb_profits_framework_subsidy_honour_company_' . $dai . '_amount'];
            } else {
                $item['amount'] = $params['profits_framework_subsidy_honour_company_' . $dai . '_amount'];
            }
            $frameworkdata[] = $item;
            $dai++;
        }
        $data['honour_vip_market_subsidy'] = json_encode($marketdata);
        $data['honour_vip_framework_subsidy'] = json_encode($frameworkdata);
        //是否存在
        $uu = M('user_affiliate')->where('user_id = ' . $user['id'])->find();
        if ($uu) {
            return M('user_affiliate')->where('user_id = ' . $user['id'])->save($data);
        } else {
            return M('user_affiliate')->add($data);
        }
    }

    /**
     * VIP续费
     * @param unknown $user_id
     * @param unknown $firstpay
     */
    public function honourVipRenew($user_id, $firstpay, $step = 2) {
        $params = M('g_parameter', null)->find();
        $user = M('member')->where('id=' . $user_id)->find();
        $arm = new AccountRecordModel();

        if ($step == 2) {
            //现金积分
            $res1 = $arm->add($user_id, Currency::Cash, CurrencyAction::CashApplyHonourVIP, -$firstpay['honour_vip_unpaid_amount'], $arm->getRecordAttach(1, '系统'), '钻卡代理续费');
            $res2 = M('user_affiliate')->where('affiliate_id = ' . $firstpay['affiliate_id'])->save(array('honour_vip_unpaid_amount' => 0));
            $res3 = M('member')->where('id = ' . $user_id)->save(array('roleid' => array('exp', '`level`'), 'level' => 7, 'open_time' => time()));
            if ($res1 === false || $res2 === false || $res3 === false) {
                return false;
            }
        }
        return true;
        
    }

    /**
     * 获取用户复投的轮数
     * @param unknown $user_id
     * @param number $plan_type
     */
    public function getNextPlan($user_id, $plan_type = 1) {
        $plan = M('user_plan')->where('user_id = ' . $user_id . ' and plan_type = ' . $plan_type)->order('plan_id desc')->find();
        if (empty($plan)) {
            return 1;
        } else {
            return $plan['plan_round'] + 1;
        }
    }

}
