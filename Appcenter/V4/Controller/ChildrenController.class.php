<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/4
 * Time: 16:18
 */

namespace V4\Controller;

use Think\Controller;
use V4\Model\ProcedureModel;

class ChildrenController extends Controller
{

    public function __construct()
    {
        parent::__construct();
        header('Content-Type:text/html; charset=utf-8');
    }

    public function index()
    {
        set_time_limit(0);
        $start = time();
        $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表
        $members = $Model->query("SELECT id, repath, `level`, role FROM __PREFIX__member WHERE `level` IN (5,6,7) OR role = 4 ORDER BY id DESC LIMIT 20000");

        $members_dict = [];
        foreach ($members as $member) {
            $members_dict[$member['id']] = $member;
        }
        unset($members);

        $children = [];
        foreach ($members_dict as $member) {
            $parents = array_reverse(explode(',', $member['repath']));
            if (in_array($member['level'], [5, 6, 7])) {
                $vip_index = 1;
                foreach ($parents as $parent) {
                    if (intval($parent) <= 0) continue;
                    if (!isset($children[$parent])) $children[$parent] = array("user_id" => $parent, "children_1_vip" => 0, "children_1_vip_ids" => ',', "children_2_vip" => 0, "children_2_vip_ids" => ',', "children_1_company" => 0, "children_1_company_ids" => ',', "children_2_company" => 0, "children_2_company_ids" => ',', "children_uptime" => time());
                    $children[$parent]['children_' . $vip_index . '_vip']++;
                    $children[$parent]['children_' . $vip_index . '_vip_ids'] .= $member['id'] . ',';
                    if ($members_dict[$parent]['level'] == 6) $vip_index++;
                    if ($vip_index == 3) break;
                }
            }
            if ($member['role'] == 4) {
                $company_index = 1;
                foreach ($parents as $parent) {
                    if (intval($parent) <= 0) continue;
                    if (!isset($children[$parent])) $children[$parent] = array("user_id" => $parent, "children_1_vip" => 0, "children_1_vip_ids" => ',', "children_2_vip" => 0, "children_2_vip_ids" => ',', "children_1_company" => 0, "children_1_company_ids" => ',', "children_2_company" => 0, "children_2_company_ids" => ',', "children_uptime" => time());
                    $children[$parent]['children_' . $company_index . '_company']++;
                    $children[$parent]['children_' . $company_index . '_company_ids'] .= $member['id'] . ',';
                    if ($members_dict[$parent]['role'] == 4) $company_index++;
                    if ($company_index == 3) break;
                }
            }
        }
        unset($members_dict);
        M()->startTrans();
        $Model = new \Think\Model();
        $Model->execute("TRUNCATE TABLE __PREFIX__user_children;");
        echo '条数：' . count($children);
        echo '<br />';
        $list = [];
        foreach ($children as $child) {
            $list[] = $child;
        }
        unset($children);
        if (M('user_children')->addAll($list)) {
            M()->commit();
            echo '成功';
        } else {
            M()->rollback();
            echo '失败';
        }
        // echo '状态：' . M('user_children')->addAll($list);        
        unset($list);
        $end = time();
        echo '<br />';
        echo '耗时：' . ($end - $start) . 's';
        set_time_limit(30);
    }


}
