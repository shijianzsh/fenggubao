<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/5
 * Time: 11:18
 */

namespace V4\Model;

/**
 * Class RewardModel
 * @package V4\Model
 * 奖励计算
 */
class RewardModel {

    private static $_instance;

    /**
     * 单例-获取new对象
     * Enter description here ...
     */
    public static function getInstance() {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * 根据订单金额，计算反省给商家和平台利润
     * Enter description here ...
     * @param  $amount 订单金额
     * @param  $reward preferential_way表的折扣信息
     * @param  $conditions
     */
    public function getProfitsByOrder($amount, $reward, $conditions = 100) {
        //利润
        $data['profits'] = $amount * $reward / $conditions;
        //商家获得返现
        $data['return_cash'] = $amount - $data['profits'];
        return $data;
    }
    /**
     * 特殊利润计算
     * Enter description here ...
     * @param unknown_type $amount
     * @param unknown_type $reward
     * @param unknown_type $conditions
     */
    public function getProfitsBySpecialOrder($amount, $bai) {
        //商家获得返现
        $data['return_cash'] = $amount * $bai * 0.01;
        //利润
        $data['profits'] = $amount - $data['return_cash'];
        return $data;
    }

    /**
     * 根据兑换利润，计算买家、商家应获得的积分
     * @param $money 兑换产生的毛利润金额
     */
    public function getPointsByMoney($money) {
        $Parameter = M('Parameter', 'g_');
        $data = array('seller' => 0, 'buyer' => 0, 'profits' => $money);
        if (empty($money)) {
            return $data;
        }
        if (!validateExtend($money, 'MONEY')) {
            return $data;
        }

        //拉取商家和买家应得积分的配置参数
        $parameter_info = $Parameter->where('id=1')->field('points_merchant,points_member')->find();

        $data['seller'] = $money * $parameter_info['points_merchant'];
        $data['buyer'] = $money * $parameter_info['points_member'];

        return $data;
    }
    
    /**
     * 根据兑换利润，计算买家获得的公让宝
     * @param $money 兑换产生的毛利润金额
     * @param $store 店铺信息
     * @return
     */
    public function getColorCoinByMoney($buyer = null, $seller = null, $money, $store) {
        $data = array('seller' => 0, 'buyer' => 0);
        if (empty($money)) {
            return $data;
        }
        if (!validateExtend($money, 'MONEY')) {
            return $data;
        }

        //拉取商家和买家应得积分的配置参数
        $param = M('g_parameter', null)->find(1);
        //应该赠送的商超券
        $data['buyer'] = $money * $param['points_member'];
        $data['seller'] = $money * $param['points_merchant'];
        
        //step3、获取商家赠送限额配置
        $merchant_give_points_total = $store['give_points_total'] + $data['buyer']; //（今日/本周对应商家已赠送总商超券）+（此次兑换该赠送给买家的商超券）
        $d_tag = 'points_merchant_max_day_' . $store['store_type'];
        $w_tag = 'points_merchant_max_week_' . $store['store_type'];
        $expr_point = C('PARAMETER_CONFIG.MERCHANT')[$d_tag] == 0 ? C('PARAMETER_CONFIG.MERCHANT')[$w_tag] : C('PARAMETER_CONFIG.MERCHANT')[$d_tag];  //商家的今日/本周可赠商超券上限
        //step4、根据限额配置计算最终赠送额度
        if ($merchant_give_points_total > $expr_point && $expr_point > 0) {
            $data['buyer'] = $expr_point - $store['give_points_total'];
            if($data['buyer'] < 0){
                $data['buyer'] = 0;
            }
            $data['seller'] = 0;
            
            return $data;
        }
        /**
        $am = new AccountModel();
        //step6、计算卖家积分和丰收点
        if ($seller) {
            $seller_balance = $am->getAllBalance($seller['id']);
            $seller['bonus'] = $seller_balance['account_bonus_balance'];
            if ($seller['bonus'] >= $param['member_bonus_max']) {
                $data['seller'] = 0;
            }
        }
        
        //step5、计算买家积分和丰收点
        if($buyer){
            $buyer_balance = $am->getAllBalance($buyer['id']);
            $buyer['bonus'] = $buyer_balance['account_bonus_balance'];
            if ($buyer['bonus'] >= $param['member_bonus_max']) {
                $data['buyer'] = 0;
            }
        }
        **/
        return $data;
    }

    /**
     * 计算买卖双方获得的最终积分和丰收点
     * Enter description here ...
     * @param $buyer 买家对象，null标识不计算
     * @param $seller 卖家对象，null标识不计算
     * @param $points 双方积分
     * @param $store 店铺对象
     * @return buyer_point=兑换后积分字段的值; buyer_get_point=本次兑换获得的积分；buyer_get_bonus=本次兑换获得的丰收点
     */
    public function getPointsAndBonus($buyer = null, $seller = null, $points, $store) {
        $am = new AccountModel();
        $data['buyer_point'] = 0;
        $data['buyer_get_point'] = 0;
        $data['seller_point'] = 0;
        $data['seller_get_point'] = 0;
        $data['buyer_get_bonus'] = 0;
        $data['seller_get_bonus'] = 0;
        $data['points_to_bonus'] = 500;
        
        $temp_buyer_point = $points['buyer'];
        $temp_seller_point = $points['seller'];
        $param = M('g_parameter', null)->find(1);
        $data['points_to_bonus'] = $param['points_to_bonus'];
        
        //step1、是否有积分的标识
        $haspoint_buyer = true;
        $haspoint_seller = true;
        if (!$seller) {
            $haspoint_seller = false;
        }else{
            $seller_balance = $am->getAllBalance($seller['id']);
            $seller['points'] = $seller_balance['account_points_balance'];
            $seller['bonus'] = $seller_balance['account_bonus_balance'];
        }
        
        if($buyer){
            $buyer_balance = $am->getAllBalance($buyer['id']);
            $buyer['points'] = $buyer_balance['account_points_balance'];
            $buyer['bonus'] = $buyer_balance['account_bonus_balance'];
        }

        if ($buyer) {
            //step3、获取商家本周已赠积分
            $merchant_give_points_total = $store['give_points_total'] + $points['buyer']; //（今日/本周对应商家已赠送总积分数额）+（此次兑换该赠送给买家的积分额）
            $d_tag = 'points_merchant_max_day_' . $store['store_type'];
            $w_tag = 'points_merchant_max_week_' . $store['store_type'];
            $expr_point = C('PARAMETER_CONFIG.MERCHANT')[$d_tag] == 0 ? C('PARAMETER_CONFIG.MERCHANT')[$w_tag] : C('PARAMETER_CONFIG.MERCHANT')[$d_tag];  //商家的今日/本周可赠积分上限
            //step4、根据商家已赠积分计算 买家积分
            if ($merchant_give_points_total > $expr_point && $expr_point > 0) {
                $temp_buyer_point = $expr_point - $store['give_points_total'];
                //卖家不再获得积分
                $haspoint_seller = false;
            }
        } else {
            $haspoint_buyer = false;
        }

        //step5、计算买家积分和丰收点
        if ($haspoint_buyer && $buyer['bonus'] < $param['member_bonus_max']) {
            $newpoint1 = $buyer['points'] + $temp_buyer_point;
            $newbonus1 = floor($newpoint1 / $param['points_to_bonus']);
            if ($newbonus1 > 0) {
                if (($buyer['bonus'] + $newbonus1) >= ($param['member_bonus_max'] + $param['member_bonus_float'])) {
                    $newbonus1 = $param['member_bonus_max'] + $param['member_bonus_float'] - $buyer['bonus'];
                    $data['buyer_get_bonus'] = $newbonus1;
                    $data['buyer_get_point'] = $newbonus1 * $param['points_to_bonus'] - $buyer['points'];
                    $data['buyer_point'] = 0;
                } else {
                    $data['buyer_get_bonus'] = $newbonus1;
                    $data['buyer_get_point'] = $temp_buyer_point;
                    $data['buyer_point'] = $newpoint1 - $newbonus1 * $param['points_to_bonus'];
                }
            } else {
                $data['buyer_get_point'] = $temp_buyer_point;
                $data['buyer_point'] = $newpoint1;
            }
        }

        //step6、计算卖家积分和丰收点
        if ($haspoint_seller && $seller['bonus'] < $param['member_bonus_max']) {
            $newpoint1 = $seller['points'] + $temp_seller_point;
            $newbonus1 = floor($newpoint1 / $param['points_to_bonus']);
            if ($newbonus1 > 0) {
                if (($seller['bonus'] + $newbonus1) >= ($param['member_bonus_max'] + $param['member_bonus_float'])) {
                    $newbonus1 = $param['member_bonus_max'] + $param['member_bonus_float'] - $seller['bonus'];
                    $data['seller_get_bonus'] = $newbonus1;
                    $data['seller_get_point'] = $newbonus1 * $param['points_to_bonus'] - $seller['points'];
                    $data['seller_point'] = 0;
                } else {
                    $data['seller_get_bonus'] = $newbonus1;
                    $data['seller_get_point'] = $temp_seller_point;
                    $data['seller_point'] = $newpoint1 - $newbonus1 * $param['points_to_bonus'];
                }
            } else {
                $data['seller_get_point'] = $temp_seller_point;
                $data['seller_point'] = $newpoint1;
            }
        }
        return $data;
    }
    
    
    /**
     * 分发积分和丰收点 
     * Enter description here ...
     * @param $buyerId
     * @param $sellerId
     * @param $rewardData
     * @return boolean;
     */
    public function assignPointsBonus($buyer, $seller, $rewardData){
        if(empty($buyer)){
            $buyer['id']=0;
            $buyer['nickname']='';
            $buyer['pic']='';
        }
        if(empty($seller)){
            $seller['id']=0;
            $seller['nickname']='';
            $seller['pic']='';
        }
        $buyerId = intval($buyer['id']);
        $sellerId = intval($seller['id']);
        $tag1=true;$tag2=true;$tag3=true;$tag4=true;$tag5=true;$tag6=true;
        $arm = new AccountRecordModel();
        if($buyerId > 0){
            if($rewardData['buyer_get_point'] > 0){
                $tag1 = $arm->add($buyerId, Currency::Points, CurrencyAction::PointsFormConsume, $rewardData['buyer_get_point'], $arm->getRecordAttach($seller['id'], $seller['nickname'], $seller['img']), '买单获得积分');
            }
            if($rewardData['buyer_get_bonus'] > 0){
                $tag2 = $arm->add($buyerId, Currency::Bonus, CurrencyAction::BonusFormPoints, $rewardData['buyer_get_bonus'], $arm->getRecordAttach(1, '系统'), '积分转丰收点');
                $tag3 = $arm->add($buyerId, Currency::Points, CurrencyAction::PointsToBonus, -$rewardData['points_to_bonus']*$rewardData['buyer_get_bonus'], $arm->getRecordAttach(1, '系统'), '积分自动转丰收点', $rewardData['points_to_bonus']);
            }
            //更新店铺遗赠积分
            if($rewardData['buyer_get_point'] > 0){
                $sp['give_points_total'] = array('exp', 'give_points_total+'.$rewardData['buyer_get_point']);
                M('store')->where('uid='.$sellerId)->save($sp);
            }
        }
        if($sellerId > 0){
            if($rewardData['seller_get_point'] > 0){
                $tag4 = $arm->add($sellerId, Currency::Points, CurrencyAction::PointsFormConsume, $rewardData['seller_get_point'], $arm->getRecordAttach($buyer['id'], $buyer['nickname'], $buyer['img']), '买单获得积分');
            }
            if($rewardData['seller_get_bonus'] > 0){
                $tag5 = $arm->add($sellerId, Currency::Bonus, CurrencyAction::BonusFormPoints, $rewardData['seller_get_bonus'], $arm->getRecordAttach(1, '系统'), '积分转丰收点');
                $tag6 = $arm->add($sellerId, Currency::Points, CurrencyAction::PointsToBonus, -$rewardData['points_to_bonus']*$rewardData['seller_get_bonus'], $arm->getRecordAttach(1, '系统'), '积分自动转丰收点', $rewardData['points_to_bonus']);
            }
        }
        if($tag1 !== false && $tag2 !== false && $tag3 !== false && $tag4 !== false && $tag5 !== false && $tag6 !== false){
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * 奖励公让宝
     * Enter description here ...
     * @param $buyer
     * @param $seller
     * @param $rewardData
     */
    public function assignColorCoin($buyer, $seller, $rewardData, $store, $order_no){
    	$params = M('g_parameter', null)->find();
        if(empty($buyer)){
            $buyer['id']=0;
            $buyer['nickname']='';
            $buyer['pic']='';
        }
        if(empty($seller)){
            $seller['id']=0;
            $seller['nickname']='';
            $seller['pic']='';
        }
        $buyerId = intval($buyer['id']);
        $sellerId = intval($seller['id']);
        $tag1=true;$tag2=true;
        $arm = new AccountRecordModel();
        if($buyerId > 0){
            if($rewardData['buyer'] > 0){
                $tag1 = $arm->add($buyer['id'], Currency::GoldCoin, CurrencyAction::GoldCoinCashConsumeBack, $rewardData['buyer'], $arm->getRecordAttach($seller['id'], $store['store_name'], $store['store_img'], $order_no), '消费送公让宝');
                $arm->personalTax($buyer['id'], $rewardData['buyer'], $params['fee_person_profits'], Currency::GoldCoin, CurrencyAction::GoldCoinTax, $tag1, '消费返公让宝扣税');
                $arm->personalTax($buyer['id'], $rewardData['buyer'], $params['fee_system_manage'], Currency::GoldCoin, CurrencyAction::GoldCoinManagementFee, $tag1, '消费返公让宝扣平台管理费');
                
                $sp['give_points_total'] = array('exp', 'give_points_total+'.$rewardData['buyer']);
                M('store')->where('uid='.$sellerId)->save($sp);
            }
        }
        if($sellerId > 0){
            if($rewardData['seller'] > 0){
                $tag2 = $arm->add($seller['id'], Currency::GoldCoin, CurrencyAction::GoldCoinCashConsumeBack, $rewardData['seller'], $arm->getRecordAttach(1, '系统', '', $order_no), '消费送公让宝');
                $arm->personalTax($seller['id'], $rewardData['seller'], $params['fee_person_profits'], Currency::GoldCoin, CurrencyAction::GoldCoinTax, $tag2, '消费返公让宝扣税');
                $arm->personalTax($seller['id'], $rewardData['seller'], $params['fee_system_manage'], Currency::GoldCoin, CurrencyAction::GoldCoinManagementFee, $tag2, '消费返公让宝扣平台管理费');
                
            }
        }
        if($tag1 !== false && $tag2){
            return true;
        }else{
            return false;
        }
    }
    
    
    /**
     * 根据订单计算平台利润
     * Enter description here ...
     * @param $profits
     * @param $order_number
     */
    public function assignPlatformProfits($profits, $order_number){
       //平台毛利润
       $data_profits_bonus['profits'] = $profits;
       $data_profits_bonus['order_number'] = $order_number;
       $data_profits_bonus['date_created'] = time();
       $r7 = M('profits_bonus')->add($data_profits_bonus);
       return $r7;
    }
}
