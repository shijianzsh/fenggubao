<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/4
 * Time: 16:23
 */

namespace V4\Model;

/**
 * 基础模块
 * Class AccountModel
 * @package V4\Model
 */
class BaseModel
{

    /**
     * 计算分页数据
     * @param $totalRows
     * @param int $listRows
     * @return array
     */
    public function paginator($totalRows, $listRows = 20)
    {
        $page = [
        	'totalRows' => $totalRows,
            'totalPage' => ceil($totalRows / $listRows),
            'everyPage' => $listRows,
        ];
        return $page;
    }


    /**
     * 添加数据（自动忽略重复数据）
     * @param $table
     * @param $items
     * @return True or False
     */
    public static function InsertIgnoreData($table, $items)
    {
        $sql = "INSERT IGNORE INTO " . $table . "(";
        $separator = '';
        foreach (array_keys($items) as $key) {
            $sql .= $separator . $key;
            $separator = ',';
        }
        $sql .= ') values (';
        $separator = '';
        foreach ($items as $value) {
            $sql .= $separator . "'" . $value . "'";
            $separator = ',';
        }
        $sql .= ');';
        if(M()->execute($sql)){
            return M()->query('SELECT LAST_INSERT_ID() AS lastinserid;')[0]['lastinserid'];
        }
        return 0;
    }


}