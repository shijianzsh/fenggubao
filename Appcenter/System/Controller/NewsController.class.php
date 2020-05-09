<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 快讯管理
// +----------------------------------------------------------------------
namespace System\Controller;
use Common\Controller\AuthController;

class NewsController extends AuthController {

	/**
	 * 快讯管理
	 */
	public function newsList() {
		$News = M('FlashNews');
		
		$map = array();
		
		if (!empty($this->get['flash_content'])) {
			$map['flash_content'] = array('like', "%{$this->get['flash_content']}%");
		}
		if (!empty($this->get['type']) && validateExtend($this->get['type'], 'NUMBER')) {
			$map['type'] = array('eq', $this->get['type']);
		}
		
		$count = $News->where($map)->count();
		$limit = $this->Page($count, 20, $this->get);

		$list = $News->where($map)->limit($limit)->select();
		$this->assign('list', $list);
		
		$this->display();
	}
	
	/**
	 * 快讯添加UI
	 */
	public function newsAddUi() {
		if ($this->get['type'] == '4') {
			$this->display('newsAddUi_4');
		} else {
			$this->display();
		}
	}
	
	/**
	 * 快讯添加
	 */
	public function newsAdd() {
		$News = M('FlashNews');
		
		$data = $this->post;
		$data['flash_link'] = $_POST['flash_link'];
		if (empty($data['flash_content'])) {
			$this->error('标题不能为空');
		}
		if (empty($data['flash_link'])) {
			$this->error('内容不能为空');
		}
		
		if (!$News->create($data, '', true)) {
			$this->error($News->getError());
		} else {
			$data['post_time'] = time();
			$id = $News->add($data);
			$this->success('添加成功', U('News/newsList'), false, "添加快讯:{$data['flash_content']}[ID:{$id}]");
		}
	}
	
	/**
	 * 快讯编辑
	 */
	public function newsModify() {
		$News = M('FlashNews');
		
		$id = $this->get['id'];
		
		if (!validateExtend($id, 'NUMBER')) {
			$this->error('参数格式有误');
		}
		
		$map['flash_id'] = array('eq', $id);
		$info = $News->where($map)->find();
		if (!$info) {
			$this->error('该信息已不存在');
		}
		
		//获取市所属省信息
		if (!empty($info['city'])) {
			$map_city['cit.city'] = array('eq', $info['city']);
			$city_info = M('City')
				->alias('cit')
				->where($map_city)
				->join('left join __PROVINCE__ pro on pro.pid=cit.pid')
				->field('cit.cid,pro.pid,pro.province')
				->find();
			$info['province'] = $city_info['province'];
		}
		
		$this->assign('info', $info);
		
		if ($info['type'] == '4') {
			$this->display('newsModify_4');
		} else {
			$this->display();
		}
	}
	
	/**
	 * 快讯保存
	 */
	public function newsSave() {
		$News = M('FlashNews');
		$data = $this->post;
		$data['flash_link'] = $_POST['flash_link'];
		$data['post_time'] = time();
		if ($News->save($data) === false) {
			$this->error('保存失败');
		} else {
			$this->success('保存成功', U('News/newsList'), false, "编辑快讯:{$this->post['flash_content']}[ID:{$this->post['flash_id']}]");
		}
	}
	
	/**
	 * 快讯删除
	 */
	public function newsDelete() {
		$News = M('FlashNews');
		
		$id = $this->get['id'];
		
		if (!validateExtend($id, 'NUMBER')) {
			$this->error('参数格式有误');
		}
		
		$map['flash_id'] = array('eq', $id);
		$info = $News->where($map)->field('flash_content')->find();
		if (!$info) {
			$this->error('该信息已不存在');
		}
		
		if ($News->where($map)->delete() === false) {
			$this->error('删除失败');
		} else {
			$this->success('删除成功', U('News/newsList'), false, "删除快讯:{$info['flash_content']}[ID:{$id}]");
		}
	}
	
	/**
	 * 推送
	 */
	public function newsPush() {
		$News = M('FlashNews');
		$Login = M('Login');
		
		$id = $this->get['id'];
		
		if (!validateExtend($id, 'NUMBER')) {
			$this->error('参数格式有误');
		}
		
		$map['flash_id'] = array('eq', $id);
		$info = $News->where($map)->find();
		if (!$info) {
			$this->error('该信息已不存在');
		}
		
		/*
		$push_data = array();
		$push_extra = array();
		
		$push_extra['target'] = '';
		
		switch ($info['type']) {
			case '1': //快讯
				break;
			case '2': //公告
				$push_extra['target'] = 'notice_list';
				break;
			case '3': //帮助中心
				break;
			case '4': //APP弹窗消息
				$push_extra['target'] = 'common_alert';
				$push_extra['msg'] = $info['flash_link'];
				break;
			default:
				$this->error('未知的快讯类型');
		}
		
		$map_login['registration_id'] = array('neq', '');
		$push_data['all'] = $Login->where($map_login)->getField('registration_id', true);

		$status = $this->push($push_data, $info['flash_content'], $push_extra);
		if (!$status) {
			$this->error('推送失败');
		} else {
			$this->success('推送成功', U('News/newsList'), false, "成功推送[{$info['flash_content']}][ID:{$id}]");
		}
		*/
		
		$target = 'common_alert';
		$extra = array();
		
		switch ($info['type']) {
			case '2': //公告
				$target = 'notice_list';
				break;
			default: //默认统一为APP弹窗消息
				$extra['msg'] = $info['flash_link'];
		}
		
		//加入推送队列
		if (!pushQueue($info['flash_content'], $target, $extra)) {
			$this->error('加入推送队列失败');
		} else {
			$this->success('成功加入推送队列', U('News/newsList'), false, "成功将[{$info['flash_content']}][ID:{$id}]加入推送队列");
		}
	}
	
}
?>