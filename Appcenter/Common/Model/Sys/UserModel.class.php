<?php
namespace Common\Model\Sys;
use Common\Model\BaseModel;

/**
 * 用户模型
 *
 */
class UserModel extends BaseModel {
	
	/**
	 * 用户表
	 */
	protected function M() {
		return M('User');
	}
	
	/**
	 * 获取用户信息
	 * 
	 * @param string $field 字段
	 * @param mixed $where 筛选条件
	 * @param mixed $page 页数(默认flash不分页)
	 * @param int $listRows 每页个数
	 * @param string $order 排序
	 * @param string $group 分组
	 * @param string $having 过滤
	 * 
	 * @return array
	 */
	public function getUserList($field='', $where='', $page=false, $listRows=20, $order='us.user_id desc', $group='us.user_id', $having='') {
		$field = empty($field) ? 'us.user_id,us.user_mobile,us.user_nickname,us.user_avatar,us.user_status' : $field;
		
		$list = $this->M()
			->alias('us')
			->field($field)
			->join('left join __MERCHANT__ mer ON mer.user_id=us.user_id')
			->join('left join __ACCOUNT__ acc ON acc.user_id=us.user_id and acc.account_tag=0')
			->join('left join __USER_AUTH__ usa ON usa.user_id=us.user_id and usa.auth_status=2')
			->where($where);
		
		$_totalRows = 0;
		if ($page) {
			$temp = clone $list;
			$_totalRows = $temp->count(0);
		}
		
		if ($_totalRows > 0) {
			$list = $list->page($page, $listRows);
		}
		
		$list = $list->group($group);
		$list = empty($having) ? $list : $list->having($having);
		
		$list = $list->order($order)->select();
		
		return [
    		'paginator' => $this->paginator($_totalRows, $listRows),
    		'list' => $list,
    	];
	}
	
	/**
	 * 获取用户connect信息
	 * 
	 * @param int $user_id 用户ID
	 */
	public function getUserConnectList($user_id) {
		$connect_list = M('UserConnect')->where("user_id={$user_id}")->select();
		
		$list = [];
		foreach ($connect_list as $k=>$v) {
			$list[] = [
				'connect_platform' => $v['connect_platform'],
				'connect_platform_cn' => C('FIELD_CONFIG')['user_connect']['connect_platform'][$v['connect_platform']],
				'connect_openid' => $v['connect_openid'],
			];
		}
		
		return $list;
	}
	
	/**
	 * 获取用户身份信息
	 * 
	 * @param int $user_id 用户ID
	 */
	public function getUserIdentityList($user_id) {
		//省、市、县
		$agent_list = M('UserAgent')->where("user_id={$user_id}")->select();
		$agent_list_all = [];
		foreach ($agent_list as $k=>$v) {
			if (!empty($v['province_id'])) {
				$agent_list_all['province'][$v['province_id']] = [
					'province_id' => $v['province_id'],
					'area' => M('Province')->where('province_id='.$v['province_id'])->getField('province_name'),
				];
			}
			
			if (!empty($v['city_id'])) {
				$agent_list_all['city'][$v['city_id']] = [
					'city_id' => $v['city_id'],
					'area' => getAreaFullName($v['city_id'], 'city'),
				];
			}
			
			if (!empty($v['county_id'])) {
				$agent_list_all['county'][$v['county_id']] = [
					'county_id' => $v['county_id'],
					'area' => getAreaFullName($v['county_id'], 'county'),
				];
			}
		}
		
		//商家
		$merchant_info = M('Merchant')
			->alias('mer')
			->join('join __MERCHANT_CATEGORY__ mec ON mec.category_id=mer.category_id')
			->where("mer.user_id={$user_id} and mer.merchant_status=2")
			->field('mer.merchant_id,mer.merchant_name,mec.category_name')
			->find();
		
		//个人代理
		$maker_info = M('User')->where("user_id={$user_id}")->field('user_maker')->find();
		$maker_apply_info = M('ApplyMaker')->where("user_id={$user_id} and apply_status=2")->find();
		$maker_info['area'] = getAreaFullName($maker_apply_info['county_id'], 'county');
		
		$list = [
			'agent' => $agent_list_all,
			'merchant' => $merchant_info,
			'maker' => $maker_info,
		];
		
		return $list;
	}
	
	/**
	 * 获取个人代理信息
	 *
	 * @param string $field 字段
	 * @param mixed $where 筛选条件
	 * @param mixed $page 页数(默认flash不分页)
	 * @param int $listRows 每页个数
	 * @param string $order 排序
	 * @param string $group 分组
	 * @param string $having 过滤
	 *
	 * @return array
	 */
	public function getAgentPersonalList($field='', $where='', $page=false, $listRows=20, $order='us.user_id desc', $group='us.user_id', $having='agent_id is null') {
		$field = empty($field) ? 'us.user_id,us.user_mobile,us.user_nickname,us.user_avatar,us.user_status,mer.county_id' : $field;
		$field .= ',usa.agent_id';
	
		$list = M('Merchant')
			->alias('mer')
			->field($field)
			->join('left join __USER__ us ON us.user_id=mer.merchant_puton')
			->join('left join __USER_AGENT__ usa ON usa.county_id=mer.county_id and usa.user_id=mer.merchant_puton')
			->where($where);
	
		$_totalRows = 0;
		if ($page) {
			$temp = clone $list;
			$_totalRows = $temp->count(0);
		}
	
		if ($_totalRows > 0) {
			$list = $list->page($page, $listRows);
		}
		
		$list = $list->group($group);
		$list = empty($having) ? $list : $list->having($having);
	
		$list = $list->order($order)->select();
	
		return [
			'paginator' => $this->paginator($_totalRows, $listRows),
			'list' => $list,
		];
	}
	
}