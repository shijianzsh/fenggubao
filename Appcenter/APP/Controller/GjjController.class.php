<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 谷聚金模块相关接口
// +----------------------------------------------------------------------
namespace APP\Controller;

use Common\Controller\ApiController;
use V4\Model\Currency;
use V4\Model\CurrencyAction;
use V4\Model\OrderModel;
use V4\Model\AccountRecordModel;
use V4\Model\GjjModel;
use V4\Model\AccountModel;

class GjjController extends ApiController
{

    private $member;
    private $gjj_block_id; //谷聚金代理专区block_id

    /**
     * @method POST
     *
     * @param int $uid 用户ID
     */
    public function __construct()
    {
        parent::__construct();

        $user_id = $this->post['uid'];

        if (!$user_id) {
            $this->myApiPrint('非法操作！');
        }

        $this->member = M('member')->where('id=' . $user_id)->field('id,is_lock,is_blacklist')->find();
        if (!$this->member) {
            $this->myApiPrint('非法操作！');
        }

        if ($this->member['is_lock'] != '0' || $this->member['is_blacklist'] != '0') {
            $this->myApiPrint('非法操作！');
        }

        $this->gjj_block_id = C('GJJ_BLOCK_ID');
    }

    /**
     * 申请省营运中心\区县代理\乡镇代理
     *
     * @method POST
     *
     * @param int uid 用户ID
     * @param int role 申请身份(1 乡镇代理, 2 区县代理, 3 市级代理（预留），4 省营运中心， 5 大中华区)
     * @param string province 省
     * @param string city 市
     * @param string country 区
     * @param string village 乡
     */
    public function apply()
    {
        $role = $this->post['role'];
        $province = $this->post['province'];
        $city = $this->post['city'];
        $country = $this->post['country'];
        $village = $this->post['village'];

        //核验实名认证
        $map_certification = [
            'user_id' => $this->member['id'],
            'certification_status' => 2
        ];
        $certification = M('certification')->where($map_certification)->find();
        if (!$certification) {
            $this->myApiPrint('请先实名认证');
        }

        //验证参数
        if (!validateExtend($role, 'NUMBER')) {
            $this->myApiPrint('申请类型格式有误');
        }
        if ($role >= 4 && !validateExtend($province, 'CHS')) {
            $this->myApiPrint('请选择省');
        }
        if ($role >= 3 && !validateExtend($city, 'CHS')) {
            $this->myApiPrint('请选择市');
        }
        if ($role >= 2 && !validateExtend($country, 'CHS')) {
            $this->myApiPrint('请选择区');
        }
// 		if ( $role >= 1 && empty($village) ) {
// 			$this->myApiPrint( '请填写乡镇信息' );
// 		}

        //核验是否符合申请要求
        $map_check = [
            'user_id' => ['eq', $this->member['id']],
            'audit_status' => ['neq', 2]
        ];
        $apply_check = M('gjj_roles')->where($map_check)->find();
        if ($apply_check) {
            $this->myApiPrint('对不起，你已经申请有其他代理身份，不能再申请当前代理');
        }

        M()->startTrans();
        $result1 = true;
        $result2 = true;
        $result3 = true;

        //判断对应地区是否已经存在已提交申请的用户
        $where_exists['role'] = array('eq', $role);
        $where_exists['audit_status'] = array('neq', 2);
        if ($role >= 4) {
            $where_exists['province'] = array('eq', $province);
        }

        //当身份省代合伙人时判断申请身份已经被占用
        if ($role == 4) {
            $exists_info = M('gjj_roles')->where($where_exists)->field('id')->find();
            if ($exists_info) {
                $this->myApiPrint('对不起，该省份已经存在对应身份的用户，不能再次申请！');
            }
        }

        //通用判断
        if ($role >= 3) {
            $where_exists['city'] = array('eq', $city);
        }
        if ($role >= 2) {
            $where_exists['country'] = array('eq', $country);
        }
// 		if ($role >= 1) {
// 			$where_exists['village'] = array('eq', $village);
// 		}
        $exists_info = M('gjj_roles')->where($where_exists)->field('id')->find();
        if ($exists_info) {
            $this->myApiPrint('对不起，该地区已经存在对应身份的用户，不能再次申请！');
        }

        //先删除之前已存在的申请记录
        $map_delete = [
            'user_id' => $this->member['id']
        ];
        $delete_info = M('gjj_roles')->where($map_delete)->find();
        if ($delete_info) {
            $result1 = M('gjj_roles')->where($map_delete)->delete();
        }

        $data = [
            'user_id' => $this->member['id'],
            'role' => $role,
            'province' => $province,
            'city' => $city,
            'country' => $country,
// 			'village' => $village,
            'created_at' => time(),
            'updated_at' => time()
        ];

        //省营运中心合伙人
        if ($role == 4) {
            $data_province = $data;

            unset($data_province['city']);
            unset($data_province['country']);

            $result3 = M('gjj_roles')->add($data_province);

            $data['role'] = 2;
        }

        $result2 = M('gjj_roles')->add($data);

        if (!$result1 || !$result2 || !$result3) {
            M()->rollback();
            $this->myApiPrint('申请失败');
        }

        M()->commit();

        $this->myApiPrint('申请成功', 400);
    }

    /**
     * 上传打款凭证
     *
     * @param int $id 谷聚金用户申请ID
     * @param image1 打款凭证
     */
    public function uploadCertificate()
    {
        $id = $this->post['id'];

        if (!validateExtend($id, 'NUMBER')) {
            $this->myApiPrint('参数格式有误');
        }

        //上传图片
        $upload_config = array(
            'file' => 'multi',
            'exts' => array('jpg', 'png', 'gif', 'jpeg'),
            'path' => 'gjj/' . date('Ymd')
        );
        $Upload = new \Common\Controller\UploadController($upload_config);
        $info = $Upload->upload();
        if (!empty($info['error'])) {
            $this->myApiPrint('申请失败，请稍后重试');
        } else {
            $image = $info['data']['image1']['url'];
        }

        //获取申请信息
        $apply_info = M('gjj_roles')->where('id=' . $id)->find();
        if (!$apply_info) {
            $this->myApiPrint('申请信息已不存在');
        }

        $data = [
            'image' => $image
        ];
        $map = [
            'user_id' => $apply_info['user_id'],
            'audit_status' => ['neq', 2]
        ];
        $result = M('gjj_roles')->where($map)->save($data);
        if (!$result) {
            $this->myApiPrint('上传失败，请稍后重试');
        }

        $this->myApiPrint('上传成功', 400, $data);
    }

    /**
     * 板块首页
     */
    public function index()
    {
    	$current_lang = getCurrentLang(true);
    	
        $GjjModel = new GjjModel();
        $AccountModel = new AccountModel();

        $result = [];

        //获取用户信息
        $member_info = M('Member')->where('id=' . $this->member['id'])->field('img,loginname,truename,img')->find();
        if (!$member_info) {
            $this->myApiPrint('用户不存在');
        }
        $result['member'] = $member_info;

        //获取用户代理身份
        $map_roles = [
            'audit_status' => ['egt', 0],
            'enabled' => ['egt', 0]
        ];
        $roles = $GjjModel->getGjjRoles($this->member['id'], false, true, $map_roles);
        if ($roles) {
            $result['is_agent'] = 1;

            $roles['role_counties'] = empty($roles['role_counties']) ? null : $roles['role_counties'];
            $result['role'] = $roles;
        } else {
            $result['is_agent'] = 0;
            $result['role'] = [
                'role_name' => '',
                'role_region' => '',
                'role_counties' => null,
                'audit_status' => '0',
                'remark' => ''
            ];
        }

        //所有已申请信息
        $apply_role = [
            'province' => null,
            'country' => null,
        ];
        $map_status = [
            'user_id' => ['eq', $this->member['id']]
        ];
        $get_field = 'id,role,province,city,country,audit_status,enabled,image,remark,created_at,paid_at,updated_at';
        //省营运中心合伙人
        $map_status['role'] = ['eq', 4];
        $roles_province = $GjjModel->getInfo($get_field, $map_status);
        if ($roles_province) {
            $apply_role['province'] = $roles_province;
        }
        //区县代理合伙人
        $map_status['role'] = ['eq', 2];
        $roles_country = $GjjModel->getInfo($get_field, $map_status);
        if ($roles_country) {
            $apply_role['country'] = $roles_country;
        }
        $result['apply_role'] = $apply_role;

        //产品信息
        $product = $GjjModel->getProductDetails('pro.id, ppr.price_id, ppr.price_cash');
        $product['price_cash'] = sprintf('%.2f', $product['price_cash']);
        $product['url'] = U('Product/showDetail/id/' . $product['id'], '', '', true);
        $result['product'] = $product;

        //提货券和兑换券余额
        $balance['colorcoin'] = $AccountModel->getBalance($this->member['id'], Currency::ColorCoin);
        $balance['colorcoin'] = floor($balance['colorcoin']);
        $balance['enroll'] = $AccountModel->getBalance($this->member['id'], Currency::Enroll);
        $balance['enroll'] = floor($balance['enroll']);
        $result['balance'] = $balance;

        //合伙人申请说明
        $result['apply_explain'] = $this->CFG['gjj_apply_instruction'.$current_lang];

        //单次最低提货数量
        $result['gjj_exchange_min'] = $this->CFG['gjj_exchange_min'];

        $this->myApiPrint('获取成功', 400, $result);
    }

    /**
     * 提货券\兑换券明细
     *
     * @param int uid 会员ID
     * @param int month 日期
     * @param int tag 收入/支出
     * @param int page 分页参数（page=0显示1-10条数据）
     * @param string currency 币种(colorcoin:提货券,enroll:兑换券[默认提货券])
     */
    public function currency_details()
    {
        $uid = $this->member['id'];
        $month = I('post.month');
        $tag = intval(I('post.tag'));  //1=收入  0=支出*/
        $pn = intval(I('post.page'));
        $currency = $this->post['currency'];

        //验证参数
        $month_suffix = verify_cash_list($uid, $month, $tag);

        $currency = empty($currency) ? 'colorcoin' : $currency;
        switch ($currency) {
            case 'colorcoin':
                $currency = Currency::ColorCoin;
                break;
            case 'enroll':
                $currency = Currency::Enroll;
                break;
            default:
                $this->myApiPrint('未知币种类型');
        }

        //加载数据
        $arm = new AccountRecordModel();
        $data = $arm->getPageList($uid, $currency, $month_suffix, $pn, $tag);

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
            $attach = $arm->initAtach($obj, $currency, $month_suffix, $v['record_id'], $v['record_action']);
            $row['from_name'] = $arm->getFinalName($attach['from_uid'], $attach['from_name']);
            $row['from_pic'] = $attach['pic'];
            $return[] = $row;
        }

        $data['list'] = $return;

        $this->myApiPrint('查询成功！', 400, $data);
    }

    /**
     * 提货券第三方充值 (支付宝,微信)
     *
     * @param uid  充值用户id
     * @param amount 充值金额
     * @param pay_type 支付类型 1支付宝2微信
     */
    public function colorcoin_recharge()
    {
        $this->myApiPrint('此功能停用');

        $GjjModel = new GjjModel();

        $uid = I('post.uid');
        $amount = I('post.amount');
        $pay_type = I('post.pay_type') ? I('post.pay_type') : 1;
        $currency = I('post.currency') ? I('post.currency') : 2; //1=现金积分； 2=提货券. 对应order表num字段

        //限制只允许充值提货券
        $currency = 2;

        //验证参数
        verify_thirdparty_recharge($uid, $amount, $pay_type);

        //只有区县代理才能充值
        $gjj_check = M('gjj_roles')->where("user_id={$uid} and role=2 and enabled=1 and audit_status=1")->field('id')->find();
        if (!$gjj_check) {
            ajax_return('非区县代理无充值权限');
        }

        //获取提货券单价
        $product_info = $GjjModel->getProductDetails('ppr.price_cash');
        $chknum = $product_info['price_cash'];

        M()->startTrans();
        if ($pay_type == 2) {
            //1.生产订单
            $om = new OrderModel();
            $order_no = $om->create($uid, $amount, PaymentMethod::Wechat, 0, 0, '微信充值', '', 0, 0, 3, $currency, 0, 0, 0, 0, $chknum);

            //2.生成订单签名
            $sign_str = $om->getWxpaySign($order_no, $amount, 'Notify/recharge');
            if ($order_no && $sign_str) {
                M()->commit();
                $returndata = $om->format_return('提交成功', 400, $sign_str);
                $this->ajaxReturn($returndata);
            } else {
                M()->rollback();
                $this->myApiPrint('return null', 300);
            }
        } else {
            //1.支付宝生产订单
            $om = new OrderModel();
            $order_no = $om->create($uid, $amount, PaymentMethod::Alipay, 0, 0, '支付宝充值', '', 0, 0, 3, $currency, 0, 0, 0, 0, $chknum);

            //2.生成订单签名
            $sign_str = $om->getAlipaySign($order_no, $amount, 'Notify/recharge');
            if ($order_no && $sign_str) {
                M()->commit();
                $returndata = $om->format_return('提交成功', 400, $sign_str);
                $this->ajaxReturn($returndata);
            } else {
                M()->rollback();
                $this->myApiPrint('return null', 300);
            }
        }
    }

    /**
     * 已申请合伙人区域
     *
     */
    public function regionByApplied()
    {
        $role_list = [5, 4, 2];
        $roles_config = C('GJJ_FIELD_CONFIG')['gjj_roles']['role'];

        $data = [];
        foreach ($role_list as $k => $v) {
            $map = [
                'rol.audit_status' => ['eq', 1],
                'rol.role' => ['eq', $v]
            ];

            $field = '';
            switch ($v) {
                case 2:
                    $field = " concat(rol.province,rol.city,rol.country) area, mem.loginname, mem.truename ";
                    break;
                case 4:
                    $field = " rol.province area, mem.loginname, mem.truename ";
                    break;
                case 5:
                    $field = " rol.region area, mem.loginname, mem.truename ";
                    break;
            }

            $applied['title'] = $roles_config[$v];
            
            $applied['list'] = M('gjj_roles')
                ->alias('rol')
                ->join('join __MEMBER__ mem ON mem.id=rol.user_id')
                ->field($field)
                ->where($map)
                ->select();
            foreach ($applied['list'] as $k1=>$v1) {
            	$applied['list'][$k1]['loginname'] = substr( $v1['loginname'], 0, 3 ) . '********';
//             	$applied['list'][$k1]['truename'] = '*' . mb_substr( $v1['truename'], 1, mb_strlen( $v1['truename'], 'utf-8' ), 'utf-8' );
            	$applied['list'][$k1]['truename'] = mb_substr( $v1['truename'], 0, 1, 'utf-8' ). '**';
            }
            
            $applied['count'] = count($applied['list']);

            $data[] = $applied;
        }

        $this->myApiPrint('查询成功', 400, ['regions' => $data]);
    }

    /**
     * 我的订单
     *
     * @param int uid 用户ID
     * @param int status 订单状态(0:全部；1:待发货；2待收货；3已完成；4退款)
     * @param int page 当前页数(默认1)
     */
    public function order_list()
    {
        $om = new OrderModel();

        $uid = $this->member['id'];
        $order_status = $this->post['status'];
        $pn = $this->post['page'] < 1 ? 1 : $this->post['page'];

        $data = $om->getOrderList($order_status, $uid, $pn, $this->gjj_block_id);


        $this->myApiPrint('查询成功！', 400, $data);
    }

    /**
     * 我的提货单
     * @param int uid 用户ID
     * @param int page 当前页数(默认1)
     */
    public function tihuo_list()
    {
        $om = new OrderModel();

        $uid = $this->member['id'];
        $pn = $this->post['page'] < 1 ? 1 : $this->post['page'];

        $data = $om->getOrderList(0, $uid, $pn, $this->gjj_block_id);

        $orders = [];
        foreach ($data['list'] as $k => $v) {
            $product = $v['items'][0];

            $field_config = C('FIELD_CONFIG');

            $orders[] = [
                //订单ID
                'id' => $v['id'],

                //数量
                'num' => $product['product_quantity'],

                //收货信息
                'received' => [
                    'consignee' => $v['affiliate_consignee'],
                    'phone' => $v['affiliate_phone'],
                    'city' => $v['affiliate_city'],
                    'address' => $v['affiliate_address']
                ],

                //时间
                'addtime' => substr($v['addtime'], 5),

                //订单状态
                'order_status' => $v['order_status'],
                'order_status_cn' => $field_config['orders']['order_status'][$v['order_status']],

                //支付类型
                'amount_type' => $v['amount_type'],
                'amount_type_cn' => $field_config['orders']['amount_type'][$v['amount_type']],

                //快递查询
                'trackingno' => $v['affiliate_trackingno'],
                'kuaidi100' => $v['kuaidi100'],
            ];
        }

        $this->myApiPrint('查询成功！', 400, ['orders' => $orders]);
    }

    /**
     * 获取合伙人申请状态
     */
    public function getApplyStatus()
    {
        $GjjModel = new GjjModel();

        $gjj_is_agent = '0'; //0:未申请,1:未激活,2:已激活

        $map_status = [
            'user_id' => ['eq', $this->member['id']],
            'role' => ['in', '2,4,5']
        ];
        $get_field = 'id,enabled';
        $roles = $GjjModel->getInfo($get_field, $map_status);
        if ($roles) {
            $gjj_is_agent = $roles['enabled'] ? '2' : '1';
        }

        $data['gjj_is_agent'] = $gjj_is_agent;

        $this->myApiPrint('查询成功！', 400, $data);
    }

}