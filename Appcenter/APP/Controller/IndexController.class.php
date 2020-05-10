<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | APP首页相关接口
// +----------------------------------------------------------------------
namespace APP\Controller;

use Common\Controller\ApiController;
use V4\Model\OrderModel;
use V4\Model\PaymentMethod;
use V4\Model\Currency;
use V4\Model\AccountRecordModel;
use V4\Model\CurrencyAction;
use V4\Model\ProductModel;
use V4\Model\Image;
use V4\Model\GjjModel;

class IndexController extends ApiController
{

    /**
     * app启动必须加载的
     * Enter description here ...
     */
    public function init()
    {
    	$current_lang = getCurrentLang(true);
        //检测开启 启动阅读公告
        /*
        if(C('PARAMETER_CONFIG.LOADING_MUST_READ_OPEN') == 1){
            $this->myApiPrint('查询成功',400,C('PARAMETER_CONFIG.LOADING_MUST_READ'));
        }else{
            $this->myApiPrint('查询成功',401,'');
        }
        */
        $read = C('PARAMETER_CONFIG.LOADING_MUST_READ_OPEN');

        //锁定的用户不显示弹窗
        if (empty($this->app_common_data['uid'])) {
            $read = '';
        } else {
            $user_lock = M('member')->where('id = ' . $this->app_common_data['uid'])->getField('is_lock');
            if ($user_lock == 1) {
                $read = '';
            }
        }
        $msg = htmlspecialchars_decode(C('PARAMETER_CONFIG.LOADING_MUST_READ'.$current_lang));
        $data = array(
            'is_must_read' => ($read == '') ? '' : $read,
            'msg' => ($msg == '') ? '' : $msg,
        );

        $this->myApiPrint('查询成功', 400, $data);
    }


    /**
     * APP首页信息
     *
     * @param city 城市名
     * @param page 商家活动分页参数
     */
    public function index()
    {
    	$current_lang = getCurrentLang(true);
    	
        $city = I('post.city');
        $city = empty($city) ? '眉山市' : $city;
        //从缓存拿数据
        S(array('type' => 'file', 'expire' => 60));
        $cache_key = md5($city);
        $data = S($cache_key);
        $data = null;
        if (empty($data)) {
            //图片
            $field_car_title = 'car_title'.$current_lang.' as car_title'; 
            $carousel = M('carousel')->field("car_id,".$field_car_title.",car_image,uid,cid,h5_path")->where('car_type=0 and is_hidden=0')->order('sort desc,car_id desc')->select();

            foreach ($carousel as $key => $item) {
                $item['car_image'] = substr($item['car_image'], 1);
                $carousel[$key] = $item;
                
//                 $carousel[$key]['id'] = $item['car_id'];
                $carousel[$key]['title'] = $item['car_title'];
                $carousel[$key]['cover'] = $item['car_image'];
                $carousel[$key]['link'] = getLunboLink($item['car_id']);
                
                //广告类型
                $carousel[$key]['type'] = getAdvType($item['car_id']);
            }


            //查询商品列表-分页,从0开始
            $post_page = intval(I('post.page'));
            if ($post_page < 1) {
                $post_page = 1;
            }
            $everyPage = '10';

            $field_block_name = 'b.block_name'.$current_lang.' as block_name';
            $field_name = 'p.`name'.$current_lang.'` as name';
            $list = M('product p')->field('p.id, '.$field_name.', p.img, p.price, p.totalnum, p.exchangenum, '.$field_block_name.', a.block_id')
                ->join('left join zc_product_affiliate a on a.product_id = p.id')
                ->join('left join zc_block b on b.block_id = a.block_id')
                ->where([
                    'p.status' => 0,
                    'p.manage_status' => 1,
//                     'b.block_id' => ['not in', ( C('GIFT_PACKAGE_BLOCK_ID').','.C('GJJ_BLOCK_ID').','.C('GRB_EXCHANGE_BLOCK_ID') )],
                	'b.block_id' => ['eq', C('GRB_EXCHANGE_BLOCK_ID')],
                	'b.block_enabled' => ['eq', 1],
                    'a.affiliate_deleted' => 0
                ])
                ->limit($everyPage)->page($post_page)
                ->order('p.ishot desc,p.id asc')
                ->select();
            $pm = new ProductModel();
            foreach ($list as $k => $v) {
                $option = M('product_price')->where(['product_id' => $v['id']])->order('price_id asc')->select();
                //$list[$k]['img'] = C('LOCAL_HOST') . $list[$k]['img'];
                $list[$k]['option'] = $pm->jiagetxt($option, $v['block_id']);
                
                //针对 非代理专区商品 和 公让宝兑换专区 名称后添加PV值显示
//                 if ($v['block_id'] != 4 && $v['block_id'] != C('GRB_EXCHANGE_BLOCK_ID')) {
//                 	$list[$k]['name'] .= '('. $list[$k]['option'][0]['pv_str']. ')';
//                 }
            }

            //公告
            $field_flash_content = 'flash_content'.$current_lang.' as flash_content';
            $field_flash_link = 'flash_link'.$current_lang.' as flash_link';
            $news = M('flash_news')->where(['type' => 2])->field('flash_id, '.$field_flash_content.', flash_image, '.$field_flash_link.', uid, cid, h5_path, city, type, post_time')->order('flash_id desc')->limit(5)->select();
            foreach ($news as $k => $v) {
                $news[$k]['flash_link'] = C('LOCAL_HOST') . '/APP/Index/getFlashNewsDetail/flash_id/' . $v['flash_id'];
            }
            $data['news'] = $news;

            //站点关闭功能
            $parameter_info = M('Parameter', 'g_')->field('is_close,close_msg')->find();
            $data['app_status'] = array($parameter_info);
            $data['carousel'] = $carousel;
            $data['block_id'] = 4;
            $data['productlist'] = $list;
            //$data['news'] = $news;
            
            //板块列表
            $field_block_name = 'block_name'.$current_lang.' as block_name';
            $map_menu = [
            	'block_enabled' => ['eq', 1],
            	'block_id' => ['neq', C('GRB_EXCHANGE_BLOCK_ID')]
            ];
            $menu = M('block')->where($map_menu)->field('block_id, '.$field_block_name.', block_icon, block_enabled')->order('block_order asc')->select();
            $menu = Image::formatList($menu, ['block_icon'], 'oss');
            $data['menus'] = $menu;
            
            //公让宝兑换区板块
            $menu_grb = M('Block')->where('block_id='.C('GRB_EXCHANGE_BLOCK_ID'))->field('block_id, '.$field_block_name.', block_icon, block_enabled')->select();
            $menu_grb = Image::formatList($menu_grb, ['block_icon'], 'oss');
            $data['menu_grb'] = $menu_grb;
            
            //放入缓存
            S($cache_key, $data, 60);
        }

        $this->myApiPrint('查询成功', 400, $data);
    }


    /**
     * 新闻详情
     */
    public function xiangqing()
    {
    	$current_lang = getCurrentLang(true);
    	
        $id = intval(I('post.id'));
        
        $field_flash_content = 'flash_content'.$current_lang.' as flash_content';
        $field_flash_link = 'flash_link'.$current_lang.' as flash_link';
        
        $news = M('flash_news')->field('flash_id, '.$field_flash_content.', flash_image, '.$field_flash_link.', uid, cid, h5_path, city, type, post_time')->find($id);
        
        $news['flash_link'] = htmlspecialchars_decode(htmlspecialchars_decode($news['flash_link']));
        
        $this->myApiPrint('获取成功', 400, $news);
    }

    /**
     * 商城一级菜单
     */
    public function firstMenu()
    {
    	$current_lang = getCurrentLang(true);
    	
    	$field_fm_name = 'fm_name'.$current_lang;
        $data = M('first_menu', 'zc_')->field('fm_id firstmenu_id,'.$field_fm_name.' firstmenu_name')->select();
        if (!empty($data)) {
            $this->myApiPrint('查询成功', 400, $data);
        } else {
            $this->myApiPrint('查询失败', 300);
        }
    }

    /**
     * 商城二级菜单
     *
     * @param firstmenu_id 一级菜单ID
     */
    public function secondMenu()
    {
    	$current_lang = getCurrentLang(true);
    	
        $fm_id = I('post.firstmenu_id');

        $wherekey['fm_id'] = intval($fm_id);

        $field_sm_name = 'sm_name'.$current_lang;
        $data = M('second_menu', 'zc_')
            ->field('sm_id secondmenu_id,'.$field_sm_name.' secondmenu_name,sm_image secondmenu_image')
            ->where($wherekey)
            ->select();

        if (!empty($data)) {
            $this->myApiPrint('查询成功', 400, $data);
        } else {
            $this->myApiPrint('查询失败', 300);
        }
    }

    /**
     * 商品评论列表
     */
    public function content_list()
    {
        $productid = I('post.productid');

        if (empty($productid)) {
            $this->myApiPrint('数据错误！');
        }

        $page = intval(I('post.page')) - 1;
        $page = $page > 0 ? $page * 10 : 0;

        $wherekey = ' a.productid=' . $productid . ' and a.iscontent=1 and a.uid=b.id';
        $totalPage = M('orders a')->join('zc_member b')->where($wherekey)->count();
        $everyPage = '10';
        $pageString = $page . ',' . $everyPage;

        $page1['totalPage'] = floor(($totalPage - 1) / 10) + 1;
        $page1['everyPage'] = $everyPage;

        $comment = M('orders a')
            ->field('a.id,a.productid,a.content,a.uid,a.score,a.comment_time,b.nickname,b.img')
            ->join('zc_member b')
            ->where($wherekey)
            ->limit($pageString)
            ->select();

        if ($comment == "") {
            $this->myApiPrint('查不到数据！', 400, $data1);
        }
        foreach ($comment as $k => $v) {
            $comment[$k]['nickname'] = mb_substr($v['nickname'], 0, 1, 'utf-8') . '**';
        }

        $data1['page'] = $page1;
        $data1['data'] = $comment;

        $this->myApiPrint('查询成功！', 400, $data1);
    }


    /**
     * 商家详情
     *
     * @param storeid 商家ID
     * @param id 个人ID
     */
    public function marchant_info()
    {
        $mar_id = I('post.storeid');
        $uid = intval(I('post.id'));

        if (empty($mar_id)) {
            $this->myApiPrint('查询失败！');
        }


        //商户信息
        $wherekey9['id'] = $mar_id;
        $data9 = M('first_menu_store')
            ->field("CONCAT(store_img,'" . C('STORE_BANNER_SIZE') . "') as store_img,evn_img,notice,content,fm_name,store_name,attention,score,person_consumption,month_consumption,address,phone,longitude,latitude,coin_proportion,start_time,end_time,pname,floor(conditions) conditions,reward")
            ->where($wherekey9)
            ->find();
        if (empty($data9)) {
            $this->myApiPrint('查询失败！');
        }
        //查询赠送比例
        $pw = M('preferential_way')->where('store_id= ' . $mar_id)->find();
        $pm = M('g_parameter', null)->find(1);

        $data9['store_reward'] = '店内消费' . $data9['conditions'] . '元可使用' . $data9['reward'] . '元丰谷宝抵扣现金';

        //处理店铺介绍富文本
        $data9['content'] = htmlspecialchars_decode(html_entity_decode($data9['content']));

        //处理logo
        //$data9['store_sm_img'] = str_replace('.', '_sm.', $data9['store_img']);
        //转换图片
        $photos = json_decode($data9['evn_img'], true);
        $banners = '';
        foreach ($photos as $k => $v) {
            $banners .= $v . C('STORE_BANNER_SIZE') . ',';
        }
        if ($photos && $banners != '') {
            $banners = substr($banners, 0, strlen($banners) - 1);
            $data9['evn_img'] = $banners;
        }
        //兼容没有图片的
        if (trim($data9['evn_img']) == '') {
            $data9['evn_img'] = $data9['store_img'];
        }
        //商品列表
        $wherekey2['storeid'] = $mar_id;
        $wherekey2['manage_status'] = '1';
        $wherekey2['status'] = '0';
        $data2 = M('product')->field("id,CONCAT(img,'" . C('STORE_PRODUCTLIST_SIZE') . "') as img,name,price,exchangenum,totalnum, is_super")
            ->where($wherekey2)
            ->order('id desc')
            ->select();
        foreach ($data2 as $k => $v) {
            if ($v['is_super'] == 1) {
                $data2[$k]['tag'] = '【商超】';
            } else {
                $data2[$k]['tag'] = '';
            }
        }

        if (empty($data9)) {
            $this->myApiPrint('查询失败！');
        }

        //每月销售
        if (date('m') == 1) {
            $y = date('Y') - 1;
            $m = 12;
        } else {
            $y = date('Y');
            $m = date('m') - 1;
        }

        $wherekey5 = " storeid=" . $mar_id . " and date_format(from_unixtime(time),'%Y%m')=" . date('Ym');
        $data5 = M('orders')->where($wherekey5)->count('storeid');
        $data9['month_consumption'] = $data5;

        //用户评价
        $wherekey3 = " a.storeid = " . $mar_id . " and a.iscontent = 1 and a.content != '' ";
        $comment = M('orders a')
            ->field("CONCAT(b.img,'" . C('HOME_BANNER_SIZE') . "') as img ,b.nickname,a.score,a.content,a.comment_time as post_time, a.comment_img, a.uid, a.id as orderid")
            ->join('zc_member b on b.id=a.uid')
            ->where($wherekey3)
            ->order('a.comment_time desc ')
            ->limit(0, 3)
            ->select();
        //格式化参数
        foreach ($comment as $k => $v) {
            if (trim($v['comment_img']) == '' || empty($v['comment_img'])) {
                $comment[$k]['comment_img'] = '';
            } else {
                $tempimgs = explode(',', $v['comment_img']);
                foreach ($tempimgs as $a => $b) {
                    if ($b != '') {
                        $tempimgs[$a] = $b . c('STORE_COMMENTLIST_SIZE');
                    }
                }
                $comment[$k]['comment_img'] = implode(',', $tempimgs);
            }
            $comment[$k]['nickname'] = mb_substr($v['nickname'], 0, 1, 'utf-8') . '**';
        }
        $wherekey4['storeid'] = $mar_id;
        $wherekey4['favorite'] = '1';
        $data9['attention'] = M('favorite_store')
            ->field('favorite')
            ->where($wherekey4)
            ->count();

        $wherekey6['storeid'] = $mar_id;
        $wherekey6['uid'] = $uid;
        $sql = M('favorite_store')
            ->field('favorite')
            ->where($wherekey6)
            ->find();
        if ($sql == '') {
            $da['uid'] = $uid;
            $da['storeid'] = $mar_id;
            $da['favorite'] = '0';
            M('favorite_store')->add($da);
            $sql = '0';
        }

        foreach ($data9 as $k => $v) {
            if ($v == '') {
                $data9[$k] = '';
            }
        }

        $data['store'] = $data9;
        $data['product'] = $data2;
        $data['user_comment'] = $comment;
        $data['count'] = $data5;
        $data['favorite'] = $sql['favorite'];

        $this->myApiPrint('查询成功！', 400, $data);
    }

    /**
     * 兑换商品确认-只能用公让宝
     * @param product_id 商品ID
     * @param tostore 1=送货上门 0=兑换
     * @param number 购买数量
     * @param comment 买家留言
     * @param uid 买家ID
     */
    public function exchange_confirm()
    {
        $product_id = I('post.product_id');
        $tostore = I('post.tostore'); //1=送货上面； 0=兑换
        $number = I('post.number');
        $comment = I('post.comment');
        $uid = I('post.uid');

        //1.验证参数
        $params = verify_exchange_confirm($product_id, $number, $comment, $uid);

        //2.判断支付货币类型
        if ($params['product']['is_super'] == 1) {
            //商超券支付
            $cashtype = Currency::ColorCoin;
            $action = CurrencyAction::ColorCoinExchange;
        } else {
            $cashtype = Currency::GoldCoin;
            $action = CurrencyAction::GoldCoinExchange;
        }

        //3.验证余额
        $amount = $params['product']['price'] * $number;
        $om = new OrderModel();
        if (!$om->compareBalance($uid, $cashtype, $amount)) {
            $this->myApiPrint(Currency::getLabel($cashtype) . '余额不足');
        }

        M()->startTrans();

        //4.创建订单
        $res2 = $om->create($uid, $amount, $cashtype, 1, $params['store']['id'], $params['product']['name'], $comment, 0, $product_id, 0, $number, $params['product']['start_time'], $params['product']['end_time']);

        //3.添加明细+扣除账户资金
        $arm = new AccountRecordModel();
        $res1 = $arm->add($uid, $cashtype, $action, -$amount, $arm->getRecordAttach($params['store']['uid'], $params['store']['store_name'], $params['store']['store_img'], $res2), '兑换商品');

        //5.更新商品兑换数量
        $num['exchangenum'] = array('exp', 'exchangenum+' . $number);
        $num['exchangeuse'] = array('exp', 'exchangeuse-' . $number);
        $res3 = M('product')->where('id=' . $product_id)->save($num);

        if ($res1 !== false && $res2 != '' && $res3 !== false) {
            M()->commit();
            $this->myApiPrint('兑换成功！', 400, M('orders')->where(array('order_number' => $res2))->find());
        } else {
            M()->rollback();
            $this->myApiPrint('兑换失败');
        }
    }


    /**
     * 默认收货地址
     *
     * @param uid 会员ID
     */
    public function default_address()
    {
        $uid = I('post.uid');
        if (empty($uid)) {
            $this->myApiPrint('数据错误！');
        }

        $wherekey['uid'] = $uid;
        $wherekey['is_default'] = 1;
        $data1 = M('address')
            ->field('id,uid,consignee,phone,city_address,address')
            ->where($wherekey)
            ->select();
        if (empty($data1)) {
            $this->myApiPrint('查询失败！');
        }

        $this->myApiPrint('', 400, $data1);
    }

    /**
     * 获取快讯详情 (非直接接口使用,此方法主要供其他接口中含有的按规则生成的超链接直接渲染使用)
     *
     * @param flash_id 快讯ID
     */
    public function getFlashNewsDetail()
    {
    	$current_lang = getCurrentLang(true);
    	
        $flash_id = I('get.flash_id');

        if (empty($flash_id)) {
            $this->error('参数错误');
        }

        $field_flash_link = 'flash_link'.$current_lang.' as flash_link';
        $field_flash_content = 'flash_content'.$current_lang.' as flash_content';
        $flash_news = M('flash_news')->field($field_flash_link.','.$field_flash_content.',post_time')->where("flash_id=" . $flash_id)->find();
        if ($flash_news) {
            //兼容2017-07-01之前发布的内容
            if ($flash_news['post_time'] > 1498867200) {
                $flash_news['flash_link'] = htmlspecialchars_decode($flash_news['flash_link']);
            }
            $this->assign('info', $flash_news);
            $this->display();
        } else {
            $this->error('文章不存在');
        }
    }

    /**
     * 商城菜单接口(包含一级和二级菜单)
     *
     * @param $fm_id 一级菜单ID (可选)
     *
     * @author yinhexi
     */
    public function menuList()
    {
    	$current_lang = getCurrentLang(true);
    	
        $FirstMenu = M('FirstMenu');
        $SecondMenu = M('SecondMenu');

        $map_first = array();
        $menu = array();

        $fm_id = $this->post['fm_id'];

        if (!empty($fm_id) && validateExtend($fm_id, 'NUMBER')) {
            $map_first['fm_id'] = array('eq', $fm_id);
        }

        $field_fm_name = 'fm_name'.$current_lang.' as fm_name';
        $menu = $FirstMenu->where($map_first)->field('fm_id,'.$field_fm_name.',fm_order')->order('fm_order desc,fm_id desc')->select();
        if (!$menu) {
            $this->myApiPrint('查询失败', 300);
        }

        $field_sm_name = 'sec.sm_name'.$current_lang.' as sm_name';
        foreach ($menu as $k => $v) {
            $menu[$k]['second_menu'] = $SecondMenu
                ->alias('sec')
                ->join('left join __MENU_ATTRIBUTE__ attr ON attr.id=sec.attr_id')
                ->field("sec.sm_id,'.$field_sm_name.', CONCAT(sec.sm_image2,'" . C('SHOP_PRODUCTTYPE_SIZE') . "') as sm_image,sec.fm_id,sec.fm_order sm_order,sec.attr_id,attr.name attr_name,attr.attr_order")
                ->group('sec.sm_id')
                ->where('sec.fm_id=' . $v['fm_id'])
                ->order('sec.fm_order desc,sec.sm_id desc')
                ->select();

            foreach ($menu[$k]['second_menu'] as $k1 => $v1) {
                if ($v1['attr_id'] === null) {
                    $menu[$k]['second_menu'][$k1]['attr_id'] = '';
                    $menu[$k]['second_menu'][$k1]['attr_name'] = '';
                    $menu[$k]['second_menu'][$k1]['attr_order'] = '';
                }
                $menu[$k]['second_menu'][$k1]['sm_image'] = ($v1['sm_image'] === null) ? '' : $v1['sm_image'];
                $menu[$k]['second_menu'][$k1]['attr_name'] = ($v1['attr_name'] === null) ? '' : $v1['attr_name'];
            }
        }

        $this->myApiPrint('查询成功', 400, $menu);
    }

    /**
     * 获取评论
     * Enter description here ...
     */
    public function getcommentlist()
    {
        $storeid = intval(I('post.storeid'));
        $uid = intval(I('post.uid'));
        $page = I('post.page');

        $page = intval($page) - 1;
        $page = $page > 0 ? $page * 10 : 0;
        $everyPage = '10';
        $pageString = $page . ',' . $everyPage;

        $where = " a.iscontent=1 and a.content != '' ";
        if ($storeid > 0) {
            $where .= ' and a.storeid = ' . $storeid;
        }
        if ($uid > 0) {
            $where .= ' and a.uid = ' . $uid;
        }

        $comment = M('orders a')
            ->field('CONCAT(b.img,\'' . C('USER_PAYLIST_SIZE') . '\') as userimg,b.nickname,a.score,a.content,a.comment_time as post_time, a.comment_img,a.productid, p.img as productimg, p.name as productname, s.store_name, CONCAT(s.store_img,\'' . C('USER_ORDERINFO_SIZE') . '\') as store_img, a.id as orderid, a.uid')
            ->join('left join zc_member b on b.id=a.uid')
            ->join('left join zc_product p on p.id=a.productid')
            ->join('left join zc_store s on s.id=a.storeid')
            ->where($where)
            ->order('a.comment_time desc ')
            ->limit($pageString)
            ->select();
        foreach ($comment as $k => $v) {
            $comment[$k]['nickname'] = mb_substr($v['nickname'], 0, 1, 'utf-8') . '**';

            $tempimgs = explode(',', $v['comment_img']);
            foreach ($tempimgs as $a => $b) {
                if ($b != '') {
                    $tempimgs[$a] = $b . c('STORE_COMMENTLIST_SIZE');
                }
            }
            $comment[$k]['comment_img'] = implode(',', $tempimgs);
        }
        $this->myApiPrint('查询成功', 400, $comment);
    }

    /**
     * 公告列表
     * Enter description here ...
     */
    public function noticeList()
    {
    	$current_lang = getCurrentLang(true);
    	
        $city = trim(I('post.city'));
        $page = intval(I('post.page'));
        $tag = intval(I('post.type')); //1=快讯，2=公告，3帮助
        if ($tag != 2) {
            $tag = 1;
        }
        $wh = " (city like '%" . $city . "%' or city='' or city is null) and `type`=  " . $tag;

        $page = ($page - 1) > 0 ? ($page - 1) * 10 : 0;
        $everyPage = '10';
        $pageString = $page . ',' . $everyPage;

        $field_flash_link = 'flash_link'.$current_lang;
        $field_flash_content = 'flash_content'.$current_lang.' as flash_content';
        $flash_news = M('flash_news')->field('flash_id,'.$field_flash_content.',h5_path, '.$field_flash_link.' as info, post_time')->where($wh)->limit($pageString)->order('cid desc, flash_id desc')->select();
        foreach ($flash_news as $k => $v) {
            $h5_path = trim($v['h5_path']);
            $flash_news[$k]['h5_path'] = empty($h5_path) ? C('LOCAL_HOST') . '/APP/Index/getFlashNewsDetail/flash_id/' . $v['flash_id'] : $h5_path;
            $info = $v['info'];
            if ($v['post_time'] > 1498867200) {
                $info = htmlspecialchars_decode($v['info']);
            }
            //提起摘要
            if (empty($current_lang)) {
	            $qian = array(" ", "　", "\t", "\n", "\r");
	            $hou = array("", "", "", "", "");
	            $tempcontent = str_replace($qian, $hou, strip_tags($info));
	            $tempcontent = str_replace('&nbsp;', '', $tempcontent);
	            $flash_news[$k]['info'] = mb_substr($tempcontent, 0, 100, 'utf-8');
            }
        }

        $this->myApiPrint('查询成功', 400, $flash_news);
    }

    /**
     * 帮助中心列表
     * Enter description here ...
     */
    public function helpList()
    {
    	$current_lang = getCurrentLang(true);
    	
        $page = intval(I('post.page'));
        $wh = " `type`= 3 ";

        $page = ($page - 1) > 0 ? $page * 10 : 0;
        $everyPage = '10';
        $pageString = $page . ',' . $everyPage;

        $field_flash_content = 'flash_content'.$current_lang.' as flash_content';
        $flash_news = M('flash_news')->field('flash_id,'.$field_flash_content.',h5_path')->where($wh)->order('cid desc, flash_id desc')->limit($pageString)->select();
        foreach ($flash_news as $k => $v) {
            $h5_path = trim($v['h5_path']);
            $flash_news[$k]['h5_path'] = empty($h5_path) ? U('index.php/Index/getFlashNewsDetail/flash_id/' . $v['flash_id'], '', '', true) : $h5_path;
        }
        $this->myApiPrint('查询成功', 400, $flash_news);
    }


    /**
     * 公告详情
     * Enter description here ...
     */
    public function notideinfo()
    {
    	$current_lang = getCurrentLang(true);
    	
        $id = intval(I('post.id'));
        
        $field_flash_content = 'flash_content'.$current_lang.' as flash_content';
        $field_flash_link = 'flash_link'.$current_lang.' as flash_link';
        $info = M('flash_news')->field('flash_id,'.$field_flash_content.','.$field_flash_link)->find($id);
        
        $this->myApiPrint('查询成功', 400, $info);
    }

    /**
     * 银行列表接口
     */
    public function bankList()
    {
        $Bank = M('Bank');
        
        $where = [];
        
        //只保留农行
        $where['id'] = ['eq', 5];

        $list = $Bank->where($where)->order('id asc')->select();

        $this->myApiPrint('查询成功', 400, $list);
    }

    /**
     * 动态获取银行网点和联行号接口
     *
     * @param string bank 银行名
     * @param string province 省份名
     * @param string city 市名
     * @param string district 区名
     * @param string network_point 网点名关键词
     */
    public function getBankCodeList()
    {
        $BankCode = M('BankCode');

        $map = array();
        $return = array('code' => '');

        $bank = $this->post['bank'];
        $province = $this->post['province'];
        $city = $this->post['city'];
        $district = $this->post['district'];
        $network_point = $this->post['network_point'];

        if (empty($network_point)) {
            $this->myApiPrint('查询成功', 400, $return);
        }

        //对参数进行处理
        $bank = str_replace('中国', '', $bank);
        $city = str_replace('市', '', $city);
        $district = str_replace('区', '', $district);

        $map['bankname'][] = array('like', "%{$bank}%");
        $map['bankname'][] = array('like', "%{$city}%");
        $map['bankname'][] = array('like', "%{$network_point}%");
        $map['bankname'][] = 'and';
        $list = $BankCode->where($map)->field('code')->select();
        if (count($list) == 1) {
            $return['code'] = $list[0]['code'];
        } else {
            unset($map['bankname'][1]);
            $map['bankname'][1] = array('like', "%{$district}%");
            $list = $BankCode->where($map)->field('code')->select();
            if (count($list) == 1) {
                $return['code'] = $list[0]['code'];
            }
        }

        $this->myApiPrint('查询成功', 400, $return);
    }


    /**
     * APP首页信息分步加载-
     *
     * @param city 城市名
     * @param page 商家活动分页参数
     */
    public function index1()
    {
        $city = I('post.city');
        $city = empty($city) ? '眉山市' : $city;

        //轮播图片
        $carousel = M('carousel')->field("CONCAT(car_image,'" . C('HOME_BANNER_SIZE') . "') as car_image,uid,cid,h5_path")->order('car_id desc')->select();
        /*/快讯
        $wh_news = " (city like '%".$city."%' or city='' or city is null) and `type`=1 ";
        $flash_news = M('flash_news')->field('flash_id,flash_content,h5_path')->where($wh_news)->select();
        foreach ($flash_news as $k=>$v) {
            $h5_path = trim($v['h5_path']);
            $flash_news[$k]['h5_path'] = empty($h5_path) ? U('Index/getFlashNewsDetail/flash_id/'.$v['flash_id'],'','',true) : $h5_path;
        }*/

        //站点关闭功能
        $parameter_info = M('Parameter', 'g_')->field('is_close,close_msg')->find();
        $data['app_status'] = array($parameter_info);

        $data['carousel'] = $carousel;
        $this->myApiPrint('查询成功', 400, $data);
    }

    public function index2()
    {
        $city = I('post.city');
        $city = empty($city) ? '眉山市' : $city;

        //二级菜单
        $menu = M('second_menu')->field("sm_id,sm_name,CONCAT(sm_image,'" . C('HOME_PRODUCTTYPE_SIZE') . "') as sm_image")->limit(9)->order('fm_order desc,sm_id asc')->select();
        //增加一个全部，到时候只改接口，不影响app
        $alltag['sm_id'] = 0;
        $alltag['sm_name'] = '全部分类';
        $alltag['sm_image'] = '/Uploads/menu/20170301/e9c1ea6a3cbaebe2ed941a70d21f6fa1.png!127x127';
        $menu[] = $alltag;

        $data['menu'] = $menu;
        $this->myApiPrint('查询成功', 400, $data);
    }

    public function index3()
    {
        $city = I('post.city');
        $city = empty($city) ? '眉山市' : $city;

        //热门兑换
        $hot_goods = M('product a')
            ->field("a.id,a.name,CONCAT(a.img,'" . C('HOME_HOTEXCHANGE_SIZE') . "') as img,a.exchangenum,a.totalnum,a.price, a.is_super")
            ->join('zc_store b on b.id=a.storeid')
            ->join('zc_preferential_way g on g.store_id=b.id')
            ->join('zc_member m on m.id = b.uid')
            ->where('m.is_lock=0 and a.manage_status=1 and a.status=0 and b.manage_status=1 and b.status=0 and g.manage_status=1 and g.`status`=0 and b.city like \'%' . $city . '%\'')
            ->limit((28 - date('H') * 1 - 5), 5)->order('a.create_time desc')->select();
        foreach ($hot_goods as $k => $v) {
            if ($v['is_super'] == 1) {
                $hot_goods[$k]['tag'] = "<font color='#ff0000'>【商超】</font>";
            } else {
                $hot_goods[$k]['tag'] = '';
            }
        }

        $data['hot_goods'] = $hot_goods;
        $this->myApiPrint('查询成功', 400, $data);
    }

    public function index4()
    {
        $city = I('post.city');
        $lng = $this->post['lng'];
        $lat = $this->post['lat'];
        $city = empty($city) ? '眉山市' : $city;

        //商家活动：查询有优惠活动的商家列表-分页
        $post_page = intval(I('post.page'));
        $everyPage = '10';
        if ($post_page > 0) {
            $post_page = $post_page * $everyPage;
        }
        $pageString = $post_page . ',' . $everyPage;

        if ($lng == '' || $lat == '') {
            $seller_act = M('store a')
                ->field("a.store_type, a.give_points_total, a.id,a.store_name,a.address,a.score,a.person_consumption,a.attention,a.pay_type,c.conditions,c.reward, CONCAT(a.store_img,'" . C('HOME_STORELIST_SIZE') . "') as img,a.longitude,a.latitude")
                ->join('zc_preferential_way c on c.store_id=a.id')
                ->join('zc_member m on m.id = a.uid ')
                ->where("m.is_lock=0 and a.manage_status=1 and a.status=0 and a.city like '%" . $city . "%' and c.manage_status=1 and c.status=0")
                ->order('a.attention desc')
                ->limit($pageString)
                ->select();
        } else {
            $seller_act = M('store a')
                ->field('a.store_type, a.give_points_total, a.id,a.store_name,a.address,a.score,a.person_consumption,a.attention,a.pay_type,c.conditions,c.reward,CONCAT(a.store_img,\'' . C('HOME_STORELIST_SIZE') . '\') as img,a.longitude,a.latitude, (6371 * acos( cos( radians(a.latitude) ) * cos( radians( ' . $lat . ' ) ) * cos( radians( ' . $lng . ' ) - radians(a.longitude) ) + sin( radians(a.latitude) ) * sin( radians( ' . $lat . ' ) ) ) ) as distance')
                ->join('zc_preferential_way c on c.store_id=a.id')
                ->join('zc_member m on m.id = a.uid ')
                ->where("m.is_lock=0 and a.manage_status=1 and a.status=0 and a.city like '%" . $city . "%' and c.manage_status=1 and c.status=0")
                ->order('distance asc')
                ->limit($pageString)
                ->select();
        }
        //加载参数
        $param = M('g_parameter', null)->find(1);
        $i = 0;
        while ($seller_act[$i]['id'] != '') {
            //换算赠送商超券
            $seller_act[$i]['reward'] = $seller_act[$i]['reward'] * $param['points_member'];
            //关注度
            $wh1['storeid'] = $seller_act[$i]['id'];
            $wh1['favorite'] = '1';
            $da = M('favorite_store')
                ->field('favorite')
                ->where($wh1)
                ->count();
            $seller_act[$i]['attention'] = $da;

            //月销量
            $map_order['storeid'] = array('eq', $seller_act[$i]['id']);
            $map_order['status'] = 4;
            $seller_act[$i]['sales_count'] = M('Orders')
                ->where($map_order)
                ->count();

            //与终端的距离
            if (empty($seller_act[$i]['distance'])) {
                $seller_act[$i]['distance'] = -1;
            } else {
                $seller_act[$i]['distance'] = intval($seller_act[$i]['distance'] * 1000);
            }
            //图片
            $img = $seller_act[$i]['img'];
            if (strpos($img, '_sm.') === false) {
                $far = explode('.', $img);
                $yuantu = $_SERVER['DOCUMENT_ROOT'] . $far[0] . '_sm.' . $far[1];
                if (file_exists($yuantu)) {
                    $seller_act[$i]['img'] = $far[0] . '_sm.' . $far[1];
                }
            }

            //剩余公让宝
            $d_tag = 'points_merchant_max_day_' . $seller_act[$i]['store_type'];
            $w_tag = 'points_merchant_max_week_' . $seller_act[$i]['store_type'];
            if (C('PARAMETER_CONFIG.MERCHANT')[$d_tag] != 0 || C('PARAMETER_CONFIG.MERCHANT')[$w_tag] != 0) {
                $expr_point = C('PARAMETER_CONFIG.MERCHANT')[$d_tag] == 0 ? C('PARAMETER_CONFIG.MERCHANT')[$w_tag] : C('PARAMETER_CONFIG.MERCHANT')[$d_tag];  //商家的今日/本周可赠公让宝上限
                $rest = $expr_point - $seller_act[$i]['give_points_total'];
                if ($rest > 0) {
                    $seller_act[$i]['store_current_max_points'] = '本周剩余可赠最高丰谷宝' . sprintf('%.2f', $rest);
                } else {
                    $seller_act[$i]['store_current_max_points'] = '本周剩余可赠最高丰谷宝0';
                }
            } else {
                $seller_act[$i]['store_current_max_points'] = '';
            }

            $seller_act[$i]['store_reward'] = '店内消费' . $seller_act[$i]['conditions'] . '元赠送' . $seller_act[$i]['reward'] . '丰谷宝';

            $i++;
        }
        $data['seller_act'] = $seller_act;

        $this->myApiPrint('查询成功', 400, $data);
    }

    /**
     * 查询所有菜单分类
     * Enter description here ...
     */
    public function categroylist()
    {
        $fmenu = M('first_menu')->field('fm_id, fm_name')->order('fm_order asc')->select();
        $smenu = M('second_menu')->field("sm_id,fm_id, sm_name,CONCAT(sm_image,'" . C('HOME_PRODUCTTYPE_SIZE') . "') as sm_image")->order('sm_id asc')->select();
        foreach ($fmenu as $k => $v) {
            foreach ($smenu as $row) {
                if ($row['fm_id'] == $v['fm_id']) {
                    $fmenu[$k]['items'][] = $row;
                }
            }
        }
        $this->myApiPrint('查询成功', 400, $fmenu);
    }

    /**
     * 获取公益慈善的地址
     * Enter description here ...
     */
    public function chariturl()
    {
        $this->myApiPrint('查询成功', 400, 'http://www.xlsfxjj66.com/Member/index.html');
    }

    /**
     * 商品详情
     *
     * @param product_id 商品ID
     * @param id 个人ID
     */
    public function productDetails()
    {
        $product_id = I('post.product_id');
        $uid = I('post.id');

        if (empty($product_id)) {
            $this->myApiPrint('商品ID有误！');
        }
        if (!empty($uid) && !is_numeric($uid)) {
            $this->myApiPrint('会员ID格式有误');
        }

        $wherekey['a.id'] = $product_id;
        $wherekey['a.manage_status'] = '1';
        $wherekey['a.status'] = '0';
        $details = M('product a')
            ->field('a.is_super, a.id,a.name,a.img,a.score,a.price,a.is_super,a.exchangenum,a.totalnum,a.storeid,b.store_Name,b.address,b.phone,b.longitude,b.latitude,a.content,a.exchangeway,a.start_time,a.end_time,a.userule,a.carousel1')
            ->join('zc_store b on b.id=a.storeid')
            ->where($wherekey)
            ->find();
        if ($details == "") {
            $this->myApiPrint('此商品不存在！');
        }

        //商品介绍富文本转换
        $details['content'] = htmlspecialchars_decode(html_entity_decode($details['content']));
        $details['score'] = intval($details['score']);
        /*if($details['is_super'] == 1){
         $details['price'] = $details['price'].'商超券';
         }else{
         $details['price'] = $details['price'].'丰谷宝';
        }*/
        $wherekey4['productid'] = $product_id;
        $wherekey4['uid'] = $uid;
        $sql = M('favorite_product')
            ->field('favorite')
            ->where($wherekey4)->find();

        if ($sql == '') {
            $da['uid'] = $uid;
            $da['productid'] = $product_id;
            $da['favorite'] = '0';
            M('favorite_product')->add($da);
            $sql['favorite'] = '0';
        }

        $wh = " a.productid=" . $product_id . " and a.iscontent=1 and a.content !='' and a.uid=b.id ";
        $comment = M('orders a')
            ->field('a.id,a.productid,a.content,a.uid,a.score,a.comment_time,a.comment_img,b.nickname,b.img')
            ->join('zc_member b')
            ->where($wh)
            ->order('a.comment_time desc')
            ->limit(0, 3)
            ->select();
        //处理评论关键词
        foreach ($comment as $k => $v) {
            $comment[$k]['nickname'] = mb_substr($v['nickname'], 0, 1, 'utf-8') . '**';
            if ($v['comment_img'] != '') {
                $tempimgs = explode(',', $v['comment_img']);
                foreach ($tempimgs as $a => $b) {
                    if ($b != '') {
                        $tempimgs[$a] = $b . c('PRODUCT_COMMENTLIST_SIZE');
                    }
                }
                $comment[$k]['comment_img'] = implode(',', $tempimgs);
            }
        }
        //兼容之前的版本
        $str = $details['carousel1'];
        $ss = str_replace('["', '', $str);
        $ss = str_replace('"]', '', $ss);
        $a = explode('","', $ss);
        $carousel = array();
        foreach ($a as $key => $val) {
            $carousel1['img'] = $val;
            if (!empty($val)) {
                $carousel[$key] = (object)$carousel1;
            }
            unset($carousel1);
        }
        //兼容目前
        if (strpos($details['carousel1'], '[') === false) {
            $photos = json_decode($details['carousel1'], true);
            $carousel = array();
            $i = 0;
            foreach ($photos as $k => $v) {
                $carousel1['img'] = $v;
                if (trim($v) != '') {
                    $carousel1['img'] = $v . c('PRODUCT_BANNER_SIZE');
                    $carousel[$i] = (object)$carousel1;
                    $i++;
                }
                unset($carousel1);
            }
        }

        $details['carousel1'] = '';
        $details['favorite'] = $sql['favorite'];
        $data['productDetails'] = $details;
        $data['usercomment'] = $comment;
        $data['carousel1'] = $carousel;

        $this->myApiPrint('查询成功', 400, $data);
    }
    
    /**
     * 引导图和闪屏图
     */
    public function guideAndSplash() {
    	$guide = [
    		Image::url('/Uploads/guide_splash/1.jpg', 'oss'),
    		Image::url('/Uploads/guide_splash/2.jpg', 'oss'),
    	];
    	$splash = [
    		Image::url('/Uploads/guide_splash/1.jpg', 'oss'),
    	];
    	
    	$data['list'] = [
    		['guide' => $guide],
    		['splash' => $splash]
    	];
    	
    	$data['ver'] = '1001';
    	
    	$this->myApiPrint('查询成功', 400, $data);
    }
    
        /**
     * 获取我的剩余出局价值
     */
    public function get_amount_info()
    {
        $user_id = I('post.user_id');
        // 消费信息
        $consume_info = M('Consume')->where(['user_id'=>$user_id])->field('amount,amount_old,income_amount,is_out,dynamic_out,dynamic_worth,static_worth')->find();
        // 出局倍数
        $out_bei = M('ConsumeRule')->alias('cr')->join('left join __CONSUME__ c ON c.level=cr.level')->where('c.user_id='.$user_id)->getField('cr.out_bei');
        $out_bei = $out_bei ? $out_bei : 2;
        // 出局剩余价值
        $dynamic_out = $consume_info['amount'] * $out_bei - $consume_info['static_worth'] - $consume_info['dynamic_worth'];
        // 判断之前是否有购买
        $count = M('consume_bak')->where(['user_id'=>$user_id])->count();
        if ($count > 0) {
            if ($dynamic_out <= 0) {
                $this->myApiPrint('您的剩余出局价值不足！', 400);
            } else {
                $this->myApiPrint('');
            }
        } else {
            $this->myApiPrint('');
        }
    }
}

?>