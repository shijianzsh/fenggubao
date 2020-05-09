<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 团队会员管理
// +----------------------------------------------------------------------
namespace System\Controller;

use Common\Controller\AuthController;
use V4\Model\AccountIncomeModel;
use V4\Model\AccountRecordModel;
use V4\Model\Currency;
use V4\Model\AccountModel;
use V4\Model\AccountFinanceModel;
use V4\Model\WithdrawModel;
use V4\Model\CurrencyAction;
use V4\Model\UserModel;
use V4\Model\ProcedureModel;
use Common\Model\Sys\PerformanceModel;
use Common\Model\Sys\PerformanceRuleModel;
use APP\Controller\YwtController;
use V4\Model\LockModel;
use V4\Model\Tag;
class SeniorSearchController extends AuthController {

    private $level_cn; //会员级别对应中文

    public function __construct() {
        parent::__construct();
        require_once "./Appcenter/Admin/Common/function.php";
        $this->level_cn = C( 'MEMBER_LEVEL' );
    }

    /**
     * 高级查询列表
     */
    public function index() {
        $member    = D( "Admin/member" );
        $searchKey = array();

        $userid = ( isset( $_GET ) && count( $_GET ) > 0 ) ? I( 'get.userid' ) : '';
        //个人身份信息
        if ( $userid != "" ) {
            if ( ! validateExtend( $userid, 'NUMBER' ) && ! validateExtend( $userid, 'CHS' ) && ! validateExtend( $userid, 'USERNAME' ) ) {
                $this->error( '会员账号格式有误' );
            }

            $searchKey_temp['zc_member.loginname'] = array( 'eq', $userid );
            $searchKey_temp['zc_member.truename']  = array( 'eq', $userid );
            $searchKey_temp['zc_member.nickname']  = array( 'eq', $userid );
            $searchKey_temp['zc_member.username']  = array( 'eq', $userid );
            $searchKey_temp['_logic']              = 'OR';
            $searchKey['_complex']                 = $searchKey_temp;
            $searchKey['zc_member.level'] = array( 'in', array( '1','2', '5' ) );

            $whereSql['_string'] = ' zc_member.id>1 and zc_member.level<99';
            $_info = $member
                ->where( $searchKey )
                ->where( $whereSql )
                ->find();
        }
        if($_info){

            if($_info['role']==3){

                $_info['region'] =$_info['province'].$_info['city'].$_info['country'];
            }
            if($_info['role']==4){
                $_info['region'] = $_info['province'];
            }
        }


        $data['shenfen'] = $_info;

//        用户关系查询
        if($userid != ""){
            $whereRelation['repath']    = array( 'eq', $_info['repath'] . $_info['id'] . ',' );
            $whereRelation['level'] = array('neq',1);
            $listRelation = $member->where( $whereRelation )->select();
        }

        $li_html = '';
        if ( $listRelation ) {
            foreach ( $listRelation as $k => $datas ) {
                $listRelation[$k]['region'] = $datas['province'].$datas['city'].$datas['country'];
                $level_cn  = '[身份:' . getLevelName( $datas['level'], $datas['star'] ) . ']';
                $level_cn  .= $datas['is_partner'] == '1' ? '[合伙人]': '';
                $level_cn  .= ! empty( $datas['role'] ) ? '[' . getRoleName( $datas['role'] ) . ']' : '';
                if(! empty( $datas['role'] )){
                    if($datas['role']==3){
                        $level_cn  .= '['.$datas['province'].$datas['city'].$datas['country'].']';
                    }elseif ($datas['role']==4){
                        $level_cn  .='['.$datas['province'].']';
                    }
                }

                $level_cn  .= $datas['store_flag'] == '1' ? '[商]' : '';
                $listRelation[$k]['level_cn'] = $level_cn;
            }
        }

        $data['relationNext'] = $listRelation;
        $whereUp['id'] = array('in',trim($_info['repath']));
        $upRelation = $member->where($whereUp)->order('id desc')->select();
        $li_html = '';
        if ( $upRelation ) {
            foreach ( $upRelation as $k => $datas ) {

                $upRelation[$k]['region'] = $datas['province'].$datas['city'].$datas['country'];
                $level_cn  = '[身份:' . getLevelName( $datas['level'], $datas['star'] ) . ']';
                $level_cn  .= $datas['is_partner'] == '1' ? '[合伙人]' : '';
                $level_cn  .= ! empty( $datas['role'] ) ? '[' . getRoleName( $datas['role'] ) . ']' : '';
                if(! empty( $datas['role'] )){
                    if($datas['role']==3){
                        $level_cn  .='['.$datas['province'].$datas['city'].$datas['country'].']';
                    }elseif ($datas['role']==4){
                        $level_cn  .='['.$datas['province'].']';
                    }
                }
                $level_cn  .= $datas['store_flag'] == '1' ? '[商]' : '';
                $upRelation[$k]['level_cn'] = $level_cn;
//                $li_html .= '<li><a href="javascript:;">' . $datas['username'] . '</a>[手机:' . $datas['loginname'] . '][姓名:' . $datas['truename'] . ']' . $level_cn . '</li>';
            }
        }

        $data['upRelation'] = $upRelation;

        //账户信息查询
        $AccountModel = new AccountModel();
        $LockModel = new LockModel();
        //通证汇总

        $income_amount = $this->getUserIncome($_info['id']);

        $income_amount = sprintf('%.4f', $income_amount);

        //流通资产
        $amount_liutong = $AccountModel->getBalance($_info['id'], Currency::GoldCoin);
        $amount_liutong = sprintf('%.4f', $amount_liutong);

        //锁定通证
        /* 旧锁定通证 */
        $LockModel = new LockModel();
        $map_lock['user_id'] = array('eq', $_info['id']);
        $lock_info = $LockModel->getInfo('*', $map_lock);
        $amount_lock_old= sprintf('%.4f', $lock_info['lock_amount']);
        /* 新锁定通证 */
        $amount_lock = $AccountModel->getBalance($_info['id'], Currency::Bonus);
        $amount_lock = sprintf('%.4f', $amount_lock);
        if($userid){
            $data['account']= array(
                'income_amount'=>$income_amount,
                'amount_liutong'=>$amount_liutong,
                'amount_lock_old'=>$amount_lock_old,
                'amount_lock'=>$amount_lock
            );
        }else{
            $data['account'] = '';
        }

        //用户业绩信息查询
        //总业绩
        $performance_info = M('Performance')->where(array('user_id'=>$_info['id'],'performance_tag'=>0))->field('performance_amount')->find();

        $performance_amount = !$performance_info ? 0 : $performance_info['performance_amount'];
        $performance_amount = sprintf('%.2f', $performance_amount);
        //动态收益
        $dynamic_info = M('Mining')->where(array('user_id'=>$_info['id'],'tag'=>0))->field('amount')->find();
        $income_amount_dynamic = !$dynamic_info ? 0 : $dynamic_info['amount'];
        $income_amount_dynamic = sprintf('%.2f', $income_amount_dynamic);
        //静态收益
        $consume_info = M('Consume')->where(array('user_id'=>$_info['id']))->field('income_amount')->find();
        $income_amount_static = !$consume_info ? 0 : $consume_info['income_amount'];
        $income_amount_static = $income_amount_static - $income_amount_dynamic;
        $income_amount_static = sprintf('%.2f', $income_amount_static);

        //通证汇总

        $income_amount = sprintf('%.2f', $this->getUserIncome($_info['id']));

        //总消费金额
        $map_order = array('uid' => array('eq', $_info['id']), 'order_status' => array('in', '1,3,4'));
        $order_amount = M('Orders')->where($map_order)->sum('amount');

        $consume_amount_total = sprintf('%.2f', $order_amount);
//1.加载配置
        $settings  = M( 'settings' )->where( 'settings_status=1' )->getField( 'settings_code, settings_value' );

        //总贡献业绩PV、收益份额、是否出局 出局收益 出局收益价值
        $consume_info = M('Consume')->where(array('user_id'=>$_info['id']))->field('amount,amount_old,income_amount,is_out,level,dynamic_out')->find();
        $consume_amount = !$consume_info ? 0 : $consume_info['amount'] - $consume_info['amount_old'];
        $consume_amount_old = !$consume_info ? 0 : $consume_info['amount_old'];
        $consume_pv = sprintf('%.2f', $consume_info['amount']);
        $income_portion = floor($consume_info['amount'] / $settings['performance_portion_base']);
        $portion = floor($consume_amount / $settings['performance_portion_base'] + ($consume_amount_old * $settings['mine_old_machine_bai'] / 100) / $settings['performance_portion_base']);
        $is_out = !$consume_info ? 0 : $consume_info['is_out'];
        $dynamic_out = !$consume_info ? 0 : $consume_info['dynamic_out'];
        $consume_rule = M('ConsumeRule')->where(array('level'=>$consume_info['level']))->find();
        $mining = M('Mining')->where(array('user_id'=>$_info['id'],'tag'=>0))->find();
//        静态出局收益
        $is_out_income =sprintf('%.2f', $consume_info['amount']*$consume_rule['out_bei']);
        $gold_prices = M('GoldcoinPrices')->order('id desc')->limit(1)->getField( 'amount' );
//        静态出局收益价值
        $is_out_income_jiazhi =sprintf('%.2f',($consume_info['income_amount']-$mining['amount'])*$gold_prices);
        //查找直推数量
        $find_zhitui = $member->where(array('reid'=>$_info['id'],'level'=>2))->count();

        if($userid){
            $data['achievement'] = array(
                'performance_amount'=>$performance_amount,
                'consume_amount_total'=>$consume_amount_total,
                'consume_pv'=>$consume_pv,
                'income_portion'=>$income_portion,
                'is_out'=>$is_out,
                'dynamic_out'=>$dynamic_out,
                'portion'=>$portion,
                'is_out_income'=>$is_out_income,
                'is_out_income_jiazhi'=>$is_out_income_jiazhi,
                'dongtai_out_income'=>$is_out_income,//动态出局收益
                'dongtai_out_jiazhi'=>sprintf('%.2f',$mining['amount']*$gold_prices),//动态出局收益价值
                'shouyi_total'=>$consume_info['income_amount'],
                'wakuang_total'=>$mining['amount'],
                'grb_price'=>$gold_prices,
                'xfyj_total'=>$consume_info['amount'],
                'out_bei'=>$consume_rule['out_bei'],
                'zhitui_total'=>$find_zhitui
            );
        }else{
            $data['achievement'] = '';
        }

        //农场详情
        //配置参数和用户消费数据
        $performancePortionBase = $settings['performance_portion_base'];
        $mineOldMachineBai = $settings['mine_old_machine_bai'];
        $consume_info = M('Consume')->where(array('user_id'=>$_info['id']))->field('amount,amount_old')->find();

        //内排数据
        $return['pv_old'] = !$consume_info ? 0 : $consume_info['amount_old'];
        $return['pv_old'] = sprintf('%.4f', $return['pv_old']);
        $return['protion_old'] = floor( $return['pv_old'] / $performancePortionBase ) * $mineOldMachineBai / 100;
        $return['protion_old'] = sprintf('%.1f', $return['protion_old']);

        //正式数据
        $return['pv_release'] = !$consume_info ? 0 : $consume_info['amount'];
        $return['pv_release'] = sprintf('%.4f', $return['pv_release'] - $return['pv_old']);
        $return['protion_release'] = floor ( $return['pv_release']  / $performancePortionBase );
        $return['protion_release'] = sprintf('%.1f', $return['protion_release']);

        //最终农场数
        $return['portion'] = sprintf('%.1f', $return['protion_release'] + $return['protion_old']);


        //未生成农场PV
        $return['pv_not_generate'] = sprintf('%.4f', $return['pv_release'] / $performancePortionBase);

        //农场计算说明
        $return['protion_description'] = $settings['mine_machine_captions'];
        if($_info['id']){
            $data['protionDetail'] = $return;
        }else{
            $data['protionDetail'] = '';
        }

        //消费列表
        $map_order = array('uid' => array('eq', $_info['id']), 'order_status' => array('in', '1,3,4'));
        $order_amount_list = M('Orders')->where($map_order)->select();
        foreach ($order_amount_list as $ord=>&$ordVal){
            $pv = M('OrderProduct')->where('order_id='.$ordVal['id'])->sum('price_cash * product_quantity * performance_bai_cash * 0.01');
            $pv = sprintf('%.2f', $pv);
            $ordVal['pv'] = $pv;
            $products = M('order_product op')
                ->field('vp.id, vp.`name`, vp.price as product_price,  op.*')
                ->join('left join zc_product as vp on vp.id = op.product_id')
                ->where('op.order_id = ' . $ordVal['id'])
                ->select();
            $product_str = array();

            foreach ($products as $prot=>&$val){
//                    $product_str[] = $val['name'].'&nbsp;/&nbsp;'.$val['product_quantity'].'&nbsp;/&nbsp;'.$val['price_cash'].'&nbsp;/&nbsp;'.$val['price_cash'] * $val['performance_bai_cash']  / 100;
                    $val['product_pv'] = $val['price_cash'] * $val['performance_bai_cash']  / 100 * $val['product_quantity'];
            }
//            $ordVal['products'] = implode('<br />',$product_str);
            $ordVal['products'] = $products;

        }

        if($_info['id']){
            $data['list'] = $order_amount_list;
        }else{
           $data['list'] = '';
        }

        $this->assign( 'info', $data );
        $this->display( 'index' );
    }

    /**
     * 体验会员列表
     */
    public function memberListNot() {
        $member    = M( "member" );
        $searchKey = array();


        if ( I( 'get.userid' ) != "" ) {

            if ( ! validateExtend( I( 'get.userid' ), 'NUMBER' ) && ! validateExtend( I( 'get.userid' ), 'CHS' ) && ! validateExtend( $this->get['userid'], 'USERNAME' ) ) {
                $this->error( '会员账号格式有误' );
            }

            $searchKey_temp['mem.loginname'] = array( 'eq', I( 'get.userid' ) );
            $searchKey_temp['mem.truename']  = array( 'eq', I( 'get.userid' ) );
            $searchKey_temp['mem.nickname']  = array( 'eq', I( 'get.userid' ) );
            $searchKey_temp['mem.username']  = array( 'eq', I( 'get.userid' ) );
            $searchKey_temp['_logic']        = 'OR';
            $searchKey['_complex']           = $searchKey_temp;
        }

        if ( I( 'get.time_min' ) != "" && I( 'get.time_max' ) != "" ) {
            $searchKey ['mem.reg_time'] = array(
                'between',
                array( strtotime( I( 'get.time_min' ) . ' 0:0:0' ), strtotime( I( 'get.time_max' ) . ' 23:59:59' ) )
            );
        } elseif ( I( 'get.time_min' ) != "" ) {
            $searchKey ['mem.reg_time'] = array( 'EGT', strtotime( I( 'get.time_min' ) . ' 0:0:0' ) );
        } elseif ( I( 'get.time_max' ) != "" ) {
            $searchKey ['mem.reg_time'] = array( 'ELT', strtotime( I( 'get.time_max' ) . ' 23:59:59' ) );
        }
        $searchKey['mem.level'] = array( 'in', array( '1' ) );
        $whereSql['_string']    = ' mem.id>1 and mem.level<99 and mem.loginname>10000000000';

        $searchKey['mem.level'] = array( 'in', array( '1' ) );
        //身份类型
        $search_level = $this->get['search_level'];
        switch ( $search_level ) {
//			case 'try':
//				$searchKey['mem.level'] = array( 'eq', 1 );
//				break;
//			case 'formal':
//				$searchKey['mem.level'] = array( 'eq', 2 );
//				break;
//			case 'maker':
//				$searchKey['mem.level'] = array( 'eq', 5 );
//				break;
            case 'partner':
                $searchKey['mem.is_partner'] = array( 'eq', 1 );
                break;
            case 'service':
                $searchKey['mem.role'] = array( 'eq', 3 );
                break;
            case 'company':
                $searchKey['mem.role'] = array( 'eq', 4 );
                break;
            case 'blacklist':
                $searchKey['mem.is_blacklist'] = array( 'gt', 0 );
                break;
        }


        //判断当前管理员是否具有小管理员权限
        $is_small_super = $this->isSmallSuperManager();
        $this->assign( 'is_small_super', $is_small_super );
        if ( ! $is_small_super ) {
            //筛选级别 如果下线出现同级或高级的会员,则不获取该会员及其下级的会员信息
            if ( session( 'admin_level' ) == 99 ) {
                $whereSql['mem.repath'] = array( 'like', '%1%' );
            } else {
                $whereSql['mem.repath'] = array( 'like', '%' . session( 'admin_mid' ) . '%' );
            }
            if ( session( 'admin_level' ) == 3 || session( 'admin_level' ) == 4 ) {
                $whereSql = array_merge( $whereSql, $this->filterMember( session( 'admin_mid' ), true, 'mem', $whereSql ) );
            }
        }

        $count = $member
            ->alias( 'mem' )
            ->where( $searchKey )
            ->where( $whereSql )
            ->count();

        $page  = new \Think\Page( $count, 20, $this->get );
        $show  = $page->show();
        $_info = $member
            ->alias( 'mem' )
            ->join( 'join __MEMBER__ mem1 on mem1.id=mem.reid' )
            ->field( 'mem.*,mem1.loginname p_loginname,mem.nickname p_nickname' )
            ->where( $searchKey )
            ->where( $whereSql )
            ->order( 'reg_time desc,id desc' )
            ->limit( $page->firstRow . ',' . $page->listRows )
            ->select();

        $AccountModel = new AccountModel();
        foreach ( $_info as $k => $v ) {
            //获取账户资金余额
            $account_info = $AccountModel->getItemByUserId( $v['id'], $AccountModel->get5BalanceFields() . ',account_cash_expenditure,account_cash_income' );
            $_info[ $k ]  = ! empty( $account_info ) ? array_merge( $v, $account_info ) : $v;

            //再次判断是否为商家
            $map_store                  = [];
            $map_store['uid']           = array( 'eq', $v['id'] );
            $map_store['status']        = array( 'eq', 0 );
            $map_store['manage_status'] = array( 'eq', 1 );
            $store_info                 = M( 'Store' )->where( $map_store )->find();
            $_info[ $k ]['store_flag']  = $store_info ? '1' : '0';
        }
        $this->assign( 'info', $_info );

        $this->assign( 'admin_level', session( 'admin_level' ) );
        $this->assign( "page", $show );
        $this->display();
    }

    /**
     * 推荐关系
     */
    public function tree() {
        $str       = '';
        $upStr = '';

        $uid = $this->get['uid'];
        //判断是否为小管理员,若为小管理员,则显示全部推荐关系
        $is_small_super_manager = $this->isSmallSuperManager();
        if ( $is_small_super_manager ) {
            $top_level = 99;
            $top_id    = 1;
        }
        $Member             = M( 'member' );
        $map['id'] = array('eq',$uid);
        //排除当前登陆用户的上级或不在同一条线上的用户
// 		$map['level'] = array('elt', $top_level);
        //针对top_level是否等于6的情况进行判断处理
        if ( $top_level == 5 || $top_level == 6 || $top_level == 7 ) {
            $map['level'] = array( array( 'elt', 2 ), array( 'eq', $top_level ), 'or' );
        } elseif ( $top_level <= 2 ) {
            $map['level'] = array( 'elt', $top_level );
        } else {
            $map['level'] = array( array( 'elt', $top_level ), array( 'eq', 6 ), 'or' );
        }
        $map['_string'] = 'find_in_set(' . $top_id . ',repath)';

        $data    = $Member->where( $map )-> find();
//        dump($data);
        $level_cur = empty( $data['role'] ) ? $data['level'] : $data['role'];
        $level_cn  = '[身份:' . getLevelName( $data['level'], $data['star'] ) . ']';
        $level_cn  .= $data['is_partner'] == '1' ? '[合伙人]' : '';
        $level_cn  .= $data['store_flag'] == '1' ? '[商]' : '';
        $level_cn  .= ! empty( $data['role'] ) ? '[' . getRoleName( $data['role'] ) . ']' : '';

            $str .= '<li><a href="javascript:;" uid="' . $uid . '" level="' . $level_cur . '" onclick="getTree(this);">' . $data['username'] . '</a>[手机:' . $data['loginname'] . '][姓名:' . $data['truename'] . ']' . $level_cn . '</li>';

        $this->assign( 'tree1', $str );
        $level_cur = empty( $data['role'] ) ? $data['level'] : $data['role'];
        $level_cn  = '[身份:' . getLevelName( $data['level'], $data['star'] ) . ']';
        $level_cn  .= $data['is_partner'] == '1' ? '[合伙人]' : '';
        $level_cn  .= $data['store_flag'] == '1' ? '[商]' : '';
        $level_cn  .= ! empty( $data['role'] ) ? '[' . getRoleName( $data['role'] ) . ']' : '';

        $upStr .= '<li><a href="javascript:;" uid="' . $uid . '" level="' . $level_cur . '" onclick="getUpTree(this);">' . $data['username'] . '</a>[手机:' . $data['loginname'] . '][姓名:' . $data['truename'] . ']' . $level_cn . '</li>';

        $this->assign( 'tree2', $upStr );
        $this->display();
    }

   public function getLevelName( $level, $star = 0 ) {
        $star = $star ? $star . '星' : '';
        switch ( $level ) {
            case 1:
                return $star . '体验会员';
            case 2:
                return $star . '个人代理';
            case 5:
                return $star . '爱心创客';
            case 99:
                return $star . '管理员';
            default :
                return '未知';
        }
    }

   public function getRoleName( $role ) {
        switch ( $role ) {
            case 3:
                return '区域合伙人';
            case 4:
                return '省级合伙人';
        }
    }

    /**
     * 异步获取推荐关系
     */
    public function getTreeByAsyn() {
        $uid       = I( 'post.uid' );
        $level     = I( 'post.level' );
        $top_level = session( 'admin_level' );

        if ( ! is_numeric( $uid ) ) {
            exit( '' );
        }
        $level = empty( $level ) ? false : $level;

        //判断是否为小管理员,若为小管理员,则设置top_level=99
        $is_small_super_manager = $this->isSmallSuperManager();
        if ( $is_small_super_manager ) {
            $top_level = 99;
        }

        $Member = M( 'member' );

        $reid_repath = $Member->where( 'id=' . $uid )->getField( 'repath' );

        //$where['reid'] = array('eq', $uid);
        $where['loginname'] = array( 'gt', 10000000000 );
        $where['repath']    = array( 'eq', $reid_repath . $uid . ',' );

        //针对top_level是否等于6的情况进行判断处理
        if ( $top_level == 5 || $top_level == 6 || $top_level == 7 ) {
            $where['level'] = array( array( 'elt', 2 ), array( 'eq', $top_level ), 'or' );
        } elseif ( $top_level <= 2 ) {
            $where['level'] = array( 'elt', $top_level );
        } else {
            $where['level'] = array( array( 'elt', $top_level ), array( 'eq', 7 ), 'or' );
        }

        $list = $Member->where( $where )->select();

        if ( $level == $top_level && $uid != session( 'admin_mid' ) && $top_level != 99 ) {
            exit( '111' );
        }

        $li_html = '<ul>';
        if ( $list ) {
            foreach ( $list as $k => $data ) {
                $level_cur = empty( $data['role'] ) ? $data['level'] : $data['role'];
                $level_cn  = '[身份:' . getLevelName( $data['level'], $data['star'] ) . ']';
                $level_cn  .= $data['is_partner'] == '1' ? '[合伙人]' : '';
                $level_cn  .= $data['store_flag'] == '1' ? '[商]' : '';
                $level_cn  .= ! empty( $data['role'] ) ? '[' . getRoleName( $data['role'] ) . ']' : '';

                $li_html .= '<li><a href="javascript:;" uid="' . $data['id'] . '" level="' . $level_cur . '" onclick="getTree(this);">' . $data['username'] . '</a>[手机:' . $data['loginname'] . '][姓名:' . $data['truename'] . ']' . $level_cn . '</li>';
            }
            $li_html .= '</ul>';
            echo $li_html;
        } else {
            exit( '' );
        }
    }

    /**
     * 异步获取推荐关系上级
     */
    public function getUpTreeByAsyn() {
        $uid       = I( 'post.uid' );
        $level     = I( 'post.level' );
        $top_level = session( 'admin_level' );

        if ( ! is_numeric( $uid ) ) {
            exit( '' );
        }
        $level = empty( $level ) ? false : $level;

        //判断是否为小管理员,若为小管理员,则设置top_level=99
        $is_small_super_manager = $this->isSmallSuperManager();
        if ( $is_small_super_manager ) {
            $top_level = 99;
        }

        $Member = M( 'member' );

        $reid = $Member->where( 'id=' . $uid )->find( );

        $list = $Member->where( array('id'=>$reid['reid']) )->select();

        $li_html = '<ul>';
        if ( $list ) {
            foreach ( $list as $k => $data ) {
                $level_cur = empty( $data['role'] ) ? $data['level'] : $data['role'];
                $level_cn  = '[身份:' . getLevelName( $data['level'], $data['star'] ) . ']';
                $level_cn  .= $data['is_partner'] == '1' ? '[合伙人]' : '';
                $level_cn  .= $data['store_flag'] == '1' ? '[商]' : '';
                $level_cn  .= ! empty( $data['role'] ) ? '[' . getRoleName( $data['role'] ) . ']' : '';

                $li_html .= '<li><a href="javascript:;" uid="' . $data['id'] . '" level="' . $level_cur . '" onclick="getUpTree(this);">' . $data['username'] . '</a>[手机:' . $data['loginname'] . '][姓名:' . $data['truename'] . ']' . $level_cn . '</li>';
            }
            $li_html .= '</ul>';
            echo $li_html;
        } else {
            exit( '' );
        }
    }

    /**
     * 搜索推荐关系
     */
    public function searchAccountByAsyn() {
        $account   = I( 'post.account' );
        $top_level = session( 'admin_level' );
        $top_id    = session( 'admin_mid' );

        if ( ! validateExtend( $account, 'NUMBER' ) && ! validateExtend( $account, 'CHS' ) && ! validateExtend( $account, '/^([0-9a-zA-Z])+$/', true ) ) {
            exit( '会员账号格式有误' );
        }

        //判断是否为小管理员,若为小管理员,则设置top_level=99
        $is_small_super_manager = $this->isSmallSuperManager();
        if ( $is_small_super_manager ) {
            $top_level = 99;
            $top_id    = 1;
        }

        $Member             = M( 'member' );
        $where['loginname'] = array( 'eq', $account );
        $where['truename']  = array( 'eq', $account );
        $where['nickname']  = array( 'eq', $account );
        $where['username']  = array( 'eq', $account );
        $where['_logic']    = 'OR';
        $map['_complex']    = $where;

        $map['loginname'] = array( 'gt', 10000000000 );

        //排除当前登陆用户的上级或不在同一条线上的用户
// 		$map['level'] = array('elt', $top_level);
        //针对top_level是否等于6的情况进行判断处理
        if ( $top_level == 5 || $top_level == 6 || $top_level == 7 ) {
            $map['level'] = array( array( 'elt', 2 ), array( 'eq', $top_level ), 'or' );
        } elseif ( $top_level <= 2 ) {
            $map['level'] = array( 'elt', $top_level );
        } else {
            $map['level'] = array( array( 'elt', $top_level ), array( 'eq', 6 ), 'or' );
        }
        $map['_string'] = 'find_in_set(' . $top_id . ',repath)';

        $list    = $Member->where( $map )->select();
        $li_html = '<ul>';
        if ( $list ) {
            foreach ( $list as $k => $data ) {
                $level_cur = empty( $data['role'] ) ? $data['level'] : $data['role'];
                $level_cn  = '[身份:' . getLevelName( $data['level'], $data['star'] ) . ']';
                $level_cn  .= $data['is_partner'] == '1' ? '[合伙人]' : '';
                $level_cn  .= $data['store_flag'] == '1' ? '[商]' : '';
                $level_cn  .= ! empty( $data['role'] ) ? '[' . getRoleName( $data['role'] ) . ']' : '';

                $li_html .= '<li><a href="javascript:;" uid="' . $data['id'] . '" level="' . $level_cur . '" onclick="getTree(this);">' . $data['username'] . '</a>[手机:' . $data['loginname'] . '][姓名:' . $data['truename'] . ']' . $level_cn . '</li>';
            }
            $li_html .= '</ul>';
            echo $li_html;
        } else {
            exit( '' );
        }
    }

    /**
     * 会员信息详情
     */
    public function memberModify() {
        $id = I( 'get.id' );

        $extend = I( 'get.extend' );
        $id     = ! empty( $extend ) ? $extend : $id;

        if ( $id == "" ) {
            $this->error( '数据错误' );
        }

        $_info = M( 'member' )
            ->alias( 'mem' )
            ->join( 'left join __WITHDRAW_BANKCARD__ wib ON wib.uid=mem.id' )
            ->where( "mem.id=" . $id )
            ->field( 'mem.*,wib.uid,wib.inaccname,wib.inacc,wib.inaccbank,wib.inaccadd,wib.bankcode,wib.bankcode_max,wib.bank_pcd' )
            ->find();

        if ( empty( $_info ) ) {
            $this->error( '数据错误' );
        }
        if ( ! empty( $_info['weixin'] ) ) {
            $_info['weixin'] = unserialize( $_info['weixin'] );
        }
        $this->assign( 'info', $_info );

        //获取所有银行名称信息
        $bank_list = M( 'Bank' )->field( 'bank' )->order( 'id asc' )->select();
        $this->assign( 'bank_list', $bank_list );

        $this->display();
    }

    /**
     * 保存会员信息
     */
    public function memberSave() {
        $member           = M( "member" );
        $WithdrawBankcard = M( 'WithdrawBankcard' );

        M()->startTrans();

        $data = I( 'post.data' );
        $data = trimarray( $data );

        if ( ! validateExtend( $data['id'], 'NUMBER' ) ) {
            $this->error( '数据错误' );
        }

        foreach ( $data as $k => $v ) {
            if ( empty( $v ) ) {
                unset( $data[ $k ] );
            }
        }

        if ( $data['password'] != "" ) {
            if ( $data['password'] != $data['repassword'] ) {
                $this->error( '登陆密码和确认登陆密码不一致' );
            }
            $data['password'] = md5( $data['password'] );
        } else {
            unset( $data['password'] );
        }
        if ( $data['safe_password'] != "" ) {
            if ( $data['safe_password'] != $data['resafe_password'] ) {
                $this->error( '安全密码和确认安全密码不一致' );
            }
            $data['safe_password'] = md5( $data['safe_password'] );
        } else {
            unset( $data['safe_password'] );
        }

        //银行卡
        $result1 = true;
        if ( isset( $data['bank'] ) ) {
            $map_bank['uid'] = array( 'eq', $data['id'] );
            $bank_data       = array(
                'inacc'        => $data['bank']['inacc'],
                'inaccbank'    => $data['bank']['inaccbank'],
                'bankcode'     => $data['bank']['bankcode'],
                'bankcode_max' => $data['bank']['bankcode_max']
            );
            unset( $data['bank'] );

            $result1 = $WithdrawBankcard->where( $map_bank )->save( $bank_data );
        }

        $result2 = M( 'member' )->save( $data );

        if ( $result1 === false || $result2 === false ) {
            M()->rollback();
            $this->error( '修改失败' );
        } else {
            M()->commit();

            $log_data = $member->field( 'loginname,nickname,username' )->where( 'id=' . $data['id'] )->find();
            $this->success( '操作成功', '', false, '修改' . $log_data['username'] . '[' . $log_data['loginname'] . '][' . $log_data['nickname'] . ']会员个人资料' );
        }
    }

    /**
     * 激活开通会员
     */
    public function memberOpen() {
        $uid = I( 'get.id' );

        if ( ! is_numeric( $uid ) ) {
            $this->error( '数据错误' );
        }

        $member = M( 'member' );

        $Info  = $member->where( "id=" . $uid )->find();
        $level = $member->where( 'id=' . $Info['reid'] )->getField( 'level' );
        if ( $level < 2 ) {
            $this->error( '你的上级还不是创客用户！' );
        }
        if ( $Info['is_pass'] == 1 ) {
            $this->error( '不需要重复激活' );
        }

        $where['id'] = $Info['id'];

        $udata1['is_pass']   = 1;
        $udata1['level']     = 2;
        $udata1['open_time'] = time();

        $member->where( $where )->save( $udata1 );

        $where_r['id'] = $Info['reid'];
        $member->where( $where_r )->setInc( 'recount', 1 ); //推荐人数加1
        #M()->execute(C('ALIYUN_TDDL_MASTER') . 'call recommand_bonus (' . $Info['id'] . ',@msg)');

        //操作记录
        $where_log['id'] = array( 'eq', $uid );
        $log_data        = $member->field( 'loginname,nickname,username' )->where( $where_log )->find();
        $this->success( '操作成功', '', false, '开通升级' . $log_data['username'] . '[' . $log_data['loginname'] . '][' . $log_data['nickname'] . ']为创客用户' );
    }

    /**
     * 删除会员
     */
    public function memberDelete() {
        $id = I( 'get.id', '' );

        if ( ! is_numeric( $id ) ) {
            $this->error( '数据错误' );
        }

        $member = M( "member" );
        $uInfo  = $member->where( "id=" . $id )->find();
        if ( empty( $uInfo ) ) {
            $this->error( '数据错误' );
        }
        if ( $uInfo['is_pass'] == 1 ) {
            $this->error( '不删除已经审核的会员' );
        }

        $where_m['repath'] = array( 'like', "%," . $id . ",%" );
        $row               = $member->where( $where_m )->select();
        if ( $row ) {
            $this->error( '不能删除有下级的体验会员 ！' );
            exit;
        }

        //操作记录
        $where_log['id'] = array( 'eq', $id );
        $log_data        = $member->field( 'loginname,nickname,username' )->where( $where_log )->find();
        $member->where( "id=" . $id )->delete();
        $this->success( '操作成功', '', false, '未激活会员列表删除' . $log_data['username'] . '[' . $log_data['loginname'] . '][' . $log_data['nickname'] . ']会员' );
    }

    /**
     * 会员锁定与解锁
     */
    public function memberLock() {

        $id  = I( 'get.id' );
        $b   = I( 'get.b' );
        $tid = I( 'get.tid' );

        if ( ! is_numeric( $id ) ) {
            $this->error( '数据错误' );
        }
        if ( $id == '1' ) {
            $this->error( "不能对管理员操作" );
        }

        $member = M( 'member' );
        $data   = $member->where( "id=" . $id )->find();
        if ( empty( $data ) ) {
            $this->error( '数据错误' );
        }

        if ( $data['is_lock'] == 1 ) {
            $_u_d['is_lock']          = 0;
            $_u_d['last_unlock_time'] = time();
            $member->where( "id=" . $data['id'] )->save( $_u_d );

            //操作记录
            $where_log['id'] = array( 'eq', $data['id'] );
            $log_data        = $member->field( 'loginname,nickname,username' )->where( $where_log )->find();
            $log_content     = '已解锁' . $log_data['username'] . '[' . $log_data['loginname'] . '][' . $log_data['nickname'] . ']为会员';
            if ( $b == '1' ) {
                M( "app_unlock" )->where( "id=" . $tid )->setField( 'pass_time', time() );
            }
        } else {
            $member->where( "id=" . $data['id'] )->setField( 'is_lock', 1 );

            //操作记录
            $where_log['id'] = array( 'eq', $data['id'] );
            $log_data        = $member->field( 'loginname,nickname,username' )->where( $where_log )->find();
            $log_content     = '已锁定' . $log_data['username'] . '[' . $log_data['loginname'] . '][' . $log_data['nickname'] . ']为会员';
        }

        $this->success( '操作成功', '', false, $log_content );
    }

    /**
     * 会员等级更改处理
     */
    public function memberGrade() {
        $member          = M( 'member' );
        $ServiceClearing = M( 'ServiceClearing' );

        $uid     = I( 'get.id' );
        $service = intval( I( 'get.service' ) );
        $company = intval( I( 'get.company' ) );

        $count = M( 'member' )->where( 'id=' . $uid )->count();
        if ( $count == 0 ) {
            $this->error( '数据错误！' );
            exit;
        }

        $level = M( 'member' )->where( 'id=' . $uid )->getField( 'level' );
        //$loginname = M('member')->where('id='.$uid)->getField('loginname');

        //获取积分相关的配置参数
        $service_company_points_clear = C( 'PARAMETER_CONFIG.POINTS' )['service_company_points_clear']; //2:取消服务/区域合伙人身份时,自动扣除对应分红股

        M()->startTrans();

        //取消服务中心
        if ( $service == 1 ) {

            $whereSql['id'] = $uid;
            $result         = M( 'member' )->where( $whereSql )->setField( 'role', '' );
            if ( ! $result ) {
                $this->error( '取消服务中心失败！', U( 'Admin/Member/memberList' ) );
                exit;
            }

            //同步删除对应管理员用户
            $SystemManager = new \Admin\Controller\AjaxController();
            $result0       = $SystemManager->memberDeleteAsyn( $uid, 'service' );

            if ( $result === false || $result0 === false ) {
                $this->error( '取消服务中心失败,请稍后重试', U( 'Admin/Member/memberList' ) );
            }

            M()->commit();

            //操作记录
            $where_log['id'] = array( 'eq', $uid );
            $log_data        = $member->field( 'loginname,nickname,username' )->where( $where_log )->find();
            $this->success( '取消服务中心成功！', U( 'Admin/Member/memberList' ), false, '会员列表取消' . $log_data['username'] . '[' . $log_data['loginname'] . '][' . $log_data['nickname'] . ']会员的服务中心，降级为创客用户' );
            exit;

        }

        //设置服务中心
        if ( $service == 2 ) {

            $where['id']  = $uid;
            $data['role'] = 3;
            $data['menu'] = M( 'parameter', 'g_' )->where( 'id=1' )->getField( 'service_auth' );
            $result       = M( 'member', 'zc_' )->where( $where )->save( $data );

            //同步添加对应管理员用户
            $manager_data  = array(
                'uid'      => $uid,
                'group_id' => array( C( 'ROLE_MUST_LIST.service' ) ),
                'type'     => 'service',
            );
            $SystemManager = new \Admin\Controller\AjaxController();
            $result1       = $SystemManager->memberAddAsyn( $manager_data );

            if ( $result === false || $result1 === false ) {
                $this->error( '设置服务中心失败,请稍后重试！', U( 'Admin/Member/memberList' ) );
                exit;
            }

            M()->commit();

            //操作记录
            $where_log['id'] = array( 'eq', $uid );
            $log_data        = $member->field( 'loginname,nickname,username' )->where( $where_log )->find();
            $this->success( '设置服务中心成功！', U( 'Admin/Member/memberList' ), false, '会员列表升级' . $log_data['username'] . '[' . $log_data['loginname'] . '][' . $log_data['nickname'] . ']会员为服务中心' );
            exit;

        }

        //取消区域合伙人
        if ( $company == 1 ) {

            $whereSql['id'] = $uid;
            $result         = M( 'member' )->where( $whereSql )->setField( 'role', '' );

            //同步删除对应管理员用户
            $SystemManager = new \Admin\Controller\AjaxController();
            $result0       = $SystemManager->memberDeleteAsyn( $uid, 'agent' );

            //同步更新定时结算状态(更新为未激活)
            $service_clearing_info = $ServiceClearing->where( 'user_id=' . $uid )->find();
            if ( $service_clearing_info ) {
                $data_service_celaring = [
                    'clearing_status' => 0,
                    'clearing_uptime' => time()
                ];
                $result2               = $ServiceClearing->where( 'user_id=' . $uid )->save( $data_service_celaring );
            }

            if ( $result === false || $result0 === false ) {
                $this->error( '取消区域合伙人失败,请稍后重试！', U( 'Admin/Member/memberList' ) );
                exit;
            }

            M()->commit();

            //操作记录
            $where_log['id'] = array( 'eq', $uid );
            $log_data        = $member->field( 'loginname,nickname' )->where( $where_log )->find();
            $this->success( '取消区域合伙人成功！', U( 'Admin/Member/memberList' ), false, '会员列表取消' . $log_data['username'] . '[' . $log_data['loginname'] . '][' . $log_data['nickname'] . ']会员的区域合伙人，降级为创客用户' );
            exit;

        }

        //设置区域合伙人
        if ( $company == 2 ) {

            $where['id']  = $uid;
            $data['role'] = 4;
            $data['menu'] = M( 'parameter', 'g_' )->where( 'id=1' )->getField( 'service_auth' );
            $result       = M( 'member', 'zc_' )->where( $where )->save( $data );

            //同步添加对应管理员用户
            $manager_data  = array(
                'uid'      => $uid,
                'group_id' => array( C( 'ROLE_MUST_LIST.agent' ) ),
                'type'     => 'agent',
            );
            $SystemManager = new \Admin\Controller\AjaxController();
            $result1       = $SystemManager->memberAddAsyn( $manager_data );

            if ( $result === false || $result1 === false ) {
                $this->error( '设置区域合伙人失败,请稍后重试！', U( 'Admin/Member/memberList' ) );
                exit;
            }

            M()->commit();

            //操作记录
            $where_log['id'] = array( 'eq', $uid );
            $log_data        = $member->field( 'loginname,nickname' )->where( $where_log )->find();
            $this->success( '设置区域合伙人成功！', U( 'Admin/Member/memberList' ), false, '会员列表升级' . $log_data['username'] . '[' . $log_data['loginname'] . '][' . $log_data['nickname'] . ']会员为区域合伙人' );
            exit;

        }

        $this->error( '操作失败！' );
        exit;
    }

    /**
     * 会员信息
     */
    public function memberBonusInfo() {
        $AccountModel = new AccountModel();
        $LockModel = new LockModel();

        $user_id = $this->get['uid'];

        if (!validateExtend($user_id, 'NUMBER')) {
            $this->myApiPrint('参数格式有误', 300);
        }
        $member_info = M( 'Member' )->where( 'id=' . $user_id )->field( 'nickname,loginname,username' )->find();
        $this->assign( "member_info", $member_info );
        //通证汇总
        /* $income_account = $AccountModel->getFieldsValues('account_goldcoin_income,account_bonus_income', "user_id={$user_id} and account_tag=0");
        $income_lock = $LockModel->getInfo('lock_amount', "user_id={$user_id}");
        $income_lock_amount = !$income_lock ? 0 : $income_lock['lock_amount'];
        $data['income_amount'] = $income_account['account_goldcoin_income'] + $income_account['account_bonus_income'] + $income_lock_amount; */
        $income_amount = $this->getUserIncome($user_id);

        $data['income_amount'] = sprintf('%.4f', $income_amount);

        //流通资产
        $data['amount_liutong'] = $AccountModel->getBalance($user_id, Currency::GoldCoin);
        $data['amount_liutong'] = sprintf('%.4f', $data['amount_liutong']);

        //锁定通证
        /* 旧锁定通证 */
        $LockModel = new LockModel();
        $map_lock['user_id'] = array('eq', $user_id);
        $lock_info = $LockModel->getInfo('*', $map_lock);
        $data['amount_lock_old'] = sprintf('%.4f', $lock_info['lock_amount']);
        /* 新锁定通证 */
        $data['amount_lock'] = $AccountModel->getBalance($user_id, Currency::Bonus);
        $data['amount_lock'] = sprintf('%.4f', $data['amount_lock']);

        $this->assign( 'total_income', $data );
        $this->display();
    }

    /**
     * 获取用户通证汇总
     *
     * @param int $user_id 用户ID
     */
    private function getUserIncome($user_id = 0)
    {
        $LockModel = new LockModel();

        if (empty($user_id)) {
            return 0;
        }

        $income_amount_AccountIncome = M('AccountIncome')->where("income_tag=0 and user_id={$user_id}")->getField('income_total');

        $income_amount_AccountIncome = !$income_amount_AccountIncome ? 0 : $income_amount_AccountIncome;

        $income_amount_LockTotalAmount = $LockModel->getInfo('total_amount', "user_id={$user_id}");
        $income_amount_LockTotalAmount = !$income_amount_LockTotalAmount ? 0 : $income_amount_LockTotalAmount['total_amount'];

        $income_amount_MiningAmount = M('Mining')->where("user_id={$user_id} and tag=" . Tag::getDay())->field('amount')->find();
        $income_amount_MiningAmount = !$income_amount_MiningAmount ? 0 : $income_amount_MiningAmount['amount'];

        $income_amount = $income_amount_AccountIncome + $income_amount_LockTotalAmount + $income_amount_MiningAmount;

        return $income_amount;
    }

    /**
     * 会员资金变动明细
     */
    public function memberFinanceInfo() {
        $RecordAccountChange = M( 'RecordAccountChange' );

        $uid        = $this->get['uid'];
        $start_time = $this->get['start_time'];
        $end_time   = $this->get['end_time'];

        if ( empty( $uid ) ) {
            $this->error( 'UID参数格式有误' );
        }

        $map_change = array();

        $map_change['uid'] = array( 'eq', $uid );
        if ( ! empty( $start_time ) ) {
            $map_change['time'][] = array( 'egt', strtotime( $start_time . ' 00:00:00' ) );
        }
        if ( ! empty( $end_time ) ) {
            $map_change['time'][] = array( 'elt', strtotime( $end_time . ' 23:59:59' ) );
        }
        if ( count( $map_change['time'] ) > 0 ) {
            if ( isset( $map_change['time'][1] ) ) {
                $map_change['time'][] = 'and';
            } else {
                $map_change['time'] = $map_change['time'][0];
            }
        }

        $count = $RecordAccountChange->where( $map_change )->count();
        $limit = $this->Page( $count, 20, $this->get );

        //获取资金变动数据
        $list = M( 'RecordAccountChange' )->where( $map_change )->limit( $limit )->order( 'time desc,id desc' )->select();

        //对数据进行处理
        $list_new = array();
        $key_list = array( 'goldcoin', 'colorcoin', 'cash', 'points', 'bonus' );
        foreach ( $list as $k => $v ) {
            foreach ( $key_list as $k1 => $v1 ) {
                $change = sprintf( '%.4f', $v[ 'new_' . $v1 ] - $v[ 'old_' . $v1 ] );
                if ( $change > 0 ) {
                    $list_new[ $k ][ $v1 ]['value'] = '+' . $change;
                } else {
                    $list_new[ $k ][ $v1 ]['value'] = $change;
                }
                $list_new[ $k ][ $v1 ]['old'] = $v[ 'old_' . $v1 ];
                $list_new[ $k ][ $v1 ]['new'] = $v[ 'new_' . $v1 ];
            }
            $list_new[ $k ]['time'] = $v['time'];
        }
        $this->assign( 'list', $list_new );

        //查询账户余额
        $account = M( 'member' )->field( 'loginname,nickname,username,goldcoin,colorcoin,cash,points,bonus' )->find( $uid );
        $this->assign( "account", $account );

        $this->display();
    }

    /**
     * 超级管理员身份模拟切换到其他账户一键登录
     */
    public function superLogin() {
        $Member  = M( 'Member' );
        $Manager = D( 'Manager' );

        $member_id = $this->get['member_id'];

        if ( ! is_numeric( $member_id ) ) {
            $this->error( '参数格式有误' );
        }

        //判断当前账号是否为小管理员
        $is_small_super_manager = $this->isSmallSuperManager();

        $sess_auth = $this->get( 'sess_auth' );
        if ( $sess_auth['admin_id'] != 1 && ! $is_small_super_manager ) {
            $this->error( '非超级管理员/小管理员无权使用一键登录其他账号功能' );
        } else {
            $map_current['mem.id'] = array( 'eq', $sess_auth['admin_id'] );
            $current_manager       = $Manager->getMemberList( $map_current, false, 'm.nickname,m.loginname,m.username' );
        }

        $map_member['id'] = array( 'eq', $member_id );
        $member_info      = $Member->where( $map_member )->field( 'loginname,level,role,password,nickname,username' )->find();
        if ( ! $member_info ) {
            $this->error( '该账号已不存在' );
        } elseif ( $member_info['role'] <= 0 ) {
            $this->error( '该账号当前身份无权登录' );
        }

        //模拟登录
        $login_data   = array(
            'username' => $member_info['loginname'],
            'password' => $member_info['password'],
        );
        $login_status = $Manager->checkExist( $login_data );
        if ( $login_status['error'] === false ) {
            session( 'admin_super_login', $sess_auth['admin_id'] );
            $this->success( '登录成功', U( 'Admin/Index/index' ), false, "管理员{$current_manager['loginname']}[{$current_manager['nickname']}]成功以{$member_info['loginname']}[{$member_info['nickname']}]身份登录后台" );
        } else {
            $this->error( $login_status['data'] );
        }
    }

    /**
     * 一键分配服务中心和区域合伙人管理员账号
     */
    public function batchManager() {
        $this->error( '此功能已禁用' );
        //保证全部执行完成
        set_time_limit( 0 );
        ignore_user_abort( true );

        $Member = M( 'Member' );

        if ( session( 'admin_id' ) != 1 ) {
            $this->error( '无操作权限' );
        }

        $role_must_list = C( 'ROLE_MUST_LIST' );
        $Manager        = new \Admin\Controller\AjaxController();

        $error        = false;
        $serviceAgent = $Member
            ->alias( 'mem' )
            ->join( 'left join __MANAGER__ man ON man.uid=mem.id' )
            ->where( 'mem.level>2 and mem.level<5 and mem.is_lock=0' )
            ->field( 'mem.loginname,mem.level,mem.nickname,mem.username,mem.id mid,man.id' )
            ->select();

        $type      = '';
        $role_name = '';
        foreach ( $serviceAgent as $k => $list ) {
            switch ( $list['level'] ) {
                case 3:
                    $group_id  = array( $role_must_list['service'] );
                    $role_name = '服务中心';
                    $type      = 'service';
                    break;
                case 4:
                    $group_id  = array( $role_must_list['agent'] );
                    $role_name = '区域合伙人';
                    $type      = 'agent';
                    break;
                default:
                    $error .= "{$serviceAgent['loginname']}[{$serviceAgent['nickname']}]分配管理员账号非法.\r\n";
                    continue;
            }

            //过滤掉已经分配过商家管理员角色的
            if ( isset( $list['id'] ) ) {
                $map_group_access['group_id'] = $group_id[0];
                $map_group_access['uid']      = array( 'eq', $list['id'] );
                $group_acess_info             = M( 'AuthGroupAccess' )->where( $map_group_access )->find();
                if ( $group_acess_info ) {
                    continue;
                }
            }

            $manager_data = array(
                'uid'      => $list['mid'],
                'group_id' => $group_id,
                'type'     => $type,
            );

            $status = $Manager->memberAddAsyn( $manager_data );
            if ( ! $status ) {
                $error .= "{$serviceAgent['loginname']}[{$serviceAgent['nickname']}]分配[{$role_name}]管理员账号失败.\r\n";
            }
        }

        if ( $error ) {
            $this->error( "部分账号分配失败:\r\n{$error}", U( 'Member/memberList' ), 20 );
        } else {
            $this->success( '账号已全部分配成功', U( 'Member/memberList' ), false, "一键分配服务中心和区域合伙人管理员账号" );
        }

        exit;
    }

    /**
     * 会员加入/移出黑名单
     */
    public function memberBlackList() {
        $Member = M( 'member' );

        $id_list        = $this->post['id'];
        $blacklist_type = $this->post['blacklist_type'];

        if ( empty( $id_list ) ) {
            exit( '请选择要操作的用户' );
        }

        //判断要设置的黑名单类型是否存在
        $blacklist_type_config = C( 'FIELD_CONFIG.member' )['is_blacklist'];
        if ( ! array_key_exists( $blacklist_type, $blacklist_type_config ) ) {
            exit( '操作的黑名单类型不存在' );
        }

        M()->startTrans();
        $map_member['id'] = array( 'in', implode( ',', $id_list ) );
        $data_member      = array(
            'is_blacklist' => $blacklist_type,
        );
        if ( $Member->where( $map_member )->save( $data_member ) === false ) {
            exit( '批量设置用户黑名单类型失败' );
        } else {
            M()->commit();
            $this->logWrite( "批量设置用户黑名单类型为[{$blacklist_type_config[$blacklist_type]}黑名单]" );
            exit;
        }
    }

    /**
     * 会员累计收益明细
     */
    public function memberIncomeInfo() {
        $uid = $this->get['uid'];

        if ( ! validateExtend( $uid, 'NUMBER' ) ) {
            $this->error( 'UID参数格式有误' );
        }

        //查询账户
        $account = M( 'member' )->field( 'loginname,nickname,username' )->find( $uid );
        $this->assign( "account", $account );

        $AccountFinanceModel = new AccountFinanceModel();

        //累计通证汇总+累计当月通证汇总
        $income['total_income'] = $AccountFinanceModel->getItemByUserId( $uid, 'finance_total' );
        $income['month_income'] = $AccountFinanceModel->getItemByUserId( $uid, 'finance_total', date( 'Ym' ) );
        $this->assign( 'income', $income );

        //月收益列表
        $income_month_list = $AccountFinanceModel->getListByUserId( $uid, '*', date( 'Ym' ) );
        $this->assign( 'list', $income_month_list );

        $this->display();
    }

    /**
     * 业绩查询
     */
    public function performance() {
        if ( $_POST ) {
            $this->performanceAction();
            $this->logWrite( "操作了业绩查询" );
        }

        $this->display();
    }

    /**
     * 业绩查询执行
     */
    private function performanceAction() {
        $Member = M( 'Member' );
        $Orders = M( 'Orders' );

        $phone1   = $this->post['phone1'];
        $phone2   = $this->post['phone2'];
        $time_min = $this->post['time_min'];
        $time_max = $this->post['time_max'];

        if ( ! validateExtend( $phone1, 'MOBILE' ) ) {
            $this->error( '主号格式有误', U( __CONTROLLER__ . '/performance' ) );
        }
        if ( ! empty( $phone2 ) ) {
            $phone2_arr = preg_match( '/\s/', $phone2 ) ? explode( ' ', $phone2 ) : array( $phone2 );
            foreach ( $phone2_arr as $ph ) {
                if ( ! validateExtend( $ph, 'MOBILE' ) ) {
                    $this->error( '截止号格式有误', U( __CONTROLLER__ . '/performance' ) );
                }
            }

            $phone2 = preg_replace( '/\s/', ',', $phone2 );
        }

        $time_min = empty( $time_min ) ? null : strtotime( $time_min . ' 00:00:00' );
        $time_max = empty( $time_max ) ? null : strtotime( $time_max . ' 23:59:59' );

        $map           = [];
        $map_repath    = [];
        $assign_phone1 = [];
        $assign_phone2 = [];

        //查询日期
        if ( ! empty( $time_min ) ) {
            $map['ord.time'][] = array( 'egt', $time_min );
        }
        if ( ! empty( $time_max ) ) {
            $map['ord.time'][] = array( 'elt', $time_max );
        }
        if ( count( $map['ord.time'] ) > 0 ) {
            if ( count( $map['ord.time'] ) > 1 ) {
                $map['ord.time'][] = 'and';
            } else {
                $map['ord.time'] = $map['ord.time'][0];
            }
        }

        //查询$phone1的ID
        $map_phone1['loginname'] = array( 'eq', $phone1 );
        $phone1_info             = $Member->where( $map_phone1 )->field( 'id,nickname' )->find();
        if ( ! $phone1_info ) {
            $this->error( '主号不存在', U( __CONTROLLER__ . '/performance' ) );
        }
        $map_repath['mem.repath'][] = array( 'like', "%,{$phone1_info['id']},%" );
        $assign_phone1              = [ $phone1 => $phone1_info['nickname'] ];

        //查询$phone2的ID[列表]
        if ( ! empty( $phone2 ) ) {
            $map_phone2['loginname'] = array( 'in', $phone2 );
            $phone2_info             = $Member->where( $map_phone2 )->field( 'id,nickname,loginname,repath' )->select();
            foreach ( $phone2_info as $k => $ph ) {
                $map_repath['mem.repath'][] = array( 'notlike', "%,{$ph['id']},%" );
                $map_repath['mem.id'][]     = array( 'neq', $ph['id'] );

                //查询该帐号是否与主号为同一条线
                $is_line         = preg_match( '/(^|,)' . $phone1_info['id'] . '($|,)/', $ph['repath'] ) ? true : false;
                $assign_phone2[] = [ $ph['loginname'] => [ 'nickname' => $ph['nickname'], 'is_line' => $is_line ] ];

            }
            $map_repath['mem.repath'][] = 'and';
            $map_repath['mem.id'][]     = 'and';
        } else {
            $map_repath['mem.repath'] = $map_repath['mem.repath'][0];
        }

        $temp['_complex'] = $map_repath;
        $temp['mem.id']   = array( 'eq', $phone1_info['id'] );
        $temp['_logic']   = 'or';

        $map['_complex'] = $temp;

        //查询兑换额
        $map['ord.order_status'] = array( 'eq', 4 );
        $map['ord.amount_type']  = array( 'in', '1,5' );
        $map['ord.dutypay']      = array( 'eq', 0 );
        $trade_money             = $Member
            ->alias( 'mem' )
            ->join( 'join __ORDERS__ ord ON ord.uid=mem.id' )
            ->join( 'join __PROFITS_BONUS__ prb ON prb.order_number=ord.order_number' )
            ->where( $map )
            ->sum( 'ord.amount-prb.profits' );
        $this->assign( 'profits_money', sprintf( '%.2f', $trade_money ) );

        $this->assign( 'assign_phone1', $assign_phone1 );
        $this->assign( 'assign_phone2', $assign_phone2 );
    }

    /**
     * 导出账户明细
     */
    public function memberBonusInfoExportAction() {
        $where = '';
        $type  = 2; //收支类型(0支出,1收入,2全部)

        //变量
        $uid           = $this->get['uid'];
        $page          = $this->get['p'] > 0 ? $this->get['p'] : 1;
        $balance_type  = $this->get['balance_type'];
        $start_time    = $this->get['start_time'];
        $end_time      = $this->get['end_time'];
        $trade_status  = $this->get['trade_status'];
        $bonus_type    = $this->get['bonus_type'];
        $currency_type = $this->get['member_cash'];

        //验证变量
        if ( empty( $uid ) ) {
            $this->error( 'UID参数格式有误' );
        }

        //对变量进行处理
        $start_time = ! empty( $start_time ) ? strtotime( $start_time . ' 00:00:00' ) : strtotime( date( "Y-m-01" ) );
        $end_time   = ! empty( $end_time ) ? strtotime( $end_time . ' 23:59:59' ) : time();

        //针对用户账户明细[收入支出]筛选条件进行处理
        if ( $balance_type == 'income' ) {
            $type = 1;
        } elseif ( $balance_type == 'expense' ) {
            $type = 0;
        }

        //日期筛选
        $month = date( 'Ym' );
        if ( ! empty( $start_time ) ) {
            $where .= " and record_addtime>='{$start_time}' ";
            $month = date( 'Ym', $start_time );
        } else {
            $where .= " and record_addtime>='" . strtotime( date( 'Ym' ) . '01' ) . "' ";
        }
        if ( ! empty( $end_time ) ) {
            $where .= " and record_addtime<='{$end_time}' ";
            $month = date( 'Ym', $start_time );
        } else {
            $where .= " and record_addtime<='" . strtotime( date( 'Ymd' ) . ' 23:59:59' ) . "' ";
        }
        if ( date( 'Ym', $start_time ) != date( 'Ym', $end_time ) ) {
            $this->error( '查询日期必须在同一个月' );
        }

        //不启用分页
        $page = false;

        //针对用户账户明细[收支类型]筛选条件进行处理
        if ( ! empty( $bonus_type ) ) {
            $where .= " and record_action='{$bonus_type}' ";
        }

        //获取配置参数
        $parameter = M( 'parameter', 'g_' )->where( 'id=1' )->find();
        $this->assign( 'parameter', $parameter );

        $member_cash = empty( $_GET['member_cash'] ) ? 'cash' : $_GET['member_cash'];  //账户类型

        $currency = '';
        switch ( $currency_type ) {
            case 'goldcoin':
                $currency = Currency::GoldCoin;
                break;
            case 'colorcoin':
                $currency = Currency::ColorCoin;
                break;
            case 'points':
                $currency = Currency::Points;
                break;
            case 'bonus':
                $currency = Currency::Bonus;
                break;
            case 'enroll':
                $currency = Currency::Enroll;
                break;
            case 'credits':
                $currency = Currency::Credits;
                break;
            case 'supply':
                $currency = Currency::Supply;
                break;
            case 'enjoy':
                $currency = Currency::Enjoy;
                break;
            default:
                $currency = Currency::Cash;
        }

        $AccountRecord = new AccountRecordModel();
        $data          = $AccountRecord->getPageList( $uid, $currency, $month, $page, $type, 10, $where );

        $list = $data['list'];
        foreach ( $list as $k => $v ) {
            $attach                  = json_decode( $v['record_attach'], true );
            $attach                  = $AccountRecord->initAtach( $attach, $currency, $month, $v['record_id'], $v['record_action'] );
            $list[ $k ]['from_name'] = $attach['from_name'] . ( empty( $attach['loginname'] ) ? '' : "[{$attach['loginname']}]" );
        }

        $export_data = [];
        foreach ( $list as $k => $v ) {
            $export_data[ $k ] = [
                $v['record_id'],
                $v['from_name'],
                $v['record_amount'] > 0 ? $v['record_amount'] : 0,
                $v['record_amount'] < 0 ? $v['record_amount'] : 0,
                $v['record_remark'],
                $v['record_balance'],
                date( 'Y-m-d H:i:s', $v['record_addtime'] ),
            ];
        }

        $head_array = array( '序号', '来源', '收入', '支出', '收支类型', '停留余额', '时间' );
        $file_name  .= '账户管理数据-' . date( 'Y-m-d' );
        $file_name = iconv( "utf-8", "gbk", $file_name );
        $return    = $this->xlsExport( $file_name, $head_array, $export_data );
        ! empty( $return['error'] ) && $this->error( $return['error'] );

        $this->logWrite( "导出账户[ID:{$uid}]的明细数据" );
    }

    /**
     * 用户业绩
     */
    public function memberPerformanceInfo() {
        $uid = $this->get['uid'];

        //查询用户信息
        if ($uid > 0) {
            $member_info = M( 'Member' )->where( 'id=' . $uid )->field( 'nickname,loginname' )->find();
        } else {
            $member_info = [
                'nickname' => '系统',
                'loginname' => '平台'
            ];
        }
        $this->assign( "member_info", $member_info );

        //总业绩
        $performance_score = M('Performance')->where("user_id={$uid} and performance_tag=0")->getField('performance_amount');
        $this->assign('performance_score', $performance_score);

        //每月业绩列表
        $performanceList = M( 'Performance' )->where( "user_id={$uid}" )->order( 'performance_tag desc' )->select();

        foreach ( $performanceList as $key => $item ) {
            if ( $item['performance_tag'] < 201800 ) {
                $this->assign( 'performance_' . $item['performance_tag'], $item['performance_amount'] );
                unset( $performanceList[ $key ] );
            }
        }

        $this->assign( 'list', $performanceList );
        $AccountModel = new AccountModel();
        $LockModel = new LockModel();
        $user_id = $this->get['uid'];
        if (!validateExtend($user_id, 'NUMBER')) {
            $this->myApiPrint('参数格式有误', 300);
        }

        //总业绩
        $performance_info = M('Performance')->where("user_id={$user_id} and performance_tag=0")->field('performance_amount')->find();
        $data['performance_amount'] = !$performance_info ? 0 : $performance_info['performance_amount'];
        $data['performance_amount'] = sprintf('%.2f', $data['performance_amount']);

        //静态收益
        $consume_info = M('Consume')->where("user_id={$user_id}")->field('income_amount')->find();
        $data['income_amount_static'] = !$consume_info ? 0 : $consume_info['income_amount'];
        $data['income_amount_static'] = sprintf('%.2f', $data['income_amount_static']);

        //通证汇总
        /* $income_account = $AccountModel->getFieldsValues('account_goldcoin_income,account_bonus_income', "user_id={$user_id} and account_tag=0");
        $income_lock = $LockModel->getInfo('lock_amount', "user_id={$user_id}");
        $income_lock_amount = !$income_lock ? 0 : $income_lock['lock_amount'];
        $data['income_amount'] = $income_account['account_goldcoin_income'] + $income_account['account_bonus_income'] + $income_lock_amount;
        $data['income_amount'] = sprintf('%.2f', $data['income_amount']); */
        $data['income_amount'] = sprintf('%.2f', $this->getUserIncome($user_id));

        //总消费金额
        $map_order = array('uid' => array('eq', $user_id), 'order_status' => array('in', '1,3,4'));
        $order_amount = M('Orders')->where($map_order)->sum('amount');
        $data['consume_amount'] = sprintf('%.2f', $order_amount);
//1.加载配置
        $settings  = M( 'settings' )->where( 'settings_status=1' )->getField( 'settings_code, settings_value' );
        //总贡献业绩PV、收益份额、是否出局
        $consume_info = M('Consume')->where("user_id={$user_id}")->field('amount,amount_old,income_amount,is_out')->find();
        $consume_amount = !$consume_info ? 0 : $consume_info['amount'] - $consume_info['amount_old'];
        $consume_amount_old = !$consume_info ? 0 : $consume_info['amount_old'];
        $data['consume_pv'] = sprintf('%.2f', $consume_info['amount']);
        $data['income_portion'] = floor($consume_info['amount'] / $settings['performance_portion_base']);
        $data['portion'] = floor($consume_amount / $settings['performance_portion_base'] + ($consume_amount_old * $settings['mine_old_machine_bai'] / 100) / $settings['performance_portion_base']);
        $data['is_out'] = !$consume_info ? 0 : $consume_info['is_out'];

        $this->assign('personData',$data);
        $this->display();
    }

    /**
     * 用户消费
     */
    public function memberConsume() {
        $uid = $this->get['uid'];

        //查询用户信息
        if ($uid > 0) {
            $member_info = M( 'Member' )->where( 'id=' . $uid )->field( 'nickname,loginname' )->find();
        } else {
            $member_info = [
                'nickname' => '系统',
                'loginname' => '平台'
            ];
        }
        $this->assign( "member_info", $member_info );




        $AccountModel = new AccountModel();
        $LockModel = new LockModel();
        $user_id = $this->get['uid'];
        if (!validateExtend($user_id, 'NUMBER')) {
            $this->myApiPrint('参数格式有误', 300);
        }

        //消费列表
        $map_order = array('uid' => array('eq', $user_id), 'order_status' => array('in', '1,3,4'));
        $order_amount_list = M('Orders')->where($map_order)->select();
        foreach ($order_amount_list as $ord=>&$ordVal){
            $pv = M('OrderProduct')->where('order_id='.$ordVal['id'])->sum('price_cash * product_quantity * performance_bai_cash * 0.01');
            $pv = sprintf('%.2f', $pv);
            $ordVal['pv'] = $pv;
        }
        $this->assign( 'list', $order_amount_list );

        //总消费金额
        $map_order = array('uid' => array('eq', $user_id), 'order_status' => array('in', '1,3,4'));
        $order_amount = M('Orders')->where($map_order)->sum('amount');
        $data['consume_amount'] = sprintf('%.2f', $order_amount);
//1.加载配置
        $settings  = M( 'settings' )->where( 'settings_status=1' )->getField( 'settings_code, settings_value' );
        //总贡献业绩PV、收益份额、是否出局
        $consume_info = M('Consume')->where("user_id={$user_id}")->field('amount,amount_old,income_amount,is_out')->find();
        $consume_amount = !$consume_info ? 0 : $consume_info['amount'] - $consume_info['amount_old'];
        $consume_amount_old = !$consume_info ? 0 : $consume_info['amount_old'];
        $data['consume_pv'] = sprintf('%.2f', $consume_info['amount']);
        $data['income_portion'] = floor($consume_info['amount'] / $settings['performance_portion_base']);
        $data['portion'] = floor($consume_amount / $settings['performance_portion_base'] + ($consume_amount_old * $settings['mine_old_machine_bai'] / 100) / $settings['performance_portion_base']);
        $data['is_out'] = !$consume_info ? 0 : $consume_info['is_out'];

        $this->assign('personData',$data);
        $this->display();
    }

    /**
     * 业绩明细
     */
    public function memberPerformanceDetails() {
        $id = $this->get['id'];
        $page = empty($this->get['page']) ? 1 : $this->get['page'];

        if (!validateExtend($id, 'NUMBER')) {
            $this->error('参数格式有误');
        }

        $performance_info = M('Performance')->where("performance_id={$id}")->field('performance_tag,user_id')->find();
        if (!$performance_info) {
            $this->error('数据获取失败');
        }
        $this->assign('performance_tag', $performance_info['performance_tag']);

        $PerformanceModel = new PerformanceModel();

        $where['user_id'] = array('eq', $performance_info['user_id']);
        $data = $PerformanceModel->getList($performance_info['performance_tag'], '', $where, $page, 20);

        $list = $data['list'];
        $this->assign('list', $list);

        $this->Page($data['paginator']['totalRows'], $data['paginator']['everyPage'], $this->get);

        $this->display();
    }

}

?>