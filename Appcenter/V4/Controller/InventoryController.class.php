<?php


namespace V4\Controller;

use \Common\Controller\PushController;

class InventoryController extends PushController
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

        $minInventory = 5;

        $stores = $Model->query("
        SELECT
            COUNT(p.`id`) AS c,
            s.`id`,
            s.`uid`,
            l.`registration_id`
        FROM
            `zc_product` AS p
        LEFT JOIN `zc_store` AS s ON p.`storeid` = s.`id`
        LEFT JOIN `zc_login` AS l ON s.`uid` = l.`uid`
        LEFT JOIN `zc_member` AS m ON s.`uid` = m.`id`
        WHERE p.`totalnum` <= $minInventory
        AND s.`id` IS NOT NULL
        AND l.`registration_id` IS NOT NULL
        AND p.`status` = 0
        AND p.manage_status = 1
        AND m.is_lock = 0
        AND s.status = 0
        GROUP BY s.`id`
        ");

        $ids = [];
        foreach ($stores as $store) {
            $ids[] = $store['registration_id'];
        }

        if (count($ids) > 0) {

            //设置参数
            $ids['all'] = $ids;
            $content = "您有产品库存数量小于等于$minInventory, 请及时补充库存。";
            //附加参数
            $extraparams['target'] = 'common_alert';
            $extraparams['msg'] = $content;

            //推送
           echo $this->push($ids, $content, $extraparams);
        }

        $end = time();
        echo '<br />';
        echo '耗时：' . ($end - $start) . 's';
        set_time_limit(30);
    }


}
