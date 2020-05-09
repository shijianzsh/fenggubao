<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
namespace Org\Util;
use Think\Db;

class MysqlBackup {

	private $path;
	private $isCompress = 0;
	private $content;
	private $dbName;
	private $tableName;
	private $error;
	private $sign = "\r\n /*++$*/ \r\n";
	
	const DIR_SEP = DIRECTORY_SEPARATOR;
	
	private $db_pwd;
	
	/**
	 * 构造函数
	 * 
	 * @param $folder string 文件夹名(主要用于浏览子文件夹文件)
	 */
	public function __construct($folder='') {
		$folder = empty($folder) ? '' : '/'.$folder;
		$this->path = $_SERVER['DOCUMENT_ROOT']. C('UPLOAD_PATH'). '/sql'. $folder;
		$this->tableName = false;
		
		//如果数据库密码启用加密模式，则解析之
		$this->db_pwd = C('DB_PWD');
		if (C('DB_PWD_SAFE')) {
			$Crypt = new \Think\Crypt\Driver\Think();
			$this->db_pwd = $Crypt->decrypt($this->db_pwd, C('ADMINISTRATOR_VAR'), true);
		}
	}
	
	/**
	 * 设置要备份的数据库名
	 * @param string $dbName
	 */
	public function setDbName($dbName) {
		$this->dbName = $dbName;
	}
	
	/**
	 * 设置备份文件保存路径
	 * @param string $path
	 */
	public function setPath($path) {
		$this->path = $path;
	}
	
	/**
	 * 设置是否启用压缩
	 * @param string $isCompress
	 */
	public function setIsCompress($isCompress) {
		$this->isCompress = $isCompress;
	}
	
	/**
	 * 备份外部命令版
	 */
	public function backupExecute() {
		
		$filePath = $this->path. '/'. date('Ymd');
		
		$path = $this->cetPath($filePath);
		if ($path !== true) {
			$this->error = '无法创建备份目录:'.$path;
			return false;
		}
		
		$mysqldump = array();
		
		if (C('DB_BACKUP_RDS')) {
			$bak_table_list = C('DB_BACKUP_TABLE_LIST');
			if (!is_array($bak_table_list) || count($bak_table_list)==0) { //当无数据库表备份配置参数时,备份全表
				$fileName = $filePath.'/'.$this->dbName.'-'.date('YmdHis').'.sql';
				$mysqldump[] = C('MYSQL_BIN_PATH').'mysqldump.exe --no-defaults -h'.C('DB_HOST').' -u'.C('DB_USER').' -P3306 -p'.$this->db_pwd.' --hex-blob --skip-lock-tables --ignore-table='.C('DB_NAME').'.zc_city_country --ignore-table='.C('DB_NAME').'.zc_favorite_member_product --ignore-table='.C('DB_NAME').'.zc_favorite_member_store --ignore-table='.C('DB_NAME').'.zc_first_menu_store --ignore-table='.C('DB_NAME').'.zc_flash_news_store --ignore-table='.C('DB_NAME').'.zc_orders_member --ignore-table='.C('DB_NAME').'.zc_orders_member_1 --ignore-table='.C('DB_NAME').'.zc_orders_store --ignore-table='.C('DB_NAME').'.zc_orders_store_product --ignore-table='.C('DB_NAME').'.zc_orders_store_product_a --ignore-table='.C('DB_NAME').'.zc_product_preferential_way --ignore-table='.C('DB_NAME').'.zc_product_store --ignore-table='.C('DB_NAME').'.zc_view_member_bonus --ignore-table='.C('DB_NAME').'.store_product_activity '.C('DB_NAME').' > '.$fileName;
			} else {
				foreach ($bak_table_list as $k=>$table) {
					$table_bak = $table['table'].'-'.date('YmdHis');
					$fileName = $filePath.'/'.$this->dbName.'-'.$table_bak.'.sql';
					$mysqldump[] = C('MYSQL_BIN_PATH').'mysqldump.exe --no-defaults -h'.C('DB_HOST').' -u'.C('DB_USER').' -P3306 -p'.$this->db_pwd.' --hex-blob --skip-lock-tables '.$table['where'].' '.C('DB_NAME').' '.$table['table'].' > '.$fileName;
				}
			}
		} else {
			$fileName = $filePath.'/'.$this->dbName.'-'.date('YmdHis').'.sql';
			$mysqldump[] = C('MYSQL_BIN_PATH').'mysqldump.exe -u'.C('DB_USER').' -p'.$this->db_pwd.' -h'.C('DB_HOST').' '.C('DB_NAME').' --single-transaction --ignore-table=mysql.event > '.$fileName;
		}
		
		foreach ($mysqldump as $dump) {
			system($dump, $status);
			if ($status != '0') {
				$this->error = '数据库备份失败,返回码:'.$status;
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * 备份
	 */
	public function backup() {
		//设置执行完毕页面进程才能结束
		set_time_limit(0);
		ignore_user_abort(true);
		
		$db = Db::getInstance();
		
		//$db->query("set names character utf8"); //client|result|connection
		//$db->query("set character set utf8"); //connection
		
		//查看存储过程
		$tables['procedure'] = $db->query('SHOW PROCEDURE STATUS');
		$tables['procedure'] = array_map('array_change_key_case', $tables['procedure']);
		
		//查看触发器
		$tables['triggers'] = $db->query('SHOW TRIGGERS');
		$tables['triggers'] = array_map('array_change_key_case', $tables['triggers']);
		
		//查看事件
		$tables['events'] = $db->query('SHOW EVENTS');
		$tables['events'] = array_map('array_change_key_case', $tables['events']);
		
		//查看表和视图
		$tables['table'] = $db->query('SHOW TABLE STATUS');
		$tables['table'] = array_map('array_change_key_case', $tables['table']);
		
		$sql = '/* This file is created by MysqlBackup '. date('Y-m-d H:i:s'). ' */';
		
		$fileName = false; //第一次遍历保存成功后生成的文件名及路径,用于继续遍历时追加数据至同一文件
		
		foreach ($tables as $type=>$tables_list) { //$type:procedure/triggers/events/table_and_view
			foreach ($tables_list as $key=>$value) {
				if ($fileName) {
					$sql = '';
				}
				
				switch ($type) {
					case 'procedure':
						$table = $value['name'];
						$result = $db->query("SHOW CREATE PROCEDURE `{$table}`");
						$create = $result[0]['create procedure'];
						$sql .= "\r\n\r\n /* 创建存储过程 {$table} */";
						$sql .= "\r\n DROP PROCEDURE IF EXISTS {$table};". $this->sign;
						$sql .= "\r\n {$create};". $this->sign;
						break;
					case 'triggers':
						$table = $value['trigger'];
						$create = $value['statement'];
						$sql .= "\r\n\r\n /* 创建触发器 {$table} */";
						$sql .= "\r\n DROP TRIGGER IF EXISTS {$table};". $this->sign;
						$sql .= "\r\n {$create};". $this->sign;
						break;
					case 'events':
						$table = $value['name'];
						$result = $db->query("SHOW CREATE EVENT `{$table}`");
						$create = $result[0]['create event'];
						$sql .= "\r\n\r\n /* 创建事件 {$table} */";
						$sql .= "\r\n DROP EVENT IF EXISTS {$table};". $this->sign;
						$sql .= "\r\n {$create};". $this->sign;
						break;
					case 'table':
						$table = $value['name'];
						$table_type = $value['comment'];
						if ($table_type == 'VIEW') {
							//创建视图
							$result = $db->query("SHOW CREATE VIEW `{$table}`");
							$create = $result[0]['create view'];
							$sql .= "\r\n\r\n /* 创建视图结构 {$table} */";
							$sql .= "\r\n DROP VIEW IF EXISTS {$table};". $this->sign;
							$sql .= "\r\n {$create};". $this->sign;
						} else {
							//创建表
							$result = $db->query("SHOW CREATE TABLE `{$table}`");
							$create = $result[0]['create table'];
							$sql .= "\r\n\r\n /* 创建表结构 {$table} */";
							$sql .= "\r\n DROP TABLE IF EXISTS {$table};". $this->sign;
							$sql .= "\r\n {$create};". $this->sign;
							//插入数据
							$result = $db->query("SELECT COUNT(*) AS count FROM `{$table}`");
							$count = $result[0]['count'];
							if ($count) {
								$sql .= "\r\n /* 插入数据 {$table} */";
								
								//每次存入100条数据
								$perpage = 100;
								$page = 0;
								$limit = $page*$perpage. ','. $perpage;
								$page_num = ceil($count/$perpage);
								for ($i=0; $i<$page_num; $i++) {
									$result = $db->query("SELECT * FROM {$table} LIMIT {$limit}");
									
									foreach ($result as $row) {
										$row = array_map('addslashes', $row);
										$sql_temp .= "\r\n INSERT INTO {$table} VALUES ('". str_replace(array("\r","\n"), array('\r','\n'), implode("', '", $row)). "');". $this->sign;
									}
									
									if ($sql_temp) {
										$content = $page>0 ? $sql_temp : $sql. $sql_temp;
										$status = $this->setFile($table, $content);
										if (!$status) {
											$this->error = $status;
											return false;
										} else {
											unset($result);
											unset($sql_temp);
										}
									}
									
									$page++;
									
									sleep(1);
								}
							}
							
							continue;
						}
						break;
					default:
						$this->error = '未知类型数据';
				}
				
				if ($sql) {
					$status = $this->setFile($table, $sql);
				
					if (!$status) {
						$this->error = $status;
						return false;
					} else {
						//清除内存中的已被保存,无用的变量缓存
						unset($tables_list[$key]);
						unset($result);
						unset($sql);
					}
				} else {
					$this->error = '数据为空';
					return false;
				}
				
			}
			
			unset($tables[$type]);
		}
		
		return true;
	}
	
	/**
	 * 删除备份
	 * @param string $fileName
	 */
	public function remove($fileName) {
		if (!@unlink($this->path. self::DIR_SEP. $fileName)) {
			$this->error = '删除失败';
			return false;
		}
		return true;
	}
	
	/**
	 * 表优化
	 */
	public function optimize() {
		$db = Db::getInstance();
		$list = $db->query('SHOW TABLE STATUS');
		$list = array_map('array_change_key_case', $list);
		
		$tables = false;
		foreach ($list as $key=>$value) {
			$tables[] = $value['name'];
		}
		
		if ($tables) {
			if (is_array($tables)) {
				$tables = implode('`,`', $tables);
				$list[] = $db->execute("OPTIMIZE TABLE `{$tables}`");
			}
		}
		
		return $list;
	}
	
	/**
	 * 还原外部命令版
	 */
	public function recoverExecute($fileName) {
		//开启后台执行
		set_time_limit(0);
		ignore_user_abort(true);

		$fileName = $this->path. '/'. $fileName;
		
		if (!file_exists($fileName)) {
			$this->error = '待还原文件不存在';
		}
		
		$mysqldump = C('MYSQL_BIN_PATH').'mysql.exe -u'.C('DB_USER').' -p'.$this->db_pwd.' -h'.C('DB_HOST').' '.C('DB_NAME').' < '.$fileName;
		system($mysqldump, $status);
		if ($status == '0') {
			return true;
		} else {
			$this->error = '数据库还原失败,返回码:'.$status;
			return false;
		}
		
	}
	
	/**
	 * 表还原恢复
	 * @param string $fileName
	 */
	public function recover($fileName) {
		$content = $this->getFile($fileName);
		
		if (!$content) {
			if ($this->error) {
				return false;
			}
			$this->error = '数据为空';
			return false;
		}
		
		$content = explode($this->sign, $content);
		$db = Db::getInstance();
		
		$rt = false;
		$rs = array('qty'=>0, 'error'=>'');
		foreach ($content as $i=>$sql) {
			$sql = trim($sql);
			if (!empty($sql)) {
				$res = $db->execute($sql);
				
				if ($res) {
					$rs['qty']++;
				} else {
					$rt[] = $sql;
				}
			}
		}
		
		$rs['error'] = $rt ? implode("\r\n", $rt) : $rt;
		return $rs;
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
	
	private function getFile($fileName) {
		$fileName = $this->path. self::DIR_SEP. $fileName;
		if (is_file($fileName)) {
			$ext = strrchr($fileName, '.');
			switch ($ext) {
				case '.sql':
					return file_get_contents($fileName);
				break;
				case '.gz':
					return implode('', gzfile($fileName));
				break;
				default:
					$this->error = '无法识别的文件格式';
					return false;	
			}
		} else {
			$this->error = '文件不存在';
			return false;
		}
	}
	
	private function setFile($table, $content) {
		if (empty($table)) {
			$this->error = '表名未指定';
			return false;
		}
		
		$recognize = $this->dbName. self::DIR_SEP. $table;
		$fileName = date('YmdH'). self::DIR_SEP. $recognize. '.sql';
		$fileName = $this->path. self::DIR_SEP. $fileName;
		
		$path = $this->cetPath($fileName);
		if ($path !== true) {
			$this->error = '无法创建备份目录:'.$path;
			return false;
		}
		
		if ($this->isCompress == 0) {
			if (!file_put_contents($fileName, $content, FILE_APPEND)) {
				$this->error = '写入文件失败，请检查磁盘空间或权限';
				return false;
			}
		} else {
			if (function_exists('gzwrite')) {
				$fileName .= '.gz';
				$gz = gzopen($fileName, 'wb');
				if ($gz) {
					$gw = gzwrite($gz, $content);
					gzclose($gz);
				} else {
					$this->error = '写入文件失败，请检查磁盘空间或权限';
					return false;
				}
			} else {
				$this->error = '没有开启gzip扩展';
				return false;
			}
		}
		
		return $fileName;
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