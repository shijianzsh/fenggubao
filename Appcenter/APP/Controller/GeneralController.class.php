<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 普通接口，只集成controller
// +----------------------------------------------------------------------
namespace APP\Controller;

use think\Controller;
use Common\Controller\BaseController;

class GeneralController extends BaseController
{

    private $sms_phone_gljt = '13547674999,15184488516'; //接收[管理津贴分发完毕]通知的用户手机号,多个用半角逗号隔开

    /**
     * 发送管理津贴短信
     * Enter description here ...
     */
    public function sendGLJTmsg()
    {
        return true;
        //查询今日管理津贴
        $gljt = M('profits')->where('gljt_money > 0 and date_created > ' . strtotime(date('Y-m-d')))->find();
        if ($gljt) {
            $this->sms($this->sms_phone_gljt, '', "今日管理津贴已发放完毕,发放总额为" . $gljt['gljt_money'] . "元", 'event');
            $this->myApiPrint('success', 400);
        } else {
            $this->myApiPrint('没有数据，成功退出', 400);
        }
    }

    /**
     * 用户数据异常预警短信
     */
    public function sendMonitoringMsg()
    {
        $count = I('get.count');
        echo $count;
//        $this->sms($this->sms_phone_gljt, '', "警告:" . $count . "个用户余额出现异常", 'event');
    }
}


?>