<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | H5注册的
// +----------------------------------------------------------------------
namespace H5\Controller;

use Common\Controller\PushController;
use V4\Model\Currency;
use V4\Model\CurrencyAction;
use V4\Model\AccountRecordModel;
use V4\Model\ProcedureModel;

class IndexController extends PushController
{

    protected $sms_time_out = 120; //短信验证码超时时间(s)
    protected $current_lang; //当前语言

    public function __construct()
    {
        parent::__construct();

        $this->current_lang = getCurrentLang();
        
        $this->assign('sms_time_out', $this->sms_time_out);
    }

    public function myApiPrint($msg, $code = 300)
    {
        $data = array(
            'code' => $code,
            'msg' => $msg
        );
//        echo '<h2>' . $data['msg'] . '</h2>';
//        exit;
        $this->ajaxReturn($data);
    }

    /**
     * 注册首页
     * 第一步
     */
    public function index()
    {
        //公共头参数
        $time = time();
        $data = array(
            'platform' => 'wap',
            'time' => $time,
            'api_token' => md5($time . C('APP_CONFIG')['SECRET_KEY']),
            'uid' => '',
        );

        if (empty($data['version'])) {
            $data['version'] = '';
        }
        if (empty($data['sessionid'])) {
            $data['sessionid'] = '';
        }

        $data_string = json_encode($data);

        //分享
        $recommer = I('get.recommer');
        $recommer = base64_decode($recommer);
        if (!empty($recommer)) {
//			$params = M('g_parameter', null)->find();
//

            $settings = M('settings')->where(['settings_code' => 'register_experience_give_goldcoin_amount'])->find();
            $this->assign('give_goldcoin', $settings['settings_value'] ?: 0);

            $where['loginname'] = $recommer;
            $member_info = M('member')->where($where)->find();
            if (!$member_info) {
                die('<h2>推荐人信息有误</h2>');
            }
//             if ($member_info['role'] == '3' || $member_info['role'] == '4') {
//                 die('<h2>推荐人不能是合伙人</h2>');
//             }
            $member_info['nickname'] = mb_substr($member_info['truename'], 0, 1, 'utf-8') . '**';
//		  	if($member_info['level'] == 1){
//		  		$member_info['str'] = '体验用户';
//		  	}elseif($member_info['level'] == 5){
//		  		$member_info['str'] = '';
//		  	}elseif($member_info['level'] == 6){
//		  		$member_info['str'] = '';
//		  	}elseif($member_info['level'] == 7){
//		  		$member_info['str'] = '';
//		  	}else{
//		  		$member_info['str'] = '正式会员';
//		  	}

            //recommer入session
            session('h5_register_recommer', $recommer);

            $this->assign('data_string', $data_string);
            $this->assign('recommer', $recommer);
            $this->assign('recommer_short', substr($recommer, 0, 3) . '****' . substr($recommer, -4, 4));
            $this->assign('member', $member_info);
            //$this->assign('newid', getUserNameIdstr());

            $this->display();
        } elseif (empty($_GET)) {
            //$this->assign('data_string', $data_string);
            //$this->display("Index/linkindex");
        }

    }

    /**
     * 注册页
     * 第二步
     */
    public function next()
    {
        $Member = M('Member');
        $Phonecode = M('Phonecode');

        $input_recommer = I('get.input_recommer');
        if ($input_recommer == '1') {
            $recommer = I('post.recommer');
            session('h5_register_recommer', $recommer);
        } else {
            $recommer = session('h5_register_recommer');
        }
        $telphone = I('post.telphone');
        $auth = I('post.auth');

        if (empty($recommer)) {
            $this->myApiPrint('推荐人信息有误');
        }
        if (!validateExtend($telphone, 'MOBILE')) {
            $this->myApiPrint('手机号格式有误');
        }
        if (!validateExtend($auth, 'NUMBER')) {
            $this->myApiPrint('验证码格式有误');
        }

        //$map_member['loginname'] = array('eq', $telphone);
        //$member_info = $Member->where($map_member)->field('id')->find();
        //if ($member_info) {
        //	$this->myApiPrint('此手机号已经注册');
        //}

        $map_phonecode['phone'] = array('eq', $telphone);
        $map_phonecode['status'] = array('eq', 0);
        $map_phonecode['post_time'] = array('egt', time() - $this->sms_time_out);
        $code = $Phonecode->where($map_phonecode)->order('id desc')->getField('code');
        if (empty($code)) {
            $this->myApiPrint('验证码不存在');
        }
        if ($code != $auth) {
            $this->myApiPrint('验证码错误');
        }

        $data_phonecode = array(
            'status' => 1
        );
        $map_phonecode1['phone'] = array('eq', $telphone);
        $map_phonecode1['code'] = array('eq', $code);
        $Phonecode->where($map_phonecode1)->save($data_phonecode);

        $result = array(
            'code' => 400
        );

        //telphone和code入session
        session('h5_register_telphone', $telphone);
        session('h5_register_code', $code);

        $this->ajaxReturn($result);
    }

    /**
     * 注册页
     * 第三步
     */
    public function datum()
    {
        $this->display();
    }

    /**
     * 注册页
     * 第四步
     */
    public function download()
    {
        $this->display();
    }

    /**
     * 注册页
     * 提交数据库
     */
    public function recommer()
    {
        //注册地区限制
        $ipmsg = getIpAddr();
        regAddrFilter($ipmsg);
        
        //判断IP是否属于大陆
        $is_china = getIpLocation()['is_china'];

        $Member = M('Member');
        $Phonecode = M('Phonecode');

        $recommer = session('h5_register_recommer');
        $telphone = I('post.telphone');
        $code = I('post.auth');
        $nickname = I('post.nickname');
        $truename = I('post.truename');
        $password = I('post.password');

        if ($truename == "" || $telphone == "") {
            $this->myApiPrint('请填写完整信息');
        }
        if ($code == "" && $is_china == '1') {
        	$this->myApiPrint('请填写完整信息');
        }
        $uw1['loginname'] = $telphone;
        $usernameisexists = M('member')->where($uw1)->find();
        if ($usernameisexists) {
            $this->myApiPrint('手机号：' . $telphone . '已经被注册了！');
        }
        if (!validateExtend($truename, 'CHS') && $is_china == '1') {
            $this->myApiPrint('姓名只能输入中文');
        }
        if (!validateExtend($telphone, 'MOBILE') && $is_china == '1') {
            $this->myApiPrint('手机号格式不正确');
        }
        if (!validateExtend($code, 'NUMBER') && $is_china == '1') {
            $this->myApiPrint('验证码错误');
        }

        $map_phonecode['telphone'] = $telphone;
        $map_phonecode['code'] = $code;
        $map_phonecode['status'] = 0;
        $phonecode_info = $Phonecode->where($map_phonecode)->order('id desc')->find();
        if (!$phonecode_info && $is_china == '1') {
            $this->myApiPrint('验证码不正确');
        }

        $params = M('g_parameter', null)->find();
        M()->startTrans();

        //获取推荐人信息
        //$map_recommer['id'] = array('neq', 1);
        $map_recommer['loginname'] = array('eq', $recommer);
        $recommer_info = $Member->lock(true)->where($map_recommer)->field('id,repath')->find();
        if (!$recommer_info) {
            $this->myApiPrint('推荐人不存在');
        }
        $data = array(
            'loginname' => $telphone,
            'username' => $truename,
            'truename' => $truename,
            'nickname' => $truename,
            'password' => md5($password),
            'reid' => $recommer_info['id'],
            'recount' => 0,
            'reg_time' => time()
        );
        $ac1 = $Member->add($data);
        //吊起存储过程
        $pm = new ProcedureModel();
        $res5 = $pm->execute('Event_register', $ac1, '@error');
        if (!$res5) {
            M()->rollback();
            $this->myApiPrint('注册失败');
        }
        if ($ac1 === false || !validateExtend($ac1, 'NUMBER') || $res5 === false) {
            M()->rollback();
            $this->myApiPrint('注册失败');
        } else {
            M()->commit();
            //清空session
            $Phonecode->where($map_phonecode)->delete();
            session('h5_register_code', null);
            session('h5_register_telphone', null);
            session('h5_register_recommer', null);
            $this->myApiPrint('注册成功', 400);
        }
    }
    
    //@override display
    public function display($tpl='') {
    	$data = $this->fetch($tpl);
    	
    	$language = ['zh-cn', 'en', 'ko'];
    	$accept_language = I('get.lang');
    	$accept_language = in_array($accept_language, $language) ? $accept_language : $language[0];
    	
    	//转译
    	if ($accept_language != 'zh-cn') {
	    	$lang_package = include ($_SERVER['DOCUMENT_ROOT'].'/Appcenter/APP/Lang/'.$accept_language.'.php');
	    	$data = decodeUnicode($data);
	    	foreach ($lang_package as $k=>$v) {
	    		$data = preg_replace("/{$k}/", $v, $data);
	    	}
    	}
    	
    	echo $data;
    }

}