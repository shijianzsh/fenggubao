<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 收货地址相关接口
// +----------------------------------------------------------------------
namespace APP\Controller;

use Common\Controller\ApiController;

class AddressController extends ApiController
{

    /**
     * 收货地址列表
     * @param uid
     */
    public function addressList()
    {
        $uid = I('post.uid');

        if ($uid == "") {
            $this->myApiPrint('非法访问！');
        }

        $wherekey['uid'] = $uid;
        $data = M('address')
            ->field('id,uid,consignee,phone,city_address first_address,address second_address,postcode,is_default, province, city, country')
            ->where($wherekey)
            ->order('is_default desc,id desc')
            ->select();
        
        foreach ($data as $k=>$v) {
        	$data[$k]['province'] = $v['province']==null ? '' : $v['province'];
        	$data[$k]['city'] = $v['city']==null ? '' : $v['city'];
        	$data[$k]['country'] = $v['country']==null ? '' : $v['country'];
        }

        if (empty($data)) {
            $this->myApiPrint('查无数据', 400, $data);
        }

        $this->myApiPrint('查询成功', 400, $data);
    }

    /**
     * 新增收货地址
     * @param uid 会员ID
     * @param consignee 收货人姓名
     * @param phone 手机号码
     * @param first_address 省市地址
     * @param second_address 详细地址
     * @param is_default 是否为默认地址(1:默认)
     * @param postcode 邮政编码
     * 
     * @param $province 省
     * @param $city 市
     * @param $country 区
     */
    public function addressAdd()
    {
    	$current_lang = getCurrentLang();
    	
        $uid = I('post.uid');
        $consignee = I('post.consignee');
        $city_address = I('post.first_address');
        $detail_address = I('post.second_address');
        $phone = I('post.phone');
        $default = intval(I('is_default'));
        $postcode = intval(I('post.postcode'));
        
        $province = $this->post['province'];
        $city = $this->post['city'];
        $country = $this->post['country'];
        
        if ($uid == "" || $consignee == "" || $phone == "") {
            $this->myApiPrint('所有项必填！');
        }
        if ($current_lang == 'zh-cn') {
	        if($city_address == '请选择区域信息'){
	        	$this->myApiPrint('请选择区域信息');
	        }
	        if (!validateExtend($phone, 'MOBILE')) {
	        	$this->myApiPrint('手机号码格式不对！');
	        }
	        if ( !validateExtend($province, 'CHS') || !validateExtend($city, 'CHS') || !validateExtend($country, 'CHS') ) {
	        	$this->myApiPrint( '请选择省市区' );
	        }
        }
        if (!is_numeric($uid)) {
            $this->myApiPrint('数据错误！');
        }
		
        if ($default == 1) {
            M('address')->where('uid = '.$uid)->save(array('is_default'=>0));
        }
        //是否有默认地址
        $addr = M('address')->where('uid='.$uid.' and is_default = 1')->find();
        if(!$addr){
            $default = 1;
        }
        
        $data['uid'] = $uid;
        $data['consignee'] = $consignee;
        $data['city_address'] = $city_address;
        $data['address'] = $detail_address;
        $data['phone'] = $phone;
        $data['postcode'] = $postcode;
        $data['is_default'] = $default;
        $data['post_time'] = time();
        $data['province'] = $province;
        $data['city'] = $city;
        $data['country'] = $country;
        
        $res = M('address')->add($data);
        if ($res !== false) {
            $this->myApiPrint('新增成功', 400, '');
        } else {
            $this->myApiPrint('异常', 300, '');
        }
    }

    /**
     * 查询指定的收货地址
     * @param id 地址ID
     */
    public function addressEdit()
    {
        $id = I('post.id');
        if ($id == "") {
            $this->myApiPrint('非法访问！');
        } else {
            $data = M('address')
                ->field('id,uid,consignee,phone,city_address first_address,address second_address,postcode,is_default, province, city, country')
                ->find();
            $this->myApiPrint('查询成功', 400, $data);
        }
    }

    /**
     * 保存修改地址
     */
    public function addressEditSave()
    {
    	$current_lang = getCurrentLang();
    	
        $id = I('post.id');
        $uid = I('post.uid');
        $consignee = I('post.consignee');
        $phone = I('post.phone');
        $city_address = I('post.first_address');
        $detail_address = I('post.second_address');
        $postcode = intval(I('post.postcode'));
        $address = I('post.address');
        $is_default = intval(I('post.is_default'));
        
        $province = $this->post['province'];
        $city = $this->post['city'];
        $country = $this->post['country'];
        
        if ($current_lang == 'zh-cn') {
        	if($city_address == '请选择区域信息'){
        		$this->myApiPrint('请选择区域信息');
        	}
        	if ( !validateExtend($province, 'CHS') || !validateExtend($city, 'CHS') || !validateExtend($country, 'CHS') ) {
        		$this->myApiPrint( '请选择省市区' );
        	}
        	if (!validateExtend($phone, 'MOBILE')) {
        		$this->myApiPrint('手机号码格式不对！');
        	}
        }
        
        if ($id == "" || $uid == "") {
            $this->myApiPrint('数据错误！');
        } else {
	        if ($is_default == 1) {
	            M('address')->where('uid = '.$uid)->save(array('is_default'=>0));
	        }
            $data['consignee'] = $consignee;
            $data['phone'] = $phone;
            $data['city_address'] = $city_address;
            $data['address'] = $detail_address;
            $data['postcode'] = $postcode;
            $data['is_default'] = $is_default;
            $data['post_time'] = time();
            $data['province'] = $province;
            $data['city'] = $city;
            $data['country'] = $country;
            
            $res = M('address')->where('uid='.$uid.' and id = '.$id)->save($data);
            if ($res !== false) {
                $this->myApiPrint('修改成功', 400, '');
            } else {
                $this->myApiPrint('修改失败', 300, '');
            }
        }
    }

    /**
     * 删除收货地址
     * @param id 收货地址ID
     */
    public function addressDelete()
    {
        $id = I('post.id');
        if ($id == "") {
            $this->myApiPrint('非法访问！');
        } else {
            $wherekey['id'] = $id;
            $data = M('address')->where($wherekey)->delete();
            if ($data !== false) {
                $this->myApiPrint('删除成功', 400, '');
            } else {
                $this->myApiPrint('删除失败', 300, '');
            }
        }
    }

}

?>