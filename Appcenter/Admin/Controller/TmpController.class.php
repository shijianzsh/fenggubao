<?php
/**
 * Created by PhpStorm.
 * User: 時間在說謊
 * Date: 2020/5/11
 * Time: 17:19
 */

namespace Admin\Controller;

use Think\Controller;

class TmpController extends Controller
{
    public function index()
    {
        $where['id'] = ['ELT', 118145514];
        $where['amount'] = ['GT', 0];
        $where['machine_amount_4'] = 0;
        $where['is_out'] = 0;
        $where['dynamic_out'] = 0;
        $field = 'id,user_id,machine_amount_4';

        $a = M('consume')->where($where)->field($field)->order('id desc')->select();
        var_dump($a);
        foreach ($a as $value) {
            $where_['id'] = $value['id'];
            $info = M('consume', '', 'DB_CONFIG1')->where($where_)->field($field)->find();
            var_dump($info);
            // if ($info['machine_amount_4'] > 0) {
            //     $data['machine_amount_4'] = $info['machine_amount_4'];
            //     $ret = M('consume')->where($where_)->save($data);
            // }
        }
    }
}