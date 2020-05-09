<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 验证码
// +----------------------------------------------------------------------
namespace Admin\Controller;
use Think\Controller;
use Think\Verify;

class VerifyController extends Controller {
	
	public function index() {
		$Verify = new Verify();
		$Verify->fontSize = 14;
		$Verify->useImgBg = false;
		$Verify->fontttf = '7.ttf';
		$Verify->imageH = 35;
		$Verify->imageW = 85;
		$Verify->useCurve = false;
		$Verify->useNoise = false;
		$Verify->bg = array(255,255,255);
		$Verify->length = 4;
		$Verify->angle = false; //不旋转字体
		$Verify->codeType = 'string'; //设置统一英文
		$Verify->codeUL = 'lower'; //设置小写
		$Verify->codeNX =  '15'; //设置字符左间距
		$Verify->entry();
		$this->display();
	}
	
}
?>