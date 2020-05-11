<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------

/**
 * 通用CURL封装
 * @param string $url url地址
 * @param string $type HTTP方式(post,get)
 * @param array $param 传值数组
 * @param string $header header头信息
 * @param int $timeout 允许执行的最长秒数
 */
function get_by_curl($url, $type='post', $param='', $header='', $timeout=false) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	if ($timeout) {
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	}

	if ($type=='post') {
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
			
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
	}

	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

	$data = curl_exec($ch);

	curl_close($ch);

	return $data;
}

/**
 * 数组转为字符串
 *
 * @param array $data 待处理数组
 * @param string $dot 字符串连接标记
 * @param string $str 用于中间缓存
 */
function arrayToString($data, $dot = ',', $str = '')
{
    foreach ($data as $k => $v) {
        if (is_array($v)) {
            $str .= arrayToString($v, $dot, $str);
        } else {
            $str = empty($str) ? $v : $dot . $v;
        }
    }

    return $str;
}

/**
 * 通用验证函数扩展
 *
 * @param string $data 待验证数据
 * @param string $method 验证方法名 (从通用配置文件中获取对应正则信息)
 * @param boolean $exp 正则是否自定义,默认否
 */
function validateExtend($data, $method, $exp = false)
{
    if ((!is_numeric($data) && empty($data)) || empty($method)) {
        return false;
    }

    if (!$exp) {
        $common_validate = C('COMMON_VALIDATE');
        $method = isset($common_validate[$method]) ? $common_validate[$method] : false;
    }

    if ($method) {
        return preg_match($method, $data);
    } else {
        return false;
    }
}

/**
 * 输出指定格式的时间日期
 *
 * @param string $time_stamp 时间戳 [默认为当前时间戳]
 * @param string $format 显示格式[默认为Y-m-d格式]
 */
function getDateByFormat($time_stamp = '', $format = '')
{
    $time_stamp = empty($time_stamp) ? time() : $time_stamp;
    $format = empty($format) ? 'Y-m-d' : $format;

    $format_week = false;
    $week = ['星期日', '星期一', '星期二', '星期三', '星期四', '星期五', '星期六'];

    if (stripos($format, 'w')) {
        $format_date = str_replace('w', '', $format);
        $format_week = true;
    } else {
        $format_date = $format;
    }

    $return = date($format_date, $time_stamp);
    $return .= $format_week ? $week[date('w', $time_stamp)] : '';

    return $return;
}

/**
 * 通用MD5字符串生成
 *
 * @param string/array $data 待处理数据
 */
function getMd5($data = '')
{
    return md5($data . rand(1, 10000) . time());
}

/**
 * 通用return返回数组封装
 *
 * @param string $error 提示信息
 * @param mixed $data 返回数据
 */
function getReturn($error = '', $data = '')
{
    $return = array('error' => $error, 'data' => $data);

    empty($error) && empty($data) && $return['error'] = '无参数';

    return $return;
}

/**
 * 通用字符串安全过滤(左右空格清除,中间空格清除,...)
 *
 * @param string $str 待处理的字符串
 * @param string $type 处理方式
 */
function safeString($str, $type = '')
{
    if (!empty($str)) {
        switch ($type) {
            case 'trim': //左右空格
                $str = trim($str);
                break;
            case 'space': //中间空格
                $str = str_replace(' ', '', $str);
                break;
            case 'trim_space': //左右和中间空格
                $str = trim($str);
                $str = str_replace(' ', '', $str);
                break;
            case 'lower': //小写
                $str = strtolower(str);
                break;
            case 'upper': //大写
                $str = strtoupper($str);
                break;
            case 'ucfirst': //首字母大写
                $str = ucfirst($str);
                break;
            default:
                $str = $str;
        }
    }

    return $str;
}


/************************ZCSH.OLD********************/

/**
 * 格式化字节大小
 *
 * @param  number $size 字节数
 * @param  string $delimiter 数字和单位分隔符
 *
 * @return string            格式化后的带单位的大小
 */

function format_bytes($size, $delimiter = '')
{
    $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
    for ($i = 0; $size >= 1024 && $i < 5; $i++) {
        $size /= 1024;
    }

    return round($size, 2) . $delimiter . $units[$i];
}

/**
 * 格式化日期
 */
function format_date($data, $t = false)
{
    if ($data == "" || $data == 0) {
        return '';
    } else {
        if ($t) {
            $re_data = date('Y-m-d H:i:s', $data);
        } else {
            $re_data = date('Y-m-d', $data);
        }
        if ($re_data == '1970-01-01' || $re_data == '1970-01-01 0:0:0') {
            return '';
        } else {
            return $re_data;
        }
    }
}

//根据mid获取id
function getUser($uid, $tag)
{
    if ($uid == "") {
        return false;
    }

    return M('member')->where("id=" . $uid)->getField($tag);
}

//根据mid获取userid
function getzUser($uid, $tag)
{
    if ($uid == "") {
        return false;
    }

    $map['id'] = array('eq', $uid);
    $map['loginname'] = array('eq', $uid);
    $map['nickname'] = array('eq', $uid);
    $map['truename'] = array('eq', $uid);
    $map['_logic'] = 'OR';

    return M('member')->where($map)->getField($tag);
}

function getTable($id, $table, $tag)
{
    if ($id == "") {
        return false;
    }

    return M($table)->where("id=" . $id)->getField($tag);
}

/**
 * 字符串截取，支持中文和其他编码
 * @static
 * @access public
 *
 * @param string $str 需要转换的字符串
 * @param string $start 开始位置
 * @param string $length 截取长度
 * @param string $charset 编码格式
 * @param string $suffix 截断显示字符
 *
 * @return string
 */
function msubstr($str, $start = 0, $length, $charset = "utf-8", $suffix = true)
{
    if (function_exists("mb_substr")) {
        $slice = mb_substr($str, $start, $length, $charset);
    } elseif (function_exists('iconv_substr')) {
        $slice = iconv_substr($str, $start, $length, $charset);
    } else {
        $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        preg_match_all($re[$charset], $str, $match);
        $slice = join("", array_slice($match[0], $start, $length));
    }

    $tag = '...';
    if (strlen($str) <= $length) {
        $tag = '';
    }

    return $suffix ? $slice . $tag : $slice;
}

//去除数组元素中的空格
function mytrim($var, $delimiter = ' ', $removehtml = false)
{
    if (is_array($var)) {
        foreach ($var as $key => $value) {
            if (is_array($value)) {
                $var[$key] = arraytrim($value, $delimiter, $removehtml);
            } else {
                if ($removehtml) {
                    $var[$key] = strip_tags(trim($value, $delimiter));
                } else {
                    $var[$key] = trim($value, $delimiter);
                }
            }
        }
    } else {
        if ($removehtml) {
            $var = strip_tags(trim($var, $delimiter));
        } else {
            $var = trim($var, $delimiter);
        }

    }

    return $var;
}

//去除数组元素中的空格
function trimarray($inpval)
{
    if (!is_array($inpval)) {
        return trim($inpval);
    }

    return array_map('trimarray', $inpval);
}

//奖项名称
function bonusname($key)
{
    switch ($key) {
        case 1:
            return '推广奖';
            break;
        case 2:
            return '重复消费';
            break;
        case 3:
            return '商家联盟';
            break;
        case 4:
            return '服务中心';
            break;
        case 5:
            return '区域合伙人';
            break;
        case 8:
            return '服务中心见点';
            break;
        case 9:
            return '区域合伙人见点';
            break;
        //case 15:
        //return '升级创客';
        //break;
        default:
            break;
    }
}

function accounttxt($key)
{
    // echo $key;
    switch ($key) {
        case 'cash':
            return '电子币';
            break;
        case 're_bonus':
            return '代数佣金钱包';
            break;
        case 'dot_bonus':
            return '见点佣金钱包';
            break;
        case 'stabonus':
            return '静态佣金钱包';
            break;
        case 'bonus':
            return '佣金钱包';
            break;
        case 'virtual_cash':
            return '消费积分钱包';
            break;
        case 'repeat_cash':
            return '重复消费钱包';
            break;

        default:
            break;
    }
}

function get_rand_char($length = 10)
{
    $str = null;
    $strPol = "ABCDEFGHJKMNPQRSTUVWXYZ123456789abcdefghjkmnpqrstuvwxyz";
    $max = strlen($strPol) - 1;

    for ($i = 0; $i < $length; $i++) {
        $str .= $strPol[rand(0, $max)];//rand($min,$max)生成介于min和max两个数之间的一个随机整数
    }

    return $str;
}

function IsMobile($no)
{
    return preg_match('/^1[34578][\d]{9}$/', $no) || preg_match('/^0[\d]{10,11}$/', $no);
}

function guid()
{
    if (strtolower(MODULE_NAME) == 'admin') {
        $_uid = session('admin_id');
    } else {
        $_uid = session('user_id');
    }
    if ($_uid == "") {
        return false;
    } else {
        return $_uid;
    }
}

function guserid()
{
    if (strtolower(MODULE_NAME) == 'admin') {
        $_userid = session('admin_userid');
    } else {
        $_userid = session('userid');
    }
    if ($_userid == "") {
        return false;
    } else {
        return $_userid;
    }
}

function gsession($tag = 'id')
{
    if (strtolower(MODULE_NAME) == 'admin') {
        return session('admin_' . $tag);
    } else {
        return session($tag);
    }
}

function isboss()
{
    $_uid = guid();
    if ($_uid == "" || $_uid == false) {
        redirect(__APP__ . '/');
    }
    $_is_boss = M("member")->where("id=" . $_uid)->getField('is_boss');
    if ($_is_boss == '1') {
        return true;
    } else {
        return false;
    }
}

/**
 * 生成随机字符串
 *
 * @param int $length 要生成的随机字符串长度
 * @param string $type 随机码类型：0，数字+大小写字母；1，数字；2，小写字母；3，大写字母；4，特殊字符；-1，数字+大小写字母+特殊字符
 *
 * @return string
 */
function randCode($length = 5, $type = 0)
{
    $arr = array(
        1 => "0123456789",
        2 => "abcdefghijklmnopqrstuvwxyz",
        3 => "ABCDEFGHIJKLMNOPQRSTUVWXYZ",
        4 => "~@#$%^&*(){}[]|"
    );
    if ($type == 0) {
        array_pop($arr);
        $string = implode("", $arr);
    } elseif ($type == "-1") {
        $string = implode("", $arr);
    } else {
        $string = $arr[$type];
    }
    $count = strlen($string) - 1;
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $string[mt_rand(0, $count)];
    }

    return $code;
}

/**
 * 发送HTTP请求方法
 *
 * @param  string $url 请求URL
 * @param  array $params 请求参数
 * @param  string $method 请求方法GET/POST
 *
 * @return array  $data   响应数据
 */
function http($url, $params, $method = 'GET', $header = array(), $multi = false)
{
    $opts = array(
        CURLOPT_TIMEOUT => 30,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER => $header
    );
    /* 根据请求类型设置特定参数 */
    switch (strtoupper($method)) {
        case 'GET':
            $opts[CURLOPT_URL] = $url . '?' . http_build_query($params);
            break;
        case 'POST':
            //判断是否传输文件
            $params = $multi ? $params : http_build_query($params);
            $opts[CURLOPT_URL] = $url;
            $opts[CURLOPT_POST] = 1;
            $opts[CURLOPT_POSTFIELDS] = $params;
            break;
        default:
            throw new Exception('不支持的请求方式！');
    }
    /* 初始化并执行curl请求 */
    $ch = curl_init();
    curl_setopt_array($ch, $opts);
    $data = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    if ($error) {
        throw new Exception('请求发生错误：' . $error);
    }

    return $data;
}

/**
 * 用户数据 DES加密
 *
 * @param String $str 需要加密的字串
 * @param String $skey 加密EKY
 *
 * @return String
 */
function myDes_encode($str, $key)
{
    $va = \Think\Crypt\Driver\Des::encrypt($str, $key . C('PASS_KEY'));
    $va = base64_encode($va);

    return str_replace(array('+', '/'), array('-', '_'), $va);
}

/**
 * 用户数据 DES解密
 *
 * @param String $str 需要解密的字串
 * @param String $skey 解密KEY
 *
 * @return String
 */
function myDes_decode($str, $key)
{
    $str = str_replace(array('-', '_'), array('+', '/'), $str);
    $str = base64_decode($str);
    $va = \Think\Crypt\Driver\Des::decrypt($str, $key . C('PASS_KEY'));

    return trim($va);
}

/**
 * 多维数组排序
 *
 * @param $multi_array :多维数组名称
 * @param $sort_key :二维数组的键名
 * @param $sort :排序常量    SORT_ASC || SORT_DESC
 */
function multi_array_sort(&$multi_array, $sort_key, $sort = SORT_DESC)
{
    if (is_array($multi_array)) {
        foreach ($multi_array as $row_array) {
            if (is_array($row_array)) {
                //把要排序的字段放入一个数组中，
                $key_array[] = $row_array[$sort_key];
            } else {
                return false;
            }
        }
    } else {
        return false;
    }
    array_multisort($key_array, $sort, $multi_array);

    return $multi_array;
}

/**
 * 根据坐标计算范围-公里
 * Enter description here ...
 *
 * @param unknown_type $lat
 * @param unknown_type $lon
 * @param unknown_type $raidus
 */
function getAround($lat, $lng, $raidus)
{
    $myLng = $lng;
    $myLat = $lat;

    $half = 6371;  //6371表示公里； 6.371表示米
    $dlng = 2 * asin(sin($raidus / (2 * $half)) / cos(deg2rad($lat)));
    $dlng = rad2deg($dlng);
    $dlat = $raidus / $half;
    $dlat = rad2deg($dlat);

    $vo['min_lat'] = $lat - $dlat;
    $vo['max_lat'] = $lat + $dlat;
    $vo['max_lng'] = $lng + $dlng;
    $vo['min_lng'] = $lng - $dlng;

    /*
    //以下为核心代码
    $range = 180 / pi() * $raidus / 6372.797;     //里面的 1 就代表搜索 1km 之内，单位km
    $lngR = $range / cos($myLat * pi() / 180);
    $maxLat = $myLat + $range;//最大纬度
    $minLat = $myLat - $range;//最小纬度
    $maxLng = $myLng + $lngR;//最大经度
    $minLng = $myLng - $lngR;//最小经度

    $vo['min_lat'] = $minLat;
    $vo['max_lat'] = $maxLat;
    $vo['max_lng'] = $maxLng;
    $vo['min_lng'] = $minLng;
    */

    return $vo;
}

/**
 * 计算两个经纬度距离-米
 * Enter description here ...
 *
 * @param unknown_type $lat1
 * @param unknown_type $lng1
 * @param unknown_type $lat2
 * @param unknown_type $lng2
 */
function getDistance($lat1, $lng1, $lat2, $lng2)
{
    $earthRadius = 6367000; //approximate radius of earth in meters

    $lat1 = ($lat1 * pi()) / 180;
    $lng1 = ($lng1 * pi()) / 180;

    $lat2 = ($lat2 * pi()) / 180;
    $lng2 = ($lng2 * pi()) / 180;

    $calcLongitude = $lng2 - $lng1;
    $calcLatitude = $lat2 - $lat1;
    $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
    $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
    $calculatedDistance = $earthRadius * $stepTwo;

    return round($calculatedDistance);
}


function deloldimg($filename)
{
    $file = $_SERVER['DOCUMENT_ROOT'] . $filename;
    if (file_exists($file) && is_file($file)) {
        @unlink($file);
    }
}

/*
* 单元测试
*/
function fuck($array, $array2 = array(), $array3 = array(), $exit = true)
{
    M()->rollback();
    echo '<pre>';
    print_r($array);
    print_r($array2);
    print_r($array3);
    if ($exit) {
        exit;
    }
}

/**
 * 生产缩略图，参数图片路径是不带点
 * Enter description here ...
 *
 * @param unknown_type $photo1
 */
function createThumb($photo1)
{
    $image = new \Think\Image();
    $image->open('.' . $photo1);               // 生成缩略图
    $path_arr = explode('.', $photo1);
    $img_temp_name = $path_arr[0];
    $img_temp_ext = $path_arr[1];

    $smallimg = '.' . $img_temp_name . '_sm.' . $img_temp_ext;
    //$image->thumb(500, 500, $image::IMAGE_THUMB_CENTER)->save($smallimg);
    $image->thumb(500, 500, $image::IMAGE_THUMB_FIXED)->save($smallimg);

    return $smallimg;
}

function createThumbScal($photo1, $w = 500, $h = 500)
{
    $image = new \Think\Image();
    $image->open('.' . $photo1);               // 生成缩略图
    $image->thumb($w, $h, $image::IMAGE_THUMB_CENTER)->save('.' . $photo1);

    return $smallimg;
}

/**
 * 图片加水印
 */
function makeWater($photo1)
{
    //$photo1 = '/Uploads/product/20161110/3b510da780714c67b4257753a8d2c10b.jpg';
    $image = new \Think\Image();
    $image->open('.' . $photo1);
    $image->water('./Uploads/water.png', $image::IMAGE_WATER_SOUTHEAST)->save('.' . $photo1);
}

/**
 * 判断文件所属目录是否存在,不存在则递归创建
 *
 * @param $file 含文件目录的文件名
 */
function createDir($file)
{
    $dir = dirname($file);

    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
}

/**
 * 根据分页码，返回对应的月份日历数据
 * Enter description here ...
 *
 * @param $page 从1开始
 */
function getmonthtodayst($page)
{
    $month = date("m");  //当前月

    $tempM = intval($month) - $page;
    if ($tempM >= 0) {
        $tt['month'] = $tempM + 1;
        $tt['year'] = date("Y");  //当前年
    } else {
        $tempY = intval(($page - 1 - date("m") * 1) / 12 + 1);
        $tt['year'] = date("Y") - $tempY;  //当前年
        $tt['month'] = $tempM + 12 * $tempY + 1;
    }
    if (intval($tt['month']) < 10) {
        $tt['month'] = '0' . $tt['month'];
    }
    $tt['start'] = $tt['year'] . '-' . $tt['month'] . '-01';
    $maxday = date('t', strtotime($tt['start']));
    $tt['end'] = $tt['year'] . '-' . $tt['month'] . '-' . $maxday;
    if ($tt['year'] . $tt['month'] == date('Ym')) {
        $maxday = date('d') * 1;
    }
    //时间戳
    $tt['starttime'] = strtotime($tt['start']);
    $tt['endtime'] = strtotime($tt['end'] . ' 23:59:59');
    //指定月份日历表
    for ($i = $maxday; $i >= 1; $i--) {
        if ($i < 10) {
            $calendar = $tt['year'] . '-' . $tt['month'] . '-0' . $i;
        } else {
            $calendar = $tt['year'] . '-' . $tt['month'] . '-' . $i;
        }
        $tt['calendar'][] = $calendar;
    }

    return $tt;
}

function pointstobonus($uid)
{

}

/**
 * 获取用户绑定银行卡信息
 *
 * @param int $uid
 */
function getBankCardByUid($uid)
{
    if (!validateExtend($uid, 'NUMBER')) {
        return false;
    }

    $map['user_id'] = array('eq', $uid);
    $info = M('BankBind')->where($map)->find();

    return $info;
}

/**
 * 验证银行卡号是否合法
 *
 * @param string $card
 */
function bankCardValidate($card)
{
    //验证位数
    $len = strlen($card);
    if ($len < 16 || $len > 19) {
        return false;
    }
    /*** 原理:从高位:(奇数位相加总和+偶数为*2[-9]相加总和)/10:余数为0合法  ***/
    $data = strrev($card); //反转
    $data = str_split($data); //打散

    $odd = $even = 0;
    foreach ($data as $k => $v) {
        $index = $k + 1;
        if ($index % 2 == 0) { //偶数位
            $even += $v * 2 > 9 ? $v * 2 - 9 : $v * 2;
        } else { //奇数位
            $odd += $v;
        }
    }

    if (($odd + $even) % 10 == 0) {
        return true;
    } else {
        return false;
    }
}

/**
 * 封装添加推送消息入队列
 *
 * @param string $title 消息标题
 * @param string $target target标识
 * @param array $extra 附加参数字段
 * @param int $uid 用户ID
 */
function pushQueue($title, $target = 'common_alert', $extra = array(), $uid = '0')
{
    $PushQueue = M('PushQueue');

    if (empty($title)) {
        return false;
    }

    //当target=common_alert时,对extra中的msg内容进行处理
    if ($target == 'common_alert') {
        if (!isset($extra['msg'])) {
            return false;
        }
        $extra['msg'] = msubstr(str_replace('&nbsp;', ' ', strip_tags($extra['msg'])), 0, 300);
    }

    //判断同一uid和target是否存在,若存在,则不插入
    $map['push_uid'] = array('eq', $uid);
    $map['push_extra'] = array('like', '%' . $target . '%');
    $info = $PushQueue->where($map)->field('id')->find();
    if ($info) {
        return true;
    }

    $push_extra = array_merge(array('target' => $target), $extra);
    $push_extra = json_encode($push_extra, JSON_UNESCAPED_UNICODE);

    //加入推送队列
    $push_queue_data = array(
        'push_content' => $title,
        'push_extra' => $push_extra,
        'push_uid' => $uid,
        'post_time' => time(),
    );

    if (!$PushQueue->add($push_queue_data)) {
        return false;
    }

    return true;
}

/**
 * 获取上一周的时间范围
 * Enter description here ...
 *
 * @param $ts
 * @param $n
 * @param $format
 *
 * @throws Exception
 */
function lastNWeek($ts, $n, $format = '%Y-%m-%d')
{
    $ts = intval($ts);
    $n = abs(intval($n));

    // 周一到周日分别为1-7
    $dayOfWeek = date('w', $ts);
    if (0 == $dayOfWeek) {
        $dayOfWeek = 7;
    }

    $lastNMonday = 7 * $n + $dayOfWeek - 1;
    $lastNSunday = 7 * ($n - 1) + $dayOfWeek;

    return array(
        strftime($format, strtotime("-{$lastNMonday} day", $ts)),
        strftime($format, strtotime("-{$lastNSunday} day", $ts))
    );
}

/**
 * 3，4是上周， 4，5本周
 * Enter description here ...
 *
 * @param unknown_type $ts
 * @param unknown_type $n
 */
function getlastNWeek($ts, $n)
{
    $ts = intval($ts);
    $n = abs(intval($n));

    // 周一到周日分别为1-7
    $dayOfWeek = date('w', $ts);
    if (0 == $dayOfWeek) {
        $dayOfWeek = 7;
    }

    $lastNMonday = 7 * $n + $dayOfWeek - 1;
    $lastNSunday = 7 * ($n - 1) + $dayOfWeek;
    $r1 = strftime('%Y%m', strtotime("-{$lastNMonday} day", $ts));
    $r2 = strftime('%Y%m', strtotime("-{$lastNSunday} day", $ts));
    $r3 = strftime('%Y%m%d', strtotime("-{$lastNMonday} day", $ts));
    $r4 = strftime('%Y%m%d', strtotime("-{$lastNSunday} day", $ts));
    $r5 = strftime('%Y-%m-%d', strtotime("-{$lastNMonday} day", $ts));
    $r6 = strftime('%Y-%m-%d', strtotime("-{$lastNSunday} day", $ts));

    return array(
        $r1,
        $r2,
        $r3,
        $r4,
        strtotime($r5),
        strtotime($r6),
        mktime(0, 0, 0, date("m"), date("d") - date("w") + 1, date("Y")),
        mktime(23, 59, 59, date("m"), date("d") - date("w") + 7, date("Y")),

    );
}


/**
 * 根据兑换金额，计算总支出的费用（所有奖项）
 *
 * @param $amount 兑换金额
 * @param $params 参数配置数据 (暂不启用)
 * @param $type 兑换类型(1:普通兑换,2:开通创客)
 * @param $order_number 订单号(仅在$type=1时有效)
 *
 * @return int 产生最终毛利润
 */
function rewardtotal($amount, $params = false, $type = 1, $order_number = false)
{
    if ($type == 1) {
        /* (暂停由所有奖项计算毛利润)
        //1、重复消费奖
        $repeat1 = $lirun*$params['repeat_bai']*10/100;
        $repeat2 = $lirun*$params['repeat_bai_1']*11/100;
        //2、商家联盟奖
        $marchant_bai = $lirun*$params['marchant_bai']/100;
        //3、服务中心奖
        $service_bai = $params['service_dai']*$params['service_bai']*$lirun/100;
        //4、运营中心奖
        $company_bai = $params['company_dai']*$params['company_bai']*$lirun/100;

        $total = $repeat1+$repeat2+$marchant_bai+$service_bai+$company_bai;
        */

        if (empty($order_number) || empty($amount)) {
            return 0;
        }

        //由兑换毛利润的75%直接计算最终毛利润
        $map_bonus['type'] = array('in', '6,30,31,32');
        $map_bonus['serial_num'] = array('eq', $order_number);
        $s_money = M('Bonus', 'g_')->where($map_bonus)->getField('money');
        if (!$s_money) {
            throw new Exception('订单对应返现给商家明细不存在:' . $order_number);
        }
        $total = ($amount - $s_money) * 0.75;

        $reward = sprintf('%.2f', $total);

        return $reward;
    } else {
        return $amount - 60; //申请创客见点奖默认60
    }
}


function getIpAddr()
{
    //$ip = $_SERVER['REMOTE_ADDR'];
    $ip = get_client_ip(0, true);
    $url = 'http://ip.taobao.com/service/getIpInfo.php?ip=' . $ip;
    $html = file_get_contents($url);
    $json = json_decode($html, true);

    /*
    $region = empty($json['data']['region']) ? 'NULL' : mb_convert_encoding($json['data']['region'], 'gbk', 'utf-8');
    $json['data']['region'] = $region;
    */

    return $json;
}

/**
 * 注册限制
 * Enter description here ...
 */
function regAddrFilter($ipmsg)
{
    $p = $ipmsg['data']['region'];
    $c = $ipmsg['data']['city'];
    $stop = false;
    foreach (C('FILTER_REGADDR') as $k => $v) {
        if ($k == $p && empty($v)) {
            $stop = true;
            break 1;
        }
        foreach ($v as $city) {
            if ($c == $city) {
                $stop = true;
                break 2;
            }
        }
    }
    if ($stop) {
        file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/app-hunan.txt',
            $_POST['phone'] . ':' . $ipmsg['data']['region'] . '-' . $ipmsg['data']['city'] . '-' . $ipmsg['data']['isp'] . '[IP:' . $ipmsg['data']['ip'] . ']' . PHP_EOL,
            FILE_APPEND);
        ajax_return('该区域暂未开通注册功能', 300);
    }
}

/**
 * 生成用户id
 */
function getUserNameIdstr()
{
    $usernamestr = substr(date('y'), 1, 1) . (date('m') * 1) . (date('d') * 1);
    $todayregcount = M('member')->where("FROM_UNIXTIME(reg_time,'%Y%m%d')=FROM_UNIXTIME(unix_timestamp(),'%Y%m%d')")->count();

    return $usernamestr . ($todayregcount + 1) . rand(5, 9);
}

/**
 * 获取用户对应级别身份中文
 */
function getrole($data)
{
	//特殊用户处理
	$special_users = M('Settings')->where("settings_code='special_income_users'")->getField('settings_value');
	$special_users = preg_match('/,/', $special_users) ? explode(',', $special_users) : [$special_users];
	if (in_array($data['loginname'], $special_users)) {
		return '西南营运中心';
	}
	
    if ($data['role'] == 4) {
        return '省级合伙人';
    }
    if ($data['role'] == 3) {
        return '区域合伙人';
    }
    if ($data['level'] == 2) {
//     	if ($data['role_star'] == 5) {
//     		return '钻石经销商';
//     	}
//         return $data['role_star'] . '个人代理';

    	if ($data['consume_level'] == '0') {
    		return '体验会员';
    	} else {
    		return $data['role_star']. '代理';
    	}
    }
    if ($data['level'] == 1) {
        return '体验会员';
    }

    return '未知';
}

/**
 * 判断指定日期是否为法定节假日或者休息日
 * 
 * @param string $date 指定日期(格式:Ymd)
 * 
 * @return string (0:工作日,1:休息日,2:节假日)
 */
function getDateStatus($date) {
	if (empty($data)) {
		$date = date('Ymd');
	}
	
	$url = "http://tool.bitefu.net/jiari/?d=".$date;
// 	$result = file_get_contents($url);
	$result = get_by_curl($url, 'get');
//	$result = json_decode($result, true);
	
	return $result;
}

/**
 * 获取用户当天第三方支付或充值的总金额
 * 
 * @param int $user_id 用户ID
 * @param int $type 类型(1:支付宝,2:微信)
 */
function getUserThirdAmountByToday($user_id, $type) {
	$map['o.uid'] = ['eq', $user_id];
	$map['o.order_status'] = ['in', '1,3,4'];
	$map['_string'] = " FROM_UNIXTIME(o.pay_time, '%Y%m%d') = FROM_UNIXTIME(UNIX_TIMESTAMP(), '%Y%m%d') ";
	
	switch ($type) {
		case '1':
			$map['o.amount_type'] = ['in', '3,5'];
			break;
		case '2':
			$map['o.amount_type'] = ['in', '2,4'];
			break;
	}

	$amount = M('Orders')
			->alias('o')
			->join('join __ORDER_AFFILIATE__ aff ON aff.order_id=o.id')
			->where($map)
			->sum('aff.affiliate_pay');
	
	return $amount;
}

/**
 * 获取IP所属位置
 * 
 * @param string $ip IP地址(空则默认自动获取当前IP)
 * 
 * @return array [ip,country,area,region,city,county,country_id,isp,isp_id,region_id,city_id,county_id,is_china]
 */
function getIpLocation($ip='') {
	
	if (empty($ip)) {
		$ip = get_client_ip();
	}
// 	$ip = '207.226.141.205';
	
// 	$url = 'http://restapi.amap.com/v3/ip?key=d7410ab7b422bce6206699dcf72976a3&ip='.$ip;
	$url = 'http://ip.taobao.com/service/getIpInfo.php?ip='.$ip;
	$data = get_by_curl($url, 'get');
	
	$data = json_decode($data, true);
	
	$return = $data['data'];
	$return['is_china'] = $return['country_id']!='CN' ? '0' : '1';
	
	if ($return['isp_id'] == 'local' || $ip == '0.0.0.0' || $ip == '127.0.0.1') {
		$return['is_china'] = '1';
	}

	//特殊处理为非大陆
	if(in_array($return['region'], ['台湾','香港','澳门'])){
        $return['is_china'] = '0';
    }
    
    //语言识别非大陆
    $current_lang = getCurrentLang();
    if ($current_lang != 'zh-cn') {
    	$return['is_china'] = '0';
    }
	
	return $return;
}

/**
 * 获取当前调用语言包名称
 * 
 * @param boolean $field_use 是否为字段使用(默认否，当为是:则返回的语言名称前加下划线,并且当语言为中文时返回空)
 * 
 * @return string [zh-cn, en, ko] / ['', '_en', '_ko']
 */
function getCurrentLang($field_use=false) {
	$allow_lang = ['zh-cn', 'en', 'ko'];
	
	$accept_language = getallheaders()['Accept-Language'];
	$accept_language = strtolower($accept_language);
	$accept_language = preg_match('/,/', $accept_language) ? explode(',', $accept_language)[0] : $accept_language;
	
	if ($accept_language=='zh-cn' || $accept_language=='ch' || $accept_language=='zh' || $accept_language=='cn' || empty($accept_language)) {
		$accept_language = 'zh-cn';
	}
	
	//兼容自适应模式下语言标记名
	$accept_language = preg_match('/us/', $accept_language) ? 'en' : $accept_language;
	
	//当非允许的语言时默认为英文
	$accept_language = in_array($accept_language, $allow_lang) ? $accept_language : 'en';
	
	if ($field_use) {
		$accept_language = ($accept_language=='zh-cn') ? '' : '_'.$accept_language;
	}
	
	return $accept_language;
} 

/**
 * Unicode转中文
 */
function decodeUnicode($str) {
	return preg_replace_callback('/\\\\u([0-9a-f]{4})/i',
			create_function(
					'$matches',
					'return mb_convert_encoding(pack("H*", $matches[1]), "UTF-8", "UCS-2BE");'
			),
			$str);
}


/**
 * 手机号判断
 * @param $phone
 */
function getPhonePrefix($phone) {
    if ((substr($phone, 0, 1) == 0 && strlen($phone) == 10) || (substr($phone, 0, 1) == 0 && strlen($phone) == 11)) {
        // 非大陆手机号
        $return['is_china'] = '0';
    } else {
        $return['is_china'] = '1';
    }

    return $return;
}
