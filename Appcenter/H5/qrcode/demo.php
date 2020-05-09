<?php
/* example this file name : /qrcode/qrcode.php */

include_once 'phpqrcode.php';
$errorCorrectionLevel = 'L'; //容错级别
$matrixPointSize = 3; //生成图片大小

QRcode::png($url, 'qrcode.png', $errorCorrectionLeve, $matrixPointSize);

$bg = 'http://'.$_SERVER['HTTP_HOST'].'/qrcode/zhiwen.png';
$QR = 'http://'.$_SERVER['HTTP_HOST'].'/qrcode/qrcode.png';

if ($bg !== false) {
	$QR = imagecreatefromstring(file_get_contents($QR));
    $bg = imagecreatefromstring(file_get_contents($bg));
    $QR_width = imagesx($QR);//二维码图片宽度
    $QR_height = imagesy($QR);//二维码图片高度
    $bg_width = imagesx($bg);//logo图片宽度
    $bg_height = imagesy($bg);//logo图片高度

    $from_width = ($bg_width - $QR_width) / 2;

    //重新组合图片并调整大小
    imagecopy($bg, $QR, $from_width, $from_width , 0, 0 , $QR_width, $QR_height);
}

imagepng($bg);


/*-----------前端调用方式---------------
<img src="{php echo 'http://'.$_SERVER['HTTP_HOST'].'/qrcode/qrcode.php?url=XXX.com">
*/
 