<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 备份钱包管理
// +----------------------------------------------------------------------
namespace System\Controller;
use Common\Controller\AuthController;

class BackupWalletController extends AuthController {
	
	public function __construct() {
		parent::__construct();
		
		Vendor("Wallet.wallet_backup");
	}
	
	public function index() {
		$action = I('get.action');
		$file = I('get.file');
		
		$Backup = new \WalletBackup();
		
		$error = false;
		$success = false;
		switch ($action) {
			case 'backup':
				if ($Backup->backup()) {
					$success = '钱包备份成功';
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
			$this->success($success, U('BackupWallet/index'), false);
			exit;
		}
		
		$list = $Backup->dataList();
		$this->assign('list', $list);
		
		$this->display();
	}
	
}
?>