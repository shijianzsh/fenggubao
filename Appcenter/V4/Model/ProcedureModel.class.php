<?php

namespace V4\Model;

/**
 * 执行存储过程
 */
class ProcedureModel extends BaseModel {

    /**
     * 执行存储过程
     * @param type $item
     */
    public function execute($action, $params = '', $params_out = '') {
        $sql = $this->sql($action, $params, $params_out);
        M()->execute($sql);
        $result = M()->query(C('ALIYUN_TDDL_MASTER') . "select " . $params_out);
        if ($result && is_array($result) && count($result) > 0 && isset($result[0]['@error']) && $result[0]['@error'] == 1) {
            return false;
        }
        return $result;
    }

    private function sql($action, $params = '', $params_out = '') {
        $sql = C('ALIYUN_TDDL_MASTER') . 'CALL ' . $action . '(';
        if ($params != '') {
            $sql .= $params;
        }
        if ($params != '' && $params_out != '') {
            $sql .= ',';
        }
        if ($params_out != '') {
            $sql .= $params_out;
        }
        $sql .= ');';
        return $sql;
    }

    public function outSql($action, $params = '', $params_out = '') {
        echo $this->sql($action, $params, $params_out) . '<br />';
    }

}
