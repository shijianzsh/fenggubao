<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 商品管理
// +----------------------------------------------------------------------

namespace Shop\Controller;

use Common\Controller\AuthController;

class GoodsController extends AuthController {

	/**
	 * 商品分类
	 */
	public function category() {
		$FirstMenu = D( 'Admin/FirstMenu' );

		$list = $FirstMenu->getList( '', true, 'fir.fm_id,fir.fm_name,fir.fm_order', false, false, false, 'fir.fm_id' );

		foreach ( $list as $k => $v ) {
			$map_second_menu['sem.fm_id'] = array( 'eq', $v['fm_id'] );
			$list[ $k ]['second']         = M( 'SecondMenu' )
				->alias( 'sem' )
				->where( $map_second_menu )
				->join( 'left join __MENU_ATTRIBUTE__ mea ON mea.id=sem.attr_id' )
				->field( 'sem.sm_id,sem.sm_name,sem.sm_image,sem.fm_order sm_order,mea.name attr_name' )
				->order( 'sem.fm_order desc,sem.sm_id desc' )
				->select();
		}

		$this->assign( 'list', $list );
		$this->display();
	}

	/**
	 * 商品分类添加UI
	 */
	public function categoryAddUi() {
		$FirstMenu     = D( 'Admin/FirstMenu' );
		$MenuAttribute = M( 'MenuAttribute' );

		$first_list = $FirstMenu->getList( '', true, 'fir.fm_id,fir.fm_name', false, false, false, 'fir.fm_id' );
		$this->assign( 'first_list', $first_list );

		//属性列表
		$attr_list = $MenuAttribute->select();
		$this->assign( 'attr_list', $attr_list );

		$this->display();
	}

	/**
	 * 商品分类添加动作
	 */
	public function categoryAdd() {
		$FirstMenu  = M( 'FirstMenu' );
		$SecondMenu = M( 'SecondMenu' );

		$data = $this->post;

		if ( empty( $data['name'] ) ) {
			$this->error( '分类名称不能为空' );
		}
		if ( empty( $data['order'] ) ) {
			$data['order'] = 0;
		}

		if ( empty( $data['fm_id'] ) ) { //添加一级分类
			$data['fm_name']  = $data['name'];
			$data['fm_name_en']  = $data['name_en'];
			$data['fm_name_ko']  = $data['name_ko'];
			$data['fm_order'] = $data['order'];
			unset( $data['name'] );
			unset( $data['name_en'] );
			unset( $data['name_ko'] );
			unset( $data['order'] );

			if ( ! $FirstMenu->create( $data, '', true ) ) {
				$this->error( $FirstMenu->getError() );
			} else {
				$id = $FirstMenu->add();
				if ( empty( $id ) ) {
					$this->error( '添加失败' );
				} else {
					$this->success( '添加成功', U( 'Goods/category' ), false, "添加商品一级分类:{$data['fm_name']}[ID:{$id}]" );
				}
			}
		} else { //添加二级分类
			$data['sm_name']  = $data['name'];
			$data['sm_name_en']  = $data['name_en'];
			$data['sm_name_ko']  = $data['name_ko'];
			$data['fm_order'] = $data['order'];
			unset( $data['name'] );
			unset( $data['name_en'] );
			unset( $data['name_ko'] );
			unset( $data['order'] );

			//上传菜单图标
			$upload_config = array(
				'file' => $_FILES['sm_image'],
				'path' => 'menu/' . date( 'Ymd' ),
			);
			$Upload        = new \Common\Controller\UploadController( $upload_config );
			$upload_info   = $Upload->upload();
			if ( empty( $upload_info['error'] ) ) {
				$data['sm_image'] = $upload_info['data']['url'];
			} else {
                $data['sm_image'] = '';
            }

			if ( ! $SecondMenu->create( $data, '', true ) ) {
				$this->error( $SecondMenu->getError() );
			} else {
				$id = $SecondMenu->add();
				if ( empty( $id ) ) {
					$this->error( '添加失败' );
				} else {
					$this->success( '添加成功', U( 'Goods/category' ), false, "添加商品二级分类:{$data['sm_name']}[ID:{$id}]" );
				}
			}
		}
	}

	/**
	 * 商品分类编辑UI
	 */
	public function categoryModify() {
		$FirstMenu     = D( 'Admin/FirstMenu' );
		$MenuAttribute = M( 'MenuAttribute' );

		$fm_id = isset( $this->get['fm_id'] ) ? $this->get['fm_id'] : false;
		$sm_id = isset( $this->get['sm_id'] ) ? $this->get['sm_id'] : false;

		$info = false;

		if ( $fm_id ) {
			if ( ! validateExtend( $fm_id, 'NUMBER' ) ) {
				$this->error( '参数格式有误' );
			}

			$map['fm_id']    = array( 'eq', $fm_id );
			$first_menu_info = M( 'FirstMenu' )->where( $map )->find();
			if ( ! $first_menu_info ) {
				$this->error( '该分类不存在' );
			} else {
				$info['first'] = $first_menu_info;
			}
		}

		if ( $sm_id ) {
			if ( ! validateExtend( $sm_id, 'NUMBER' ) ) {
				$this->error( '参数格式有误' );
			}

			$map['sm_id']     = array( 'eq', $sm_id );
			$second_menu_info = M( 'SecondMenu' )->where( $map )->find();
			if ( ! $second_menu_info ) {
				$this->error( '该分类不存在' );
			} else {
				$info['second'] = $second_menu_info;
			}

			$first_menu_list = $FirstMenu->getList( '', true, 'fir.fm_id,fir.fm_name', false, false, false, 'fir.fm_id' );
			$this->assign( 'first_menu_list', $first_menu_list );

			//属性列表
			$attr_list = $MenuAttribute->select();
			$this->assign( 'attr_list', $attr_list );
		}

		if ( ! $info ) {
			$this->error( '参数不存在' );
		}

		$this->assign( 'info', $info );
		$this->display();
	}

	/**
	 * 商品分类保存
	 */
	public function categorySave() {
		$category_type = isset( $this->post['category_type'] ) ? $this->post['category_type'] : false;

		$data = $this->post;

		if ( $category_type == 'first_menu' ) {
			unset( $data['attr_id'] );

			if ( M( 'FirstMenu' )->save( $data ) === false ) {
				$this->error( '保存失败' );
			} else {
				$this->success( '保存成功', U( 'Goods/category' ), false, "编辑商品分类:{$data['fm_name']}[ID:{$data['fm_id']}]" );
			}
		}

		if ( $category_type == 'second_menu' ) {

			//上传菜单图标
			$upload_config = array(
				'file' => $_FILES['sm_image'],
				'path' => 'menu/' . date( 'Ymd' ),
			);
			$Upload        = new \Common\Controller\UploadController( $upload_config );
			$upload_info   = $Upload->upload();
			if ( empty( $upload_info['error'] ) ) {
				$data['sm_image'] = $upload_info['data']['url'];
			}

			//上传菜单图标
			$upload_config = array(
				'file' => $_FILES['sm_image2'],
				'path' => 'menu/' . date( 'Ymd' ),
			);
			$Upload        = new \Common\Controller\UploadController( $upload_config );
			$upload_info   = $Upload->upload();
			if ( empty( $upload_info['error'] ) ) {
				$data['sm_image2'] = $upload_info['data']['url'];
			}

			if ( M( 'SecondMenu' )->save( $data ) === false ) {
				$this->error( '保存失败' );
			} else {
				$this->success( '保存成功', U( 'Goods/category' ), false, "编辑商品分类:{$data['sm_name']}[ID:{$data['sm_id']}]" );
			}

		}
	}

	/**
	 * 商品分类删除
	 */
	public function categoryDelete() {
		$fm_id = isset( $this->get['fm_id'] ) ? $this->get['fm_id'] : false;
		$sm_id = isset( $this->get['sm_id'] ) ? $this->get['sm_id'] : false;

		if ( $fm_id ) {
			if ( ! validateExtend( $fm_id, 'NUMBER' ) ) {
				$this->error( '参数格式有误' );
			}

			//检查是否有下属子分类
			$map_second_menu['fm_id'] = array( 'eq', $fm_id );
			$second_menu_info         = M( 'SecondMenu' )->where( $map_second_menu )->count();
			if ( $second_menu_info > 0 ) {
				$this->error( '请先删除所有下属子分类' );
			}

			//查询该分类是否存在
			$map_first_menu['fm_id'] = array( 'eq', $fm_id );
			$first_menu_info         = M( 'FirstMenu' )->where( $map_first_menu )->field( 'fm_name' )->find();
			if ( ! $first_menu_info ) {
				$this->error( '该分类已不存在' );
			}

			if ( M( 'FirstMenu' )->where( $map_first_menu )->delete() === false ) {
				$this->error( '删除失败' );
			} else {
				$this->success( '删除成功', U( 'Goods/category' ), false, "删除商品分类:{$first_menu_info['fm_name']}[ID:{$fm_id}]" );
			}
		}

		if ( $sm_id ) {
			if ( ! validateExtend( $sm_id, 'NUMBER' ) ) {
				$this->error( '参数格式有误' );
			}

			//查询该分类是否存在
			$map_second_menu['sm_id'] = array( 'eq', $sm_id );
			$second_menu_info         = M( 'SecondMenu' )->where( $map_second_menu )->field( 'sm_name' )->find();
			if ( ! $second_menu_info ) {
				$this->error( '该分类已不存在' );
			}

			if ( M( 'SecondMenu' )->where( $map_second_menu )->delete() === false ) {
				$this->error( '删除失败' );
			} else {
				$this->success( '删除成功', U( 'Goods/category' ), false, "删除商品分类:{$second_menu_info['sm_name']}[ID:{$sm_id}]" );
			}
		}
	}

	/**
	 * 商品列表
	 */
	public function goodsList() {
		$Product = D( 'Admin/Product' );

		$product_name = $this->get['product_name'];
		$block_id     = $this->get['block_id'];
		$type         = $this->get['type'];

		$map = array();

		//排除非当前管理员的下线,除了超管和小管理员
		/* 暂停该限制(由于配置参数中批量处理了除平台管理员外其他管理员均视为了非小管理员，所以会导致商城管理员无法正常管理商品，故暂停该限制)
		$is_small_super = $this->isSmallSuperManager();
		if ( ! $is_small_super && session( "admin_id" ) != 1 ) {
			$map['mem.repath'] = array( 'like', "%,{$this->get('sess_auth')['admin_mid']},%" );
			$map               = array_merge( $map, $this->filterMember( session( 'admin_mid' ), true, 'mem', $map ) );
		}
		*/

		if ( ! empty( $product_name ) ) {
			$map['_string'] = " (sto.store_name like '%{$product_name}%' or pro.name like '%{$product_name}%') ";
		}

		switch ( $type ) {
			case '0':
				$map['pro.manage_status'] = array( 'eq', 0 );
				break;
			case '1':
				$map['pro.manage_status'] = array( 'eq', 1 );
				break;
			case '2':
				$map['pro.manage_status'] = array( 'eq', 2 );
				break;
		}

		//商品为未删除状态
		$map['aff.affiliate_deleted'] = array( array( 'eq', 0 ), array( 'exp', 'is null' ), 'or' );

		//所属板块筛选
		if ( validateExtend( $block_id, 'NUMBER' ) ) {
			$map['aff.block_id'] = array( 'eq', $block_id );
		}

		$count = $Product->alias( 'pro' );
		$count = $count->join( 'LEFT JOIN __STORE__ sto ON sto.id=pro.storeid' );
		$count = $count->join( 'JOIN __MEMBER__ mem ON mem.id=sto.uid' );
		$count = $count->join( 'left join __PRODUCT_AFFILIATE__ aff ON aff.product_id=pro.id' );
		$count = empty( $map ) ? $count : $count->where( $map );
		$count = $count->count();
		$limit = $this->Page( $count, 20, $this->get );

		$datalist = $Product->getList( $map, true, '', false, $limit );

		foreach ( $datalist as $k => $v ) {
			$datalist[ $k ]['prices'] = '';
			$prices                   = M( 'ProductPrice' )->where( 'product_id=' . $v['id'] )->order( 'price_tag asc' )->select();
			foreach ( $prices as $price ) {
				if ( $datalist[ $k ]['prices'] != '' ) {
					$datalist[ $k ]['prices'] .= '<br />';
				}
				if ( $price['price_cash'] > 0 ) {
					$datalist[ $k ]['prices'] .=  sprintf( "%.2f", $price['price_cash'] ) . ' ';
				}
			}

			//查询兑换方式
//			$affiliate_list    = [ 'credits', 'supply', 'goldcoin', 'colorcoin' ];
//			$affiliate_list_cn = [ '积分', '特供券', '公让宝', '商超券' ];
//			$affiliate_use     = [];
			$affiliate         = M( 'ProductAffiliate' )->where( 'product_id=' . $v['id'] )->find();
			if ( $affiliate ) {
//				foreach ( $affiliate_list as $k1 => $v1 ) {
//					if ( $affiliate[ 'affiliate_' . $v1 ] > 0 ) {
//						$affiliate_use[] = $affiliate_list_cn[ $k1 ];
//					}
//				}

				$block                        = M( 'Block' )->where( 'block_id=' . $affiliate['block_id'] )->find();
				$datalist[ $k ]['block_name'] = $block['block_name'];

//				if ( empty( $affiliate_use ) ) {
//					if ( $block ) {
//						foreach ( $affiliate_list as $k1 => $v1 ) {
//							if ( $block[ 'block_' . $v1 . '_percent' ] > 0 ) {
//								$affiliate_use[] = $affiliate_list_cn[ $k1 ];
//							}
//						}
//					}
//				}

//				$datalist[ $k ]['affiliate'] = implode( '+', $affiliate_use );

				$datalist[ $k ]['affiliate_freight'] = $affiliate['affiliate_freight'];
			}
		}

		$this->assign( 'list', $datalist );

		//板块列表
		$block = M( 'Block' )->field( '*' )->order( 'block_id asc' )->select();
		$this->assign( 'block', $block );

		$this->display();
	}

	/**
	 * 商品详情
	 */
	public function goodsDetail() {
		$Product = D( 'Admin/Product' );

		$id = $this->get['id'];

		if ( ! validateExtend( $id, 'NUMBER' ) ) {
			$this->error( '参数格式有误' );
		}

		$map['pro.id'] = array( 'eq', $id );
		$info          = $Product->getList( $map, false );
		if ( empty( $info ) ) {
			$this->error( '该商品不存在' );
		}

		$info['content'] = html_entity_decode( $info['content'] );
		$info['img']     = str_replace( '_sm', '', $info['img'] );
		
		//商品一级分类
		$secondcate = M('second_menu')->find($info['typeid']);
		$firstcate = M('first_menu')->find($secondcate['fm_id']);
		$info['fid'] = $firstcate['fm_id'];

		//板块列表
		$block = M( 'Block' )->field( '*' )->order( 'block_id asc' )->select();
		$this->assign( 'block', $block );

		//商品附属表信息
		$info['affiliate'] = M( 'ProductAffiliate' )->where( 'product_id=' . $id )->find();
		if ( $info['affiliate'] ) {
			if ( ! empty( $info['affiliate']['affiliate_attr'] ) ) {
				$info['affiliate']['affiliate_attr'] = json_decode( $info['affiliate']['affiliate_attr'], true );
			}
		}

		//价格策略
		$map_product_price_1     = [
			'product_id' => [ 'eq', $id ],
			'price_tag'  => [ 'eq', 1 ]
		];
		$product_price_1         = M( 'ProductPrice' )->where( $map_product_price_1 )->find();
		$info['product_price_1'] = $product_price_1;

		$map_product_price_2     = [
			'product_id' => [ 'eq', $id ],
			'price_tag'  => [ 'eq', 2 ]
		];
		$product_price_2         = M( 'ProductPrice' )->where( $map_product_price_2 )->find();
		$info['product_price_2'] = $product_price_2;

		$this->assign( 'info', $info );
		$this->display();
	}

	/**
	 * 商品保存
	 */
	public function goodsSave() {
		$data = $this->post;

		//$data['start_time'] = strtotime($data['start_time']. '00:00:00');
		//$data['end_time'] = strtotime($data['end_time']. '23:59:59');
		$data['typeid'] = $data['sid'];

		if ( empty( $data['name'] ) ) {
			$this->error( '商品名称不能为空' );
		}
		if ( ! validateExtend( $data['totalnum'], 'NUMBER' ) || $data['totalnum'] < 1 ) {
			$this->error( '商品数量不能小于1' );
		}
		if ( ! validateExtend( $data['exchangenum'], 'NUMBER' ) || $data['exchangenum'] < 0 ) {
			$this->error( '已售数量不能小于0' );
		}
		if ( ! validateExtend( $data['price'], 'MONEY' ) ) {
			$this->error( '销售价格格式有误' );
		}
		$price_fields = [
			'price_goldcoin',
			'price_points',
			'give_goldcoin',
			'give_points',
			'performance_bai_cash',
			'performance_bai_goldcoin',
			'performance_bai_points'
		];
		foreach ( $data['product_price_1'] as $k => $v ) {
			switch ( $k ) {
				case 'price_cash':
					if ( ! validateExtend( $v, 'MONEY' ) || $v <= 0 ) {
						$this->error( '销售价格或价格策略1现金币格式有误（必须大于0）' );
					}
					break;
				case 'price_goldcoin':
					if ( ! validateExtend( $v, 'MONEY' ) ) {
						$this->error( '价格策略1公让宝格式有误' );
					}
					break;
//				case 'performance_bai_goldcoin':
//					if ( ! validateExtend( $v, 'MONEY' ) || $v <= 0 ) {
//						$this->error( '价格策略1公让宝折算业绩比例格式有误（必须大于0）' );
//					}
//					break;
				default:
					$data['product_price_1'][ $k ] = 0;
					break;
			}
		}
		foreach ( $price_fields as $key ) {
			if ( ! isset( $data['product_price_1'][ $key ] ) ) {
				$data['product_price_1'][ $key ] = 0;
			}
		}

		$hasPrice2 = false;
		if ( isset( $data['product_price_2'] ) && $data['product_price_2'] ) {
			foreach ( $data['product_price_2'] as $k => $v ) {
				if ( $v != '' ) {
					$hasPrice2 = true;
				}
			}
			if ( $hasPrice2 ) {
				foreach ( $data['product_price_2'] as $k => $v ) {
					switch ( $k ) {
						case 'price_cash':
							if ( ! validateExtend( $v, 'MONEY' ) || $v <= 0 ) {
								$this->error( '价格策略2现金币格式有误（必须大于0）' );
							}
							break;
						case 'give_points':
							if ( ! validateExtend( $v, 'MONEY' ) ) {
								$this->error( '价格策略2赠送积分格式有误' );
							}
							break;
						case 'performance_bai_cash':
							if ( ! validateExtend( $v, 'MONEY' ) ) {
								$this->error( '价格策略2现金币折算业绩比例格式有误' );
							}
							if ( $v < 5 || $v > 50 ) {
								$this->error( '价格策略2现金币折算业绩比例必须大于等于5%且小于等于50%' );
							}
							break;
						default:
							$data['product_price_2'][ $k ] = 0;
							break;
					}
				}
				foreach ( $price_fields as $key ) {
					if ( ! isset( $data['product_price_2'][ $key ] ) ) {
						$data['product_price_2'][ $key ] = 0;
					}
				}
			}
		}

//		//判断兑换日期是否正确
//		if ($data['start_time'] < strtotime(date('Y-m-d 00:00:00', time()))) {
//			//$this->error('兑换起始日期不能小于当前日期');
//		}
//		if ($data['end_time'] <  strtotime(date('Y-m-d 23:59:59', time()))) {
//			//$this->error('兑换结束时间必须大于当前日期');
//		}

		//加载商品原始数据
		$info              = M( 'product' )->find( $data['id'] );
		$data['carousel1'] = json_decode( $info['carousel1'], true );
		$i                 = 1;
		foreach ( $data['carousel1'] as $k => $v ) {
			$data['carousel1'][ 'pic' . $i ] = $v;
			$i ++;
		}
		if ( empty( $_FILES ) ) { //如果图片全部被手动删除,且未上传新图片
			$data['carousel1'] = '';
		} else {
			//上传图片
			$upload_config = array(
				'file' => 'multi',
				'path' => 'product/' . date( 'Ymd' ),
			);

			$Upload      = new \Common\Controller\UploadController( $upload_config );
			$upload_info = $Upload->upload();
			if ( empty( $upload_info['error'] ) ) {
				foreach ( $upload_info['data'] as $k => $v ) {
					$key = substr( $v['key'], - 1, 1 );
					if ( strpos( $v['key'], 'carouse' ) !== false ) {
						//多图
						$kk                                          = explode( '_', $v['key'] );
						$data['carousel1'][ 'pic' . ( $kk[1] + 1 ) ] = $v['url'];
					} else {
						//单图
						$data['img'] = $v['url'];
						$data['img'] = str_replace( '/Uploads', 'Uploads', $data['img'] );
					}
				}
			}

		}
		//去掉删除的
		$tmpimg = array();
		foreach ( $_FILES as $k => $v ) {
			if ( strpos( $k, 'carouse' ) !== false ) {
				$kk                  = explode( '_', $k );
				$picindex            = 'pic' . ( $kk[1] + 1 );
				$tmpimg[ $picindex ] = $data['carousel1'][ $picindex ];
			}
		}
		//组装图片数据
		$data['carousel1'] = json_encode( $tmpimg, JSON_UNESCAPED_SLASHES );

		//处理商品附属表数据
		/* 处理属性空值 */
		foreach ( $data['affiliate']['affiliate_attr'] as $k => $v ) {
			if ( empty( $v['name'] ) ) {
				unset( $data['affiliate']['affiliate_attr'][ $k ] );
			} else {
				$data['affiliate']['affiliate_attr'][ $k ]['value'] = array_filter( $v['value'] );
			}
		}
		$affiliate_attr = empty( $data['affiliate']['affiliate_attr'] ) ? '' : json_encode( $data['affiliate']['affiliate_attr'], JSON_UNESCAPED_UNICODE );
		$data_affiliate = [
			'affiliate_id'              => $data['affiliate']['affiliate_id'],
			'product_id'                => $data['id'],
			'block_id'                  => $data['affiliate']['block_id'],
//			'affiliate_credits'   => $data['affiliate']['affiliate_credits'] == '' ? null : $data['affiliate']['affiliate_credits'],
//			'affiliate_supply'    => $data['affiliate']['affiliate_supply'] == '' ? null : $data['affiliate']['affiliate_supply'],
//			'affiliate_goldcoin'  => $data['affiliate']['affiliate_goldcoin'] == '' ? null : $data['affiliate']['affiliate_goldcoin'],
//			'affiliate_colorcoin' => $data['affiliate']['affiliate_colorcoin'] == '' ? null : $data['affiliate']['affiliate_colorcoin'],
			'affiliate_freight'         => $data['affiliate']['affiliate_freight'] == '' ? null : $data['affiliate']['affiliate_freight'],
			'affiliate_freight_collect' => $data['affiliate']['affiliate_freight_collect'] == '' ? null : $data['affiliate']['affiliate_freight_collect'],
			'affiliate_attr'            => $affiliate_attr
		];
		unset( $data['affiliate'] );

//处理价格策略1
		$data_product_price_1              = $data['product_price_1'];
		$data_product_price_1['price_tag'] = 1;
		unset( $data['product_price_1'] );

		//处理价格策略2
		$data_product_price_2 = false;
		if ( $hasPrice2 ) {
			$data_product_price_2              = $data['product_price_2'];
			$data_product_price_2['price_tag'] = 2;
		}
		unset( $data['product_price_2'] );

		M()->startTrans();

		$result1          = M( 'Product' )->save( $data );
		$result2          = M( 'ProductAffiliate' )->save( $data_affiliate );
		$mapProductPrice1 = [
			'product_id' => [ 'eq', $data['id'] ],
			'price_tag'  => [ 'eq', 1 ]
		];
		if ( M( 'ProductPrice' )->where( $mapProductPrice1 )->count() > 0 ) {
			$result3 = M( 'ProductPrice' )->where( $mapProductPrice1 )->save( $data_product_price_1 );
		} else {
			$data_product_price_1['product_id'] = $data['id'];
			$result3                            = M( 'ProductPrice' )->add( $data_product_price_1 );
		}

		$mapProductPrice2 = [
			'product_id' => [ 'eq', $data['id'] ],
			'price_tag'  => [ 'eq', 2 ]
		];
		if ( M( 'ProductPrice' )->where( $mapProductPrice2 )->count() > 0 ) {
			if ( $data_product_price_2 ) {
				$result4 = M( 'ProductPrice' )->where( $mapProductPrice2 )->save( $data_product_price_2 );
			} else {
				$result4 = M( 'ProductPrice' )->where( $mapProductPrice2 )->delete();
			}
		} else {
			if ( $data_product_price_2 ) {
				$data_product_price_2['product_id'] = $data['id'];
				$result4                            = M( 'ProductPrice' )->add( $data_product_price_2 );
			} else {
				$result4 = true;
			}
		}

		if ( $result1 === false || $result2 === false || $result3 === false || $result4 === false ) {
			M()->rollback();
			$this->error( '保存失败' );
		} else {
			M()->commit();
			$this->success( '保存成功', '', false, "编辑商品信息:{$data['name']}[ID:{$data['id']}]" );
		}
	}

	/**
	 * 商品审核通过
	 */
	public function goodsPass() {
		$Product = D( 'Admin/Product' );

		$id = $this->get['id'];

		if ( ! validateExtend( $id, 'NUMBER' ) ) {
			$this->error( '参数有误' );
		}

		$map['pro.id']         = array( 'eq', $id );
		$data['manage_status'] = 1;
		if ( $Product->alias( 'pro' )->where( $map )->save( $data ) === false ) {
			$this->error( '操作失败' );
		} else {
			$info = $Product->getList( $map, false, 'pro.name,sto.store_name' );
			$this->success( '已审核通过', '', false, "审核通过店铺[{$info['store_name']}]的商品:[{$info['name']}]" );
		}
	}

	/**
	 * 商品驳回
	 */
	public function goodsReject() {
		$Product = D( 'Admin/Product' );

		$id = $this->get['id'];

		if ( ! validateExtend( $id, 'NUMBER' ) ) {
			$this->error( '参数有误' );
		}

		$map['pro.id']         = array( 'eq', $id );
		$data['manage_status'] = 2;
		if ( $Product->alias( 'pro' )->where( $map )->save( $data ) === false ) {
			$this->error( '操作失败' );
		} else {
			$info = $Product->getList( $map, false, 'pro.name,sto.store_name' );
			$this->success( '已驳回', '', false, "驳回店铺[{$info['store_name']}]的商品:[{$info['name']}]" );
		}
	}

	/**
	 * 分类属性列表
	 */
	public function attributeList() {
		$MenuAttribute = M( 'MenuAttribute' );

		$list = $MenuAttribute->select();
		$this->assign( 'list', $list );

		$this->display();
	}

	/**
	 * 分类属性添加
	 */
	public function attributeAdd() {
		$MenuAttribute = M( 'MenuAttribute' );

		$data = $this->post;

		if ( empty( $data['name'] ) ) {
			$this->error( '属性名称不能为空' );
		}
		if ( ! empty( $data['attr_order'] ) && ! validateExtend( $data['attr_order'], 'NUMBER' ) ) {
			$this->error( '属性排序格式有误' );
		}

		if ( ! $MenuAttribute->create( $data, '', true ) ) {
			$this->error( $MenuAttribute->getError() );
		} else {
			$id = $MenuAttribute->add();
			if ( empty( $id ) ) {
				$this->error( '添加失败' );
			} else {
				$this->success( '添加成功', U( 'Goods/attributeList' ), false, "添加商品分类属性:{$data['name']}[ID:{$id}]" );
			}
		}
	}

	/**
	 * 分类属性编辑
	 */
	public function attributeModify() {
		$MenuAttribute = M( 'MenuAttribute' );

		$id = $this->get['id'];

		if ( ! validateExtend( $id, 'NUMBER' ) ) {
			$this->error( '参数格式有误' );
		}

		$map['id'] = array( 'eq', $id );
		$info      = $MenuAttribute->where( $map )->find();
		if ( ! $info ) {
			$this->error( '该属性已不存在' );
		}
		$this->assign( 'info', $info );

		$this->display();
	}

	/**
	 * 分类属性保存
	 */
	public function attributeSave() {
		$MenuAttribute = M( 'MenuAttribute' );

		$data = $this->post;

		if ( empty( $data['name'] ) ) {
			$this->error( '属性名称不能为空' );
		}
		if ( ! empty( $data['attr_order'] ) && ! validateExtend( $data['attr_order'], 'NUMBER' ) ) {
			$this->error( '属性排序格式有误' );
		}

		if ( $MenuAttribute->save( $data ) === false ) {
			$this->error( '保存失败' );
		} else {
			$this->success( '保存成功', U( 'Goods/attributeList' ), false, "编辑商品分类属性:{$data['name']}[ID:{$data['id']}]" );
		}
	}

	/**
	 * 分类属性删除
	 */
	public function attributeDelete() {
		$MenuAttribute = M( 'MenuAttribute' );
		$SecondMenu    = M( 'SecondMenu' );

		$id = $this->get['id'];

		if ( ! validateExtend( $id, 'NUMBER' ) ) {
			$this->error( '参数格式有误' );
		}

		//判断是否有二级分类已选中该分类属性
		$map_second_menu['attr_id'] = array( 'eq', $id );
		$second_menu_info           = $SecondMenu->where( $map_second_menu )->limit( 1 )->find();
		if ( $second_menu_info ) {
			$this->error( '无法删除：已有商品分类使用该属性！' );
		}

		$map['id'] = array( 'eq', $id );

		$info = $MenuAttribute->where( $map )->find();
		if ( ! $info ) {
			$this->error( '该属性已不存在' );
		}

		if ( $MenuAttribute->where( $map )->delete() === false ) {
			$this->error( '删除失败' );
		} else {
			$this->success( '删除成功', U( 'Goods/attributeList' ), false, "删除商品分类属性:{$info['name']}[ID:{$id}]" );
		}
	}

	/**
	 * 商品二级分类批量编辑
	 */
	public function secondCategoryModify() {
		$SecondMenu = M( 'SecondMenu' );

		$list = $SecondMenu->order( 'fm_order desc,sm_id desc' )->select();
		$this->assign( 'list', $list );

		$this->display();
	}

	/**
	 * 商品二级分类批量保存
	 */
	public function secondCategorySave() {
		$SecondMenu = M( 'SecondMenu' );

		$data_single = array();

		//获取批量POST数据并拆分
		$data = $this->post;
		foreach ( $data as $k => $v ) {
			foreach ( $v as $k1 => $v1 ) {
				switch ( $k ) {
					case 'sm_name':
						$data_single[ $k1 ]['sm_name'] = $v1;
						break;
					case 'sm_name_en':
						$data_single[ $k1 ]['sm_name_en'] = $v1;
						break;
					case 'sm_name_ko':
						$data_single[ $k1 ]['sm_name_ko'] = $v1;
						break;
					case 'fm_order':
						$data_single[ $k1 ]['fm_order'] = $v1;
						break;
					case 'sm_id':
						$data_single[ $k1 ]['sm_id'] = $v1;
						break;
				}
			}
		}
		//批量获取FILE数据并拆分
		foreach ( $_FILES as $k => $v ) {
			if ( $k == 'sm_image' ) {
				$file_info = array();

				foreach ( $v as $k1 => $v1 ) {
					foreach ( $v1 as $k2 => $v2 ) {
						if ( ! empty( $v2 ) ) {
							$file_info[ $k1 ] = $v2;
							//上传菜单图标 （圆形）
							$upload_config = array(
								'file' => $file_info,
								'path' => 'menu/' . date( 'Ymd' ),
							);
							$Upload        = new \Common\Controller\UploadController( $upload_config );
							$upload_info   = $Upload->upload();
							if ( empty( $upload_info['error'] ) ) {
								$data_single[ $k2 ]['sm_image'] = $upload_info['data']['url'];
							}
						}
					}
				}
			}

			if ( $k == 'sm_image2' ) {
				$file_info = array();

				foreach ( $v as $k1 => $v1 ) {
					foreach ( $v1 as $k2 => $v2 ) {
						if ( ! empty( $v2 ) ) {
							$file_info[ $k1 ] = $v2;
							//上传菜单图标 （方形）
							$upload_config = array(
								'file' => $file_info,
								'path' => 'menu/' . date( 'Ymd' ),
							);
							$Upload        = new \Common\Controller\UploadController( $upload_config );
							$upload_info   = $Upload->upload();
							if ( empty( $upload_info['error'] ) ) {
								$data_single[ $k2 ]['sm_image2'] = $upload_info['data']['url'];
							}
						}
					}
				}
			}
		}

		//分批次保存
		$error = array();
		foreach ( $data_single as $k => $v ) {
			if ( $SecondMenu->save( $v ) === false ) {
				$error[]['error'] = "{$v[sm_name]}的数据保存失败";
			}
		}

		if ( count( $error ) == 0 ) {
			$this->success( '批量保存成功', U( 'Goods/category' ), false, "批量编辑商品二级分类分类" );
		} else {
			$this->logWrite( "批量编辑商品二级分类部分保存成功" );
			$this->assign( 'failed_data', $error );
			$this->display( 'secondCategorySaveFailed' );
		}
	}

	/**
	 * 板块列表
	 */
	public function block() {
		$BlockModel = M( 'Block' );

		$list = $BlockModel->order( 'block_order asc,block_id asc' )->select();
		foreach ($list as $k=>$v) {
			$list[$k]['block_enabled_cn'] = C('FIELD_CONFIG')['block']['block_enabled'][$v['block_enabled']];
			$list[$k]['block_only_member_cn'] = C('FIELD_CONFIG')['block']['block_only_member'][$v['block_only_member']];
		}
		$this->assign( 'list', $list );

		$this->display();
	}

	/**
	 * 板块添加[UI]
	 */
	public function blockAdd() {
		$this->display();
	}

	/**
	 * 板块添加动作
	 */
	public function blockAddAction() {
		$BlockModel = M( 'Block' );

		$data               = $this->post;
		$data['block_name'] = safeString( $data['block_name'], 'trim' );

		if ( empty( $data['block_name'] ) ) {
			$this->error( '板块名称不能为空' );
		}

		//检测板块名是否已存在
		$map['block_name'] = array( 'eq', $data['block_name'] );
		$info              = $BlockModel->where( $map )->find();
		if ( $info ) {
			$this->error( '板块名称已存在' );
		}

		if ( $BlockModel->create( $data ) === false ) {
			$this->error( $BlockModel->getError() );
		}
		$block_id = $BlockModel->add();

		$this->success( '板块添加成功', U( 'Goods/block' ), false, "成功添加商品板块[{$data[block_name]}][ID:{$block_id}]" );
	}

	/**
	 * 板块修改
	 */
	public function blockModify() {
		$BlockModel = M( 'Block' );

		$block_id = $this->get['block_id'];

		if ( ! validateExtend( $block_id, 'NUMBER' ) ) {
			$this->error( '版块ID格式有误' );
		}

		$info = $BlockModel->where( 'block_id=' . $block_id )->find();
		if ( ! $info ) {
			$this->error( '该板块信息不存在' );
		}
		$this->assign( 'info', $info );

		$this->display();
	}

	/**
	 * 板块保存
	 */
	public function blockSave() {
		$BlockModel = M( 'Block' );

		$data = $this->post;
//		dump($data);dump($data['block_goldcoin_percent']);
        if($data['block_goldcoin_percent']>100){
            $this->error('丰谷宝抵扣抵比例最大100%');
        }
		//$data['block_name'] = safeString($data['block_name'], 'trim');

		/*
		if (empty($data['block_name'])) {
			$this->error('板块名称不能为空');
		}
		*/
        
        //排序
        if (!validateExtend($data['block_order'], 'NUMBER')) {
        	$this->error('排序格式有误');
        }

		//检测板块名是否已存在
		/*
		$map['block_name'] = array('eq', $data['block_name']);
		$info = $BlockModel->where($map)->field('block_id')->find();
		if ($info['block_id'] != $data['block_id']) {
			$this->error('板块名称已存在');
		}
		*/
        
        //上传图标
        $upload_config = array(
        	'file' => $_FILES['block_icon'],
        	'path' => 'block/' . date( 'Ymd' ),
        );
        $Upload        = new \Common\Controller\UploadController( $upload_config );
        $upload_info   = $Upload->upload();
        if ( empty( $upload_info['error'] ) ) {
        	$data['block_icon'] = $upload_info['data']['url'];
        }
        
        //上传封面图
        $upload_config = array(
        	'file' => $_FILES['block_cover'],
        	'path' => 'block/' . date( 'Ymd' ),
        );
        $Upload        = new \Common\Controller\UploadController( $upload_config );
        $upload_info   = $Upload->upload();
        if ( empty( $upload_info['error'] ) ) {
        	$data['block_cover'] = $upload_info['data']['url'];
        }

		if ( $BlockModel->save( $data ) === false ) {
			$this->error( '保存失败，请稍后重试' );
		}

		$this->success( '保存成功', U('Goods/block'), false, "成功修改商品板块[ID:{$data['block_id']}]" );
	}
	
	/**
	 * 板块激活/禁用
	 */
	public function block_enabled() {
		$BlockModel = M( 'Block' );
		
		$enabled = $this->get['enabled'];
		$id = $this->get['id'];
		
		if (!validateExtend($enabled, 'NUMBER') || !validateExtend($id, 'NUMBER')) {
			$this->error('参数格式有误');
		}
		
		$data = [
			'block_enabled' => $enabled
		];
		$result = $BlockModel->where('block_id='.$id)->save($data);
		if (!$result) {
			$this->error('操作失败');
		}
		
		$this->success('操作成功', '', false, "成功修改板块状态为[enabled:{$enabled}]");
	}
	
	/**
	 * 板块排序
	 */
	public function block_modifysort() {
		$data = $this->post;
		 
		if (!validateExtend($data['block_id'], 'NUMBER')) {
			$this->error('商品ID参数格式有误');
		}
		if (!validateExtend($data['block_order'], 'NUMBER')) {
			$this->error('排序格式有误');
		}
		 
		$data_block = [
			'block_order' => $data['block_order']
		];
		 
		$result = M('Block')->where('block_id='.$data['block_id'])->save($data_block);
		if ($result === false) {
			$this->error('保存失败');
		} else {
			$this->success('保存成功', '', false, "成功修改商品板块排序[ID:{$data[block_id]}]为{$data['block_order']}");
		}
	}

}

?>