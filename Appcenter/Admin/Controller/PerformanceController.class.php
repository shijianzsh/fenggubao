<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 业绩管理
// +----------------------------------------------------------------------
namespace Admin\Controller;

use Common\Controller\AuthController;
use V4\Model\ProcedureModel;

class PerformanceController extends AuthController
{


    public function __construct()
    {
        parent::__construct();

        C('TOKEN_ON', false);
    }

    public function index()
    {
        $this->display();
    }

    /**
     * 业绩结算任务管理zc_performance_reward_task
     */
    public function rewardTask()
    {
        $where = [];
        $count = M('performance_reward_task')->where($where)->count();
        $limit = $this->Page($count, 20, $this->get);
        $list = M('performance_reward_task')->where($where)->order('task_id desc')->limit($limit)->select();
        foreach ($list as $k => $v) {
            if ($v['task_status'] == 0) {
                $list[$k]['task_status'] = '未执行';
            } elseif ($v['task_status'] == 1) {
                $list[$k]['task_status'] = '执行中';
            } elseif ($v['task_status'] == 2) {
                $list[$k]['task_status'] = '已失败';
            } elseif ($v['task_status'] == 3) {
                $list[$k]['task_status'] = '已完成';
            }
        }
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 区域合伙人业务补贴记录
     */
    public function rewardRecord()
    {
        $where = ['p.task_id' => intval($this->get['task_id'])];
        $count = M('performance_reward_record p')->where($where)->count();
        $limit = $this->Page($count, 20, $this->get);
        $list = M('performance_reward_record p')
            ->field('m.id, m.role, m.loginname, m.nickname, m.img, m.is_partner, m.star, m.level, p.*')
            ->join('left join zc_member m on m.id = p.user_id')
            ->order('p.record_id desc')
            ->where($where)
            ->limit($limit)
            ->select();
        foreach ($list as $k => $v) {
            if ($v['record_status'] == 0) {
                $list[$k]['record_status'] = '未执行';
            } elseif ($v['record_status'] == 1) {
                $list[$k]['record_status'] = '执行中';
            } elseif ($v['record_status'] == 2) {
                $list[$k]['record_status'] = '已失败';
            } elseif ($v['record_status'] == 3) {
                $list[$k]['record_status'] = '已完成';
            }
            $list[$k]['dengji'] = getrole([
                'level' => $list[$k]['user_level'],
                'star' => $list[$k]['user_star'],
                'role' => 0,
                'is_partner' => 0
            ]);
            if ($list[$k]['dengji'] == '体验会员') {
                $list[$k]['dengji'] = '';
            }
        }
        $this->assign('list', $list);
        $this->display();
    }

    public function bonusTask()
    {

        $yesterdayPerformanceAmount = M('performance_' . date("Ym", strtotime("-1 day")))->where([
            'user_id' => 0,
            'performance_tag' => date("Ymd", strtotime("-1 day"))
        ])->getField('performance_amount') ?: '0.0000';
        $performancePortionBase = M('settings')->where(['settings_code' => 'performance_portion_base'])->getField('settings_value') ?: 1000;
        $consumes = M('consume as c')
            ->join('zc_member as m on c.user_id = m.id')
            ->join('zc_performance_rule as r on m.star = r.rule_id')
            ->field('m.star, r.rule_label as label, count(c.user_id) as `count`, sum(c.amount) as `amount`')
            ->where('m.star > 0')
            ->order('m.star desc')
            ->group('m.star')
            ->select();
        foreach ($consumes as $index => $consume) {
            $consume['portion'] = intval($consume['amount'] / $performancePortionBase);
            $consumes[$index] = $consume;
        }
        $where = [];
        $count = M('performance_bonus')->where($where)->count();
        $limit = $this->Page($count, 20, $this->get);
        $bonus = M('performance_bonus')->where($where)->order('id desc')->limit($limit)->select();
        $this->assign('yesterdayPerformanceAmount', $yesterdayPerformanceAmount);
        $this->assign('performancePortionBase', $performancePortionBase);
        $this->assign('consumes', $consumes);
        $this->assign('bonus', $bonus);
        $this->display();
    }

    /**
     * 加权分红 [AJAX]
     */
    public function bonus()
    {
        set_time_limit(0);
        $data = $this->post;
        if (!validateExtend($data['star'], 'NUMBER') || $data['star'] <= 0) {
            $this->error('分红等级有误');
        }
        if (!validateExtend($data['performance_amount'], 'MONEY') || $data['performance_amount'] <= 0) {
            $this->error('');
        }
        if (!validateExtend($data['total_amount'], 'MONEY') || $data['total_amount'] <= 0) {
            $this->error('');
        }

        M()->startTrans();
        $procedureModel = new ProcedureModel();
        $result = $procedureModel->execute('Income_bonus', sprintf("%d,%f,%f", $data['star'], $data['performance_amount'], $data['total_amount']), '@error');
        set_time_limit(15);
        if ($result === false) {
            M()->commit();
            $this->error('分红失败');
        } else {
            M()->rollback();
            $this->success('分红成功', '', false, "成功加权分红");
        }
    }

    public function bonusRecord()
    {
//		$where = [ 'p.task_id' => intval( $this->get['task_id'] ) ];
        $count = M('subsidy_record')->count();
        $limit = $this->Page($count, 20, $this->get);
        $list = M('subsidy_record sr')
            ->field('m.id, m.role, m.role_star, m.loginname, m.nickname, m.img, m.star, m.level, sr.*')
            ->join('left join zc_member m on m.id = sr.user_id')
            ->order('sr.record_id desc')
            ->limit($limit)
            ->select();

        foreach ($list as $k => $v) {
            $list[$k]['dengji'] = getrole([
                'level' => $list[$k]['level'],
                'star' => $list[$k]['star'],
                'role' => $list[$k]['role'],
                'role_star' => $list[$k]['role_star']
            ]);
            if ($list[$k]['dengji'] == '体验会员') {
                $list[$k]['dengji'] = '';
            }
        }
        $this->assign('list', $list);
        $this->display();
    }


}