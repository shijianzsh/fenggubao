<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/4
 * Time: 17:07
 */

namespace V4\Model;

/**
 * 配置相关
 * Class Currency
 * @package V4\Model
 */
class SettingsModel
{

    private static $_instance;

    /**
     * 单例-获取new对象
     * Enter description here ...
     */
    public static function getInstance()
    {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }


    public function get($key)
    {
        return M('Settings')->where(['settings_code' => $key, 'settings_status' => 1])->getField('settings_value');
    }

    public function gets($keys = [])
    {
        $data = [];
        foreach ($keys as $key) {
            $data[$key] = null;
        }
        $list = M('Settings')->where(['settings_code' => ['in', $keys], 'settings_status' => 1])->field('settings_code,settings_value')->select();
        foreach ($list as $item) {
            if (in_array($item['settings_code'], $keys)) {
                $data[$item['settings_code']] = $item['settings_value'];
            }
        }
        return $data;
    }

    public function saveValue($key, $value)
    {
        return M('Settings')->where(['settings_code' => $key, 'settings_status' => 1])->save(['settings_value' => $value]);
    }

}
