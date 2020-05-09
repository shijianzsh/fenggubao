<?php
require_once "wallet_config.php";

class WalletBackup {

	private $path;
	private $isCompress = 0;
	private $error;
	
	const DIR_SEP = '/';
	
	/**
	 * 构造函数
	 * 
	 * @param $folder string 文件夹名(主要用于浏览子文件夹文件)
	 */
	public function __construct() {
		$this->path = WalletConfig::BACKUP_PATH;
	}
	
	/**
	 * 备份
	 */
	public function backup() {
		//设置执行完毕页面进程才能结束
		set_time_limit(0);
		ignore_user_abort(true);
		
		Vendor("btc.btc_client");
		 
		$destination = $this->path.'/'.date('Y-m-d-H-i-s').'.dat';
		return \Api_Rpc_Client::backupUserWallet($destination);
	}
	
	/**
	 * 文件下载
	 * @param string $fileName
	 */
	public function downloadFile($fileName) {
		ob_end_clean();
 		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
 		header("Content-Description: File Transfer");
 		header("Content-Type: application/octet-stream");
 		header('Content-Transfer-Encoding: binary');
		
		$filePath = $this->path. self::DIR_SEP. $fileName;
		$stat = stat($filePath);
 		header("Content-Disposition: attachment; filename='". $fileName. "'");
 		header("Content-Length: ". $stat['size']);
		readfile($filePath);
	}
	
	/**
	 * 文件列表
	 */
	public function dataList() {
		$filePath = opendir($this->path);
		$fileAndFolderArray = array();
		
		while (($file = readdir($filePath)) !== false) {
			if ($file!='.' && $file!='..' && $file!='.svn') {
				$stat = stat($this->path. '/'. $file);
				$rt['filename'] = $file;
				$rt['filetime'] = date('Y-m-d H:i:s', $stat['mtime']);
				$rt['filesize'] = $stat['size'];
				$rt['filetype'] = filetype($this->path. '/'. $file);
				$fileAndFolderArray[] = $rt;
			}
		}
		
		krsort($fileAndFolderArray);
		return $fileAndFolderArray;
	}
	
	private function cetPath($fileName) {
		//$dirs = explode(self::DIR_SEP, dirname($fileName));
		//上面的处理方式会出现最后一个文件夹没有生成的情况
		$dirs = explode(self::DIR_SEP, $fileName);
		
		$tmp = '';
		foreach ($dirs as $dir) {
			$tmp .= $dir. self::DIR_SEP;
			if (!file_exists($tmp) && !@mkdir($tmp, 0777)) {
				return false;
			}
		}
		return true;
	}
	
	public function error() {
		return $this->error;
	}
    
}