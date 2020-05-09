<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 搜索相关接口
// +----------------------------------------------------------------------
namespace APP\Controller;

use Common\Controller\ApiController;
use V4\Model\ProductModel;

class SearchController extends ApiController {


	/**
	 * 商城首页
	 */
	public function index() {
		$current_lang = getCurrentLang(true);
		
		$page_size   = 10;
		$page        = intval( I( 'page' ) ) ?: 1;
		$block_id    = intval( I( 'block_id' ) );
		$category_id = intval( I( 'category_id' ) );
		$keywords    = str_replace( '%', '', str_replace( '_', '', I( 'keywords' ) ) );

		$data = [
			'block'      => null,
			'categories' => [],
		];
		if ( $block_id ) {
			$field_block_name = 'block_name'.$current_lang.' as block_name';
			$data['block'] = M( 'block' )->field( 'block_id, '.$field_block_name.', block_cover' )->where( [ 'block_id' => $block_id ] )->find();
		}

		$field_fm_name = 'fm_name'.$current_lang;
		$data['categories'] = array_merge( [
			[
				'id'   => 0,
				'name' => '全部'
			]
		], M( 'first_menu' )->field( 'fm_id as id, '.$field_fm_name.' as name' )->order( 'fm_order' )->select() );

		$where = [ 'p.status' => 0, 'p.manage_status' => 1, 'a.affiliate_deleted' => 0 ];
		if ( $data['block'] ) {
			$where['b.block_id'] = $block_id;
			$where['b.block_enabled'] = 1;
		}
		
		if ( $category_id ) {
			$where['sm.fm_id'] = $category_id;
		}

		//代理专区分类进行特殊处理
		if ( $block_id == '4' ) {
			unset($where['sm.fm_id']);
			$where['sm.sm_id'] = array('eq', $category_id);
			$map_categories['fm_id'] = array('eq', 20);
			
			//@override
			$field_sm_name = 'sm_name'.$current_lang;
			$data['categories'] = M('SecondMenu')->field('sm_id as id, '.$field_sm_name.' as name')->where($map_categories)->order('fm_order desc')->select();
		}

		//关键词
		if ( $keywords != '' ) {
			$where['p.`name`'] = [ 'like', '%' . $keywords . '%' ];
		}
		
		$field_block_name = 'b.block_name'.$current_lang.' as block_name';
		$field_name = 'p.`name'.$current_lang.'` as name';
		$data['products'] = M( 'product p' )->field( 'p.id, '.$field_name.', p.img, p.price, p.totalnum, p.exchangenum, '.$field_block_name.', a.block_id, sm.fm_id as category_id' )
		                                    ->join( 'left join zc_product_affiliate a on a.product_id = p.id' )
		                                    ->join( 'left join zc_block b on b.block_id = a.block_id' )
		                                    ->join( 'left join zc_second_menu sm on sm.sm_id = p.typeid' )
		                                    ->where( $where )
		                                    ->order( 'p.ishot desc,p.id asc' )->limit( $page_size )->page( $page )
		                                    ->select();
		$pm               = new ProductModel();
		foreach ( $data['products'] as $key => $product ) {
			$product['img']           = $product['img'];
			$option                   = M( 'product_price' )->where( [ 'product_id' => $product['id'] ] )->order( 'price_id asc' )->select();
			$product['option']        = $pm->jiagetxt( $option, $data['products'][$key]['block_id'] );
			
			$data['products'][ $key ] = $product;
		}


		$this->myApiPrint( '获取成功', 400, $data );
	}


	/**
	 * 商品列表
	 */
	public function dalibaoliebiao() {
		$current_lang = getCurrentLang(true);
		
		$page = intval( I( 'post.page' ) );
		if ( $page < 1 ) {
			$page = 1;
		}
		$ps       = 10;
		$block_id = intval( I( 'post.block_id' ) );
		$field_block_name = 'b.block_name'.$current_lang.' as block_name';
		$field_name = 'p.`name'.$current_lang.'` as name';
		$list     = M( 'product p' )->field( 'p.id, '.$field_name.', p.img, p.price, p.totalnum, p.exchangenum, '.$field_block_name.', a.block_id' )
		                            ->join( 'left join zc_product_affiliate a on a.product_id = p.id' )
		                            ->join( 'left join zc_block b on b.block_id = a.block_id' )
		                            ->where( [ 'p.status' => 0, 'p.manage_status' => 1, 'b.block_id' => $block_id ] )
		                            ->order( 'p.ishot desc,p.id asc' )->limit( $ps )->page( $page )
		                            ->select();
		$pm       = new ProductModel();
		foreach ( $list as $k => $v ) {
			$option               = M( 'product_price' )->where( [ 'product_id' => $v['id'] ] )->order( 'price_id asc' )->select();
			$list[ $k ]['option'] = $pm->jiagetxt( $option );
		}
		$this->myApiPrint( '获取成功', 400, $list );
	}


	public function searchTo() {
		$current_lang = getCurrentLang(true);
		
		$page        = intval( I( 'post.page' ) );
		$block_id    = intval( I( 'post.block_id' ) );
		$keywords    = str_replace( '%', '', str_replace( '_', '', I( 'post.searchContent' ) ) );
		$cityName    = I( 'post.cityName' );
		$countryName = I( 'post.countryName' );
		$search_sort = intval( I( 'post.search_sort' ) );
		$sm_id       = intval( I( 'post.sm_id' ) );
		$price_range = intval( I( 'post.price_range' ) );
		$lng         = I( 'post.lng' );
		$lat         = I( 'post.lat' );
		$range       = empty( $_POST['range'] ) ? 0 : intval( $_POST['range'] );
		if ( $page < 1 ) {
			$page = 1;
		}
		$ps    = 10;
		$where = [ 'p.status' => 0, 'p.manage_status' => 1, 'b.block_id' => $block_id, 'a.affiliate_deleted' => 0 ];
		if ( $sm_id > 0 ) {
			$where['p.typeid'] = $sm_id;
		}
		//关键词
		if ( $keywords != '' ) {
			$where['p.`name`'] = [ 'like', '%' . $keywords . '%' ];
		}
		if ( $countryName != '' ) {
			$where['m.country'] = [ 'like', '%' . $countryName . '%' ];
		}
		$min_price = 0;
		$max_price = 100000;
		if ( $price_range > 0 ) {
			switch ( $price_range ) {
				case 1:
					$min_price = 0;
					$max_price = 100;
					break;
				case 2:
					$min_price = 100;
					$max_price = 200;
					break;
				case 3:
					$min_price = 200;
					$max_price = 500;
					break;
				case 4:
					$min_price = 500;
					$max_price = 1000;
					break;
				case 5:
					$min_price = 1000;
			}
			$where['p.price'] = [ 'between', [ $min_price, $max_price ] ];
		}
		
		$field_block_name = 'b.block_name'.$current_lang.' as block_name';
		$field_name = 'p.`name'.$current_lang.'` as name';
		$list = M( 'product p' )->field( 'p.id, '.$field_name.', p.img, p.price, p.totalnum, p.exchangenum, '.$field_block_name.', a.block_id' )
		                        ->join( 'left join zc_product_affiliate a on a.product_id = p.id' )
		                        ->join( 'left join zc_block b on b.block_id = a.block_id' )
		                        ->join( 'left join zc_store m on m.id = p.storeid' )
		                        ->where( $where )
		                        ->order( 'p.ishot desc,p.id asc' )->limit( $ps )->page( $page )
		                        ->select();
		$pm   = new ProductModel();
		foreach ( $list as $k => $v ) {
			$option               = M( 'product_price' )->where( [ 'product_id' => $v['id'] ] )->order( 'price_id asc' )->select();
			$list[ $k ]['option'] = $pm->jiagetxt( $option, $v['block_id'] );
		}

		$totalPage          = M( 'product p' )->field( 'p.id, p.`name`, p.img, p.price, p.totalnum, p.exchangenum, '.$field_block_name.', a.block_id' )
		                                      ->join( 'left join zc_product_affiliate a on a.product_id = p.id' )
		                                      ->join( 'left join zc_block b on b.block_id = a.block_id' )
		                                      ->join( 'left join zc_store m on m.id = p.storeid' )
		                                      ->where( $where )->count();
		$page1['totalPage'] = floor( ( $totalPage - 1 ) / $ps ) + 1;
		$page1['everyPage'] = $ps;
		$data1['data']      = $list;
		$data1['page']      = $page1;
		$this->myApiPrint( '查询成功！', 400, $data1 );
	}

	/**
	 * 搜索县区
	 *
	 * @param city_name 城市名称
	 */
	public function search_country() {
		$city_name = I( 'post.city_name' );

		if ( $city_name == "" ) {
			$this->myApiPrint( '数据错误！' );
		}

		$wherekey = " city_name like '%" . $city_name . "%' ";
		$data     = M( 'city_country' )->where( $wherekey )->select();
		if ( empty( $data ) ) {
			$this->myApiPrint( '查询失败！' );
		}

		$this->myApiPrint( '查询成功！', 400, $data );
	}


	public function searchStore() {
		$page1['totalPage'] = 1;
		$page1['everyPage'] = 10;
		$data1['data']      = [];
		$data1['page']      = $page1;
		$this->myApiPrint( '查询成功！', 400, $data1 );
	}


}

?>