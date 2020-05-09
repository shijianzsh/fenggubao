<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 提现 通用 接口
// +----------------------------------------------------------------------
namespace APP\Controller;

use Common\Controller\ApiController;
use V4\Model\CurrencyAction;
use V4\Model\Currency;
use V4\Model\AccountRecordModel;
use V4\Model\AccountModel;
use V4\Model\DebugLogModel;

class WithdrawController extends ApiController
{

    protected $api_safe_ip = '110.184.215.84,120.79.85.89,0.0.0.0'; //允许远程调用接口的服务器IP列表
    protected $api_remote_ip; //远程IP
    protected $cgbPay;
    protected $push_uid = 1; //接收推送消息的用户ID,多个用半角逗号隔开
    protected $log_file; //用于存放日志文件

    public function __construct($request = '')
    {
        parent::__construct($request);

        //检测创建日志文件
        $this->log_file = $_SERVER['DOCUMENT_ROOT'] . '/record/' . MODULE_NAME . '/' . CONTROLLER_NAME . '/' . ACTION_NAME . '/' . date('Y') . '_' . date('m') . '_' . date('d') . '.log.php';
        if (!file_exists($this->log_file)) {
            $log_dir = dirname($this->log_file);
            if (!is_dir($log_dir)) {
                mkdir($log_dir, 0777, true);
            }
            file_put_contents($this->log_file, "<?php\n exit; \n ?> \n" . PHP_EOL, FILE_APPEND);
        }

        $this->api_remote_ip = get_client_ip();
        $ip_allow = strpos($this->api_safe_ip, ',') ? explode(',', $this->api_safe_ip) : array($this->api_safe_ip);
        $ip_result = false; //ip检测结果,初始化默认不通过
        foreach ($ip_allow as $ip) {
            if (preg_match('/\-/', $ip)) { //ip段
                $ip_dot = explode('.', $ip);
                $ip_prefix = $ip_dot[0] . '.' . $ip_dot[1] . '.' . $ip_dot[2];
                if (strpos($this->api_remote_ip, $ip_prefix) !== false) {
                    $ip_result = true;
                    break;
                }
            } else {
                if ($ip == $this->api_remote_ip) {
                    $ip_result = true;
                    break;
                }
            }
        }

        if (!$ip_result) {
            file_put_contents($this->log_file, date('Y-m-d H:i:s') . "[ip:{$this->api_remote_ip}][start][fail:Unauthorized IP]" . PHP_EOL, FILE_APPEND);
            exit('Unauthorized IP:' . $this->api_remote_ip);
        } else {
            file_put_contents($this->log_file, date('Y-m-d H:i:s') . "[ip:{$this->api_remote_ip}][start][success]" . PHP_EOL, FILE_APPEND);
        }

        Vendor("WxPay.WxPay#Api"); //微信支付基础组件

        Vendor("CgbPay.CgbPay#Api"); //广发银企直联基础组件
        $this->cgbPay = new \CgbPayApi();

        Vendor("AliPay.AliPay#Api"); //支付宝基础组件
    }

    /**
     * 对接 远程定时调用 执行微信提现队列操作 接口
     *
     * @建议频率: 30s
     */
    public function wxDepositQueue()
    {
        //企业支付接口
        $pay_url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';

        $TixianQueue = M('TixianQueue');
        $WithdrawCash = M('WithdrawCash');
        $Member = M('Member');
        $limit = 10;

        $AccountModel = new AccountModel();

        //获取提现队列数据
        $map_tixian['type'] = array('eq', 1);
        $wc_list = $TixianQueue->where($map_tixian)->limit($limit)->select();

        //循环处理给用户付款
        foreach ($wc_list as $list) {
            $WithdrawCash->startTrans(); //提现申请开启事务
            $Member->startTrans(); //会员表开始事务,用于表锁定

            $map['id'] = array('eq', $list['wcid']);

            //再次加载条件,避免执行过的状态再次提交
            $map['status'] = array('eq', 0);
            $map['tiqu_type'] = array('eq', 1); //仅处理微信提现

            $map_tixian['wcid'] = array('eq', $list['wcid']);

            $arm = new AccountRecordModel();
            $withdraw_info = $WithdrawCash->lock(true)->where($map)->find();
            if ($withdraw_info) {
                $uid = $withdraw_info['uid'];
                $map_member['id'] = array('eq', $uid);
                $member_info = $Member->lock(true)->where($map_member)->field('weixin,nickname')->find(); //锁行

                if ($member_info) {
                    $weixin_info = unserialize($member_info['weixin']);
                    if (!empty($weixin_info['openid'])) {
                        $openid = $weixin_info['openid'];
                    } else {
                        $wc_data = array(
                            'status' => 'F',
                            'failure_code' => '用户openid不存在',
                            'finish_time' => time(),
                        );
                        $affect1 = $WithdrawCash->where($map)->save($wc_data);

                        //返还提现金额
                        $affect = $arm->add($withdraw_info['uid'], Currency::Cash, CurrencyAction::CashWithdrawRefund, $withdraw_info['amount'], $arm->getRecordAttach(1, '管理员', ''), '现金提现退款（微信）');
                        $affect2 = $arm->add($withdraw_info['uid'], Currency::Cash, CurrencyAction::CashWithdrawFeeRefund, $withdraw_info['commission'], $arm->getRecordAttach(1, '管理员', ''), '提现手续费退款（微信）');
                        if ($affect1 === false || $affect === false || $affect2 === false) {
                            $WithdrawCash->rollback();
                            $Member->rollback();
                        } else {
                            $WithdrawCash->commit();
                            $Member->commit();
                        }
                        $TixianQueue->where($map_tixian)->delete();
                        continue;
                    }
                } else {
                    $wc_data = array(
                        'status' => 'F',
                        'failure_code' => '用户未绑定微信',
                        'finish_time' => time(),
                    );
                    $affect1 = $WithdrawCash->where($map)->save($wc_data);

                    //返还提现金额
                    $affect = $arm->add($withdraw_info['uid'], Currency::Cash, CurrencyAction::CashWithdrawRefund, $withdraw_info['amount'], $arm->getRecordAttach(1, '管理员', ''), '现金提现退款（微信）');
                    $affect2 = $arm->add($withdraw_info['uid'], Currency::Cash, CurrencyAction::CashWithdrawFeeRefund, $withdraw_info['commission'], $arm->getRecordAttach(1, '管理员', ''), '提现手续费退款（微信）');
                    if ($affect1 === false || $affect === false || $affect2 === false) {
                        $WithdrawCash->rollback();
                        $Member->rollback();
                    } else {
                        $WithdrawCash->commit();
                        $Member->commit();
                    }
                    $TixianQueue->where($map_tixian)->delete();
                    continue;
                }

                $data = array(
                    'mch_appid' => \WxPayConfig::APPID,
                    'mchid' => \WxPayConfig::MCHID,
                    'nonce_str' => \WxPayApi::getNonceStr(),
                    'partner_trade_no' => $withdraw_info['serial_num'],
                    'openid' => $openid,
                    'check_name' => 'FORCE_CHECK',
                    're_user_name' => $member_info['nickname'],
                    'amount' => $withdraw_info['amount'] * 100, //单位为分
                    'desc' => '用户提现',
                    'spbill_create_ip' => $_SERVER['REMOTE_ADDR']
                );

                //生成签名
                $WxPR = \WxPayResults::InitFromArray($data, true);
                $WxPR->SetSign();

                $data_xml = $WxPR->ToXml();

                $result = \WxPayApi::postXmlCurl($data_xml, $pay_url, true);
                $result = $WxPR->FromXml($result);

                if ($result) {
                    if ($result['return_code'] == 'SUCCESS') {
                        if ($result['result_code'] == 'SUCCESS') {
                            $wc_data = array(
                                'status' => 'S',
                                'ali_inner_serial_num' => $result['payment_no'],
                                'finish_time' => time(),//$result['payment_time'],
                            );
                            $affect = $WithdrawCash->where($map)->save($wc_data);
                            if ($affect === false) {
                                $WithdrawCash->rollback();
                                $Member->rollback();
                            } else {
                                $WithdrawCash->commit();
                                $Member->commit();
                            }
                            $TixianQueue->where($map_tixian)->delete();
                        } else {
                            $wc_data = array(
                                'status' => 'F',
                                'failure_code' => $result['err_code_des'],
                                'finish_time' => time(),
                            );
                            $affect1 = $WithdrawCash->where($map)->save($wc_data);

                            //返还提现金额
                            $affect = $arm->add($withdraw_info['uid'], Currency::Cash, CurrencyAction::CashWithdrawRefund, $withdraw_info['amount'], $arm->getRecordAttach(1, '管理员', ''), '现金提现退款（微信）');
                            $affect2 = $arm->add($withdraw_info['uid'], Currency::Cash, CurrencyAction::CashWithdrawFeeRefund, $withdraw_info['commission'], $arm->getRecordAttach(1, '管理员', ''), '提现手续费退款（微信）');
                            if ($affect1 === false || $affect === false || $affect2 === false) {
                                $WithdrawCash->rollback();
                                $Member->rollback();
                            } else {
                                $WithdrawCash->commit();
                                $Member->commit();
                            }
                            $TixianQueue->where($map_tixian)->delete();
                        }
                    } else {
                        $wc_data = array(
                            'status' => 'F',
                            'failure_code' => $result['return_msg'],
                            'finish_time' => time(),
                        );
                        $affect1 = $WithdrawCash->where($map)->save($wc_data);

                        //返还提现金额
                        $affect = $arm->add($withdraw_info['uid'], Currency::Cash, CurrencyAction::CashWithdrawRefund, $withdraw_info['amount'], $arm->getRecordAttach(1, '管理员', ''), '现金提现退款（微信）');
                        $affect2 = $arm->add($withdraw_info['uid'], Currency::Cash, CurrencyAction::CashWithdrawFeeRefund, $withdraw_info['commission'], $arm->getRecordAttach(1, '管理员', ''), '提现手续费退款（微信）');
                        if ($affect1 === false || $affect === false || $affect2 === false) {
                            $WithdrawCash->rollback();
                            $Member->rollback();
                        } else {
                            $WithdrawCash->commit();
                            $Member->commit();
                        }
                        $TixianQueue->where($map_tixian)->delete();
                    }
                }
            }

            sleep(1);
        }
    }


    /**
     * 对接 远程定时调用 执行银行卡提现操作 接口
     *
     * @建议频率: 30s
     */
    public function bankcardWithdraw()
    {
        //设置00:00-07:00禁止提现相关操作
        if (date('H') >= '00' && date('H') < '07') {
            exit;
        }

        $WithdrawCash = M('WithdrawCash');
        $Member = M('Member');
        $WithdrawBankCard = M('WithdrawBankcard');
        $TixianQueue = M('TixianQueue');
        $Bank = M('Bank');
        $limit = 10;

        $arm = new AccountRecordModel();

        //获取提现队列数据
        $map_tixian['tiq.type'] = array('eq', 2);
        $wc_list = $TixianQueue
            ->alias('tiq')
            ->join("join __WITHDRAW_CASH__ wic ON wic.id=tiq.wcid and wic.status='0'")
            ->where($map_tixian)
            ->field('tiq.*')
            ->limit($limit)
            ->select();

        //循环处理给用户付款
        foreach ($wc_list as $list) {
            M()->startTrans();

            $map['id'] = array('eq', $list['wcid']);

            //再次加载条件,避免执行过的状态再次提交
            $map['status'] = array('eq', 0);
            $map['tiqu_type'] = array('eq', 2); //仅处理银行卡提现

            //提现信息
            $withdraw_info = $WithdrawCash->lock(true)->where($map)->find();
            if (!$withdraw_info) {
                continue;
            }

            //用户信息
            $uid = $withdraw_info['uid'];
            $map_member['id'] = array('eq', $uid);
            $member_info = $Member->lock(true)->where($map_member)->field('nickname')->find();

            //银行卡信息
            $map_bankcard['uid'] = array('eq', $uid);
            $bankcard_info = $WithdrawBankCard->lock(true)->where($map_bankcard)->find();

            //联行号信息
            if ($withdraw_info['amount'] > 50000) {
                $bankcode = $bankcard_info['bankcode'];
            } else {
                $map_bank['bank'] = array('eq', $bankcard_info['inaccbank']);
                $bank_info = $Bank->where($map_bank)->field('code')->find();
                if (!$bank_info || empty($bank_info['code'])) {
                    $bankcode = $bankcard_info['bankcode'];
                } else {
                    $bankcode = $bank_info['code'];
                }
            }

            if (!$member_info || !$bankcard_info || (empty($bankcode) && !preg_match('/广发银行/', $bankcard_info['inaccbank']))) {
                $failure_code = !$member_info ? '用户会员表信息不存在' : (!$bankcard_info ? '用户银行卡信息不存在' : '联行号不能为空');

                $wc_data = array(
                    'status' => 'F',
                    'failure_code' => $failure_code,
                    'finish_time' => time(),
                );
                $cu1 = $WithdrawCash->where($map)->save($wc_data);

                //返还提现金额
                $cu2 = $arm->add($withdraw_info['uid'], Currency::Cash, CurrencyAction::CashWithdrawRefund, $withdraw_info['amount'], $arm->getRecordAttach(1, '管理员', ''), '现金提现退款（微信）');
                $cu3 = $arm->add($withdraw_info['uid'], Currency::Cash, CurrencyAction::CashWithdrawFeeRefund, $withdraw_info['commission'], $arm->getRecordAttach(1, '管理员', ''), '提现手续费退款（微信）');

                $cu4 = $TixianQueue->where('id=' . $list['id'])->delete();

                if ($cu1 === false || $cu2 === false || $cu3 === false || $cu4 === false) {
                    M()->rollback();
                } else {
                    M()->commit();
                }

                continue;
            }

            if (preg_match('/广发银行/', $bankcard_info['inaccbank'])) { //行内
                $data = array(
                    'tranCode' => '0011',
                    'entSeqNo' => $withdraw_info['serial_num'],
                    'traceNo' => '',
                    'outAccName' => '',
                    'outAcc' => \CgbPayConfig::outAcc,
                    'outAccName' => '',
                    'inAccName' => $bankcard_info['inaccname'],
                    'inAcc' => $bankcard_info['inacc'],
                    'inAccBank' => '',
                    'inAccAdd' => $bankcard_info['bank_pcd'],
                    'amount' => $withdraw_info['amount'],
                    'remark' => '行内转账至用户',
                    'date' => date('Ymd'),
                    'comment' => '此笔兑换由' . C('APP_TITLE') . '付款',
                    'creNo' => '',
                    'frBalance' => '',
                    'toBalance' => '',
                    'handleFee' => '',
                );
            } else { //行外
                $data = array(
                    'tranCode' => '0021',
                    'entSeqNo' => $withdraw_info['serial_num'],
                    'outAccName' => \CgbPayConfig::outAccName,
                    'outAcc' => \CgbPayConfig::outAcc,
                    'inAccName' => $bankcard_info['inaccname'],
                    'inAcc' => $bankcard_info['inacc'],
                    'inAccBank' => $bankcard_info['inaccbank'],
                    'inAccAdd' => $bankcard_info['bank_pcd'],
                    'amount' => $withdraw_info['amount'],
                    'remark' => '跨行转账至用户',
                    'comment' => '此笔兑换由' . C('APP_TITLE') . '付款',
                    'paymentBankid' => $bankcode,
                );
            }

            //提交数据给前置机
            $data_xml = $this->cgbPay->getXmlData($data);
            $result = $this->cgbPay->postXmlCurl($data_xml);
            $result = $this->cgbPay->getArrayData($result);

            if ($result) {
                //对返回错误代码为218(重复流水号)按已提交处理
                if ($result['retCode'] == '218') {
                    continue;
                }

                if ($result['retCode'] == '000') {
                    $wc_data = array(
                        'status' => 'W',
                        'ali_inner_serial_num' => $result['traceNo'],
                        'submit_flag' => time(),
                    );
                    $cu1 = $WithdrawCash->where($map)->save($wc_data);

                    if ($cu1 === false) {
                        M()->rollback();
                    } else {
                        M()->commit();
                    }
                } else {
                    $wc_data = array(
                        'status' => 'F',
                        'failure_code' => $result['retCode'],
                        'finish_time' => time(),
                    );
                    $cu1 = $WithdrawCash->where($map)->save($wc_data);

                    //返还提现金额
                    $cu2 = $arm->add($withdraw_info['uid'], Currency::Cash, CurrencyAction::CashWithdrawRefund, $withdraw_info['amount'], $arm->getRecordAttach(1, '管理员', ''), '现金提现退款（微信）');
                    $cu3 = $arm->add($withdraw_info['uid'], Currency::Cash, CurrencyAction::CashWithdrawFeeRefund, $withdraw_info['commission'], $arm->getRecordAttach(1, '管理员', ''), '提现手续费退款（微信）');

                    $cu4 = $TixianQueue->where('id=' . $list['id'])->delete();

                    if ($cu1 === false || $cu2 === false || $cu3 === false || $cu4 === false) {
                        M()->rollback();
                    } else {
                        M()->commit();
                    }
                }
            }

            sleep(1);
        }
    }

    /**
     * 对接 远程定时调用 查询银行卡提现完成状态  回调接口
     *
     * @建议频率: 180s
     */
    public function checkBankWithdrawStatus()
    {
        $WithdrawCash = M('WithdrawCash');
        $TixianQueue = M('TixianQueue');
        $limit = 10;

        //获取提现队列数据
        $map_tixian['tiq.type'] = array('eq', 2);
        $map_tixian['_string'] = " tiq.id>=(select floor(rand()*(select max(id) from zc_tixian_queue))) ";
        $wc_list = $TixianQueue
            ->alias('tiq')
            ->join("join __WITHDRAW_CASH__ wic ON wic.id=tiq.wcid and wic.status='W'")
            ->where($map_tixian)
            ->field('tiq.*')
            ->order('tiq.id asc')
            ->limit($limit)
            ->select();

        //循环查询银行卡提现完成状态
        foreach ($wc_list as $list) {
            M()->startTrans();

            //提现信息
            $map['id'] = array('eq', $list['wcid']);
            $withdraw_info = $WithdrawCash->lock(true)->where($map)->find();
            if (!$withdraw_info) {
                continue;
            }

            //查询时间
            $origEntdate = date('Ymd', $withdraw_info['submit_flag']);

            //修复2017-05-21号提交的时间差的问题
            $origEntdate = $origEntdate == '20170521' ? '20170522' : $origEntdate;

            //查询银行处理状态
            $data = array(
                'tranCode' => '1004',
                'entSeqNo' => $withdraw_info['serial_num'],
                'origEntseqno' => $withdraw_info['serial_num'],
                'origEntdate' => $origEntdate,
            );
            $data_xml = $this->cgbPay->getXmlData($data);
            $result = $this->cgbPay->postXmlCurl($data_xml);
            $result = $this->cgbPay->getArrayData($result);

            if ($result) {
                $bankStatus = $result['hostStatus']; //兑换处理状态([行内]6:主机兑换成功,7:主机兑换失败,8:状态未知,没有收到后台系统返回的应答,[跨行]A:支付系统正在处理,B:处理成功,C:处理失败,D:状态未知,E:大额查证)
                $payRemarks = $result['ERRORREASON']; //失败原因

                //初始化变量
                $wc_data = array(
                    'finish_time' => time(),
                );
                $return_amount = false;
                $cu1 = $cu2 = $cu3 = $cu4 = true;

                //map条件
                $map_member['id'] = array('eq', $withdraw_info['uid']);
                $map_bonus['serial_num'] = array('eq', $withdraw_info['serial_num']);
                $map_tx['id'] = array('eq', $list['id']);

                //处理相关数据
                if ($bankStatus == '6' || $bankStatus == 'B') { //成功
                    $wc_data['status'] = 'S';
                } elseif ($bankStatus == '7' || $bankStatus == 'C') { //失败
                    //对返回错误代码为218(重复流水号)进行按已兑换成功处理
                    if ($payRemarks == '218') {
                        $wc_data['status'] = 'S';
                    } else {
                        $wc_data['status'] = 'F';
                        $wc_data['failure_code'] = $payRemarks;

                        $return_amount = true;
                    }
                } else { //处理中或未知
                    $map_tx = false;
                    $wc_data = false;
                }

                if (is_array($wc_data)) {
                    $cu1 = $WithdrawCash->where($map)->save($wc_data);
                }

                //退钱
                if ($return_amount > 0) {
                    $arm = new AccountRecordModel();
                    $cu2 = $arm->add($withdraw_info['uid'], Currency::Cash, CurrencyAction::CashWithdrawRefund, $withdraw_info['amount'], $arm->getRecordAttach(1, '平台'), '现金提现退款');
                    $cu3 = $arm->add($withdraw_info['uid'], Currency::Cash, CurrencyAction::CashWithdrawFeeRefund, $withdraw_info['commission'], $arm->getRecordAttach(1, '平台'), '现金提现手续费退款');
                }

                if ($map_tx) {
                    $cu4 = $TixianQueue->where($map_tx)->delete();
                }

                if ($cu1 === false || $cu2 === false || $cu3 === false || $cu4 === false) {
                    M()->rollback();
                    continue;
                } else {
                    M()->commit();
                }
            }

            sleep(1);
        }
    }

    /**
     * 对接 远程定时调用 执行支付宝提现队列操作 接口
     *
     * @建议频率: 30s
     */
    public function aliDepositQueue()
    {
        $AliPay = new \AliPay();

        $TixianQueue = M('TixianQueue');
        $WithdrawCash = M('WithdrawCash');
        $Member = M('Member');
        $limit = 10;

        $AccountModel = new AccountModel();

        //获取提现队列数据
        $map_tixian['type'] = array('eq', 0);
        $wc_list = $TixianQueue->where($map_tixian)->limit($limit)->select();

        //循环处理给用户付款
        foreach ($wc_list as $list) {
            M()->startTrans();

            $map['id'] = array('eq', $list['wcid']);

            //再次加载条件,避免执行过的状态再次提交
            $map['status'] = array('eq', 0);
            $map['tiqu_type'] = array('eq', 0); //仅处理支付宝提现

            $map_tixian['wcid'] = array('eq', $list['wcid']);

            $arm = new AccountRecordModel();
            $withdraw_info = $WithdrawCash->lock(true)->where($map)->find();
            if ($withdraw_info) {
                //首先对提现订单status=W的订单进行再次查询确认
                if ($withdraw_info['status'] == 'W') {
                    $data_query = [
                        'out_biz_no' => $withdraw_info['serial_num'],
                        'order_id' => $withdraw_info['ali_inner_serial_num']
                    ];
                    $AliPay->setData($data_query);
                    $result = $AliPay->orderQuery();
                    $result = $result->alipay_fund_trans_order_query_response;

                    if ($result->status == 'SUCCESS') {
                        $wc_data = [
                            'status' => 'S',
                            'finish_time' => time(),
                        ];
                        $affect = $WithdrawCash->where($map)->save($wc_data);
                        $affect2 = $TixianQueue->where($map_tixian)->delete();

                        if ($affect === false || $affect2 === false) {
                            M()->rollback();
                        } else {
                            M()->commit();
                        }
                        continue;
                    } elseif ($result->status == 'INIT' || $result->status == 'DEALING') { //INIT:等待处理,DEALING:处理中,UNKNOWN:状态未知
                        continue;
                    } elseif ($result->status == 'FAIL' || $result->status == 'REFUND') { //FAIL:失败,REFUND:退票
                        $wc_data = [
                            'status' => 'F',
                            'failure_code' => $result->fail_reason . ':' . $result->error_code . ':[1]',
                            'finish_time' => time(),
                        ];
                        $affect1 = $WithdrawCash->where($map)->save($wc_data);

                        //返还提现金额
                        $affect = $arm->add($withdraw_info['uid'], Currency::Cash, CurrencyAction::CashWithdrawRefund, $withdraw_info['amount'], $arm->getRecordAttach(1, '管理员', ''), '现金提现退款（支付宝）');
                        $affect2 = $arm->add($withdraw_info['uid'], Currency::Cash, CurrencyAction::CashWithdrawFeeRefund, $withdraw_info['commission'], $arm->getRecordAttach(1, '管理员', ''), '提现手续费退款（支付宝）');

                        $affect3 = $TixianQueue->where($map_tixian)->delete();

                        if ($affect1 === false || $affect === false || $affect2 === false || $affect3 === false) {
                            M()->rollback();
                        } else {
                            M()->commit();
                        }
                        continue;
                    } else { //包含$result['status']==UNKNOWN的情况和其他非上述状态的情况:再次发起转账

                    }
                }

                $uid = $withdraw_info['uid'];
                $map_member['mem.id'] = array('eq', $uid);
                $member_info = $Member
                    ->alias('mem')
                    ->join('join __USER_AFFILIATE__ aff ON aff.user_id=mem.id')
                    ->where($map_member)
                    ->field('mem.nickname,aff.alipay_account')
                    ->find();

                if (!$member_info || empty($member_info['alipay_account'])) {
                    $wc_data = array(
                        'status' => 'F',
                        'failure_code' => '用户未绑定支付宝账号[2]',
                        'finish_time' => time(),
                    );
                    $affect1 = $WithdrawCash->where($map)->save($wc_data);

                    //返还提现金额
                    $affect = $arm->add($withdraw_info['uid'], Currency::Cash, CurrencyAction::CashWithdrawRefund, $withdraw_info['amount'], $arm->getRecordAttach(1, '管理员', ''), '现金提现退款（支付宝）');
                    $affect2 = $arm->add($withdraw_info['uid'], Currency::Cash, CurrencyAction::CashWithdrawFeeRefund, $withdraw_info['commission'], $arm->getRecordAttach(1, '管理员', ''), '提现手续费退款（支付宝）');

                    $affect3 = $TixianQueue->where($map_tixian)->delete();

                    if ($affect1 === false || $affect === false || $affect2 === false || $affect3 === false) {
                        M()->rollback();
                    } else {
                        M()->commit();
                    }
                    continue;
                }

                //检测用户绑定支付宝账号的类型(16位纯数字为支付宝ID,否则为支付宝登录账号)
                $payee_type = (strlen($member_info['alipay_account']) == 16 && validateExtend($member_info['alipay_account'], 'NUMBER')) ? 'ALIPAY_USERID' : 'ALIPAY_LOGONID';

                //组装数据
                $data = array(
                    'out_biz_no' => $withdraw_info['serial_num'],
                    'payee_type' => $payee_type,
                    'payee_account' => $member_info['alipay_account'],
                    'amount' => $withdraw_info['amount'], //单位为元
                    'payer_show_name' => C('APP_TITLE'),
                    'payee_real_name' => $member_info['nickname'],
                    'remark' => "用户支付宝提现"
                );

                $AliPay->setData($data);
                $result = $AliPay->tixian();
                $result = $result->alipay_fund_trans_toaccount_transfer_response;

                // 记录调试日志
//                DebugLogModel::instance()->add($result, $data);

                if (!empty($result->code)) {
                    if ($result->code == 10000) {
                        $wc_data = array(
                            'status' => 'S',
                            'ali_inner_serial_num' => $result->order_id,
                            'finish_time' => time(),
                        );
                        $affect = $WithdrawCash->where($map)->save($wc_data);
                        $affect2 = $TixianQueue->where($map_tixian)->delete();

                        if ($affect === false || $affect2 === false) {
                            M()->rollback();
                        } else {
                            M()->commit();
                        }
                    } else {
                        if ($result->code == 20000 || ($result->code == 40004 && $result->sub_code != 'PAYEE_USER_INFO_ERROR') || $result->sub_code == 'SYSTEM_ERROR') { //对掉单数据只更新支付宝流水号,待下次执行该任务时再次查询确认
                            $wc_data = [
                                'status' => 'W',
                                'ali_inner_serial_num' => $result->order_id
                            ];
                            $affect = $WithdrawCash->where($map)->save($wc_data);

                            if ($affect === false) {
                                M()->rollback();
                            } else {
                                M()->commit();
                            }
                        } else {
                            $wc_data = array(
                                'status' => 'F',
                                'failure_code' => $result->sub_msg . ':' . $result->msg . ':' . $result->code . ':' . $result->sub_code . ':[3]',
                                'finish_time' => time(),
                            );
                            $affect1 = $WithdrawCash->where($map)->save($wc_data);

                            //返还提现金额
                            $affect = $arm->add($withdraw_info['uid'], Currency::Cash, CurrencyAction::CashWithdrawRefund, $withdraw_info['amount'], $arm->getRecordAttach(1, '管理员', ''), '现金提现退款（支付宝）');
                            $affect2 = $arm->add($withdraw_info['uid'], Currency::Cash, CurrencyAction::CashWithdrawFeeRefund, $withdraw_info['commission'], $arm->getRecordAttach(1, '管理员', ''), '提现手续费退款（支付宝）');

                            $affect3 = $TixianQueue->where($map_tixian)->delete();

                            if ($affect1 === false || $affect === false || $affect2 === false || $affect3 === false) {
                                M()->rollback();
                            } else {
                                M()->commit();
                            }
                        }
                    }
                }
            }

            sleep(1);
        }
    }

    public function __destruct()
    {
        parent::__destruct();

        file_put_contents($this->log_file, date('Y-m-d H:i:s') . "[ip:{$this->api_remote_ip}][end]" . PHP_EOL, FILE_APPEND);
    }

}

?>