<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/4
 * Time: 17:07
 */

namespace V4\Model;


/**
 * 验证
 * Class Currency
 * @package V4\Model
 */
class ValidateMethod{
    
	/**
	 * 添加购物车验证
	 */
	public function addCart($data){
		//1.验证店铺及商品状态和库存
		$status = $this->checkShopAndProductStatus($data['cart_quantity'], $data['product_id']);
		if ($status !== true) {
			return array('status'=>0, 'msg'=>$status);
		}
		
		//2.验证用户
		$wherem['id'] = $data['user_id'];
		$user = M('member')->where($wherem)->find();
		if(empty($user)){
			return array('status'=>0, 'msg'=>'用户不存在');
		}
		if($user['is_blacklist'] > 0 || $user['is_lock'] > 0){
			return array('status'=>0, 'msg'=>'你的账号被锁定，禁止兑换');
		}
		if(trim($data['cart_attr']) == ''){
			return array('status'=>0, 'msg'=>'请选择商品规格');
		}
		
		return array('status'=>1);
	}
	
	/**
	 * 初始化数据
	 * @param unknown $data
	 */
	public function initvalue($data){
		foreach ($data as $k=>$v){
			if($v == ''){
				$data[$k] = '';
			}
		}
		return $data;
	}
	
	/**
	 * 验证店铺及商品状态和库存
	 * 
	 * @param int $buy_num 购买数量
	 * @param int $product_id 商品ID
	 */
	public function checkShopAndProductStatus($buy_num, $product) {
		if($product['block_id'] == 2 || $product['block_id'] == 3){
			return "该商品不支持加入购物车";
		}

		//验证库存
		if ($buy_num > ($product['totalnum'] - $product['exchangenum'])) {
			return "商品[{$product['name']}]库存不足";
		}
		
		return true;
	}
	
}