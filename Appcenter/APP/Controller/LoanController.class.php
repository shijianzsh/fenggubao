<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 贷款相关接口
// +----------------------------------------------------------------------
namespace APP\Controller;
use Common\Controller\ApiController;

class LoanController extends ApiController {
	
	/**
	 * 贷款申请
	 * @param uid 会员ID
	 * @param money 贷款金额
	 * @param time 申请放款时间（每个月3号，6号，9号，12号）
	 */
	public function loan() {
		$uid = intval(I('post.uid'));
		$loan_money = intval(I('post.money'));
		$loan_time = intval(I('post.time'));
		
		$where['id'] = $uid;
		$m = M('member')->where($where)->count();
		if ($m == 0) {
			$this->myApiPrint('对不起，用户信息不存在！');
		}
		
		$level = M('member')->field('level')->where($where)->find();
		if ($level == 1) {
			$this->myApiPrint('对不起，你是体验会员，不能申请贷款！');
		}
		
		$data['uid'] = $uid;
		$data['loan_money'] = $loan_money;
		$data['loan_time'] = $loan_time;
		$result = M('loan_record')->add($data);
		
		$this->myApiPrint('审请成功，等待后台审核....！', 400);
	}
	
}
?>