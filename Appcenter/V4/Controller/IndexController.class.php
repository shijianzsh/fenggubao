<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/4
 * Time: 16:18
 */

namespace V4\Controller;

use V4\Model\AccountModel;
use V4\Model\AccountRecordModel;
use V4\Model\Currency;
use Think\Controller;
use V4\Model\CurrencyAction;
use V4\Model\RewardModel;
use V4\Model\Tag;

class IndexController extends Controller
{
    public function index()
    {
//    	$buyer = M('member')->find(9536);
//    	$seller = M('member')->find(40063);
//
//    	$store = M('store')->where('uid=40063')->find();
//    	$discount = M('preferential_way')->where('store_id='.$store['id'])->find();
//
//    	$amount = 1000; //订单金额
//    	$profits = D('Reward')->getProfitsByOrder($amount, $discount['reward']);  //计算利润和商家返现
//
//    	$points = D('Reward')->getPointsByMoney($profits['profits']);  //计算买卖双方积分
//
//    	$res = D('Reward')->getPointsAndBonus($buyer, $seller, $points, $store); //计算买卖双方最终获得的积分和丰收点
//		fuck($res, $array2);

        $AccountRecordM = new AccountRecordModel();
        $AccountRecordM->add(1, Currency::Cash, CurrencyAction::CashRecharge, 150, '', '现金充值');
        $AccountRecordM->add(1, Currency::GoldCoin, CurrencyAction::GoldCoinBonus, 160, '', '分红公让宝');
        $AccountRecordM->add(1, Currency::GoldCoin, CurrencyAction::GoldCoinConsume, -80, '', '公让宝买单');
        $AccountRecordM->add(1, Currency::ColorCoin, CurrencyAction::ColorCoinBonus, 70, '', '分红商超券');
        $AccountRecordM->add(1, Currency::Points, CurrencyAction::PointsFormConsume, 140, '', '买单返积分');
        $AccountRecordM->add(1, Currency::Bonus, CurrencyAction::BonusFormPoints, 3, '', '积分转分红股');

        $AccountM = new AccountModel();

        echo 'getCashBalance : ' . $AccountM->getCashBalance(1) . '<br />';
        echo 'getGoldCoinBalance : ' . $AccountM->getGoldCoinBalance(1) . '<br />';
        echo 'getColorCoinBalance : ' . $AccountM->getColorCoinBalance(1) . '<br />';
        echo 'getBonusBalance : ' . $AccountM->getBonusBalance(1) . '<br />';
        echo 'getPointsBalance : ' . $AccountM->getPointsBalance(1) . '<br />';

        print_r($AccountM->getItemByUserId(1, '*', Tag::getDay()));
        echo '<br />';
        print_r($AccountRecordM->getPageList(Currency::Cash, 1));
        echo '<br />';
        print_r($AccountRecordM->getPageList(Currency::GoldCoin, 1));
        echo '<br />';
        print_r($AccountRecordM->getPageList(Currency::ColorCoin, 1));
        echo '<br />';
        print_r($AccountRecordM->getPageList(Currency::Points, 1));
        echo '<br />';
        print_r($AccountRecordM->getPageList(Currency::Bonus, 1));

    }
}