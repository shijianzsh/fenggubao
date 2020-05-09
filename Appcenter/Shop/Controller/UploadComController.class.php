<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-3.0 )
// +----------------------------------------------------------------------
// | 通用文件上传接口
// +----------------------------------------------------------------------
namespace Shop\Controller;
use Common\Controller\AuthController;

class UploadComController extends AuthController {
	
	/**
	 * 通用文件上传
	 * @param files  文件
	 * @param folder 文件夹
	 */
	public function upload() {
		//处理图片
		$upload_config = array (
			'file' => 'multi',
			'exts' => array('jpg','png','gif','jpeg'),
			'path' => 'shop/'. date('Ymd')
		);
		$Upload = new \Common\Controller\UploadController($upload_config);
		$info = $Upload->upload();
		if (!empty($info['error'])) {
			$this->error('请上传文件！'.$info['error']);
		} else {
			if(empty($info['data'])){
				$this->error('上传失败！');
			}
			if(count($info['data']) > 1){
				//多文件
				$return = array();
				foreach($info['data'] as $k=>$v){
					$dd['key'] = $v['key'];
					$dd['filename'] = $v['url'];
					$return[] = $dd;
				}
				$this->success('上传成功！', $return);
			}else{
				//单文件
				foreach($info['data'] as $k=>$v){
					$return['key'] = $v['key'];
					$return['filename'] = $v['url'];
				}
				$this->success('上传成功！', $return);
			}
		}
		
	}
	
}
?>

