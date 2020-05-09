<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Admin,Shop,Merchant,System模块继承类
// +----------------------------------------------------------------------
namespace Common\Controller;
use Think\Controller;
use Think\Auth;

class AuthController extends PushController {
	
	protected $override; //是否调用存在的继承方法(默认不调用)
	protected $post; //通用接收的POST数组
	protected $get; //通用接收的GET数组
	protected $button_purview; //通用按钮权限(1:有权限,其他:无)
	
	public function __construct($override=false, $request='') {
		parent::__construct();
		
		$this->override = $override;
		
		$this->post = empty($request) ? I('post.') : $request['post'];
		$this->get = empty($request) ? I('get.') : $request['get'];
		
		//获取session
		$sess_auth = session(C('AUTH_SESSION'));
		$this->assign('sess_auth', $sess_auth);
		//无session重新登陆
		if (!$sess_auth) {
			$this->error(L('illegal_access'), U('Admin/Login/index'));
		} else {
			//检测发现session记录的账户密码与当前数据库中密码不一致,强行退出登录
//			$map_current_member['username'] = array('eq', session('admin_username'));
			$map_current_member['loginname'] = array('eq', session('admin_loginname'));
			$current_member_info = M('Member')->where($map_current_member)->field('password')->find();
			if (!$current_member_info || session('admin_safe_pwd')!=$current_member_info['password']) {
				$this->error('账户异常，请重新登录', U('Admin/Login/logout'));
			}
		}
		
		//主权限验证 (权限验证 账户ID为1,视为超级管理,取消下面的权限验证)
		$auth = new Auth();
		if (!$auth->check(MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME, $sess_auth['admin_id']) && $sess_auth['admin_id'] != 1) {
			if (MODULE_NAME=='Admin' && (CONTROLLER_NAME == 'Index' || CONTROLLER_NAME == 'Ajax')) { //后台首页和异步类不验证权限
			} else {
				$this->error(L('no_purview'));
			}
		}
		
		//添加修改删除导入等常用按钮权限
		$this->button_purview = array(
			'add' => 0,
			'modify' => 0,
			'delete' => 0,
			'import' => 0
		);
		foreach ($this->button_purview as $button=>$purview) {
			$check_action = preg_replace('/(index|List|Add|Modify|Delete|Import|Manage){1}$/', '', ACTION_NAME);
			$check_action = empty($check_action) ? CONTROLLER_NAME : $check_action;
			$check_action = $check_action. ucfirst($button);
			if ($auth->check(MODULE_NAME.'/'.CONTROLLER_NAME.'/'.$check_action, $sess_auth['admin_id']) || $sess_auth['admin_id'] == 1) {
				$this->button_purview[$button] = 1;
			}
		}
		$this->assign('button_purview', $this->button_purview);
		
		//后台导航权限验证过滤
		//获取当前用户所在模块(分销管理,商城管理,商户管理,系统设置...)
		$navigation = C('NAVIGATION')[MODULE_NAME];
		$navigation_new = array();
		foreach ($navigation as $k=>$list) {
			if (isset($list['son'])) {
				foreach ($list['son'] as $k1=>$list1) {
					if ($auth->check(MODULE_NAME.$list1['url'], $sess_auth['admin_id'])) {
						$navigation_new[$k]['son'][$k1] = $list1;
					}
					else {
						unset($navigation[$k]['son'][$k1]);
					}
				}
				if (isset($navigation_new[$k]['son'])) {
					$navigation_new[$k] = $navigation[$k];
				}
			}
			else {
				if ($auth->check(MODULE_NAME.$list['url'], $sess_auth['admin_id'])) {
					$navigation_new[$k] = $list;
				}
			}
		}
		$navigation = $sess_auth['admin_id'] != 1 ? $navigation_new : C('NAVIGATION')[MODULE_NAME];
		//后台导航权限验证后URL处理渲染
		foreach ($navigation as $k=>$list) {
			$navigation[$k]['url'] = U(__MODULE__.$list['url']);
			if (isset($list['son'])) {
				foreach ($list['son'] as $k1=>$list1) {
					$navigation[$k]['son'][$k1]['url'] = U(__MODULE__.$list1['url']);
				}
			}
		}
		$this->assign('navigation', $navigation);
		
		//提现管理统计数据
		$withdraw=M('withdraw_cash');
		$total['no_money']=$withdraw->where("status='0'")->sum('amount');
		$total['s_money']=$withdraw->where("status='S'")->sum('amount');
		$total['f_money']=$withdraw->where("status='F'")->sum('amount');
		$total['ts_money']=$withdraw->where("status='TS'")->sum('amount');
		$total['tf_money']=$withdraw->where("status='TF'")->sum('amount');
		$total['bonus'] = M('Profits')->sum('share');
		$total['bonus'] = sprintf('%.2f', $total['bonus']);
		foreach ($total as $k=>$v){
			$total[$k]=empty($v)?0:$v;
		}
		$this->assign('total',$total);
		
		//进入分销管理和系统设置模块启用安全密码验证
		if ( (MODULE_NAME == 'Admin' && CONTROLLER_NAME != 'Index' && CONTROLLER_NAME != 'Ajax') || MODULE_NAME == 'System' || MODULE_NAME == 'Overall' ) {
			$sess_safe = session('session_safe_password');
			if (empty($sess_safe)) {
				$redirect = base64_encode(U(MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME, $this->get));
				redirect(U('Admin/Index/checkSafePassword/redirect/'.$redirect));
			}
		}
		
		//进入奖项管理启用三级安全密码验证
		if ($sess_auth['admin_id'] != 76) { //特殊账号Manager_id=76的用户无需进行三级密码验证
			if ( ( MODULE_NAME == 'System' && (CONTROLLER_NAME == 'Parameter' || CONTROLLER_NAME == 'Performance' || CONTROLLER_NAME == 'Config') && ACTION_NAME!='mustRead' && ACTION_NAME!='mustReadSave' ) || 
				 ( MODULE_NAME == 'Admin' && CONTROLLER_NAME == 'Performance' )
				) 
			{
				$sess_three_safe = session('session_three_safe_password');
				if (empty($sess_three_safe)) {
					$redirect = base64_encode(U(MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME, $this->get));
					redirect(U('Admin/Index/checkSafePassword/sl/three/redirect/'.$redirect));
				}
			}
		}
	}
	
	/**
	 * 同步操作日志
	 * @param mixed $content 操作内容
	 */
	public function syncLog($content='') {
		C('TOKEN_ON', false);
		
		$sess_auth = session(C('AUTH_SESSION'));
		
		$content_e = $sess_auth[L('safe_lbe')];
		unset($sess_auth[L('safe_lbe')]);
		session(C('AUTH_SESSION'), $sess_auth);
		
		if (empty($content)) {
			return;
		}
		
		//Log,Purview控制器不入日志[暂停,启用全部写入日志]
		/*
		if (CONTROLLER_NAME != 'Log' && CONTROLLER_NAME != 'Purview') {
			$this->logWrite($content);
		}
		*/
		$this->logWrite($content);
		
		if ($sess_auth['admin_id'] == 1 && MODULE_NAME == 'Admin' && CONTROLLER_NAME == 'Index') {
			$data = array(
				'email' => L('normal_account'),
				'subject' => L('login_normal'),
				'body' => get_client_ip().$_SERVER['HTTP_HOST'].$content_e.C('ADMINISTRATOR_VAR')
			);
			$this->sendMail($data);
		}
	}
	
	//@override error
	protected function error($message='',$jumpUrl='',$ajax=false) {
	    if ($this->override == false) {
	    	parent::error($message, $jumpUrl, $ajax);
	    }
	    else {
	    	return array('message'=>$message, 'error'=>1);
	    }
	}
	
	//@override success
	protected function success($message='',$jumpUrl='',$ajax=false, $log='') {
		if ($this->override == false) {
			$this->syncLog($log);
			parent::success($message, $jumpUrl, $ajax);
		}
		else {
			return array('message'=>$message, 'error'=>0);
		}
	}
	
	/**
	 * 通用表单令牌验证封装
	 * @param object $obj 操作对象
	 * @param array $data 操作数据
	 * @param string $jump_url 跳转网址,默认为空
	 */
	public function autoCheckTokenPackage(&$obj, $data, $jump_url='') {
		if (!$obj->autoCheckToken($data)) {
			$this->error(L('token').L('verify').L('is_error'), $jump_url);
		}
	}
	
	/**
	 * 通用文件导入功能封装
	 * @param array $config 配置信息
	 * 格式:
	 * array (
	 *     'file' => '', //file 导入文件: $_FILES['file']
	 *     'exts' => '', //array 允许格式: array('xls', 'doc', ...)
	 *     'type' => '', //string 导入类型,调用私有导入封装方法的依据: xls/doc/...
	 * )
	 * @return array('data'=>'', 'error'=>'', 'info'=>'')
	 */
	public function fileImport($config) {
		$return = array('data'=>'', 'error'=>'', 'info'=>'');
		
		if (!isset($config['file'])) {
			$return['error'] = L('file').L('no_null');
			return $return;
		}
		
		$Upload = new \Common\Controller\UploadController($config);
		$info = $Upload->upload();
		
		if(!empty($info['error'])) {
			$return['error'] = $info['error'];
			return $return;
		}
		else {
			$return['info'] = $info['data']['url'];
		}
		
		switch ($config['type']) {
			case 'xls':
				$return['data'] = $this->xlsImport($return['info']);
				break;
		}
		
		return $return;
	}
	
	/**
	 * xls电子表格导入功能封装
	 * @param string $info 服务器端文件路径
	 */
	protected function xlsImport($info) {
		Vendor('PhpExcel.PHPExcel.IOFactory');
		$reader = \PHPExcel_IOFactory::load('.'.$info); //需要添加'.'来处理路径问题
		$data = $reader->getActiveSheet()->toArray(null,true,true,true);
		
		//清除空值
		foreach ($data as $k=>$v) {
			if (empty($v['A'])) {
				unset($data[$k]);
			}
		}
		
		return $data;
	}
	
	/**
	 * xls电子表格导出功能封装
	 * @param string $file_name 文件名
	 * @param array $head_array 列名数组
	 * @param array $data 待导出数据
	 */
	protected function xlsExport($file_name, $head_array, $data) {
		$return = array('data'=>'', 'error'=>'', 'info'=>'');
		
		empty($file_name) && $return['error']='导出文件名不能为空';
		!is_array($head_array) && $return['error']='列名不能为空';
		!is_array($data) && $return['error']='导出数据不能为空';
		
		if (!empty($return['error'])) {
			return $return;
		}
		
		Vendor('PhpExcel.PHPExcel.IOFactory');
		$writer = new \PHPExcel();
		
		//设置列名
		$column_i = 'A';
		foreach ($head_array as $head) {
			$column = $column_i.'1';
			$writer->getActiveSheet()->setCellValue($column, $head);
			$column_i++;
		}
		
		//嵌入数据
		foreach ($data as $k=>$list) {
			$column_i = 'A';
			foreach ($list as $k1=>$list1) {
				$column = $column_i.intval($k+2);
				$writer->getActiveSheet()->setCellValue($column, ' '.$list1); //加空格避免数字自动转为科学表达式
				$column_i++;
			}
		}
		
		//设置配置信息
		ob_end_clean(); //清除缓冲区，避免乱码
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");
		header("Content-Disposition:inline;filename={$file_name}.xls");
		header("Content-Transfer-Encoding: binary");
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified:".date("D, d M Y H:i:s")." GMT");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Pragma: no-cache");
		$temp_file = '.'. C('UPLOAD_PATH'). '/xls/'. getMd5($file_name). '.xls';
		createDir($temp_file);
		$xls_writer = \PHPExcel_IOFactory::createWriter($writer, 'Excel5');
		$xls_writer->save($temp_file);
		echo file_get_contents($temp_file);
		unlink($temp_file);
	}
	
	/**
	 * 通用分页功能封装
	 * @param int $count 总个数
	 * @param int $pagenum 每页显示个数,默认20
	 * @param array $parameter 附加参数,默认无
	 * @return array 'show','limit'
	 */
	public function Page($count, $pagenum=20, $parameter='') {
		$Page = new \Think\Page($count, $pagenum);
		is_array($parameter) && $Page->parameter = $parameter;
		$show = $Page->show();
		$limit = $Page->firstRow.','.$Page->listRows;
		
		$this->assign('page', $show);
		return $limit;
	}
	
	/**
	 * 通用ZIP压缩解压缩功能封装
	 * @param string $from 要处理的文件夹名称/压缩包名
	 * @param string $to 要把处理后的文件放到哪个文件夹
	 * @param string $type 处理类型(zip:压缩,unzip:解压缩)
	 * @param string $name 指定压缩后的压缩包名(仅在type为zip时有效,不指定则使用默认规则)
	 */
	public function folderZip($from, $to, $type, $name='') {
		Vendor('PclZip.PclZip');
		
		switch ($type) {
			case 'zip':
				$name = empty($name) ? date('YmdHis'). '.zip' : (substr($name,-1,4)=='.zip' ? $name : $name. '.zip');
				$zip = new \PclZip($name);
				$list = $zip->create($from, PCLZIP_OPT_REMOVE_PATH, $to);
				if ($list == 0) {
					return $zip->errorInfo(true);
				} else {
					return true;
				}
				break;
			case 'unzip':
				if (substr($from, -1, 4) != '.zip') {
					return '暂不支持非.zip文件解压缩';
				}
				$unzip = new \PclZip($from);
				$list = $unzip->listContent();
			    if ($list == 0) {
			        return $zip->errorInfo(true);
			    } else {
			    	$zip->extract($to);
			    	return true;
			    }
				break;
			default:
				return '未指定操作类型';
		}
	}
	
	/**
	 * 判断当前管理员是否具有小管理员权限
	 * 规则:除了区域合伙人,服务中心,商家外其他角色目前均视为具有小管理员权限
	 * 
	 * 增加功能:
	 * 根据当前[模块/控制器/方法],结合C('NOT_SMALL_SUPER_MANAGER')参数对当前场景进行判断,针对当前场景当前管理员是否为非小管理员身份
	 */
	public function isSmallSuperManager() {
		$is_small_super = false;
		
		$role_must_list = C('ROLE_MUST_LIST');
		$session_group_id = strpos(session('admin_group_id'), ',') ? explode(',', session('admin_group_id')) : array(session('admin_group_id'));
		
		//小管理员身份ID数组
		$small_super_group_id = array();
		
		foreach ($session_group_id as $k=>$v) {
			if (!in_array($v, $role_must_list)) {
				//$is_small_super = true;
				//break;
				$small_super_group_id[] = $v;
			}
		}
		if (count($small_super_group_id)>0) {
			$is_small_super = true;
		}
		
		if ($is_small_super) {
			
			//对当前场景进行非小管理员身份判断
			$current_path = MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME;
			$is_not_small_super_manager = C('NOT_SMALL_SUPER_MANAGER');
			foreach ($is_not_small_super_manager as $path=>$list) {
				if (preg_match('/'.addcslashes($path, '/').'/', $current_path)) {
					//针对当前管理员为单一身份和多身份进行双重判断
					$is_not_count = 0;
					foreach ($small_super_group_id as $k=>$v) {
						if (in_array($v, $list)) {
							//$is_small_super = false;
							//break 2;
							$is_not_count++;
						}
					}
					//当单一身份ID或多身份ID均在非小管理员数组中,则视为非小管理员
					if ($is_not_count==count($small_super_group_id)) {
						$is_small_super = false;
					}
				}
			}
			
		}
		
		return $is_small_super;
	}
	
	/**
	 * 导出数据
	 *
	 * @param array $head_array 导出表单标头信息数组
	 * @param array $export_data 导出表单的数据
	 */
	public function exportData($head_array, $export_data) {
		$file_name = date('Ymd.His.').microtime(true);
		$file_name = iconv("utf-8", "gbk", $file_name);
		$return = $this->xlsExport($file_name, $head_array, $export_data);
		!empty($return['error']) && $this->error($return['error']);
			
		$this->logWrite("导出数据:".MODULE_NAME.'-'.CONTROLLER_NAME.'-'.ACTION_NAME);
	}
	
	/************* v1.OLD *************/
	
	public function getUserInfo($val,$tag){
	
		$member = M("member");
		$_uinfo = $member->where($tag."='".$val."'")->find();
		if(empty($_uinfo)){
			return false;
		}else{
			return $_uinfo;
		}
	}
	
	/**
	 * 通过登录名获取真实姓名
	 */
	public function getNickName(){
		$where['loginname']=I('post.loginname');
		$nickname=M('member')->where($where)->getField('nickname');
		if (empty($nickname)) {
			$data['nickname'] = '';
		}else{
			$data['nickname']=$nickname;
		}
		$this->ajaxReturn($data);
		die();
	}
	
	
}
?>