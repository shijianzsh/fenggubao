<?php

namespace V4\Model;

/**
 * 用户财务统计
 */
class ProcedureQueueModel extends BaseModel {

    /**
     * 获取可执行队列
     * @return type
     */
    public function getQueue() {
        $where = "(queue_status IN (0, 2) OR (queue_status = 1 AND queue_tag <>''))"
                . " AND queue_execute_count < 5" // 执行失败次数不能超过 5 次
                . " AND (queue_status = 0 OR  (queue_endtime < IF(queue_execute_count = 4, " . (time() - 60 * 20) . ", IF(queue_execute_count = 3, " . (time() - 60 * 10) . ", IF(queue_execute_count=2, " . (time() - 60 * 5) . ",  IF(queue_execute_count=1, " . (time() - 60 * 1) . ", " . time() . "))))))"; // 重新执行时间频率, 1 : 60s， 2  : 300s, 3 : 600s, 4 : 1200s

        return M('procedure_queue')->where($where)->group('queue_tag')->having('queue_status <> 1')->order('queue_id ASC')->find();
    }

    /**
     * 执行队列
     * @param type $item
     */
    public function execute($item) {
        $sql = C('ALIYUN_TDDL_MASTER') . 'CALL ' . $item['queue_action'] . '(';
        if ($item['queue_params'] != '') {
            $sql .= $item['queue_params'];
        }
        if ($item['queue_params'] != '' && $item['queue_params_out'] != '') {
            $sql .= ',';
        }
        if ($item['queue_params_out'] != '') {
            $sql .= $item['queue_params_out'];
        }
        $sql .= ');';
        M()->execute($sql);
        $result = M()->query(C('ALIYUN_TDDL_MASTER') . "select " . $item['queue_params_out']);
        if ($result && is_array($result) && count($result) > 0 && isset($result[0]['@error']) && $result[0]['@error'] == 1) {
            return false;
        }
        return true;
    }

    /**
     * 执行存储过程，自动断开数据库连接
     * @param type $queue
     * @return boolean
     */
    public function executeQueue($queue) {
        M()->startTrans();
        $result = $this->execute($queue);
        if ($result) {
            M()->commit();
        } else {
            M()->rollback();
        }
        return $result;
    }

    /**
     * 更新队列
     * @param type $item
     * @return type
     */
    public function update($item) {
        return M('procedure_queue')->save($item);
    }

    /**
     * 添加队列
     * @param type $item
     * @return type
     */
    public function add($item) {
        return M('procedure_queue')->add($item);
    }
    
    
    

}
