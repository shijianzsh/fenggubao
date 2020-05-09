<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/4
 * Time: 17:52
 */

namespace V4\Model;

/**
 * 货币操作类型，与旧版货币明细类型BONUS_TYPE值保持一致
 * Class CurrencyAction
 * @package V4\Model
 *
 *
 * @internal 第三方：指理财平台
 */
class CurrencyAction
{
    /*     * ******************** 公让宝 ************************ */

    /**
     * 后台充值
     */
    const GoldCoinByChongzhi = 101;

    /**
     * 订单退款
     */
    const GoldCoinByDingdanTuikuan = 102;

    /**
     * 消费赠送
     */
    const GoldCoinByConsumeGive = 103;

    /**
     * 销售奖
     */
    const GoldCoinConsume = 104;

    /**
     * 管理津贴
     */
    const GoldCoinSubsidy = 105;

    /**
     * 申请区域合伙人赠送
     */
    const GoldCoinServiceGive = 106;

    /**
     * 加权分红
     */
    const GoldCoinBonus = 107;

    /**
     * 关爱奖
     */
    const GoldCoinCare = 108;

    /**
     * 好友转赠
     */
    const GoldCoinReceived = 109;

    /**
     * 区域合伙人奖
     */
    const GoldCoinCountyServer = 110;

    /**
     * 最高级合伙人奖(原:省级合伙人奖)
     */
    const GoldCoinProvinceServer = 111;

    /**
     * 最高级合伙人见点奖(原:省级合伙人见点奖)
     */
    const GoldCoinRecommendServer = 112;

    /**
     * 锁定通证丰收
     */
    const GoldCoinReleaseLock = 113;

    /**
     * 转让到公共市场(退款)
     */
    const GoldCoinTradeRefund = 114;
    
    /**
     * 丰收
     */
    const GoldCoinMining = 115;
    
    /**
     * 商家收款
     */
    const GoldCoinMerchantReceived = 116;
    
    /**
     * 感恩奖
     */
    const GoldCoinThankful = 117;
    
    /**
     * 谷聚金 - 管理招商补贴
     */
    const GoldCoinGjjManageSubsidy = 130;
    
    /**
     * 谷聚金 - 重复消费奖
     */
    const GoldCoinGjjRepeatConsume = 131;
    
    /**
     * 农场释放
     */
    const GoldCoinFarmRelease = 132;

    /**
     * 商城消费支付
     */
    const GoldCoinByXiaofei = 150;

    /**
     * 后台扣款
     */
    const GoldCoinByHOutaiKoukuan = 151;

    /**
     * 转赠给好友
     */
    const GoldCoinTransfer = 152;

    /**
     * 转让到公共市场
     */
    const GoldCoinTransferToGRB = 153;
    
    /**
     * 从公共市场转入
     */
    const GoldCoinReceievdToGRB = 154;
    
    /**
     * 从公共市场转入奖励
     */
    const GoldCoinReceievdToGRBReward = 155;
    
    /**
     * 转让至Quant Broker(华佗商城)
     */
    const GoldCoinTransferToHT = 160;
    
    /**
     * 转让至Quant Broker(华佗商城)手续费
     */
    const GoldCoinTransferToHTFee = 161;

    /**
     * 锁仓
     */
    const GoldCoinToBonus = 162;
    
    /***************** 公让宝(锁定通证) ***********************/
    /**
     * 后台充值
     */
    const BonusByChongzhi = 201;
    
    /**
     * 订单退款
     */
    const BonusByDingdanTuikuan = 202;
    
    /**
     * 消费赠送(代理专区赠送)
     */
    const BonusByConsumeGive = 203;
    
     /**
     * 销售奖
     */
    const BonusConsume = 204;

    /**
     * 管理津贴(业绩结算)
     */
    const BonusSubsidy = 205;

    /**
     * 申请区域合伙人赠送
     */
    const BonusServiceGive = 206;

    /**
     * 加权分红
     */
    const BonusBonus = 207;

    /**
     * 关爱奖
     */
    const BonusCare = 208;

    /**
     * 好友转赠
     */
    const BonusReceived = 209;

    /**
     * 区域合伙人奖
     */
    const BonusCountyServer = 210;

    /**
     * 最高级合伙人奖(原:省级合伙人奖)
     */
    const BonusProvinceServer = 211;

    /**
     * 最高级合伙人见点奖(原:省级合伙人见点奖)
     */
    const BonusRecommendServer = 212;
    
    /**
     * 丰收
     */
    const BonusMining = 215;
    
    /**
     * 商家收款
     */
    const BonusMerchantReceived = 216;
    
    /**
     * 感恩奖
     */
    const BonusThankful = 217;

    /**
     * 锁仓
     */
    const BonusFromGoldCoin = 218;
    
    /**
     * 商城消费支付
     */
    const BonusByXiaofei = 250;
    
    /**
     * 后台扣款
     */
    const BonusByHOutaiKoukuan = 251;
    
    /**
     * 转赠给好友
     */
    const BonusTransfer = 252;
    
    /**
     * 转让到公共市场
     */
    const BonusTransferToGRB = 253;
    
    /**
     * 锁定通证丰收
     */
    const BonusReleaseLock = 254;
    


    /*     * ******************** 现金 ************************ 34 */
    /**
     * 后台充值
     */
    const CashChongzhi = 300;

    /**
     * 微信余额充值
     */
    const CashChongzhiWeixin = 301;

    /**
     * 微信消费充值
     */
    const CashXiaofeiChongzhiWeixin = 302;

    /**
     * 支付宝余额充值
     */
    const CashChongzhiZhifubao = 303;

    /**
     * 支付宝消费充值
     */
    const CashXiaofeiChongzhiZhifubao = 304;

    /**
     * 订单退款
     */
    const CashDingdantuikuan = 305;

    /**
     * 代理商销售奖
     */
    const CashAgentSales = 314;
    
    /**
     * 钻石经销商补贴
     */
    const CashZuanshiSubsidy = 315;

    /**
     * 商家收款
     */
    const CashMerchantReceived = 316;
    
    /**
     * 转入
     */
    const CashReceived = 318;

    /**
     * 后台扣款
     */
    const CashHOutaikoukuan = 350;

    /**
     * 商城消费支付
     */
    const CashXiaofei = 351;

    /**
     * 微信支付订单
     */
    const CashWeixinXiaofei = 352;

    /**
     * 支付宝支付订单
     */
    const CashZhifbaoXiaofei = 353;

	/**
	 * 提现
	 */
    const CashTixian = 354;

	/**
	 * 提现手续费
	 */
    const CashTixianShouxufei = 355;

    /**
     * 激活会员
     */
    const CashActiveMember = 356;

    /**
     * 转入
     */
    const CashTransfer = 360;
    
    /**
     * 银行卡消费充值
     */
    const CashXiaofeiChongzhiBank = 370;
    
    
    /********************** 提货券 ***********************/
    
    /**
     * 后台充值
     */
    const colorcoinByChongZhi = 401;
    
    /**
     * 微信充值
     */
    const colorcoinChongzhiWeixin = 402;
    
    /**
     * 支付宝充值
     */
    const colorcoinChongzhiZhifubao = 403;
    
    /**
     * 现金积分充值
     */
    const colorcoinChongzhiCash = 404;
    
    /**
     * 好友转赠
     */
    const colorcoinReceived = 405;
    
    /**
     * 订单退款
     */
    const colorcoinByDingdanTuikuan = 406;
    
    /**
     * 申请合伙人赠送
     */
    const colorcoinByApplyPartner = 407;
    
    /**
     * 商家收款
     */
    const colorcoinMerchantReceived = 416;
    
    /**
     * 商城消费支付
     */
    const colorcoinByXiaofei = 450;
    
    /**
     * 后台扣款
     */
    const colorcoinByHOutaiKoukuan = 451;
    
    /**
     * 转赠给好友
     */
    const colorcoinTransfer = 452;


    
    /********************** 矿池 ************************ */

	/**
	 * 后台充值
	 */
	const PointsByChongzhi = 501;

	/**
	 * 消费拨比
	 */
	const PointsByConsumeRatio = 502;
	
	/**
	 * 商家收款
	 */
	const PointsMerchantReceived = 516;

	/**
	 * 丰收
	 */
	const PointsByMining = 550;

	/**
	 * 后台扣款
	 */
	const PointsByHOutaiKoukuan = 551;

	
	/********************** 兑换券 ************************ */
	
	/**
     * 后台充值
     */
    const enrollByChongZhi = 601;
    
    /**
     * 微信充值
     */
    const enrollChongzhiWeixin = 602;
    
    /**
     * 支付宝充值
     */
    const enrollChongzhiZhifubao = 603;
    
    /**
     * 现金积分充值
     */
    const enrollChongzhiCash = 604;
    
    /**
     * 好友转赠
     */
    const enrollReceived = 605;
    
    /**
     * 订单退款
     */
    const enrollByDingdanTuikuan = 606;
    
    /**
     * 申请合伙人赠送
     */
    const enrollByApplyPartner = 607;
    
    /**
     * 商家收款
     */
    const enrollMerchantReceived = 616;
    
    /**
     * 商城消费支付
     */
    const enrollByXiaofei = 650;
    
    /**
     * 后台扣款
     */
    const enrollByHOutaiKoukuan = 651;
    
    /**
     * 转赠给好友
     */
    const enrollTransfer = 652;
	
    
    /*     * ******************** 报单币 ************************ 34 */
    /**
     * 后台充值
     */
    const SupplyChongzhi = 700;
    
    /**
     * 微信余额充值
     */
    const SupplyChongzhiWeixin = 701;
    
    /**
     * 微信消费充值
     */
    const SupplyXiaofeiChongzhiWeixin = 702;
    
    /**
     * 支付宝余额充值
     */
    const SupplyChongzhiZhifubao = 703;
    
    /**
     * 支付宝消费充值
     */
    const SupplyXiaofeiChongzhiZhifubao = 704;
    
    /**
     * 订单退款
     */
    const SupplyDingdantuikuan = 705;
    
    /**
     * 代理商销售奖
     */
    const SupplyAgentSales = 714;
    
    /**
     * 钻石经销商补贴
     */
    const SupplyZuanshiSubsidy = 715;
    
    /**
     * 商家收款
     */
    const SupplyMerchantReceived = 716;
    
    /**
     * 转入
     */
    const SupplyReceived = 718;
    
    /**
     * 后台扣款
     */
    const SupplyHOutaikoukuan = 750;
    
    /**
     * 商城消费支付
     */
    const SupplyXiaofei = 751;
    
    /**
     * 微信支付订单
     */
    const SupplyWeixinXiaofei = 752;
    
    /**
     * 支付宝支付订单
     */
    const SupplyZhifbaoXiaofei = 753;
    
    /**
     * 提现
     */
    const SupplyTixian = 754;
    
    /**
     * 提现手续费
     */
    const SupplyTixianShouxufei = 755;
    
    /**
     * 转入
     */
    const SupplyTransfer = 760;
    
    /**
     * 银行卡消费充值
     */
    const SupplyXiaofeiChongzhiBank = 770;
    
    
    /*     * ******************** 澳洲SKN股数 ************************ */
    
    /**
     * 后台充值
     */
    const EnjoyChongzhi = 800;
    
    /**
     * 订单退款
     */
    const EnjoyByDingdanTuikuan = 802;
    
    /**
     * 消费赠送
     */
    const EnjoyByConsumeGive = 803;
    
    /**
     * 好友转赠
     */
    const EnjoyReceived = 809;
    
    /**
     * 转让到公共市场(退款)
     */
    const EnjoyTradeRefund = 814;
    
    /**
     * 丰收
     */
    const EnjoyMining = 815;
    
    /**
     * 后台扣款
     */
    const EnjoyHOutaikoukuan = 850;
    
    /**
     * 转赠给好友
     */
    const EnjoyTransfer = 852;
    
    /**
     * 转让到公共市场
     */
    const EnjoyTransferToGRB = 853;
    
    /**
     * 签到
     */
    const EnjoySignIn = 861;
    
    /**
     * 分享朋友圈
     */
    const EnjoyShare = 862;
    
    /**
     * 提现
     */
    const EnjoyTixian = 863;
    
    /**
     * 提现(退款)
     */
    const EnjoyTixianTuikuan = 864;
    
    
	
    /* ********************* GRC购物积分[credits] ************************ */
    /**
     * 后台充值
     */
    const CreditsByChongzhi = 901;
    
    /**
     * 订单退款
     */
    const CreditsByDingdanTuikuan = 902;
    
    /**
     * 好友转赠
     */
    const CreditsReceived = 909;
    
    /**
     * 转让到公共市场(退款)
     */
    const CreditsTradeRefund = 914;
    
    /**
     * 农场释放
     */
    const CreditsFarmRelease = 932;
    
    /**
     * 商城消费支付
     */
    const CreditsByXiaofei = 950;
    
    /**
     * 后台扣款
     */
    const CreditsByHOutaiKoukuan = 951;
    
    /**
     * 转赠给好友
     */
    const CreditsTransfer = 952;
    
    /**
     * 转让到公共市场
     */
    const CreditsTransferToGRB = 953;
    
    /**
     * 从公共市场转入
     */
    const CreditsReceievdToGRB = 954;
    
    /**
     * 从公共市场转入奖励
     */
    const CreditsReceievdToGRBReward = 955;
    
    /**
     * 转让至Quant Broker(华佗商城)
     */
    const CreditsTransferToHT = 960;
    
    /**
     * 转让至Quant Broker(华佗商城)手续费
     */
    const CreditsTransferToHTFee = 961;
    

    /**
     * 获取货币类型名称
     *
     * @param CurrencyAction $currencyAction
     *
     * @return string
     */
    public static function getLabel($currencyAction)
    {
        switch ($currencyAction) {
        	
            /* 公让宝 */
            case CurrencyAction::GoldCoinByChongzhi:
                return '后台充值';
            case CurrencyAction::GoldCoinByDingdanTuikuan:
                return '订单退款';
            case CurrencyAction::GoldCoinByConsumeGive:
                return '消费赠送';
            case CurrencyAction::GoldCoinConsume:
                return '销售奖';
            case CurrencyAction::GoldCoinSubsidy:
                return '管理津贴';
            case CurrencyAction::GoldCoinServiceGive:
                return '申请区域合伙人赠送';
            case CurrencyAction::GoldCoinBonus:
                return '加权分红';
            case CurrencyAction::GoldCoinCare:
                return '关爱奖';
            case CurrencyAction::GoldCoinReceived:
                return '转赠';
            case CurrencyAction::GoldCoinCountyServer:
                return '区域合伙人奖';
            case CurrencyAction::GoldCoinProvinceServer:
                return '最高级合伙人奖';
            case CurrencyAction::GoldCoinRecommendServer:
                return '最高级合伙人见点奖';
            case CurrencyAction::GoldCoinReleaseLock:
                return '锁定通证丰收';
            case CurrencyAction::GoldCoinTradeRefund:
                return '转让到公共市场(退款)';
            case CurrencyAction::GoldCoinMining:
            	return '丰收';
            case CurrencyAction::GoldCoinMerchantReceived:
            	return '商家收款';
            case CurrencyAction::GoldCoinThankful:
            	return '感恩奖';
            case CurrencyAction::GoldCoinGjjManageSubsidy:
            	return '谷聚金 - 管理招商补贴';
            case CurrencyAction::GoldCoinGjjRepeatConsume:
            	return '谷聚金 - 重复消费奖';
            case CurrencyAction::GoldCoinFarmRelease:
            	return '农场释放';
            case CurrencyAction::GoldCoinByXiaofei:
                return '商城消费支付';
            case CurrencyAction::GoldCoinByHOutaiKoukuan:
                return '后台扣款';
            case CurrencyAction::GoldCoinTransfer:
                return '转赠给';
            case CurrencyAction::GoldCoinTransferToGRB:
                return '转让到公共市场';
            case CurrencyAction::GoldCoinReceievdToGRB:
            	return '从公共市场转入';
            case CurrencyAction::GoldCoinReceievdToGRBReward:
            	return '从公共市场转入奖励';
            case CurrencyAction::GoldCoinTransferToHT:
            	return '转出至Quant Broker';
            case CurrencyAction::GoldCoinTransferToHTFee:
            	return '转出至Quant Broker手续费';
            case CurrencyAction::GoldCoinToBonus:
                return '锁仓';
                
            /* 公让宝(锁定通证) */
            case CurrencyAction::BonusByChongzhi:
            	return '后台充值';
            case CurrencyAction::BonusByDingdanTuikuan:
            	return '订单退款';
            case CurrencyAction::BonusByConsumeGive:
            	return '消费赠送';
            case CurrencyAction::BonusConsume:
            	return '消费奖';
            case CurrencyAction::BonusSubsidy:
            	return '管理津贴';
            case CurrencyAction::BonusServiceGive:
            	return '申请区域合伙人赠送';
            case CurrencyAction::BonusBonus:
            	return '加权分红';
            case CurrencyAction::BonusCare:
            	return '关爱奖';
            case CurrencyAction::BonusReceived:
            	return '转赠';
            case CurrencyAction::BonusCountyServer:
            	return '区域合伙人奖';
            case CurrencyAction::BonusProvinceServer:
            	return '最高级合伙人奖';
            case CurrencyAction::BonusRecommendServer:
            	return '最高级合伙人见点奖';
            case CurrencyAction::BonusMining:
            	return '丰收';
            case CurrencyAction::BonusMerchantReceived:
            	return '商家收款';
            case CurrencyAction::BonusThankful:
            	return '感恩奖';
            case CurrencyAction::BonusFromGoldCoin:
                return '锁仓';
            case CurrencyAction::BonusByXiaofei:
            	return '转到锁定';
            case CurrencyAction::BonusByHOutaiKoukuan:
            	return '后台扣款';
            case CurrencyAction::BonusTransfer:
            	return '转赠给';
            case CurrencyAction::BonusTransferToGRB:
            	return '转让到公共市场';
            case CurrencyAction::BonusReleaseLock:
            	return '锁定通证丰收';

            /* 现金积分 */
            case CurrencyAction::CashChongzhi:
                return '后台充值';
            case CurrencyAction::CashChongzhiWeixin:
                return '微信余额充值';
            case CurrencyAction::CashXiaofeiChongzhiWeixin:
                return '微信消费充值';
            case CurrencyAction::CashChongzhiZhifubao:
                return '支付宝余额充值';
            case CurrencyAction::CashXiaofeiChongzhiZhifubao:
                return '支付宝消费充值';
			case CurrencyAction::CashDingdantuikuan:
				return '订单退款';
			case CurrencyAction::CashAgentSales:
				return '代理商销售奖';
			case CurrencyAction::CashZuanshiSubsidy:
				return '钻石经销商补贴';
			case CurrencyAction::CashMerchantReceived:
				return '商家收款';
            case CurrencyAction::CashReceived:
                return '转赠';
            case CurrencyAction::CashHOutaikoukuan:
                return '后台扣款';
            case CurrencyAction::CashXiaofei:
                return '现金积分兑换订单';
            case CurrencyAction::CashWeixinXiaofei:
                return '微信支付订单';
            case CurrencyAction::CashZhifbaoXiaofei:
                return '支付宝支付订单';
            case CurrencyAction::CashTixian:
                return '提现';
            case CurrencyAction::CashTixianShouxufei:
                return '提现手续费';
            case CurrencyAction::CashTransfer:
                return '转赠给';
            case CurrencyAction::CashXiaofeiChongzhiBank:
            	return '银行卡消费充值';
            case CurrencyAction::CashActiveMember:
                return '激活会员';
            	
            /* 矿池 */
			case CurrencyAction::PointsByChongzhi:
				return '后台充值';
			case CurrencyAction::PointsByConsumeRatio:
				return '消费拨比';
			case CurrencyAction::PointsMerchantReceived:
				return '商家收款';
			case CurrencyAction::PointsByMining:
				return '丰收';
			case CurrencyAction::PointsByHOutaiKoukuan:
				return '后台扣款';
				
			/* 提货券 */
			case CurrencyAction::colorcoinByChongZhi:
				return '后台充值';
			case CurrencyAction::colorcoinChongzhiWeixin:
				return '微信充值';
			case CurrencyAction::colorcoinChongzhiZhifubao:
				return '支付宝充值';
			case CurrencyAction::colorcoinChongzhiCash:
				return '现金积分充值';
			case CurrencyAction::colorcoinReceived:
				return '转赠';
			case CurrencyAction::colorcoinByDingdanTuikuan:
				return '订单退款';
			case CurrencyAction::colorcoinByApplyPartner:
				return '申请合伙人赠送';
			case CurrencyAction::colorcoinMerchantReceived:
				return '商家收款';
			case CurrencyAction::colorcoinByXiaofei:
				return '商城消费支付';
			case CurrencyAction::colorcoinByHOutaiKoukuan:
				return '后台扣款';
			case CurrencyAction::colorcoinTransfer:
				return '转赠给';
				
			/* 兑换券 */
			case CurrencyAction::enrollByChongZhi:
				return '后台充值';
			case CurrencyAction::enrollChongzhiWeixin:
				return '微信充值';
			case CurrencyAction::enrollChongzhiZhifubao:
				return '支付宝充值';
			case CurrencyAction::enrollChongzhiCash:
				return '现金积分充值';
			case CurrencyAction::enrollReceived:
				return '转赠';
			case CurrencyAction::enrollByDingdanTuikuan:
				return '订单退款';
			case CurrencyAction::enrollByApplyPartner:
				return '申请合伙人赠送';
			case CurrencyAction::enrollMerchantReceived:
				return '商家收款';
			case CurrencyAction::enrollByXiaofei:
				return '商城消费支付';
			case CurrencyAction::enrollByHOutaiKoukuan:
				return '后台扣款';
			case CurrencyAction::enrollTransfer:
				return '转赠给';
				
			/* 报单币 */
			case CurrencyAction::SupplyChongzhi:
				return '后台充值';
			case CurrencyAction::SupplyChongzhiWeixin:
				return '微信余额充值';
			case CurrencyAction::SupplyXiaofeiChongzhiWeixin:
				return '微信消费充值';
			case CurrencyAction::SupplyChongzhiZhifubao:
				return '支付宝余额充值';
			case CurrencyAction::SupplyXiaofeiChongzhiZhifubao:
				return '支付宝消费充值';
			case CurrencyAction::SupplyDingdantuikuan:
				return '订单退款';
			case CurrencyAction::SupplyAgentSales:
				return '代理商销售奖';
			case CurrencyAction::SupplyZuanshiSubsidy:
				return '钻石经销商补贴';
			case CurrencyAction::SupplyMerchantReceived:
				return '商家收款';
			case CurrencyAction::SupplyReceived:
				return '转赠';
			case CurrencyAction::SupplyHOutaikoukuan:
				return '后台扣款';
			case CurrencyAction::SupplyXiaofei:
                return '激活锁定资产';
			case CurrencyAction::SupplyWeixinXiaofei:
				return '微信支付订单';
			case CurrencyAction::SupplyZhifbaoXiaofei:
				return '支付宝支付订单';
			case CurrencyAction::SupplyTixian:
				return '提现';
			case CurrencyAction::SupplyTixianShouxufei:
				return '提现手续费';
			case CurrencyAction::SupplyTransfer:
				return '转赠给';
			case CurrencyAction::SupplyXiaofeiChongzhiBank:
				return '银行卡消费充值';
				
			/* 澳洲SKN股数 */
			case CurrencyAction::EnjoyChongzhi:
				return '后台充值';
            case CurrencyAction::EnjoyByDingdanTuikuan:
                return '订单退款';
            case CurrencyAction::EnjoyByConsumeGive:
                return '消费赠送';
            case CurrencyAction::EnjoyReceived:
                return '转赠';
            case CurrencyAction::EnjoyTradeRefund:
                return '转让到公共市场(退款)';
            case CurrencyAction::EnjoyMining:
            	return '丰收';
            case CurrencyAction::EnjoyHOutaikoukuan:
            	return '后台扣款';
            case CurrencyAction::EnjoyTransfer:
                return '转赠给';
            case CurrencyAction::EnjoyTransferToGRB:
                return '转让到公共市场';
            case CurrencyAction::EnjoySignIn:
            	return '签到';
            case CurrencyAction::EnjoyShare:
            	return '分享朋友圈';
            case CurrencyAction::EnjoyTixian:
            	return '提现';
            case CurrencyAction::EnjoyTixianTuikuan:
            	return '提现(退款)';
            	
            /* GRC购物积分 */
            case CurrencyAction::CreditsByChongzhi:
            	return '后台充值';
            case CurrencyAction::CreditsByDingdanTuikuan:
            	return '订单退款';
            case CurrencyAction::CreditsReceived:
            	return '转赠';
            case CurrencyAction::CreditsTradeRefund:
            	return '转让到公共市场(退款)';
            case CurrencyAction::CreditsFarmRelease:
            	return '农场释放';
            case CurrencyAction::CreditsByXiaofei:
            	return '商城消费支付';
            case CurrencyAction::CreditsByHOutaiKoukuan:
            	return '后台扣款';
            case CurrencyAction::CreditsTransfer:
            	return '转赠给';
            case CurrencyAction::CreditsTransferToGRB:
            	return '转让到公共市场';
            case CurrencyAction::CreditsReceievdToGRB:
            	return '从公共市场转入';
            case CurrencyAction::CreditsReceievdToGRBReward:
            	return '从公共市场转入奖励';
            case CurrencyAction::CreditsTransferToHT:
            	return '转出至Quant Broker';
            case CurrencyAction::CreditsTransferToHTFee:
            	return '转出至Quant Broker手续费';

            default:
                return $currencyAction . '未知';
        }
    }

    /**
     * 返回现金收入类型集合
     *
     * @return string
     */
    public static function getCashIncomeIds()
    {
        $ids = '';
        $ids .= CurrencyAction::CashRepeat . ',';
        $ids .= CurrencyAction::CashMerchant . ',';
        $ids .= CurrencyAction::CashService . ',';
        $ids .= CurrencyAction::CashCompany . ',';
        $ids .= CurrencyAction::CashServiceJian . ',';
        $ids .= CurrencyAction::CashCompanyJian . ',';
        $ids .= CurrencyAction::CashRecharge . ',';
        $ids .= CurrencyAction::CashColorCoinConsumeBackToMerchant . ',';
        $ids .= CurrencyAction::CashGoldCoinConsumeBackToMerchant . ',';
        $ids .= CurrencyAction::CashConsumeBackToMerchant . ',';
        $ids .= CurrencyAction::CashSystemAdd . ',';
        $ids .= CurrencyAction::CashSystemReceived . ',';
        $ids .= CurrencyAction::CashBouns . ',';
        $ids .= CurrencyAction::CashStarMakerManage . ',';
        $ids .= CurrencyAction::CashServiceManage . ',';
        $ids .= CurrencyAction::AlipayCallbackFailure . ',';
        $ids .= CurrencyAction::WechatPayRecharge . ',';
        $ids .= CurrencyAction::CashReceived;

        return $ids;
    }

    /**
     * 返回现金支出类型集合
     *
     * @return string
     */
    public static function getCashExpenditureIds()
    {
        $ids = '';
        $ids .= CurrencyAction::CashWithdraw . ',';
        $ids .= CurrencyAction::CashWithdrawFee . ',';
        $ids .= CurrencyAction::AlipayApplyMaker . ',';
        $ids .= CurrencyAction::CashApplyMaker . ',';
        $ids .= CurrencyAction::WechatApplyMaker . ',';
        $ids .= CurrencyAction::CashConsume . ',';
        $ids .= CurrencyAction::CashToGoldCoin . ',';
        $ids .= CurrencyAction::CashDutyConsume . ',';
        $ids .= CurrencyAction::CashSystemReduce . ',';
        $ids .= CurrencyAction::CashSystemTransfer . ',';
        $ids .= CurrencyAction::CashToCashFee . ',';
        $ids .= CurrencyAction::CashPushShake . ',';
        $ids .= CurrencyAction::CashArtificialRefund . ',';
        $ids .= CurrencyAction::CashTransfer . ',';
        $ids .= CurrencyAction::WechatConsume . ',';
        $ids .= CurrencyAction::WechatDutyConsume;

        return $ids;
    }

    /**
     * 公让宝收入类型集合
     *
     * @return string
     */
    public static function getGoldCoinIncomeIds()
    {
        $ids = '';
        $ids .= CurrencyAction::GoldCoinShake . ',';
        $ids .= CurrencyAction::GoldCoinCashConsumeBack . ',';
        $ids .= CurrencyAction::GoldCoinCancelOrder . ',';
        $ids .= CurrencyAction::GoldCoinSystemAdd . ',';
        $ids .= CurrencyAction::GoldCoinBonus . ',';
        $ids .= CurrencyAction::GoldCoinReceived . ',';
        $ids .= CurrencyAction::GoldCoinServiceManage . ',';
        $ids .= CurrencyAction::GoldCoinStarMakerManage;

        return $ids;
    }

    /**
     * 公让宝支出类型集合
     *
     * @return string
     */
    public static function getGoldCoinExpenditureIds()
    {
        $ids = '';
        $ids .= CurrencyAction::GoldCoinExchange . ',';
        $ids .= CurrencyAction::GoldApplyMaker . ',';
        $ids .= CurrencyAction::GoldCoinConsume . ',';
        $ids .= CurrencyAction::GoldCoinSystemReduce . ',';
        $ids .= CurrencyAction::GoldCoinPushShake . ',';
        $ids .= CurrencyAction::GoldCoinTransfer;

        return $ids;
    }

    /**
     * 商超券收入类型集合
     *
     * @return string
     */
    public static function getColorCoinIncomeIds()
    {
        $ids = '';
        $ids .= CurrencyAction::ColorCoinRecommand . ',';
        $ids .= CurrencyAction::ColorCoinSystemAdd . ',';
        $ids .= CurrencyAction::ColorCoinBonus . ',';
        $ids .= CurrencyAction::ColorCoinStarMakerManage . ',';
        $ids .= CurrencyAction::ColorCoinServiceManage;

        return $ids;
    }

    /**
     * 商超券支出类型集合
     *
     * @return string
     */
    public static function getColorCoinExpenditureIds()
    {
        $ids = '';
        $ids .= CurrencyAction::ColorCoinToCash . ',';
        $ids .= CurrencyAction::ColorCoinConsume . ',';
        $ids .= CurrencyAction::ColorCoinSystemReduce;

        return $ids;
    }

    /**
     * 积分收入类型集合
     *
     * @return string
     */
    public static function getPointsIncomeIds()
    {
        $ids = '';
        $ids .= CurrencyAction::PointsSystemAdd . ',';
        $ids .= CurrencyAction::PointsFormConsume;

        return $ids;
    }

    /**
     * 积分支出类型集合
     *
     * @return string
     */
    public static function getPointsExpenditureIds()
    {
        $ids = '';
        $ids .= CurrencyAction::PointsSystemReduce . ',';
        $ids .= CurrencyAction::PointsToBonus;

        return $ids;
    }

    /**
     * 丰收点收入类型集合
     *
     * @return string
     */
    public static function getBonusIncomeIds()
    {
        $ids = '';
        $ids .= CurrencyAction::BonusSystemAdd . ',';
        $ids .= CurrencyAction::BonusFormPoints;

        return $ids;
    }

    /**
     * 丰收点支出类型集合
     *
     * @return string
     */
    public static function getBonusExpenditureIds()
    {
        $ids = '';
        $ids .= CurrencyAction::BonusSystemReduce . ',';
        $ids .= CurrencyAction::BonusCosts;

        return $ids;
    }

    /**
     * 注册币收入类型集合
     *
     * @return string
     */
    public static function getEnrollIncomeIds()
    {
        $ids = '';
        $ids .= CurrencyAction::ENrollBuyBackAdd . ',';
        $ids .= CurrencyAction::ENrollReceivedZCGY;
        $ids .= CurrencyAction::ENrollSystemAdd;

        return $ids;
    }

    /**
     * 注册币支出类型集合
     *
     * @return string
     */
    public static function getEnrollExpenditureIds()
    {
        $ids = '';
        $ids .= CurrencyAction::ENrollTransferZCGY;
        $ids .= CurrencyAction::ENrollSystemReduce;

        return $ids;
    }

    /**
     * 系统产生的
     * @return type
     */
    public static function systemMaKe()
    {
        $actions = [];
        $actions[] = CurrencyAction::ColorCoinRecommand;   // 推广奖
        $actions[] = CurrencyAction::CashRepeat;                     // 重复消费奖
        $actions[] = CurrencyAction::CashMerchant;                 // 商家联盟奖
        $actions[] = CurrencyAction::CashService;                     // 服务中心奖
        $actions[] = CurrencyAction::CashCompany;                 // 区域代理奖
        $actions[] = CurrencyAction::CashServiceJian;              // 服务中心见点奖
        $actions[] = CurrencyAction::CashCompanyJian;          // 区域代理见点奖

        $actions[] = CurrencyAction::CashBouns;                         // 丰收现金
        $actions[] = CurrencyAction::CashStarMakerManage;    // 星级创客管理津贴现金
        $actions[] = CurrencyAction::CashServiceManage;          // 服务/区域合伙人管理津贴现金

        $actions[] = CurrencyAction::GoldCoinBonus;                         // 丰收公让宝
        $actions[] = CurrencyAction::GoldCoinStarMakerManage;    // 星级创客管理津贴公让宝
        $actions[] = CurrencyAction::GoldCoinServiceManage;         // 服务/区域合伙人管理津贴公让宝

        $actions[] = CurrencyAction::ColorCoinBonus;                        // 丰收商超券
        $actions[] = CurrencyAction::ColorCoinStarMakerManage;   // 星级创客管理津贴商超券
        $actions[] = CurrencyAction::ColorCoinServiceManage;        // 服务/区域合伙人管理津贴商超券


        return $actions;
    }

    /**
     * 兑换产生的
     * @return type
     */
    public static function transactionMake()
    {
        $actions = [];
        $actions[] = CurrencyAction::CashReceived;                                                  // 现金转入（替换原 11 ）
        $actions[] = CurrencyAction::CashColorCoinConsumeBackToMerchant;
        $actions[] = CurrencyAction::CashGoldCoinConsumeBackToMerchant;
        $actions[] = CurrencyAction::CashConsumeBackToMerchant;
        $actions[] = CurrencyAction::GoldCoinShake;
        $actions[] = CurrencyAction::GoldCoinReceived;
        $actions[] = CurrencyAction::GoldCoinCashConsumeBack;
        $actions[] = CurrencyAction::GoldCoinFromCash;

        return $actions;
    }

    public static function handMake()
    {
        $actions = [];
        $actions[] = CurrencyAction::CashSystemAdd;
        $actions[] = CurrencyAction::GoldCoinSystemAdd;
        $actions[] = CurrencyAction::ColorCoinSystemAdd;

        return $actions;
    }

}
