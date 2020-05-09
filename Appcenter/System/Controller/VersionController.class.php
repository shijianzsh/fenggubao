<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | APP版本管理
// +----------------------------------------------------------------------
namespace System\Controller;
use Common\Controller\AuthController;

class VersionController extends AuthController {
	
	public $apkManage;
	public $allow_platform;
	
	public function __construct() {
		parent::__construct();
		
		$this->apkManage = M('ApkManage');
		$this->allow_platform = array('1', '2'); //1:安卓,2:苹果
	}

	public function index() {
		$map = array();
		
		$platform = $this->get['platform'];
		$version_num = $this->get['version_num'];
		
		if (!empty($platform) && !validateExtend($platform, 'NUMBER')) {
			$this->error('终端类型格式有误');
		}
		
		if (!empty($platform)) {
			$map['platform'] = array('eq', $platform);
		}
		if (!empty($version_num)) {
			$map['version_num'] = array('like', "%{$version_num}%");
		}
		
		$count = $this->apkManage->where($map)->count();
		$limit = $this->Page($count, 20, $this->get);
		
		$list = $this->apkManage->where($map)->order('id desc')->limit($limit)->select();
		$this->assign('list', $list);
		
		$this->display();
	}
	
	/**
	 * 添加版本UI
	 */
	public function appAddUi() {
		$this->display();
	}
	
	/**
	 * 添加版本动作
	 */
	public function appAdd() {
		$version_num = safeString($this->post['version_num'], 'trim_space');
		$platform = $this->post['platform'];
		$is_need = $this->post['is_need'];
		$content = safeString($this->post['content'], 'trim');
		$content_en = safeString($this->post['content_en'], 'trim');
		$content_ko = safeString($this->post['content_ko'], 'trim');
		$number = $this->post['number'];

		if (empty($version_num)) {
			$this->error('版本号不能为空');
		}
		if (!in_array($platform, $this->allow_platform)) {
			$this->error('未被允许的终端类型');
		}
		if ($is_need!='0' && $is_need!='1') {
			$this->error('未知的强制更新参数');
		}
		if (empty($content)) {
			$this->error('请填写更新描述');
		}

		$data = array();
		$platform_cn = '';
		
		$data['version_num'] = $version_num;
		$data['add_time'] = time();
		$data['content'] = $content;
		$data['content_en'] = $content_en;
		$data['content_ko'] = $content_ko;
		$data['point'] = 0;
		$data['is_need'] = $is_need;
		$data['platform'] = $platform;
		$data['number'] = $number;
		
		//检查版本号是否已存在
		if ($this->isExistsVersion($version_num, $platform)) {
			$this->error('版本号已存在');
		}
		
		//文件数据处理
		if ($platform == '1') { //安卓
			if (!empty($this->post['src'])) {
				if (!preg_match('/^(http|https){1,}/', $this->post['src'])) {
					$this->error('APP文件地址格式有误');
				} else {
					$data['src'] = $this->post['src'];
				}
			} else {
				$upload_config = array(
					'file' => $_FILES['src'],
					'exts' => 'apk',
					'path' => 'apk',
					'size' => 1024000*50,
				);
				$upload = new \Common\Controller\UploadController($upload_config);
				$upload_info = $upload->upload();
				if (!empty($upload_info['error'])) {
					$this->error($upload_info['error']);
				}
				$data['src'] = U('/','','',true).$upload_info['data']['url'];
			}
			
			$platform_cn = '安卓端';
			
		} elseif ($platform == '2') { //苹果
			
			$data['src'] = safeString($this->post['src'], 'trim');
			if (empty($data['src'])) {
				$this->error('请填写APP文件地址');
			}
			
			$platform_cn = '苹果端';
			
		} else {
			$this->error('未知终端类型');
		}
		
		if ($this->apkManage->add($data)) {
			$this->success('添加版本成功', U('Version/index'), false, "添加{$platform_cn}版本[{$version_num}]成功");
		}
	}
	
	/**
	 * 编辑版本
	 */
	public function appModify() {
		$id = $this->get['id'];
		
		if (!validateExtend($id, 'NUMBER')) {
			$this->error('参数格式有误');
		}
		
		$map['id'] = array('eq', $id);
		$info = $this->apkManage->where($map)->find();
		if (!$info) {
			$this->error('信息不存在');
		}
		$this->assign('info', $info);
		
		$this->display();
	}
	
	/**
	 * 保存版本
	 */
	public function appSave() {
		$id = $this->post['id'];
		$version_num = safeString($this->post['version_num'], 'trim_space');
		$platform = $this->post['platform'];
		$is_need = $this->post['is_need'];
		$content = safeString($this->post['content'], 'trim');
		$content_en = safeString($this->post['content_en'], 'trim');
		$content_ko = safeString($this->post['content_ko'], 'trim');
		$number = $this->post['number'];
		
		if (!validateExtend($id, 'NUMBER')) {
			$this->error('缺少参数');
		}
		if (empty($version_num)) {
			$this->error('版本号不能为空');
		}
		if (!in_array($platform, $this->allow_platform)) {
			$this->error('未被允许的终端类型');
		}
		if ($is_need!='0' && $is_need!='1') {
			$this->error('未知的强制更新参数');
		}
		if (empty($content)) {
			$this->error('请填写更新描述');
		}
		
		$data = array();
		$platform_cn = '';
		
		$data['version_num'] = $version_num;
		$data['content'] = $content;
		$data['content_en'] = $content_en;
		$data['content_ko'] = $content_ko;
		$data['is_need'] = $is_need;
		$data['platform'] = $platform;
		$data['number'] = $number;
		
		//文件数据处理
		if ($platform == '1') { //安卓
			if (!empty($this->post['src'])) {
				if (!preg_match('/^(http|https){1,}/', $this->post['src'])) {
					$this->error('APP文件地址格式有误');
				} else {
					$data['src'] = $this->post['src'];
				}
			} else {
				$upload_config = array(
					'file' => $_FILES['src'],
					'exts' => 'apk',
					'path' => 'apk',
					'size' => 1024000*50,
				);
				$upload = new \Common\Controller\UploadController($upload_config);
				$upload_info = $upload->upload();
				if (empty($upload_info['error'])) {
					$data['src'] = U('/','','',true).$upload_info['data']['url'];
				}
			}
				
			$platform_cn = '安卓端';
				
		} elseif ($platform == '2') { //苹果
				
			$data['src'] = safeString($this->post['src'], 'trim');
			if (empty($data['src'])) {
				unset($data['src']);
			}
				
			$platform_cn = '苹果端';
				
		} else {
			$this->error('未知终端类型');
		}
		
		if ($this->apkManage->where('id='.$id)->save($data) === false) {
			$this->error('保存失败,请稍后重试');
		}
		
		$this->success('修改版本成功', U('Version/index'), false, "修改{$platform_cn}版本[{$version_num}]成功");
	}
	
	/**
	 * 删除版本
	 */
	public function appDelete() {
		$id = $this->get['id'];
		
		if (!validateExtend($id, 'NUMBER')) {
			$this->error('参数格式有误');
		}
		
		$map['id'] = array('eq', $id);
		$info = $this->apkManage->where($map)->field('version_num,platform')->find();
		if (!$info) {
			$this->error('该信息已不存在');
		}
		
		$version_num = $info['version_num'];
		$platform_cn = '';
		if ($info['platform'] == '1') {
			$platform_cn = '安卓端';
		} elseif ($info['platform'] == '2') {
			$platform_cn = '苹果端';
		}
		
		if ($this->apkManage->where($map)->delete() === false) {
			$this->error('删除失败');
		}
		
		$this->success('删除版本成功', U('Version/index'), false, "删除{$platform_cn}版本[{$version_num}]成功");
	}
	
	/**
	 * 检查版本号是否已存在
	 * 
	 * @param mixed $version_num 版本号
	 * @param mixed $platform 终端类型 
	 */
	private function isExistsVersion($version_num=false, $platform=false) {
		if (!$version_num || !$platform) {
			return false;
		}
		
		$map['version_num'] = array('eq', $version_num);
		$map['platform'] = array('eq', $platform);
		$apk_manage_info = $this->apkManage->where($map)->field('id')->find();
		if ($apk_manage_info) {
			return true;
		} else {
			return false;
		}
	}
	
}
?>