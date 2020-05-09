<?php

namespace V4\Model;

/**
 * 用户实时统计数据模块
 */
class DebugLogModel
{

    // 存放实例
    public static $instance;

    private function __construct()
    {

    }

    public static function instance()
    {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    private function getTableName($tag = 0)
    {
        $_tableName = 'debug_log';
        return $_tableName;
    }

    protected function M($tag = 0)
    {
        return M($this->getTableName($tag));
    }

    public function add($log, $params = '')
    {

        if (!is_string($log)) {
            $log = json_encode($log, JSON_UNESCAPED_UNICODE);
        }

        if (!is_string($params)) {
            $params = json_encode($params, JSON_UNESCAPED_UNICODE);
        }

        $item = [
            'debug_log' => $log,
            'debug_params' => $params,
            'debug_addtime' => time(),
        ];

        $this->M()->add($item);
        return $item;
    }


}
