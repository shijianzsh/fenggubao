<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 图片处理通用类封装 (需要GD库支持)
// +----------------------------------------------------------------------
namespace Common\Controller;
use Think\Controller;

class ImageController extends Controller {
	
	private $image; //图片处理对象
	
	public $image_url = false; //要处理的图片(图片完整路径)
	public $image_new_url = false; //处理后生成的新图片,false则默认覆盖要处理的图片(图片完整路径)
	public $image_width = 100; //生成新图宽度
	public $image_height = 100; //生成新图高度
	
	public $image_cut_x = 0; //默认裁剪开始x坐标
	public $image_cut_y = 0; //默认裁剪开始y坐标
	
	public $image_thumb_type = 1; //缩略图生成类型
	
	public $image_water_url = false; //水印图片
	public $image_water_pos = 9; //水印生成位置
	public $image_water_alpha = 100; //水印透明度
	
	public function __construct() {
		parent::__construct();
		
		$this->image = new \Think\Image();
	}
	
	/**
	 * 获取对应的缩略图生成类型标识
	 * @param $type 缩略图生成类型参数
	 */
	private function thumbType($type) {
		switch ($type) {
			case 1:
				return \Think\Image::IMAGE_THUMB_SCALE; //等比例缩放类型
				break;
			case 2:
				return \Think\Image::IMAGE_THUMB_FILLED; //缩放后填充类型
				break;
			case 3:
				return \Think\Image::IMAGE_THUMB_CENTER; //居中裁剪类型
				break;
			case 4:
				return \Think\Image::IMAGE_THUMB_NORTHWEST; //左上角裁剪类型
				break;
			case 5:
				return \Think\Image::IMAGE_THUMB_SOUTHEAST; //右下角裁剪类型
				break;
			case 6:
				return \Think\Image::IMAGE_THUMB_FIXED; //固定尺寸缩放类型
				break;
			default:
				return \Think\Image::IMAGE_THUMB_SCALE;
		}
	}
	
	/**
	 * 获取对应的水印生成位置
	 * @param $pos 水印生成位置
	 */
	private function waterPos($pos) {
		switch ($pos) {
			case 1:
				return \Think\Image::IMAGE_WATER_NORTHWEST; //左上角水印
				break;
			case 2:
				return \Think\Image::IMAGE_WATER_NORTH; //上居中水印
				break;
			case 3:
				return \Think\Image::IMAGE_WATER_NORTHEAST; //右上角水印
				break;
			case 4:
				return \Think\Image::IMAGE_WATER_WEST; //左居中水印
				break;
			case 5:
				return \Think\Image::IMAGE_WATER_CENTER; //居中水印
				break;
			case 6:
				return \Think\Image::IMAGE_WATER_EAST; //右居中水印
				break;
			case 7:
				return \Think\Image::IMAGE_WATER_SOUTHWEST; //左下角水印
				break;
			case 8:
				return \Think\Image::IMAGE_WATER_SOUTH; //下居中水印
				break;
			case 9:
				return \Think\Image::IMAGE_WATER_SOUTHEAST; //右下角水印
				break;
			default:
				return \Think\Image::IMAGE_WATER_CENTER;
		}
	}
	
	/**
	 * 返回常用参数
	 * @return array(
	 *     'url' => 图片相对地址,
	 *     'width' => 图片宽度,
	 *     'height' => 图片高度,
	 *     'mime' => 图片MIME类型
	 *     'size' => 图片大小,
	 *     'type' => 图片类型,
	 * )
	 */
	private function returnParam() {
		$data = array();
		
		$data['url'] = str_replace($_SERVER['DOCUMENT_ROOT'], '', $this->image_new_url);
		$data['width'] = $this->image->width();
		$data['height'] = $this->image->height();
		$data['mime'] = $this->image->mime();
		$data['size'] = $this->image->size();
		$data['type'] = $this->image->type();
		
		return $data;
	}
	
	
	/**
	 * 裁切图片
	 */
	public function cut() {
		if (!$this->image_url) {
			getReturn('未指定待处理图片');
		}
		
		$this->image_new_url = $this->image_new_url ? $this->image_new_url : $this->image_url;
		
		$this->image->open($this->image_url);
		$this->image->crop($this->image_width, $this->image_height, $this->image_cut_x, $this->image_cut_y)->save($this->image_new_url);
		
		if ($this->image_water_url) {
			$this->image_url = $this->image_new_url;
			$this->water();
		}
		
		return $this->returnParam();
	}
	
	/**
	 * 生成缩略图
	 */
	public function thumb() {
		if (!$this->image_url) {
			getReturn('未指定待处理图片');
		}
		
		if (!$this->image_new_url) {
			$image_arr = explode('.', $this->image_url);
			$image_ext = $image_arr[count($image_arr)-1];
			$this->image_new_url = str_replace('.'.$image_ext, '', $this->image_url). '-'. $this->image_width. 'x'. $this->image_height. '.'. $image_ext;
		}
		
		$this->image->open($this->image_url);
		$this->image->thumb($this->image_width, $this->image_height, $this->thumbType($this->image_thumb_type))->save($this->image_new_url);
		
		if ($this->image_water_url) {
			$this->image_url = $this->image_new_url;
			$this->water();
		}
		
		return $this->returnParam();
	}
	
	/**
	 * 添加水印
	 */
	public function water() {
		if (!$this->image_url) {
			getReturn('未指定待处理图片');
		}
		
		if (!$this->image_water_url) {
			getReturn('未指定水印图片');
		}
		
		$this->image_new_url = $this->image_new_url ? $this->image_new_url : $this->image_url;
		
		$this->image->open($this->image_url);
		$this->image->water($this->image_water_url, $this->waterPos($this->image_water_pos), $this->image_water_alpha)->save($this->image_new_url);
		
		return $this->returnParam();
	}

}
?>