<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/4
 * Time: 16:18
 */

namespace APP\Controller;

use Think\Controller;
use V4\Model\ProcedureModel;

class FixedController extends Controller {

    public function __construct() {
        parent::__construct();
        header('Content-Type:text/html; charset=utf-8');
    }

    public function index() {
        
    }

    public function maker() {
        set_time_limit(0);
        
        $this->FinanceStatisticsDay_maker(20160901, 20160930);
        $this->FinanceStatisticsDay_maker(20161001, 20161031);
        $this->FinanceStatisticsDay_maker(20161101, 20161130);
        $this->FinanceStatisticsDay_maker(20161201, 20161231);
        $this->FinanceStatisticsDay_maker(20170101, 20170131);
        $this->FinanceStatisticsDay_maker(20170201, 20170228);
        $this->FinanceStatisticsDay_maker(20170301, 20170331);
        $this->FinanceStatisticsDay_maker(20170401, 20170430);
        $this->FinanceStatisticsDay_maker(20170501, 20170531);
        $this->FinanceStatisticsDay_maker(20170601, 20170630);
        $this->FinanceStatisticsDay_maker(20170701, 20170731);
        $this->FinanceStatisticsDay_maker(20170801, 20170820);

        $procedureM = new ProcedureModel();
        if (!$procedureM->execute('FinanceStatistics', 201609, '@error')) {
            die('执行 ' . 201609 . ' 失败');
        }
        if (!$procedureM->execute('FinanceStatistics', 201610, '@error')) {
            die('执行 ' . 201610 . ' 失败');
        }
        if (!$procedureM->execute('FinanceStatistics', 201611, '@error')) {
            die('执行 ' . 201611 . ' 失败');
        }
        if (!$procedureM->execute('FinanceStatistics', 201612, '@error')) {
            die('执行 ' . 201612 . ' 失败');
        }
        if (!$procedureM->execute('FinanceStatistics', 2016, '@error')) {
            die('执行 ' . 2016 . ' 失败');
        }
        if (!$procedureM->execute('FinanceStatistics', 201701, '@error')) {
            die('执行 ' . 201701 . ' 失败');
        }
        if (!$procedureM->execute('FinanceStatistics', 201702, '@error')) {
            die('执行 ' . 201702 . ' 失败');
        }
        if (!$procedureM->execute('FinanceStatistics', 201703, '@error')) {
            die('执行 ' . 201703 . ' 失败');
        }
        if (!$procedureM->execute('FinanceStatistics', 201704, '@error')) {
            die('执行 ' . 201704 . ' 失败');
        }
        if (!$procedureM->execute('FinanceStatistics', 201705, '@error')) {
            die('执行 ' . 201705 . ' 失败');
        }
        if (!$procedureM->execute('FinanceStatistics', 201706, '@error')) {
            die('执行 ' . 201706 . ' 失败');
        }
        if (!$procedureM->execute('FinanceStatistics', 201707, '@error')) {
            die('执行 ' . 201707 . ' 失败');
        }
        if (!$procedureM->execute('FinanceStatistics', 201708, '@error')) {
            die('执行 ' . 201708 . ' 失败');
        }
        if (!$procedureM->execute('FinanceStatistics', 2017, '@error')) {
            die('执行 ' . 2017 . ' 失败');
        }
        if (!$procedureM->execute('FinanceStatistics', '0', '@error')) {
            die('执行 ' . 0 . ' 失败');
        }
        die('complete');
        set_time_limit(30);
    }

    private function FinanceStatisticsDay_maker($start_tag, $end_tag) {
        $procedureM = new ProcedureModel();
        for ($index = $start_tag; $index <= $end_tag; $index++) {

            if (!$procedureM->execute('FinanceStatisticsDay_recharge', $index, '@error')) {
                die('执行 recharge ' . $index . ' 失败');
            }

            if (!$procedureM->execute('FinanceStatisticsDay_balance', $index, '@error')) {
                die('执行 balance ' . $index . ' 失败');
            }

            if (!$procedureM->execute('FinanceStatisticsDay_expenditure', $index, '@error')) {
                die('执行 expenditure ' . $index . ' 失败');
            }

            if (!$procedureM->execute('FinanceStatisticsDay_profits', $index, '@error')) {
                die('执行 profits ' . $index . ' 失败');
            }

            if (!$procedureM->execute('FinanceStatisticsDay_maker', $index, '@error')) {
                die('执行 maker ' . $index . ' 失败');
            }

            if (!$procedureM->execute('FinanceStatisticsDay_withdraw', $index, '@error')) {
                die('执行 withdraw ' . $index . ' 失败');
            }
        }
    }

}
