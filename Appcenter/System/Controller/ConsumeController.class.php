<?php
// +----------------------------------------------------------------------
// | 配置管理
// +----------------------------------------------------------------------
namespace System\Controller;

use Common\Controller\AuthController;
use Common\Model\Sys\ConsumeRuleModel;

class ConsumeController extends AuthController {

	public function rule() {
		$ConsumeRuleModel = new ConsumeRuleModel();
		$data                 = $ConsumeRuleModel->getList();
		$this->assign( 'data', $data );
		$this->display();
	}
	
	/**
	 * 规则修改
	 */
	public function modify() {
		$id = $this->get['id'];
		
		if (!validateExtend($id, 'NUMBER')) {
			$this->error('参数格式有误');
		}
		
		$ConsumeRuleModel = new ConsumeRuleModel();
		
		$where['id'] = array('eq', $id);
		$info = $ConsumeRuleModel->getInfo('*', $where);
		
		$this->assign('info', $info);
		
		$this->display();
	}
	
	/**
	 * 规则保存
	 */
	public function save() {
		$data = $this->post;
		$data['uptime'] = time();
		
		if (!validateExtend($data['amount'], 'MONEY')) {
			$this->error('消费指标格式有误');
		}
		if (!validateExtend($data['subsidy_bai'], 'MONEY')) {
			$this->error('管理津贴比例格式有误');
		}
		
		$where['id'] = $data['id'];
		$result = M('consume_rule')->where($where)->save($data);
		if ($result === false) {
			$this->error('保存失败');
		} else {
			$this->success('保存成功', U('Consume/rule'), false, "编辑消费等级规则成功[ID:{$data[id]}]");
		}
	}

}

?>