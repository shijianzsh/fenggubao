<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 商家粉丝管理
// +----------------------------------------------------------------------

namespace Merchant\Controller;
use Common\Controller\AuthController;

class FansController extends AuthController {
	
	public function index() {
		//获取商家
		$map_store['uid'] = array('eq', session('admin_mid'));
		$map_store['status'] = array('eq', '0');
		$map_store['manage_status'] = array('eq', '1');
		$storeid = M('store')->where($map_store)->getField('id');
		if(!$storeid){
			$this->error('店铺不存在');
		}
		
		$where['f.storeid'] = $storeid;
		$where['f.favorite'] = '1';
		//分页
		$ps='15';
		$pn = intval($_GET['p'])<1?1:intval($_GET['p']);
		$count = M('favorite_store f')->where($where)->count();
		$page = new \Think\Page($count, $ps, $this->get);
		$show = $page->show();
		$this->assign("page", $show);
		
		//查询列表
		$datalist = M('favorite_store f')->field('f.*, m.nickname, m.loginname, m.img, m.username')
					->join('left join zc_store as s on s.id = f.storeid ')
					->join('left join zc_member as m on m.id = f.uid ')
					->where($where)->limit(($pn-1)*$ps.','.$ps)->order('id asc')->select();
		$this->assign("datalist", $datalist);
		//unittest($datalist);
		
		$this->display();
	}
	
	
}
?>