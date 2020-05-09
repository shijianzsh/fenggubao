<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 新闻资讯接口
// +----------------------------------------------------------------------
namespace APP\Controller;

use Common\Controller\ApiController;
use V4\Model\NewsModel;

class NewsController extends ApiController
{

    /**
     * 新闻资讯列表
     *
     * @method POST
     *
     * @param int $page 当前页面(默认:1)
     * @param int $category_id 分类ID(默认:1)
     */
    public function index()
    {
    	$current_lang = getCurrentLang(true);
    	
        $page = $this->post['page'];
        $page = $page > 1 ? $page : 1;
        $category_id = empty($this->post['category_id']) ? 1 : $this->post['category_id'];

        if (!validateExtend($category_id, 'NUMBER')) {
            $this->myApiPrint('分类ID有误');
        }

        $NewsModel = new NewsModel();

        //轮播
        /*
        $data = $NewsModel->getList('id, title, cover', 1, 5);
        $return['lunbo'] = $data['list'];
        */
        $field_car_title = 'car_title'.$current_lang.' as car_title';
        $data = M('carousel')->field("car_id,".$field_car_title.",car_image")->where('car_type=2 and is_hidden=0')->order('sort desc,car_id desc')->select();
        foreach ($data as $k => $v) {
            $lunbo[$k] = [
                'id' => $v['car_id'],
                'title' => $v['car_title'],
                'cover' => $v['car_image'],
                'link' => getLunboLink($v['car_id'])
            ];
        }
        $return['lunbo'] = $lunbo;

        //分类
        $data = C('FIELD_CONFIG')['news']['category'];
        $category_list = [];
        foreach ($data as $k => $v) {
            $category_list[] = [
                'id' => $k,
                'name' => $v
            ];
        }
        $return['category_list'] = $category_list;

        //列表
        $map_news = [
            'category' => array('eq', $category_id)
        ];
        $field_title = 'title'.$current_lang.' as title';
        $field_content = 'content'.$current_lang.' as content';
        $data = $NewsModel->getList('id, '.$field_title.', '.$field_content.', cover, created_at, updated_at', $page, 10, $map_news);
        $list = $data['list'];
        $return['list'] = $list;

        $this->myApiPrint('查询成功', 400, $return);
    }

    /**
     * 新闻资讯详情
     *
     * @method POST
     *
     * @param int $id 新闻资讯ID
     * @param int $type 输出方式(1:直接输出,2:渲染输出)[默认2]
     */
    public function details()
    {
    	$current_lang = getCurrentLang(true);
    	
        $id = $this->post['id'];
        $type = empty($this->post['type']) ? 2 : $this->post['type'];

        if (!validateExtend($id, 'NUMBER') || !validateExtend($type, 'NUMBER')) {
            $this->myApiPrint('参数格式有误', 300);
        }

        $newsModel = new NewsModel();

        $map_news['id'] = array('eq', $id);
        $data = $newsModel->getInfo('*', $map_news);
        
        $data['title'] = $data['title'.$current_lang];
        $data['content'] = $data['content'.$current_lang];

        switch ($type) {
            case '2':
                $data['content'] = U('APP/News/showNewsDetails/id/' . $data['id'], '', '', true);
                break;
        }

        $this->myApiPrint('查询成功', 400, $data);
    }

    /**
     * 新闻资讯详情渲染
     *
     * @method GET
     *
     * @param int $id 新闻资讯ID
     */
    public function showNewsDetails()
    {
    	$current_lang = getCurrentLang(true);
    	
        $id = $this->get['id'];

        if (!validateExtend($id, 'NUMBER')) {
            $this->myApiPrint('参数格式有误', 300);
        }

        $newsModel = new NewsModel();

        $map_news['id'] = array('eq', $id);
        $data = $newsModel->getInfo('*', $map_news);
        
        $data['title'] = $data['title'.$current_lang];
        $data['content'] = $data['content'.$current_lang];
        
        $this->assign('info', $data);

        $this->display();
    }

}

?>