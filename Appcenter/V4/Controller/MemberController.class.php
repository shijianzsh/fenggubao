<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/4
 * Time: 16:18
 */

namespace V4\Controller;

use V4\Model\AccountModel;
use V4\Model\AccountRecordModel;
use V4\Model\Currency;
use Think\Controller;
use V4\Model\CurrencyAction;
use V4\Model\RewardModel;
use V4\Model\Tag;

class MemberController extends Controller {

    public function index() {
        
        //set_time_limit(0);
        $o_count =  M('member')->where("repath <> ''")->count();
        //$i = 0;
        //while ($i < 1000) {
            $sql = "UPDATE zc_member AS m1, zc_member AS m2 SET m1.relevel = m2.relevel + 1, m1.repath = CONCAT(m2.repath, m2.id, ',') WHERE m1.reid = m2.id AND m2.repath <> '';";
            M()->execute($sql);
            $n_count = M('member')->where("repath <> ''")->count();
            if ($o_count < $n_count) {
                $o_count = $n_count;
                echo $i . ' => ' . $o_count . ', ' . $n_count . '<br />';
                //sleep(1);
				echo "<script>location.reload();</script>";
            } else {
                echo $i . ' => ' . $o_count . ', ' . $n_count . '<br />';
                //break;
            }
            //$i++;
        //}
        //set_time_limit(30);
    }

}
