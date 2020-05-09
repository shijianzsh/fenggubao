<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 广告管理
// +----------------------------------------------------------------------
namespace System\Controller;
use Common\Controller\AuthController;
use V4\Model\AdModel;
use V4\Model\Tag;

class AdController extends AuthController {

	/**
	 * 广告列表
	 */
	public function index() {
		$AdModel = new AdModel();
		
		$page = $this->get['p']>1 ? $this->get['p'] : 1;
		
		$data = $AdModel->getAdList('*', $page, 20, '', 0);
		
		$list = $data['list'];
		$field_config = C('FIELD_CONFIG.ad');
		foreach ($list as $k=>$v) {
			//发布者账号信息
			$member_info = M('Member')->where('id='.$v['user_id'])->field('loginname,nickname')->find();
			$list[$k]['loginname'] = $member_info ? $member_info['loginname'] : '';
			$list[$k]['nickname'] = $member_info ? $member_info['nickname'] : '';
			
			//广告状态对应中文
			if (array_key_exists('ad_status', $field_config) && isset($v['ad_status'])) {
				$list[$k]['ad_status_cn'] = $field_config['ad_status'][$v['ad_status']];
			}
			
			//广告类型对应中文
			if (array_key_exists('ad_type', $field_config) && isset($v['ad_type'])) {
			    $list[$k]['ad_type_cn'] = $field_config['ad_type'][$v['ad_type']];
			}
			
			//获取累计浏览次数和当日浏览次数
			$view_all = $AdModel->getAdViewList('count(*) count', false, 0, 'ad_id='.$v['ad_id'], 0);
			$list[$k]['view_all'] = $view_all['list'][0]['count'];
			$view_today = $AdModel->getAdViewList('count(*) count', false, 0, "ad_id={$v[ad_id]} and view_addtime>=".strtotime(date('Y-m-d')), 0);
			$list[$k]['view_today'] = $view_today['list'][0]['count'];
		}
		$this->assign('list', $list);
		
		$this->Page($data['paginator']['totalPage']*$data['paginator']['everyPage'], $data['paginator']['everyPage'], $this->get);
		
		$this->display();
	}
	
	/**
	 * 发布广告[ui]
	 */
	public function adAdd() {
		$this->display();
	}
	
	/**
	 * 发布广告
	 */
	public function adAddAction() {
		C('TOKEN_ON', false);
		
		$data = $this->post;
		$data['ad_starttime'] = $data['time_min'];
		$data['ad_endtime'] = $data['time_max'];
		$data['ad_addtime'] = time();
		$data['ad_pushtime'] = time();
		$data['user_id'] = session('admin_mid');
		
		if (empty($data['ad_title'])) {
			$this->error('标题不能为空');
		}
		if (!validateExtend($data['ad_amount'], 'MONEY')) {
			$this->error('广告现金投放单价格式有误');
		}
		if (!validateExtend($data['ad_amount_credits'], 'MONEY')) {
		    $this->error('广告积分投放单价格式有误');
		}
		if (!validateExtend($data['ad_amount_max'], 'MONEY')) {
		    $this->error('创客现金封顶金额格式有误');
		}
		if (!validateExtend($data['ad_amount_credits_max'], 'MONEY')) {
		    $this->error('创客积分封顶金额格式有误');
		}
		if (!validateExtend($data['ad_amount_vip_max'], 'MONEY')) {
		    $this->error('VIP现金封顶金额格式有误');
		}
		if (!validateExtend($data['ad_amount_vip_credits_max'], 'MONEY')) {
		    $this->error('VIP积分封顶金额格式有误');
		}
		
		switch ($data['ad_type']) {
		    case '1':
		        if (!validateExtend($data['ad_link'], '/^(http|https):\/\/(.*)$/', true)) {
		            $this->error('外部链接地址格式有误');
		        }
		        break;
		    case '2':
		        if (!validateExtend($data['ad_link'], 'NUMBER')) {
		            $this->error('店铺ID格式有误');
		        }
		        break;
		    case '3':
		        if (!validateExtend($data['ad_link'], 'NUMBER')) {
		            $this->error('商品ID格式有误');
		        }
		        break;
		}
		
		if (empty($data['ad_starttime'])) {
			$this->error('请选择开始时间');
		} else {
			$data['ad_starttime'] = strtotime($data['ad_starttime']);
		}
		if (empty($data['ad_endtime'])) {
			$this->error('请选择结束时间');
		} else {
			$data['ad_endtime'] = strtotime($data['ad_endtime'].' 23:59:59');
		}
		unset($data['time_min']);
		unset($data['time_max']);
		
		
		//上传图片
		$upload_config = array(
			'file' => $_FILES['ad_image'],
			'path' => 'ad/'. date('Ymd'),
		);
		$Upload = new \Common\Controller\UploadController($upload_config);
		$upload_info = $Upload->upload();
		if (!empty($upload_info['error'])) {
			$this->error('图片上传失败:'.$upload_info['error']);
		} else {
			$data['ad_image'] = $upload_info['data']['url'];
		}
		
		$AdModel = new AdModel();
		$result = $AdModel->adAdd($data);
		if ($result['status'] !== true) {
			$this->error($result['status']);
		} else {
			$id = $result['id'];
			$this->success('添加成功', U('Ad/index'), false, "添加广告:{$data['ad_title']}[ID:{$id}]");
		}
	}
	
	/**
	 * 广告编辑[ui]
	 */
	public function adModify() {
		$AdModel = new AdModel();
		
		$id = $this->get['id'];
		
		if (!validateExtend($id, 'NUMBER')) {
			$this->error('参数有误');
		}
		
		$info = $AdModel->getAdInfo('*', 'ad_id='.$id);
		if (!$info) {
			$this->error('该广告不存在');
		}
		
		//发布者账号信息
		$member_info = M('Member')->where('id='.$info['user_id'])->field('loginname,nickname')->find();
		$info['loginname'] = $member_info ? $member_info['loginname'] : '';
		$info['nickname'] = $member_info ? $member_info['nickname'] : '';
		
		//数字对应中文处理
		$field_config = C('FIELD_CONFIG.ad');
		if (array_key_exists('ad_status', $field_config) && isset($info['ad_status'])) {
			$info['ad_status_cn'] = $field_config['ad_status'][$info['ad_status']];
		}
		
		//开始结束时间处理
		$info['ad_starttime'] = date('Y-m-d', $info['ad_starttime']);
		$info['ad_endtime'] = date('Y-m-d', $info['ad_endtime']);
		
		$this->assign('info', $info);
		
		$this->display();
	}
	
	/**
	 * 广告保存
	 */
	public function adSave() {
		C('TOKEN_ON', false);
	
		$data = $this->post;
		$data['ad_starttime'] = $data['time_min'];
		$data['ad_endtime'] = $data['time_max'];
	
		if (empty($data['ad_title'])) {
			$this->error('标题不能为空');
		}
		if (!validateExtend($data['ad_amount'], 'MONEY')) {
			$this->error('广告现金投放单价格式有误');
		}
		if (!validateExtend($data['ad_amount_credits'], 'MONEY')) {
		    $this->error('广告积分投放单价格式有误');
		}
		if (!validateExtend($data['ad_amount_max'], 'MONEY')) {
		    $this->error('创客现金封顶金额格式有误');
		}
		if (!validateExtend($data['ad_amount_credits_max'], 'MONEY')) {
		    $this->error('创客积分封顶金额格式有误');
		}
		if (!validateExtend($data['ad_amount_vip_max'], 'MONEY')) {
		    $this->error('VIP现金封顶金额格式有误');
		}
		if (!validateExtend($data['ad_amount_vip_credits_max'], 'MONEY')) {
		    $this->error('VIP积分封顶金额格式有误');
		}
	
	    switch ($data['ad_type']) {
            case '1':
                if (!validateExtend($data['ad_link'], '/^(http|https):\/\/(.*)$/', true)) {
                    $this->error('外部链接地址格式有误');
                }
                break;
            case '2':
                if (!validateExtend($data['ad_link'], 'NUMBER')) {
                    $this->error('店铺ID格式有误');
                }
                break;
            case '3':
                if (!validateExtend($data['ad_link'], 'NUMBER')) {
                    $this->error('商品ID格式有误');
                }
                break;
        }
	
		if (!empty($data['ad_starttime'])) {
			$data['ad_starttime'] = strtotime($data['ad_starttime']);
		} else {
			unset($data['ad_starttime']);
		}
		if (!empty($data['ad_endtime'])) {
			$data['ad_endtime'] = strtotime($data['ad_endtime'].' 23:59:59');
		} else {
			unset($data['ad_endtime']);
		}
		unset($data['time_min']);
		unset($data['time_max']);
	
	
		//上传图片
		$upload_config = array(
			'file' => $_FILES['ad_image'],
			'path' => 'ad/'. date('Ymd'),
		);
		$Upload = new \Common\Controller\UploadController($upload_config);
		$upload_info = $Upload->upload();
		if (empty($upload_info['error'])) {
			$data['ad_image'] = $upload_info['data']['url'];
		}
	
		$AdModel = new AdModel();
		$result = $AdModel->adSave($data, 'ad_id='.$data['ad_id']);
		if ($result === false) {
			$this->error('保存失败');
		} else {
			$this->success('保存成功', U('Ad/index'), false, "成功编辑广告:{$data['ad_title']}[ID:{$data['ad_id']}]");
		}
	}
	
	/**
	 * 广告浏览记录
	 */
	public function adView() {
		$AdModel = new AdModel();
		
		$id = $this->get['id'];
		$page = $this->get['p']>1 ? $this->get['p'] : 1;
		
		if (!validateExtend($id, 'NUMBER')) {
			$this->error('参数有误');
		}
		
		//广告标题
		$ad_info = $AdModel->getAdInfo('ad_title', 'ad_id='.$id);
		if (!$ad_info) {
			$this->error('该广告已不存在');
		}
		$this->assign('ad_info', $ad_info);
		
		$data = $AdModel->getAdViewList('*', $page, 20, 'ad_id='.$id, 0);
		
		$list = $data['list'];
		foreach ($list as $k=>$v) {
			//浏览者账号信息
			$member_info = M('Member')->where('id='.$v['user_id'])->field('loginname,nickname,username,truename')->find();
			$list[$k]['loginname'] = $member_info ? $member_info['loginname'] : '';
			$list[$k]['nickname'] = $member_info ? $member_info['nickname'] : '';
			$list[$k]['username'] = $member_info ? $member_info['username'] : '';
			$list[$k]['truename'] = $member_info ? $member_info['truename'] : '';
		}
		$this->assign('list', $list);
		
		$this->Page($data['paginator']['totalPage']*$data['paginator']['everyPage'], $data['paginator']['everyPage'], $this->get);
		
		$this->display();
	}
	
	/**
	 * 停止/开启广告投放
	 */
	public function adStatus() {
	    $AdModel = new AdModel();
		
		$id = $this->get['id'];
		
		if (!validateExtend($id, 'NUMBER')) {
			$this->error('参数有误');
		}
		
		
		
		//判断广告是否存在并获取广告信息
		$info = $AdModel->getAdInfo('ad_title,ad_status', 'ad_id='.$id);
		if (!$info) {
		    $this->error('广告已不存在');
		}
		
		if ($info['ad_status'] == 3) {
		    $this->error('该广告已结束');
		}
		
		$data = [
		    'ad_status' => $info['ad_status']==2 ? 1 : 2
		];
		
		$title = $info['ad_status']==2 ? '停止' : '开启';
		
		if ($AdModel->adSave($data, 'ad_id='.$id) === false) {
		    $this->error('操作失败');
		}
		
		$this->success('操作成功', '', false, "成功{$title}广告投放[标题:{$info['ad_title']}[ID:{$id}]");
	}
	
	/**
	 * 广告删除
	 * 
	 * @internal 只有未被点击的广告才能被删除
	 */
	public function adDelete() {
	    $AdModel = new AdModel();
	    
	    $id = $this->get['id'];
	    
	    if (!validateExtend($id, 'NUMBER')) {
	        $this->error('参数有误');
	    }
	    
	    //判断广告是否存在并获取广告信息
	    $info = $AdModel->getAdInfo('ad_title', 'ad_id='.$id);
	    if (!$info) {
	        $this->error('广告已不存在');
	    }
	    
	    //判断广告是否已被点击过
	    $view_all = $AdModel->getAdViewList('count(*) count', false, 0, 'ad_id='.$id, 0);
	    $view_count = $view_all['list'][0]['count'];
	    if ($view_count > 0) {
	        $this->error('该广告已被点击过，无法删除');
	    }
	    
	    if ($AdModel->adDelete('ad_id='.$id) === false) {
	        $this->error('删除失败');
	    }
	    
	    $this->success('删除成功', '', false, "成功删除广告[标题:{$info['ad_title']}[ID:{$id}]");
	}
	
}
?>