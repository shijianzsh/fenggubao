<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 第三方平台 ->调用 本接口 转账
// +----------------------------------------------------------------------
namespace APP\Controller;
use think\Controller;
use V4\Model\Currency;
use V4\Model\CurrencyAction;
use V4\Model\AccountRecordModel;
use V4\Model\OrderModel;
use V4\Model\Platform3Model;

class AccountReceivedController extends Controller {
	
	
    /**
     * 第三方转入过来
     * Enter description here ...
     */
    public function recevied(){
    	ajax_return('暂停使用', 300);
        //互转开关
        $switch = intval(C('PARAMETER_CONFIG.THIRD_CURRENCY_TRANSFER'));
        if($switch != 1){
            ajax_return('抱歉，此功能暂停使用！', 300);
        }
        $data['openid'] = I('post.openid');   //本系统用户登录账户
        $data['type'] = I('post.type');  //1.现金； 2丰谷宝； 3商超券 4注册币
        $data['amount'] = I('post.amount');  //金额；
        $data['fromid'] = I('post.fromid');   //手机号
        $data['fromname'] = I('post.fromname');  //昵称
        
        $signstr = I('post.sign');
        
        //1.验证来源
        if(get_client_ip() != c('ZCGY_IP')){
            //ajax_return('不合法ip:'.get_client_ip(), 300);
        }
        
        //1.【验证签名】
        $oc = new Platform3Model();
        $oc->setValues($data);
        $sign = $oc->MakeSign();
        if($sign != $signstr){
            ajax_return('转账失败,签名错误', 300);
        }
        //2.验证参数
        $user = verify_source_recevied($data['openid'], $data['type'], $data['amount']);
        
        // 计算手续费
        $commission = 0;
        
        //3.判断类型
        if($data['type'] == 1){
        	ajax_return('抱歉，此功能暂停使用！', 300);
            $currency = Currency::Cash;
            $record_action1 = CurrencyAction::CashReceivedZCGY;
            //扣除手续费
            $commission = $data['amount'] * C('PARAMETER_CONFIG.THIRD_CURRENCY_TRANSFER_IN_BAI') / 100;
            $record_action2 = CurrencyAction::CashReceivedZCGYFee;
        }elseif($data['type'] == 2){
        	ajax_return('抱歉，此功能暂停使用！', 300);
        	$this->myApiPrint('丰谷宝第三方互转功能维护中，请稍候！');
            $currency = Currency::GoldCoin;
            $record_action1 = CurrencyAction::GoldCoinReceivedZCGY;
        }elseif($data['type'] == 3){
        	ajax_return('抱歉，此功能暂停使用！', 300);
        	$this->myApiPrint('商超券第三方互转功能维护中，请稍候！');
            $currency = Currency::ColorCoin;
            $record_action1 = CurrencyAction::ColorCoinReceivedZCGY;
        }elseif($data['type'] == 4){
            $currency = Currency::Enroll;
            $record_action1 = CurrencyAction::ENrollReceivedZCGY;
            $this->myApiPrint('注册币第三方互转功能维护中，请稍候！');
        }else{
        	ajax_return('类型错误', 300);
        }
        
        M()->startTrans();
        $res2 = true;
        //4.更新明细记录
        $arm = new AccountRecordModel();
        if($data['type'] == 1){//现金才有手续费
            $res2 = $arm->add($user['id'], $currency, $record_action2, -$commission, $arm->getRecordAttach(1, $data['fromname'],'','',$data['fromid'], $user['loginname'], $user['nickname']), '第三方转入手续费'); //转出
        }
        $res1 = $arm->add($user['id'], $currency, $record_action1, $data['amount'], $arm->getRecordAttach(1, $data['fromname'],'','',$data['fromid'], $user['loginname'], $user['nickname']), '第三方转入，手续费：'.$commission.'元'); //转出

        if($res1 !== false && $res2 !== false){
            M()->commit();
            ajax_return('转账成功', 400);
        }else{
            M()->rollback();
            ajax_return('转账失败', 300);
        }
    }
}


?>