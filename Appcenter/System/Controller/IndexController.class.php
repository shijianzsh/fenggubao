<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 杂项管理(首页轮播广告,协议管理,密码修改)
// +----------------------------------------------------------------------
namespace System\Controller;
use Common\Controller\AuthController;
use V4\Model\CustomerServiceModel;
use V4\Model\NewsModel;

class IndexController extends AuthController {

	public function index() {
		$this->display();
	}
	
	/**
	 * 首页轮播广告
	 */
	public function advList() {
		$Carousel = M('Carousel');
		
		$type = $this->get['type'];
		$type = empty($type) ? 0 : $type;
		
		if (!validateExtend($type, 'NUMBER')) {
			$this->error('参数格式有误');
		}
		
		$map['car_type'] = array('eq', $type);
		
		$count = $Carousel->where($map)->count();
		$limit = $this->Page($count, 10, $this->get);
		
		$list = $Carousel->where($map)->limit($limit)->order('car_id desc')->select();
		foreach ($list as $k=>$v) {
			if (!empty($v['car_link'])) {
				$car_link_cn = M('News')->where('id='.$v['car_link'])->getField('title');
				$list[$k]['car_link_cn'] = $car_link_cn;
			}
		}
		
		$this->assign('list', $list);
		$this->assign('type', $type);
		$this->display();
	}
	
	/**
	 * 首页轮播广告添加UI
	 */
	public function advAddUi() {
		//资讯列表
		$NewsModel = new NewsModel();
		$data = $NewsModel->getList('id, title', false);
		$this->assign('zixun_list', $data['list']);
		
		$this->display();
	}
	
	/**
	 * 首页轮播广告添加
	 */
	public function advAdd() {
		$Carousel = M('Carousel');
		
		C('TOKEN_ON', false);
		
		$data = $this->post;
		
		if (empty($data['car_title'])) {
			$this->error('名称不能为空');
		}
		if (empty($data['uid']) && empty($data['cid']) && empty($data['h5_path'])) {
			//$this->error('商家ID,商品ID,外链地址不能同时为空');
		}
		
		//上传图片
		$upload_config = array(
			'file' => $_FILES['car_image'],
			'path' => 'carousel/'. date('Ymd'),
		);
		$Upload = new \Common\Controller\UploadController($upload_config);
		$upload_info = $Upload->upload();
		if (!empty($upload_info['error'])) {
			$this->error('图片上传失败:'.$upload_info['error']);
		} else {
			$data['car_image'] = $upload_info['data']['url'];
		}
		
		if (!$Carousel->create($data, '', true)) {
			$this->error($Carousel->getError());
		} else {
			$id = $Carousel->add();
			$this->success('添加成功', U('System/Index/advList/type/'.$data['car_type']), false, "添加轮播广告:{$data['car_title']}[ID:{$id}]");
		}
	}
	
	/**
	 * 首页轮播广告修改
	 */
	public function advModify() {
		$Carousel = M('Carousel');
	
		$id = $this->get['id'];
	
		if (!validateExtend($id, 'NUMBER')) {
			$this->error('参数格式有误');
		}
	
		$map['car_id'] = array('eq', $id);
		$info = $Carousel->where($map)->find();
		if (!$info) {
			$this->error('该信息不存在');
		}
	
		$this->assign('info', $info);
		
		$NewsModel = new NewsModel();
		$data = $NewsModel->getList('id, title', false);
		$this->assign('zixun_list', $data['list']);
		
		$this->display();
	}
	
	/**
	 * 首页轮播广告保存
	 */
	public function advSave() {
		$Carousel = M('Carousel');
		
		$data = $this->post;
		
		//清空car_link,uid,cid,h5_path中其他三项的值
		$option = ['car_link', 'uid', 'cid', 'h5_path'];
		foreach ($option as $k=>$v) {
			if (!isset($data[$v])) {
				$data[$v] = '';
			}
		}
		
		//上传图片
		$upload_config = array (
			'file' => $_FILES['car_image'],
			'path' => 'carousel/'. date('Ymd'),
		);
		$Upload = new \Common\Controller\UploadController($upload_config);
		$upload_info = $Upload->upload();
		if (empty($upload_info['error'])) {
			$data['car_image'] = $upload_info['data']['url'];
		}
			
		if ($Carousel->save($data) === false) {
			$this->error('保存失败');
		} else {
			$this->success('保存成功', U('System/Index/advList/type/'.$data['car_type']), false, "编辑轮播广告:{$data['car_title']}[ID:{$data['car_id']}]");
		}
	}
	
	/**
	 * 首页轮播广告删除
	 */
	public function advDelete() {
		$Carousel = M('Carousel');
		
		$id = $this->get['id'];
		
		if (!validateExtend($id, 'NUMBER')) {
			$this->error('参数格式有误');
		}
		
		$map['car_id'] = array('eq', $id);
		$info = $Carousel->where($map)->field('car_title')->find();
		if (!$info) {
			$this->error('该信息不存在');
		}
		
		if ($Carousel->where($map)->delete() === false) {
			$this->error('删除失败');
		} else {
			$this->success('删除成功', U('Index/advList'), false, "删除首页轮播广告:{$info['car_title']}[ID:{$id}]");
		}
	}
	
	/**
	 * 协议管理
	 */
	public function agreementDetail() {
		$Agreement = M('Agreement');
		
		$info = $Agreement->order('id desc')->find();
		
		$this->assign('info', $info);
		$this->display();
	}
	
	/**
	 * 协议保存
	 */
	public function agreementSave() {
		$Agreement = M('Agreement');
		
		if ($Agreement->save($this->post) === false) {
			$this->error('保存失败');
		} else {
			$this->success('保存成功', '', false, "编辑协议管理内容");
		}
	}
	
	/**
	 * 客服平台列表
	 */
	public function customerService() {
		$CustomerServicePlatform = M('CustomerServicePlatform');
		
		//获取平台编码列表
		$CustomerServiceModel = new CustomerServiceModel();
		$platform_list = $CustomerServiceModel->platform;
		
		$list = $CustomerServicePlatform->order('platform_id asc')->select();
		foreach ($list as $k=>$v) {
			$list[$k]['platform_name'] = $platform_list[$v['platform_name']];
		}
		$this->assign('list', $list);
		
		
		$this->display();
	}
	
	/**
	 * 客服平台添加UI
	 */
	public function customerServiceAddUi() {
		//获取平台编码列表
	    $CustomerServiceModel = new CustomerServiceModel();
		$platform_list = $CustomerServiceModel->platform;
		$this->assign('platform_list', $platform_list);
		
		$this->display();
	}
	
	/**
	 * 客服平台添加动作
	 */
	public function customerServiceAdd() {
		$CustomerServicePlatform = M('CustomerServicePlatform');
		
		$data = $this->post;
		
		if (empty($data['platform_name']) || empty($data['platform_config'])) {
			$this->error('请填写完整');
		}
		
		//判断平台名称是否已存在
		$is_exists = $CustomerServicePlatform->where("platform_name='{$data[platform_name]}'")->count();
		if ($is_exists > 0) {
			$this->error('该客服平台已存在');
		}
		
		$result = $CustomerServicePlatform->create($data);
		
		if (!$result) {
			$this->error('添加失败:'.$CustomerServicePlatform->getError());
		} else {
			$platform_id = $CustomerServicePlatform->add();
			$this->success('添加成功', U('Index/customerService'), false, "成功添加{$data['platform_name']}[ID:{$platform_id}]客服平台");
		}
	}
	
	/**
	 * 客服平台修改
	 */
	public function customerServiceModify() {
		$CustomerServicePlatform = M('CustomerServicePlatform');
		
		$platform_id = $this->get['platform_id'];
		
		if (!validateExtend($platform_id, 'NUMBER')) {
			$this->error('参数格式有误');
		}
		
		$info = $CustomerServicePlatform->where('platform_id='.$platform_id)->find();
		if (!$info) {
			$this->error('该信息已不存在');
		}
		$this->assign('info', $info);
		
		//获取平台编码列表
		$CustomerServiceModel = new CustomerServiceModel();
		$platform_list = $CustomerServiceModel->platform;
		$this->assign('platform_list', $platform_list);
		
		$this->display();
	}
	
	/**
	 * 客服平台保存
	 */
	public function customerServiceSave() {
		$CustomerServicePlatform = M('CustomerServicePlatform');
	
		$data = $this->post;
	
		$result = $CustomerServicePlatform->save($data);
	
		if ($result === false) {
			$this->error($CustomerServicePlatform->getError());
		} else {
			$this->success('保存成功', '', false, "成功修改{$data['platform_name']}[{$data['platform_id']}]客服平台信息");
		}
	}
	
}
?>