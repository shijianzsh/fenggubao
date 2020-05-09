<?php

namespace V4\Controller;
use Think\Controller;
/**
 * 监控
 * Class MonitoringController
 * @package V4\Controller
 */
class MonitoringController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        header('Content-Type:text/html; charset=utf-8');
    }


    /**
     * 账户异常预警系统
     */
    public function index()
    {
        $count = M('account')->where('account_tag=0')->where('account_bonus_balance < 0 OR account_cash_balance < 0 OR account_colorcoin_balance < 0 OR account_goldcoin_balance < 0 OR account_credits_balance < 0 OR account_enjoy_balance < 0 OR account_enroll_balance < 0 OR account_points_balance < 0 OR account_supply_balance < 0 OR account_redelivery_balance < 0')->count();
        if ($count > 0) {
            $url = U('/General/sendMonitoringMsg', ['count'=>$count], true, true);
            echo file_get_contents($url);
        }
    }

}
