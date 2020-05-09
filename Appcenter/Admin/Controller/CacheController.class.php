<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 缓存管理 
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Common\Controller\AuthController;

class CacheController extends AuthController {
	
	protected $config;
	
	public function __construct($request='') {
		parent::__construct(false, $request);
		
		$this->config = array(
			array('temp'=>RUNTIME_PATH.'Cache/'),
			array('temp'=>RUNTIME_PATH.'Data/'),
			array('temp'=>RUNTIME_PATH.'Temp/'),
			array('temp'=>RUNTIME_PATH.'common~runtime.php')
		);
	}
	
	public function index() {
		$this->display();
	}
	
	/**
	 * 清除全部缓存
	 */
	public function clear() {
		$return = '';
		$status = false;
		foreach ($this->config as $list) {
			$path = explode('/', $list['temp']);
			$path = empty($path[count($path)-1]) ? $path[count($path)-2] : $path[count($path)-1];
			
			$Cache = new \Think\Cache\Driver\File($list);
			if ($Cache->clear()) {
				if (!$status) {
					$status = true;
				}
				$return .= "{$path}[ok] ";
			}
			else {
				$return .= "{$path}[no] ";
			}
		}
		if ($status) {
			$this->success($return, '', false, '清除缓存');
		}
		else {
			$this->error($return);
		}
	}
	
}
?>