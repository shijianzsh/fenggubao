<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/4
 * Time: 17:07
 */

namespace V4\Model;

/**
 * 提现相关
 * Class Currency
 * @package V4\Model
 */
class WithdrawModel {

    
    public function getListByUserId($user_id, $pn, $ps=10){
        $where['uid'] = $user_id;
        //记录总数
        $count = M('withdraw_cash')->where($where)->count();
            
        $data = M('withdraw_cash')
            ->field('id, serial_num, receiver, amount, `status`, `check`, tiqu_type, add_time')
            ->where($where)
            ->order('id desc')
            ->limit(($pn-1)*$ps.','.$ps)
            ->select();
        
        //处理数据
        foreach ($data as $k=>$v){
            if($v['tiqu_type'] == 1){
                $data[$k]['tool'] = '微信';
            }else{
                $data[$k]['tool'] = '银行卡';
            }
            if($v['check'] == '0'){
                $data[$k]['status_code'] = '未审核';
            }else{
                if($v['status'] == '0'){
                    $data[$k]['status_code'] = '待处理';
                }elseif($v['status'] == 'S'){
                    $data[$k]['status_code'] = '成功';
                }elseif($v['status'] == 'F'){
                    $data[$k]['status_code'] = '失败';
                }elseif($v['status'] == 'B'){
                    $data[$k]['status_code'] = '已退款';
                }elseif($v['status'] == 'TS'){
                    $data[$k]['status_code'] = '手动退款成功';
                }elseif($v['status'] == 'TF'){
                    $data[$k]['status_code'] = '手动退款失败';
                }elseif($v['status'] == 'W'){
                    $data[$k]['status_code'] = '处理中';
                }
            }
            $data[$k]['time'] = date('Y-m-d H:i', $v['add_time']);
        }
        
        $page['totalPage'] = ceil($count/$ps);
        $page['everyPage'] = $ps;
        $return['page'] = $page;
        $return['data'] = $data;
        $return['count'] = $count;
        
        return $return;
    }
    
    
    /**
     * 获取提现状态
     * Enter description here ...
     * @param $order_no
     */
    public function getStatus($order_no){
        $where['serial_num'] = $order_no;
        $item = M('withdraw_cash')->field(' `status`,  tiqu_type')->where($where)->find();
        if($item['status'] == '0'){
            return '待处理';
        }elseif($item['status'] == 'S'){
            return '成功到账';
        }elseif($item['status'] == 'F'){
            return '失败';
        }elseif($item['status'] == 'B'){
            return '已退款';
        }elseif($item['status'] == 'TS'){
            return '手动退款成功';
        }elseif($item['status'] == 'TF'){
            return '手动退款失败';
        }elseif($item['status'] == 'W'){
            return '银行处理中';
        }else{
            return '-';
        }
    }
    
}
?>