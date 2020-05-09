<?php

namespace V4\Model;

/**
 * 客服平台模型
 */

class CustomerServiceModel extends BaseModel {
    public $platform;
	protected $platform_name;
	protected $platform_config;
	protected $target_id;
	protected $current_id;
	
	/**
	 * 构造函数
	 * 
	 * @param int $current_id 当前用户ID
	 * @param int $target_id 目标用户ID
	 */
	public function __construct($target_id, $current_id) {
	    if (!empty($target_id) && !empty($current_id)) {
		  $this->setPlatform($current_id, $target_id);
	    }
		$this->platform = [
			'easemob' => '互动云客服',
			'kf5' => '逸创云客服',
		];
	}
	
	/**
	 * 设置平台名称和配置信息
	 * 
	 * @param int $current_id 当前用户ID
	 * @param int $target_id 目标用户ID
	 */
	private function setPlatform($current_id, $target_id) {
		$user_info = M('UserAffiliate')->where('user_id='.$target_id)->field('service_platform_id,service_platform_config')->find();
		if ($user_info) {
			$this->platform_config = $user_info['service_platform_config'];
			
			if (!empty($user_info['service_platform_id'])) {
				$platform_info = M('CustomerServicePlatform')->where('platform_id='.$user_info['service_platform_id'])->field('platform_name')->find();
				if ($platform_info) {
					$this->platform_name = $platform_info['platform_name'];
				}
			}
		}
		
		$this->target_id = $target_id;
		$this->current_id = $current_id;
	}

    /**
     * 初始化客服平台
     * 
     * @internal 使用前需先设置平台名称和配置信息
     * 
     * @return mixed 当初始化失败或无返回信息时返回false,否则返回所需信息
     */
	public function init() {
		//当没有启用配置中的可用客服平台时，默认启用即时通讯
		if (!array_key_exists($this->platform_name, $this->platform)) {
			return $this->chat();
		}
		
		$platform_config = explode('\r\n', $this->platform_config);
		$platform_config_new = [];
		foreach ($platform_config as $k=>$v) {
			if (preg_match('/=/', $v)) {
				$eq_first = stripos($v, '=');
				$key = substr($v, 0, $eq_first);
				$value = substr($v, $eq_first+1);
				$platform_config_new[$key] = $value;
			}
		}
		$this->platform_config = $platform_config_new;
		
		$method = $this->platform_name;
		return $this->$method();
	}
	
	/**
	 * 即时聊天
	 */
	private function chat() {
		return U("Im/index/current_id/{$this->current_id}/target_id/{$this->target_id}", '', true, true);
	}
	
	/**
	 * kf5平台
	 */
	private function kf5() {
		if (array_key_exists('url', $this->platform_config)) {
			return $this->platform_config['url'];
		}
		
		return false;
	}
	
	/**
	 * 互动云客服平台
	 */
	private function easemob() {
		if (array_key_exists('configId', $this->platform_config)) {
			return 'https://kefu.easemob.com/webim/im.html?configId='.$this->platform_config['configId'];
		}
		
		return false;
	}
	
}
