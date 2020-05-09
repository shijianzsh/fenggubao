<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 谷聚金 - 审核管理
// +----------------------------------------------------------------------
namespace Admin\Controller;

use Common\Controller\AuthController;
use V4\Model\GjjModel;
use V4\Model\ProcedureModel;

class PartnerController extends AuthController
{

    public function __construct()
    {
        parent::__construct();

        C('TOKEN_ON', false);
    }
    
    /**
     * 大中华区合伙人
     */
    public function region() {
    	$GjjModel = new GjjModel();
    	
    	$data = $this->baseData(5, 1);
    	
    	$list = $data['list'];
    	$export_data = []; //导出数据
    	foreach ($list as $k=>$v) {
    		//用户信息
    		$member = M('Member')->where('id='.$v['user_id'])->field('loginname,truename')->find();
    		if ($member) {
    			$list[$k] = array_merge($list[$k], $member);
    		}
    	
    		//申请身份中文
    		$list[$k]['role_cn'] = C('GJJ_FIELD_CONFIG')['gjj_roles']['role'][$v['role']];
    	
    		//审核状态中心
    		$list[$k]['audit_status_cn'] = C('GJJ_FIELD_CONFIG')['gjj_roles']['audit_status'][$v['audit_status']];
    	
    		//激活状态中文
    		$list[$k]['enabled_cn'] = C('GJJ_FIELD_CONFIG')['gjj_roles']['enabled'][$v['enabled']];
    		
    		//直辖区县
    		$countrys = $this->baseData(2, 1, $v['user_id'], true);
    		$list[$k]['countrys'] = $countrys['list'];
    		$export_countrys = '';
    		foreach ($list[$k]['countrys'] as $k1=>$v1) {
    			$export_countrys .= $v1['province'].$v1['city'].$v1['country'].'<br>';
    		}
    	
    		//导出数据
    		$export_data[] = [
	    		$list[$k]['loginname'],
	    		$list[$k]['truename'],
	    		$v['province'],
	    		$export_countrys,
	    		date('Y-m-d H:i:s', $v['created_at']),
	    		$list[$k]['enabled_cn']
    		];
    	}
    	$this->assign('list', $list);
    	 
    	//导出功能
    	if ($this->get['action'] == 'exportData') {
    		$head_array = array('账号', '姓名', '开通地区', '直辖区县', '开通时间', '激活状态');
    		$this->exportData($head_array, $export_data);
    	}
    	 
    	$this->Page($data['paginator']['totalRows'], $data['paginator']['everyPage'], $this->get);
    	 
    	$this->display();
    }
    
    /**
     * 审核通过大中华区合伙人
     */
    public function openRegion() {
    	$uid = $this->post['uid'];
    	$name = $this->post['name'];
    	$province = $this->post['province'];
    	$city = $this->post['city'];
    	$country = $this->post['country'];
    
    	if (!validateExtend($uid, 'NUMBER')) {
    		$this->error('用户ID参数格式有误');
    	}
    	if (!validateExtend($name, 'CHS')) {
    		$this->error('大区参数格式有误');
    	}
    
    	M()->startTrans();
    
    	//判断是否有申请资格
    	$map_roles = [
    	'user_id' => ['eq', $uid],
    	'audit_status' => ['neq', 2]
    	];
    	$apply_info = M('gjj_roles')->where($map_roles)->field('id')->find();
    	if ($apply_info) {
    		$this->error('该用户已是谷聚金代理身份');
    	}
    
    	$data = [
	    	'user_id' => $uid,
	    	'region' => $name,
	    	'role' => 5,
	    	'audit_status' => 1,
	    	'enabled' => 0,
	    	'created_at' => time(),
	    	'updated_at' => time()
    	];
    
    	//开通大中华区合伙人
    	$result = M('gjj_roles')->add($data);
    	if (!$result || $result == null) {
    		M()->rollback();
    		$this->error('审核通过大中华区合伙人失败');
    	}
    
    	//检测是否每个大中华区所辖的省份都已分配直辖区县代理合伙人身份
    	$region_province_count = M('gjj_regions')->where("`name`='{$name}'")->count();
    	if (count($country) != $region_province_count) {
    		M()->rollback();
    		$this->error('对应大中华区的每个省份都需分配直辖区县代理合伙人身份');
    	}
    
    	//开通直辖区县代理合伙人
    	foreach ($province as $k=>$v) {
    		$data['province'] = $v;
    		$data['city'] = $city[$k];
    		$data['country'] = $country[$k];
    		$data['role'] = 2;
    			
    		if (!validateExtend($data['province'], 'CHS') || !validateExtend($data['city'], 'CHS') || !validateExtend($data['country'], 'CHS')) {
    			M()->rollback();
    			$this->error('省市区参数格式有误');
    		}
    			
    		//检测该省市区是否已被使用
    		$map_country = [
	    		'province' => ['eq', $data['province']],
	    		'city' => ['eq', $data['city']],
	    		'country' => ['eq', $data['country']]
    		];
    		$apply_exists = M('gjj_roles')->where($map_country)->field('id')->find();
    		if ($apply_exists) {
    			M()->rollback();
    			$this->error("对不起，{$data[province]}-{$data[city]}-{$data[country]}已有区县代理合伙人");
    		}
    			
    		$result = M('gjj_roles')->add($data);
    		if (!$result || $result == null) {
    			M()->rollback();
    			$this->error('审核通过直辖区县代理合伙人失败');
    		}
    	}
    
    	M()->commit();
    
    	$this->success('审核通过成功', '', false, "成功审核通过用户[ID:{$uid}][{$name}]大中华区身份");
    }
    
    /**
     * 激活大中华区身份
     */
    public function activateRegion() {
    	$id = $this->get['id'];
    	
    	if (!validateExtend($id, 'NUMBER')) {
    		$this->error('参数格式有误');
    	}
    	
    	M()->startTrans();
    	
    	$info = M('gjj_roles')->where('id='.$id)->find();
    	if (!$info) {
    		$this->error('申请信息不存在');
    	}
    	$member = M('Member')->where('id='.$info['user_id'])->field('loginname,truename')->find();
    	if (!$member) {
    		$this->error('用户信息不存在');
    	}
    	
    	$result1 = M('gjj_roles')->where('user_id='.$info['user_id'])->save(['enabled'=>1]);
    	
    	//调用存储过程
    	$ProcedureModel = new ProcedureModel();
    	$result2 = $ProcedureModel->execute('Gjj_Event_activated', "{$info['user_id']},5", "@error");
    	
    	if (!$result1 || !$result2) {
    		M()->rollback();
    		$this->error('激活失败');
    	}
    	
    	M()->commit();
    	
    	$this->success('激活成功', '', false, "成功激活用户[ID:{$member['loginname']}][{$member['truename']}]大中华区身份");
    }
    
    /**
     * 取消大中华区合伙人
     */
    public function closeRegion() {
    	$this->error('大中华区合伙人一旦开通便不能后台取消，若取消请联系技术人员');
    
    	$uid = $this->post['uid'];
    
    	if (!validateExtend($uid, 'NUMBER')) {
    		$this->error('用户ID参数格式有误');
    	}
    
    	$result = M('gjj_roles')->where('user_id='.$uid)->delete();
    	if (!$result) {
    		$this->error('取消失败');
    	}
    
    	$this->success('取消成功', '', false, "成功取消用户[ID:{$uid}]大中华区合伙人身份");
    }

    /**
     * 省营运中心申请列表
     */
    public function province() {
    	$type = $this->get['type'];
    	
    	if (!validateExtend($type, 'NUMBER')) {
    		$this->error('参数格式有误');
    	}
    	
    	$data = $this->baseData(4, $type);
    	
    	$list = $data['list'];
    	$export_data = []; //导出数据
    	foreach ($list as $k=>$v) {
    		//用户信息
    		$member = M('Member')->where('id='.$v['user_id'])->field('loginname,truename')->find();
    		if ($member) {
    			$list[$k] = array_merge($list[$k], $member);
    		}
    		
    		//申请身份中文
    		$list[$k]['role_cn'] = C('GJJ_FIELD_CONFIG')['gjj_roles']['role'][$v['role']];
    		
    		//审核状态中心
    		$list[$k]['audit_status_cn'] = C('GJJ_FIELD_CONFIG')['gjj_roles']['audit_status'][$v['audit_status']];
    		
    		//激活状态中文
    		$list[$k]['enabled_cn'] = C('GJJ_FIELD_CONFIG')['gjj_roles']['enabled'][$v['enabled']];
    		
    		//直辖区县
    		$countrys = $this->baseData(2, $type, $v['user_id'], true);
    		$list[$k]['countrys'] = $countrys['list'];
    		$export_countrys = '';
    		foreach ($list[$k]['countrys'] as $k1=>$v1) {
    			$export_countrys .= $v1['province'].$v1['city'].$v1['country'].'<br>';
    		}
    		
    		//导出数据
    		$export_data[] = [
    			$list[$k]['loginname'],
    			$list[$k]['truename'],
    			$v['province'],
    			$export_countrys,
    			$v['image'],
    			date('Y-m-d H:i:s', $v['created_at']),
    			$list[$k]['audit_status_cn'],
    			empty($v['updated_at']) ? '' : date('Y-m-d H:i:s', $v['updated_at']),
    			$list[$k]['enabled_cn']
    		];
    	}
    	$this->assign('list', $list);
    	
    	//导出功能
    	if ($this->get['action'] == 'exportData') {
    		$head_array = array('账号', '姓名', '申请地区', '直辖区县', '打款凭据', '申请时间', '审核状态', '审核时间', '激活状态');
    		$this->exportData($head_array, $export_data);
    	}
    	
    	$this->Page($data['paginator']['totalRows'], $data['paginator']['everyPage'], $this->get);
    	
    	$this->display();
    }
    
    /**
     * 区县代理合伙人申请列表
     */
    public function country() {
    	$type = $this->get['type'];
    	 
    	if (!validateExtend($type, 'NUMBER')) {
    		$this->error('参数格式有误');
    	}
    	 
    	$data = $this->baseData(2, $type);
    	 
    	$list = $data['list'];
    	$export_data = []; //导出数据
    	foreach ($list as $k=>$v) {
    		//用户信息
    		$member = M('Member')->where('id='.$v['user_id'])->field('loginname,truename')->find();
    		if ($member) {
    			$list[$k] = array_merge($list[$k], $member);
    		}
    
    		//申请身份中文
    		$list[$k]['role_cn'] = C('GJJ_FIELD_CONFIG')['gjj_roles']['role'][$v['role']];
    
    		//审核状态中心
    		$list[$k]['audit_status_cn'] = C('GJJ_FIELD_CONFIG')['gjj_roles']['audit_status'][$v['audit_status']];
    
    		//激活状态中文
    		$list[$k]['enabled_cn'] = C('GJJ_FIELD_CONFIG')['gjj_roles']['enabled'][$v['enabled']];
    
    		//导出数据
    		$export_data[] = [
    		$list[$k]['loginname'],
    		$list[$k]['truename'],
    		$v['province']. $v['city']. $v['country'],
    		$v['image'],
    		date('Y-m-d H:i:s', $v['created_at']),
    		$list[$k]['audit_status_cn'],
    		empty($v['updated_at']) ? '' : date('Y-m-d H:i:s', $v['updated_at']),
    		$list[$k]['enabled_cn']
    		];
    	}
    	$this->assign('list', $list);
    	 
    	//导出功能
    	if ($this->get['action'] == 'exportData') {
    		$head_array = array('账号', '姓名', '申请地区', '打款凭据', '申请时间', '审核状态', '审核时间', '激活状态');
    		$this->exportData($head_array, $export_data);
    	}
    	 
    	$this->Page($data['paginator']['totalRows'], $data['paginator']['everyPage'], $this->get);
    	 
    	$this->display();
    }
    
    /**
     * 审核操作
     * 
     * @internal 由于谷聚金身份针对同一用户不分地区只能存在一种,所以当驳回一个身份时可直接驳回该用户的所有其他身份,激活操作也同理
     */
    public function review() {
    	$id = $this->get['id'];
    	$status = $this->get['status'];
    	$enabled = $this->get['enabled'];
    	$reason = safeString($this->get['reason'], 'trim_space');
    	$reason = urldecode($reason);
    	
    	M()->startTrans();
    	
    	//获取申请信息
    	$apply_info = M('gjj_roles')->where('id='.$id)->find();
    	if (!$apply_info) {
    		$this->error('申请信息不存在');
    	}
    	
    	$data = [
    		'updated_at' => time()
    	];
    	$log = "成功审核[ID:{$id}]状态为";
    	
    	if (!validateExtend($id, 'NUMBER')) {
    		$this->error('参数格式有误');
    	}
    	
    	if (validateExtend($status, 'NUMBER')) {
	    	$status_config = C('GJJ_FIELD_CONFIG')['gjj_roles']['audit_status'];
	    	if (!array_key_exists($status, $status_config)) {
	    		$this->error('审核状态异常');
	    	}
	    	$data['audit_status'] = $status;
    	}
    	
    	if (validateExtend($enabled, 'NUMBER')) {
	    	$enabled_config = C('GJJ_FIELD_CONFIG')['gjj_roles']['enabled'];
	    	if (!array_key_exists($enabled, $enabled_config)) {
	    		$this->error('激活状态异常');
	    	}
	    	if ($enabled == '0') {
	    		$this->error('激活后不能再进行禁用操作');
	    	}
	    	$data['enabled'] = $enabled;
    	}
    	
    	if ($status == 1) { //审核通过
    		//再次判断是否已存在同身份和地区的已审核通过的用户
    		$map_status_check = [
    			'role' => ['eq', $apply_info['role']],
    			'audit_status' => ['eq', 1],
    			'region' => ['eq', $apply_info['region']],
    			'province' => ['eq', $apply_info['province']],
    			'city' => ['eq', $apply_info['city']],
    			'country' => ['eq', $apply_info['country']]
    		];
    		$check_exists = M('gjj_roles')->where($map_status_check)->field('id')->find();
    		if ($check_exists) {
    			$this->error('对不起，该身份和地区已有对应的代理合伙人');
    		}
    		//清空备注信息
    		$data['remark'] = '';
    	} elseif ($status == 2) {
    		if ($apply_info['enabled'] == 1) { //激活的不能再驳回
    			$this->error('激活后不能再进行驳回操作');
    		}
    		if (empty($reason)) {
    			$this->error('驳回原因不能为空');
    		}
    		$data['remark'] = $reason;
    	}
    	
    	$result1 = true;
    	if (isset($data['enabled'])) {
    		if ($apply_info['audit_status'] != 1) {
    			$this->error('已审核通过的申请才能进行激活操作');
    		}
    		$log .= "[ENABLED:{$enabled}]";
    		
    		//调用存储过程
    		$ProcedureModel = new ProcedureModel();
    		$result1 = $ProcedureModel->execute('Gjj_Event_activated', "{$apply_info['user_id']},{$apply_info['role']}", "@error");
    	}
    	
    	if (isset($data['status'])) {
    		$log .= "[STATUS:{$status}]";
    	}
    	
    	$result = M('gjj_roles')->where('user_id='.$apply_info['user_id'])->save($data);
    	
    	if (!$result || !$result1) {
    		M()->rollback();
    		$this->error('操作失败');
    	}
    	
    	M()->commit();
    	
    	$this->success('操作成功', '', false, $log);
    }
    
    /**
     * 申请基础数据封装
     * 
     * @param int $role 身份类型(1 乡镇代理, 2 区县代理, 3 市级代理，4 省营运中心, 5 大中华区)
     * @param int $type 审核状态(0 待审核, 1 已审核)
     * @param int $uid 用户ID(默认false)
     * @param boolean $zhixia 是否为直辖调用(默认false,当true且role=2时不启用join关联)
     */
    private function baseData($role, $type, $uid=false, $zhixia=false) {
    	$GjjModel = new GjjModel();
    	
    	$page = $this->get['p']<1 ? 1 : $this->get['p'];
    	$page = $this->get['action']=='exportData' ? false : $page;
    	$user_id = $this->get['user_id'];
    	$time_min = empty($this->get['time_min']) ? '' : strtotime($this->get['time_min']);
    	$time_max = empty($this->get['time_max']) ? '' : strtotime($this->get['time_max']. '23:59:59');
    	$status = $this->get['status'];
    	$enabled = $this->get['enabled'];
    	
    	if (!validateExtend($role, 'NUMBER') || !validateExtend($type, 'NUMBER')) {
    		$this->error('参数格式有误');
    	}
    	
    	$map = [
	    	'role' => ['eq', $role],
	    	'audit_status' => $type==0 ? ['eq', 0] : ['neq', 0]
    	];
    	
    	if (validateExtend($user_id, 'NUMBER')) {
    		$map_member = [
    			'loginname' => ['eq', $user_id],
    			'truename' => ['eq', $user_id],
    			'_logic' => 'or'
    		];
    		$member_id = M('Member')->where($map_member)->getField('id', true);
    		if ($member_id) {
    			$map['user_id'] = ['in', implode(',', $member_id)];
    		}
    	} elseif ($uid) {
    		$map['user_id'] = ['eq', $uid];
    	}
    	
    	if (!empty($time_min) && !empty($time_max)) {
    		$map['created_at'] = [['egt', $time_min], ['elt', $time_max], 'and'];
    	} elseif (!empty($time_min)) {
    		$map['created_at'] = ['egt', $time_min];
    	} elseif (!empty($time_max)) {
    		$map['created_at'] = ['elt', $tiime_max];
    	}
    	
    	if (validateExtend($status, 'NUMBER')){
    		$map['audit_status'] = ['eq', $status];
    	}
    	
    	if (validateExtend($enabled, 'NUMBER')){
    		$map['enabled'] = ['eq', $enabled];
    	}
    	
    	$use_join = false;
    	$join_section = '';
    	$join_map = [];
    	if ($role == 2 && $zhixia == false) {
    		foreach ($map as $k=>$v) {
    			$join_map['g.'.$k] = $v;
    		}
    		$map = $join_map;
    		$map['_string'] = ' g1.id is null ';
    		$use_join = true;
    		$join_section = " and g1.role in (4,5) ";
    	}
    	
    	$data = $GjjModel->getList('*', $page, 20, $map, $use_join, $join_section);
    	
    	return $data;
    }
    
    /**
     * 搜索用户信息
     */
    public function getUserInfo() {
    	$user_account = $this->post['user_account'];
    	
    	$html = ['error'=>'', 'data'=>''];
    	
    	if (!validateExtend($user_account, 'MOBILE')) {
    		$html['error'] = '用户手机号格式有误';
    		exit(json_encode($html, JSON_UNESCAPED_UNICODE));
    	}
    	
    	$user_info = M('Member')->where('loginname='.$user_account)->field('id,loginname,truename')->find();
    	if (!$user_info) {
    		$html['error'] = '该用户不存在';
    		exit(json_encode($html, JSON_UNESCAPED_UNICODE));
    	}
    	
    	$html['data'] = <<<EOF
    				<span>{$user_info['loginname']}[{$user_info['truename']}]</span>
    				<a href="javascript:openRegion({$user_info['id']},'{$user_info['loginname']}[{$user_info['truename']}]')" class="zc_btuaineq">开通大中华区</a>
EOF;
    	exit(json_encode($html, JSON_UNESCAPED_UNICODE));
    }

}

?>