<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/4
 * Time: 16:18
 */

namespace V4\Controller;

use Think\Controller;
use V4\Model\ProcedureModel;

class SqlController extends Controller {

    public function __construct() {
        parent::__construct();
        header('Content-Type:text/html; charset=utf-8');
    }

    public function index() {
        
    }

    public function out() {
        set_time_limit(0);
        $procedureM = new ProcedureModel();
        
        $this->AccountStatisticsProfitDay(20170810, 20170820);
        $procedureM->outSql('AccountStatisticsProfit', '201708', '@error');
        $procedureM->outSql('AccountStatisticsProfit', '2017', '@error');
        $procedureM->outSql('AccountStatisticsProfit', '0', '@error');
        

        echo 'TRUNCATE TABLE zc_finance; # 清空平台统计表' . '<br />';

        $this->FinanceStatisticsDay(20160901, 20160930);
        $this->FinanceStatisticsDay(20161001, 20161031);
        $this->FinanceStatisticsDay(20161101, 20161130);
        $this->FinanceStatisticsDay(20161201, 20161231);
        $this->FinanceStatisticsDay(20170101, 20170131);
        $this->FinanceStatisticsDay(20170201, 20170228);
        $this->FinanceStatisticsDay(20170301, 20170331);
        $this->FinanceStatisticsDay(20170401, 20170430);
        $this->FinanceStatisticsDay(20170501, 20170531);
        $this->FinanceStatisticsDay(20170601, 20170630);
        $this->FinanceStatisticsDay(20170701, 20170731);
        $this->FinanceStatisticsDay(20170801, 20170820);

        
        $procedureM->outSql('FinanceStatistics', '201609', '@error');
        $procedureM->outSql('FinanceStatistics', '201610', '@error');
        $procedureM->outSql('FinanceStatistics', '201611', '@error');
        $procedureM->outSql('FinanceStatistics', '201612', '@error');
        $procedureM->outSql('FinanceStatistics', '2016', '@error');
        $procedureM->outSql('FinanceStatistics', '201701', '@error');
        $procedureM->outSql('FinanceStatistics', '201702', '@error');
        $procedureM->outSql('FinanceStatistics', '201703', '@error');
        $procedureM->outSql('FinanceStatistics', '201704', '@error');
        $procedureM->outSql('FinanceStatistics', '201705', '@error');
        $procedureM->outSql('FinanceStatistics', '201706', '@error');
        $procedureM->outSql('FinanceStatistics', '201707', '@error');
        $procedureM->outSql('FinanceStatistics', '201708', '@error');
        $procedureM->outSql('FinanceStatistics', '2017', '@error');
        $procedureM->outSql('FinanceStatistics', '0', '@error');
        set_time_limit(30);
    }

    private function AccountStatisticsProfitDay($start_tag, $end_tag) {
        $procedureM = new ProcedureModel();
        for ($index = $start_tag; $index <= $end_tag; $index++) {
            $procedureM->outSql('TimerTask_profitday', $index, '@error');
        }
    }

    private function FinanceStatisticsDay($start_tag, $end_tag) {
        $procedureM = new ProcedureModel();
        for ($index = $start_tag; $index <= $end_tag; $index++) {
            $procedureM->outSql('FinanceStatisticsDay_recharge', $index, '@error');
            $procedureM->outSql('FinanceStatisticsDay_balance', $index, '@error');
            $procedureM->outSql('FinanceStatisticsDay_expenditure', $index, '@error');
            $procedureM->outSql('FinanceStatisticsDay_profits', $index, '@error');
            $procedureM->outSql('FinanceStatisticsDay_maker', $index, '@error');
            $procedureM->outSql('FinanceStatisticsDay_withdraw', $index, '@error');
        }
    }

}
