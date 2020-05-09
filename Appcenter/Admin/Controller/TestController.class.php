<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 性能测试专用
// +----------------------------------------------------------------------
namespace Admin\Controller;

use Common\Controller\AuthController;

class TestController extends AuthController
{

    public function __construct()
    {
        parent::__construct();

        C('TOKEN_ON', false);

        G('begin');
    }

    public function __destruct()
    {
        parent::__destruct();

        G('end');
        echo '<br>Time: ' . G('begin', 'end', 3) . 's ';
        echo 'Memory: ' . G('begin', 'end', 'm') . 'kb';
    }

    public function index()
    {
        $this->display();
    }

    /**
     * 测试merchant_bonus存储过程原始性能
     * 商家联盟奖
     * 耗时: ~0.135s
     */
    public function testMerchantBonus($merchant_uid = '7599', $money = '137')
    {
        M()->execute(C('ALIYUN_TDDL_MASTER') . "call merchant_bonus({$merchant_uid}, {$money})");
    }

    /**
     * 测试merchant_bonus存储过程转换性能
     * @merchant_uid 7599[雷勇军] 推荐人 7233[李学容]
     * 耗时: ~0.142s
     * 对比: 耗时基本相同,优势为可及时返回处理成功与否状态
     */
    public function testMerchantBonusSql($merchant_uid = '7599', $money = '137')
    {
        $Member = M('Member');
        $Parameter = M('Parameter', 'g_');
        $Bonus = M('Bonus', 'g_');

        $Bonus->startTrans();

        //商家推荐人ID
        $map_member['is_lock'] = array('eq', 0);
        $map_member['id'] = array('eq', $merchant_uid);
        $merchant_info = $Member
            ->where($map_member)
            ->field('reid,relevel')
            ->find();

        //商家联盟奖百分比
        $parameter_info = $Parameter->where('id=1')->field('marchant_bai')->find();
        $merchant_bai = $parameter_info['marchant_bai'];

        //获取推荐人是否被锁定
        $map_reid_member['id'] = array('eq', $merchant_info['reid']);
        $reid_member_info = $Member
            ->where($map_reid_member)
            ->field('is_lock,level')
            ->find();

        //初始化变化,为保存数据做准备
        $data_bonus = array(
            'uid' => $merchant_info['reid'],
            'post_time' => time(),
            'type' => 3, //商家联盟奖
            'is_pay' => 1,
            'pay_time' => time(),
            'from_uid' => $merchant_uid,
        );

        //推荐人未被锁定,且为非体验会员
        if ($reid_member_info['is_lock'] == 0 && $reid_member_info['level'] > 1) {
            //应得的推广奖金额
            $data_bonus['bonus'] = sprintf('%.4f', $money * $merchant_bai / 100);
            if ($data_bonus['bonus'] <= 0) {
                return false;
            }

            if ($data_bonus['bonus'] > 0) {
                $data_bonus['about'] = "拿{$merchant_info['relevel']}代会员";

                //增加联盟奖明细
                if (!$Bonus->create($data_bonus, '', true)) {
                    return false;
                } else {
                    $bonus_id = $Bonus->add();
                    if (empty($bonus_id)) {
                        return false;
                    } else {
                        //增加联盟奖现金币至推荐人账号
                        $map_update['id'] = array('eq', $merchant_info['reid']);
                        $data_member['cash'] = array('exp', 'cash+' . $data_bonus['bonus']);
                        if ($Member->where($map_update)->save($data_member) === false) {
                            $Bonus->rollback();
                            return false;
                        } else {
                            $Bonus->commit();
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * 测试query_3_4存储过程原始性能
     * 筛选掉同级以下会员(包括同级)
     * 耗时: ~0.021s
     */
    public function testQuery34($uid = '1', $l3 = '3', $l4 = '4')
    {
        M()->execute(C('ALIYUN_TDDL_MASTER') . "call query_3_4($uid, $l3, $l4)");
    }

    /**
     * 测试query_3_4存储过程转换性能
     * 耗时: ~0.231s
     * 对比: 耗时相差较大
     */
    public function testQuery34Sql($uid = '1', $l3 = '3', $l4 = '4')
    {
        $Member = M('Member');

        $map['repath'] = array('like', '%,' . $uid . ',%');
        $map['level'][] = array('eq', $l3);
        $map['level'][] = array('eq', $l4);
        $map['level'][] = 'or';

        $list_1 = $Member
            ->where($map)
            ->field('id')
            ->select();

        $return = array();
        unset($map);

        foreach ($list_1 as $list) {
            $return[] = $list['id'];

            $map['repath'] = array('like', '%,' . $list['id'] . ',%');
            $list_2 = $Member
                ->where($map)
                ->field('id')
                ->select();
            foreach ($list_2 as $list1) {
                $return[] = $list1['id'];
            }
        }

        return $return;
    }

    /**
     * 测试query_condition存储过程原始性能
     * 申请服务中心或区域合伙人的条件
     * 耗时: ~0.018s
     */
    public function testQueryCondition($uid = 3142, $ceng = 2)
    {
        M()->execute(C('ALIYUN_TDDL_MASTER') . "call query_condition(" . $uid . "," . $ceng . ",@msg)");
    }

    /**
     * 测试query_condition存储过程转换性能
     * 耗时: ~0.020s
     */
    public function testQueryConditionSql($uid = 3142, $ceng = 2)
    {
        $Member = M('Member');

        $map_level['id'] = array('eq', $uid);
        $level_info = $Member->where($map_level)->field('relevel')->find();

        $map_member['repath'] = array('like', "%,{$uid},%");
        $map_member['recount'] = array('egt', 3);
        $map_member['level'] = array('gt', 1);
        $map_member['is_pass'] = array('eq', 0); //注册通过

        $i_min = $level_info['relevel'] + 1;
        $i_max = $level_info['relevel'] + $ceng;
        $msg = 1; //默认满足

        if ($ceng == 0) {
            $msg = 1;
        } else {
            //是否满足$i_max层
            $map_member['relevel'] = array('eq', $i_min);
            $member_list = $Member
                ->where($map_member)
                ->field('id')
                ->select();

            if (count($member_list) >= 3) {
                foreach ($member_list as $k => $list) {
                    $member_arr = function ($i_min, $list) use ($i_max, $map_member, $Member) {
                        if ($i_min >= $i_max) {
                            return 1;
                        }

                        $map_member['repath'] = array('like', "%,{$list['id']},%");
                        $map_member['relevel'] = array('eq', $i_min);
                        $member_list_son = $Member
                            ->where($map_member)
                            ->field('id')
                            ->select();

                        if (count($member_list_son) >= 3) {
                            foreach ($member_list_son as $k1 => $list1) {
                                return $member_arr($i_min + 1, $list1);
                            }
                        } else {
                            return 0;
                        }
                    };

                    $msg = $member_arr($i_min, $list);
                    if ($msg == 0) {
                        break;
                    }
                }
            } else {
                $msg = 0;
            }
        }

        echo $msg;

        return $msg;
    }

    /**
     * 测试query_m_4存储过程原始性能
     * 筛选掉同级以下会员(不包括同级)
     * 耗时: ~0.023s
     */
    public function testQueryM4($uid = '1', $l3 = '3', $l4 = '4')
    {
        M()->execute(C('ALIYUN_TDDL_MASTER') . "call query_m_4({$uid},{$l3},{$l4})");
    }

    /**
     * 测试query_m_4存储过程转换性能
     * 耗时: ~0.239s
     */
    public function testQueryM4Sql($uid = '1', $l3 = '3', $l4 = '4')
    {
        $Member = M('Member');

        $map['repath'] = array('like', '%,' . $uid . ',%');
        $map['level'][] = array('eq', $l3);
        $map['level'][] = array('eq', $l4);
        $map['level'][] = 'or';

        $list_1 = $Member
            ->where($map)
            ->field('id')
            ->select();

        $return = array();
        unset($map);

        foreach ($list_1 as $list) {
            //$return[] = $list['id'];

            $map['repath'] = array('like', '%,' . $list['id'] . ',%');
            $list_2 = $Member
                ->where($map)
                ->field('id')
                ->select();
            foreach ($list_2 as $list1) {
                $return[] = $list1['id'];
            }
        }

        return $return;
    }

    /**
     * 测试recommand_bonus存储过程原始性能
     * 推荐奖
     * 耗时: ~0.008s
     */
    public function testRecommandBonus($uid = '4365')
    {
        M()->execute(C('ALIYUN_TDDL_MASTER') . "call recommand_bonus({$uid}, @msg)");
    }

    /**
     * 测试recommand_bonus存储过程转换性能
     * 耗时: ~1.169s
     */
    public function testRecommandBonusSql($uid = '4365')
    {
        $Member = M('Member');
        $Parameter = M('Parameter', 'g_');
        $Bonus = M('Bonus', 'g_');

        $Bonus->startTrans();

        //获取所有推荐人信息
        $map['m.is_lock'] = array('eq', 0);
        $map['m.level'] = array('gt', 1);
        $map['_string'] = " find_in_set(m.id, mem.repath)";
        $member_info = $Member
            ->alias('m')
            ->join("join __MEMBER__ mem ON mem.id={$uid}")
            ->where($map)
            ->field('m.id,m.relevel,mem.relevel son_relevel')
            ->select();

        //获取推广奖相关配置参数
        $parameter_info = $Parameter->where('id=1')->field('tui_bai,tui_dai,rate_color')->find();

        //初始化明细表数据
        $data_bonus = array(
            'post_time' => time(),
            'type' => 1,
            'exchange' => $parameter_info['rate_color'],
            'is_pay' => 1,
            'pay_time' => time(),
            'from_uid' => $uid,
        );

        foreach ($member_info as $k => $list) {
            //当前会员ID与推荐人ID层级小于等于指定层级时
            $diff_relevel = $list['son_relevel'] - $list['relevel'];

            if ($diff_relevel <= $parameter_info['tui_dai']) {
                $data_bonus['uid'] = $list['id'];
                $data_bonus['money'] = $parameter_info['tui_bai'];
                $data_bonus['about'] = "拿{$diff_relevel}代会员";
                if (!$Bonus->create($data_bonus, '', true)) {
                    return false;
                }

                $id = $Bonus->add();
                if (!$id) {
                    return false;
                }

                $map_member['id'] = array('eq', $list['id']);
                $data_member['colorcoin'] = array('exp', 'colorcoin+' . $data_bonus['money']);
                if ($Member->where($map_member)->save($data_member) === false) {
                    $Bonus->rollback();
                    return false;
                }

                $Bonus->commit();
            }
        }

        return true;
    }

    /**
     * 测试repeat_bonus存储过程原始性能
     * 重复消费奖
     * 耗时: ~0.805s
     */
    public function testRepeatBonus($uid = '4365', $money = '100')
    {
        M()->execute(C('ALIYUN_TDDL_MASTER') . "call repeat_bonus({$uid},{$money})");
    }

    /**
     * 测试repeat_bonus存储过程转换性能
     * 耗时: ~0.950s
     */
    public function testRepeatBonusSql($uid = '4365', $money = '100')
    {
        $Member = M('Member');
        $Parameter = M('Parameter', 'g_');
        $Bonus = M('Bonus', 'g_');

        //获取所有推荐人信息
        $map['m.is_lock'] = array('eq', 0);
        $map['m.level'] = array('gt', 1);
        $map['_string'] = " find_in_set(m.id, mem.repath)";
        $member_info = $Member
            ->alias('m')
            ->join("join __MEMBER__ mem ON mem.id={$uid}")
            ->where($map)
            ->field('m.id,m.relevel,mem.relevel son_relevel')
            ->select();

        //获取重复消费奖相关配置参数
        $parameter_info = $Parameter->where('id=1')->field('repeat_bai,repeat_dai')->find();

        //初始化明细表数据
        $data_bonus = array(
            'post_time' => time(),
            'type' => 2,
            'is_pay' => 1,
            'pay_time' => time(),
            'from_uid' => $uid,
        );

        foreach ($member_info as $k => $list) {
            //当前会员ID与推荐人ID层级小于等于指定层级时
            $diff_relevel = $list['son_relevel'] - $list['relevel'];

            if ($diff_relevel <= $parameter_info['repeat_dai']) {
                $data_bonus['money'] = sprintf('%.4f', $parameter_info['repeat_bai'] / 100 * $money);
                if ($data_bonus['money'] <= 0) {
                    return false;
                }

                $data_bonus['uid'] = $list['id'];
                $data_bonus['about'] = "拿{$diff_relevel}代会员";
                if (!$Bonus->create($data_bonus, '', true)) {
                    return false;
                }

                $id = $Bonus->add();
                if (!$id) {
                    return false;
                }

                $map_member['id'] = array('eq', $list['id']);
                $data_member['cash'] = array('exp', 'cash+' . $data_bonus['money']);
                if ($Member->where($map_member)->save($data_member) === false) {
                    $Bonus->rollback();
                    return false;
                }

                $Bonus->commit();
            }
        }

        return true;
    }

    /**
     * 测试service_company_bonus存储过程原始性能
     * 服务中心和区域合伙人奖
     * 耗时: ~0.143s
     */
    public function testServiceCompanyBonus($uid = '4365', $money = '100')
    {
        M()->execute(C('ALIYUN_TDDL_MASTER') . "call service_company_bonus({$uid},{$money})");
    }

    /**
     * 测试service_company_bonus存储过程转换性能
     * 耗时: ~0.128s
     */
    public function testServiceCompanyBonusSql($uid = '4365', $money = '100')
    {
        $Member = M('Member');
        $Parameter = M('Parameter', 'g_');
        $Bonus = M('Bonus', 'g_');

        $Bonus->startTrans();

        //获取所有推荐人信息
        $map['m.is_lock'] = array('eq', 0);
        $map['_string'] = " find_in_set(m.id, mem.repath)";
        $map['m.level'] = array('in', '3,4');
        $member_info = $Member
            ->alias('m')
            ->join("join __MEMBER__ mem ON mem.id={$uid}")
            ->where($map)
            ->field('m.id,m.relevel,m.level,mem.relevel son_relevel,mem.level son_level')
            ->select();

        //获取服务中心和区域合伙人奖相关配置参数
        $parameter_info = $Parameter->where('id=1')->field('service_bai,service_dai,company_bai,company_dai')->find();

        //初始化明细表数据
        $data_bonus = array(
            'post_time' => time(),
            'is_pay' => 1,
            'pay_time' => time(),
            'from_uid' => $uid,
        );

        foreach ($member_info as $k => $list) {
            //当前会员ID与推荐人ID层级小于等于指定层级时
            $diff_relevel = $list['son_relevel'] - $list['relevel'];
            //相差等级
            $diff_level = $list['level'] - $list['son_level'];

            //服务中心奖
            if ($list['level'] == 3 && $diff_relevel <= $parameter_info['service_dai'] && $diff_level > 0) {
                $data_bonus['money'] = sprintf('%.4f', $parameter_info['service_bai'] / 100 * $money);
                $data_bonus['type'] = 4;
            }

            //区域中心奖
            if ($list['level'] == 4 && $diff_relevel <= $parameter_info['company_dai'] && $diff_level > 0) {
                $data_bonus['money'] = sprintf('%.4f', $parameter_info['company_bai'] / 100 * $money);
                $data_bonus['type'] = 5;
            }

            if ($data_bonus['money'] <= 0) {
                return false;
            }

            $data_bonus['uid'] = $list['id'];
            $data_bonus['about'] = "拿{$diff_relevel}代会员";

            if (!$Bonus->create($data_bonus, '', true)) {
                return false;
            }

            $id = $Bonus->add();
            if (!$id) {
                return false;
            }

            $map_member['id'] = array('eq', $list['id']);
            $data_member['cash'] = array('exp', 'cash+' . $data_bonus['money']);
            if ($Member->where($map_member)->save($data_member) === false) {
                $Bonus->rollback();
                return false;
            }

            $Bonus->commit();
        }

        return true;
    }

    /**
     * 测试service_company_jian存储过程原始性能
     * 服务中心和区域合伙人见点奖
     * 耗时: ~0.068s
     */
    public function testServiceCompanyJian($uid = '4365')
    {
        M()->execute(C('ALIYUN_TDDL_MASTER') . "call service_company_jian({$uid})");
    }

    /**
     * 测试service_company_jian存储过程转换性能
     * 耗时: ~0.087s
     */
    public function testServiceCompanyJianSql($uid = '4365')
    {
        $Member = M('Member');
        $Parameter = M('Parameter', 'g_');
        $Bonus = M('Bonus', 'g_');

        $Bonus->startTrans();

        //获取所有推荐人信息
        $map['m.is_lock'] = array('eq', 0);
        $map['_string'] = " find_in_set(m.id, mem.repath)";
        $map['m.level'] = array('in', '3,4');
        $member_info = $Member
            ->alias('m')
            ->join("join __MEMBER__ mem ON mem.id={$uid}")
            ->where($map)
            ->field('m.id,m.relevel,m.level,mem.relevel son_relevel,mem.level son_level')
            ->select();

        //获取服务中心和区域合伙人见点奖相关配置参数
        $parameter_info = $Parameter->where('id=1')->field('service_jian,service_dai,company_jian,company_dai')->find();

        //初始化明细表数据
        $data_bonus = array(
            'post_time' => time(),
            'is_pay' => 1,
            'pay_time' => time(),
            'from_uid' => $uid,
        );

        foreach ($member_info as $k => $list) {
            //当前会员ID与推荐人ID层级小于等于指定层级时
            $diff_relevel = $list['son_relevel'] - $list['relevel'];

            //服务中心见点奖
            if ($list['level'] == 3 && $diff_relevel <= $parameter_info['service_dai']) {
                $data_bonus['money'] = $parameter_info['service_jian'];
                $data_bonus['type'] = 8;
            }
            //区域合伙人见点奖
            if ($list['level'] == 4 && $diff_relevel <= $parameter_info['company_dai']) {
                $data_bonus['money'] = $parameter_info['company_jian'];
                $data_bonus['type'] = 9;
            }

            $data_bonus['uid'] = $list['id'];
            $data_bonus['about'] = "拿{$diff_relevel}代会员";

            if (!$Bonus->create($data_bonus, '', true)) {
                return false;
            }

            $id = $Bonus->add();
            if (!$id) {
                return false;
            }

            $map_member['id'] = array('eq', $list['id']);
            $data_member['cash'] = array('exp', 'cash+' . $data_bonus['money']);
            if ($Member->where($map_member)->save($data_member) === false) {
                $Bonus->rollback();
                return false;
            }

            $Bonus->commit();
        }

        return true;
    }

    /**
     * 读写分离并发测试1
     */
    public function dbTest1()
    {
        $Member = M('Member');
        M()->startTrans();
        $member_info = $Member->lock(true)->where('id=4365')->field('points')->find();

        sleep(5);

        $data['points'] = array('exp', 'points+1');
        $Member->where('id=4365')->save($data);
        M()->commit();

        $member_info = $Member->where('id=4365')->field('points')->find();
        print_r($member_info);
    }

    /**
     * 读写分离并发测试2
     */
    public function dbTest2()
    {
        $Member = M('Member');
        M()->startTrans();

        $member_info = $Member->lock(true)->where('id=4365')->field('points')->find();

        $data['points'] = array('exp', 'points+1');
        $Member->where('id=4365')->save($data);
        M()->commit();

        $member_info = $Member->where('id=4365')->field('points')->find();
        print_r($member_info);
    }

    //广发接口测试
    public function cgbTest()
    {
        Vendor("CgbPay.CgbPay#Api"); //广发银企直联基础组件
        $cgbPay = new \CgbPayApi();

        $data = array(
            'tranCode' => '0011',
            'traceNo' => '',
            'outAccName' => '',
            'outAcc' => \CgbPayConfig::outAcc,
            'outAccName' => '',
            'inAccName' => '张三',
            'inAcc' => '2324234234',
            'inAccBank' => '中国广发银行',
            'inAccAdd' => '四川省成都市高新区',
            'amount' => 250.33,
            'remark' => '转账至用户',
            'date' => date('Ymd'),
            'comment' => '',
            'creNo' => '',
            'frBalance' => '',
            'toBalance' => '',
            'handleFee' => '',
        );

        echo $cgbPay->getXmlData($data);
    }

}

?>