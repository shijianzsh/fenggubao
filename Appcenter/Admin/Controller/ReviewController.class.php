<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 审核管理
// +----------------------------------------------------------------------
namespace Admin\Controller;

use Common\Controller\AuthController;
use V4\Model\ProcedureModel;
use V4\Model\UserModel;
use V4\Model\Tag;
use V4\Model\Currency;
use V4\Model\CurrencyAction;
use V4\Model\AccountRecordModel;

class ReviewController extends AuthController
{

    public function __construct()
    {
        parent::__construct();

        C('TOKEN_ON', false);
    }

    /**
     * 区域合伙人审核列表基础方法
     * @param string $apply_level
     * @param bool $limit
     *
     * @return mixed
     */
    private function reviewBase($apply_level = '3', $limit = true)
    {
        $ViewApplyServiceCenter = M('ViewApplyServiceCenter');

        $where = array();
        $searchKey = array();

        if (!empty($this->get['time_min'])) {
            $searchKey['get_time'][] = array('egt', strtotime($this->get['time_min'] . ' 00:00:00'));
        }
        if (!empty($this->get['time_max'])) {
            $searchKey['get_time'][] = array('elt', strtotime($this->get['time_max'] . ' 23:59:59'));
        }
        if (count($searchKey['get_time']) == 1) {
            $searchKey['get_time'] = $searchKey['get_time'][0];
        }

        $searchKey['apply_level'] = array('eq', $apply_level);

        //审核状态筛选
        if ($this->get['type'] == '0') {
            $searchKey['status'] = array('eq', 0);

            $where1 = $searchKey;
        } else {
            if ($this->get['status'] == '1') {
                $searchKey['status'] = array('eq', 1);
            } elseif ($this->get['status'] == '2') {
                $searchKey['status'] = array('eq', 2);
            } else {
                $searchKey['status'] = array('neq', 0);
            }

            $where1['_complex'] = $searchKey;
            if ($this->get['status'] != '2') {
                $where1['_string'] = " apply_level={$apply_level} and id is null ";
                $where1['_logic'] = 'or';
            }
        }

        $where['_complex'] = $where1;

        if (!empty($this->get['userid'])) {
            $where['_string'] = " loginname='{$this->get[userid]}' or nickname='{$this->get[userid]}' ";
        }

        //判断当前管理员是否具有小管理员权限
        $is_small_super = $this->isSmallSuperManager();
        if (!$is_small_super && session("admin_id") != 1) {
//             $filter_member = $this->filterMember(session('admin_mid'), false, '', array('repath' => "repath like '%," . session('admin_mid') . ",%'"), "string");
//             $where['_string'] = " ({$where['_string']}) and uid in (select id from zc_member where {$filter_member['repath']} and {$filter_member['id']}) ";
        }

        if ($limit) {
            $count = $ViewApplyServiceCenter->where($where)->count();
            $limit = $this->Page($count, 10, $this->get);
        } else {
            $limit = '';
        }

        $info = $ViewApplyServiceCenter->where($where)->order('get_time desc,uid asc')->limit($limit)->select();
        foreach ($info as $k => $v) {
            $info[$k]['img4'] = explode(',', $v['img4']);

            //当为区域合伙人身份时，判断是否存在自动结算回本记录或者获取自动结算回本信息
            if ($apply_level == '4') {
                $service_clearing_info = M('ServiceClearing')->where('user_id=' . $v['uid'])->field('clearing_status')->find();
                $info[$k]['service_clearing_status'] = $service_clearing_info ? $service_clearing_info['clearing_status'] : null;
            }

            //获取当前用户的身份等级 和 省市区
            $member_info = M('Member')->where('id=' . $v['uid'])->field('level,role,province,city,country')->find();
            if ($member_info) {
                $info[$k]['level'] = $member_info['level'];
                $info[$k]['role'] = $member_info['role'];
                $info[$k]['province'] = $member_info['province'];
                $info[$k]['city'] = $member_info['city'];
                $info[$k]['country'] = $member_info['country'];
            } else {
                $info[$k]['level'] = '未知';
                $info[$k]['role'] = '未知';
                $info[$k]['province'] = '未知';
                $info[$k]['city'] = '未知';
                $info[$k]['country'] = '未知';
            }
        }

        return $info;
    }

    /**
     * 区域合伙人审核列表
     */
    public function serviceReview()
    {
        $info = $this->reviewBase('3');
        $this->assign('info', $info);

        $this->display();
    }

    /**
     * 省级合伙人审核列表
     */
    public function agentReview()
    {
        $info = $this->reviewBase('4');
        $this->assign('info', $info);
        $this->display();
    }

    /**
     * 合伙人审核列表
     */
    public function partnerReview()
    {
        $info = $this->reviewBase('5');

        $this->assign('info', $info);
        $this->display();
    }

    /**
     * 区域合伙人审核操作
     */
    public function serviceReviewCenter()
    {
        $member = M('member');

        $status = I('get.status');
        $id = I('get.id');

        M()->startTrans();

        if ($id == "") {
            $this->error('数据错误！');
        }

        if ($status == 1) { //审核通过

            $wherekey['id'] = $id;
            $where['id'] = M('apply_service_center', 'zc_')->where($wherekey)->getField('uid');
            $data['role'] = 3;
            $result = M('member', 'zc_')->where($where)->save($data);

            $data1['status'] = 1;
            $data1['post_time'] = time();
            $result1 = M('apply_service_center', 'zc_')->where($wherekey)->save($data1);

            //操作记录
            $where_log['id'] = array('eq', $where['id']);
            $log_data = $member->field('loginname,nickname')->where($where_log)->find();

            //同步添加对应管理员用户
            $manager_data = array(
                'uid' => $where['id'],
                'group_id' => array(C('ROLE_MUST_LIST.service')),
                'type' => 'service',
            );
            $SystemManager = new \Admin\Controller\AjaxController();
            $result2 = $SystemManager->memberAddAsyn($manager_data);

//			$procedureModel = new ProcedureModel();
//			$result3        = $procedureModel->execute( 'Service_star', $id, '@error' );
//
//			$result4 = $procedureModel->execute( 'Service_parentStar', $id, '@error' );

            if ($result === false || $result1 === false || $result2 === false) {
                M()->rollback();
                $this->error('操作失败,请稍后重试');
            }

            M()->commit();
            $this->success('审核通过！', '', false, '审核通过审核管理中的' . $log_data['loginname'] . '[' . $log_data['nickname'] . ']为区域合伙人');
            exit;

        } elseif ($status == 2) { //驳回

            $wherekey['id'] = $id;
            $data2['status'] = 2;
            $data2['post_time'] = time();
            $data2['reason'] = urldecode($this->get['reason']);
            $result1 = M('apply_service_center', 'zc_')->where($wherekey)->save($data2);
            if ($result1 === false) {
            	M()->rollback();
                $this->error('操作失败,请稍后重试');
            }

            $info = M('ApplyServiceCenter')
                ->alias('aps')
                ->join('JOIN __MEMBER__ mem ON mem.id=aps.uid')
                ->where("aps.id=" . $id)
                ->field('mem.id,mem.nickname,mem.loginname')
                ->find();
            
            //清除member表省市区信息
            $data_member = [
            	'province' => '',
            	'city' => '',
            	'country' => '',
            	'role' => 0
            ];
            $result2 = M('Member')->where('id='.$info['id'])->save($data_member);
            if ($result2 === false) {
            	M()->rollback();
            	$this->error('操作失败');
            }

            M()->commit();
            $this->success("驳回成功", U('Admin/Review/serviceReview/type/1'), false, "驳回:{$info['nickname']}[{$info['loginname']}]的区域合伙人申请");

        } elseif ($status == 3) { //删除

            $wherekey['id'] = $id;
            $whereSql['id'] = M('apply_service_center', 'zc_')->where($wherekey)->getField('uid');
            M('member')->where($whereSql)->setField('role', '');
            $result1 = M('apply_service_center', 'zc_')->where($wherekey)->delete();

            //操作记录
            $where_log['id'] = array('eq', $whereSql['id']);
            $log_data = $member->field('loginname,nickname')->where($where_log)->find();

            //同步删除对应管理员用户
            $SystemManager = new \Admin\Controller\AjaxController();
            $result2 = $SystemManager->memberDeleteAsyn($log_data['loginname'], 'service');

            if ($result1 === false || $result2 === false) {
                $this->error('操作失败,请稍后重试');
            }

            M()->commit();
            $this->success('删除区域合伙人成功！', U('Admin/Review/serviceReview/type/1'), false, '成功删除审核管理中的' . $log_data['loginname'] . '[' . $log_data['nickname'] . ']区域合伙人申请资料');
            exit;
        }
    }

    /**
     * 省级合伙人审核操作
     */
    public function agentReviewCenter()
    {
        $member = M('member');
        $Paramater = M('Parameter', 'g_');
        $ServiceClearing = M('ServiceClearing');

        $status = I('get.status');
        $id = I('get.id');
        $apply_level = I('get.apply_level');

        M()->startTrans();

        if ($id == "") {
            $this->error('数据错误！');
        }

        //获取用户ID
        $uid = M('apply_service_center', 'zc_')->where('id=' . $id)->getField('uid');

        if ($status == 1) { //审核通过

            $wherekey['id'] = $id;
            $where['id'] = $uid;
            $data['role'] = 4;
            $result = M('member', 'zc_')->where($where)->save($data);

            $data1['status'] = 1;
            $data1['post_time'] = time();
            $result1 = M('apply_service_center', 'zc_')->where($wherekey)->save($data1);

            //同时删除区域合伙人
            $wherekey_s['uid'] = array('eq', $uid);
            $wherekey_s['apply_level'] = array('eq', 3);
            $wherekey_s['status'] = array('eq', 1);
            $wherekey_s['id'] = array('neq', $id);
            $count = M('apply_service_center')->where($wherekey_s)->count();
            $result2 = true;
            if ($count >= 1) {
                $result2 = M('apply_service_center')->where($wherekey_s)->delete();
            }

            //操作记录
            $where_log['id'] = array('eq', $uid);
            $log_data = $member->field('loginname,nickname')->where($where_log)->find();

            //同步删除对应区域合伙人管理员用户
            $SystemManager = new \Admin\Controller\AjaxController();
            $result3 = $SystemManager->memberDeleteAsyn($log_data['loginname'], 'service');
            //同步添加对应省级合伙人管理员用户
            $manager_data = array(
                'uid' => $uid,
                'group_id' => array(C('ROLE_MUST_LIST.agent')),
                'type' => 'agent',
            );
            $result4 = $SystemManager->memberAddAsyn($manager_data);
            if ($result === false || $result1 === false || $result2 === false || $result3 === false || $result4 === false) {
                $this->error('操作失败，请稍后重试');
            }

            M()->commit();
            $this->success('审核通过！', '', false, '审核通过审核管理中的' . $log_data['loginname'] . '[' . $log_data['nickname'] . ']为省级合伙人');
            exit;

        } elseif ($status == 2) { //驳回

            $wherekey['id'] = $id;
            $data2['status'] = 2;
            $data2['post_time'] = time();
            $data2['reason'] = urldecode($this->get['reason']);
            $result1 = M('apply_service_center', 'zc_')->where($wherekey)->save($data2);
            if ($result1 === false) {
            	M()->rollback();
                $this->erro('操作失败，请稍后重试');
            }

            $info = M('ApplyServiceCenter')
                ->alias('aps')
                ->join('JOIN __MEMBER__ mem ON mem.id=aps.uid')
                ->where("aps.id=" . $id)
                ->field('mem.id,mem.nickname,mem.loginname')
                ->find();
            
            //清除member表省市区信息
            $data_member = [
	            'province' => '',
	            'city' => '',
	            'country' => '',
	            'role' => 0
            ];
            $result2 = M('Member')->where('id='.$info['id'])->save($data_member);
            if ($result2 === false) {
            	M()->rollback();
            	$this->error('操作失败');
            }

            M()->commit();
            $this->success("驳回成功", U('Admin/Review/agentReview/type/1'), false, "驳回:{$info['nickname']}[{$info['loginname']}]的省级合伙人申请");

        } elseif ($status == 3) { //删除
            $this->error('删除功能已禁用');

            $wherekey['id'] = $id;
            $whereSql['id'] = $uid;
            M('member')->where($whereSql)->setField('role', '');
            $result1 = M('apply_service_center', 'zc_')->where($wherekey)->delete();

            //操作记录
            $where_log['id'] = array('eq', $whereSql['id']);
            $log_data = $member->field('loginname,nickname')->where($where_log)->find();

            //同步删除对应管理员用户
            $SystemManager = new \Admin\Controller\AjaxController();
            $result2 = $SystemManager->memberDeleteAsyn($log_data['loginname'], 'agent');

            if ($result1 === false || $result2 === false) {
                $this->error('操作失败，请稍后重试');
            }

            $this->success('删除省级合伙人成功！', U('Admin/Review/agentReview/type/1'), false, '成功删除审核管理中' . $log_data['loginname'] . '[' . $log_data['nickname'] . ']省级合伙人申请资料');
            exit;
        }
    }

    /**
     * 合伙人审核操作
     */
    public function partnerReviewCenter()
    {
        $member = M('member');
        $Paramater = M('Parameter', 'g_');
        $ServiceClearing = M('ServiceClearing');

        $status = I('get.status');
        $id = I('get.id');
        $apply_level = I('get.apply_level');

        M()->startTrans();

        if ($id == "") {
            $this->error('数据错误！');
        }

        //获取用户ID
        $uid = M('apply_service_center', 'zc_')->where('id=' . $id)->getField('uid');

        if ($status == 1) { //审核通过

            $wherekey['id'] = $id;
            $where['id'] = $uid;
            $data['is_partner'] = 1;
            $result = M('member', 'zc_')->where($where)->save($data);

            $data1['status'] = 1;
            $data1['post_time'] = time();
            $result1 = M('apply_service_center', 'zc_')->where($wherekey)->save($data1);

            //操作记录
            $where_log['id'] = array('eq', $uid);
            $log_data = $member->field('loginname,nickname')->where($where_log)->find();

            //同步删除对应服务中心管理员用户
            $SystemManager = new \Admin\Controller\AjaxController();
            $result3 = $SystemManager->memberDeleteAsyn($log_data['loginname'], 'service');
            //同步添加对应区域合伙人管理员用户
            $manager_data = array(
                'uid' => $uid,
                'group_id' => array(C('ROLE_MUST_LIST.agent')),
                'type' => 'agent',
            );
            $result4 = $SystemManager->memberAddAsyn($manager_data);

            if ($result === false || $result1 === false || $result3 === false || $result4 === false) {
                $this->error('操作失败，请稍后重试');
            }

            M()->commit();
            $this->success('审核通过！', '', false, '审核通过审核管理中的' . $log_data['loginname'] . '[' . $log_data['nickname'] . ']为合伙人');
            exit;

        } elseif ($status == 2) { //驳回

            $wherekey['id'] = $id;
            $data2['status'] = 2;
            $data2['post_time'] = time();
            $data2['reason'] = urldecode($this->get['reason']);
            $result1 = M('apply_service_center', 'zc_')->where($wherekey)->save($data2);

            if ($result1 === false) {
                $this->erro('操作失败，请稍后重试');
            }

            $info = M('ApplyServiceCenter')
                ->alias('aps')
                ->join('JOIN __MEMBER__ mem ON mem.id=aps.uid')
                ->where("aps.id=" . $id)
                ->field('mem.nickname,mem.loginname')
                ->find();

            M()->commit();
            $this->success("驳回成功", U('Admin/Review/agentReview/type/1'), false, "驳回:{$info['nickname']}[{$info['loginname']}]的合伙人申请");

        } elseif ($status == 3) { //删除
            $this->error('删除功能已禁用');

            $wherekey['id'] = $id;
            $whereSql['id'] = $uid;
            M('member')->where($whereSql)->setField('role', '');
            $result1 = M('apply_service_center', 'zc_')->where($wherekey)->delete();

            //操作记录
            $where_log['id'] = array('eq', $whereSql['id']);
            $log_data = $member->field('loginname,nickname')->where($where_log)->find();

            //同步删除对应管理员用户
            $SystemManager = new \Admin\Controller\AjaxController();
            $result2 = $SystemManager->memberDeleteAsyn($log_data['loginname'], 'agent');

            if ($result1 === false || $result2 === false) {
                $this->error('操作失败，请稍后重试');
            }

            $this->success('删除合伙人成功！', U('Admin/Review/agentReview/type/1'), false, '成功删除审核管理中' . $log_data['loginname'] . '[' . $log_data['nickname'] . ']合伙人申请资料');
            exit;
        }
    }

    /**
     * 服务中心列表导出
     */
    public function serviceReviewExportAction()
    {
        $title_cn = '';
        //审核状态筛选
        switch ($this->get['type']) {
            case '0':
                $title_cn = '未审';
                break;
            case '1':
                $title_cn = '已审';
                break;
            default:
                $title_cn = '全部';
        }

        $limit = false;

        $data = $this->reviewBase('3', $limit);

        $export_data = array();
        foreach ($data as $k => $v) {
            $level = '体验用户';
            switch ($v['level']) {
                case '2':
                    $level = '创客用户';
                    break;
                case '5':
                    $level = '银卡代理';
                    break;
                case '6':
                    $level = '金卡代理';
                    break;
                case '7':
                    $level = '钻卡代理';
                    break;
            }
            switch ($v['role']) {
                case '3':
                    $level .= ' | 服务中心';
                    break;
                case '4':
                    $level .= ' | 区域合伙人';
                    break;
            }
            $vo = array(
                $v[loginname] . '[' . $v[nickname] . ']',
                format_date($v[get_time]),
                $v[status] == '1' ? '审核通过' : ($v[status] == '2' ? '驳回:' . $v[reason] : '后台手动直接升级'),
                format_date($v[post_time]),
                $level,
            );
            $export_data[] = $vo;
        }

        $head_array = array('用户账号', '申请时间', '审核结果', '审核时间', '当前身份');
        $file_name = "{$title_cn}服务中心列表-" . date('Y-m-d') . "[全部]";
        $file_name = iconv("utf-8", "gbk", $file_name);
        $return = $this->xlsExport($file_name, $head_array, $export_data);
        !empty($return['error']) && $this->error($return['error']);

        $this->logWrite("导出{$title_cn}服务中心列表-" . date('Y-m-d') . "[全部]");
    }

    /**
     * 区域合伙人列表导出
     */
    public function agentReviewExportAction()
    {
        $title_cn = '';
        //审核状态筛选
        switch ($this->get['type']) {
            case '0':
                $title_cn = '未审';
                break;
            case '1':
                $title_cn = '已审';
                break;
            default:
                $title_cn = '全部';
        }

        $limit = false;

        $data = $this->reviewBase('4', $limit);

        $export_data = array();
        foreach ($data as $k => $v) {
            $level = '体验用户';
            switch ($v['level']) {
                case '2':
                    $level = '创客用户';
                    break;
                case '5':
                    $level = '银卡代理';
                    break;
                case '6':
                    $level = '金卡代理';
                    break;
                case '7':
                    $level = '钻卡代理';
                    break;
            }
            switch ($v['role']) {
                case '3':
                    $level .= ' | 服务中心';
                    break;
                case '4':
                    $level .= ' | 区域合伙人';
                    break;
            }

            $vo = array(
                $v[loginname] . '[' . $v[nickname] . ']',
                format_date($v[get_time]),
                $v[status] == '1' ? '审核通过' : ($v[status] == '2' ? '驳回:' . $v[reason] : '后台手动直接升级'),
                format_date($v[post_time]),
                $level,
            );
            $export_data[] = $vo;
        }

        $head_array = array('用户账号', '申请时间', '审核结果', '审核时间', '当前身份');
        $file_name .= "{$title_cn}区域合伙人列表-" . date('Y-m-d') . "[全部]";
        $file_name = iconv("utf-8", "gbk", $file_name);
        $return = $this->xlsExport($file_name, $head_array, $export_data);
        !empty($return['error']) && $this->error($return['error']);

        $this->logWrite("导出{$title_cn}区域合伙人列表-" . date('Y-m-d') . "[全部]");
    }

    /**
     * 区域合伙人自动结算记录
     *
     * @param int $id 区域合伙人申请ID
     */
    public function serviceClearingLog()
    {
        $UserModel = new UserModel();

        $where = ' 1 ';

        $uid = $this->get['uid'];
        $time_min = $this->get['time_min'];
        $time_max = $this->get['time_max'];
        $page = $this->get['p'] > 1 ? $this->get['p'] : 1;

        if (!validateExtend($uid, 'NUMBER')) {
            $this->error('参数格式有误');
        } else {
            $where .= " and user_id=" . $uid;
        }

        if (!empty($time_min)) {
            $where .= " and log_addtime>=" . strtotime($time_min);
        }
        if (!empty($time_max)) {
            $where .= " and log_addtime<=" . strtotime($time_max . ' 23:59:59');
        }

        $data = $UserModel->getServiceClearingLogList('*', $page, 20, $where);

        $list = $data['list'];
        $this->assign('list', $list);

        $this->Page($data['pagintator']['totalPage'] * $data['paginator']['everyPage'], $data['paginator']['everyPage'], $this->get);

        $this->display();
    }

    /**
     * 开启/关闭区域合伙人自动回本结算状态
     */
    public function ServiceClearingStatus()
    {
        $ServiceClearing = M('ServiceClearing');

        $uid = $this->get['uid'];

        if (!validateExtend($uid, 'NUMBER')) {
            $this->error('参数有误');
        }

        $where = "user_id=" . $uid;
        $data = [];
        $action = '开启';

        $service_clearing = $ServiceClearing->where($where)->field('clearing_status')->find();
        if ($service_clearing) {
            if ($service_clearing['clearing_status'] == '0') {
                $data['clearing_status'] = 1;
            } elseif ($service_clearing['clearing_status'] == '1') {
                $data['clearing_status'] = 0;
                $action = '关闭';
            }

            if ($ServiceClearing->where($where)->save($data) === false) {
                $this->error('操作失败');
            }
        } else {
            $parameter_info = M('Parameter', 'g_')->where('id=1')->field('company_return_amount,company_return_month')->find();
            $data = [
                'user_id' => $uid,
                'clearing_times' => $parameter_info['company_return_month'],
                'clearing_amount' => $parameter_info['company_return_amount'],
                'clearing_status' => 1,
                'clearing_addtime' => time()
            ];

            if (!$ServiceClearing->create($data)) {
                $this->error($ServiceClearing->getError());
            } else {
                $ServiceClearing->add();
            }
        }

        $log_info = M('Member')->where('id=' . $uid)->field('loginname,nickname')->find();

        $this->success('成功' . $action . '区域合伙人自动回本', '', false, "[{$action}]{$log_info[nickname]}[{$log_info[loginname]}]区域合伙人自动回本");
    }

    /**
     * VIP审核列表
     */
    public function vipReview()
    {
        $info = $this->vipReviewBase();
        $this->assign('info', $info);

        $this->display();
    }

    /**
     * VIP审核列表基础数据
     *
     * @param mixed $limit 用于导出功能时使用
     */
    private function vipReviewBase($limit = true)
    {
        $VipApplyModel = M('VipApply');
        $MemberModel = M('Member');
        $AccountRecordModel = new AccountRecordModel();

        $where_via = ' 1 ';
        $where_mem = ' 1 ';

        if (!empty($this->get['time_min'])) {
            $where_via .= " and via.apply_uptime>=" . strtotime($this->get['time_min'] . ' 00:00:00');
            $where_mem .= " and mem.open_time>=" . strtotime($this->get['time_min'] . ' 00:00:00');
        }
        if (!empty($this->get['time_max'])) {
            $where_via .= " and via.apply_uptime<=" . strtotime($this->get['time_max'] . ' 23:59:59');
            $where_mem .= " and mem.open_time<=" . strtotime($this->get['time_max'] . ' 23:59:59');
        }

        //审核状态筛选
        if ($this->get['type'] == '0') {
            $where_via .= " and via.apply_status=1 ";
            $where_mem = '';
        } elseif ($this->get['type'] == '1') {
            if (!empty($this->get['status'])) {
                $where_via .= " and via.apply_status=" . $this->get['status'];
            } else {
                $where_via .= " and via.apply_status in(2,3)";
            }

            //兼容显示未启用审核管理之前的已开通金卡代理的用户
            if (empty($this->get['status']) || $this->get['status'] == '3') {
                $where_mem .= " and mem.level=6";
            } else {
                $where_mem = '';
            }
        } else {
            $this->error('未知审核状态');
        }

        if (!empty($this->get['userid'])) {
            $where_via .= " and mem.loginname='{$this->get[userid]}' or mem.nickname='{$this->get[userid]}' ";
        }

        //判断当前管理员是否具有小管理员权限
        $is_small_super = $this->isSmallSuperManager();
        if (!$is_small_super && session("admin_id") != 1) {
//             $filter_member = $this->filterMember(session('admin_mid'), false, '', array('repath' => "repath like '%," . session('admin_mid') . ",%'"), "string");
//             $where_via .= " and mem.id in (select id from zc_member where {$filter_member['repath']} and {$filter_member['id']}) ";
        }

        //组合查询条件
        if (!empty($where_mem)) {
            $where = " ({$where_via}) or ({$where_mem}) ";
        } else {
            $where = $where_via;
        }

        //所属计划筛选(针对全局筛选,故放至最后)
        if ($this->get['plan_type'] == '0') {
            $where = " ({$where}) and (usp.plan_type={$this->get['plan_type']} or usp.plan_type is null) ";
        } elseif ($this->get['plan_type'] == '1') {
            $where = " ({$where}) and usp.plan_type={$this->get['plan_type']} ";
        }

        if ($limit) {
            $count = $MemberModel
                ->alias('mem')
                ->join('left join __VIP_APPLY__ via ON mem.id=via.user_id')
                ->join('left join __USER_PLAN__ usp ON usp.user_id=mem.id')
                ->where($where)
                ->count();
            $limit = $this->Page($count, 20, $this->get);
        } else {
            $limit = '';
        }

        $info = $MemberModel
            ->alias('mem')
            ->join('left join __VIP_APPLY__ via ON mem.id=via.user_id')
            ->join('left join __USER_PLAN__ usp ON usp.user_id=mem.id')
            ->where($where)
            ->field('via.*,mem.id mid,mem.loginname,mem.nickname,mem.truename,mem.level,mem.role,mem.open_time,usp.plan_type')
            ->order('via.apply_addtime desc,mem.open_time desc,via.user_id asc,mem.id asc')
            ->limit($limit)
            ->select();
        foreach ($info as $k => $v) {
            //获取自动结算回本信息
            $vip_clearing_info = M('VipClearing')->where('user_id=' . $v['mid'])->field('clearing_status')->find();
            $info[$k]['vip_clearing_status'] = $vip_clearing_info ? $vip_clearing_info['clearing_status'] : null;

            //获取申请时支付金额
            $apply_amount = [];
            $map_apply = [
                'user_id' => array('eq', $v['user_id']),
                'record_action' => array('eq', CurrencyAction::CashApplyVIP),
            ];
            $apply_amount['cash'] = $AccountRecordModel->getFieldsValues(Currency::Cash, Tag::getMonth($v['apply_uptime']), 'record_amount', $map_apply);

            $map_apply['record_action'] = array('eq', CurrencyAction::WechatApplyVIP);
            $apply_amount['wechat'] = $AccountRecordModel->getFieldsValues(Currency::Cash, Tag::getMonth($v['apply_uptime']), 'record_amount', $map_apply);

            $map_apply['record_action'] = array('eq', CurrencyAction::ENrollApplyVIP);
            $apply_amount['enroll'] = $AccountRecordModel->getFieldsValues(Currency::Enroll, Tag::getMonth($v['apply_uptime']), 'record_amount', $map_apply);

            $info[$k]['apply_amount'] = $apply_amount;
        }

        return $info;
    }

    /**
     * 金卡代理审核操作
     */
    public function vipReviewCenter()
    {
        $member = M('member');
        $Paramater = M('Parameter', 'g_');
        $VipClearing = M('VipClearing');
        $VipApplyModel = M('VipApply');

        $status = I('get.status');
        $id = I('get.id');

        if (!validateExtend($id, 'NUMBER')) {
            $this->error('ID参数有误');
        }
        if (!validateExtend($status, 'NUMBER')) {
            $this->error('ID参数有误');
        }

        M()->startTrans();

        //获取用户ID
        $uid = $VipApplyModel->where('apply_id=' . $id)->getField('user_id');

        //供操作记录使用
        $where_log['id'] = array('eq', $uid);
        $log_data = $member->field('loginname,nickname,roleid')->where($where_log)->find();

        if ($status == 3) { //审核通过
            //更改用户级别
            $where['id'] = $uid;
            $data['level'] = 6;
            $result = M('member', 'zc_')->where($where)->save($data);

            //更改VIP申请表状态
            $wherekey['apply_id'] = $id;
            $data1['apply_status'] = 3;
            $data1['apply_uptime'] = time();
            $result1 = $VipApplyModel->where($wherekey)->save($data1);

            //插入或更新定时结算数据(开启)
            $vip_clearing_info = $VipClearing->where('user_id=' . $uid)->find();
            if ($vip_clearing_info) {
                $data_vip_celaring = [
                    'clearing_status' => 1,
                    'clearing_uptime' => time()
                ];
                $result5 = $VipClearing->where('user_id=' . $uid)->save($data_vip_celaring);
            } else {
                //获取定时结算配置参数
                $Parameter = M('Parameter', 'g_');
                $parameter_info = $Paramater->where('id=1')->field('vip_apply_return_amount,vip_apply_return_month')->find();
                if (!$parameter_info) {
                    $this->error('定时结算配置参数获取失败');
                }

                $data_service_celaring = [
                    'user_id' => $uid,
                    'clearing_times' => $parameter_info['vip_apply_return_month'],
                    'clearing_amount' => $parameter_info['vip_apply_return_amount'],
                    'clearing_status' => 1,
                    'clearing_addtime' => time()
                ];
                if (!$VipClearing->create($data_service_celaring)) {
                    $this->error($VipClearing->getError());
                } else {
                    $result5 = $VipClearing->add();
                }
            }

            //推广奖+机构补贴(奖项移至接口执行)
            /*
	        M()->execute(C('ALIYUN_TDDL_MASTER') . 'call Subsidy_market(' . $uid . ',@msg)');
	        M()->execute(C('ALIYUN_TDDL_MASTER') . 'call Subsidy_company(' . $uid . ',@msg)');
	        */

            if ($result === false || $result1 === false || $result5 === false) {
                M()->rollback();
                $this->error('操作失败，请稍后重试');
            }

            M()->commit();
            $this->success('审核通过！', '', false, '审核通过审核管理中的' . $log_data['loginname'] . '[' . $log_data['nickname'] . ']为金卡代理');
        } elseif ($status == 2) { //驳回
            //更改用户级别
            $where['id'] = $uid;
            $data['level'] = $log_data['roleid'] > 0 ? $log_data['roleid'] : 2; //兼容此功能更新前个别已申请VIP的用户若被驳回后如果roleid为0,则默认直接恢复为创客级别
            $result = M('member', 'zc_')->where($where)->save($data);

            //更改VIP申请表状态
            $wherekey['apply_id'] = $id;
            $data2['apply_status'] = 2;
            $data2['apply_uptime'] = time();
            $data2['apply_remark'] = urldecode($this->get['reason']);
            $result1 = $VipApplyModel->where($wherekey)->save($data2);

            //更新定时结算数据(关闭)
            $vip_clearing_info = $VipClearing->where('user_id=' . $uid)->find();
            if ($vip_clearing_info) {
                $data_vip_celaring = [
                    'clearing_status' => 0,
                    'clearing_uptime' => time()
                ];
                $result5 = $VipClearing->where('user_id=' . $uid)->save($data_vip_celaring);
            }

            if ($result === false || $result1 === false || $result5 === false) {
                M()->rollback();
                $this->erro('操作失败，请稍后重试');
            }

            M()->commit();
            $this->success("驳回成功", U('Admin/Review/vipReview/type/1'), false, "驳回:{$log_data['nickname']}[{$log_data['loginname']}]的金卡代理申请");
        } else {
            $this->error('未知操作类型');
        }
    }

    /**
     * 金卡代理自动回本记录
     */
    public function vipClearingLog()
    {
        $UserModel = new UserModel();

        $where = ' 1 ';

        $uid = $this->get['uid'];
        $time_min = $this->get['time_min'];
        $time_max = $this->get['time_max'];
        $page = $this->get['p'] > 1 ? $this->get['p'] : 1;

        if (!validateExtend($uid, 'NUMBER')) {
            $this->error('参数格式有误');
        } else {
            $where .= " and user_id=" . $uid;
        }

        if (!empty($time_min)) {
            $where .= " and log_addtime>=" . strtotime($time_min);
        }
        if (!empty($time_max)) {
            $where .= " and log_addtime<=" . strtotime($time_max . ' 23:59:59');
        }

        $data = $UserModel->getVipClearingLogList('*', $page, 20, $where);

        $list = $data['list'];
        $this->assign('list', $list);

        $this->Page($data['paginator']['totalPage'] * $data['paginator']['everyPage'], $data['paginator']['everyPage'], $this->get);

        //获取自动回本信息状态
        $info = M('VipClearing')->where('user_id=' . $uid)->field('clearing_status')->find();
        $this->assign('info', $info);

        $this->display();
    }

    /**
     * 金卡代理自动回本状态处理
     */
    public function vipClearingStatus()
    {
        $VipClearing = M('VipClearing');

        $uid = $this->get['uid'];

        if (!validateExtend($uid, 'NUMBER')) {
            $this->error('参数有误');
        }

        $where = "user_id=" . $uid;
        $data = [];
        $action = '开启';

        $vip_clearing = $VipClearing->where($where)->field('clearing_status')->find();
        if (!$vip_clearing) {
            $this->error('当前用户自动回本信息不存在');
        }

        if ($vip_clearing['clearing_status'] == '0') {
            $data['clearing_status'] = 1;
        } elseif ($vip_clearing['clearing_status'] == '1') {
            $data['clearing_status'] = 0;
            $action = '关闭';
        }

        if ($VipClearing->where($where)->save($data) === false) {
            $this->error('操作失败');
        }

        $log_info = M('Member')->where('id=' . $uid)->field('loginname,nickname')->find();

        $this->success('成功' . $action . '金卡代理自动回本', '', false, "[{$action}]{$log_info[nickname]}[{$log_info[loginname]}]金卡代理自动回本");
    }

    /**
     * 金卡代理列表导出
     */
    public function vipReviewExportAction()
    {
        $title_cn = '';
        //审核状态筛选
        switch ($this->get['type']) {
            case '0':
                $title_cn = '未审';
                break;
            case '1':
                $title_cn = '已审';
                break;
            default:
                $title_cn = '全部';
        }

        $data = $this->vipReviewBase(false);

        $export_data = array();
        foreach ($data as $k => $v) {
            $level = '体验用户';
            switch ($v['level']) {
                case '2':
                    $level = '创客用户';
                    break;
                case '5':
                    $level = '银卡代理';
                    break;
                case '6':
                    $level = '金卡代理';
                    break;
                case '7':
                    $level = '钻卡代理';
                    break;
            }
            switch ($v['role']) {
                case '3':
                    $level .= ' | 服务中心';
                    break;
                case '4':
                    $level .= ' | 区域合伙人';
                    break;
            }

            $vo = array(
                $v[loginname] . '[' . $v[nickname] . ']',
                format_date($v[apply_addtime]),
                $v[status] == '2' ? '审核通过' : ($v[status] == '1' ? '驳回:' . $v[apply_remark] : '后台手动直接升级'),
                format_date($v[apply_uptime]),
                $level,
                $v[plan_type] == 1 ? 'B计划' : 'A计划',
            );
            $export_data[] = $vo;
        }

        $head_array = array('用户账号', '申请时间', '审核结果', '审核时间', '当前身份', '所属计划');
        $file_name .= "{$title_cn}金卡代理列表-" . date('Y-m-d') . "[全部]";
        $file_name = iconv("utf-8", "gbk", $file_name);
        $return = $this->xlsExport($file_name, $head_array, $export_data);
        !empty($return['error']) && $this->error($return['error']);

        $this->logWrite("导出{$title_cn}金卡代理列表-" . date('Y-m-d') . "[全部]");
    }

    /**
     * 钻卡审核列表
     */
    public function honourVipReview()
    {
        $info = $this->honourVipReviewBase();
        $this->assign('info', $info);

        $this->display();
    }

    /**
     * 钻卡审核列表基础数据
     *
     * @param mixed $limit 用于导出功能时使用
     */
    private function honourVipReviewBase($limit = true)
    {
        $HonourVipApplyModel = M('HonourVipApply');
        $MemberModel = M('Member');
        $AccountRecordModel = new AccountRecordModel();

        $where_via = ' 1 ';
        $where_mem = ' 1 ';

        if (!empty($this->get['time_min'])) {
            $where_via .= " and via.apply_uptime>=" . strtotime($this->get['time_min'] . ' 00:00:00');
            $where_mem .= " and mem.open_time>=" . strtotime($this->get['time_min'] . ' 00:00:00');
        }
        if (!empty($this->get['time_max'])) {
            $where_via .= " and via.apply_uptime<=" . strtotime($this->get['time_max'] . ' 23:59:59');
            $where_mem .= " and mem.open_time<=" . strtotime($this->get['time_max'] . ' 23:59:59');
        }

        //审核状态筛选
        if ($this->get['type'] == '0') {
            $where_via .= " and via.apply_status=1 ";
            $where_mem = '';
        } elseif ($this->get['type'] == '1') {
            if (!empty($this->get['status'])) {
                $where_via .= " and via.apply_status=" . $this->get['status'];
            } else {
                $where_via .= " and via.apply_status in(2,3)";
            }

            //兼容显示未启用审核管理之前的已开通金卡代理的用户
            if (empty($this->get['status']) || $this->get['status'] == '3') {
                $where_mem .= " and mem.level=7";
            } else {
                $where_mem = '';
            }
        } else {
            $this->error('未知审核状态');
        }

        if (!empty($this->get['userid'])) {
            $where_via .= " and mem.loginname='{$this->get[userid]}' or mem.nickname='{$this->get[userid]}' ";
        }

        //判断当前管理员是否具有小管理员权限
        $is_small_super = $this->isSmallSuperManager();
        if (!$is_small_super && session("admin_id") != 1) {
//             $filter_member = $this->filterMember(session('admin_mid'), false, '', array('repath' => "repath like '%," . session('admin_mid') . ",%'"), "string");
//             $where_via .= " and mem.id in (select id from zc_member where {$filter_member['repath']} and {$filter_member['id']}) ";
        }

        //组合查询条件
        if (!empty($where_mem)) {
            $where = " ({$where_via}) or ({$where_mem}) ";
        } else {
            $where = $where_via;
        }

        //所属计划筛选(针对全局筛选,故放至最后)
        if ($this->get['plan_type'] == '0') {
            $where = " ({$where}) and (usp.plan_type={$this->get['plan_type']} or usp.plan_type is null) ";
        } elseif ($this->get['plan_type'] == '1') {
            $where = " ({$where}) and usp.plan_type={$this->get['plan_type']} ";
        }

        if ($limit) {
            $count = $MemberModel
                ->alias('mem')
                ->join('left join __HONOUR_VIP_APPLY__ via ON mem.id=via.user_id')
                ->join('left join __USER_PLAN__ usp ON usp.user_id=mem.id')
                ->where($where)
                ->count();
            $limit = $this->Page($count, 20, $this->get);
        } else {
            $limit = '';
        }

        $info = $MemberModel
            ->alias('mem')
            ->join('left join __HONOUR_VIP_APPLY__ via ON mem.id=via.user_id')
            ->join('left join __USER_PLAN__ usp ON usp.user_id=mem.id')
            ->where($where)
            ->field('via.*,mem.id mid,mem.loginname,mem.nickname,mem.level,mem.role,mem.open_time,usp.plan_type')
            ->order('via.apply_addtime desc,mem.open_time desc,via.user_id asc,mem.id asc')
            ->limit($limit)
            ->select();
        foreach ($info as $k => $v) {
            //获取自动结算回本信息
            $vip_clearing_info = M('HonourVipClearing')->where('user_id=' . $v['mid'])->field('clearing_status')->find();
            $info[$k]['vip_clearing_status'] = $vip_clearing_info ? $vip_clearing_info['clearing_status'] : null;

            //获取申请时支付金额
            $apply_amount = [];
            $map_apply = [
                'user_id' => array('eq', $v['user_id']),
                'record_action' => array('eq', CurrencyAction::CashApplyHonourVIP),
            ];
            $apply_amount['cash'] = $AccountRecordModel->getFieldsValues(Currency::Cash, Tag::getMonth($v['apply_uptime']), 'record_amount', $map_apply);

            $map_apply['record_action'] = array('eq', CurrencyAction::WechatApplyHonourVIP);
            $apply_amount['wechat'] = $AccountRecordModel->getFieldsValues(Currency::Cash, Tag::getMonth($v['apply_uptime']), 'record_amount', $map_apply);

            $map_apply['record_action'] = array('eq', CurrencyAction::ENrollApplyHonourVIP);
            $apply_amount['enroll'] = $AccountRecordModel->getFieldsValues(Currency::Enroll, Tag::getMonth($v['apply_uptime']), 'record_amount', $map_apply);

            $info[$k]['apply_amount'] = $apply_amount;
        }

        return $info;
    }

    /**
     * 钻卡代理审核操作
     */
    public function honourVipReviewCenter()
    {
        $member = M('member');
        $Paramater = M('Parameter', 'g_');
        $HonourVipClearing = M('HonourVipClearing');
        $HonourVipApplyModel = M('HonourVipApply');

        $status = I('get.status');
        $id = I('get.id');

        if (!validateExtend($id, 'NUMBER')) {
            $this->error('ID参数有误');
        }
        if (!validateExtend($status, 'NUMBER')) {
            $this->error('ID参数有误');
        }

        M()->startTrans();

        //获取用户ID
        $uid = $HonourVipApplyModel->where('apply_id=' . $id)->getField('user_id');

        //供操作记录使用
        $where_log['id'] = array('eq', $uid);
        $log_data = $member->field('loginname,nickname,roleid')->where($where_log)->find();

        if ($status == 3) { //审核通过
            //更改用户级别
            $where['id'] = $uid;
            $data['level'] = 7;
            $result = M('member', 'zc_')->where($where)->save($data);

            //更改VIP申请表状态
            $wherekey['apply_id'] = $id;
            $data1['apply_status'] = 3;
            $data1['apply_uptime'] = time();
            $result1 = $HonourVipApplyModel->where($wherekey)->save($data1);

            //插入或更新定时结算数据(开启)
            $vip_clearing_info = $HonourVipClearing->where('user_id=' . $uid)->find();
            if ($vip_clearing_info) {
                $data_vip_celaring = [
                    'clearing_status' => 1,
                    'clearing_uptime' => time()
                ];
                $result5 = $HonourVipClearing->where('user_id=' . $uid)->save($data_vip_celaring);
            } else {
                //获取定时结算配置参数
                $Parameter = M('Parameter', 'g_');
                $parameter_info = $Paramater->where('id=1')->field('honour_vip_apply_return_amount,honour_vip_apply_return_month')->find();
                if (!$parameter_info) {
                    $this->error('定时结算配置参数获取失败');
                }

                $data_service_celaring = [
                    'user_id' => $uid,
                    'clearing_times' => $parameter_info['honour_vip_apply_return_month'],
                    'clearing_amount' => $parameter_info['honour_vip_apply_return_amount'],
                    'clearing_status' => 1,
                    'clearing_addtime' => time()
                ];
                if (!$HonourVipClearing->create($data_service_celaring)) {
                    $this->error($HonourVipClearing->getError());
                } else {
                    $result5 = $HonourVipClearing->add();
                }
            }

            if ($result === false || $result1 === false || $result5 === false) {
                M()->rollback();
                $this->error('操作失败，请稍后重试');
            }

            M()->commit();
            $this->success('审核通过！', '', false, '审核通过审核管理中的' . $log_data['loginname'] . '[' . $log_data['nickname'] . ']为钻卡代理');
        } elseif ($status == 2) { //驳回
            //更改用户级别
            $where['id'] = $uid;
            $data['level'] = $log_data['roleid'] > 0 ? $log_data['roleid'] : 2; //兼容此功能更新前个别已申请VIP的用户若被驳回后如果roleid为0,则默认直接恢复为创客级别
            $result = M('member', 'zc_')->where($where)->save($data);

            //更改VIP申请表状态
            $wherekey['apply_id'] = $id;
            $data2['apply_status'] = 2;
            $data2['apply_uptime'] = time();
            $data2['apply_remark'] = urldecode($this->get['reason']);
            $result1 = $HonourVipApplyModel->where($wherekey)->save($data2);

            //更新定时结算数据(关闭)
            $vip_clearing_info = $HonourVipClearing->where('user_id=' . $uid)->find();
            if ($vip_clearing_info) {
                $data_vip_celaring = [
                    'clearing_status' => 0,
                    'clearing_uptime' => time()
                ];
                $result5 = $HonourVipClearing->where('user_id=' . $uid)->save($data_vip_celaring);
            }

            if ($result === false || $result1 === false || $result5 === false) {
                M()->rollback();
                $this->erro('操作失败，请稍后重试');
            }

            M()->commit();
            $this->success("驳回成功", U('Admin/Review/honourVipReview/type/1'), false, "驳回:{$log_data['nickname']}[{$log_data['loginname']}]的钻卡代理申请");
        } else {
            $this->error('未知操作类型');
        }
    }

    /**
     * 钻卡代理自动回本记录
     */
    public function honourVipClearingLog()
    {
        $UserModel = new UserModel();

        $where = ' 1 ';

        $uid = $this->get['uid'];
        $time_min = $this->get['time_min'];
        $time_max = $this->get['time_max'];
        $page = $this->get['p'] > 1 ? $this->get['p'] : 1;

        if (!validateExtend($uid, 'NUMBER')) {
            $this->error('参数格式有误');
        } else {
            $where .= " and user_id=" . $uid;
        }

        if (!empty($time_min)) {
            $where .= " and log_addtime>=" . strtotime($time_min);
        }
        if (!empty($time_max)) {
            $where .= " and log_addtime<=" . strtotime($time_max . ' 23:59:59');
        }

        $data = $UserModel->getHonourVipClearingLogList('*', $page, 20, $where);

        $list = $data['list'];
        $this->assign('list', $list);

        $this->Page($data['paginator']['totalPage'] * $data['paginator']['everyPage'], $data['paginator']['everyPage'], $this->get);

        //获取自动回本信息状态
        $info = M('HonourVipClearing')->where('user_id=' . $uid)->field('clearing_status')->find();
        $this->assign('info', $info);

        $this->display();
    }

    /**
     * 钻卡代理自动回本状态处理
     */
    public function honourVipClearingStatus()
    {
        $HonourVipClearing = M('HonourVipClearing');

        $uid = $this->get['uid'];

        if (!validateExtend($uid, 'NUMBER')) {
            $this->error('参数有误');
        }

        $where = "user_id=" . $uid;
        $data = [];
        $action = '开启';

        $vip_clearing = $HonourVipClearing->where($where)->field('clearing_status')->find();
        if (!$vip_clearing) {
            $this->error('当前用户自动回本信息不存在');
        }

        if ($vip_clearing['clearing_status'] == '0') {
            $data['clearing_status'] = 1;
        } elseif ($vip_clearing['clearing_status'] == '1') {
            $data['clearing_status'] = 0;
            $action = '关闭';
        }

        if ($HonourVipClearing->where($where)->save($data) === false) {
            $this->error('操作失败');
        }

        $log_info = M('Member')->where('id=' . $uid)->field('loginname,nickname')->find();

        $this->success('成功' . $action . '钻卡代理自动回本', '', false, "[{$action}]{$log_info[nickname]}[{$log_info[loginname]}]钻卡代理自动回本");
    }

    /**
     * 钻卡代理列表导出
     */
    public function honourVipReviewExportAction()
    {
        $title_cn = '';
        //审核状态筛选
        switch ($this->get['type']) {
            case '0':
                $title_cn = '未审';
                break;
            case '1':
                $title_cn = '已审';
                break;
            default:
                $title_cn = '全部';
        }

        $data = $this->honourVipReviewBase(false);

        $export_data = array();
        foreach ($data as $k => $v) {
            $level = '体验用户';
            switch ($v['level']) {
                case '2':
                    $level = '创客用户';
                    break;
                case '5':
                    $level = '银卡代理';
                    break;
                case '6':
                    $level = '金卡代理';
                    break;
                case '7':
                    $level = '钻卡代理';
                    break;
            }
            switch ($v['role']) {
                case '3':
                    $level .= ' | 服务中心';
                    break;
                case '4':
                    $level .= ' | 区域合伙人';
                    break;
            }

            $vo = array(
                $v[loginname] . '[' . $v[nickname] . ']',
                format_date($v[apply_addtime]),
                $v[status] == '2' ? '审核通过' : ($v[status] == '1' ? '驳回:' . $v[apply_remark] : '后台手动直接升级'),
                format_date($v[apply_uptime]),
                $level,
                $v[plan_type] == 1 ? 'B计划' : 'A计划',
            );
            $export_data[] = $vo;
        }

        $head_array = array('用户账号', '申请时间', '审核结果', '审核时间', '当前身份', '所属计划');
        $file_name .= "{$title_cn}钻卡代理列表-" . date('Y-m-d') . "[全部]";
        $file_name = iconv("utf-8", "gbk", $file_name);
        $return = $this->xlsExport($file_name, $head_array, $export_data);
        !empty($return['error']) && $this->error($return['error']);

        $this->logWrite("导出{$title_cn}钻卡代理列表-" . date('Y-m-d') . "[全部]");
    }

    /**
     * 身份信息审核列表
     */
    public function identiyReview()
    {
        $info = $this->identiyReviewBase();
        $this->assign('info', $info);

        $this->display();
    }

    /**
     * 身份信息审核列表基础数据
     *
     * @param mixed $limit 用于导出功能时使用
     */
    private function identiyReviewBase($limit = true)
    {
        $CertificationModel = M('Certification');
        $MemberModel = M('Member');

        //隐藏买单时用户未实名认证默认收货地址信息记入实名认证表的数据
        $where_via = " 1  and via.certification_identify_1<>'' and via.certification_identify_2<>'' and via.certification_identify_3<>'' ";
        $where_mem = '  ';

        if (!empty($this->get['time_min'])) {
            $where_via .= " and via.certification_uptime>=" . strtotime($this->get['time_min'] . ' 00:00:00');
            //$where_mem .= " and mem.open_time>=" . strtotime($this->get['time_min'] . ' 00:00:00');
        }
        if (!empty($this->get['time_max'])) {
            $where_via .= " and via.certification_uptime<=" . strtotime($this->get['time_max'] . ' 23:59:59');
            //$where_mem .= " and mem.open_time<=" . strtotime($this->get['time_max'] . ' 23:59:59');
        }

        //审核状态筛选
        if ($this->get['type'] == '0') {
            $where_via .= " and via.certification_status=0 ";
            $where_mem = '';
        } elseif ($this->get['type'] == '1') {
            if (!empty($this->get['status'])) {
                $where_via .= " and via.certification_status=" . $this->get['status'];
            } else {
                $where_via .= " and via.certification_status in(1,2)";
            }

            //兼容显示未启用审核管理之前的已开通金卡代理的用户
            $where_mem = '';
        } else {
            $this->error('未知审核状态');
        }

        if (!empty($this->get['userid'])) {
            $where_via .= " and (mem.loginname='{$this->get[userid]}' or mem.nickname='{$this->get[userid]}' or mem.username='{$this->get[userid]}') ";
        }

        //判断当前管理员是否具有小管理员权限
        $is_small_super = $this->isSmallSuperManager();
        if (!$is_small_super && session("admin_id") != 1) {
//			$filter_member = $this->filterMember( session( 'admin_mid' ), false, '', array( 'repath' => "repath like '%," . session( 'admin_mid' ) . ",%'" ), "string" );
//
//			$where_via     .= " and mem.id in (select id from zc_member where {$filter_member['repath']} and {$filter_member['id']}) ";
        }

        //组合查询条件
        if (!empty($where_mem)) {
            $where = " ({$where_via}) or ({$where_mem}) ";
        } else {
            $where = $where_via;
        }

        if ($limit) {
            $count = $MemberModel
                ->alias('mem')
                ->join('join __CERTIFICATION__ via ON mem.id=via.user_id')
                ->where($where)
                ->count();

            $limit = $this->Page($count, 20, $this->get);
        } else {
            $limit = 10;
        }

        $info = $MemberModel
            ->alias('mem')
            ->join('join __CERTIFICATION__ via ON mem.id=via.user_id')
            ->where($where)
            ->field('via.*,mem.id mid,mem.loginname,mem.nickname,mem.level,mem.role,mem.open_time,mem.username')
            ->order('via.certification_addtime desc,via.user_id asc,mem.id asc')
            ->limit($limit)
            ->select();

        return $info;
    }

    /**
     * 身份信息审核操作
     */
    public function identiyReviewCenter()
    {
        $member = M('member');
        $CertificationModel = M('Certification');

        $status = I('get.status');
        $id = I('get.id');

        if (!validateExtend($id, 'NUMBER')) {
            $this->error('ID参数有误');
        }
        if (!validateExtend($status, 'NUMBER')) {
            $this->error('ID参数有误');
        }

        M()->startTrans();

        //获取用户ID
        $uid = $CertificationModel->where('certification_id=' . $id)->getField('user_id');

        //供操作记录使用
        $where_log['id'] = array('eq', $uid);
        $log_data = $member->field('loginname,nickname,roleid')->where($where_log)->find();

        if ($status == 2) { //审核通过
            //更改身份信息申请表状态
            $wherekey['certification_id'] = $id;
            $data1['certification_status'] = 2;
            $data1['certification_uptime'] = time();
            $result1 = $CertificationModel->where($wherekey)->save($data1);

            if ($result1 === false) {
                M()->rollback();
                $this->error('操作失败，请稍后重试');
            }

            M()->commit();
            $this->success('审核通过！', '', false, '审核通过审核管理中的' . $log_data['loginname'] . '[' . $log_data['nickname'] . ']的身份信息申请');
        } elseif ($status == 1) { //驳回
            //更改身份信息申请表状态
            $wherekey['certification_id'] = $id;
            $data2['certification_status'] = 1;
            $data2['certification_uptime'] = time();
            $data2['certification_remark'] = urldecode($this->get['reason']);
            $result1 = $CertificationModel->where($wherekey)->save($data2);

            if ($result1 === false) {
                M()->rollback();
                $this->erro('操作失败，请稍后重试');
            }

            M()->commit();
//            $this->success("驳回成功", U('Admin/Review/identiyReview/type/1'), false, "驳回:{$log_data['nickname']}[{$log_data['loginname']}]的身份信息申请");
            $this->success("驳回成功", '', false, "驳回:{$log_data['nickname']}[{$log_data['loginname']}]的身份信息申请");
        } else {
            $this->error('未知操作类型');
        }
    }

    /**
     * 身份信息列表导出
     */
    public function identiyReviewExportAction()
    {
        $title_cn = '';
        //审核状态筛选
        switch ($this->get['type']) {
            case '0':
                $title_cn = '未审';
                break;
            case '1':
                $title_cn = '已审';
                break;
            default:
                $title_cn = '全部';
        }

        $data = $this->identiyReviewBase(false);

        $export_data = array();
        foreach ($data as $k => $v) {
            $vo = array(
                $v[loginname] . '[' . $v[nickname] . ']',
                format_date($v[certification_addtime]),
                $v[status] == '2' ? '审核通过' : ($v[status] == '1' ? '驳回:' . $v[apply_remark] : '未知'),
                format_date($v[certification_uptime])
            );
            $export_data[] = $vo;
        }

        $head_array = array('用户账号', '申请时间', '审核结果', '审核时间');
        $file_name .= "{$title_cn}身份信息列表-" . date('Y-m-d') . "[全部]";
        $file_name = iconv("utf-8", "gbk", $file_name);
        $return = $this->xlsExport($file_name, $head_array, $export_data);
        !empty($return['error']) && $this->error($return['error']);

        $this->logWrite("导出{$title_cn}身份信息列表-" . date('Y-m-d') . "[全部]");
    }

    /**
     * 银卡代理审核列表
     */
    public function microVipReview()
    {
        $info = $this->microVipReviewBase();
        $this->assign('info', $info);

        $this->display();
    }

    /**
     * 银卡代理审核列表基础数据
     *
     * @param mixed $limit 用于导出功能时使用
     */
    private function microVipReviewBase($limit = true)
    {
        $MicroVipApplyModel = M('MicroVipApply');
        $MemberModel = M('Member');
        $AccountRecordModel = new AccountRecordModel();

        $where_via = ' 1 ';
        $where_mem = ' 1 ';

        if (!empty($this->get['time_min'])) {
            $where_via .= " and via.apply_uptime>=" . strtotime($this->get['time_min'] . ' 00:00:00');
            $where_mem .= " and mem.open_time>=" . strtotime($this->get['time_min'] . ' 00:00:00');
        }
        if (!empty($this->get['time_max'])) {
            $where_via .= " and via.apply_uptime<=" . strtotime($this->get['time_max'] . ' 23:59:59');
            $where_mem .= " and mem.open_time<=" . strtotime($this->get['time_max'] . ' 23:59:59');
        }

        //审核状态筛选
        if ($this->get['type'] == '0') {
            $where_via .= " and via.apply_status=1 ";
            $where_mem = '';
        } elseif ($this->get['type'] == '1') {
            if (!empty($this->get['status'])) {
                $where_via .= " and via.apply_status=" . $this->get['status'];
            } else {
                $where_via .= " and via.apply_status in(2,3)";
            }

            //兼容显示未启用审核管理之前的已开通金卡代理的用户
            if (empty($this->get['status']) || $this->get['status'] == '3') {
                $where_mem .= " and mem.level=5";
            } else {
                $where_mem = '';
            }
        } else {
            $this->error('未知审核状态');
        }

        if (!empty($this->get['userid'])) {
            $where_via .= " and mem.loginname='{$this->get[userid]}' or mem.nickname='{$this->get[userid]}' ";
        }

        //判断当前管理员是否具有小管理员权限
        $is_small_super = $this->isSmallSuperManager();
        if (!$is_small_super && session("admin_id") != 1) {
            $filter_member = $this->filterMember(session('admin_mid'), false, '', array('repath' => "repath like '%," . session('admin_mid') . ",%'"), "string");
            $where_via .= " and mem.id in (select id from zc_member where {$filter_member['repath']} and {$filter_member['id']}) ";
        }

        //组合查询条件
        if (!empty($where_mem)) {
            $where = " ({$where_via}) or ({$where_mem}) ";
        } else {
            $where = $where_via;
        }

        //所属计划筛选(针对全局筛选,故放至最后)
        if ($this->get['plan_type'] == '0') {
            $where = " ({$where}) and (usp.plan_type={$this->get['plan_type']} or usp.plan_type is null) ";
        } elseif ($this->get['plan_type'] == '1') {
            $where = " ({$where}) and usp.plan_type={$this->get['plan_type']} ";
        }

        if ($limit) {
            $count = $MemberModel
                ->alias('mem')
                ->join('left join __MICRO_VIP_APPLY__ via ON mem.id=via.user_id')
                ->join('left join __USER_PLAN__ usp ON usp.user_id=mem.id')
                ->where($where)
                ->count();
            $limit = $this->Page($count, 20, $this->get);
        } else {
            $limit = '';
        }

        $info = $MemberModel
            ->alias('mem')
            ->join('left join __MICRO_VIP_APPLY__ via ON mem.id=via.user_id')
            ->join('left join __USER_PLAN__ usp ON usp.user_id=mem.id')
            ->where($where)
            ->field('via.*,mem.id mid,mem.loginname,mem.nickname,mem.level,mem.role,mem.open_time,usp.plan_type')
            ->order('via.apply_addtime desc,mem.open_time desc,via.user_id asc,mem.id asc')
            ->limit($limit)
            ->select();
        foreach ($info as $k => $v) {
            //获取自动结算回本信息
            $micro_vip_clearing_info = M('MicroVipClearing')->where('user_id=' . $v['mid'])->field('clearing_status')->find();
            $info[$k]['micro_vip_clearing_status'] = $micro_vip_clearing_info ? $micro_vip_clearing_info['clearing_status'] : null;

            //获取申请时支付金额
            $apply_amount = [];
            $map_apply = [
                'user_id' => array('eq', $v['user_id']),
                'record_action' => array('eq', CurrencyAction::CashApplyMicroVIP),
            ];
            $apply_amount['cash'] = $AccountRecordModel->getFieldsValues(Currency::Cash, Tag::getMonth($v['apply_uptime']), 'record_amount', $map_apply);

            $map_apply['record_action'] = array('eq', CurrencyAction::WechatApplyMicroVIP);
            $apply_amount['wechat'] = $AccountRecordModel->getFieldsValues(Currency::Cash, Tag::getMonth($v['apply_uptime']), 'record_amount', $map_apply);

            $map_apply['record_action'] = array('eq', CurrencyAction::ENrollApplyMicroVIP);
            $apply_amount['enroll'] = $AccountRecordModel->getFieldsValues(Currency::Enroll, Tag::getMonth($v['apply_uptime']), 'record_amount', $map_apply);

            $info[$k]['apply_amount'] = $apply_amount;
        }

        return $info;
    }

    /**
     * 银卡代理代理审核操作
     */
    public function microVipReviewCenter()
    {
        $member = M('member');
        $Paramater = M('Parameter', 'g_');
        $MicroVipClearing = M('MicroVipClearing');
        $MicroVipApplyModel = M('MicroVipApply');

        $status = I('get.status');
        $id = I('get.id');

        if (!validateExtend($id, 'NUMBER')) {
            $this->error('ID参数有误');
        }
        if (!validateExtend($status, 'NUMBER')) {
            $this->error('ID参数有误');
        }

        M()->startTrans();

        //获取用户ID
        $uid = $MicroVipApplyModel->where('apply_id=' . $id)->getField('user_id');

        //供操作记录使用
        $where_log['id'] = array('eq', $uid);
        $log_data = $member->field('loginname,nickname,roleid')->where($where_log)->find();

        if ($status == 3) { //审核通过
            //更改用户级别
            $where['id'] = $uid;
            $data['level'] = 5;
            $result = M('member', 'zc_')->where($where)->save($data);

            //更改银卡代理申请表状态
            $wherekey['apply_id'] = $id;
            $data1['apply_status'] = 3;
            $data1['apply_uptime'] = time();
            $result1 = $MicroVipApplyModel->where($wherekey)->save($data1);

            //插入或更新定时结算数据(开启)
            $vip_clearing_info = $MicroVipClearing->where('user_id=' . $uid)->find();
            if ($vip_clearing_info) {
                $data_vip_celaring = [
                    'clearing_status' => 1,
                    'clearing_uptime' => time()
                ];
                $result5 = $MicroVipClearing->where('user_id=' . $uid)->save($data_vip_celaring);
            } else {
                //获取定时结算配置参数
                $Parameter = M('Parameter', 'g_');
                $parameter_info = $Paramater->where('id=1')->field('v51_mirco_vip_apply_return_amount,v51_mirco_vip_apply_return_month')->find();
                if (!$parameter_info) {
                    $this->error('定时结算配置参数获取失败');
                }

                $data_service_celaring = [
                    'user_id' => $uid,
                    'clearing_times' => $parameter_info['v51_mirco_vip_apply_return_month'],
                    'clearing_amount' => $parameter_info['v51_mirco_vip_apply_return_amount'],
                    'clearing_status' => 1,
                    'clearing_addtime' => time()
                ];
                if (!$MicroVipClearing->create($data_service_celaring)) {
                    $this->error($MicroVipClearing->getError());
                } else {
                    $result5 = $MicroVipClearing->add();
                }
            }

            if ($result === false || $result1 === false || $result5 === false) {
                M()->rollback();
                $this->error('操作失败，请稍后重试');
            }

            M()->commit();
            $this->success('审核通过！', '', false, '审核通过审核管理中的' . $log_data['loginname'] . '[' . $log_data['nickname'] . ']为金卡代理');
        } elseif ($status == 2) { //驳回
            //更改用户级别
            $where['id'] = $uid;
            $data['level'] = $log_data['roleid'] > 0 ? $log_data['roleid'] : 2; //兼容此功能更新前个别已申请VIP的用户若被驳回后如果roleid为0,则默认直接恢复为创客级别
            $result = M('member', 'zc_')->where($where)->save($data);

            //更改VIP申请表状态
            $wherekey['apply_id'] = $id;
            $data2['apply_status'] = 2;
            $data2['apply_uptime'] = time();
            $data2['apply_remark'] = urldecode($this->get['reason']);
            $result1 = $MicroVipApplyModel->where($wherekey)->save($data2);

            //更新定时结算数据(关闭)
            $micro_vip_clearing_info = $MicroVipClearing->where('user_id=' . $uid)->find();
            if ($micro_vip_clearing_info) {
                $data_vip_celaring = [
                    'clearing_status' => 0,
                    'clearing_uptime' => time()
                ];
                $result5 = $MicroVipClearing->where('user_id=' . $uid)->save($data_vip_celaring);
            }

            if ($result === false || $result1 === false || $result5 === false) {
                M()->rollback();
                $this->erro('操作失败，请稍后重试');
            }

            M()->commit();
            $this->success("驳回成功", U('Admin/Review/vipReview/type/1'), false, "驳回:{$log_data['nickname']}[{$log_data['loginname']}]的银卡代理申请");
        } else {
            $this->error('未知操作类型');
        }
    }

    /**
     * 银卡代理自动回本记录
     */
    public function microVipClearingLog()
    {
        $UserModel = new UserModel();

        $where = ' 1 ';

        $uid = $this->get['uid'];
        $time_min = $this->get['time_min'];
        $time_max = $this->get['time_max'];
        $page = $this->get['p'] > 1 ? $this->get['p'] : 1;

        if (!validateExtend($uid, 'NUMBER')) {
            $this->error('参数格式有误');
        } else {
            $where .= " and user_id=" . $uid;
        }

        if (!empty($time_min)) {
            $where .= " and log_addtime>=" . strtotime($time_min);
        }
        if (!empty($time_max)) {
            $where .= " and log_addtime<=" . strtotime($time_max . ' 23:59:59');
        }

        $data = $UserModel->getMicroVipClearingLogList('*', $page, 20, $where);

        $list = $data['list'];
        $this->assign('list', $list);

        $this->Page($data['paginator']['totalPage'] * $data['paginator']['everyPage'], $data['paginator']['everyPage'], $this->get);

        //获取自动回本信息状态
        $info = M('MicroVipClearing')->where('user_id=' . $uid)->field('clearing_status')->find();
        $this->assign('info', $info);

        $this->display();
    }

    /**
     * 银卡代理自动回本状态处理
     */
    public function microVipClearingStatus()
    {
        $MicroVipClearing = M('MicroVipClearing');

        $uid = $this->get['uid'];

        if (!validateExtend($uid, 'NUMBER')) {
            $this->error('参数有误');
        }

        $where = "user_id=" . $uid;
        $data = [];
        $action = '开启';

        $micro_vip_clearing = $MicroVipClearing->where($where)->field('clearing_status')->find();
        if (!$micro_vip_clearing) {
            $this->error('当前用户自动回本信息不存在');
        }

        if ($micro_vip_clearing['clearing_status'] == '0') {
            $data['clearing_status'] = 1;
        } elseif ($micro_vip_clearing['clearing_status'] == '1') {
            $data['clearing_status'] = 0;
            $action = '关闭';
        }

        if ($MicroVipClearing->where($where)->save($data) === false) {
            $this->error('操作失败');
        }

        $log_info = M('Member')->where('id=' . $uid)->field('loginname,nickname')->find();

        $this->success('成功' . $action . '银卡代理自动回本', '', false, "[{$action}]{$log_info[nickname]}[{$log_info[loginname]}]银卡代理自动回本");
    }

    /**
     * 银卡代理列表导出
     */
    public function microVipReviewExportAction()
    {
        $title_cn = '';
        //审核状态筛选
        switch ($this->get['type']) {
            case '0':
                $title_cn = '未审';
                break;
            case '1':
                $title_cn = '已审';
                break;
            default:
                $title_cn = '全部';
        }

        $data = $this->vipReviewBase(false);

        $export_data = array();
        foreach ($data as $k => $v) {
            $level = '体验用户';
            switch ($v['level']) {
                case '2':
                    $level = '创客用户';
                    break;
                case '5':
                    $level = '银卡代理';
                    break;
                case '6':
                    $level = '金卡代理';
                    break;
                case '7':
                    $level = '钻卡代理';
                    break;
            }
            switch ($v['role']) {
                case '3':
                    $level .= ' | 服务中心';
                    break;
                case '4':
                    $level .= ' | 区域合伙人';
                    break;
            }

            $vo = array(
                $v[loginname] . '[' . $v[nickname] . ']',
                format_date($v[apply_addtime]),
                $v[status] == '2' ? '审核通过' : ($v[status] == '1' ? '驳回:' . $v[apply_remark] : '后台手动直接升级'),
                format_date($v[apply_uptime]),
                $level,
                $v[plan_type] == 1 ? 'B计划' : 'A计划',
            );
            $export_data[] = $vo;
        }

        $head_array = array('用户账号', '申请时间', '审核结果', '审核时间', '当前身份', '所属计划');
        $file_name .= "{$title_cn}银卡代理列表-" . date('Y-m-d') . "[全部]";
        $file_name = iconv("utf-8", "gbk", $file_name);
        $return = $this->xlsExport($file_name, $head_array, $export_data);
        !empty($return['error']) && $this->error($return['error']);

        $this->logWrite("导出{$title_cn}银卡代理列表-" . date('Y-m-d') . "[全部]");
    }

}

?>