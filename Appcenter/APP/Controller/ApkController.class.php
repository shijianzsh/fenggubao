<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 创客相关接口
// +----------------------------------------------------------------------
namespace APP\Controller;

use Think\Controller;

class ApkController extends Controller
{

    public function latest()
    {
        $dowloadUrl = M('apk_manage')->order('id desc')->getField('src');
        header(sprintf('Location: %s', $dowloadUrl));
        die;
    }
}