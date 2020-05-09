<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------

namespace Think\Upload\Driver;
#require_once dirname(__FILE__)."/Oss/aliyun-oss-php-sdk.phar";

use Think\Upload\Driver\OSS\OssClient;
#use OSS\Core\OssException;

class Oss {
	
    /**
     * 上传文件根目录
     * @var string
     */
    private $rootPath;

    /**
     * 上传错误信息
     * @var string
     */
    private $error = ''; //上传错误信息    

    public $config = array(
    	'accessKeyId'     => '', //您从OSS获得的AccessKeyId
    	'accessKeySecret' => '', //您从OSS获得的AccessKeySecret
    	'endpoint'        => '', //您选定的OSS数据中心访问域名，例如http://oss-cn-hangzhou.aliyuncs.com
    	'bucket'          => '', //您使用的Bucket名字，注意命名规范
    );

    private $oss = null;

    /**
     * 构造函数，用于设置上传根路径
     * @param string $root 根目录
     */
	public function __construct($config = null){
        $this->config = array_merge($this->config, $config);
        $this->oss = new OssClient($this->config['accessKeyId'], $this->config['accessKeySecret'], $this->config['endpoint']);
	}

    /**
     * 检测上传根目录
     * @return boolean true-检测通过，false-检测失败
     */
    public function checkRootPath($rootpath){
		 $this->rootPath = trim($rootpath, './') . '/';
		 return true;
    }    

    /**
     * 检测保存目录
     * @param  string $savepath 上传目录
     * @return boolean          检测结果，true-通过，false-失败
     */
	public function checkSavePath($savepath){
		return true;
    }

    /**
     * 创建文件夹 
     * @param  string $savepath 目录名称
     * @return boolean          true-创建成功，false-创建失败
     */
    public function mkdir($savepath){
    	return true;
    }

    /**
     * 保存指定文件
     * @param  array   $file    保存的文件信息
     * @param  boolean $replace 同名文件是否覆盖
     * @return boolean          保存状态，true-成功，false-失败
     */
    public function save(&$file,$replace=true) {
    	$fileName = $this->rootPath.$file['savepath'].$file['savename'];
    	$filePath = $file['tmp_name'];

    	try {
			$this->oss->uploadFile($this->config['bucket'], $fileName, $filePath);
			$file['url'] = $this->domain."/". $fileName;
			return true;
		} catch (OssException $e) {
			$this->error = $e->getMessage();
			return false;
		}
    }

    /**
     * 获取最后一次上传错误信息
     * @return string 错误信息
     */
    public function getError(){
        return $this->error;
    }
    
}
