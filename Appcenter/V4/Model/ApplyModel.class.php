<?php

namespace V4\Model;

class ApplyModel {

	private static $_instance;

	public static function getInstance() {
		if ( ! ( self::$_instance instanceof self ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}


	public function service() {

	}

	public function serviceCallback( $user_id, $apply_level = 3 ) {
		$member_info = M( 'member' )->where( [ 'id' => $user_id, 'role' => '0' ] )->field( 'password,nickname,loginname,username' )->find();
		if ( ! $member_info ) {
			return false;
		}

		if ( ! M( 'member' )->where( [ 'id' => $user_id, 'role' => '0' ] )->save( [ 'role' => $apply_level ] ) ) {
			return false;
		}

		if ( ! M( 'apply_service_center' )->where( [ 'uid' => $user_id, 'apply_level' => $apply_level, 'status' => '0' ] )->save( [ 'status' => 1, 'post_time' => time() ] ) ) {
			return false;
		}


		//同步添加对应管理员用户


		$AuthMember            = D( "Admin/Manager" );
		$AuthGroupAccess       = D( "Admin/AuthGroupAccess" );
		$group_access_group_id = array( C( 'ROLE_MUST_LIST.service' ) );
		$role_must_list        = C( 'ROLE_MUST_LIST' );
		unset( $role_must_list['merchant'] );
		$role_must_list = array_values( $role_must_list );
//		$role_name      = '服务中心';

		//如果存在账户,则尝试更新角色
		$auth_member_info = $AuthMember->getMemberList( [ 'mem.uid' => $user_id ], false, 'mem.id' );
		if ( $auth_member_info ) {

			$auth_group_access_list = $AuthGroupAccess->getGroupAccessList( 'group_id', [ 'uid' => $auth_member_info['id'] ], true );
			foreach ( $auth_group_access_list as $k => $group ) {
				if ( ! in_array( $group['group_id'], $role_must_list ) ) {
					$group_access_group_id[] = $group['group_id'];
				}
			}
			$AuthGroupAccess->delAccess( [], $auth_member_info['id'] );
			$AuthGroupAccess->addAccess( $group_access_group_id, $auth_member_info['id'] );

//			$this->logWrite( "同步添加后台管理员用户{$member_info['username']}[{$data['loginname']}][{$member_info['nickname']}]的[{$role_name}]角色" );

			return true;

		}
		$manager_data = array(
			'uid'       => $user_id,
			'type'      => 'service',
			'nickname'  => $member_info['nickname'],
			'loginname' => $member_info['loginname'],
		);
		if ( ! $AuthMember->create( $manager_data ) ) {
			return false;
		}

		$id = $AuthMember->add();
		if ( ! $id ) {
			return false;
		}
		foreach ( $group_access_group_id as $k => $v ) {
			$AuthGroupAccess->addAccess( $group_access_group_id, $id );
			$AuthGroupAccess->delAccess( $group_access_group_id, $id );
		}

//		$pm = new ProcedureModel();
//		if ( ! $pm->execute( 'Event_applyService', $user_id, '@error' ) ) {
//			return false;
//		}
//
		return true;
	}
}
