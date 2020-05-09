<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]   **把之前的区域合伙人改为：省级合伙人**
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 省级合伙人相关接口
// +----------------------------------------------------------------------
namespace APP\Controller;

use Common\Controller\ApiController;
use V4\Model\AccountModel;

class CompanyController extends ApiController
{


    public function companyBefore()
    {
    	$current_lang = getCurrentLang(true);
    	
        if ($this->member['role'] == '4') {
            $this->myApiPrint('你已经是省级合伙人,不需要重复申请！');
        }

        if ($this->member['role'] == '3') {
            $this->myApiPrint('你已经是区域合伙人,不能再申请省级合伙人！');
        }

        //如果已提交了区代或省级申请，并且未审核，则依然不能申请同一个或另外的身份


        $data = [];
        $data['title'] = '申请省级合伙人';
//		$data['balance_cash'] = sprintf( '%.2f', AccountModel::getInstance()->getBalance( $this->member['id'], 'cash' ) );
        $data['balance_cash'] = 0;

        $data['agreement'] = htmlspecialchars_decode(html_entity_decode(M('agreement')->where('id=1')->getField('benefittxt'.$current_lang) ?: ''));

        //		prize_service_consume_bai_2

//		$data['amount'] = sprintf( '%.2f', $this->CFG['apply_service_amount'] ?: 0 );
        $data['amount'] = 0;


        $this->myApiPrint('OK', 400, $data);

    }

    /**
     * 省级合伙人申请
     *
     * @param uid 会员ID
     * @param photo1 身份证正面
     * @param photo2 身份证反面
     * @param photo3 营业执照
     * @param photo4 公司形象照
     *
     * @param string $province 省
     * @param string $city 市
     * @param string $country 区
     */
    public function company()
    {
    	$current_lang = getCurrentLang();
    	
        $uid = intval(I('post.uid'));

        $province = $this->post['province'];
        $city = $this->post['city'];
        $country = $this->post['country'];

        $where['id'] = $uid;
        $m = M('member')->where($where)->count();
        if ($m == 0) {
            $this->myApiPrint('对不起，用户信息不存在！');
        }
        
        //核验实名认证
        $map_certification = [
	        'user_id' => $uid,
	        'certification_status' => 2
        ];
        $certification = M('certification')->where($map_certification)->find();
        if (!$certification) {
        	$this->myApiPrint('请先实名认证', 300);
        }

        if ($current_lang == 'zh-cn') {
        	if (!validateExtend($province, 'CHS')) {
        		$this->myApiPrint('请选择申请省份');
        	}
        }

        $uu = M('member')->where($where)->find();
        if ($uu['is_lock'] == 1) {
            $this->myApiPrint('账号已锁定，请联系管理员解锁!');
        }
        $level = $uu['level'];
        if ($level == 1) {
            //$this->myApiPrint('对不起，你是体验会员，不能申请省级合伙人！');
        } elseif ($level == 4) {
            $this->myApiPrint('对不起，你已经是省级合伙人，无须再申请省级合伙人！');
        }

        //验证是不是商家
        /*
        $store = M('store')->where('uid = ' . $uid)->find();
        if ($store) {
            if ($store['manage_status'] == 0) {
                $this->myApiPrint('对不起，你正在申请商家，不能申请区域合伙人！');
            } elseif ($store['manage_status'] == 1) {
                $this->myApiPrint('对不起，你是商家，不能申请区域合伙人！');
            } elseif ($store['manage_status'] == 10) {
                $this->myApiPrint('对不起，你的店铺尚未注销，不能申请区域合伙人！');
            } elseif ($store['status'] == 1) {
                $this->myApiPrint('对不起，你的店铺被冻结，不能申请区域合伙人！');
            }
        }
        */

        M()->startTrans();
        $result1 = true;
        $result2 = true;
        $result3 = true;

        //判断是否已提交了区代申请
        $wherekey['uid'] = array('eq', $uid);
        $wherekey['apply_level'] = array('eq', 3);
        $wherekey['status'] = array('neq', 2);
        $apply_info = M('apply_service_center')->where($wherekey)->field('id')->order('id desc')->find();
        if ($apply_info) {
            $this->myApiPrint('对不起，你已经申请了区代合伙人，不能再申请省级合伙人！', 300);
        }

        //判断对应地区是否还能申请
        $map_apply_exists['mem.province'] = array('eq', $province);
        $map_apply_exists['mem.city'] = array('eq', $city);
        $map_apply_exists['mem.country'] = array('eq', $country);
        $map_apply_exists['ase.apply_level'] = array('eq', 4);
        $map_apply_exists['ase.status'] = array('neq', 2);
        $apply_exists = M('Member')->alias('mem')
            ->join('join __APPLY_SERVICE_CENTER__ ase ON ase.uid=mem.id')
            ->where($map_apply_exists)
            ->field('mem.id')
            ->find();
        if ($apply_exists) {
            $this->myApiPrint('对不起，该地区已不能再申请');
        }

        $wherekey['uid'] = array('eq', $uid);
        $wherekey['apply_level'] = array('eq', 4);
        $apply_info = M('apply_service_center')->where($wherekey)->field('id,status')->order('id desc')->find();
        if ($apply_info) {
            if ($apply_info['status'] == 0) {
                $this->myApiPrint('对不起，你已经申请了省级合伙人，正在审核中。。。，不能再申请省级合伙人！', 300);
            }
            //如果之前申请的已驳回,则自动清除之前的申请
            if ($apply_info['status'] == 2) {
                $map_apply['id'] = array('eq', $apply_info['id']);
                $result1 = M('ApplyServiceCenter')->where($map_apply)->delete();
            }
        }

        //保存省市区
        $map_member['id'] = array('eq', $uid);
        $data_member = [
            'province' => $province,
            'city' => $city,
            'country' => $country
        ];
        $result2 = M('Member')->where($map_member)->save($data_member);
        $result2 = $current_lang=='zh-cn' ? $result2 : true;

        //处理图片
        $upload_config = array(
            'file' => 'multi',
            'exts' => array('jpg', 'png', 'gif', 'jpeg'),
            'path' => 'service/' . date('Ymd')
        );
//		$Upload = new \Common\Controller\UploadController($upload_config);
//		$info = $Upload->upload();
//		if (!empty($info['error'])) {
//			$this->myApiPrint('申请失败，请重新上传图片！',300,(object)$result);
//		} else {
//			for ($i=1; $i<=3; $i++) {
//				if (empty($info['data']['photo'.$i]['url'])) {
//					$this->myApiPrint('图片上传失败，请重新上传！');
//				}
//			}
        $data['uid'] = $uid;
//			$data['img1'] = $info['data']['photo1']['url'];
//			$data['img2'] = $info['data']['photo2']['url'];
//			$data['img3'] = $info['data']['photo3']['url'];
//			$img4 = '';
//			if ($info['data']['photo4_1']) {
//				$img4 .= $info['data']['photo4_1']['url'].',';
//			}
//			if ($info['data']['photo4_2']) {
//				$img4 .= $info['data']['photo4_2']['url'].',';
//			}
//			if ($info['data']['photo4_3']) {
//				$img4 .= $info['data']['photo4_3']['url'].',';
//			}
//			if ($info['data']['photo4_4']) {
//				$img4 .= $info['data']['photo4_4']['url'].',';
//			}
//			if($img4 != ''){
//				$img4 = substr($img4, 0, strlen($img4)-1);
//			}
//			$data['img4']= $img4;

        $data['get_time'] = time();
        $data['apply_level'] = 4;
        $result3 = M('apply_service_center')->add($data);

        if ($result1 === false || $result2 === false || !$result3) {
            M()->rollback();
            $this->myApiPrint('处理失败', 300);
        } else {
            M()->commit();
        }

//			$result['img1'] = $data['img1'];
//			$result['img2'] = $data['img2'];
//			$result['img3'] = $data['img3'];
//			$result['img4'] = $data['img4'];

        $this->myApiPrint('申请省级合伙人成功，正在审核中。。。！', 400);
//		}
    }

    /**
     * 区域合伙人和服务中心申请条件
     */
    public function parameter()
    {
        $data = M('parameter', 'g_')->field('service_condition,company_condition,loan_condition')->select();
        $this->myApiPrint('查询成功！', 400, $data);
    }

}

?>