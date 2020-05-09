<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 异步调用 (此类中的方法均可直接调用,不会受登录权限验证)
// +----------------------------------------------------------------------
namespace Admin\Controller;

use Common\Controller\AuthController;
use V4\Model\AccountFinanceModel;
use V4\Model\Currency;
use V4\Model\CurrencyAction;
use V4\Model\Tag;
use V4\Model\AwardFinanceModel;
use Common\Model\Sys\SettingModel;
use V4\Model\GjjModel;
use V4\Model\WalletModel;

class AjaxController extends AuthController
{

    public function __construct()
    {
        parent::__construct();

        layout(false);
    }

    /**
     * 地区联动
     */
    public function areaSelect()
    {
        $Province = M('Province');
        $City = M('City');
        $District = M('District');

        $return = array('error' => '', 'data' => '');

        $type = $this->post['type'];
        $type = empty($type) ? '0' : $type;
        $pid = $this->post['pid'];
        $cid = $this->post['cid'];

        switch ($type) {
            case '0': //省
                $return['data'] = $Province->select();
                break;
            case '1': //市
                if (!validateExtend($pid, 'NUMBER') && !validateExtend($pid, 'CHS')) {
                    $return['error'] = '参数格式有误';
                    $this->ajaxReturn($return);
                }

                //监测传入的值如果是名称则获取其ID
                if (!validateExtend($pid, 'NUMBER')) {
                    $map_province['province'] = array('eq', $pid);
                    $province_info = $Province->where($map_province)->field('pid')->find();
                    if ($province_info) {
                        $map['pid'] = array('eq', $province_info['pid']);
                    } else {
                        $return['error'] = '该省份不存在';
                        $this->ajaxReturn($return);
                    }
                } else {
                    $map['pid'] = array('eq', $pid);
                }

                $return['data'] = $City->where($map)->select();
                break;
            case '2': //区
                if (!validateExtend($cid, 'NUMBER') && !validateExtend($cid, 'CHS')) {
                    $return['error'] = '参数格式有误';
                    $this->ajaxReturn($return);
                }

                //监测传入的值如果是名称则获取其ID
                if (!validateExtend($cid, 'NUMBER')) {
                    $map_city['city'] = array('eq', $cid);
                    $city_info = $City->where($map_city)->field('cid')->find();
                    if ($city_info) {
                        $map['cid'] = array('eq', $city_info['cid']);
                    } else {
                        $return['error'] = '该城市不存在';
                        $this->ajaxReturn($return);
                    }
                } else {
                    $map['cid'] = array('eq', $cid);
                }

                $return['data'] = $District->where($map)->select();
                break;
        }

        $this->ajaxReturn($return);
        exit;
    }

    /**
     * 分类联动
     */
    public function categorySelect()
    {
        $FirstMenu = M('FirstMenu');
        $SecondMenu = M('SecondMenu');

        $return = array('error' => '', 'data' => '');

        $type = $this->post['type'];
        $type = empty($type) ? '0' : $type;
        $fid = $this->post['fid'];
        $sid = $this->post['sid'];

        switch ($type) {
            case '0': //一级菜单
                $return['data'] = $FirstMenu->order('fm_order desc,fm_id desc')->select();
                break;
            case '1': //二级菜单
                if (!validateExtend($fid, 'NUMBER') && !validateExtend($fid, 'CHS')) {
                    $return['error'] = '参数格式有误';
                    $this->ajaxReturn($return);
                }

                //监测传入的值如果是名称则获取其ID
                if (!validateExtend($fid, 'NUMBER')) {
                    $map_first_menu['fm_name'] = array('eq', $fid);
                    $first_menu_info = $FirstMenu->where($map_first_menu)->field('fm_id')->find();
                    if ($first_menu_info) {
                        $map['fm_id'] = array('eq', $first_menu_info['fm_id']);
                    } else {
                        $return['error'] = '该分类不存在';
                        $this->ajaxReturn($return);
                    }
                } else {
                    $map['fm_id'] = array('eq', $fid);
                }

                $return['data'] = $SecondMenu->where($map)->order('fm_order desc,sm_id desc')->select();
                break;
        }

        $this->ajaxReturn($return);
        exit;
    }

    /**
     * 富文本编辑器文件上传
     *
     * @param $path 路径名(相对于Uploads下的文件夹名)
     */
    public function upload()
    {
        $path = $this->get['path'];

        if (!empty($path) && validateExtend($path, 'CHS')) {
            echo 'error|路径名格式有误';
        }

        $upload_config = array(
            'file' => 'multi',
            'exts' => array('bmp', 'jpg', 'png', 'gif', 'jpeg', 'doc', 'xls'),
            'size' => 10240000, //10M
        );
        if (!empty($path)) {
            $upload_config['path'] = $path . '/' . date('Ymd');
        }

        $Upload = new \Common\Controller\UploadController($upload_config);
        $upload_info = $Upload->upload();
        if (!empty($upload_info['error'])) {
            echo 'error|' . $upload_info['error'];
        } else {
            $data = $upload_info['data'];
            if (isset($data['url'])) { //单文件
                $url = $data['url'];
            } else { //多文件,暂时采用英文逗号拼接
                foreach ($data as $k => $v) {
                    $url[] = $v['url'];
                }
                $url = implode(',', $url);
            }
            echo $url;
        }

        exit;
    }

    /**
     * 账号添加：专供外部调用
     *
     * @param $data array array('uid'=>'','group_id'=>array(), 'type'=>'agent/service/merchant')
     */
    public function memberAddAsyn($data = '')
    {
        $AuthMember = D("Admin/Manager");
        $Member = M('Member');
        $AuthGroupAccess = D("Admin/AuthGroupAccess");

        C('TOKEN_ON', false);

        if (empty($data)) {
            return false;
        }

        if (!validateExtend($data['uid'], 'NUMBER')) {
            return false;
        }

        //检测会员表中是否存在此账号,并拉取对应账号的密码和昵称
        $map_member['id'] = array('eq', $data['uid']);
        $member_info = $Member->where($map_member)->field('password,nickname,loginname,username')->find();
        if ($member_info) {
            $data['nickname'] = $member_info['nickname'];
            $data['loginname'] = $member_info['loginname'];
        } else {
            return false;
        }

        $group_access_group_id = $data['group_id'];
        unset($data['group_id']);

        //获取账号已有角色列表,然后删除服务代理 / 商家 角色,再进行重新分配;但保留 非 区域合伙人 / 商家  外的其他角色身份;
        $role_must_list = C('ROLE_MUST_LIST');
        $role_name = '';
        switch ($data['type']) {
            case 'service':
                unset($role_must_list['merchant']);
                $role_must_list = array_values($role_must_list);
                $role_name = '服务中心';
                break;
            case 'agent':
                unset($role_must_list['merchant']);
                $role_must_list = array_values($role_must_list);
                $role_name = '区域合伙人';
                break;
            case 'merchant':
                $role_must_list = array($role_must_list['merchant']);
                $role_name = '商家';
                break;
            default:
                $role_must_list = array_values($role_must_list);
                $role_name = '区域合伙人+商家';
        }

        //如果存在账户,则尝试更新角色
        $map['mem.uid'] = array('eq', $data['uid']);
        $auth_member_info = $AuthMember->getMemberList($map, false, 'mem.id');
        if ($auth_member_info) {

            $map_group_access['uid'] = array('eq', $auth_member_info['id']);
            $auth_group_access_list = $AuthGroupAccess->getGroupAccessList('group_id', $map_group_access, true);
            foreach ($auth_group_access_list as $k => $group) {
                if (!in_array($group['group_id'], $role_must_list)) {
                    $group_access_group_id[] = $group['group_id'];
                }
            }
            $AuthGroupAccess->delAccess(array(), $auth_member_info['id']);
            $AuthGroupAccess->addAccess($group_access_group_id, $auth_member_info['id']);

            $this->logWrite("同步添加后台管理员用户{$member_info['username']}[{$data['loginname']}][{$member_info['nickname']}]的[{$role_name}]角色");

            return true;

        }

        if (!$AuthMember->create($data)) {
            return false;
        } else {
            $id = $AuthMember->add();

            foreach ($group_access_group_id as $k => $v) {
                $AuthGroupAccess->addAccess($group_access_group_id, $id);
                $AuthGroupAccess->delAccess($group_access_group_id, $id);
            }

            $this->logWrite("同步添加后台管理员用户{$data['loginname']}[{$member_info['nickname']}]的[{$role_name}]角色");
        }

        return true;
    }

    /**
     * 账户删除:专用外部调用
     *
     * @param $member_id
     * @param $type agent/service/merchant 要删除的类型:区域合伙人/商家
     */
    public function memberDeleteAsyn($member_id, $type)
    {
        $AuthMember = D("Admin/Manager");
        $AuthGroupAccess = D("Admin/AuthGroupAccess");

        if (empty($member_id)) {
            return false;
        }

        if (empty($type)) {
            return false;
        }

        //检测用户是否已分配有管理员账户
        $map_group_access_tmp['man.uid'] = array('eq', $member_id);
        $group_access_info = M('AuthGroupAccess')
            ->alias('aug')
            ->join('JOIN __MANAGER__ man ON man.id=aug.uid')
            ->where($map_group_access_tmp)
            ->find();
        if (!$group_access_info) {
            return true;
        }

        $map['mem.uid'] = array('eq', $member_id);
        $member_info = $AuthMember->getMemberList($map);
        $member_id = $member_info['id'];
        if ($member_id == 1) {
            return false;
        }

        //获取账号已有角色列表,然后删除 区域合伙人/商家 角色,再进行重新分配;但保留 非区域合伙人/商家 外的其他角色身份;
        $group_access_group_id = array();
        $role_must_list = C('ROLE_MUST_LIST');
        $role_name = '';
        switch ($type) {
            case 'agent':
                $role_must_list = array($role_must_list['agent']);
                $role_name = '区域合伙人';
                break;
            case 'service':
                $role_must_list = array($role_must_list['service']);
                $role_name = '服务中心';
                break;
            case 'merchant':
                $role_must_list = array($role_must_list['merchant']);
                $role_name = '商家';
                break;
            default:
                $role_must_list = array_values($role_must_list);
                $role_name = '区域合伙人+商家';
        }

        $map_group_access['uid'] = array('eq', $member_id);
        $auth_group_access_list = $AuthGroupAccess->getGroupAccessList('group_id', $map_group_access, true);
        foreach ($auth_group_access_list as $k => $group) {
            if (!in_array($group['group_id'], $role_must_list)) {
                $group_access_group_id[] = $group['group_id'];
            }
        }

        if (count($group_access_group_id) == 0) {
            if (!$AuthMember->where('id=' . $member_id)->delete()) {
                return false;
            }
        }

        $AuthGroupAccess->delAccess($group_access_group_id, $member_id);
        $this->logWrite("同步删除后台管理员用户{$member_info['loginname']}[{$member_info['nickname']}]的[{$role_name}]角色");

        return true;
    }

    /**
     * 获取指定用户信息的商家信息
     *
     * @param $key uid:用户ID / username:用户名
     * 由于昵称存在同名的可能性,故在此不作为筛选条件
     */
    public function getMemberStoreInfo()
    {
        $Store = M('Store');

        $return = array('error' => '', 'data' => '');

        $key = isset($this->post['key']) ? $this->post['key'] : false;

        $map = array();

        if (!validateExtend($key, 'NUMBER') && !validateExtend($key, 'USERNAME')) {
            $return['error'] = '缺少参数或参数格式有误';
            $this->ajaxReturn($return, 'JSON');
        }

        $map1['sto.uid'] = array('eq', $key);
        $map1['mem.loginname'] = array('eq', $key);
        $map1['mem.username'] = array('eq', $key);
        $map1['_logic'] = 'or';
        $map['_complex'] = $map1;
        $map['_string'] = " sto.manage_status=1 and sto.status=0 ";

        $info = $Store
            ->alias('sto')
            ->join('join __MEMBER__ mem ON mem.id=sto.uid')
            ->where($map)
            ->field('sto.store_name')
            ->find();
        if (!$info) {
            $return['error'] = '无相关信息';
            $this->ajaxReturn($return, 'JSON');
        }
        $return['data'] = $info;

        $this->ajaxReturn($return, 'JSON');
    }

    /**
     * 获取当前 服务/区域合伙人审核通过后 赠送/扣除积分方法配置参数
     */
    public function getServiceCompanyPointsConfig()
    {
        $Parameter = M('Parameter', 'g_');

        $return = array('error' => '', 'data' => '');

        $uid = $this->post['uid'];

        if (!empty($uid) && !validateExtend($uid, 'NUMBER')) {
            $return['error'] = '参数格式有误';
            $this->ajaxReturn($return);
        }

        //登录用户才有权限获取
        $admin_mid = session('admin_mid');
        if (empty($admin_mid)) {
            $return['error'] = '未登录用户无权获取相关信息';
            $this->ajaxReturn($return);
        }

        //获取增/减积分方案配置参数
        $config = C('PARAMETER_CONFIG.POINTS');
        if (!is_array($config) || count($config) == 0) {
            $return['error'] = '未获取到相关信息';
            $this->ajaxReturn($return);
        }

        //获取赠送积分数额配置参数
        $config_info = $Parameter->where('id=1')->field('service_points,company_points')->find();
        if (!$config_info) {
            $return['error'] = '相关配置参数不存在';
            $this->ajaxReturn($return);
        }

        $config = array_merge($config, $config_info);

        $return['data'] = $config;
        $this->ajaxReturn($return);
    }

    /**
     * 获取微信企业支付指定单号的提现明细
     * type: POST
     *
     * @param string $wcid 提现ID (用于查询提现)
     */
    public function getWxPayDetail()
    {
        $return = array('error' => '', 'data' => '');

        Vendor("WxPay.WxPay#Api"); //微信支付基础组件

        $wcid = $this->post['wcid'];

        if (!validateExtend($wcid, 'NUMBER')) {
            $return['error'] = '提现ID参数有误';
            $this->ajaxReturn($return);
        }

        //获取提现编号
        $serial_num = M('WithdrawCash')->where('id=' . $wcid)->getField('serial_num');
        if (!$serial_num) {
            $return['error'] = '提现信息异常';
            $this->ajaxReturn($return);
        }

        //企业支付查询接口
        $pay_url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/gettransferinfo';

        $data = array(
            'appid' => \WxPayConfig::APPID,
            'mch_id' => \WxPayConfig::MCHID,
            'nonce_str' => \WxPayApi::getNonceStr(),
            'partner_trade_no' => $serial_num,
        );

        //生成签名
        $WxPR = \WxPayResults::InitFromArray($data, true);
        $WxPR->SetSign();

        $data_xml = $WxPR->ToXml();

        try {
            $result = \WxPayApi::postXmlCurl($data_xml, $pay_url, true, 10);
        } catch (\WxPayException $e) {
            $return['error'] = $e->errorMessage();
            $this->ajaxReturn($return);
        }

        $result = $WxPR->FromXml($result);

        //过滤掉敏感信息
        unset($result['mch_id']);
        unset($result['openid']);

        //格式化数据
        $result['payment_amount'] = sprintf('%.2f', $result['payment_amount'] / 100);

        $return['data'] = $result;
        $this->ajaxReturn($return);
    }

    //获取微信提现完成状态
    public function getWxDepositStatus()
    {
        if (session('WxDepositSuccess') == 1) {
            session('WxDepositSuccess', null);
            exit('1');
        }
        exit('0');
    }

    //获取银行卡提现完成状态
    public function getBankDepositSuccess()
    {
        if (session('BankDepositSuccess') == 1) {
            session('BankDepositSuccess', null);
            exit('1');
        }
        exit('0');
    }

    /**
     * 获取微信兑换指定单号的兑换明细
     * type: POST
     *
     * @param string $serial_num 订单ID (用于直接查询系统流水号)
     */
    public function getWxBuyDetail()
    {
        $return = array('error' => '', 'data' => '');

        Vendor("WxPay.WxPay#Api"); //微信支付基础组件

        $serial_num = $this->post['serial_num'];

        if (empty($serial_num)) {
            $return['error'] = '参数不能为空';
            $this->ajaxReturn($return);
        }

        //兑换查询接口
        $pay_url = 'https://api.mch.weixin.qq.com/pay/orderquery';

        $data = array(
            'appid' => \WxPayConfig::APPID,
            'mch_id' => \WxPayConfig::MCHID,
            'out_trade_no' => $serial_num,
            'nonce_str' => \WxPayApi::getNonceStr(),
        );

        //生成签名
        $WxPR = \WxPayResults::InitFromArray($data, true);
        $WxPR->SetSign();

        $data_xml = $WxPR->ToXml();

        try {
            $result = \WxPayApi::postXmlCurl($data_xml, $pay_url, true, 10);
        } catch (\WxPayException $e) {
            $return['error'] = $e->errorMessage();
            $this->ajaxReturn($return);
        }

        $result = $WxPR->FromXml($result);

        //过滤掉敏感信息
        unset($result['mch_id']);
        unset($result['openid']);

        $trade_state = '';
        switch ($result['trade_state']) {
            case 'SUCCESS':
                $trade_state = '支付成功';
                break;
            case 'REFUND':
                $trade_state = '转入退款';
                break;
            case 'NOTPAY':
                $trade_state = '未支付';
                break;
            case 'CLOSED':
                $trade_state = '已关闭';
                break;
            case 'REVOKED':
                $trade_state = '已撤销(刷卡支付)';
                break;
            case 'USERPAYING':
                $trade_state = '用户支付中';
                break;
            case 'PAYERROR':
                $trade_state = '支付失败(其他原因银行返回失败)';
                break;
        }
        $result['trade_state'] = $trade_state;

        //格式化数据
        $result['total_fee'] = sprintf('%.2f', $result['total_fee'] / 100);

        $return['data'] = $result;
        $this->ajaxReturn($return);
    }

    /**
     * 获取银行卡提现错误代码对应中文说明
     */
    public function getBankErrorCodeDescription()
    {
        $BankCgbErrorCode = M('BankCgbErrorCode');

        $error_code = $this->post['error_code'];

        if (empty($error_code)) {
            exit('错误码不能为空');
        }

        $map['code'] = array('eq', $error_code);
        $info = $BankCgbErrorCode->where($map)->find();
        if (!$info) {
            exit($error_code);
        } else {
            exit($info['description']);
        }
    }

    /**
     * 生成今日当前拨比统计数据
     */
    public function todayBonusCreate()
    {
        //更新今日拨比统计
        $msg = M()->query(C('ALIYUN_TDDL_MASTER') . "call pro_ratio_today(@msg)");
        $msg = $msg[0]['msg'] == '1' ? '已生成今日当前拨比统计数据' : '生成今日当前拨比统计数据失败,请稍后重试';

        $this->logWrite("生成今日当前拨比统计数据");
        exit($msg);
    }

    /**
     * 获取拨比的支出明细
     *
     * @param int $tag 明细标签
     */
    public function getRatioDetail()
    {
        $AccountFinanceModel = new AccountFinanceModel();

        $tag = $this->post['tag'];

        if (!validateExtend($tag, 'NUMBER')) {
            exit('参数格式有误');
        }

        $AwardFinanceModel = new AwardFinanceModel();
        $fields = [
            'income_tag as tag',
            'income_cash_dutyconsume `dutyconsume`',
            'income_cash_repeat `repeat`',
            'income_cash_merchant `marchant`',
            'income_cash_uniondelivery `uniondelivery`',
            'income_cash_marketsubsidy `marketsubsidy`',
            'income_cash_companysubsidy `companysubsidy`',
            'income_cash_viewad `viewad`',
            'income_cash_shake `shake`',
            'income_cash_freesubsidy `freesubsidy`',
            'income_cash_bonus `bonus`'
        ];
        $data = $AwardFinanceModel->getPageList(false, 1, $fields, 'income_tag=' . $tag);
        $list = $data['list'][0];

        $this->assign('statistics_info', $list);
        $html = $this->fetch();

        exit($html);
    }

    /**
     * 获取微信的收入明细
     *
     * @param int $tag 明细标签
     */
    public function getWxDetail()
    {
        $Orders = M('Orders');

        $tag = $this->post['tag'];
        $tag = 20171208;

        if (!validateExtend($tag, 'NUMBER')) {
            exit('参数格式有误');
        }

        $result = ['shop' => 0, 'buy' => 0, 'rechange' => 0, 'apply' => 0];
        $list = $Orders
            ->alias('ord')
            ->join('left join __ORDER_AFFILIATE__ aff ON aff.order_id=ord.id')
            ->field('ord.exchangeway,sum((ord.amount-ifnull(aff.affiliate_pay,0))) score')
            ->where("ord.order_status=4 and ord.amount_type=5 and from_unixtime(pay_time,'%Y%m%d')={$tag}")
            ->group('ord.exchangeway')
            ->select();

        foreach ($list as $k => $v) {
            switch ($v['exchangeway']) {
                case '1':
                    $result['shop'] = $v['score'];
                    break;
                case '2':
                    $result['buy'] = $v['score'];
                    break;
                case '3':
                    $result['recharge'] = $v['score'];
                    break;
                case '4':
                    $result['apply'] = $v['score'];
                    break;
            }
        }
        $this->assign('list', $result);

        $html = $this->fetch();

        exit($html);
    }

    /**
     * 获取指定板块币种比例
     *
     * @param int block_id 板块ID
     */
    public function getBlockPercent()
    {
        $data = ['error' => '', 'data' => ''];

        $block_id = $this->post['block_id'];

        if (!validateExtend($block_id, 'NUMBER')) {
            $data['error'] = '参数格式有误';
        } else {
            $info = M('Block')->where('block_id=' . $block_id)->find();
            if (!$info) {
                $data['error'] = '板块信息不存在';
            } else {
                $data['data'] = [
                    'affiliate_credits' => $info['block_credits_percent'],
                    'affiliate_supply' => $info['block_supply_percent'],
                    'affiliate_goldcoin' => $info['block_goldcoin_percent'],
                    'affiliate_colorcoin' => $info['block_colorcoin_percent'],
                    'affiliate_freight' => $info['block_freight']
                ];
            }
        }

        exit(json_encode($data));
    }

    /**
     * 获取指定订单详情
     *
     * @param int $id 订单ID
     */
    public function getOrderDetails()
    {
        $data = ['error' => '', 'data' => ''];

        $id = $this->post['id'];

        if (!validateExtend($id, 'NUMBER')) {
            $data['error'] = '参数格式有误';
        } else {
            $info = M('OrderAffiliate')
                ->alias('aff')
                ->join('join __ORDERS__ ord ON ord.id=aff.order_id')
                ->field('aff.*,ord.amount, ord.amount_type,ord.order_status')
                ->where('order_id=' . $id)
                ->find();
            $this->assign('info', $info);

            $product = M('OrderProduct')
                ->alias('opr')
                ->join('join __PRODUCT__ pro ON pro.id=opr.product_id')
                ->join('join __PRODUCT_AFFILIATE__ aff ON aff.product_id=opr.product_id')
                ->join('join __BLOCK__ blo ON blo.block_id=aff.block_id')
                ->field('opr.*,pro.name product_name,blo.block_name')
                ->where('opr.order_id=' . $id)
                ->order('opr.oproduct_id asc')
                ->select();
            $this->assign('product', $product);

            $data['data'] = $this->fetch();
        }

        exit(json_encode($data));
    }
    
    /**
     * 获取订单商家备注
     *
     * @param int $id 订单ID
     */
    public function getOrderRemark() {
    	$data = ['error' => '', 'data' => ''];
    
    	$id = $this->post['id'];
    
    	if (!validateExtend($id, 'NUMBER')) {
    		$data['error'] = '参数格式有误';
    	} else {
    		$info = M('Orders')->field('id,merchant_remark')->where('id='.$id)->find();
	    	$this->assign('info', $info);
    
    		$data['data'] = $this->fetch();
    	}
    
    	exit(json_encode($data));
    }

    /**
     * 发货模块
     *
     * @param int $id 订单ID
     */
    public function sendGoods()
    {
        $data = ['error' => '', 'data' => ''];

        $id = $this->post['id'];

        if (!validateExtend($id, 'NUMBER')) {
            $data['error'] = '参数格式有误';
        } else {
            $express_list = M('Express')->order('express_id asc')->select();
            $this->assign('list', $express_list);

            $this->assign('id', $id);

            $data['data'] = $this->fetch();
        }

        exit(json_encode($data));
    }

    /**
     * 获取指定客服平台的配置参数模板
     *
     * @param int $platform_id 客服平台ID
     */
    public function getCustomerServicePlatformConfig()
    {
        $data = ['error' => '', 'data' => ''];

        $platform_id = $this->post['platform_id'];

        if (!validateExtend($platform_id, 'NUMBER')) {
            $data['error'] = '参数格式有误';
        } else {
            $config_info = M('CustomerServicePlatform')->where('platform_id=' . $platform_id)->getField('platform_config');
            $data['data'] = $config_info;
        }

        exit(json_encode($data));
    }

    /**
     * 买家申请取消订单详情
     *
     * @param string $cancel_reason 取消理由
     * @param string $cancel_remark 取消备注
     * @param int $order_id 订单ID
     */
    public function getOrderCancelDetail()
    {
        $data = ['error' => '', 'data' => ''];

        $cancel_id = $this->post['cancel_id'];

        if (!validateExtend($cancel_id, 'NUMBER')) {
            $data['error'] = '参数格式有误';
        } else {
            $cancel_info = M('OrderCancel')->where('cancel_id=' . $cancel_id)->find();
            if (!$cancel_info) {
                $data['error'] = '该订单取消申请已不存在';
            } else {
                $this->assign('info', $cancel_info);
                $data['data'] = $this->fetch();
            }
        }

        exit(json_encode($data));
    }

    /**
     * 修改商品库存
     *
     * @param int $product_id 商品ID
     */
    public function exchangenumModify()
    {
        $data = ['error' => '', 'data' => ''];

        $product_id = $this->post['product_id'];

        if (!validateExtend($product_id, 'NUMBER')) {
            $data['error'] = '参数格式有误';
        } else {
            $info = M('Product')->where('id=' . $product_id)->field('id,name,exchangenum,totalnum')->find();
            if (!$info) {
                $data['error'] = '商品不存在';
            } else {
                $this->assign('info', $info);
                $data['data'] = $this->fetch();
            }
        }

        exit(json_encode($data));
    }

    /**
     * keditor文件上传
     */
    public function keditorUpload()
    {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/Public/Admin/js/kindeditor-4.1.6/php/JSON.php';
        $json = new \Services_JSON();

        //图片上传
        if (isset($_FILES['imgFile'])) {
            //处理图片
            $upload_config = [
                'file' => $_FILES['imgFile'],
                'exts' => array('jpg', 'png', 'gif', 'jpeg'),
                'path' => 'keditor/image/' . date('Ymd')
            ];
            $UPLOAD = new \Common\Controller\UploadController($upload_config);
            $info = $UPLOAD->upload();

            header('Content-type: text/html; charset=UTF-8');
            if (!empty($info['error'])) {
                echo $json->encode(array('error' => 1, 'message' => '请上传图片'));
            } else {
                $url = U($info['data']['url'], '', '', true);
                echo $json->encode(array('error' => 0, 'url' => $url));
            }
            exit;
        }
    }

    /**
     * 创建配置组
     */
    public function createSettingsGroup()
    {
        $SettingModel = new SettingModel();

        $html = $this->fetch();

        exit($html);
    }

    /**
     * 创建配置项
     */
    public function createSettings()
    {
        $SettingModel = new SettingModel();

        $where['group_status'] = array('eq', 1);
        $settings_group_list = $SettingModel->getGroupList('group_id,group_name', $where);
        $this->assign('list', $settings_group_list['list']);

        $html = $this->fetch();

        exit($html);
    }

    /**
     * 修改配置项
     */
    public function modifySettings()
    {
        $SettingModel = new SettingModel();

        $settings_id = $this->post['settings_id'];

        if (!validateExtend($settings_id, 'NUMBER')) {
            exit('参数格式有误');
        }

        //获取配置组信息
        $where['group_status'] = array('eq', 1);
        $settings_group_list = $SettingModel->getGroupList('group_id,group_name', $where);
        $this->assign('list', $settings_group_list['list']);

        $where['settings_id'] = array('eq', $settings_id);
        $info = M('Settings')->where($where)->find();
        if (!$info) {
            exit('信息不存在');
        }
        $this->assign('info', $info);

        $html = $this->fetch();

        exit($html);
    }

    /**
     * 修改配置项
     */
    public function bonus()
    {
//        $SettingModel = new SettingModel();
//
//        $settings_id = $this->post['settings_id'];
//
//        if ( ! validateExtend( $settings_id, 'NUMBER' ) ) {
//            exit( '参数格式有误' );
//        }
//
//        //获取配置组信息
//        $where['group_status'] = array( 'eq', 1 );
//        $settings_group_list   = $SettingModel->getGroupList( 'group_id,group_name', $where );
//        $this->assign( 'list', $settings_group_list['list'] );
//
//        $where['settings_id'] = array( 'eq', $settings_id );
//        $info                 = M( 'Settings' )->where( $where )->find();
//        if ( ! $info ) {
//            exit( '信息不存在' );
//        }
//        $this->assign( 'info', $info );

        $yesterdayPerformanceAmount = M('performance_' . date("Ym", strtotime("-1 day")))->where([
            'user_id' => 0,
            'performance_tag' => date("Ymd", strtotime("-1 day"))
        ])->getField('performance_amount') ?: '0.0000';
        $performancePortionBase = M('settings')->where(['settings_code' => 'performance_portion_base'])->getField('settings_value') ?: 1000;
        $consume = M('consume as c')
            ->join('zc_member as m on c.user_id = m.id')
            ->join('zc_performance_rule as r on m.star = r.rule_id')
            ->field('m.star, r.rule_label as label, count(c.user_id) as `count`, sum(c.amount) as `amount`')
            ->where(['m.star' => $this->post['star'] ?: 0])
            ->find();
        $consume['portion'] = intval($consume['amount'] / $performancePortionBase);
        $this->assign('yesterdayPerformanceAmount', $yesterdayPerformanceAmount);
        $this->assign('performancePortionBase', $performancePortionBase);
        $this->assign('performancePortionBase', $performancePortionBase);
        $this->assign('consume', $consume);
        $html = $this->fetch();
        exit($html);
    }

    /**
     * 修改配置组
     */
    public function modifySettingsGroup()
    {
        $SettingModel = new SettingModel();

        $group_id = $this->post['group_id'];

        if (!validateExtend($group_id, 'NUMBER')) {
            exit('参数格式有误');
        }

        //获取配置组信息
        $where['group_id'] = array('eq', $group_id);
        $info = M('SettingsGroup')->where($where)->find();
        if (!$info) {
            exit('信息不存在');
        }
        $this->assign('info', $info);

        $html = $this->fetch();

        exit($html);
    }
    
    /**
     * 获取流通兑换明细
     */
    public function getGrbTransactionDetails() {
    	$txid = $this->post['txid'];
    	
    	$wallet_type = M('Trade')->where("txid='{$txid}'")->getField('type');
    	
    	//处理类型
    	$wallet_platform = $wallet_type=='AGX' ? 'AJS' : $wallet_type;
    	
    	$WalletModel = new WalletModel($wallet_platform);
    	
    	$data = $WalletModel->getTransactionByTxid($txid);
    	
    	$error = '';
    	
    	if (!is_array($data)) {
    		$error = '获取明细失败';
    	}
    	
    	//格式化科学记数
    	$data['fee'] = number_format($data['fee'], 8);
    	
    	//确认数状态转换
    	if ($data['confirmations'] == 0) {
    		$data['confirmations_cn'] = '未确认';
    	} elseif ($data['confirmations'] < 3) {
    		$data['confirmations_cn'] = '确认中';
    	} else {
    		$data['confirmations_cn'] = '已确认';
    	}
    	
    	$this->assign('error', $error);
    	$this->assign('info', $data);
    	
    	$html = $this->fetch();
    	
    	exit($html);
    }
    
    /**
     * 商品排序
     */
    public function setGoodsSort() {
    	$id = $this->post['id'];
    	
    	$error = '';
    	
    	if (!validateExtend($id, 'NUMBER')) {
    		$error = '参数格式有误';
    	}
    	
    	$info = M('Product')->where('id='.$id)->field('id,ishot,name')->find();
    	if (!$info) {
    		$error = '未查询到该商品信息';
    	}
    	
    	$this->assign('info', $info);
    	
    	$html = $this->fetch();
    	
    	exit($html);
    }
    
    /**
     * 开通大中华区
     */
    public function openRegion() {
    	$GjjModel = new GjjModel();
    	
    	$uid = $this->post['uid'];
    	
    	$error = '';
    	
    	if (!validateExtend($uid, 'NUMBER')) {
    		$error = '参数格式有误';
    	}
    	
    	$this->assign('uid', $uid);
    	
    	//大中华区
    	$regions_name = $GjjModel->getRegionsName();
    	$this->assign('regions_name', $regions_name);
    	
    	$html = $this->fetch();
    	
    	exit($html);
    }
    
    /**
     * 获取指定大中华区对应省份
     */
    public function getRegionsProvince() {
    	$GjjModel = new GjjModel();
    	
    	$name = $this->post['name'];
    	
    	$regions_province = $GjjModel->getRegionsProvince($name);
    	$this->assign('regions_province', $regions_province);
    	
    	$html = $this->fetch();
    	
    	exit($html);
    }
    
    /**
     * 获取指定身份的联动市区
     */
    public function getRegionsCountry() {
    	$province = $this->post['province'];
    	$regions_id = $this->post['regions_id'];
    	
    	$input_name = [
    		'province' => "province[{$regions_id}]",
    		'city' => "city[{$regions_id}]",
    		'country' => "country[{$regions_id}]"
    	];
    	
    	$this->assign('province', $province);
    	$this->assign('input_name', $input_name);
    	
    	$html = $this->fetch();
    	
    	exit($html);
    }
    
    /**
     * 商品板块排序
     */
    public function setBlockSort() {
    	$id = $this->post['id'];
    	 
    	$error = '';
    	 
    	if (!validateExtend($id, 'NUMBER')) {
    		$error = '参数格式有误';
    	}
    	 
    	$info = M('Block')->where('block_id='.$id)->field('block_id,block_order,block_name')->find();
    	if (!$info) {
    		$error = '未查询到该板块信息';
    	}
    	 
    	$this->assign('info', $info);
    	 
    	$html = $this->fetch();
    	 
    	exit($html);
    }

}

?>