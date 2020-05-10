<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 系统相关接口 
// +----------------------------------------------------------------------
namespace APP\Controller;

use Common\Controller\ApiController;
use V4\Model\Image;
use V4\Model\ProcedureModel;

class SysController extends ApiController
{

    /**
     * 返回安卓版本信息
     */
    public function get_android_version()
    {
    	$current_lang = getCurrentLang(true);
    	//\Think\Log::write('==================================');
        //\Think\Log::write(json_encode($this->app_common_data));
        $manage = M('ApkManage');
        //判断设备对应的版本
        if ($this->app_common_data['platform'] == 'android') {
            $where = 'platform=1';
        } else {
            $where = 'platform=2';
        }
        
        $apk_item = $manage->where($where)->order('id desc')->limit(1)->find();
        if (strpos($apk_item['src'], 'http') !== false) {
            $apkurl = $apk_item['src'];
        } else {
            $apkurl = C('LOCAL_HOST') . $apk_item['src'];
        }
        // if ($apk_item) {
            $result = array(
                'code' => 0,
                'data' => array(
                    'apk_url' => $apkurl,
                    'isneed' => intval($apk_item['is_need']),  // 是否强制更新
                    'create_time' => date('Y-m-d H:i:s', $apk_item['add_time']),
                    'message' => $apk_item['content'.$current_lang],
                    'version' => $apk_item['version_num'],
                    'number' => $apk_item['number'],
                ),
                'message' => 'ok',
                'time' => date('Y-m-d H:i:s', time()),
            );

            $this->ajaxReturn($result);

        // } else {
        //     $this->myApiPrint('无最新版', 300);
        // }
    }

    /**
     * 获取apk
     *
     * 若数据库中存在指定文件，则获取，否则获取最新版本
     *
     * 返回：zcsh_[版本号].apk
     */
    public function get_latest_apk()
    {
        $manage = M('apk_manage');
        //判断设备对应的版本
        if ($this->app_common_data['platform'] == 'android') {
            $where = 'platform=1 and point=1';
            $apk_item = $manage->where($where)->find();
            if (!$apk_item) {
                $apk_item = $manage->order('id desc')->limit(1)->find();
            }

            $apk_name = 'zcsh_' . $apk_item['version_num'] . '.apk';
            $filename = C('LOCAL_HOST') . $apk_item['src'];

            header('Content-Type: application/vnd.android.package-archive');
            header('Content-Disposition: attachment; filename="' . $apk_name . '"');
            readfile("$filename");
        } else {
            $where = 'platform=2 and point=1';
            $apk_item = $manage->where($where)->find();
            if (!$apk_item) {
                $this->myApiPrint('无最新版', 300);
                exit;
            }
            $this->myApiPrint('ios链接', 300, $apk_item['src']);
            exit;
        }

    }

    /**
     * 上传apk
     *
     * 上传文件名不能带中划线
     *
     * @param version_num 版本号
     * @param content 描述，可选
     * @param is_need 是否强制更新(0否,1是)
     *
     */
    public function updateapk_version()
    {
        $this->myApiPrint('暂时禁用apk', 300);
        exit;
        $version_num = I('post.version_num');
        $content = I('post.content');
        $is_need = I('post.is_need');

        $config = array(                                // 上传类配置
            'rootPath' => './Uploads/',
            'savePath' => 'apk/',
            'autoSub' => false,
            'exts' => array('apk'),
            'maxSize' => 0
        );
        $upload = new \Think\Upload($config);           // 实例化
        $info = $upload->upload();
        if (!$info) {
            $this->myApiPrint('上传失败，未接收到apk', 300);
        } else {
            $apk_save_path = $upload->rootPath . $info['apk']['savepath'] . $info['apk']['savename'];   // 图片保存的相对路径
            $manage = M('apk_manage');

            $result['src'] = $result['apk'] = substr($apk_save_path, 1);   // 去掉相对路径前的 . 号
            $result['add_time'] = time();
            $result['content'] = $content;
            $result['is_need'] = empty($is_need) ? 0 : $is_need;
            $result['version_num'] = $version_num;

            $flag = $manage->add($result);
            if ($flag) {
                $this->myApiPrint('上传成功', 400, $result);
            } else {
                $this->myApiPrint('上传失败', 300);
            }
        }
    }

    /**
     * 指定下载版本
     *
     * 指定某个版本，原指定的版本（如果存在）会被取消指定
     *
     * id 主键，若为0，则取消指定，否则指定某个存在的版本。
     */
    public function point()
    {
        $id = I('post.id');
        $manage = M('apk_manage');
        $affect1 = $manage->where(array('point' => 1))->save(array('point' => 0));
        if ($id) {
            $affect2 = $manage->where(array('id' => $id))->save(array('point' => 1));
            if ($affect2) {
                $this->myApiPrint('指定成功', 400);
            }
        } else {
            if ($affect1) {
                $this->myApiPrint('取消已指定版本', 400);
            }
        }

        $this->myApiPrint('未指定任何记录', 300);
    }

    /**
     * 二维码分享注册提示文字
     */
    public function sharetxt()
    {
    	$current_lang = getCurrentLang();
    	
        $params = M('g_parameter', null)->find();
        $data['title'] = C('APP_TITLE');
        $data['content'] = '免费注册' . C('APP_TITLE') . '商城';
        $data['logo'] = Image::url('Public/images/logo.png');

        //$user = M('member')->where(['id'=>$this->post['user_id']])->find();
        $user = M('member')->find(intval($this->app_common_data['uid']));
        $url = C('LOCAL_HOST') . 'H5/Index/index/lang/'.$current_lang.'/recommer/' . base64_encode($user['loginname']);
        $data['url'] = $url;
        $this->myApiPrint('获取成功', 400, $data);
    }

    /**
     * 二维码分享下载提示文字
     */
    public function sharetxt2()
    {
        $params = M('g_parameter', null)->find();
        $data['title'] = C('APP_TITLE');
        $data['content'] = '免费下载' . C('APP_TITLE') . 'APP';
        $data['logo'] = Image::url('Public/images/logo.png');
        $data['url'] = C('LOCAL_HOST') . 'android/share_app.html';
        $this->myApiPrint('获取成功', 400, $data);
    }

    /**
     * 付款后的推送
     * Enter description here ...
     */
    public function pushafterpay($touid, $fromuid, $paytime, $money)
    {
        $platform = 'all';     //I('post.platform');
        $towhere['uid'] = $touid;//I('post.touid');
        $towhere['registration_id'] = array('neq', '');
        $fromwhere['id'] = $fromuid;//I('post.fromuid');

        //获取用户推送id
        $mlogin = M('login')->where($towhere)->order('id desc')->getField('registration_id', true);
        $fromuser = M('member')->where($fromwhere)->find();
        //设置参数
        $ids['all'] = $mlogin;
        $content = '您的账号在' . $paytime . '收入' . $money . '元. 付款人' . $fromuser['nickname'];
        //附加参数
        $extraparams['target'] = 'cash_details';
        $extraparams['ios_p1'] = 'ios参数设置';
        $extraparams['android_p1'] = 'android参数设置';
        //掉推送接口
        $jpush = new \Common\Controller\PushController();
        $res = $jpush->push($ids, $content, $extraparams);
        //$this->myApiPrint('推送成功', 400, $res);
    }
    
    /**
     * 统一更新矿机统计数据
     */
    public function calculationMachine() {
    	ignore_user_abort(true);
    	set_time_limit(0);
    	
    	$ProcedureModel = new ProcedureModel();
    	 
    	$result = $ProcedureModel->execute('CalculationMachine_batch', '', '@error');
    	if (!$result) {
    		$this->logWrite('执行统一更新矿机统计数据接口成功', 1);
    
    		$this->myApiPrint('统一更新矿机统计数据失败');
    	}
    	
    	$this->logWrite('执行统一更新矿机统计数据接口成功', 1);
    	
    	$this->myApiPrint('统一更新矿机统计数据成功', 400);
    }

}

?>