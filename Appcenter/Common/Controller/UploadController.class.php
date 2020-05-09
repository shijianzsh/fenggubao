<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 上传文件通用类封装
// +----------------------------------------------------------------------
namespace Common\Controller;
use Think\Controller;

class UploadController extends Controller {
	
	protected $upload_config; //上传文件配置信息
	protected $device_config; //上传驱动配置
	protected $upload_file; //上传文件 当值为multi时,为多文件上传
	protected $return = array('error'=>'', 'data'=>''); //返回数据
	protected $device = array('Local', 'Ftp', 'Sae', 'Bc', 'Qiniu', 'Oss'); //允许驱动方式
	protected $domain; //指定域名
	
	/**
	 * 构造方法
	 * @param array $upload_config 上传配置信息
	 * 格式:
	 * array (
	 *     'file' => '', //file 导入文件: $_FILES['file']
	 *     'exts' => '', //array 允许格式: array('xls', 'doc', ...),此项可无,默认为array('jpg','png','gif','jpeg')
	 *     'path' => '', //string 保存路径: 此项可无,默认为2015/05/15/格式
	 *     'size' => '', //int 允许上传文件大小: 此项可无,默认为10240000
	 *     'device' => '', //上传驱动方式：默认为Local(可选:Local,Ftp,Sae,Bcs,七牛,又拍云)
	 *     'root' => '', //string 保存根路径：此项在device为Local模式时建议无
	 * )
	 * @param mixed $device_config 驱动配置信息
	 * 格式:(此格式视具体驱动方式而定)
	 * array (
	 *     'device' => 'Ftp', //驱动方式
	 *     'host' => '', //服务器IP
	 *     'port' => '', //端口,如:21
	 *     'timeout' => '', //超时时间,如:90
	 *     'username' => '', //用户名
	 *     'password' => '', //密码
	 * )
	 */
	public function __construct($upload_config, $device_config=false) {
		parent::__construct();
		
		if (!isset($upload_config['file'])) {
			$this->return['error'] = L('file').L('no_null');
			return $this->$return;
		}
		
		$this->upload_config = array(
//			'maxSize' => isset($upload_config['size']) ? $upload_config['size'] : 1024*1024*1024*1024*20,
			'exts' => isset($upload_config['exts']) ? $upload_config['exts'] : array('jpg', 'png', 'gif', 'jpeg'),
			'savePath' => isset($upload_config['path']) ? $upload_config['path'].'/' : date('Y').'/'.date('m').'/'.date('d').'/',
			'autoSub' => false,
			'saveName' => array('getMd5', array('__FILE__')),
		);
		
		//判断附件分离是否开启,如果已开启且$device_config=false,则自动加载配置驱动参数
		$this->device_config = $device_config;
		if (C('ATTACH_SEPARATION_ON') && !$device_config) {
			$this->device_config =  C('DEVICE_CONFIG')[C('DEVICE_CONFIG_DEFAULT')];
		}
		
		$this->upload_file = $upload_config['file'];
		
		//非通用扩展配置
		isset($upload_config['root']) && $this->upload_config['rootPath'] = $upload_config['root'];
		$this->domain = isset($device_config['domain']) ? $device_config['domain'] : '';
	}
	
	/**
	 * 文件上传
	 * @return array(
	 *     'key' => '附件上传表单名称',
	 *     'savepath' => '上传文件保存路径',
	 *     'name' => '上传文件原始名',
	 *     'savename' => '上传文件保存名',
	 *     'size' => '上传文件大小',
	 *     'type' => '上传文件的MIME类型',
	 *     'ext' => '上传文件的后缀类型',
	 *     'md5' => '上传文件的md5验证字符串',
	 *     'sha1' => '上传文件的sha1验证字符串',
	 * )
	 */
	public function upload() {
		if (!$this->device_config) {
			$upload = new \Think\Upload($this->upload_config);
		}
		else {
			if (!in_array($this->device_config['device'], $this->device)) {
				$this->return['error'] = '不支持的上传驱动方式';
				return $this->return;
			}
			$device = $this->device_config['device'];
			unset($this->device_config['device']);
			$upload = new \Think\Upload($this->upload_config, $device, $this->device_config);
		}
		
		//加工处理domain
		$this->domain = empty($this->domain) ? str_replace('.', '', $upload->rootPath) : $this->domain. str_replace('.', '', $upload->rootPath);
		
		if ($this->upload_file == 'multi') {
			$data = $upload->upload();
			if (!$data) {
				$this->return['error'] = $upload->getError();
				return $this->return;
			}
			foreach ($data as $k=>$file) {
				$data[$k]['url'] = $this->domain. $file['savepath']. $file['savename'];
			}
		}
		else {
			$data = $upload->uploadOne($this->upload_file);
			if (!$data) {
				$this->return['error'] = $upload->getError();
				return $this->return;
			}
			$data['url'] = $this->domain. $data['savepath']. $data['savename'];
		}
		
		$this->return['data'] = $data;
		
		return $this->return;
	}

}
?>