<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | app转出给第三方平台
// +----------------------------------------------------------------------
namespace APP\Controller;
use Common\Controller\ApiController;
use V4\Model\Currency;
use V4\Model\CurrencyAction;
use V4\Model\AccountRecordModel;
use V4\Model\OrderModel;
use V4\Model\Platform3Model;

class AccountTransferController extends ApiController {
	
	
    /**
     * APP资金转出
     * Enter description here ...
     */
    public function rollout(){
        //互转开关
        $switch = intval(C('PARAMETER_CONFIG.THIRD_CURRENCY_TRANSFER'));
        if($switch != 1){
            ajax_return('抱歉，此功能暂停使用！', 300);
        }
        
        $source_tel = I('post.sid');   //本系统用户登录账户
        $depart_tel = I('post.did');   //第三方用户唯一代码
        $type = I('post.type');  //1.现金； 2丰谷宝； 3商超券 ;4注册币；5乐享币
        $amount = I('post.amount');  //金额；
        
        //1.验证参数
        $user = verify_source_transfer($source_tel, $type, $amount, $this->app_common_data['uid']);
        
        // 计算手续费
        $commission = 0;
        //2.判断类型
        if($type == 1){
        	$this->myApiPrint('抱歉，此功能暂停使用！');
            $currency = Currency::Cash;
            $record_action1 = CurrencyAction::CashTransferZCGY;
            $commission = $amount * C('PARAMETER_CONFIG.THIRD_CURRENCY_TRANSFER_OUT_BAI') / 100;
            $record_action2 = CurrencyAction::CashTransferZCGYFee;
        }elseif($type == 2){
        	$this->myApiPrint('抱歉，此功能暂停使用！');
            $currency = Currency::GoldCoin;
            $record_action1 = CurrencyAction::GoldCoinTransferZCGY;
        }elseif($type == 3){
        	$this->myApiPrint('抱歉，此功能暂停使用！');
            $currency = Currency::ColorCoin;
            $record_action1 = CurrencyAction::ColorCoinTransferZCGY;
        }elseif($type == 4){
            $currency = Currency::Enroll;
            $record_action1 = CurrencyAction::ENrollTransferZCGY;
        }elseif($type == 5){
        	$this->myApiPrint('抱歉，此功能暂停使用！');
            $currency = Currency::Enjoy;
            $record_action1 = CurrencyAction::EnjoyTransferZCGY;
        }
        
        //3.验证余额
        $om = new OrderModel();
        if(!$om->compareBalance($user['id'], $currency, ($amount+$commission))){
            $this->myApiPrint('余额不足，无法转账', 300);
        }
        M()->startTrans();
        $res2 = true;
        
        //1.【设置参数】
        $oc = new Platform3Model();
        $data['amount'] = $amount;
        $data['type'] = $type;
        $data['openid'] = $depart_tel;
        $data['fromid'] = $user['username'];
        $data['fromname'] = $user['nickname'];
        $data['platform'] = 1;
        
        //2.【签名】
        $oc->setValues($data);
        $oc->SetSign(); 
        //3.【请求接口】
        $res = postdata(c('ZCGYURL'), $oc->getUrlParams());
        if(!empty($res)){
            //4.更新明细记录
            $arm = new AccountRecordModel();
            if($commission > 0){//现金才有手续费
                $res2 = $arm->add($user['id'], $currency, $record_action2, -$commission, $arm->getRecordAttach($user['id'], $user['nickname'], $user['img'],'',$depart_tel, $user['loginname'], $user['nickname']), '转至第三方手续费'); //转出
            }
            $res1 = $arm->add($user['id'], $currency, $record_action1, -$amount, $arm->getRecordAttach($user['id'], $user['nickname'], $user['img'],'',$depart_tel, $user['loginname'], $user['nickname']), '转至第三方，手续费：'.$commission.'元'); //转出
            if($res['code'] == 400 && $res1 !== false && $res2 !== false){
                M()->commit();
                $this->myApiPrint('转账成功', 400);
            }else{
                M()->rollback();
                $this->myApiPrint($res['msg'], 300);
            }
        }else{
            M()->rollback();
            $this->myApiPrint('转账失败', 300);
        }
    }
    
    
    /**
     * 转出说明
     * Enter description here ...
     */
    public function getrule(){
        $type = I('post.type');  //1.现金； 2公让宝； 3商超券
        //if($type == 1){
            $rule =  C('PARAMETER_CONFIG.THIRD_CURRENCY_TRANSFER_DESCRIPTION');
            $rule = preg_replace('/{%third_currency_min%}/', C('PARAMETER_CONFIG.THIRD_CURRENCY_TRANSFER_MIN'), $rule);
            $rule = preg_replace('/{%third_currency_bei%}/', C('PARAMETER_CONFIG.THIRD_CURRENCY_TRANSFER_BEI'), $rule);
            $rule = preg_replace('/{%third_currency_fee_out%}/', C('PARAMETER_CONFIG.THIRD_CURRENCY_TRANSFER_OUT_BAI'), $rule);
            $rule = preg_replace('/{%third_currency_fee_in%}/', C('PARAMETER_CONFIG.THIRD_CURRENCY_TRANSFER_IN_BAI'), $rule);
            $this->myApiPrint('获取成功', 400, $rule);
        //}else{
          //  $this->myApiPrint('获取成功', 400, '');
       // }
    }
}


?>