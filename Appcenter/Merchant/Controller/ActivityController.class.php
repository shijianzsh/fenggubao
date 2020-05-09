<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 商家活动
// +----------------------------------------------------------------------

namespace Merchant\Controller;
use Common\Controller\AuthController;

class ActivityController extends AuthController {
	
	public function index() {
		//获取商家优惠活动
		$map_store['uid'] = array('eq', session('admin_mid'));
		$map_store['status'] = array('eq', '0');
		$map_store['manage_status'] = array('eq', '1');
		$storeid = M('store')->where($map_store)->getField('id');
		if(!$storeid){
			$this->error('店铺不存在');
		}
		$info = M('preferential_way')->where('store_id = '.$storeid)->find();
		$info['discount'] = ($info['conditions']-$info['reward'])/$info['conditions']*10;
		
		//unittest($info);
		$this->assign('info',  $info);
		$this->display('pwinfo');
	}
	
	/**
	 * 处理保存
	 * Enter description here ...
	 */
	public function pwSave() {
		$PreferentialWay = M('PreferentialWay');
		
		$this->error('此功能已禁用');
		
		//获取商家优惠活动
		$map_store['uid'] = array('eq', session('admin_mid'));
		$map_store['status'] = array('eq', '0');
		$map_store['manage_status'] = array('eq', '1');
		$storeid = M('store')->where($map_store)->getField('id');
		if(!$storeid){
			$this->error('店铺不存在');
		}
		$data = $this->post;
		
		if (empty($data['pname'])) {
			$this->error('活动名称不能为空');
		}
		if (!validateExtend($data['discount'], 'MONEY')) {
			$this->error('活动折扣额度格式有误');
		}
		
		if($data['discount'] < 10 && $data['discount'] > 1){
			$data['conditions'] = 100;
			$data['reward'] = round(100-($data['discount']*10));
			$data['manage_status'] = 0;  //修改后未审核

			//是否上传文件
			if ($_FILES['img']['error'] == 0) {
				//上传图片
				$upload_config = array (
					'file' => 'multi',
					'path' => 'activity/'. date('Ymd'),
				);
				$Upload = new \Common\Controller\UploadController($upload_config);
				$upload_info = $Upload->upload();
				if (empty($upload_info['error'])) {
					$data['img'] = $upload_info['data']['img']['url'];
					$data['img'] = str_replace('/Uploads', 'Uploads', $data['img']);
				}
			}
			
			if (empty($data['id'])) {
				if (empty($data['img'])) {
					$this->error('请上传活动图片');
				}
				
				$data['store_id'] = $storeid;
				if (!$PreferentialWay->create($data, '', true)) {
					$this->error($PreferentialWay->getError());
				}
				//$id = $PreferentialWay->add();
				if (empty($id)) {
					$this->error('保存失败');
				}
			} else {
				$ss['discount'] = $data['discount'];
				M('store')->where('id='.$storeid)->save($ss);
				if ($PreferentialWay->where('id='.$data['id'].' and store_id='.$storeid)->save($data) === false) {
					$this->error('保存失败');
				}
				$id = $data['id'];
			}
			
			$this->success('保存成功', '', false, "编辑店铺活动:{$data['pname']}[ID:{$id}]");
		}
		
	}
	
}
?>