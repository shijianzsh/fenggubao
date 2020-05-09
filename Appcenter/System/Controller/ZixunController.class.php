<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 新闻资讯管理
// +----------------------------------------------------------------------
namespace System\Controller;

use Common\Controller\AuthController;

class ZixunController extends AuthController
{

    /**
     * 新闻资讯管理
     */
    public function index()
    {
        $News = M('News');

        $map = array();

        if (!empty($this->get['title'])) {
            $map['title'] = array('like', "%{$this->get['title']}%");
        }
        if (!empty($this->get['category'])) {
        	$map['category'] = ['eq', $this->get['category']];
        }

        $count = $News->where($map)->count();
        $limit = $this->Page($count, 20, $this->get);

        $list = $News->where($map)->order('sort asc,id desc')->limit($limit)->select();
        foreach ($list as $k=>$v) {
        	$list[$k]['category_cn'] = C('FIELD_CONFIG')['news']['category'][$v['category']];
        }
        $this->assign('list', $list);
        
        $category_list = C('FIELD_CONFIG')['news']['category'];
        $this->assign('category_list', $category_list);

        $this->display();
    }

    /**
     * 新闻资讯添加UI
     */
    public function addUi()
    {
    	$category_list = C('FIELD_CONFIG')['news']['category'];
    	$this->assign('category_list', $category_list);
    	
        $this->display();
    }

    /**
     * 新闻资讯添加
     */
    public function add()
    {
        $News = M('News');

        C('TOKEN_ON', false);

        $data = $this->post;
        $data['content'] = $_POST['content'];
        $data['created_at'] = empty($data['created_at']) ? date('Y-m-d H:i:s') : $data['created_at'];
        $data['updated_at'] = empty($data['updated_at']) ? date('Y-m-d H:i:s') : $data['updated_at'];
        
        if (empty($data['title'])) {
            $this->error('标题不能为空');
        }
        if (empty($data['content'])) {
            $this->error('内容不能为空');
        }

        //上传封面
        $upload_config = array(
            'file' => $_FILES['cover'],
            'path' => 'zixun/' . date('Ymd'),
        );
        $Upload = new \Common\Controller\UploadController($upload_config);
        $upload_info = $Upload->upload();
        if (empty($upload_info['error'])) {
            $data['cover'] = $upload_info['data']['url'];
        }

        if (!$News->create($data, '', true)) {
            $this->error($News->getError());
        } else {
            $id = $News->add($data);
            $this->success('添加成功', U('Zixun/index'), false, "添加新闻资讯:{$data['title']}[ID:{$id}]");
        }
    }

    /**
     * 新闻资讯编辑
     */
    public function modify()
    {
        $News = M('News');

        $id = $this->get['id'];

        if (!validateExtend($id, 'NUMBER')) {
            $this->error('参数格式有误');
        }

        $map['id'] = array('eq', $id);
        $info = $News->where($map)->find();
        if (!$info) {
            $this->error('该信息已不存在');
        }

        $this->assign('info', $info);
        
        $category_list = C('FIELD_CONFIG')['news']['category'];
        $this->assign('category_list', $category_list);

        $this->display();
    }

    /**
     * 新闻资讯保存
     */
    public function save()
    {
        $News = M('News');

        $data = $this->post;
        $data['content'] = $_POST['content'];
        $data['created_at'] = empty($data['created_at']) ? date('Y-m-d H:i:s') : $data['created_at'];
        $data['updated_at'] = empty($data['updated_at']) ? date('Y-m-d H:i:s') : $data['updated_at'];

        //上传封面
        $upload_config = array(
            'file' => $_FILES['cover'],
            'path' => 'zixun/' . date('Ymd'),
        );
        $Upload = new \Common\Controller\UploadController($upload_config);
        $upload_info = $Upload->upload();
        if (empty($upload_info['error'])) {
            $data['cover'] = $upload_info['data']['url'];
        }

        if ($News->save($data) === false) {
            $this->error('保存失败');
        } else {
            $this->success('保存成功', U('Zixun/index'), false, "编辑新闻资讯:{$this->post['title']}[ID:{$this->post['id']}]");
        }
    }

    /**
     * 新闻资讯删除
     */
    public function delete()
    {
        $News = M('News');

        $id = $this->get['id'];

        if (!validateExtend($id, 'NUMBER')) {
            $this->error('参数格式有误');
        }

        $map['id'] = array('eq', $id);
        $info = $News->where($map)->field('title')->find();
        if (!$info) {
            $this->error('该信息已不存在');
        }

        if ($News->where($map)->delete() === false) {
            $this->error('删除失败');
        } else {
            $this->success('删除成功', U('Zixun/index'), false, "删除新闻资讯:{$info['title']}[ID:{$id}]");
        }
    }

}

?>