<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 丰收接口
// +----------------------------------------------------------------------
namespace APP\Controller;

use Common\Controller\ApiController;
use V4\Model\MiningModel;
use V4\Model\Tag;
use V4\Model\AccountRecordModel;
use V4\Model\EnjoyModel;
use V4\Model\Image;
use Think\Log;

class MiningController extends ApiController
{

    /**
     * 丰收界面
     * 
     * @method POST
     * 
     * @param int $uid 用户ID
     * @param int $page 当前页面
     * 
     */
	public function index() {
		$MiningModel = new MiningModel();
		$AccountRecordModel = new AccountRecordModel();
		
		$uid = empty($this->post['uid']) ? $this->app_common_data['uid'] : $this->post['uid'];
		$page = empty($this->post['page']) ? 1 : $this->post['page'];
		
		if (empty($uid)) {
			$this->myApiPrint('当前用户登录状态异常');
		}
		
		//丰收排行榜
		/*
		if ($page > 10) { //只显示前100条数据
			$return['list'] = [];
		} else {
			//加缓存
			S(array('type' => 'file', 'expire' => 3600));
			$cache_key = md5(CONTROLLER_NAME.'-'.ACTION_NAME);
			$list = S($cache_key);
			if ($list === false) {
				$where['tag'] = array('eq', Tag::getDay());
				$where['user_id'] = array('gt', 0);
				$data = $MiningModel->getList('user_id,amount', $page, 10, $where);
				$list = $data['list'];
				foreach ($list as $k=>$v) {
					$member_info = M('Member')->where('id='.$v['user_id'])->field('loginname,username')->find();
					$member_info['username'] = '*' . mb_substr( $member_info['username'], -1, mb_strlen( $member_info['username'], 'utf-8' ), 'utf-8' );
					$member_info['loginname'] = substr( $member_info['loginname'], 0, 3 ) . '********';
					$list[$k]['user'] = $member_info;
				}
				S($cache_key, $list, 3600);
			}
			
			$list = empty($list) ? [] : $list;
			
			$return['list'] = $list;
		}
		*/
		$return['list'] = [];
		
		//当前用户已挖数据
		$tag = Tag::getDay();
		$mining_info = M('Mining')->where("user_id={$uid} and tag={$tag}")->field('amount')->find();
		$mining_score = !$mining_info ? 0 : $mining_info['amount'];
		$return['mining_score'] = sprintf('%.4f', $mining_score);
		
		//当前用户农场个数
		$portion_info = $MiningModel->getPortionNumber($uid, true);
		$return['portion'] = $portion_info['enabled'].'个';
		
		//是否正在丰收(0:否,1:是)
		$mine_info = M('MiningQueue')->where("user_id={$uid} and is_expired=0 and FROM_UNIXTIME(updated_time, '%Y%m%d')=FROM_UNIXTIME(UNIX_TIMESTAMP(), '%Y%m%d')")->find();
		$return['mining_status'] = $mine_info ? 1 : 0;
		
		//是否已下载过TT语音
		$is_tt = M('Member')->where('id='.$uid)->getField('is_tt');
		$is_tt = 1; //取消下载限制
		$return['is_tt'] = $is_tt;
		$return['tt_msg'] = '请先下载YY语音，才能丰收';
		
		//动态图片地址
		$return['dynamic_img'] = C('MINING_DYNAMIC_IMG');
		
		$this->myApiPrint('查询成功', 400, $return);
	}

	/**
     * 自动打开农场收益
     */
    public function autoMine()
    {
    	//判断节假日条件
        $date_validate = getDateInfo(); //0：工作日，1：假日，2：节日
        if ($date_validate == 0) {
        	// 查询出消费记录大于0的
	        $where_consume['amount'] = ['gt',0];
	        $where_consume['is_out'] = 0;
	        $where_consume['dynamic_out'] = 0;
	        $consume_arr = M('Consume')->where($where_consume)->field('user_id,level,amount,dynamic_worth,static_worth')->select();
	        foreach ($consume_arr as $value) {
	            // 出局倍数
	            $out_bei = M('ConsumeRule')->where(['level'=>$value['level']])->getField('out_bei');
	            $out_bei = $out_bei ? $out_bei : 2;
	            // 出局剩余价值
	            $dynamic_out = $value['amount'] * $out_bei - $value['static_worth'] - $value['dynamic_worth'];
	            if ($dynamic_out > 0) {
	                $MiningModel = new MiningModel();
	                $EnjoyModel = new EnjoyModel();
	                
	                //早x点至晚y点可丰收
	                $hour = date('H');
	                $hour_start = $this->CFG['mine_start_hour'];
	                if ($hour > $hour_start) {
	                    //判断系统条件
	                    $system_validate = $MiningModel->mineValidateBySystem($this->CFG);
	                    if (!$system_validate['error']) {
	                        // 执行丰收 查询是否存在丰收队列数据：无则新增,有则更新日期
	                        $info = M('MiningQueue')->where("user_id={$value['user_id']} and is_expired=0 and FROM_UNIXTIME(updated_time, '%Y%m%d')=FROM_UNIXTIME(UNIX_TIMESTAMP(), '%Y%m%d')")->find();
	                        if (!$info) {
	                            $data_info = [
	                                'user_id' => $value['user_id'],
	                                'created_time' => time(),
	                                'updated_time' => time()
	                            ];
	                            $result = M('MiningQueue')->add($data_info);
	                            //当前用户农场个数
	                            $portion_info = $MiningModel->getPortionNumber($value['user_id'], true);
	                            $portion = $portion_info['enabled'];
	                            //扣除澳洲SKN股数
	                            $mining_use_amount = floor ( $portion / 0.5 ) * $this->CFG['enjoy_mining'];
	                            $result2 = $EnjoyModel->miningUse($value['user_id'], $mining_use_amount);
	                            if (!$result || !$result2) {
	                                $this->logWrite('定时任务Error:添加当日丰收队列失败', 1, false, $value['user_id']);
	                            }
	                        }
	                    } else {
	                        if ($system_validate['is_print']) {
	                            $this->logWrite('定时任务Error' . $system_validate['msg'], 1, false, $value['user_id']);
	                        }
	                        $this->logWrite('定时任务Error' . $system_validate['msg'], 1, false, $value['user_id']);
	                    }
	                }   
	            }
	        }
        }
	}
	
	/**
	 * 丰收
	 * 
	 * @param int $uid 用户ID
	 */
	public function mineAction() {
		$uid = empty($this->post['uid']) ? $this->app_common_data['uid'] : $this->post['uid'];
		
		if (empty($uid)) {
			$this->myApiPrint('当前用户登录状态异常');
		}
		
		$MiningModel = new MiningModel();
		$EnjoyModel = new EnjoyModel();
		
		
		
		
		//判断节假日条件
		$date_validate = getDateInfo(); //0：工作日，1：节假日，2：节假日调整为工作日的休息日，3：休息日
		$date_special_array = ['20190505']; //视为工作日的特殊日期
		$date_today = date('Ymd');
		if ($date_validate != '0' && $date_validate != '2' && !in_array($date_today, $date_special_array)) {
			if ($date_validate == '1' || $date_validate == '3') {
				$this->ajaxReturn('节假日和休息日不能丰收');
			} else {
				$this->ajaxReturn('2');
			}
		}
		
		//早x点至晚y点可丰收
		$hour = date('H');
		$hour_start = $this->CFG['mine_start_hour'];
		$hour_end   = $this->CFG['mine_end_hour'];
		if ($hour < $hour_start || $hour >= $hour_end) {
			$this->myApiPrint("可丰收时间为{$hour_start}点至{$hour_end}点");
		}
		
		M()->startTrans();
		
		//判断系统条件
		$system_validate = $MiningModel->mineValidateBySystem($this->CFG);
		if ($system_validate['error']) {
			$this->logWrite('丰收首页Error:'.$system_validate['msg'], 1, false, $uid);
			M()->commit();
			
			if ($system_validate['is_print']) {
				$this->myApiPrint($system_validate['msg']);
			}
			
			$this->myApiPrint('添加当日丰收队列成功:SYS', 400);
		}
		
		//判断用户条件
		$user_validate = $MiningModel->mineValidateByUser($uid, $this->CFG);
		if ($user_validate['error']) {
			$this->logWrite('丰收首页Error:'.$user_validate['msg'], 1, false, $uid);
			M()->commit();
			
			if ($user_validate['is_print']) {
				$this->myApiPrint($user_validate['msg']);
			}
			
			$this->myApiPrint('添加当日丰收队列成功:USER', 400);
		}
		
		//查询是否存在丰收队列数据：无则新增,有则更新日期
		$info = M('MiningQueue')->where("user_id={$uid} and is_expired=0 and FROM_UNIXTIME(updated_time, '%Y%m%d')=FROM_UNIXTIME(UNIX_TIMESTAMP(), '%Y%m%d')")->find();
		
		if (!$info) {
			$data_info = [
				'user_id' => $uid,
				'created_time' => time(),
				'updated_time' => time()
			];
			$result = M('MiningQueue')->add($data_info);
			
			//当前用户农场个数
			$portion_info = $MiningModel->getPortionNumber($uid, true);
			$portion = $portion_info['enabled'];
			
			//扣除澳洲SKN股数
			$mining_use_amount = floor ( $portion / 0.5 ) * $this->CFG['enjoy_mining'];
			$result2 = $EnjoyModel->miningUse($uid, $mining_use_amount);
			
			if (!$result || !$result2) {
				M()->rollback();
				$this->logWrite('丰收首页Error:添加当日丰收队列失败', 1, false, $uid);
				$this->myApiPrint('添加当日丰收队列失败');
			}
			
			M()->commit();
		}
		
		$this->logWrite('丰收首页Success:添加当日丰收队列成功', 1, false, $uid);
		$this->myApiPrint('添加当日丰收队列成功', 400);
	}
	
	/**
	 * 农场详情
	 * 
	 * @method POST
	 * 
	 * @param int $uid 用户ID
	 */
	public function protionDetails() {
		$uid = $this->post['uid'];
		
		$MiningModel = new MiningModel();
		
		if (!validateExtend($uid, 'NUMBER')) {
			$this->myApiPrint('参数格式有误');
		}
		
		$data = $MiningModel->getPortionNumber($uid, true);
		
		$return['list'] = [
			['label' => '内排贡献业绩值', 'value' => $data['pv_old']],
			['label' => '内排农场数', 'value' => $data['old']],
			['label' => '正式业绩值', 'value' => $data['pv_release']],
			['label' => '正式农场数', 'value' => $data['release']],
			['label' => '总农场数', 'value' => $data['all']],
			['label' => '有效农场数', 'value' => $data['enabled']],
			['label' => '报废农场数', 'value' => sprintf('%.1f', $data['all'] - $data['enabled'])],
			['label' => '未生成农场业绩值', 'value' => $data['pv_not_generate']],
			['label' => '农场计算说明', 'value' => $this->CFG['mine_machine_captions']]
		];
		
		$this->myApiPrint('获取成功', 400, $return);
	}

}

?>