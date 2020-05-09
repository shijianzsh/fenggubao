<?php
/**
 * Created by PhpStorm.
 * User: jay
 * Date: 2018/9/8
 * Time: 9:56
 */

namespace V4\Model;

/**
 * [公让宝]
 * Class ProductModel
 * @package V4\Model
 */
class ProductModel {

	public function jiagetxt( $option, $block_id) {
		foreach ( $option as $k => $v ) {
			$v['price_cash']     = sprintf( '%.2f', $v['price_cash'] );
			$v['price_goldcoin'] = sprintf( '%.2f', $v['price_goldcoin'] );
			$v['give_points']    = sprintf( '%.2f', $v['give_points'] );

			$txt = '';
			if ( $v['price_cash'] > 0 ) {
				$txt .= '￥' . $v['price_cash'] . '元';
			}

			if ( $v['price_goldcoin'] > 0 ) {
				if ( $txt ) {
					$txt .= ' + ';
				}
				$txt .= $v['price_goldcoin'] . '份GRB';
			}

			if ( $v['give_points'] > 0 ) {
				$txt .= '送' . $v['give_points'] . '积分';
			}
			
			//PV
			$pv = $v['price_cash'] * $v['performance_bai_cash']  / 100;
			$pv_str = sprintf('%.0f', $v['performance_bai_cash']).'%业绩';
			
			//公让宝代理专区商品不显示PV + 价格显示为公让宝
			if ($block_id == C('GRB_EXCHANGE_BLOCK_ID')) {
				$pv = '0%业绩';
				$pv_str = '0%业绩';
				$txt = $v['price_cash']. '份GRB';
			}
			
			//$txt .= "(PV值{$pv})";
			$option[$k]['pv'] = (String)$pv;
			$option[$k]['pv_str'] = $pv_str;
			
			$option[ $k ]['txt'] = $txt;
			
		}

		return $option;
	}

}