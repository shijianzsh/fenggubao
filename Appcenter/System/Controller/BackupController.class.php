<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 数据库管理
// +----------------------------------------------------------------------
namespace System\Controller;
use Common\Controller\AuthController;
use Org\Util\MysqlBackup;

class BackupController extends AuthController {
	
	public function __construct() {
		parent::__construct();
		
		//$this->error('数据库管理功能已禁用');
		//exit;
	}
	
	public function index() {
		$action = I('get.action');
		$file = I('get.file');
		$folder = I('get.folder');
		
		$Backup = new \Org\Util\MysqlBackup($folder);
		$Backup->setDbName(C('DB_NAME'));
		
		$error = false;
		$success = false;
		switch ($action) {
			case 'backup':
				//if ($Backup->backup()) {
				if ($Backup->backupExecute()) {
					$success = '数据库备份成功';
				} else {
					$error = $Backup->error();
				}
			break;
			case 'optimize':
				if ($Backup->optimize()) {
					$success = '数据库优化成功';
				} else {
					$error = $Backup->error();
				}
			break;
			case 'recover':
				//$this->error('此功能出于系统稳定和安全性考虑暂不启用');
				//exit;
				
				if (!$file) {
					$error = '请选择要还原的数据库文件';
					break;
				}
				//$re = $Backup->recover($file);
				$re = $Backup->recoverExecute($file);
				if ($re) {
					if ($re['error']) {
						$error = $re['error'];
					} else {
						$success = "数据库还原成功[还原文件:{$file}]";
					}
				} else {
					$error = $Backup->error();
				}
			break;
			case 'Delete':
				if (!$file) {
					$error = '请选择要删除的数据库文件';
					break;
				}
				if ($Backup->remove($file)) {
					$success = "数据库备份文件:{$file} 删除成功";
				} else {
					$error = $Backup->error();
				}
			break;
			case 'download':
				if (!$file) {
					$error = '请选择要下载的已备份文件';
					break;
				}
				$Backup->downloadFile($file);
				$success = "下载备份文件:{$file}";
			break;
		}
		
		if ($error) {
			$this->error($error);
		}
		
		if ($success) {
			$this->logWrite($success);
			$this->success($success, U('Backup/index'), false);
			exit;
		}
		
		$list = $Backup->dataList();
		$this->assign('list', $list);
		
		$this->display();
	}
	
}
?>