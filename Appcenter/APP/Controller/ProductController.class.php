<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 商品相关
// +----------------------------------------------------------------------
namespace APP\Controller;

use Common\Controller\ApiController;
use V4\Model\AccountModel;
use V4\Model\PaymentMethod;
use V4\Model\Currency;
use V4\Model\AccountRecordModel;
use V4\Model\CurrencyAction;
use V4\Model\ProductModel;
use V4\Model\ShakeModel;
use V4\Model\OrderModel;
use V4\Model\ValidateMethod;
use V4\Model\CustomerServiceModel;
use V4\Model\GoldcoinPricesModel;

/**
 * 商品相关
 * @author jay
 *
 */
class ProductController extends ApiController
{

    /**
     * 商品详情
     * 
     * @method POST
     * 
     * @param int id 商品ID
     * @param int user_id 用户ID
     * @param int specification_id 规格ID(默认空不使用规格)
     */
    public function detail()
    {
    	$current_lang = getCurrentLang(true);
    	
        $product_id = intval(I('post.id'));
        $user_id = intval(I('post.user_id'));
        $specification_id = $this->post['specification_id'];

        //1.获取商品信息
        $wherekey['id'] = $product_id;
        $wherekey['manage_status'] = '1';
        $wherekey['status'] = '0';
        $field_block_name = 'b.block_name'.$current_lang.' as block_name';
        $details = M('product p')->field('p.*, '.$field_block_name.', a.block_id, a.affiliate_freight product_freight, b.block_freight, b.block_freight_collect, b.block_freight_order_amount, b.block_freight_increase_amount, a.affiliate_freight_collect')
            ->join('left join zc_product_affiliate a on a.product_id = p.id')
            ->join('left join zc_block b on b.block_id = a.block_id')
            ->where($wherekey)->find();
        if ($details == "") {
            $this->myApiPrint('此商品不存在！');
        }
        
        $details['name'] = $details['name'.$current_lang];
        $details['content'] = $details['content'.$current_lang];
        
        $vm = new ValidateMethod();
        $details = $vm->initvalue($details);
        //格式化附属属性
        $details['affiliate_attr'] = json_decode($details['affiliate_attr'], JSON_UNESCAPED_SLASHES);
        foreach ($details['affiliate_attr'] as $k => $v) {
            $details['affiliate_attr'][$k]['value'] = array_values($v['value']);
        }
        if (empty($details['affiliate_attr'])) {
            $details['affiliate_attr'] = [
                ['name' => '规格', 'value' => ['常规']]
            ];
        }
        
        //获取价格体系
        $option = M('product_price')->where(['product_id' => $details['id']])->order('price_id asc')->select();
        
        //当前规格对应价格
        if (!empty($specification_id)) {
        	$specification_price = M('ProductSpecification')->where('specification_id='.$specification_id)->getField('specification_price');
        	if ($specification_price) {
        		$option[0]['price_cash'] = sprintf( '%.2f', $specification_price);
        	}
        }
        
        $pm = new ProductModel();
        $details['option'] = $pm->jiagetxt($option, $details['block_id']);
        
        //商品名称后添加PV值显示
        $details['name'] .= '('. $details['option'][0]['pv_str']. ')';

        //2.格式化富文本内容
        $details['content'] = U('Product/showDetail/', ['id' => $product_id], '', true);

        //评分
        $details['score'] = ceil($details['score'] * 100) / 100;
        if ($details['score'] == 0) {
            $details['score'] = 5;
        }
        
        //运费
        if ($details['affiliate_freight_collect'] == 1) {
            //货到付款
            $details['yunfei'] = '到付';
        } elseif ($details['product_freight'] > 0) {
            $details['yunfei'] = sprintf('%.2f元', $details['product_freight']);
        } elseif ($details['product_freight'] == '') {
            if ($details['block_freight_collect'] == 1) {
                $details['yunfei'] = '到付';
            } elseif ($details['block_freight'] == 0) {
                $details['yunfei'] = '免运费';
            } elseif ($details['block_freight'] > 0) {
                $details['yunfei'] = sprintf('%.2f元', $details['block_freight']);
            }
        } elseif ($details['product_freight'] == 0) {
            $details['yunfei'] = '免运费';
        }
        //运费判断订单是否不满XX自动增加运费XX元
        if ($details['block_freight_increase_amount'] > 0) {
        	$block_freight_order_amount = sprintf('%.2f', $details['block_freight_order_amount']);
        	$block_freight_increase_amount = sprintf('%.2f', $details['block_freight_increase_amount']);
        	$details['yunfei'] = "订单不满{$block_freight_order_amount}元，运费{$block_freight_increase_amount}元";
        }
        
        //商品总量数据处理为商品库存
        $details['totalnum'] = (String)($details['totalnum'] - $details['exchangenum']);

        //3.是否收藏
        $where1['productid'] = $product_id;
        $where1['uid'] = $user_id;
        $isfavorite = M('favorite_product')->where($where1)->getField('favorite');

        //4.评论
        $wh = " a.productid=" . $product_id . " and a.iscontent=1 and a.content !=''";
        $comment = M('orders a')
            ->field('a.id,a.productid,a.content,a.uid,a.score,a.comment_time,a.comment_img,b.nickname,b.img')
            ->join('left join zc_member b on a.uid=b.id ')
            ->where($wh)
            ->order('a.comment_time desc')
            ->limit(0, 3)
            ->select();
        //处理评论关键词
        foreach ($comment as $k => $v) {
            $comment[$k]['nickname'] = mb_substr($v['nickname'], 0, 1, 'utf-8') . '**';
            if ($v['comment_img'] != '') {
                $tempimgs = explode(',', $v['comment_img']);
                foreach ($tempimgs as $a => $b) {
                    if ($b != '') {
                        $tempimgs[$a] = $b . c('PRODUCT_COMMENTLIST_SIZE');
                    }
                }
                $comment[$k]['comment_img'] = implode(',', $tempimgs);
            }
        }

        $carousel = array();

        //兼容目前
        if (strpos($details['carousel1'], '[') === false) {
            $photos = json_decode($details['carousel1'], true);
            $i = 0;
            foreach ($photos as $k => $v) {
                $carousel1['img'] = $v;
                if (trim($v) != '') {
                    $carousel1['img'] = substr($v, 1);
                    $carousel[$i] = (object)$carousel1;
                    $i++;
                }
                unset($carousel1);
            }
        }
        
        //商品规格
        $specification = M('ProductSpecification')->where('product_id='.$product_id)->select();
        foreach ($specification as $k=>$v) {
        	$details['specification'][] = [
        		'specification_id' => $v['specification_id'],
        		'specification_name' => $v['specification_name'.$current_lang]
        	];
        }
        
        //商品规格说明
        $details['specification_description'] = '商品规格说明信息';

        $details['carousel1'] = '';
        $details['favorite'] = intval($isfavorite);
        $details['img'] = $details['img'];
        $data['productDetails'] = $details;
        $data['usercomment'] = $comment;
        $data['carousel1'] = $carousel;

        //客服
        $mchid = M('store')->where('id=' . $details['storeid'])->getField('uid');
        $csm = new CustomerServiceModel($mchid, $user_id);
        $data['chatonlineurl'] = $csm->init();
        
        $this->myApiPrint('查询成功', 400, $data);
    }


    /**
     * 发布商品
     */
    public function add()
    {

    }


    /**
     * 修改商品
     */
    public function edit()
    {

    }


    /**
     * 删除商品
     */
    public function delete()
    {

    }


    /**
     * 商品内容HTML
     */
    public function showDetail()
    {
    	$current_lang = getCurrentLang(true);
    	
        $product_id = $this->get['id'];

        $content = M('Product')->where('id=' . $product_id)->getField('content'.$current_lang);
        $content = htmlspecialchars_decode(html_entity_decode($content));
        //随机获取一个附件头域名
        $attach_domain_key = array_rand(C('DEVICE_CONFIG')[C('DEVICE_CONFIG_DEFAULT')]['attach_domain'], 1);
        $attach_domain = C('DEVICE_CONFIG')[C('DEVICE_CONFIG_DEFAULT')]['attach_domain'][$attach_domain_key];
        $content = str_replace(C('LOCAL_HOST'), $attach_domain, $content);
        $this->assign('content', $content);

        $this->display();
    }
    
    /**
     * 商品价格转换
     * 
     * @method POST
     * 
     * @param int $type 转换为价格类型(1:现金积分,6:公让宝)
     * @param double $amount 价格金额
     */
    public function amountTransfer() {
    	$type = $this->post['type'];
    	$amount = $this->post['amount'];
    	
    	if (!validateExtend($type, 'NUMBER')) {
    		$this->myApiPrint('价格类型格式有误');
    	}
    	if (!validateExtend($amount, 'MONEY')) {
    		$this->myApiPrint('价格金额格式有误');
    	}
    	
    	$data = ['amount' => $amount];
    	
    	switch ($type) {
    		case '1':
    			break;
    		case '6':
    			$GoldcoinPricesModel = new GoldcoinPricesModel();
    			$amount = $GoldcoinPricesModel->getGrbByRmb($amount);
    			$data['amount'] = sprintf('%.4f', $amount);
    			break;
    	}
    	
    	$this->myApiPrint('查询成功', 400, $data);
    }

}

?>