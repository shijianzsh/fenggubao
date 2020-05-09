<?php
// +----------------------------------------------------------------------
// | 配置管理
// +----------------------------------------------------------------------
namespace System\Controller;

use Common\Controller\AuthController;
use Common\Model\Sys\PerformanceRuleModel;

class PerformanceController extends AuthController {

	public function rule() {
		$PerformanceRuleModel = new PerformanceRuleModel();
		$data                 = $PerformanceRuleModel->getList();
		$this->assign( 'data', $data );
		$this->display();
	}
	
	/**
	 * 分配规则修改
	 */
	public function modify() {
		$rule_id = $this->get['id'];
		
		if (!validateExtend($rule_id, 'NUMBER')) {
			$this->error('参数格式有误');
		}
		
		$PerformanceRuleModel = new PerformanceRuleModel();
		
		$where['rule_id'] = array('eq', $rule_id);
		$info = $PerformanceRuleModel->getInfo('*', $where);
		
		$this->assign('info', $info);
		
		$this->display();
	}
	
	/**
	 * 分配规则保存
	 */
	public function save() {
		$data = $this->post;
		
		if (!validateExtend($data['rule_amount'], 'MONEY')) {
			$this->error('业绩指标格式有误');
		}
		if ($data['rule_condition_count'] > 0) {
			if (!validateExtend($data['rule_condition_count'], 'NUMBER') || !validateExtend($data['rule_condition_level'], 'NUMBER')) {
				$this->error('附加条件格式有误');
			}
		}
		
		$where['rule_id'] = $data['rule_id'];
		$result = M('performance_rule')->where($where)->save($data);
		if ($result === false) {
			$this->error('保存失败');
		} else {
			$this->success('保存成功', U('Performance/rule'), false, "编辑分配规则成功[ID:{$data[rule_id]}]");
		}
	}

}

?>