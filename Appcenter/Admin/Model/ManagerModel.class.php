<?php
// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 管理员模型
// +----------------------------------------------------------------------
namespace Admin\Model;

use Common\Model\CommonModel;

class ManagerModel extends CommonModel {

	/**
	 * 检测登录信息是否存在，并返回布尔值或账号信息
	 *
	 * @param $data 用于其他方法调用时传值使用:array('username'=>'','password'=>'md5'),默认false
	 * @param $asyn 是否为异步调用,默认false
	 *
	 * @return boolean
	 */
	public function checkExist( $data = false, $asyn = false ) {
		$return = array( 'error' => true, 'data' => '' );

		$params = I( 'post.' );

		$cook_auth = cookie( C( 'AUTH_COOKIE' ) );

		//优先级：$data > $cook_auth > $post
		if ( $data ) {
			$params = $data;
		} elseif ( $cook_auth ) {
			$params = $cook_auth;
		} else {
			$params['password'] = md5( $params['password'] );
		}

		if ( ! $params['username'] ) {
			$return['data'] = '登陆异常，重新登陆';
			return $return;
		}

		$cookie_remember = isset( $_POST['remember'] ) ? true : false;

		$map['m.loginname'] = array( 'eq', $params['username'] );


		$info = $this->alias( 'mem' )
		             ->field( 'mem.*,m.level,m.role,m.img,m.id as mid,m.password,m.is_lock m_is_lock,m.store_flag,m.nickname,m.loginname' )
		             ->join( 'join __MEMBER__ m on m.id=mem.uid' )
                    ->where( $map )
                    ->find();

		if ( count( $info ) > 0 ) {
			$wcd = C( 'ADMINISTRATOR_VAR' );

			//当$data=false且$asyn=true时检测超管登陆页面参数是否正确
			if ( ! $data && $asyn && $info['id'] == 1 ) {
				if ( $params['wcd'] != $wcd ) {
					$return['data'] = '非法操作';

					return $return;
				}
			}

			if ( $info['password'] != $params['password'] ) {
				$return['data'] = '密码不正确';
			} elseif ( $info['is_lock'] == 1 ) { //已锁定
				$return['data'] = '该管理员账号已被锁定';
			} elseif ( $info['m_is_lock'] == 1 ) {
				$return['data'] = '该管理员对应会员账号已被锁定';
				//} elseif ($info['level']<3) {
				//$return['data'] = '你没有登录权限';
			} else {
				//兼容V5.0新增的role字段
				$info['level'] = empty( $info['role'] ) ? $info['level'] : $info['role'];

				//更新登陆信息
				$login_data = array(
					'last_login_time' => time(),
					'last_login_ip'   => get_client_ip( 0, true ),
					'login_count'     => intval( $info['login_count'] ) + 1,
				);
//				$this->where( 'id=' . $info['id'] )->save( $login_data );
                M('Manager')->where( 'id=' . $info['id'] )->save( $login_data );


				//获取角色名称,用于注入session
				$map_group['aga.uid'] = array( 'eq', $info['id'] );
				$group_info           = M( 'AuthGroupAccess' )->alias( 'aga' )
				                                              ->join( 'left join __AUTH_GROUP__ ag on aga.group_id=ag.id' )
				                                              ->field( 'ag.title,ag.id' )
				                                              ->where( $map_group )
				                                              ->select();
				$info_group           = function () use ( $group_info ) {
					$info_group_list = array();
					foreach ( $group_info as $k => $v ) {
						$info_group_list['title'][] = $v['title'];
						$info_group_list['id'][]    = $v['id'];
					}

					return $info_group_list;
				};
				$infoGroup = $info_group();
				if(!$infoGroup){
					$return['data'] = '你没有登录权限';
					return $return;
				}

				$info['group']        = implode( ',', $infoGroup['title'] );
				$info['group_id']     = implode( ',', $infoGroup['id'] );

				//根据角色检测后台顶部菜单是否匹配,如有匹配的则在后台页面显示对应菜单
				$module_list = array( 'Admin', 'Shop', 'Merchant', 'System' );

				foreach ( $module_list as $k => $module ) {
					//获取所有角色的规则id列表
					unset( $map_group );
					$map_group['id'] = array( 'in', $info['group_id'] );

					$group_list      = M( 'AuthGroup' )->where( $map_group )->field( 'rules' )->select();

					$group_rules = array();
					foreach ( $group_list as $list ) {
						$group_rules[] = $list['rules'];
					}

					$group_rules = explode( ',', implode( ',', $group_rules ) );
					$group_rules = array_unique( $group_rules );


					//判断是否有对应菜单的规则权限
					unset( $map_rule );
					$map_rule['id']   = array( 'in', implode( ',', $group_rules ) );
					$map_rule['name'] = array( 'like', "{$module}%" );
					$map_rule         = M( 'AuthRule' )->where( $map_rule )->field( 'id' )->find();
					if ( ! $map_rule ) {
						unset( $module_list[ $k ] );
					}

					//判断当前管理员是否为商家,若不是商家则不显示Merchant
					if ( $k == 2 ) {
						if ( $info['store_flag'] == 0 ) {
							unset( $module_list[ $k ] );
						} else {
							$map_store['uid']           = array( 'eq', $info['mid'] );
							$map_store['manage_status'] = array( 'eq', 1 );
							$map_store['status']        = array( 'eq', 0 );
							$store_info                 = M( 'Store' )->where( $map_store )->find();
							if ( ! $store_info ) {
								unset( $module_list[ $k ] );
							}
						}
					}
				}

				$info['module_list'] = $module_list;

				$return['error'] = false;
				$return['data']  = $this->writeSession( $info, $cookie_remember );
			}
		} else {
			$return['data'] = '该帐户不存在';
		}

		return $return;
	}

	/**
	 * 获取账号信息或列表
	 *
	 * @param array $map where条件,默认空
	 * @param boolean $select 查询方式[select,find默认]
	 * @param string/array $field 字段域,默认空
	 * @param $distinct boolean 是否查询唯一不同的值
	 * @param $limit string
	 *
	 * @return array
	 */
	public function getMemberList( $map = '', $select = false, $field = '', $distinct = false, $limit = false ) {
		$field = empty( $field ) ? 'mem.*,m.nickname,m.loginname,m.username,m.id mid' : $field;

		$memberInfo = $this->alias( 'mem' );
		$memberInfo = $memberInfo->join( 'left join __MEMBER__ m ON m.id=mem.uid' );
		$memberInfo = $memberInfo->join( 'left join __AUTH_GROUP_ACCESS__ aga ON aga.uid=mem.id' );
		$memberInfo = empty( $field ) ? $memberInfo : $memberInfo->field( $field );
		$memberInfo = empty( $map ) ? $memberInfo : $memberInfo->where( $map );

		$memberInfo = $memberInfo->order( 'mem.id asc' );
		$memberInfo = $distinct ? $memberInfo->distinct( true ) : $memberInfo;
		$memberInfo = $limit ? $memberInfo->limit( $limit ) : $memberInfo;
		$memberInfo = $memberInfo->group( 'mem.id' );
		$memberInfo = $select ? $memberInfo->select() : $memberInfo->find();

		return $memberInfo;
	}

	/**
	 * 统一session注入
	 *
	 * @param $member array 用户信息
	 * @param $cookie_remember boolean 是否同时保存cookie
	 */
	public function writeSession( $member, $cookie_remember = false ) {

		if ( $cookie_remember ) {
			$cookie = array(
				'loginname' => $member['loginname'],
				'password'  => $member['password'],
			);
			cookie( C( 'AUTH_COOKIE' ), $cookie );
		}

		//兼容老版session
		session( 'admin_id', $member['id'] ); //manager表ID
		session( 'admin_level', $member['level'] );
		session( 'admin_loginname', $member['loginname'] );
		session( 'admin_nickname', $member['nickname'] );
		session( 'admin_username', $member['username'] );
		session( 'admin_img', $member['img'] );
		session( 'admin_group', $member['group'] );
		session( 'admin_group_id', $member['group_id'] );
		session( 'admin_mid', $member['mid'] ); //member表ID
		session( 'admin_module_list', $member['module_list'] ); //具有权限的系统顶部菜单列表
		session( 'admin_safe_pwd', $member['password'] );

		$member = array(
			'admin_id'          => $member['id'], //manager表ID
			'admin_level'       => $member['level'],
			'admin_loginname'   => $member['loginname'],
			'admin_nickname'    => $member['nickname'],
			'admin_username'    => $member['username'],
			'admin_img'         => $member['img'],
			'admin_group'       => $member['group'],
			'admin_group_id'    => $member['group_id'],
			'admin_mid'         => $member['mid'], //member表ID
			'admin_module_list' => $member['module_list'], //具有权限的系统顶部菜单列表
			'admin_safe_pwd'    => $member['password'],
		);
		session( C( 'AUTH_SESSION' ), $member );

		return true;
	}

}

?>