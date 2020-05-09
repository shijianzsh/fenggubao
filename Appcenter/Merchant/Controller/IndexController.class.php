<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 商家管理首页
// +----------------------------------------------------------------------

namespace Merchant\Controller;
use Common\Controller\AuthController;

class IndexController extends AuthController {
	
	public function index() {
		$this->display();
	}
	
	/**
	 * 店铺信息查看
	 */
	public function storeDetail() {
		$uid = session('admin_mid');
		
		$map_store['uid'] = array('eq', $uid);
		$map_store['status'] = array('eq', '0');
		$map_store['manage_status'] = array('eq', '1');
		
		$info = M('store')->where($map_store)->order('id desc')->find();
		
		$carousel1 = json_decode($info['evn_img'], true);
		$info['carousel1'] = '';
		foreach ($carousel1 as $k=>$v){
			$info['carousel1'][] = $v;
		}
		
		$zk = D('preferential_way')->where('store_id='.$info['id'])->find();
		$zk['discount'] = ($zk['conditions']-$zk['reward'])/$zk['conditions']*10;
		$this->assign('zk', $zk);
		
		$this->assign('info', $info);
		$this->display();
	}
	
	/**
	 * 处理保存
	 * Enter description here ...
	 */
	public function saveDetail() {
		//设置折扣正常范围内的最大和最小值
		$discount_max = 9.5;
		$discount_min = 5;
		
		$data = $this->post;
		
		if(count($_POST['services']) == 2){
			$data['service'] = 3;
		}elseif(count($_POST['services']) == 1){
			$data['service'] = $_POST['services'][0];
		}elseif(count($_POST['services']) == 0){
			$data['service'] = 0;
		}
		
		//获取老discount值和老折扣表数据
		$old_discount = M('Store')->where('id='.$data['id'])->field('discount')->find();
		$preferentialway_info = M('PreferentialWay')->where('store_id='.$data['id'])->field('conditions,reward')->find();
		if (!$old_discount || !$preferentialway_info) {
			$this->error('保存失败:店铺老折扣数据或折扣表数据不存在');
		}
		$preferentialway_discount = ($preferentialway_info['conditions'] - $preferentialway_info['reward'])/10;
		
		if ($data['discount'] <= $discount_max && $data['discount'] >= $discount_min){
			//判断折扣表数据是否大于新discount值,如果大于新discount值,则保存折扣表数据
			if ($preferentialway_discount > $data['discount']) {
				$data['discount'] = $preferentialway_discount;
			} else {
				$data_pw['pname'] = $data['pname'];
				$data_pw['conditions'] = 100;
				$data_pw['reward'] = round(100-($data['discount']*10));
				$data_pw['manage_status'] = 1;
				$PreferentialWay = M('PreferentialWay');
				if ($PreferentialWay->where('store_id='.$data['id'])->save($data_pw) === false) {
					$this->error('保存失败:01');
				}
			}
			unset($data['pname']);
		} else {
			//判断老discount字段值是否为0或在值在非正常范围内
			if ($old_discount['discount'] == '0' || ($old_discount['discount'] <= $discount_max && $old_discount['discount'] >= $discount_min)) {
				//判断折扣表数据是否在正常范围内
				if ($preferentialway_discount <= $discount_max && $preferentialway_discount >= $discount_min) {
					//如果折扣表数据为正常范围内,则同步折扣表数据至discount字段
					$data['discount'] = $preferentialway_discount;
				} else {
					$this->error('保存失败:原始活动折扣数据在非合理折扣数据范围内,请修改为合理的活动折扣');
				}
			} else {
				//老折扣数据在合理范围内,则舍弃非合理的新折扣数据
				unset($data['discount']);
			}
		}
		
		//营业时间格式处理
		$data['start_time'] = strtotime(date('Y-m-d '). $data['start_time']);
		$data['end_time'] = strtotime(date('Y-m-d '). $data['end_time']);
		
		//加载商户原始数据
		$info = M('store')->find($data['id']);
		$data['evn_img'] = json_decode($info['evn_img'], true);
		$i = 1;
		foreach ($data['evn_img'] as $k=>$v){
			$data['evn_img']['pic'.$i] = $v;
			$i++;
		}
		$isuploadlogo = false;
		if (empty($_FILES)) { //如果图片全部被手动删除,且未上传新图片
			$data['evn_img'] = '';
		} else {
			//上传图片
			$upload_config = array (
				'file' => 'multi',
				'path' => 'store/'. date('Ymd'),
			);
			
			$Upload = new \Common\Controller\UploadController($upload_config);
			$upload_info = $Upload->upload();
			if (empty($upload_info['error'])) {
				foreach ($upload_info['data'] as $k=>$v) {
					$key = substr($v['key'], -1, 1);
					if(strpos($v['key'] , 'carouse') !== false){
						$kk = explode('_', $v['key']);
						//多图
						$data['evn_img']['pic'.($kk[1]+1)] = $v['url'];
					}else{
						//单图
						$data['store_img'] = $v['url'];
						$isuploadlogo = true;
					}
				}
			}
		}
		//去掉删除的
		$tmpimg = array();
		foreach ($_FILES as $k=>$v){
			if(strpos($k , 'carouse') !== false){
				$kk = explode('_', $k);
				$picindex = 'pic'.($kk[1]+1);
				$tmpimg[$picindex] = $data['evn_img'][$picindex];
			}
		}
		$data['evn_img'] = json_encode($tmpimg,JSON_UNESCAPED_SLASHES);
		//移动单图
		/*if($isuploadlogo){
			$tempfile = $_SERVER['DOCUMENT_ROOT'].$data['store_img'];
			$data['store_img'] = str_replace('/Uploads/store', 'Uploads/business_licence', $data['store_img']);
			$newfile = $_SERVER['DOCUMENT_ROOT'].'/'.$data['store_img'];
			//是否创建目标目录
			$path = $_SERVER['DOCUMENT_ROOT'].'/Uploads/business_licence/'.date('Ymd');
			if(!is_dir($path)){
				mkdir($path);
			}
			rename($tempfile, $newfile);
		}*/
		//unittest($data);
		if (M('store')->save($data) === false) {
			$this->error('保存失败');
		} else {
			$this->success('保存成功', '', false, "编辑店铺信息:{$data['store_name']}[ID:{$data['id']}]");
		}
	}
	
}
?>