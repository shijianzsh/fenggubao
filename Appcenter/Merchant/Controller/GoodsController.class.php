<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 商家商品管理
// +----------------------------------------------------------------------

namespace Merchant\Controller;

use Common\Controller\AuthController;

class GoodsController extends AuthController
{

    /**
     * 商品列表
     * Enter description here ...
     */
    public function index()
    {

        $start_time = $this->get['start_time'];
        $end_time = $this->get['end_time'];
        $status = $this->get['status'];
        $manage_status = $this->get['manage_status'];
        $kw = $this->get['kw'];
        $block_id = $this->get['block_id'];

        //获取店铺
        $map_store['uid'] = array('eq', session('admin_mid'));
        $map_store['status'] = array('eq', '0');
        $map_store['manage_status'] = array('eq', '1');
        $storeid = M('store')->where($map_store)->getField('id');
        if (!$storeid) {
            $this->error('店铺不存在');
        }
        $where['storeid'] = $storeid;

        if (isset($start_time) && $start_time != '') {
            $where['pro.create_time'][] = array('egt', strtotime($start_time));
        }
        if (isset($end_time) && $end_time != '') {
            $where['pro.create_time'][] = array('elt', strtotime($end_time . ' 23:59:59'));
        }
        if ($status > -1) {
            $where['pro.status'] = array('eq', intval($status));
        }
        if ($manage_status > -1) {
            $where['pro.manage_status'] = array('eq', intval($manage_status));
        }
        if ($kw != '') {
            $where['pro.name'] = array('like', '%' . trim($kw) . '%');
        }

        //商品为未删除状态
        $where['aff.affiliate_deleted'] = array(array('eq', 0), array('exp', 'is null'), 'or');

        //所属板块筛选
        if (validateExtend($block_id, 'NUMBER')) {
            $where['aff.block_id'] = array('eq', $block_id);
        }

        //分页
        $count = M('product')
            ->alias('pro')
            ->join('left join __PRODUCT_AFFILIATE__ aff ON aff.product_id=pro.id')
            ->where($where)
            ->count();
        $limit = $this->Page($count, 20, $this->get);

        //查询列表
        $datalist = M('product')
            ->alias('pro')
            ->join('left join __PRODUCT_AFFILIATE__ aff ON aff.product_id=pro.id')
            ->field('pro.*,aff.affiliate_freight,aff.affiliate_freight_collect')
            ->where($where)
            ->limit($limit)
            ->order('pro.ishot desc,pro.id desc')
            ->select();

        foreach ($datalist as $k => $v) {
            $datalist[$k]['prices'] = '';
            $prices = M('ProductPrice')->where('product_id=' . $v['id'])->order('price_tag asc')->select();
            foreach ($prices as $price) {
                if ($datalist[$k]['prices'] != '') {
                    $datalist[$k]['prices'] .= '<br />';
                }
                if ($price['price_cash'] > 0) {
                    $datalist[$k]['prices'] .= sprintf("%.2f", $price['price_cash']) . ' ';
                }
//                if ($price['price_goldcoin'] > 0) {
//                    $datalist[$k]['prices'] .= '代:' . sprintf("%.2f", $price['price_goldcoin']) . ' ';
//                }
//
//                if ($price['price_points'] > 0) {
//                    $datalist[$k]['prices'] .= '积:' . sprintf("%.2f", $price['price_points']) . ' ';
//                }
//
//                if ($price['give_goldcoin'] > 0) {
//                    $datalist[$k]['prices'] .= '返代:' . sprintf("%.2f", $price['give_goldcoin']) . ' ';
//                }
//                if ($price['give_points'] > 0) {
//                    $datalist[$k]['prices'] .= '返积:' . sprintf("%.2f", $price['give_points']) . '';
//                }
            }


            //查询兑换方式
//			$affiliate_list    = [ 'credits', 'supply', 'goldcoin', 'colorcoin' ];
//			$affiliate_list_cn = [ '积分', '特供券', '丰谷宝', '商超券' ];
//			$affiliate_use     = [];
            $affiliate = M('ProductAffiliate')->where('product_id=' . $v['id'])->find();
            if ($affiliate) {
//				foreach ( $affiliate_list as $k1 => $v1 ) {
//					if ( $affiliate[ 'affiliate_' . $v1 ] > 0 ) {
//						$affiliate_use[] = $affiliate_list_cn[ $k1 ];
//					}
//				}
//
                $block = M('Block')->where('block_id=' . $affiliate['block_id'])->find();
                $datalist[$k]['block_name'] = $block['block_name'];
//
//				if ( empty( $affiliate_use ) ) {
//					if ( $block ) {
//						foreach ( $affiliate_list as $k1 => $v1 ) {
//							if ( $block[ 'block_' . $v1 . '_percent' ] > 0 ) {
//								$affiliate_use[] = $affiliate_list_cn[ $k1 ];
//							}
//						}
//					}
//				}
//
//				$datalist[ $k ]['affiliate'] = implode( '+', $affiliate_use );
            }
        }
        $this->assign("datalist", $datalist);

        //板块列表
        $block = M('Block')->field('*')->order('block_id asc')->select();
        $this->assign('block', $block);

        $this->display();
    }

    /**
     * 发布商品
     * Enter description here ...
     */
    public function goodsAddUi()
    {
        $Store = M('Store');

        //获取店铺默认的一级和二级菜单
        $map_store['uid'] = array('eq', session('admin_mid'));
        $map_store['status'] = array('eq', '0');
        $map_store['manage_status'] = array('eq', '1');
        $store_info = $Store
            ->where($map_store)
            ->field('fid,sid,store_supermarket')
            ->find();
        if (!$store_info) {
            $this->error('该店铺已不存在,无法添加商品');
        }
        $this->assign('info', $store_info);

        //板块列表
        //非自营商家不能在免费区和特供区发布商品
        $map_block = [];
        //if ( $store_info['store_supermarket'] != '1' ) {
        //$map_block['block_id'] = array( 'not in', '2,3' );
        //}
        $block = M('Block')->field('*')->order('block_order asc')->select();
        $this->assign('block', $block);

        $this->display();
    }

    /**
     * 添加动作
     */
    public function goodsAdd()
    {

        C('TOKEN_ON', false);

        //获取店铺
        $map_store['uid'] = array('eq', session('admin_mid'));
        $map_store['status'] = array('eq', '0');
        $map_store['manage_status'] = array('eq', '1');
        $storeid = M('store')->where($map_store)->getField('id');
        if (!$storeid) {
            $this->error('店铺不存在');
        }


        $data = $this->post;
        $data['storeid'] = $storeid;
        $data['status'] = 1;         //添加时默认下架
        $data['manage_status'] = 0;  //添加时默认未审核
        $data['exchangenum'] = 0;
        $data['exchangeway'] = 0;
        $data['ishot'] = 0;
        $data['userule'] = '';
//        $data['totalnum'] = 0;
        $data['start_time'] = 0;
        $data['end_time'] = 0;
        $data['score'] = 0;
        $data['typeid'] = intval($data['sid']);
        $data['create_time'] = time();

        if (empty($data['name'])) {
            $this->error('商品名称不能为空');
        }
        if (!validateExtend($data['totalnum'], 'NUMBER') || $data['totalnum'] < 1) {
            $this->error('商品数量不能小于1');
        }
//        if (!validateExtend($data['exchangenum'], 'NUMBER') || $data['exchangenum'] < 0) {
//            $this->error('已售数量不能小于0');
//        }
        if (!validateExtend($data['price'], 'MONEY')) {
            $this->error('商品原价格式有误');
        }
        foreach ($data['product_price_1'] as $k => $v) {
            switch ($k) {
                case 'price_cash':
                    if (!validateExtend($v, 'MONEY') || $v <= 0) {
                        $this->error('销售价格格式有误（必须大于0）');
                    }
                    break;
//                case 'price_goldcoin':
//                    if (!validateExtend($v, 'MONEY')) {
//                        $this->error('丰谷宝销售价格格式有误');
//                    }
//                    break;
                case 'performance_bai_cash':
                    if (!validateExtend($v, 'NUMBER')) {
                        $this->error('业绩比例格式有误');
                    }
                    if ($v < 0 || $v > 100) {
                    	$this->error('业绩比例只能在0-100之间(包含0和100)');
                    }
                    break;
                default:
                    $data['product_price_1'][$k] = 0;
                    break;
            }
        }

        if ($data['affiliate']['block_id'] == 4) {
            $data['product_price_1']['performance_bai_cash'] = 100;
        }

        $hasPrice2 = false;
//		if ( isset( $data['product_price_2'] ) && $data['product_price_2'] ) {
//			foreach ( $data['product_price_2'] as $k => $v ) {
//				if ( $v != '' ) {
//					$hasPrice2 = true;
//				}
//			}
//			if ( $hasPrice2 ) {
//				foreach ( $data['product_price_2'] as $k => $v ) {
//					switch ( $k ) {
//						case 'price_cash':
//							if ( ! validateExtend( $v, 'MONEY' ) || $v <= 0 ) {
//								$this->error( '价格策略2现金币格式有误（必须大于0）' );
//							}
//							break;
//						case 'give_points':
//							if ( ! validateExtend( $v, 'MONEY' ) ) {
//								$this->error( '价格策略2赠送积分格式有误' );
//							}
//							break;
//						case 'performance_bai_cash':
//							if ( ! validateExtend( $v, 'MONEY' ) ) {
//								$this->error( '价格策略2现金币折算业绩比例格式有误' );
//							}
//							if ( $v < 5 || $v > 50 ) {
//								$this->error( '价格策略2现金币折算业绩比例必须大于等于5%且小于等于50%' );
//							}
//							break;
//						default:
//							$data['product_price_2'][ $k ] = 0;
//							break;
//					}
//				}
//			}
//		}


        //判断兑换日期是否正确
        if ($data['start_time'] < strtotime(date('Y-m-d 00:00:00', time()))) {
            //$this->error('兑换起始日期不能小于当前日期');
        }
        if ($data['end_time'] < strtotime(date('Y-m-d 23:59:59', time()))) {
            //$this->error('兑换结束时间必须大于当前日期');
        }

        //对应板块可用虚拟币总金额不能小于板块默认虚拟币配置总比例对应的金额 + 运费金额不能小于默认运费金额
        $block_config = M('Block')->where('block_id=' . $data['affiliate']['block_id'])->find();
        if ($data['affiliate']['affiliate_freight'] != '' && $data['affiliate']['affiliate_freight'] < $block_config['block_freight']) {
            $this->error('运费金额不能小于默认配置运费金额');
        }



        //上传图片
        $upload_config = array(
            'file' => 'multi',
            'exts' => array('jpg','png','gif','jpeg','wma','wmv','mp4'),
            'path' => 'product/' . date('Ymd'),
        );
        $Upload = new \Common\Controller\UploadController($upload_config);
        $upload_info = $Upload->upload();
        if (empty($upload_info['error'])) {
            foreach ($upload_info['data'] as $k => $v) {
                $key = substr($v['key'], -1, 1);
                if (strpos($v['key'], 'carouse') !== false) {
                    //多图
                    $kk = explode('_', $v['key']);
                    $data['carousel1']['pic' . ($kk[1] + 1)] = $v['url'];
                } else {
                    //单图
                    $data['img'] = $v['url'];
                    $data['img'] = str_replace('/Uploads', 'Uploads', $data['img']);

                }
            }
        }

        $data['video_url'] = $data['video'];

        //检测商品图片img不能为空
        if (empty($data['img'])) {
            $this->error('请上传商品图片');
        }
        //组装图片数据
        $data['carousel1'] = json_encode($data['carousel1'], JSON_UNESCAPED_SLASHES);



        //处理商品附属表数据
        /* 处理属性空值 */
        foreach ($data['affiliate']['affiliate_attr'] as $k => $v) {
            if (empty($v['name'])) {
                unset($data['affiliate']['affiliate_attr'][$k]);
            } else {
                $data['affiliate']['affiliate_attr'][$k]['value'] = array_filter($v['value']);
            }
        }
        $affiliate_attr = empty($data['affiliate']['affiliate_attr']) ? '' : json_encode($data['affiliate']['affiliate_attr'], JSON_UNESCAPED_UNICODE);
        $data_affiliate = [
            'block_id' => $data['affiliate']['block_id'],
//			'affiliate_credits'         => $data['affiliate']['affiliate_credits'] == '' ? null : $data['affiliate']['affiliate_credits'],
//			'affiliate_supply'          => $data['affiliate']['affiliate_supply'] == '' ? null : $data['affiliate']['affiliate_supply'],
//			'affiliate_goldcoin'        => $data['affiliate']['affiliate_goldcoin'] == '' ? null : $data['affiliate']['affiliate_goldcoin'],
//			'affiliate_colorcoin'       => $data['affiliate']['affiliate_colorcoin'] == '' ? null : $data['affiliate']['affiliate_colorcoin'],
            'affiliate_freight' => $data['affiliate']['affiliate_freight'] == '' ? null : $data['affiliate']['affiliate_freight'],
            'affiliate_freight_collect' => $data['affiliate']['affiliate_freight_collect'] == '' ? null : $data['affiliate']['affiliate_freight_collect'],
            'affiliate_attr' => $affiliate_attr
        ];
        unset($data['affiliate']);

        //处理价格策略1
        $data_product_price_1 = $data['product_price_1'];
        $data_product_price_1['price_tag'] = 1;
        unset($data['product_price_1']);

        //处理价格策略2
        $data_product_price_2 = false;
        if ($hasPrice2) {
            $data_product_price_2 = $data['product_price_2'];
            $data_product_price_2['price_tag'] = 2;
        }
        unset($data['product_price_2']);

        M()->startTrans();

        $ProductModel = M('Product');
        $ProductAffiliateModel = M('ProductAffiliate');
        $ProductSpecificationModel = M('ProductSpecification');

        if ($ProductModel->create($data) === false) {
            M()->rollback();
            $this->error($ProductModel->getError());
        } else {
            $product_id = $ProductModel->add();

            $data_affiliate['product_id'] = $product_id;
            if ($ProductAffiliateModel->create($data_affiliate) === false) {
                M()->rollback();
                $this->error($ProductAffiliateModel->getError());
            } else {

            	//商品规格
            	$specification_check = [];
            	foreach ($data['specification'] as $k=>$v) {
            		if (array_key_exists($v['specification_name'], $specification_check)) {
            			$specification_check[$v['specification_name']]++;
            		} else {
            			$specification_check[$v['specification_name']] = 0;
            		}
            	}
            	foreach ($specification_check as $k=>$v) {
            		if ($v>0) {
            			$this->error('商品规格名称不能重复');
            		}
            	}
            	foreach ($data['specification'] as $k=>$v) {
            		if (empty($v['specification_name']) || empty($v['specification_price'])) {
            			M()->rollback();
            			$this->error('商品规格的名称和单价不能为空');
            			break;
            		}
            			
            		$map_specification = [
            			'specification_name' => ['eq', $v['specification_name']],
            			'product_id' => ['eq', $product_id]
            		];
            		$specification_exists = $ProductSpecificationModel->where($map_specification)->field('specification_id')->find();
            		if ($specification_exists) {
            			$v['specification_uptime'] = time();
            			$result_specification = $ProductSpecificationModel->where('specification_id='.$specification_exists['specification_id'])->save($v);
            			if ($result_specification === false) {
            				M()->rollback();
            				$this->error('商品规格添加失败');
            				break;
            			}
            		} else {
            			$v['product_id'] = $product_id;
            			$v['specification_addtime'] = time();
            			$v['specification_uptime'] = time();
            			$result_specification = $ProductSpecificationModel->add($v);
            			if (!$result_specification) {
            				M()->rollback();
            				$this->error('商品规格添加失败');
            				break;
            			}
            		}
            	}
            	
                $data_product_price_1['product_id'] = $product_id;
                $product_price_1 = M('ProductPrice')->where('product_id=' . $product_id)->add($data_product_price_1);

                $product_price_2 = true;
                if ($data_product_price_2) {
                    $data_product_price_2['product_id'] = $product_id;
                    $product_price_2 = M('ProductPrice')->where('product_id=' . $product_id)->add($data_product_price_2);
                }

                if (empty($product_price_1) || !$product_price_1 || empty($product_price_2) || !$product_price_2) {
                    M()->rollback();
                    $this->error('添加价格策略失败');
                }
            }
            $ProductAffiliateModel->add();

            M()->commit();

            $this->success('添加成功', U('Goods/index'), false, "添加商品:{$data['name']}");
        }
    }

    public function ajaxVideo(){
        $file = $_FILES['file'];
        //上传图片
        $upload_config = array(
            'file' => 'multi',
            'exts' => array('jpg','png','gif','jpeg','wma','wmv','mp4','gif'),
            'path' => 'product/' . date('Ymd'),
        );
        $Upload = new \Common\Controller\UploadController($upload_config);
        $upload_info = $Upload->upload();
        if (empty($upload_info['error'])) {
            foreach ($upload_info['data'] as $k => $v) {
                $key = substr($v['key'], -1, 1);
                if (strpos($v['key'], 'video') !== false){
                    //上传视频
                    $data['video_url'] = $v['url'];
                    $data['video_url'] = str_replace('/Uploads', 'Uploads', $data['video_url']);
                }
            }
            $data['status'] = 200;
            $data['msg'] = '成功';
            $this->ajaxReturn($data);
        }else{
            $data['status'] = -1;
            $data['msg'] = '失败';
            $this->ajaxReturn($data);
        }

    }

    /**
     * 修改商品
     * Enter description here ...
     */
    public function goodsModify()
    {

        //获取店铺
        $map_store['uid'] = array('eq', session('admin_mid'));
        $map_store['status'] = array('eq', '0');
        $map_store['manage_status'] = array('eq', '1');
        $store_info = M('store')->where($map_store)->field('id,store_supermarket')->find();
        if (!$store_info) {
            $this->error('店铺不存在');
        } else {
            $storeid = $store_info['id'];
        }
        $id = intval($_GET['id']);
        $info = M('product')->where('id = ' . $id . ' and storeid = ' . $storeid)->find();
        if (!$info) {
            $this->error('该商品信息已不存在');
        }

        //商品一级分类
        $secondcate = M('second_menu')->find($info['typeid']);
        $firstcate = M('first_menu')->find($secondcate['fm_id']);
        $info['fid'] = $firstcate['fm_id'];

        $carousel1 = json_decode($info['carousel1'], true);
        $info['carousel1'] = '';
        foreach ($carousel1 as $k => $v) {
            $info['carousel1'][] = $v;
        }

        //板块列表
        //非自营商家不能在免费区和特供区发布商品
        $map_block = [];
//		if ( $store_info['store_supermarket'] != '1' ) {
//			$map_block['block_id'] = array( 'not in', '2,3' );
//		}
        $block = M('Block')->field('*')->order('block_order asc')->select();
        $this->assign('block', $block);

        //商品附属表信息
        $info['affiliate'] = M('ProductAffiliate')->where('product_id=' . $id)->find();
        if ($info['affiliate']) {
            if (!empty($info['affiliate']['affiliate_attr'])) {
                $info['affiliate']['affiliate_attr'] = json_decode($info['affiliate']['affiliate_attr'], true);
            }
        }
        
        //商品规格表信息
        $info['specification'] = M('ProductSpecification')->where('product_id='.$id)->select();

        $info['product_price_1'] = M('ProductPrice')->where([
            'product_id' => ['eq', $id],
            'price_tag' => ['eq', 1]
        ])->find();

        $info['product_price_2'] = M('ProductPrice')->where([
            'product_id' => ['eq', $id],
            'price_tag' => ['eq', 2]
        ])->find();

        $this->assign("info", $info);
        $this->display();
    }

    /**
     * 保存修改
     */
    public function goodsSave()
    {
        C('TOKEN_ON', false);

        $data = $this->post;

        //$data['start_time'] = strtotime($data['start_time']. '00:00:00');
        //$data['end_time'] = strtotime($data['end_time']. '23:59:59');
        $data['typeid'] = intval($data['sid']);

        if (empty($data['name'])) {
            $this->error('商品名称不能为空');
        }
        if (!validateExtend($data['totalnum'], 'NUMBER') || $data['totalnum'] < 1) {
            $this->error('商品数量不能小于1');
        }
//        if (!validateExtend($data['exchangenum'], 'NUMBER') || $data['exchangenum'] < 0) {
//            $this->error('已售数量不能小于0');
//        }
        if (!validateExtend($data['price'], 'MONEY')) {
            $this->error('商品原价格式有误');
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
        foreach ($data['product_price_1'] as $k => $v) {
            switch ($k) {
                case 'price_cash':
                    if (!validateExtend($v, 'MONEY') || $v <= 0) {
                        $this->error('销售价格格式有误（必须大于0）');
                    }
                    break;
                case 'price_goldcoin':
                    if (!validateExtend($v, 'MONEY')) {
                        $this->error('丰谷宝销售价格格式有误');
                    }
                    break;
                case 'performance_bai_cash':
                    if (!validateExtend($v, 'MONEY')) {
                        $this->error('业绩计算比例格式有误');
                    }
                    break;
                default:
                    $data['product_price_1'][$k] = 0;
                    break;
            }
        }
        if ($data['affiliate']['block_id'] == 4) {
            $data['product_price_1']['performance_bai_cash'] = 100;
        }
        foreach ($price_fields as $key) {
            if (!isset($data['product_price_1'][$key])) {
                $data['product_price_1'][$key] = 0;
            }
        }

        $hasPrice2 = false;
//		if ( isset( $data['product_price_2'] ) && $data['product_price_2'] ) {
//			foreach ( $data['product_price_2'] as $k => $v ) {
//				if ( $v != '' ) {
//					$hasPrice2 = true;
//				}
//			}
//			if ( $hasPrice2 ) {
//				foreach ( $data['product_price_2'] as $k => $v ) {
//					switch ( $k ) {
//						case 'price_cash':
//							if ( ! validateExtend( $v, 'MONEY' ) || $v <= 0 ) {
//								$this->error( '价格策略2现金币格式有误（必须大于0）' );
//							}
//							break;
//						case 'give_points':
//							if ( ! validateExtend( $v, 'MONEY' ) ) {
//								$this->error( '价格策略2赠送积分格式有误' );
//							}
//							break;
//						case 'performance_bai_cash':
//							if ( ! validateExtend( $v, 'MONEY' ) ) {
//								$this->error( '价格策略2现金币折算业绩比例格式有误' );
//							}
//							if ( $v < 5 || $v > 50 ) {
//								$this->error( '价格策略2现金币折算业绩比例必须大于等于5%且小于等于50%' );
//							}
//							break;
//						default:
//							$data['product_price_2'][ $k ] = 0;
//							break;
//					}
//				}
//				foreach ( $price_fields as $key ) {
//					if ( ! isset( $data['product_price_2'][ $key ] ) ) {
//						$data['product_price_2'][ $key ] = 0;
//					}
//				}
//			}
//		}


        //判断兑换日期是否正确
        if ($data['start_time'] < strtotime(date('Y-m-d 00:00:00', time()))) {
            //$this->error('兑换起始日期不能小于当前日期');
        }
        if ($data['end_time'] < strtotime(date('Y-m-d 23:59:59', time()))) {
            //$this->error('兑换结束时间必须大于当前日期');
        }

        //对应板块可用虚拟币总金额不能小于板块默认虚拟币配置总比例对应的金额 + 运费金额不能小于默认运费金额
        $block_config = M('Block')->where('block_id=' . $data['affiliate']['block_id'])->find();
        if (!$block_config) {
            $this->error('对应板块不存在');
        }

        if ($data['affiliate']['affiliate_freight'] != '' && $data['affiliate']['affiliate_freight'] < $block_config['block_freight']) {
            $this->error('运费金额不能小于默认配置运费金额' . $block_config['block_freight'] . '元');
        }

        //初始化商品状态为未审核已下架状态
        $data['status'] = 1;
        $data['manage_status'] = 0;

        //加载商品原始数据
        $info = M('product')->find($data['id']);
        $data['carousel1'] = json_decode($info['carousel1'], true);
        $i = 1;
        foreach ($data['carousel1'] as $k => $v) {
            $data['carousel1']['pic' . $i] = $v;
            $i++;
        }
        if (empty($_FILES)) { //如果图片全部被手动删除,且未上传新图片
            $data['carousel1'] = '';
        } else {
            //上传图片
            $upload_config = array(
                'file' => 'multi',
                'exts' => array('jpg','png','gif','jpeg','wma','wmv','mp4','gif'),
                'path' => 'product/' . date('Ymd'),
            );

            $Upload = new \Common\Controller\UploadController($upload_config);
            $upload_info = $Upload->upload();
            if (empty($upload_info['error'])) {
                foreach ($upload_info['data'] as $k => $v) {
                    $key = substr($v['key'], -1, 1);
                    if (strpos($v['key'], 'carouse') !== false) {
                        //多图
                        $kk = explode('_', $v['key']);
                        $data['carousel1']['pic' . ($kk[1] + 1)] = $v['url'];
                    } else {

                            //单图
                            $data['img'] = $v['url'];
                            $data['img'] = str_replace('/Uploads', 'Uploads', $data['img']);


                    }
                }
            }

        }

        $data['video_url'] = $data['video'];

        //去掉删除的
        $tmpimg = array();
        foreach ($_FILES as $k => $v) {
            if (strpos($k, 'carouse') !== false) {
                $kk = explode('_', $k);
                $picindex = 'pic' . ($kk[1] + 1);
                $tmpimg[$picindex] = $data['carousel1'][$picindex];
            }
        }
        //组装图片数据
        $data['carousel1'] = json_encode($tmpimg, JSON_UNESCAPED_SLASHES);

        //处理商品附属表数据
        /* 处理属性空值 */
        foreach ($data['affiliate']['affiliate_attr'] as $k => $v) {
            if (empty($v['name'])) {
                unset($data['affiliate']['affiliate_attr'][$k]);
            } else {
                $data['affiliate']['affiliate_attr'][$k]['value'] = array_filter($v['value']);
            }
        }
        $affiliate_attr = empty($data['affiliate']['affiliate_attr']) ? '' : json_encode($data['affiliate']['affiliate_attr'], JSON_UNESCAPED_UNICODE);
        $data_affiliate = [
            'affiliate_id' => $data['affiliate']['affiliate_id'],
            'product_id' => $data['id'],
            'block_id' => $data['affiliate']['block_id'],
//			'affiliate_credits'         => $data['affiliate']['affiliate_credits'] == '' ? null : $data['affiliate']['affiliate_credits'],
//			'affiliate_supply'          => $data['affiliate']['affiliate_supply'] == '' ? null : $data['affiliate']['affiliate_supply'],
//			'affiliate_goldcoin'        => $data['affiliate']['affiliate_goldcoin'] == '' ? null : $data['affiliate']['affiliate_goldcoin'],
//			'affiliate_colorcoin'       => $data['affiliate']['affiliate_colorcoin'] == '' ? null : $data['affiliate']['affiliate_colorcoin'],
            'affiliate_freight' => $data['affiliate']['affiliate_freight'] == '' ? null : $data['affiliate']['affiliate_freight'],
            'affiliate_freight_collect' => $data['affiliate']['affiliate_freight_collect'] == '' ? null : $data['affiliate']['affiliate_freight_collect'],
            'affiliate_attr' => $affiliate_attr
        ];
        unset($data['affiliate']);

		//处理价格策略1
        $data_product_price_1 = $data['product_price_1'];
        $data_product_price_1['price_tag'] = 1;
        unset($data['product_price_1']);

        //处理价格策略2
        $data_product_price_2 = false;
        if ($hasPrice2) {
            $data_product_price_2 = $data['product_price_2'];
            $data_product_price_2['price_tag'] = 2;
        }
        unset($data['product_price_2']);

        M()->startTrans();

        $result1 = M('Product')->save($data);
        $result2 = M('ProductAffiliate')->save($data_affiliate);

        $mapProductPrice1 = [
            'product_id' => ['eq', $data['id']],
            'price_tag' => ['eq', 1]
        ];
        if (M('ProductPrice')->where($mapProductPrice1)->count() > 0) {
            $result3 = M('ProductPrice')->where($mapProductPrice1)->save($data_product_price_1);
        } else {
            $data_product_price_1['product_id'] = $data['id'];
            $result3 = M('ProductPrice')->add($data_product_price_1);
        }

        $mapProductPrice2 = [
            'product_id' => ['eq', $data['id']],
            'price_tag' => ['eq', 2]
        ];
        if (M('ProductPrice')->where($mapProductPrice2)->count() > 0) {
            if ($data_product_price_2) {
                $result4 = M('ProductPrice')->where($mapProductPrice2)->save($data_product_price_2);
            } else {
                $result4 = M('ProductPrice')->where($mapProductPrice2)->delete();
            }
        } else {
            if ($data_product_price_2) {
                $data_product_price_2['product_id'] = $data['id'];
                $result4 = M('ProductPrice')->add($data_product_price_2);
            } else {
                $result4 = true;
            }
        }
        
        //商品规格
        $specification_check = [];
    	foreach ($data['specification'] as $k=>$v) {
            if (array_key_exists($v['specification_name'], $specification_check)) {
            	$specification_check[$v['specification_name']]++;
            } else {
            	$specification_check[$v['specification_name']] = 0;
            }
        }
        foreach ($specification_check as $k=>$v) {
        	if ($v>0) {
        		$this->error('商品规格名称不能重复');
        	}
        }
        foreach ($data['specification'] as $k=>$v) {
        	if (empty($v['specification_name']) || empty($v['specification_price'])) {
        		M()->rollback();
        		$this->error('商品规格的名称和单价不能为空');
        		break;
        	}
        	 
        	if (empty($v['specification_id'])) {
        		$v['product_id'] = $data['id'];
        		$v['specification_addtime'] = time();
        		$v['specification_uptime'] = time();
        		$result_specification = M('ProductSpecification')->add($v);
        		if (!$result_specification) {
        			M()->rollback();
        			$this->error('商品规格添加失败');
        			break;
        		}
        	} else {
        		$v['specification_uptime'] = time();
        		$result_specification = M('ProductSpecification')->where('specification_id='.$v['specification_id'])->save($v);
        		if ($result_specification === false) {
        			M()->rollback();
        			$this->error('商品规格添加失败');
        			break;
        		}
        	}
        }
        
        if ($result1 === false || $result2 === false || $result3 === false || $result4 === false) {
            M()->rollback();
            $this->error('保存失败');
        } else {
            M()->commit();
            $this->success('保存成功', '', false, "编辑商品:{$data['name']}[ID:{$data['id']}]");
        }

    }

    /**
     * 删除商品
     * Enter description here ...
     */
    public function goodsDelete()
    {
        //获取店铺
        $map_store['uid'] = array('eq', session('admin_mid'));
        $map_store['status'] = array('eq', '0');
        $map_store['manage_status'] = array('eq', '1');
        $storeid = M('store')->where($map_store)->getField('id');
        if (!$storeid) {
            $this->error('店铺不存在');
        }

        $id = $this->get['id'];

        if (!validateExtend($id, 'NUMBER')) {
            $this->error('参数格式有误');
        }

        M()->startTrans();

        $info = M('product')->where('id = ' . $id . ' and storeid = ' . $storeid)->find();
        if (!$info) {
            $this->error('产品信息已不存在');
        }

        //确认商品附属表信息是否存在
        $product_affiliate_info = M('ProductAffiliate')->where('product_id=' . $id)->field('affiliate_id')->find();
        if (!$product_affiliate_info) {
            $data = [
                'product_id' => $id,
                'affiliate_deleted' => 1
            ];
            $result = M('ProductAffiliate')->add($data);
        } else {
            $data = [
                'affiliate_deleted' => 1
            ];
            $result = M('ProductAffiliate')->where('product_id=' . $id)->save($data);
        }

        if ($result === false) {
            M()->rollback();
            $this->error('操作失败，请稍后重试');
        }

        M()->commit();
        $this->success('删除成功', '', false, "删除商品:{$info['name']}[ID:{$info['id']}]");
    }

    /**
     * 上下架
     * Enter description here ...
     */
    public function changeStatus()
    {
        //获取店铺
        $map_store['uid'] = array('eq', session('admin_mid'));
        $map_store['status'] = array('eq', '0');
        $map_store['manage_status'] = array('eq', '1');
        $storeid = M('store')->where($map_store)->getField('id');
        if (!$storeid) {
            $this->error('店铺不存在');
        }
        $goodsid = intval($_GET['id']);
        //验证商品
        $status = $_GET['cs'];
        if ($status != 1) {
            $status = 0;
        }
        $product = M('product')->where('storeid=' . $storeid . ' and id = ' . $goodsid)->find();
        if (!$product) {
            $this->error('商品不存在');
        }
        //改变状态
        M('product')->where('storeid=' . $storeid . ' and id = ' . $goodsid)->save(array('status' => $status));

        $status_cn = $status == 0 ? "上架" : "下架";
        $this->success('操作成功', '', false, "{$status_cn}商品:{$product['name']}[ID:{$goodsid}]");
    }

    /**
     * 修改库存
     *
     * @param int $type 类型(1:增加,2:减少)
     * @param int $count 修改数量
     * @param int $id 商品ID
     */
    public function exchangenumModify()
    {
        $type = $this->post['type'];
        $count = $this->post['count'];
        $id = $this->post['id'];

        if ($type != '1' && $type != '2') {
            $this->error('操作类型异常');
        }
        if (!validateExtend($count, 'NUMBER') || $count < 1) {
            $this->error('修改数量格式有误');
        }
        if (!validateExtend($id, 'NUMBER')) {
            $this->error('参数格式有误');
        }

        M()->startTrans();

        $info = M('Product')->where('id=' . $id)->field('totalnum,exchangenum')->find();
        if (!$info) {
            $this->error('商品不存在');
        }
        if ($type == '2' && $info['totalnum'] - $count < $info['exchangenum']) {
            $this->error('库存数量不能低于已售数量');
        }

        switch ($type) {
            case '1':
                $result = M('Product')->where('id=' . $id)->setInc('totalnum', $count);
                break;
            case '2':
                $result = M('Product')->where('id=' . $id)->setDec('totalnum', $count);
                break;
        }

        if ($result === false) {
            M()->rollback();
            $this->error('修改失败');
        } else {
            M()->commit();
            $this->success('修改库存成功', '', false, "成功修改商品[ID:{$id}]的库存");
        }
    }

    /**
     * 商品排序
     */
    public function modifySort() {
    	$data = $this->post;

    	if (!validateExtend($data['id'], 'NUMBER')) {
    		$this->error('商品ID参数格式有误');
    	}
    	if (!validateExtend($data['ishot'], 'NUMBER')) {
    		$this->error('排序格式有误');
    	}

    	$data_product = [
    		'ishot' => $data['ishot']
    	];

    	$result = M('Product')->where('id='.$data['id'])->save($data_product);
    	if ($result === false) {
    		$this->error('保存失败');
    	} else {
    		$this->success('保存成功', '', false, "成功修改商品排序[ID:{$data[id]}]为{$data['ishot']}");
    	}
    }

}

?>
