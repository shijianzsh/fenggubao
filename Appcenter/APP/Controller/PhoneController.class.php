<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 短信相关接口
// +----------------------------------------------------------------------
namespace APP\Controller;

use Common\Controller\ApiController;

class PhoneController extends ApiController
{

    private $super_manager_sms_telphone = '13547674999'; //超级管理员接收短信的电话号码

    /**
     * 发送短信验证码
     *
     * @param telphone 手机号码
     * @param send_type 消息内容类型(R:注册验证码,P:重置验证码)
     */
    public function get_phone_code()
    {
        $post_phone = $this->post['telphone'];
        $post_type = $this->post['send_type'];

        !isset($post_phone) && $this->myApiPrint('无手机号', 300);
        $smsTemplateKey = '';
        switch ($post_type) {
            case 'R':
                $where['loginname'] = $post_phone;
                $count = M('member')->where($where)->count();
                if ($count > 0) {
//                    $this->myApiPrint('此手机号码已经注册', 300);
                }
                $content_type = '注册验证码';
                $smsTemplateKey = '1003';
                break;
            case 'P':
//                $content_type = '重设密码验证码';
                $smsTemplateKey = '1004';
                break;
            default:
                $this->myApiPrint('无此类型验证码', 300);
        }

        if ($post_type == 'R') {
            //判断在此人之前已记录的用户(60s内）是否有已完成注册的用户,若有已完成的,则视为非刷用户行为
            $w1['ipaddr'] = array('eq', $_SERVER['REMOTE_ADDR']);
            $w1['post_time'] = array('egt', time() - 60);
            $reg_exist = M('phonecode')->where($w1)->find();
            if ($reg_exist) {
                $w1['status'] = array('eq', 1);
                $reg_success = M('phonecode')->where($w1)->count();
                if ($reg_success == 0) {
                    $this->myApiPrint('请勿频繁操作！');
                }
            }
        }

        $code = sprintf('%06d', mt_rand(100000, 999999));
        Vendor("Luosimao.Luosimao#Api");
        $return = \LuosimaoApi::sendByTemplateKey($post_phone, $smsTemplateKey, $code);
        $data['m_phone_code'] = '1';

        //记录到表中
        $pc['phone'] = $post_phone;
        $pc['code'] = $code;
        $pc['post_time'] = time();
        $pc['ipaddr'] = $_SERVER['REMOTE_ADDR'];
        M('phonecode')->add($pc);

        if ($return['error'] !== 0) {
            $this->myApiPrint('短信发送失败，请稍后再试');
        }

        $message = '验证码已发送至' . $post_phone;
        $this->myApiPrint($message, 400, $data);
    }

    /**
     * 给指定管理员用户发送短信验证码(仅用于后台管理员登录时使用)
     *
     * @param $id 用户ID
     *
     * @description 仅当session('admin_mid_'.$id.'_login_fail_count')不为null时有效
     */
    public function sendSmsToManager()
    {
        //测试系统不使用该功能,故统一返回1
        exit('1');

        $id = $this->post['id'];
        $template = $this->post['template'];
        $msg = $this->post['msg'];

        $map_member['id'] = array('eq', $id);
        $member_info = M('Member')->where($map_member)->field('loginname')->find();
        if ($member_info) {
            //对超级管理员帐号进行特殊处理
            $loginname = $id == '1' ? $this->super_manager_sms_telphone : $member_info['loginname'];

            $return = $this->sms($loginname, '', $msg, $template);
            if (!empty($return['error'])) {
                exit($return['error']);
            }

            //记录到表中(仅当msg为空时视为发送的是验证码,非空则视为发送的通知)
            if (empty($msg)) {
                $pc['phone'] = $member_info['loginname'];
                $pc['code'] = $return['data'];
                $pc['post_time'] = time();
                $pc['ipaddr'] = $_SERVER['REMOTE_ADDR'];
                M('phonecode')->add($pc);
            }

            exit('1');
        } else {
            exit('帐号不存在:' . $id);
        }
    }

}

?>