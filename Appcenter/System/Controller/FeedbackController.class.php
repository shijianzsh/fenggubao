<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 意见反馈管理
// +----------------------------------------------------------------------
namespace System\Controller;
use Common\Controller\AuthController;

class FeedbackController extends AuthController {

	public function index() {
		$Feedback = M('Feedback');
		
		$count = $Feedback->count();
		$limit = $this->Page($count, 20, $this->get);
		
		$list = $Feedback
			->alias('feb')
			->join('join __MEMBER__ mem ON mem.id=feb.uid')
			->limit($limit)
			->order('feb.id desc')
			->field('feb.*,mem.loginname,mem.nickname')
			->select();
		$this->assign('list', $list);
		
		$this->display();
	}
	
	public function feedbackDelete() {
		$Feedback = M('Feedback');
		
		$id = $this->get['id'];
		
		if (!validateExtend($id, 'NUMBER')) {
			$this->error('参数格式有误');
		}
		
		$map['id'] = array('eq', $id);
		$info = $Feedback->where($map)->field('id')->find();
		if (!$info) {
			$this->error('该信息不存在');
		}
		
		if ($Feedback->where($map)->delete() === false) {
			$this->error('删除失败');
		} else {
			$this->success('删除成功', U('Feedback/index'), false, "成功删除意见反馈:{$info['title']}[ID:{$id}]");
		}
	}
	
	/**
	 * 回复留言
	 */
	public function reply() {
		$content = safeString($this->post['content'], 'trim');
		$fid = $this->post['fid'];
		
		if (empty($content)) {
			$this->error('请填写回复内容');
		}
		
		$map['feb.id'] = array('eq', $fid);
		$info = M('Feedback')
			->alias('feb')
			->join('join __MEMBER__ mem ON mem.id=feb.uid')
			->join('join __LOGIN__ lon ON lon.uid=feb.uid')
			->where($map)
			->field('mem.id')
			->find();
		if (!$info) {
			$this->error('回复失败:未找到该用户');
		}
		
		M()->startTrans();
		
		//更新留言表
		$data_reply = [
			'reply' => $content,
			'date_updated' => time()
		];
		$result1 = M('Feedback')->where('id='.$fid)->save($data_reply);
		
		//加入推送队列
		$target = 'common_alert';
		$extra['msg'] = $content;
		$result2 = pushQueue('意见反馈回复', $target, $extra, $info['id']);
		
		if ($result1 === false || $result2 === false) {
			M()->rollback();
			$this->error('意见反馈回复：加入推送队列失败');
		} else {
			M()->commit();
			$this->success('意见反馈回复：成功加入推送队列', U('Feedback/index'), false, "成功将意见反馈[ID:{$fid}]回复加入推送队列");
		}
	}
	
}
?>