<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 会员常用操作接口 
// +----------------------------------------------------------------------
namespace APP\Controller;

use AliPay;
use Common\Controller\ApiController;
use Common\Controller\UploadController;
use V4\Model\Image;
use V4\Model\OrderModel;
use V4\Model\Currency;
use V4\Model\AccountRecordModel;
use V4\Model\CurrencyAction;
use V4\Model\AccountModel;
use V4\Model\MemberModel;
use V4\Model\ProcedureModel;
use V4\Model\EnjoyModel;
use V4\Model\WithdrawModel;
use V4\Model\WalletModel;

class MemberController extends ApiController
{

    /**
     * 会员个人信息
     *
     * @param id 会员ID
     * @param level
     */
    public function memberInfo()
    {
    	$om = new AccountModel();
    	
        $id = intval(I('post.id'));
        $wherekey['id'] = $id;
        $data = M('member')
            //->field("CONCAT(img,'".C('HOME_BANNER_SIZE')."') as img,username,nickname,reg_time,qrcode,email,loginname,level,role,store_flag,star")
            ->field("id,img,username,nickname,is_partner,reg_time,qrcode,email,loginname,level,role,store_flag,star,role_star")
            ->where($wherekey)
            ->find();
        //$data['img'] = C('LOCAL_HOST') . $data['img'];
        if (empty($data)) {
            $this->myApiPrint('此用户不存在！');
        }

        $consume = M('Consume')->field('level as role_star,is_out,dynamic_out')->where('user_id=' . $id)->find();
        $data['role_star'] = '';
        $data['is_out'] = 0;
        $data['consume_level'] = $consume['role_star'];
        if ($consume) {
            //新版星级
            $data['role_star'] = $consume['role_star'] ? $consume['role_star'] . '星' : '';
            $data['is_out'] = ($consume['is_out'] || $consume['dynamic_out']) ? true : false;
        }



        //荣耀指数处理:最小级别为1(0时改为1)
        $data['star'] = $data['star'] == '0' ? 1 : $data['star'];

        /*
        //获取余额
        $om = new AccountModel();
        $balance = $om->getItemByUserId($id);

        $data['goldcoin'] = sprintf('%.2f', $balance['account_goldcoin_balance']);
        $data['cash'] = sprintf('%.2f', $balance['account_cash_balance']);
        $data['points'] = sprintf('%.2f', $balance['account_points_balance']);
        */

        //今日是否签到
        $data['checkin'] = 0;
        $ckwhere['user_id'] = $id;
        $ckwhere['_string'] = "FROM_UNIXTIME(checkin_addtime,'%Y%m%d') = FROM_UNIXTIME(UNIX_TIMESTAMP(),'%Y%m%d')";
        $checkin = M('account_checkin')->where($ckwhere)->order('checkin_id desc')->find();
        if ($checkin) {
            $data['checkin'] = 1;
        }

        //增加商家申请状态
        /*
        $store = M('store');
        $where['uid'] = $id;
        $store_arr = $store->field('manage_status')->where($where)->order('id desc')->find();
        $data['store_status'] = empty($store_arr) ? '3' : $store_arr['manage_status'];
        if ($data['store_status'] == 11) {
            $data['store_status'] = '3';
        }
        if (empty($store_arr)) {
            $data['store_flag'] = 0;
        }
        */

        //是否可以申请店铺
        /*
        $data['apply_store_tag'] = 1;
        $data['apply_store_msg'] = '';
        if ($data['role'] == 4) {
            $data['apply_store_tag'] = 0;
            $data['apply_store_msg'] = '区域合伙人不能申请店铺';
        }
        */


        //身份
        $data['utypestr'] = getrole($data, $id);
        // if ($data['is_out'] && $data['level'] > 1) {
        //     $data['utypestr'] .= '（已出局）';
        // }

//		$data['star'] = 0;
//		if ( $data['role'] == 4 ) {
//			$data['star'] = 5;
//		} else {
//			$data['star'] = 3;//兼容app所有人都显示责任消费
//		}

        //实名认证
        $certification = M('certification')->where('user_id = ' . $id)->find();
        $data['certification_msg'] = '';
        if (empty($certification)) {
            $data['certification_status'] = -1; //未认证
        } elseif (empty($certification['certification_identify_1']) && empty($certification['certification_identify_2']) && empty($certification['certification_identify_3'])) { //买单自动添加的实名认证记录默认为未认证
        	$data['certification_status'] = -1; //未认证
        } else {
            $data['certification_status'] = $certification['certification_status'];
            if ($certification['certification_status'] == 1) {
                $data['certification_msg'] = '实名认证被驳回：' . $certification['certification_remark'];
            }
        }

        //缩略图
        $temp_str = explode('.', $data['img']);
        $temp_str = $temp_str[0] . '_sm.' . $temp_str[1];
        $data['sm_img'] = $temp_str;

        //增加创客分享H5注册页面链接
        $url = C('LOCAL_HOST') . 'H5/Index/index/recommer/' . base64_encode($data['loginname']);
        $data['url'] = $url;

        //第三方 名称+url
//		$data['partner1label'] = '巴蜀公用';
//		$data['partner1url']   = 'http://';//'转入美来分享系统';

        //增加是否绑定银行卡信息
        /*
        $bankcard_info = M('WithdrawBankcard')->where('uid=' . $id)->find();
        $data['is_bind_bankcard'] = $bankcard_info ? '1' : '0';
        */

        //客服电话
        //$data['concatus'] = '';//C('KEFU_PHONE');

        //im个人中心
        //$data['imurl'] = U('Im/dialog/', array('current_id' => $id), true, true);

        //现金积分余额
        
        $balance = $om->getItemByUserId($id, 'account_cash_balance');
        $data['account_cash_balance'] = sprintf('%.2f', $balance['account_cash_balance']);
        
        //报单币余额
        $supply_balance = $om->getBalance($id, Currency::Supply);
        $data['account_supply_balance'] = sprintf('%.2f', $supply_balance);
        
        //澳洲SKN股数余额
        $enjoy_balance = $om->getBalance($id, Currency::Enjoy);
        $data['account_enjoy_balance'] = sprintf('%.2f', $enjoy_balance);
        
        //第三方APP下载信息
        $data['third_wallet'] = [
	        'title' => C('THIRD_WALLET.name'),
	        'icon' => C('THIRD_WALLET.icon'),
	        'url' => C('THIRD_WALLET.url'),
        ];

        $this->myApiPrint('查询成功', 400, $data);
    }

    /**
     * 获取余额
     */
    public function getbalance($user_id)
    {
        $id = $this->post['user_id'];

        //获取余额
        $om = new AccountModel();
        $balance = $om->getItemByUserId($id);

        $data['goldcoin'] = sprintf('%.2f', $balance['account_goldcoin_balance']);
        $data['cash'] = sprintf('%.2f', $balance['account_cash_balance']);
        $data['colorcoin'] = sprintf('%.2f', $balance['account_colorcoin_balance']);
        $data['enroll'] = sprintf('%.2f', $balance['account_enroll_balance']);
        $data['supply'] = sprintf('%.2f', $balance['account_supply_balance']);
        $data['credits'] = sprintf('%.2f', $balance['account_credits_balance']);
        $data['enjoy'] = sprintf('%.2f', $balance['account_enjoy_balance']);

        $this->myApiPrint('获取成功', 400, $data);
    }

    /**
     * 我的订单列
     */
    public function order_list()
    {
        $uid = I('post.uid');   //会员ID
        $order_status = intval(I('post.status'));  //0.全部 1.待评价 2.已完成3.未完成 4.退款
        $pn = I('post.page');
        if ($pn < 1) {
            $pn = 1;
        }

        $member = M('member')->where('id = ' . $uid)->find();
        if (!$member) {
            $this->myApiPrint('数据错误！', 300, $page);
        }

        $om = new OrderModel();
        $data = $om->getOrderByList($order_status, $uid, $pn);


        $this->myApiPrint('查询成功！', 400, $data);
    }

    /**
     * 删除订单
     * Enter description here ...
     */
    public function delOrder()
    {
        $order_id = I('post.order_id');
        $u_id = I('post.uid');
        $om = new OrderModel();
        $res = $om->delOrder($order_id, $u_id);
        if ($res) {
            $this->myApiPrint('删除成功！', 400, "");
        } else {
            $this->myApiPrint('删除失败！');
        }
    }


    /**
     * 订单退款、取消：
     */
    public function exchange_delete()
    {
        $order_id = I('post.order_id');
        $cancel_reason = I('post.cancel_reason');
        $cancel_descp = I('post.cancel_descp');

        if (empty($order_id)) {
            $this->myApiPrint('数据错误！');
        }

        $post = verify_exchangeOrder_cancel($order_id);

        M()->startTrans();
        $om = new OrderModel();
        $res = $om->cancenExchangeOrder($post['product'], $post['order'], $cancel_reason, $cancel_descp);
        if ($res) {
            M()->commit();
            $this->myApiPrint('成功取消！', 400, '');
        } else {
            M()->rollback();
            $this->myApiPrint('取消失败！', 300, '');
        }
    }

    /**
     * 到店订单删除
     *
     * @param order_id 订单ID
     */
    public function myOrders_delete()
    {
        $order_id = I('post.order_id');

        if (empty($order_id)) {
            $this->myApiPrint('数据错误！');
        }

        $wherekey['id'] = $order_id;
        $data = M('orders')->where($wherekey)->find();
        if ($data == '') {
            $this->myApiPrint('找不到订单数据！');
        }

        $row = M('orders')->where($wherekey)->delete();
        if ($row) {
            $this->myApiPrint('订单删除成功！', 400, '');
        } else {
            $this->myApiPrint('订单删除失败！', 300, '');
        }
    }

    /**
     * 到店订单去评价页面
     */
    public function exchange_comment()
    {
        $order_id = I('post.order_id');

        if (empty($order_id)) {
            $this->myApiPrint('数据错误！');
        }

        $wherekey['id'] = $order_id;
        $data = M('orders')->field('id,iscontent')->where($wherekey)->find();

        $this->myApiPrint('查询完成！', 400, $data);
    }

    /**
     * 订单评价2.0
     */
    public function exchange_comment_save()
    {
        $order_id = I('post.order_id');  //订单id
        $comment_score = I('post.score');  //评分
        $comment_content = trim($_POST['content']); //评论内容

        if (empty($order_id)) {
            $this->myApiPrint('数据错误！');
        }

        $wherekey['id'] = $order_id;
        $dt = M('orders')->field('id,iscontent')->where($wherekey)->find();
        if ($dt['iscontent'] == '1') {
            $this->myApiPrint('你已经对该订单发布评论了！');
        }

        $ok['score'] = $comment_score;
        $ok['content'] = $comment_content;
        $ok['iscontent'] = '1';  //状态已评价
        $ok['comment_time'] = time();

        //处理图片
        $upload_config = array(
            'file' => 'multi',
            'exts' => array('jpg', 'png', 'gif', 'jpeg'),
            'path' => 'comment/' . date('Ymd')
        );
        $Upload = new UploadController($upload_config);
        $info = $Upload->upload();
        if (!empty($info['error'])) {
            //$this->myApiPrint('活动图片上传失败，请重新上传图片！',300,(object)$result);
        } else {

            $carousel1 = '';
            //第一张图片
            if ($info['data']['photo1']) {
                $photo1 = $info['data']['photo1']['url'];
                $carousel1 .= $photo1 . ',';
                //压缩图片
                //createThumbScal($photo1, 500, 500);
            }
            if ($info['data']['photo2']) {
                $photo2 = $info['data']['photo2']['url'];
                $carousel1 .= $photo2 . ',';
                //createThumbScal($photo2, 500, 500);;
            }
            if ($info['data']['photo3']) {
                $photo3 = $info['data']['photo3']['url'];
                $carousel1 .= $photo3 . ',';
                //createThumbScal($photo3, 500, 500);
            }
            if ($info['data']['photo4']) {
                $photo4 = $info['data']['photo4']['url'];
                $carousel1 .= $photo4 . ',';
                //createThumbScal($photo4, 500, 500);
            }
            if ($carousel1 != '') {
                $carousel1 = substr($carousel1, 0, strlen($carousel1) - 1);
                $carousel1 = str_replace('/Uploads', 'Uploads', $carousel1);
            }
            $ok['comment_img'] = $carousel1;

        }

        $res = M('orders')->where($wherekey)->save($ok);
        if ($res !== false) {
            $this->myApiPrint('评论完成！', 400, '');
        } else {
            $this->myApiPrint('评论失败！', 300);
        }
    }

    /**
     * 用户删除评论
     * Enter description here ...
     */
    public function deletecomments()
    {
        $orderid = intval(I('post.orderid'));
        $uid = intval(I('post.uid'));
        if ($orderid < 1 || $uid < 1) {
            $this->myApiPrint('参数错误！', 300, '');
            exit;
        }
        $info = M('orders')->where('id=' . $orderid . ' and uid=' . $uid)->find();
        if ($info) {
            M('orders')->where('id=' . $orderid . ' and uid=' . $uid)->save(array(
                'content' => '',
                'comment_img' => '',
                'score' => 0
            ));
            $this->myApiPrint('删除成功！', 400, '');
        } else {
            $this->myApiPrint('数据不存在！', 300, '');
            exit;
        }
    }

    /**
     * 商家评论列表
     *
     * @param storeid 商家ID
     * @param minstore 最小评分(空值或0表示返回全部评论)
     * @param maxstore (1-2星不满意,3-4星味道不错,5星满意)
     */
    public function commentList()
    {
        $storeid = I('post.storeid');
        $minscore = intval(I('post.minscore'));
        $maxscore = intval(I('post.maxscore'));

        if (empty($storeid)) {
            $this->myApiPrint('数据错误！');
        }

        $whereString = ' 1=1 ';
        if ($minscore > 0) {
            $whereString .= ' and a.score>' . $minscore;
        }
        if ($maxscore > $minscore) {
            $whereString .= ' and a.score<' . $maxscore;
        }

        //用户评价
        $wherekey2['a.storeid'] = $storeid;
        $comment = M('orders a')
            ->field('b.img,b.nickname,a.score,a.content,a.post_time')
            ->join('zc_member b on b.id=a.uid')
            ->where($wherekey2)
            ->where($whereString)
            ->select();
        foreach ($comment as $k => $v) {
            $comment[$k]['nickname'] = mb_substr($v['nickname'], 0, 1, 'utf-8') . '**';
        }

        $data['usercomment'] = $comment;

        $this->myApiPrint('查询成功', 400, $data);
    }

    /**
     * 我的买单-废弃
     * @param uid 会员ID
     * @param page 页面
     */
    public function myShoppingList()
    {
        $uid = I('post.uid');

        if (($uid == "")) {
            $this->myApiPrint('数据错误！');
        }

        $page = intval(I('post.page')) - 1;
        $page = $page > 0 ? $page * 10 : 0;

        $wherekey = 'uid=' . $uid;
        $record = M('orders_member_1')->where($wherekey)->count();
        $coin = M('orders_member_1')->where($wherekey)->sum('goldcoin');
        $gife = M('orders_member_1')->where($wherekey)->sum('gife');

        $totalPage = $record;
        $everyPage = '10';
        $pageString = $page . ',' . $everyPage;

        $data = M('orders_member_1')->where($wherekey)->order('id desc')->limit($pageString)->select();
        $page1['totalPage'] = floor(($totalPage - 1) / 10) + 1;
        $page1['everyPage'] = $everyPage;
        $data1['page'] = $page1;
        if (empty($data)) {
            $this->myApiPrint('查询失败！', 300, $data1);
        }

        $data1['data'] = $data;
        $data1['totalrecord'] = $record;
        $data1['totalcoin'] = $coin;
        $data1['totalgife'] = $gife;

        $this->myApiPrint('查询成功！', 400, $data1);
    }

    /**
     * 系统消息公告
     *
     * @param id 消息公告ID(0或空则显示全部)
     * @param mclass 类别(1::系统消息,2:常见问题,0或空则显示全部)
     */
    public function systemMessage()
    {
        $id = intval(I('post.id'));
        $mclass = intval(I('post.mclass'));

        $whereString = ' id>0';

        if ($id > 0) {
            $whereString .= ' and id=' . $id;
        }
        if ($mclass > 0) {
            $whereString .= ' and mclass=' . $mclass;
        }

        $data = M('message')
            ->field('title,content,post_time')
            ->where($whereString)
            ->select();

        $this->myApiPrint('查询完成！', 400, $data);
    }


    /**
     * 收藏店铺动作
     *
     * @param uid 会员ID
     * @param storeid 店铺ID
     */
    public function myfavoriteS()
    {
        $uid = I('uid');
        $storeid = I('storeid');

        if (!$uid || !$storeid) {
            $this->myApiPrint('数据错误！');
        }

        $wherekey['uid'] = $uid;
        $wherekey['storeid'] = $storeid;
        $data = M('favorite_store')->where($wherekey)->find();
        if (empty($data)) {
            $da['uid'] = $uid;
            $da['storeid'] = $storeid;
            $da['favorite'] = '1';
            M('favorite_store')->add($da);
            $data1 = M('favorite_store')->where($wherekey)->find();
            M('store')->where('id=' . $storeid)->setInc('attention', 1);

            $this->myApiPrint('收藏成功', 400, $data1);
        } else {
            $da['favorite'] = strval(intval($data['favorite']) xor 1);
            M('favorite_store')->where($wherekey)->save($da);
            $data1 = M('favorite_store')->where($wherekey)->find();
            if ($da['favorite'] == 0) {
                M('store')->where('id=' . $storeid)->setDec('attention', 1);
                $this->myApiPrint('取消成功', 400, $data1);
            } else {
                M('store')->where('id=' . $storeid)->setInc('attention', 1);
                $this->myApiPrint('收藏成功', 400, $data1);
            }
        }
    }

    /**
     * 收藏商品动作
     *
     * @param uid 会员ID
     * @param productid 商品ID
     */
    public function myfavoriteP()
    {
        $uid = I('uid');
        $productid = I('productid');

        if (!$uid || !$productid) {
            $this->myApiPrint('数据错误！');
        }

        $wherekey['uid'] = $uid;
        $wherekey['productid'] = $productid;
        $data = M('favorite_product')->where($wherekey)->find();
        if (empty($data)) {
            $da['uid'] = $uid;
            $da['productid'] = $productid;
            $da['favorite'] = '1';
            M('favorite_product')->add($da);
        } else {
            $da['favorite'] = strval(intval($data['favorite']) xor 1);
            M('favorite_product')->where($wherekey)->save($da);
        }

        $data1 = M('favorite_product')->where($wherekey)->find();
        if ($data['favorite'] == 0) {
            $this->myApiPrint('收藏成功', 400, $data1);
        } else {
            $this->myApiPrint('取消成功', 400, $data1);
        }
    }

    /**
     * 已收藏店铺列表
     *
     * @param uid 会员ID
     */
    public function favorite_listS()
    {
    	$current_lang = getCurrentLang(true);
    	
        $uid = I('uid');

        if ((!$uid)) {
            $this->myApiPrint('数据错误！');
        }
        $data1['data'] = array();
        $page = intval(I('post.page')) - 1;
        $page = $page > 0 ? $page * 10 : 0;

        $wherekey['f.uid'] = $uid;
        $wherekey['f.favorite'] = 1;
        $totalPage = M('favorite_store f')->where($wherekey)->count();
        $everyPage = '10';
        $pageString = $page . ',' . $everyPage;

        $field_store_name = 's.store_name'.$current_lang.' as store_name';
        $data = M('favorite_store f')
            ->field("f.uid, f.storeid, m.nickname, ".$field_store_name.", s.score as store_score, s.address as store_address, s.attention as store_attention, CONCAT(s.store_img,'" . C('USER_COLS_STORE_SIZE') . "') as store_img")
            ->join('left join zc_member as m on m.id = f.uid')
            ->join('left join zc_store as s on s.id = f.storeid')
            ->where($wherekey)->limit($pageString)->select();
        if (empty($data)) {
            $this->myApiPrint('找不到数据！', 400, $data1);
        }
        $page1['totalPage'] = floor(($totalPage - 1) / 10) + 1;
        $page1['everyPage'] = $everyPage;
        $data1['data'] = $data;
        $data1['page'] = $page1;
        $this->myApiPrint('查询成功！', 400, $data1);
    }

    /**
     * 已收藏商品列表
     */
    public function favorite_listP()
    {
    	$current_lang = getCurrentLang(true);
    	
        $uid = I('uid');

        if ((!$uid)) {
            $this->myApiPrint('数据错误！');
        }
        $data1['data'] = array();

        $page = intval(I('post.page')) - 1;
        $page = $page > 0 ? $page * 10 : 0;

        $wherekey['f.uid'] = $uid;
        $wherekey['f.favorite'] = 1;
        $totalPage = M('favorite_product f')->where($wherekey)->count();
        $everyPage = '10';
        $pageString = $page . ',' . $everyPage;
        
        $field_name = 'p.name'.$current_lang;
        $data = M('favorite_product f')
            ->field("f.uid, f.productid, m.nickname, ".$field_name." as product_name, p.price as product_price, IFNULL(p.exchangenum,0) as product_exchangenum, p.totalnum as product_totalnum, CONCAT(p.img,'" . C('USER_COLS_STORE_SIZE') . "') as product_img")
            ->join('left join zc_member as m on m.id = f.uid')
            ->join('left join zc_product as p on p.id = f.productid')
            ->where($wherekey)->limit($pageString)->select();
        if (empty($data)) {
            $this->myApiPrint('找不到数据！', 400, $data1);
        }
        foreach ($data as $k => $row) {
            foreach ($row as $a => $v) {
                if (!$v) {
                    $data[$k][$a] = '';
                }
            }
        }
        $page1['totalPage'] = floor(($totalPage - 1) / 10) + 1;
        $page1['everyPage'] = $everyPage;
        $data1['data'] = $data;
        $data1['page'] = $page1;

        $this->myApiPrint('查询成功！', 400, $data1);
    }


    /**
     * 添加或修改支付宝账号
     *
     * @param uid 会员ID
     * @param account_ali 支付宝账号(手机号或邮箱)
     * @param account_ali_name 支付宝账号对应的用户名称(前端应提示用户账户及账户名的正确性)
     */
    public function update_ali_account_info()
    {
        $uid = I('post.uid');
        $data['account_ali'] = I('post.account_ali');
        $data['account_ali_name'] = I('post.account_ali_name');

        if (empty($uid) || empty($data['account_ali']) || empty($data['account_ali_name'])) {
            $this->myApiPrint('入参有空值', 300);
        }

        $affect_row = M('Member')->where(array('id' => $uid))->save($data);
        if ($affect_row) {
            $this->myApiPrint('更新成功', 400);
        } else {
            $this->myApiPrint('更新失败', 300);
        }
    }


    /**
     * 注册币转特供券
     */
    public function enrollToSupply()
    {
        $uid = I('post.uid');
        $amount = I('post.amount');

        $user = M('member')->where(array('id' => $uid))->find();
        if (!$user) {
            ajax_return('账号不存在');
        }
        //3.验证余额
        $om = new OrderModel();
        if (!$om->compareBalance($uid, Currency::Enroll, $amount)) {
            $this->myApiPrint('余额不足，无法转账', 300);
        }
        M()->startTrans();

        //4.明细+资金变更
        $arm = new AccountRecordModel();
        $res1 = $arm->add($uid, Currency::Enroll, CurrencyAction::EnrollToSupply, -$amount, $arm->getRecordAttach($uid, $user['nickname'], $user['img']), '注册币转换特供券'); //转出
        $res2 = $arm->add($uid, Currency::Supply, CurrencyAction::SupplyTransferEnroll, $amount, $arm->getRecordAttach($uid, $user['nickname'], $user['img']), '注册币转换特供券'); //转入

        if ($res1 !== false && $res2 !== false) {
            M()->commit();
            $this->myApiPrint('转账成功', 400);
        } else {
            M()->rollback();
            $this->myApiPrint('转账失败', 300);
        }

    }


    /**
     * 现金积分或公让宝互转
     *
     * @param sid 转出用户手机号
     * @param did 接收用户手机号
     * @param type 代币类型(1:现金积分,2:公让宝,3:锁定通证,4:提货券,5:报单币)
     * @param amount 转账金额
     * @param content 备注(默认为"转账")
     *
     * @description 方法中的参数用于内部其他方法调用
     */
    public function account_transfer()
    {
    	$EnjoyModel = new EnjoyModel();
    	
        $stel = I('post.sid');
        $dtel = I('post.did');
        $type = I('post.type');
        $amount = I('post.amount');
        $content = I('post.content');

        //1.验证参数
        $post = verify_account_transfer($stel, $dtel, $type, $amount, $this->CFG);

        //2.判断类型
        if ($type == 1) {
            $currency = Currency::Cash;
            $record_action1 = CurrencyAction::CashTransfer;
            $record_action2 = CurrencyAction::CashReceived;
        } elseif ($type == 2) {
            $currency = Currency::GoldCoin;
            $record_action1 = CurrencyAction::GoldCoinTransfer;
            $record_action2 = CurrencyAction::GoldCoinReceived;
        } elseif ($type == 3) {
        	$currency = Currency::Bonus;
        	$record_action1 = CurrencyAction::BonusTransfer;
        	$record_action2 = CurrencyAction::BonusReceived;
        } elseif ($type == 4) {
        	$this->myApiPrint('此功能停用');
        	$currency = Currency::ColorCoin;
        	$record_action1 = CurrencyAction::colorcoinTransfer;
        	$record_action2 = CurrencyAction::colorcoinReceived;
        } elseif ($type == 5) {
        	$currency = Currency::Supply;
        	$record_action1 = CurrencyAction::SupplyTransfer;
        	$record_action2 = CurrencyAction::SupplyReceived;
        } else {
            $this->myApiPrint('未知互转类型');
        }

        //3.验证余额
        $om = new OrderModel();
        if (!$om->compareBalance($post['suser']['id'], $currency, $amount)) {
            $this->myApiPrint('余额不足，无法转账', 300);
        }
        M()->startTrans();

        //4.明细+资金变更
        $arm = new AccountRecordModel();
        $res1 = $arm->add($post['suser']['id'], $currency, $record_action1, -$amount, $arm->getRecordAttach($post['duser']['id'], $post['duser']['nickname'], $post['duser']['img']), '转账'); //转出
        $res2 = $arm->add($post['duser']['id'], $currency, $record_action2, $amount, $arm->getRecordAttach($post['suser']['id'], $post['suser']['nickname'], $post['suser']['img']), '收到汇款'); //转入
        
        //扣除澳洲SKN股数
        $result3 = $EnjoyModel->transferUse($post['suser']['id'], $this->CFG['enjoy_transfer']);
        
        if ($res1 !== false && $res2 !== false && $result3 !== false) {
            M()->commit();
            $this->myApiPrint('转账成功', 400);
        } else {
            M()->rollback();
            $this->myApiPrint('转账失败', 300);
        }
    }

    /**
     * 彩分兑换现金积分
     *
     * @param uid 会员ID
     * @param amount 待转成现金积分的彩分数量
     */
    public function cai_2_cash()
    {
        $this->myApiPrint('抱歉，此功能已暂停使用!');
    }

    /**
     * 现金积分转公让宝
     *
     * @param uid 会员ID
     * @param amount 转换数量
     */
    public function cash_2_goldcoin()
    {
        //$this->myApiPrint('抱歉，此功能已禁用！',300);
        //exit;
        $uid = I('post.uid');
        $amount = I('post.amount');

        $post = verify_cash2goldcoin($uid, $amount);

        //验证余额
        $om = new OrderModel();
        if (!$om->compareBalance($uid, Currency::Cash, $amount)) {
            $this->myApiPrint('余额不足，无法转换', 300);
        }
        M()->startTrans();

        //插入明细
        $arm = new AccountRecordModel();
        $res1 = $arm->add($uid, Currency::Cash, CurrencyAction::CashToGoldCoin, -$amount, $arm->getRecordAttach($uid, $post['nickname'], $post['img']), '现金积分转换丰谷宝');
        $res2 = $arm->add($uid, Currency::GoldCoin, CurrencyAction::GoldCoinFromCash, $amount, $arm->getRecordAttach($uid, $post['nickname'], $post['img']), '成功转换丰谷宝');

        if ($res1 !== false && $res2 !== false) {
            M()->commit();
            $this->myApiPrint('现金积分转公让宝成功，获得丰谷宝' . $amount, 400);
        } else {
            M()->rollback();
            $this->myApiPrint('转换失败');
        }
    }

    /**
     * 保存用户微信基本信息接口
     *
     * @param uid 用户ID
     * @param weixin 绑定微信的基本信息数据(json格式)
     */
    public function save_member_weixin()
    {
        //$this->myApiPrint('系统提示：微信绑定功能已暂停！');
        $uid = I('post.uid');

        //对nickname进行处理
        $nickname = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $this->post['nickname']);

        $weixin = array(
            'headimgurl' => I('post.headimgurl'),
            'nickname' => $nickname,
            'openid' => I('post.openid'),
            'sex' => I('post.sex'),
            'unionid' => I('post.unionid'),
        );

        if (empty($uid)) {
            $this->myApiPrint('用户ID不能为空');
        }
        if (empty($weixin['openid'])) {
            $this->myApiPrint('用户openid获取失败');
        }
        if (empty($weixin['headimgurl'])) {
            $weixin['headimgurl'] = '/Uploads/head_sculpture/logo.png';
        }

        //验证用户是否还有未处理的提现数据
        $res = M('withdraw_cash')->where("uid = '$uid' and `status` = '0' and tiqu_type=1")->find();
        if ($res) {
            $this->myApiPrint('您还有未受理的提现记录，现在不能解绑！');
        }

        foreach ($weixin as $k => $val) {
            $val = trim($val);
            //如果昵称过滤后为空,则特殊处理
            if (empty($val) && $k == 'nickname') {
                $weixin[$k] = C('APP_TITLE');
            }
            //排除sex不能为空
            if (empty($val) && $k != 'sex' && $k != 'nickname') {
                $this->myApiPrint($k . '不能为空');
            }
        }

        $data = array(
            'weixin' => serialize($weixin)
        );

        $map['id'] = array('eq', $uid);
        $Member = M('Member');
        $status = $Member->where($map)->save($data);
        if ($status === false) {
            $this->myApiPrint('保存失败');
        } else {
            $this->myApiPrint('保存成功', 400, (Object)array());
        }
    }

    /**
     * 检测用户是否已绑定微信
     *
     * @param uid 用户ID
     */
    public function get_member_weixin_bind_status()
    {
        $uid = intval(I('post.uid'));

        if (empty($uid)) {
            $this->myApiPrint('用户ID不能为空');
        }

        $Member = M('member');
        $member_info = $Member->where('id=' . $uid)->field('weixin')->find();
        if (!$member_info || empty($member_info['weixin'])) {
            $this->myApiPrint('获取失败');
        }
        $member_info = unserialize($member_info['weixin']);

        //目前只传openid,nickname,headimgurl给APP端
        $member_info = array(
            'openid' => $member_info['openid'],
            'nickname' => $member_info['nickname'],
            'headimgurl' => $member_info['headimgurl']
        );

        //检测用户微信信息是否完整
        foreach ($member_info as $k => $v) {
            if (empty($v)) {
                $this->myApiPrint('微信信息不完整,请解绑后重新绑定');
                exit;
            }
        }

        $this->myApiPrint('获取成功', 400, $member_info);
    }


    /**
     * 检测用户是否已绑定支付宝
     *
     * @param uid 用户ID
     */
    public function get_member_alipay_bind_status()
    {
        $uid = intval(I('post.uid'));

        if (empty($uid)) {
            $this->myApiPrint('用户ID不能为空');
        }

        $member_info = M('user_affiliate')->where('user_id = ' . $uid)->find();
        if (empty($member_info['alipay_account'])) {
            $this->myApiPrint('未绑定支付宝');
        }
        //目前只传openid,nickname,headimgurl给APP端
        $okok = array(
            'openid' => $member_info['alipay_account'],
            'nickname' => $member_info['alipay_nick_name'],
            'headimgurl' => $member_info['alipay_avatar']
        );


        $this->myApiPrint('获取成功', 400, $okok);
    }

    /**
     * 取消用户微信授权
     *
     * @param uid 用户ID
     */
    public function clear_member_weixin_bind()
    {
        $uid = I('post.uid');

        if (empty($uid)) {
            $this->myApiPrint('用户ID不能为空');
        }
        //验证用户是否还有未处理的提现数据
        $res = M('withdraw_cash')->where("uid = '$uid' and `status` = '0'")->find();
        if ($res) {
            $this->myApiPrint('您还有未受理的提现记录，现在不能解绑！');
        }

        $Member = M('Member');
        $map['id'] = array('eq', $uid);
        $data = array(
            'weixin' => '',
        );
        $status = $Member->where($map)->save($data);
        if ($status === false) {
            $this->myApiPrint('取消授权失败,请稍后重试!');
        } else {
            $this->myApiPrint('已取消授权', 400, (Object)array());
        }
    }

    /**
     * 微信提现申请
     *
     * 手续费以提现申请提交时的比例为准
     *
     * @param uid  申请提现会员id
     * @param amount   提现金积分额
     * @param content 备注 (可为空)
     */
    public function withdraw_cash_apply_weixin()
    {
        if ($this->CFG['withdraw_switch_wechat'] == '关闭') {
            $this->myApiPrint('提现功能暂未开放');
        }
        $uid = trim(I('post.uid'));
        $amount = I('post.amount');
        $content = I('post.content');
        if (!validateExtend($amount, 'MONEY')) {
            $this->myApiPrint('金额格式不对！');
        }
        if ($amount < 0.01) {
            $this->myApiPrint('金额格式不对');
        }

        //参数
        $rule = verify_withdraw_by_weixin($uid, $amount, $this->CFG);

        // 计算手续费
        $commission = $amount * $rule['tiqu_fee_weixin'] / 100;

        //验证余额
        $withhold_total_amount = $amount + $commission;
        $om = new OrderModel();
        if (!$om->compareBalance($uid, Currency::Cash, $withhold_total_amount)) {
            $this->myApiPrint('余额不足，申请失败', 300);
        }
        M()->startTrans();

        $res = $om->withdrawByWechatAndCard(1, $uid, $amount, $commission, $content, $rule);
        if ($res) {
            M()->commit();
            $this->myApiPrint('提现处理中，请留意微信钱包到帐余额!', 400);
        } else {
            M()->rollback();
            $this->myApiPrint('申请失败!', 300);
        }
    }


    /**
     * 支付宝提现申请
     *
     * 手续费以提现申请提交时的比例为准
     *
     * @param uid  申请提现会员id
     * @param amount   提现金积分额
     * @param content 备注 (可为空)
     */
    public function withdraw_cash_apply_alipay()
    {
        if ($this->CFG['withdraw_switch_alipay'] == '关闭') {
            $this->myApiPrint('提现功能暂未开放');
        }
        $uid = trim(I('post.uid'));
        $amount = I('post.amount');
        $content = I('post.content');

        if (!validateExtend($amount, 'MONEY')) {
            $this->myApiPrint('金额格式不对！');
        }
        if ($amount < 0.01) {
            $this->myApiPrint('金额格式不对');
        }
        //参数
        $rule = verify_withdraw_by_alipay($uid, $amount, $this->CFG);

        // 计算手续费
        $commission = $amount * $rule['tiqu_fee'] / 100;

        //验证余额
        $withhold_total_amount = $amount + $commission;
        $om = new OrderModel();
        if (!$om->compareBalance($uid, Currency::Cash, $withhold_total_amount)) {
            $this->myApiPrint('余额不足，申请失败', 300);
        }
        M()->startTrans();

        $res = $om->withdrawByWechatAndCard(0, $uid, $amount, $commission, $content, $rule);
        if ($res) {
            M()->commit();
            $this->myApiPrint('提现处理中，请留意支付宝到帐余额!', 400);
        } else {
            M()->rollback();
            $this->myApiPrint('申请失败!', 300);
        }
    }


    /**
     * 提现-银行卡申请
     *
     * @param uid  申请提现会员id
     * @param amount   提现金积分额
     * @param content 备注 (可为空)
     */
    public function withdraw_cash_apply_cgbcard()
    {
    	$EnjoyModel = new EnjoyModel();
    	
        if ($this->CFG['withdraw_switch_bank'] == '关闭') {
            $this->myApiPrint('提现功能暂未开放');
        }
        $uid = trim(I('post.uid'));
        $amount = I('post.amount');
        $content = I('post.content');

        if (!validateExtend($amount, 'MONEY')) {
            $this->myApiPrint('金额格式不对！');
        }
        if ($amount < 0.01) {
            $this->myApiPrint('金额格式不对');
        }
        //参数
        $rule = verify_withdraw_by_bankcard($uid, $amount, $this->CFG);

        // 计算手续费
        $commission = $amount * $this->CFG['withdraw_fee'] / 100;

        //验证余额
        $withhold_total_amount = $amount + $commission;
        $om = new OrderModel();
        if (!$om->compareBalance($uid, Currency::Cash, $withhold_total_amount)) {
            $this->myApiPrint('余额不足，申请失败', 300);
        }
        M()->startTrans();

        $res = $om->withdrawByWechatAndCard(2, $uid, $amount, $commission, $content, $rule);
        
        //扣除澳洲SKN股数
        $result2 = $EnjoyModel->tixianUse($uid, $this->CFG['enjoy_tixian']);
        
        if ($res && $result2) {
            M()->commit();
            $this->myApiPrint('提现处理中，请留意银行卡到帐余额!', 400);
        } else {
            M()->rollback();
            $this->myApiPrint('申请失败!', 300);
        }

    }


    /**
     * 修改头像
     * Enter description here ...
     */
    public function modify_header()
    {
        $uid = I('post.uid');

        //处理图片
        /*
		$upload = new \Think\Upload();
		$upload->maxSize = 1024000*3;
		$upload->exts = array('jpg', 'gif', 'png', 'jpeg');
		$upload->rootPath = './Uploads/';
		$upload->savePath = '';
		$upload->autoSub = true;
		$upload->subName = 'store/'.date('Ymd');
		$info = $upload->upload();
		if (!$info) {
			$this->myApiPrint('请重新上传图片！',300);
		} else {
			$data['img'] = '/Uploads/head_sculpture/' . $info['img']['savepath'] . $info['img']['savename'];
		}
		*/

        $upload_config = array(
            'file' => $_FILES['img'],
            'path' => 'head_sculpture/',
        );
        $Upload = new UploadController($upload_config);
        $upload_info = $Upload->upload();
        if (empty($upload_info['error'])) {
            $data['img'] = $upload_info['data']['url'];
        } else {
            $this->myApiPrint($upload_info['error'], 300);
        }

        M('member')->where('id=' . $uid)->save($data);
        $this->myApiPrint('更新成功！', 400);
    }

    /**
     * 根据手机号获取用户
     * Enter description here ...
     */
    public function get_nickname()
    {
        $tel = I('post.tel');
        $user = M('member')->where("loginname='$tel'")->find();
        if ($user) {
            $this->myApiPrint('查询成功！', 400, array('nickname' => $user['truename'], 'username' => ''));
        } else {
            $this->myApiPrint('查询成功！', 400, array('nickname' => ''));
        }
    }

    /**
     * 根据uid获取推荐人
     * Enter description here ...
     */
    public function getReuser()
    {
        $uid = intval(I('post.uid'));
        $member = M('member')->field('id, reid')->find($uid);
        $remember = M('member')->field('id, loginname, nickname')->find($member['reid']);

        //暂停返回推荐人信息(使用返空值方式):20170328
        $remember = array(
            'id' => $member['reid'],
            'loginname' => '',
            'nickname' => '',
        );

        if ($remember) {
            $this->myApiPrint('查询成功！', 400, $remember);
        } else {
            $this->myApiPrint('没有数据！', 300, '');
        }
    }

    /**
     * 修改登录密码
     *
     * by ryo
     */
    public function passUpdate()
    {

        if (!I('id') || !I('pass') || empty(I('post.old_password'))) {
            $this->myApiPrint('参数有空值，请检查', 300);
        }

        $member = M('member');
        $where['password'] = md5(I('post.old_password'));
        $where['id'] = I('post.id');
        $row = $member->where($where)->find();
        if (!$row) {
            $this->myApiPrint('旧密码不正确，请重新输入！');
        }
        $list = $member->field('entry')->where(array('id' => I('id')))->find();
        $member->password = md5(I('pass'));
        $flag = $member->where('id=\'' . I('id') . '\'')->save();

        //同时更新商家管理列表
        $mem = $member->field('username')->where('id=' . I('post.id') . ' and level in(3,4)')->find();
        $whereSql['username'] = $mem['username'];
        if (!empty($whereSql['username'])) {
            $data_a['password'] = md5(I('pass'));
            M('user')->where($whereSql)->save($data_a);
        }

        if ($flag !== false) {
            $this->myApiPrint('更新成功', 400);
        }
        $this->myApiPrint('更新失败');
    }

    /**
     * 用户收款
     *
     * @param $uid 付款人ID
     * @param $receive_uid 收款人ID
     * @param $pay_type 兑换类型: 1支付宝,2微信,3其他,4现金积分,5公让宝
     * @param $amount 兑换金额
     */
    public function userReceive()
    {
        //直接调用买单接口，这个接口废弃
        exit;


    }

    /**
     * 用户退出
     */
    public function loginout()
    {
        $status = $this->appLoginout();

        if ($status) {
            $this->myApiPrint('退出成功', 400);
        } else {
            $this->myApiPrint('退出失败,请重试', 300);
        }
    }

    /**
     * 银行卡绑定说明
     * Enter description here ...
     */
    public function bindcarddescp()
    {
        $descp = C('PARAMETER_CONFIG.WITHDRAW_BANK_MSG')['bind'];
        $this->myApiPrint('查询成功', 400, $descp);
    }

    /**
     * GRB 绑定银行卡
     * 
     * @method POST
     * 
     * @param int $uid 用户ID
     * @param string $phone 手机号
     * @param string $cardNo 银行卡号
     * @param string $bankName 银行名称
     * @param string $name 开户名
     */
    public function savebankcard()
    {
   		$data = [
    		'user_id' => $this->post['uid'],
    		'phone' => $this->post['phone'],
    		'cardNo' => $this->post['cardNo'],
    		'bankName' => $this->post['bankName'],
    		'name' => $this->post['name'],
    		'created_time' => time(),
    		'updated_time' => time()
    	];
   		
   		//兼容旧银行卡绑定相关功能
   		if (!empty($this->post['inaccname'])) {
   			$data['name'] = $this->post['inaccname'];
   		}
   		if (!empty($this->post['inacc'])) {
   			$data['cardNo'] = $this->post['inacc'];
   		}
   		if (!empty($this->post['inaccbank'])) {
   			$data['bankName'] = $this->post['inaccbank'];
   		}
    	
    	if (!validateExtend($data['user_id'], 'NUMBER')) {
    		$this->myApiPrint('用户ID格式有误');
    	}
    	if (!empty($data['phone']) && !validateExtend($data['phone'], 'MOBILE')) {
    		$this->myApiPrint('手机号格式有误');
    	}
    	$cardNoValidate = bankCardValidate($data['cardNo']);
    	if (!$cardNoValidate) {
    		$this->myApiPrint('银行卡号格式有误');
    	}
    	if (!validateExtend($data['bankName'], 'CHS')) {
    		$this->myApiPrint('银行名称格式有误');
    	}
    	if (!validateExtend($data['name'], 'CHS')) {
    		$this->myApiPrint('开户名格式有误');
    	}
    	
    	$info = M('BankBind')->where('user_id='.$data['user_id'])->find();
    	if ($info) { //修改
    		unset($data['created_time']);
    		$result = M('BankBind')->where('user_id='.$data['user_id'])->save($data);
    	} else { //新增
    		$result = M('BankBind')->add($data);
    	}
    	
    	if (!$result || $result==null) {
    		$this->myApiPrint('操作失败');
    	}
    	
    	$this->myApiPrint('操作成功', 400);
    }

    /**
     * 用户绑定银行卡信息
     *
     * @param int uid 用户ID
     */
    public function getMemberBankcardInfo()
    {
        $map = array();

        $uid = $this->post['uid'];

        if (!validateExtend($uid, 'NUMBER')) {
            $this->myApiPrint('参数格式有误', 300);
        }

        $data = array();

        $map['user_id'] = array('eq', $uid);
        $info = M('BankBind')->where($map)->order('id desc')->find();
        if (!$info) {
            $data['is_bind_bankcard'] = '0';
        } else {
            $data = $info;
            $data['is_bind_bankcard'] = '1';
        }
        
        //兼容旧银行卡绑定相关功能
        $data['inaccname'] = $data['name'];
        $data['inacc'] = $data['cardno'];
        $data['inaccbank'] = $data['bankname'];

        $this->myApiPrint('查询成功', 400, $data);
    }


    public function personaldata()
    {
        $id = I('post.uid');
        $where['id'] = $id;
        $user = M('member')->field('id, loginname, nickname, img, reg_time, weixin')->where($where)->find();

        if ($user) {
            //增加是否绑定银行卡信息
            $bankcard_info = M('BankBind')->where('user_id=' . $id)->find();
            $user['is_bind_bankcard'] = $bankcard_info ? '1' : '0';

            //是否绑定微信
            $user['is_bind_wechat'] = empty($user['weixin']) ? '0' : '1';
            $weixin_info = unserialize($user['weixin']);
            $user['wechat_img'] = $weixin_info['headimgurl'] . '';

            //是否绑定支付宝
            $af = M('user_affiliate')->where('user_id=' . $id)->find();
            $user['is_bind_alipay'] = empty($af['alipay_account']) ? '0' : '1';
            $user['alipay_img'] = $af['alipay_avatar'] . '';

            //是否允许绑定微信
            $user['iscan_bind_wechat'] = 1;
            $user['iscan_bind_alipay'] = 1;

            //实名认证
            $certification = M('certification')->where('user_id = ' . $id)->find();
            $user['certification_msg'] = '';
            if (empty($certification)) {
                $user['certification_status'] = -1; //未认证
            } else {
                $user['certification_status'] = $certification['certification_status'];
                if ($certification['certification_status'] == 0) {
                    $user['certification_msg'] = '实名认证审核中';
                } elseif ($certification['certification_status'] == 1) {
                    $user['certification_msg'] = '实名认证被驳回：' . $certification['certification_remark'];
                }
            }
            if ($user['reg_time'] < 1511165083) {
                $user['certification_status'] = 2; //老用户直接不虚实名认证
            }

            $user = Image::formatItem($user, ['img']);

            $this->myApiPrint('查询成功', 400, $user);
        }
    }

    /**
     * 会员签到 [此功能停用]
     */
    public function checkin()
    {
    	$this->myApiPrint('此功能停用');
    	
        $uid = I('post.uid');
        //验证操作
        verify_checkin($uid);
        //处理业务
        M()->startTrans();

        $mm = new MemberModel();
        $res = $mm->userCheckIn($uid, $this->CFG);
        if ($res) {
            M()->commit();
            $this->myApiPrint('签到成功，获得' . $res . '澳洲SKN股数', 400);
        } else {
            M()->rollback();
            $this->myApiPrint('签到失败');
        }
    }
    
    /**
     * 会员签到中心
     * 
     * @param int $user_id 用户ID
     */
    public function checkinCenter() {
    	$AccountCheckin = M('AccountCheckin');
    	$mm = new MemberModel();
    	
    	$uid = $this->post['user_id'];
    	
    	$data = [];
    	
    	//验证用户
    	verify_checkin_user($uid);
    	
    	M()->startTrans();
    	
    	$result = false;
    	$check_checkin = verify_checkin($uid);
    	if (!$check_checkin) {
    		$result = $mm->userCheckIn($uid, $this->CFG);
    	}
    	
    	$where = [
	    	'user_id' => ['eq', $uid],
	    	'checkin_addtime' => [['egt', strtotime(date('Y-m-d 00:00:00'))], ['elt', time()], 'and'],
    	];
    	
    	//当天日期 + 签到获取金币
    	$data['checkin_today'] = date('Y-m-d');
    	$data['checkin_today_amount'] = $AccountCheckin->where($where)->sum('checkin_amount');
    	$data['checkin_today_amount'] = sprintf('%.2f', $data['checkin_today_amount']);
    	
    	$where['checkin_addtime'] = [['egt', strtotime(date('Y-m-01'))], ['elt', time()], 'and'];
    	
    	//当月签到天数
    	$data['checkin_days_count'] = $AccountCheckin->where($where)->count();
    	
    	//当月签到日期
    	$days_list = $AccountCheckin->where($where)->field('checkin_addtime')->select();
    	foreach ($days_list as $k=>$v) {
    		$data['checkin_days_list'][] = [
    			'year' => date('Y', $v['checkin_addtime']),
    			'month' => date('m', $v['checkin_addtime']),
    			'day' => date('d', $v['checkin_addtime'])
    		];
    	}
    	
    	//计算积累金币
    	unset($where['checkin_addtime']);
    	$data['checkin_amount'] = $AccountCheckin->where($where)->sum('checkin_amount');
    	$data['checkin_amount'] = sprintf('%.2f', $data['checkin_amount']);
    	
    	if ($result) {
    		M()->commit();
    		
    		//今日签到获取澳洲SKN股数
    		$data['checkin_today_amount'] = sprintf('%.2f', $result);
    		
    		$this->myApiPrint('签到成功，获得' . $result . '澳洲SKN股数', 400, $data);
    	} else {
    		M()->rollback();
    		$this->myApiPrint('今日已签到', 400, $data);
    	}
    }

    /**
     * 看广告
     */
    public function watchads()
    {
        $ad_id = intval(I('post.id'));
        $user_id = intval(I('post.uid'));

        M()->startTrans();
        $ProcedureModel = new ProcedureModel();
        $res = $ProcedureModel->execute('Event_adViewed', "$user_id,$ad_id", '@status,@message,@error');
        if ($res && $res[0]['@error'] == 0 && $res[0]['@status'] == 1) {
            M()->commit();
            $this->myApiPrint($res[0]['@message'], 400);
        } else {
            M()->rollback();
            $this->myApiPrint($res[0]['@message']);
        }
    }

    /**
     * 检测是否认证
     */
    public function isAuthentication()
    {
        $user_id = intval(I('post.uid'));
        $user = M('member')->where('id=' . $user_id)->find();
        if ($user['reg_time'] < 1511165083) {
            $this->myApiPrint('已认证', 400, "2");
        }
        
        //排除买单时用户未实名认证默认收货地址信息记入实名认证表的数据
        $map = [
        	'user_id' => ['eq', $user_id],
        	'certification_identify_1' => ['neq', ''],
        	'certification_identify_2' => ['neq', ''],
        	'certification_identify_3' => ['neq', '']
        ];
        $affiliate = M('certification')->where($map)->find();
        if (empty($affiliate)) {
            $this->myApiPrint('未实名认证', 400, -1);
        }

        if ($affiliate['certification_status'] == 0) {
            $this->myApiPrint('实名认证正在审核中，审核时间为1-3个工作日', 400, $affiliate['certification_status']);
        } elseif ($affiliate['certification_status'] == 1) {
            $this->myApiPrint('实名认证被驳回', 400, $affiliate['certification_status']);
        }
        $this->myApiPrint('已认证', 400, $affiliate['certification_status']);
    }

    /**
     * 实名认证
     * @return number
     */
    public function authentication()
    {
    	$current_lang = getCurrentLang();
    	
        $user_id = intval(I('post.uid'));
        //$truename = I('post.truename');
        //if (!validateExtend($truename, 'CHS')) {
        //	$this->myApiPrint('姓名只能是中文！');
        //}

        $province = $this->post['province'];
        $city = $this->post['city'];
        $country = $this->post['country'];

        if ($current_lang == 'zh-cn') {
        	if (!validateExtend($province, 'CHS') || !validateExtend($city, 'CHS') || !validateExtend($country, 'CHS')) {
        		$this->myApiPrint('请选择省市区');
        	}
        }

        $data['province'] = $province;
        $data['city'] = $city;
        $data['country'] = $country;

        $upload_config = array(
            'file' => 'multi',
            'exts' => array('jpg', 'png', 'gif', 'jpeg'),
            'path' => 'comment/' . date('Ymd')
        );
        $Upload = new UploadController($upload_config);
        $info = $Upload->upload();
        if (!empty($info['error'])) {
            $this->myApiPrint('请一次性提交3张照片:' . $info['error']);
        } else {
            $affiliate = M('certification')->where('user_id = ' . $user_id)->find();
            
            $data['user_id'] = $user_id;
            $data['certification_status'] = 0;
            $data['certification_addtime'] = time();
            $data['certification_uptime'] = time();
            //第一张图片
            if ($info['data']['photo1']) {
                $data['certification_identify_1'] = $info['data']['photo1']['url'];
            } else {
                $this->myApiPrint('请一次性提交3张照片:002');
            }
            if ($info['data']['photo2']) {
                $data['certification_identify_2'] = $info['data']['photo2']['url'];
            } else {
                $this->myApiPrint('请一次性提交3张照片:003');
            }
            if ($info['data']['photo3']) {
                $data['certification_identify_3'] = $info['data']['photo3']['url'];
            } else {
                $this->myApiPrint('请一次性提交3张照片:004');
            }

            if ($affiliate) {
            	unset($data['certification_addtime']);
            	
            	//如果已存在 买单时用户未实名认证默认收货地址信息记入实名认证表的数据,则省市区信息不再记入
            	unset($data['province']);
            	unset($data['city']);
            	unset($data['country']);
            	
                M('certification')->where('certification_id=' . $affiliate['certification_id'])->save($data);
            } else {
                M('certification')->add($data);
            }

            //M('member')->where('id='.$user_id)->save(array('truename'=>$truename));

            $this->myApiPrint('提交成功，请等待审核！审核时间为1-3个工作日！', 400);
        }
    }

    /**
     * 获取绑定支付宝授权码
     */
    public function getalipayauthcode()
    {
        //支付宝
        Vendor('Alipay.AliPay#Api');
        $aliPay = new AliPay();
        $user_id = intval(I('post.uid'));
        $res = $aliPay->getAccountAuth($user_id);
        $this->myApiPrint('ok', 400, $res);
    }


    /**
     * 绑定支付宝
     */
    public function bind_zfb()
    {
        $uid = intval(I('post.uid'));
        $ali_account = intval(I('post.ali_account')); //数字，用户id
        $auth_code = I('post.auth_code');  //授权码
        $ww['user_id'] = $uid;
        $user = M('user_affiliate')->where($ww)->find();
        if (!$user) {
            M('user_affiliate')->add($ww);
        }
        $user = M('user_affiliate')->where($ww)->find();
        if ($user) {
            if ($user['alipay_account'] != '') {
                $this->myApiPrint('已绑定支付宝');
            }
            //验证用户是否还有未处理的提现数据
            $res = M('withdraw_cash')->where("uid = '$uid' and `status` = '0' and tiqu_type=0")->find();
            if ($res) {
                $this->myApiPrint('您还有未受理的提现记录，现在不能解绑！');
            }

            //支付宝授权用户信息
            Vendor('Alipay.AliPay#Api');
            $aliPay = new AliPay();
            $alipayuser = $aliPay->getAuthUserInfo($auth_code);

            $upali['alipay_account'] = $alipayuser['user_id'];
            $upali['alipay_avatar'] = $alipayuser['avatar'];
            $upali['alipay_nick_name'] = $alipayuser['nick_name'];
            M('user_affiliate')->where($ww)->save($upali);
            $this->myApiPrint('绑定成功', 400);
        } else {
            $this->myApiPrint('用户不存在');
        }
    }

    /**
     * 解除绑定支付宝
     */
    public function unbind_zfb()
    {
        $uid = intval(I('post.uid'));
        $ww['user_id'] = $uid;
        $user = M('user_affiliate')->where($ww)->find();
        if ($user) {
            //验证用户是否还有未处理的提现数据
            $res = M('withdraw_cash')->where("uid = '$uid' and `status` = '0' and tiqu_type=0")->find();
            if ($res) {
                $this->myApiPrint('您还有未受理的提现记录，现在不能解绑！');
            }
            M('user_affiliate')->where($ww)->save(array(
                'alipay_account' => '',
                'alipay_avatar' => '',
                'alipay_nick_name' => ''
            ));
            $this->myApiPrint('解除成功', 400);
        } else {
            M('user_affiliate')->add($ww);
            $this->myApiPrint('用户不存在');
        }
    }
    
    /**
     * YY语音下载页
     */
    public function ttDown() {
    	$data = [];
    	
    	//下载说明
    	$data['down_description'] = '下载说明';
    	
    	//下载链接
    	$data['down_url'] = 'http://www.yy.com/';
    	
    	$this->myApiPrint('查询成功', 400, $data);
    }
    
    /**
     * 记录YY语音下载
     * 
     * @method POST
     * 
     * @param int $user_id 用户ID
     */
    public function ttDownRecord() {
    	$user_id = $this->post['user_id'];
    	
    	if (!validateExtend($user_id, 'NUMBER')) {
    		$this->myApiPrint('参数格式有误');
    	}
    	
    	$member_info = M('Member')->where('id='.$user_id)->field('is_tt')->find();
    	if (!$member_info) {
    		$this->myApiPrint('未查询到相关用户信息');
    	}
    	
    	if ($member_info['is_tt'])  {
    		$this->myApiPrint('已有记录', 400);
    	}
    	
    	$data_member = [
    		'is_tt' => 1
    	];
    	$result = M('Member')->where('id='.$user_id)->save($data_member);
    	if ($result === false) {
    		$this->myApiPrint('记录失败');
    	}
    		
    	$this->myApiPrint('记录成功', 400);
    }
    
    /**
     * 分享朋友圈赠送澳洲SKN股数
     * 
     * @method POST
     * 
     * @param int $user_id 用户ID
     */
    public function giveEnjoyByShare() {
    	$EnjoyModel = new EnjoyModel();
    	
    	$user_id = $this->post['user_id'];
    	 
    	if (!validateExtend($user_id, 'NUMBER')) {
    		$this->myApiPrint('参数格式有误');
    	}
    	
    	M()->startTrans();
    	
    	//检测是否超出每天分享次数限制
    	$map_share = [
    		'user_id' => ['eq', $user_id],
    		'share_addtime' => [['egt', strtotime(date('Y-m-d 00:00:00'))], ['elt', time()], 'and']
    	];
    	$share_count = M('AccountShare')->where($map_share)->count();
    	if ($share_count >= $this->CFG['enjoy_share_count']) {
    		$this->myApiPrint('分享成功');
    	}
    	
    	$result1 = $EnjoyModel->shareGive($user_id, $this->CFG['enjoy_share']);
    	
    	$data_share = [
	    	'user_id' => $user_id,
	    	'share_addtime' => time(),
	    	'share_amount' => $this->CFG['enjoy_share']
    	];
    	$result2 = M('AccountShare')->add($data_share);
    	
   		if ($result1 !== false  && $result2 !== false && $result2 !== null) {
   			M()->commit();
            $this->myApiPrint('分享成功，获得' . $this->CFG['enjoy_share'] . '澳洲SKN股数', 400);
        } else {
        	M()->rollback();
            $this->myApiPrint('分享赠送失败');
        }
    }
    
    /**
     * 整合所有货币明细接口
     *
     * @param int uid 会员ID
     * @param int month 日期
     * @param int tag 收入/支出
     * @param int page 分页参数（page=0显示1-10条数据）
     * @param string currency 币种[默认cash](cash:现金积分, goldcoin:公让宝, bonus:锁定通证, colorcoin:提货券, enroll:兑换券, supply:报单币, enjoy:澳洲SKN股数)
     */
    public function currency_details()
    {
    	$wm = new WithdrawModel();
    	$arm = new AccountRecordModel();
    	
    	$uid = $this->post['uid'];
    	$month = $this->post['month'];
    	$tag = $this->post['tag'];  //1=收入  0=支出*/
    	$pn = $this->post['page'];
    	$currency = $this->post['currency'];
    
    	//验证参数
    	$month_suffix = verify_cash_list($uid, $month, $tag);
    
    	$currency = empty($currency) ? 'cash' : $currency;
    	switch ($currency) {
    		case 'cash':
    			$currency = Currency::Cash;
    			break;
    		case 'goldcoin':
    			$currency = Currency::GoldCoin;
    			break;
    		case 'bonus':
    			$currency = Currency::Bonus;
    			break;
    		case 'colorcoin':
    			$currency = Currency::ColorCoin;
    			break;
    		case 'enroll':
    			$currency = Currency::Enroll;
    			break;
    		case 'supply':
    			$currency = Currency::Supply;
    			break;
    		case 'enjoy':
    			$currency = Currency::Enjoy;
    			break;
    		default:
    			$this->myApiPrint('未知币种类型');
    	}
    
    	//加载数据
    	$data = $arm->getPageList($uid, $currency, $month_suffix, $pn, $tag, 10, '', false);
    
    	$tagstr = '';
    	if ($tag == 1) {
    		$tagstr = '+';
    	}
    	//处理数据
    	$return = array();
    	foreach ($data['list'] as $k => $v) {
    		if ($v['record_amount'] < 0.01 && $v['record_amount'] > -0.01) {
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
    
    		//处理附件信息
    		$obj = json_decode($v['record_attach'], true);
    		$attach = $arm->initAtach($obj, $currency, $month_suffix, $v['record_id'], $v['record_action']);
    		$row['from_name'] = $arm->getFinalName($attach['from_uid'], $attach['from_name']);
    		$row['from_pic'] = Image::url($attach['pic'], 'oss');
    		
    		//转入转出记录添加用户信息
    		$in_out_action = [109,152, 209,252, 318,360, 405,452, 605,652, 718,760, 808,852];
    		if (in_array($v['record_action'], $in_out_action)) {
	    		$from_loginname = M('Member')->where('id='.$attach['from_uid'])->getField('loginname');
	    		if ($v['record_amount'] > 0) {
	    			$row['action'] = '收到'. $attach['from_name']. "({$from_loginname})". $row['action'];
	    		} else {
	    			$row['action'] = $row['action']. $attach['from_name']. "({$from_loginname})";
	    		}
    		}
    		
    		//处理提现进度
    		if ($v['record_action'] == CurrencyAction::CashTixian) {
    			$row['status'] = $wm->getStatus($obj['serial_num']);
    		}
    		if ($v['record_action'] == CurrencyAction::CashTixianShouxufei) {
    			$row['status'] = $wm->getStatus($obj['serial_num']);
    		}
    		
    		//转让到公共市场明细增加第三方信息
    		if ($v['record_action'] == CurrencyAction::GoldCoinTransferToGRB) {
    			$wallet_type_config = C('FIELD_CONFIG')['trade']['type'];
    			$wallet_type = isset($wallet_type_config[$obj['type']]) ? $wallet_type_config[$obj['type']] : 'unknown';
    			$row['action'] = $row['action']. "({$wallet_type})";
    		}
    		
    		$return[] = $row;
    	}
    
    	$data['list'] = $return;
    
    	$this->myApiPrint('查询成功！', 400, $data);
    }
    
    /**
     * 提现开关
     */
    public function tixianSwitch() {
    	$data = [
    		'switch' => true,
    		'intro' => ''
    	];
    	if ($this->CFG['withdraw_switch_bank'] == '关闭' && $this->CFG['withdraw_switch_wechat'] == '关闭' && $this->CFG['withdraw_switch_alipay'] == '关闭') {
    		$data['switch'] = false;
    		$data['intro'] = '提现功能暂未开放';
    	}
    	
    	$this->myApiPrint('获取成功', 400, $data);
    }
    
    /**
     * 用户钱包地址和说明信息
     * 
     * @method POST
     * 
     * @param int user_id 用户ID
     * -- @param string type 钱包类型(AJS:澳交所,ZWY:中网云,SLU:SLU),默认:ZWY
     */
    public function userWalletInfo() {
    	$current_lang = getCurrentLang(true);
    	
    	$user_id = $this->post['user_id'];
//     	$type = empty($this->post['type']) ? 'ZWY' : $this->post['type'];
    	
    	if (!validateExtend($user_id, 'NUMBER')) {
    		$this->myApiPrint('会员ID格式有误');
    	}
    	
    	$this->autoGenerateWalletAddress($user_id);
    	
//     	$type_config = C('FIELD_CONFIG')['goldcoin_prices']['type'];
//     	if (!array_key_exists($type, $type_config)) {
//     		$this->myApiPrint('未知钱包类型');
//     	}
    	
    	$field = 'zhongwy_wallet_address,wallet_address,wallet_address_2,slu_wallet_address';
    	$wallet_address_info = M('UserAffiliate')->where('user_id='.$user_id)->field($field)->find();
    	
//    	if ($type=='ZWY') {
//    		$wallet_address = $wallet_address_info['zhongwy_wallet_address'];
//    	} elseif ($type == 'AJS') {
//    		$wallet_address = $wallet_address_info['wallet_address']. ','. $wallet_address_info['wallet_address_2'];
//    	} elseif ($type == 'SLU') {
//    		$wallet_address = $wallet_address_info['slu_wallet_address'];
//    	}
    	
//    	$wallet_address = (String)$wallet_address;
    	
    	$data['item'] = [
    		'wallet' => [
    			['address' => (String)$wallet_address_info['zhongwy_wallet_address'], 'title' => '查看信企贵交钱包地址', 'tag' => 'ZWY'],
//     			['address' => (String)$wallet_address_info['wallet_address'], 'title' => '查看澳交所钱包地址', 'tag' => 'AJS'],
//    			['address' => (String)$wallet_address_info['wallet_address_2'], 'title' => '查看AOGEX钱包地址', 'tag' => 'AJS'],
//    			['address' => (String)$wallet_address_info['slu_wallet_address'], 'title' => '查看Silk Trader钱包地址', 'tag' => 'SLU'],
    		],
    		'caption' => $this->CFG['wallet_caption'.$current_lang]
    	];
    	
    	$this->myApiPrint('查询成功', 400, $data);
    }
    
    /**
     * 判断用户是否已分配钱包地址,无则分配
     * 
     * @param int $user_id 用户ID
     */
    public function autoGenerateWalletAddress($user_id) {
    	$wallet_platform = ['ZWY', 'AJS', 'SLU', 'ETH'];
    	
    	foreach ($wallet_platform as $k=>$v) {
    		$WalletModel = new WalletModel($v);
    		 
    		$data_user_affiliate = [];
    		 
    		if ($v == 'ZWY') {
    			$user_affiliate_info = M('UserAffiliate')->where('user_id='.$user_id)->field('zhongwy_wallet_address')->find();
    	
    			if (!$user_affiliate_info) {
    				$data_user_affiliate = [
    					'user_id' => $user_id,
    					"zhongwy_wallet_address" => $WalletModel->getNewAddress($user_id),
    				];
    				M('UserAffiliate')->add($data_user_affiliate);
    			} else {
    				if (empty($user_affiliate_info['zhongwy_wallet_address'])) {
    					$data_user_affiliate = [
	    					"zhongwy_wallet_address" => $WalletModel->getNewAddress($user_id),
    					];
    					M('UserAffiliate')->where('user_id='.$user_id)->save($data_user_affiliate);
    				}
    			}
    		} elseif ($v == 'AJS') {
//    			$user_affiliate_info = M('UserAffiliate')->where('user_id='.$user_id)->field('wallet_address,wallet_address_2')->find();
//
//    			if (!$user_affiliate_info) {
//    				$data_user_affiliate = [
//    					'user_id' => $user_id,
//    					'wallet_address' => $WalletModel->getNewAddress($user_id),
//    					'wallet_address_2' => $WalletModel->getNewAddress($user_id),
//    				];
//    				M('UserAffiliate')->add($data_user_affiliate);
//    			} else {
//    				if (empty($user_affiliate_info['wallet_address'])) {
//    					$data_user_affiliate = [
//	    					'wallet_address' => $WalletModel->getNewAddress($user_id),
//    					];
//    					M('UserAffiliate')->where('user_id='.$user_id)->save($data_user_affiliate);
//    				}
//
//    				if (empty($user_affiliate_info['wallet_address_2'])) {
//    					$data_user_affiliate = [
//	    					'wallet_address_2' => $WalletModel->getNewAddress($user_id),
//	    				];
//    					M('UserAffiliate')->where('user_id='.$user_id)->save($data_user_affiliate);
//    				}
//    			}
    		} elseif ($v == 'SLU') {
//    			$user_affiliate_info = M('UserAffiliate')->where('user_id='.$user_id)->field('slu_wallet_address')->find();
//
//    			if (!$user_affiliate_info) {
//    				$data_user_affiliate = [
//	    				'user_id' => $user_id,
//	    				"slu_wallet_address" => $WalletModel->getNewAddress($user_id),
//    				];
//    				M('UserAffiliate')->add($data_user_affiliate);
//    			} else {
//    				if (empty($user_affiliate_info['slu_wallet_address'])) {
//    					$data_user_affiliate = [
//    						"slu_wallet_address" => $WalletModel->getNewAddress($user_id),
//    					];
//    					M('UserAffiliate')->where('user_id='.$user_id)->save($data_user_affiliate);
//    				}
//    			}
    		} elseif ($v == 'ETH') {
//    			$user_affiliate_info = M('UserAffiliate')->where('user_id='.$user_id)->field('eth_wallet_address')->find();
//
//    			if (!$user_affiliate_info) {
//    				$data_user_affiliate = [
//	    				'user_id' => $user_id,
//	    				"eth_wallet_address" => $WalletModel->getNewAddress($user_id),
//    				];
//    				M('UserAffiliate')->add($data_user_affiliate);
//    			} else {
//    				if (empty($user_affiliate_info['eth_wallet_address'])) {
//    					$data_user_affiliate = [
//    						"eth_wallet_address" => $WalletModel->getNewAddress($user_id),
//    					];
//    					M('UserAffiliate')->where('user_id='.$user_id)->save($data_user_affiliate);
//    				}
//    			}
    		}
    	}
    }

}

?>