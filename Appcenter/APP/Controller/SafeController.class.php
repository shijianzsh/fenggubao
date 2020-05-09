<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 安全监测 和 远程对接 接口
// +----------------------------------------------------------------------
namespace APP\Controller;
use Common\Controller\ApiController;
use V4\Model\AccountModel;
use V4\Model\AccountRecordModel;
use V4\Model\Currency;

class SafeController extends ApiController {
	
	protected $api_safe_session = 'zcshApiSafeSession';
	protected $api_safe_env = 1; //0:开发环境 1:正式环境
	protected $api_safe_ip = '120.79.85.89'; //允许远程调用接口的服务器IP列表
	protected $api_remote_ip; //远程IP
	protected $push_uid = 4365; //接收推送消息的用户ID,多个用半角逗号隔开
	protected $sms_phone = '15184488516'; //接收[监控用户资金变动是否正常]短信通知的用户手机号,多个用半角逗号隔开
	protected $log_file; //用于存放日志文件
	protected $gljt_thread_path; //管理津贴发放线程调用对应文件路径
	protected $sms_phone_gljt = '13547674999,15184488516'; //接收[管理津贴分发完毕]通知的用户手机号,多个用半角逗号隔开
	
	public function __construct($request='') {
		parent::__construct($request);
		
		$this->gljt_thread_path = $_SERVER['DOCUMENT_ROOT'].'/GLJT/';
		
		//检测创建日志文件
		$this->log_file = $_SERVER['DOCUMENT_ROOT'].'/record/'.MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME.'/'.date('Y').'_'.date('m').'_'.date('d').'.log.php';
		if (!file_exists($this->log_file)) {
			$log_dir = dirname($this->log_file);
			if (!is_dir($log_dir)) {
				mkdir($log_dir,0777,true);
			}
			file_put_contents($this->log_file, "<?php\n exit; \n ?> \n".PHP_EOL, FILE_APPEND);
		}
		
		$this->api_remote_ip = get_client_ip();
		$ip_allow = strpos($this->api_safe_ip, ',') ? explode(',', $this->api_safe_ip) : array($this->api_safe_ip);
		$ip_result = false; //ip检测结果,初始化默认不通过
		foreach ($ip_allow as $ip) {
			if (preg_match('/\-/', $ip)) { //ip段
				$ip_dot = explode('.', $ip);
				$ip_prefix = $ip_dot[0]. '.'. $ip_dot[1]. '.'. $ip_dot[2];
				if (strpos($this->api_remote_ip, $ip_prefix)!==false) {
					$ip_result = true;
					break;
				}
			} else {
				if ($ip == $this->api_remote_ip) {
					$ip_result = true;
					break;
				}
			}
		}
		
		if (!$ip_result) {
			file_put_contents($this->log_file, date('Y-m-d H:i:s')."[ip:{$this->api_remote_ip}][start][fail:Unauthorized IP]".PHP_EOL, FILE_APPEND);
			exit('Unauthorized IP:'.$this->api_remote_ip);
		} else {
			file_put_contents($this->log_file, date('Y-m-d H:i:s')."[ip:{$this->api_remote_ip}][start][success]".PHP_EOL, FILE_APPEND);
		}
	}
	
	/**
	 * 监控用户资金变动是否正常
	 * 
	 * @建议频率: 60*60s
	 */
	public function monitorMemberAccountChange() {
		$Login = M('Login');
		
		//1.随机3条账户数据
		$am = new AccountModel();
		$list = $am->getRandomRecord();
		
		$arm = new AccountRecordModel();
		$warning = array();
		foreach ($list as $row){
		    $cashrecord = $arm->getByUserId($row['user_id'], Currency::Cash, '');
		    $goldcoinrecord = $arm->getByUserId($row['user_id'], Currency::GoldCoin, '');
		    $colorcoinrecord = $arm->getByUserId($row['user_id'], Currency::ColorCoin, '');
		    $pointrecord = $arm->getByUserId($row['user_id'], Currency::Points, '');
		    $bonusrecord = $arm->getByUserId($row['user_id'], Currency::Bonus, '');
		    
		    if($cashrecord['record_balance'] != $row['account_cash_balance']){
		        $warning[] = array('user_id'=>$row['user_id'],'record_id'=>$cashrecord['record_id'],'account_cash_balance'=>$row['account_cash_balance']);
		    }
		    if($goldcoinrecord['record_balance'] != $row['account_goldcoin_balance']){
		        $warning[] = array('user_id'=>$row['user_id'],'record_id'=>$goldcoinrecord['record_id'],'account_goldcoin_balance'=>$row['account_goldcoin_balance']);
		    }
		    if($colorcoinrecord['record_balance'] != $row['account_colorcoin_balance']){
		        $warning[] = array('user_id'=>$row['user_id'],'record_id'=>$colorcoinrecord['record_id'],'account_colorcoin_balance'=>$row['account_colorcoin_balance']);
		    }
		    if($pointrecord['record_balance'] != $row['account_points_balance']){
		        $warning[] = array('user_id'=>$row['user_id'],'record_id'=>$pointrecord['record_id'],'account_points_balance'=>$row['account_points_balance']);
		    }
		    if($bonusrecord['record_balance'] != $row['account_bonus_balance']){
		        $warning[] = array('user_id'=>$row['user_id'],'record_id'=>$bonusrecord['record_id'],'account_bonus_balance'=>$row['account_bonus_balance']);
		    }
		}
		
		
		//整理数据,判断报警级别,推送报警数据+发送短信通知
		if (count($warning)>0) {
			$push_data = array();
			$push_extra = array();
			
			$map_login['registration_id'] = array('neq', '');
			$map_login['uid'] = array('in', $this->push_uid);
			$push_data['all'] = $Login->where($map_login)->getField('registration_id', true);
			
			$warning = "警报::报警级别:".count($warning)."级,异常数据:".json_encode($warning);
			
			$this->push($push_data, $warning, $push_extra);
			$this->sms($this->sms_phone, '', $warning, 'warning');
		}
		
		exit;
	}
	
	/**
	 * 对接 推送队列执行 接口
	 * 
	 * @频率:30s
	 */
	public function pushQueueAction() {
		$PushQueue = M('PushQueue');
		$Login = M('Login');
		$limit = 100; //全用户分批次推送每次100个registration_id
		
		$map_login = array();
		$map_model = array();
		$map_push = array();
		$push_data = array();
		$push_last = false; //针对推送至全部用户时,分批次推送的最后一个用户uid(统一按asc排序)
		
		$push_info = $PushQueue->order('id asc')->find();
		if (!$push_info) {
			exit;
		}
		
		$map_push['id'] = array('eq', $push_info['id']);
	
		//获取push接收者
		$map_login['registration_id'] = array('neq', '');
		if ($push_info['push_uid'] > 0) {
			$map_login['uid'] = array('eq', $push_info['push_uid']);
			
			$push_data['all'] = $Login->where($map_login)->getField('registration_id', true);
			
			//registration_id不存在
			if (empty($push_data['all'])) {
				$PushQueue->where($map_push)->delete();
				exit;
			}
		} else {
			$map_login['id'] = array('gt', $push_info['push_last']);
			
			$push_count = $Login->where($map_login)->order('id asc')->limit($limit)->count();
			$push_last = $push_count<$limit ? false : $Login->field('id')->where($map_login)->order('id asc')->limit($limit)->getField('id', true);
			$push_last = !$push_last ? $push_last : $push_last[count($push_last)-1];
			
			//当上一批次已经是最后一批次时
			if ($push_count==0) {
				$PushQueue->where($map_push)->delete();
				exit;
			}
			
			$push_data['all'] = $Login->where($map_login)->order('id asc')->limit($limit)->getField('registration_id', true);
		}
		
		$status = $this->push($push_data, $push_info['push_content'], json_decode($push_info['push_extra'],true));
		if ($status) {
			if (!$push_last) {
				$PushQueue->where($map_push)->delete();
			} else {
				$data = array(
					'push_last' => $push_last
				);
				$PushQueue->where($map_push)->save($data);
			}
		}
	}
	
	
	/**
	 * 方案二(1.1)
	 * 对接 丰收完成后依据zc_member_bonus表中数据添加待分发管理津贴用户数据 接口
	 * 
	 * @建议频率 1s
	 */
	public function gljtInsertShareData1() {
		$status = M()->execute(C('ALIYUN_TDDL_MASTER') . "call procedure_insert_gljt_share_data_1(@msg)");
		if ($status=='1') {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * 方案二(1.2)
	 * 对接 丰收完成后依据zc_member_bonus表中数据添加待分发管理津贴用户数据 接口
	 *
	 * @建议频率 1s
	 */
	public function gljtInsertShareData2() {
		$status = M()->execute(C('ALIYUN_TDDL_MASTER') . "call procedure_insert_gljt_share_data_2(@msg)");
		if ($status=='1') {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * 方案二(1.3)
	 * 对接 丰收完成后依据zc_member_bonus表中数据添加待分发管理津贴用户数据 接口
	 *
	 * @建议频率 1s
	 */
	public function gljtInsertShareData3() {
		$status = M()->execute(C('ALIYUN_TDDL_MASTER') . "call procedure_insert_gljt_share_data_3(@msg)");
		if ($status=='1') {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * 方案二(1.4)
	 * 对接 丰收完成后依据zc_member_bonus表中数据添加待分发管理津贴用户数据 接口
	 *
	 * @建议频率 1s
	 */
	public function gljtInsertShareData4() {
		$status = M()->execute(C('ALIYUN_TDDL_MASTER') . "call procedure_insert_gljt_share_data_4(@msg)");
		if ($status=='1') {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * 方案二(1.5)
	 * 对接 丰收完成后依据zc_member_bonus表中数据添加待分发管理津贴用户数据 接口
	 *
	 * @建议频率 1s
	 */
	public function gljtInsertShareData5() {
		$status = M()->execute(C('ALIYUN_TDDL_MASTER') . "call procedure_insert_gljt_share_data_5(@msg)");
		if ($status=='1') {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * 方案二(2.1)
	 * 对接 分发管理津贴 接口
	 * 
	 * @建议频率 1s
	 */
	public function gljtShare1() {
		$status = M()->execute(C('ALIYUN_TDDL_MASTER') . "call gljt_action1(@msg)");
		if ($status=='1') {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * 方案二(2.2)
	 * 对接 分发管理津贴 接口
	 *
	 * @建议频率 1s
	 */
	public function gljtShare2() {
		$status = M()->execute(C('ALIYUN_TDDL_MASTER') . "call gljt_action2(@msg)");
		if ($status=='1') {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * 方案二(2.3)
	 * 对接 分发管理津贴 接口
	 *
	 * @建议频率 1s
	 */
	public function gljtShare3() {
		$status = M()->execute(C('ALIYUN_TDDL_MASTER') . "call gljt_action3(@msg)");
		if ($status=='1') {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * 方案二(2.4)
	 * 对接 分发管理津贴 接口
	 *
	 * @建议频率 1s
	 */
	public function gljtShare4() {
		$status = M()->execute(C('ALIYUN_TDDL_MASTER') . "call gljt_action4(@msg)");
		if ($status=='1') {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * 方案二(2.5)
	 * 对接 分发管理津贴 接口
	 *
	 * @建议频率 1s
	 */
	public function gljtShare5() {
		$status = M()->execute(C('ALIYUN_TDDL_MASTER') . "call gljt_action5(@msg)");
		if ($status=='1') {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * 定时 检测当日管理津贴是否已分发完毕
	 * 
	 * @建议频率 300s
	 */
	public function checkGljtActionStatus() {
	    //调用存储过程，计算今天发的管理津贴
	    M()->execute(C('ALIYUN_TDDL_MASTER') . "call get_today_gltjamount('@dd')");
	    
		$Profits = M('Profits');
		
		//判断是否已检测过当日管津津贴是否已执行完毕
		$map_profits['_string'] = " from_unixtime(date_created,'%Y%m%d')=from_unixtime(unix_timestamp(),'%Y%m%d') ";
		$profits_info = $Profits->where($map_profits)->field('id,gljt_money')->find();
		
		if ($profits_info && $profits_info['gljt_money']<=0) {
			
			//获取当日管理津贴分发是否已全部执行完毕
			$gljt_share1 = M('GljtShare1')->count();
			$gljt_share2 = M('GljtShare2')->count();
			$gljt_share3 = M('GljtShare3')->count();
			$gljt_share4 = M('GljtShare4')->count();
			$gljt_share5 = M('GljtShare5')->count();
			$gljt_member_bonus1 = M('MemberBonus1')->count();
			$gljt_member_bonus2 = M('MemberBonus2')->count();
			$gljt_member_bonus3 = M('MemberBonus3')->count();
			$gljt_member_bonus4 = M('MemberBonus4')->count();
			$gljt_member_bonus5 = M('MemberBonus5')->count();
			
			if ($gljt_share1==0 && $gljt_share2==0 && $gljt_share3==0 && $gljt_share4==0 && $gljt_share5==0 && $gljt_member_bonus1==0 && $gljt_member_bonus2==0 && $gljt_member_bonus3==0 && $gljt_member_bonus4==0 && $gljt_member_bonus5==0) {
				
				$this->sms($this->sms_phone_gljt, '', "今日管理津贴已发放完毕,发放总额为".$profits_info['gljt_money']."元", 'event');
			}
		}
	}
	
	/**
	 * 定时执行 初始化检测升级用户星级创客 接口
	 * 
	 * @建议频率: 3s
	 * 
	 * @执行顺序:
	 * 1、执行pro_init_member_star_queue_init存储过程(只需初始化执行一次)
	 * 2、执行此任务
	 * 3、待任务执行完毕后,此接口任务即可停用,待下次需要重新初始化时按步骤重新执行即可
	 */
	public function init_member_star_queue() {
		$InitMemberStarQueue = M('InitMemberStarQueue');
		$limit = 1;
		
		$list = $InitMemberStarQueue->field('uid')->order('id asc')->limit($limit)->select();
		foreach ($list as $k=>$v) {
			M()->execute(C('ALIYUN_TDDL_MASTER') . "call star_member_condition({$v['uid']},@msg)");
			$InitMemberStarQueue->where('uid='.$v['uid'])->delete();
		}
	}
	
	public function __destruct() {
		parent::__destruct();
		
		file_put_contents($this->log_file, date('Y-m-d H:i:s')."[ip:{$this->api_remote_ip}][end]".PHP_EOL, FILE_APPEND);
	}
	
}
?>