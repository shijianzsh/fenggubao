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
namespace APP\Controller;
use Common\Controller\ApiController;

class UploadComController extends ApiController {
	
	/**
	 * 通用文件上传
	 * @param files  文件
	 * @param folder 文件夹
	 */
	public function upload() {
		$folder = I('post.folder');
		if($folder == ''){
		    $folder = 'product';
		}
		//处理图片
		$upload_config = array (
			'file' => 'multi',
			'exts' => array('jpg','png','gif','jpeg'),
			'path' => $folder.'/'. date('Ymd')
		);
		$Upload = new \Common\Controller\UploadController($upload_config);
		$info = $Upload->upload();
		if (!empty($info['error'])) {
			$this->myApiPrint('请上传文件！'.$info['error']);
		} else {
			if(empty($info['data'])){
				$this->myApiPrint('上传失败！');
			}
			if(count($info['data']) > 1){
				//多文件
				$return = array();
				foreach($info['data'] as $k=>$v){
					$dd['key'] = $v['key'];
					$dd['url'] = $v['url'];
					$return[] = $dd;
				}
				$this->myApiPrint('上传成功！',400, $return);
			}else{
				//单文件
				$return = array();
				foreach($info['data'] as $k=>$v){
					$dd['key'] = $v['key'];
					$dd['url'] = $v['url'];
					$return[] = $dd;
				}
				$this->myApiPrint('上传成功！',400, $return);
			}
		}
		
		$this->myApiPrint('上传成功！', 400);
	}
	
}
?>

