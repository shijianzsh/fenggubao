<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 日志管理
// +----------------------------------------------------------------------
namespace System\Controller;
use Common\Controller\AuthController;

class LogController extends AuthController {

	public function index() {
		$this->display();
	}
	
	/**
	 * 后台日志列表
	 */
	public function logList() {
		$Log = D("Admin/Log");
		$AuthMember = D("Admin/Manager");
		
		$type = empty($this->get['type']) ? 0 : $this->get['type'];
		$admin_id = $this->get['admin_id'];
		$keyword = safeString($this->get['keyword'], 'trim_space');
		
		if (!is_numeric($type)) {
			$this->error('类型格式有误');
		}
		if (!empty($admin_id) && !validateExtend($admin_id, 'NUMBER')) {
			$this->error('管理员ID格式有误');
		}
		
		if ($type == 0 || $type == 1) {
			
			$map = array();
			$map['log.type'] = array('eq', $type);
				
			if (!empty($keyword)) {
				$map['log.content'] = array('like', "%{$keyword}%");
			}
		
			if ($type == 0) {
				
				//管理员列表
				$admin_list = $AuthMember->getMemberList('', true, $field='mem.id,mem.nickname');
				$this->assign('admin_list', $admin_list);
				
				if (!empty($admin_id)) {
					$map['log.admin_id'] = array('eq', $admin_id);
				}
				
				$count = $Log->alias('log')->where($map)->count();
				$limit = $this->Page($count, 20, $this->get);
				
				$log_list = $Log->getList($map, true, '', false, $limit);
				
			} elseif ($type == 1) {
				
				$count = $Log->alias('log')->where($map)->count();
				$limit = $this->Page($count, 20, $this->get);
				
				$log_list = M('Log')
					->alias('log')
					->join('left join __MEMBER__ mem ON mem.id=log.admin_id')
					->where($map)
					->order('log.id desc,log.admin_id desc')
					->field('log.*,mem.loginname,mem.nickname')
					->limit($limit)
					->select();
			}
			
			$this->assign('log_list', $log_list);
			$this->display();
			
		} elseif ($type == 2) {

			if (!empty($keyword)) {
				$where['m.loginname'] = array('like', "%{$keyword}%");
			}
			
			$count = M('login l')->join('left join zc_member as m on m.id = l.uid')->where($where)->count();
			$limit = $this->Page($count, 20, $this->get);
			
			$log_list = M('Login')
				->alias('l')
				->join('left join __MEMBER__ m ON m.id=l.uid')
				->where($where)
				->order('l.id desc')
				->limit($limit)
				->field('l.*,m.loginname,m.nickname')
				->select();
			
			$this->assign('log_list', $log_list);
			//unittest($log_list, $array2);
			//接口日志
			$this->display('logList2');
			
		}
		
	}
	
	/**
	 * 日志删除
	 */
	public function logDelete() {
		$this->error('日志删除功能暂停使用');
		
		$Log = D("Admin/Log");
	
		!is_numeric(I('get.id')) && $this->error(L('param').L('format_error'));
	
		$map['log.id'] = array('eq', I('get.id'));
		$info = $Log->getList($map, false, 'log.id');
		!$info && $this->error(L('null_result'));
	
		if ($Log->where('id='.I('get.id'))->delete() === false) {
			$this->error('删除失败!');
		}
		else {
			$this->success('删除成功!', '', false, "删除日志ID:{$this->get['id']}");
		}
	}
	
	/**
	 * 导出接口日志[ui]
	 */
	public function logExport() {
		C("TOKEN_ON", false);
	
		$this->display();
	}
	
	/**
	 * 导出接口日志[动作]
	 */
	public function logExportAction() {
		$Log = D("Admin/Log");
	
		!validateExtend($this->post['days'], 'NUMBER') && $this->post['days']!=0 && $this->error('导出日期格式有误');
		
		//导出日期处理
		$days = time()-$this->post['days']*24*60*60;
		$time = array(
			'start' => strtotime(date('Y-m-d 00:00:00', $days)),
			'end' => strtotime(date('Y-m-d 23:59:59', $days))
		);
	
		//获取相关接口日志信息
		$map_log['log.type'] = array('eq', $this->post['type']);
		//$map_log['log.admin_id'] = array('eq', 1);
		$map_log['log.date_created'] = array(array('egt', $time['start']), array('elt', $time['end']), 'AND');
	
		$log_list = $Log->getList($map_log, true, "mem.nickname,log.content,from_unixtime(log.date_created,'%Y-%m-%d %H:%i:%s'),log.admin_ip");
		!$log_list && $this->error('暂无相关信息');
	
		$head_array = array('操作者', '操作记录', '记录日期', '使用IP');
		$file_name = $this->post['type']==0 ? '后台' : '接口';
		$file_name .= '操作日志数据-'.date('Y-m-d');
		$return = $this->xlsExport($file_name, $head_array, $log_list);
		!empty($return['error']) && $this->error($return['error']);
		
		$this->logWrite("导出接口日志,日期:".$this->post['days']);
	}
	
}
?>