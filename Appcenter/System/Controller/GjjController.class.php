<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 大中华区区域管理
// +----------------------------------------------------------------------
namespace System\Controller;
use Common\Controller\AuthController;

class GjjController extends AuthController {
    /**
     * 大中华区管理
     */
	public function regions(){
        $list = M('GjjRegions')->field('id,name')->group('name')->order('id asc')->select();
        foreach ($list as $k=>&$v){
            $v['children'] = M('GjjRegions')->where(array('name'=>$v['name']))->select();
        }
        $this->assign('list', $list);

        $this->display();
    }

    /**
     * id 列表页区域添加
     * 区域添加
     */
    public function regionAdd(){
	    $id = I('id');
	    $data = M('GjjRegions')->where(array('id'=>$id))->find();
	    $this->assign('data',$data);
	    $provices = M('Province')->select();
	    $this->assign('province',$provices);
	    $this->display();
    }

    /**
     * 添加处理
     */
    public function regionInsert(){
        $name = I('name');
        $province = I('province');
        if(empty($name)){
            $this->error('区域名称必须填写');
        }
        $find_province = M('GjjRegions')->where(array('province'=>$province))->find();
        if($find_province){
            $this->error('该省份已经存在其他区域');
        }
        $data = array('name'=>$name,'province'=>$province);
        if(M('GjjRegions')->add($data)){
            $this->success('添加成功',U('Gjj/regions'));
        }else{
            $this->error('添加失败',U('Gjj/regions'));
        }
    }

    public function edit(){
	    $p = I('p');
	    $chl = I('chl');
	    $id = I('id');
	    $data = M('GjjRegions')->where(array('id'=>$id))->find();
        $provices = M('Province')->select();
        $this->province=$provices;
	    $this->p= $p;
	    $this->chl= $chl;
	    $this->data = $data;
	    $this->display();
    }

    public function regionSave(){
        $p = I('p');
        $chl = I('chl');
        $id = I('id');
        $name = I('name');
        $province = I('province');
        if($p){
            $data = array('name'=>$name);
        }

        if($chl){
            $is_exist = M('GjjRegions')->where(array('province'=>$province))->find();
            if($is_exist){
                $this->error('该省份已经存在其他区域');
            }else{
              $data = array('name'=>$name,'province'=>$province);
            }
        }
        $res = M('GjjRegions')->where(array('id'=>$id))->save($data);
        if($res){
            $this->success('修改成功',U('Gjj/regions'));
        }else{
            $this->error('修改失败');
        }
    }

    /**
     * p 区域删除
     * chl 区域下的子集删除
     */
    public function regionDel(){
        $p = I('p');
        $chl = I('chl');
        $id = I('id');
        if($p){
            $find_this = M('GjjRegions')->where(array('id'=>$id))->find();
            $find_child = M('GjjRegions')->where(array('name'=>$find_this['name']))->count();
            if($find_child>1){
                $this->error('该区域下面有子集，请先删除下面的子集');
            }else{
                $res = M('GjjRegions')->where(array('id'=>$id))->delete();
            }
        }
        if($chl){
            $res = M('GjjRegions')->where(array('id'=>$id))->delete();
        }

        if($res){
            $this->success('删除成功',U('Gjj/regions'));
        }else{
            $this->error('删除失败');
        }

    }
	
}
?>