<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/4
 * Time: 17:07
 */

namespace V4\Model;

/**
 * 货币标签
 * Class Currency
 * @package V4\Model
 */
class Tag {

    /**
     * 获取标签
     * @param string $type ： total, year, last_year month, last_month, day, yesterday
     * @param int $time
     * @return int
     */
    public static function get($type = 'total', $time = 0) {
        if ($type == 'total')
            return 0;
        if ($time == 0)
            $time = time();
        switch ($type) {
            case 'year':
                return date('Y', $time);
            case 'last_year':
                return date('Y', $time) - 1;
            case 'month':
                return date('Ym', $time);
            case 'last_month':
                return date('Ym', $time) - 1;
            case 'day':
                return date('Ymd', $time);
            case 'yesterday':
                return date('Ymd', $time - 3600 * 24);
        }
        return 0;
    }

    /**
     * 获取当年或指定年标签
     * @param int $time
     * @return int
     */
    public static function getYear($time = 0) {
        return self::get('year', $time);
    }

    /**
     * 获取去年或指定年前一年标签
     * @param int $time
     * @return int
     */
    public static function getLastYear($time = 0) {
        return self::get('last_year', $time);
    }

    /**
     * 获取当年或指定年标签
     * @param int $time
     * @return int
     */
    public static function getMonth($time = 0) {
        return self::get('month', $time);
    }

    /**
     * 获取去年或指定年前一年标签
     * @param int $time
     * @return int
     */
    public static function getLastMonth($time = 0) {
        return self::get('last_month', $time);
    }

    /**
     * 获取当天或指定日期标签
     * @param int $time
     * @return int
     */
    public static function getDay($time = 0) {
        return self::get('day', $time);
    }

    /**
     * 获取昨天或指定日期前一天标签
     * @param int $time
     * @return int
     */
    public static function getYesterday($time = 0) {
        return self::get('yesterday', $time);
    }

}
