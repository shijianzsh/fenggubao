<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/4
 * Time: 17:07
 */

namespace V4\Model;


/**
 * 支付方式
 * Class Currency
 * @package V4\Model
 */
class PaymentMethod
{
    /**
     * 现金积分
     */
    const Cash = 'cash';

    /**
     * 公让宝
     */
    const GoldCoin = 'goldcoin';

    /**
     * 商超券（彩分）
     */
    const ColorCoin = 'colorcoin';

    /**
     * 积分（丰收积分）
     */
    const Wechat = 'wechat';

    /**
     * 分红股（丰收点）
     */
    const Alipay = 'alipay';
    
    /**
     * 注册币
     */
    const Enroll = 'enroll';
    
    /**
     * 商城积分
     */
    const Credits = 'credits';

    /**
     * 特供券
     */
    const Supply = 'supply';
    
    /**
     * 银行卡
     */
    const Bank = 'bank';

    
    /**
     * 获取货币类型名称
     * @param Currency $currency
     * @return string
     */
    public static function getLabel($currency)
    {
        switch ($currency) {
            case self::Cash:
                return '现金积分支付';
            case self::GoldCoin:
                return '公让宝支付';
            case self::ColorCoin:
                return '商超券支付';
            case self::Wechat:
                return '微信支付';
            case self::Alipay:
                return '支付宝支付';
            case self::Enroll:
                return '注册币支付';
            case self::Credits:
                return '商城积分支付';
            case self::Supply:
                return '特供券支付';
            case self::Bank:
            	return '银行卡支付';
        }
        return '未知';

    }
}