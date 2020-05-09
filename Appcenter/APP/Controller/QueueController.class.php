<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/4
 * Time: 16:18
 */

namespace APP\Controller;

use Think\Controller;
use V4\Model\ProcedureQueueModel;

class QueueController extends Controller {

    public function __construct() {
        parent::__construct();
        header('Content-Type:text/html; charset=utf-8');
    }

    public function index() {
        
    }

    public function procedure() {

        $queueM = new ProcedureQueueModel();
        $queue = $queueM->getQueue();
        if ($queue) {
            set_time_limit(0);
            // 锁定队列
            echo date('Y-m-d H:i:s') . " - 锁定队列" . "\n";
            $queueM->update(['queue_id' => $queue['queue_id'], 'queue_status' => 1, 'queue_starttime' => time(), 'queue_execute_count' => $queue['queue_execute_count'] + 1]);
            // 执行队列
            echo date('Y-m-d H:i:s') . " - 执行队列" . "\n";
            $result = $queueM->executeQueue($queue);
            // 结束队列       
            if ($result) {
                echo date('Y-m-d H:i:s') . " - 执行成功" . "\n";
                $queueM->update(['queue_id' => $queue['queue_id'], 'queue_status' => 3, 'queue_endtime' => time()]);

                // 管理津贴发放成功后，发送短信通知
                if ($queue['queue_action'] == 'Bonus_gljt') {
                    $url = U('General/sendGLJTmsg', '', true, true);
                    echo file_get_contents($url);
                }
            } else {
                echo date('Y-m-d H:i:s') . " - 执行失败" . "\n";
                $queueM->update(['queue_id' => $queue['queue_id'], 'queue_status' => 2, 'queue_endtime' => time()]);
            }
            set_time_limit(30);
        } else {
            echo date('Y-m-d H:i:s') . " - 无队列" . "\n";
        }
    }

}
