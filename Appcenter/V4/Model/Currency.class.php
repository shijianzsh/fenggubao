<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/4
 * Time: 17:07
 */

namespace V4\Model;


/**
 * 货币类型
 * Class Currency
 * @package V4\Model
 */
class Currency
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
     * 提货券
     */
    const ColorCoin = 'colorcoin';

    /**
     * 矿池
     */
    const Points = 'points';

    /**
     * 锁定通证
     */
    const Bonus = 'bonus';
    
    /**
     * 兑换券
     */
    const Enroll = 'enroll';
    
    /**
     * GRC购物积分
     */
    const Credits = 'credits';
    
    /**
     * 报单币
     */
    const Supply = 'supply';

    /**
     * 澳洲SKN股数
     */
    const Enjoy = 'enjoy';
    
    /**
     * 复投币（停用）
     */
    const Redelivery = 'redelivery';

    /**
     * 获取货币类型名称
     * @param Currency $currency
     * @return string
     */
    public static function getLabel($currency)
    {
        switch ($currency) {
            case self::Cash:
                return '现金积分';
            case self::GoldCoin:
                return '丰谷宝';
            case self::ColorCoin:
                return '提货券';
            case self::Points:
                return '矿池';
            case self::Bonus:
                return '锁定通证';
            case self::ENroll:
            	return '兑换券';
            case self::Credits:
            	return 'GRC购物积分';
            case self::Supply:
            	return '报单币';
            case self::Enjoy:
            	return '澳洲SKN股数';
            case self::Redelivery:
            	return '复投币';
        }
        return '未知';

    }
}