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
use V4\Model\DebugLogModel;
use V4\Model\RewardModel;
use V4\Model\Tag;


class TestController extends Controller
{

    public function index()
    {
        DebugLogModel::instance()->add(['name' => 'x', 'id' => 1]);
    }

}
