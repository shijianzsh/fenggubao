<?php

namespace V4\Model;

/**
 * 新闻资讯模型
 *
 */
class NewsModel extends BaseModel
{

    //实例化新闻资讯模型
    protected function M()
    {
        return M('News');
    }


    /**
     * 获取新闻资讯列表
     *
     * @param string $fields 获取字段
     * @param int $page 当前页数,当为false时视为不分页
     * @param int $listRows 每页个数
     * @param string $actionwhere 筛选条件
     *
     * @return array
     */
    public function getList($fields = '*', $page = 1, $listRows = 10, $actionwhere = '')
    {
        //当$page为false时不分页
        $_totalRows = 0;
        if ($page !== false) {
            $_totalRows = $this->M()->where($actionwhere)->count(0);
        }

        $list = $this->M()->field($fields)->where($actionwhere);

        if ($page !== false) {
            $list = $list->page($page, $listRows);
        }

        $list = $list->order('sort asc,id desc')->select();

        return [
            'paginator' => $this->paginator($_totalRows, $listRows),
            'list' => $list,
        ];
    }

    /**
     * 获取新闻资讯详情
     *
     * @param string $fields 获取字段
     * @param string $actionwhere 筛选条件
     *
     * @return array
     */
    public function getInfo($fields = '*', $actionwhere = '')
    {
        $info = $this->M()->field($fields);
        $info = empty($actionwhere) ? $info : $info->where($actionwhere);
        $info = $info->find();
        $info['content'] = htmlspecialchars_decode(html_entity_decode($info['content']));
        //随机获取一个附件头域名
        $attach_domain_key = array_rand( C( 'DEVICE_CONFIG' )[ C( 'DEVICE_CONFIG_DEFAULT' ) ]['attach_domain'], 1 );
        $attach_domain     = C( 'DEVICE_CONFIG' )[ C( 'DEVICE_CONFIG_DEFAULT' ) ]['attach_domain'][ $attach_domain_key ];
        $info['content'] = str_replace('http://apifgb.fenggubao.com/', $attach_domain, $info['content']);
        return $info;
    }

}
