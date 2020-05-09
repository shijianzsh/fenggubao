<?php
// +----------------------------------------------------------------------
// | 配置管理
// +----------------------------------------------------------------------
namespace System\Controller;

use Common\Controller\AuthController;
use Common\Model\Sys\SettingModel;

class ConfigController extends AuthController {

	public function index() {
		$SettingModel = new SettingModel();

		$data_group = $SettingModel->getGroupList( '*', [ 'group_status' => 1 , 'group_id' => ['not in', '7,8'] ] );
		$list       = $data_group['list'];

		foreach ( $list as $k => $v ) {
			$where              = [
				'group_id'        => $v['group_id'],
				'settings_status' => [ 'in', '(0,1)' ],
			];
			$setting_list       = $SettingModel->getList( '*', $where );
			$list[ $k ]['list'] = $setting_list['list'];
		}
		$this->assign( 'list', $list );

		$this->display();
	}
	
	/**
	 * 谷聚金专区奖项管理
	 */
	public function gjj() {
		$SettingModel = new SettingModel();
	
		$data_group = $SettingModel->getGroupList( '*', [ 'group_status' => 1 , 'group_id' => ['eq', 8] ] );
		$list       = $data_group['list'];
	
		foreach ( $list as $k => $v ) {
			$where              = [
			'group_id'        => $v['group_id'],
			'settings_status' => [ 'in', '(0,1)' ],
			];
			$setting_list       = $SettingModel->getList( '*', $where );
			$list[ $k ]['list'] = $setting_list['list'];
		}
		$this->assign( 'list', $list );
	
		$this->display('index');
	}
	
	/**
	 * 挖矿奖项管理
	 */
	public function mining() {
		$SettingModel = new SettingModel();
	
		$data_group = $SettingModel->getGroupList( '*', [ 'group_status' => 1 , 'group_id' => ['eq', 7] ] );
		$list       = $data_group['list'];
	
		foreach ( $list as $k => $v ) {
			$where              = [
			'group_id'        => $v['group_id'],
			'settings_status' => [ 'in', '(0,1)' ],
			];
			$setting_list       = $SettingModel->getList( '*', $where );
			$list[ $k ]['list'] = $setting_list['list'];
		}
		$this->assign( 'list', $list );
	
		$this->display('index');
	}

	public function special() {
		$SettingModel = new SettingModel();

		$bonusExcludePartner = explode( ',', M( 'Settings' )->where( "settings_code='bonus_exclude_partner'" )->getField( 'settings_value' ) );
		$partnerList         = M( 'member' )->field( 'id,loginname,nickname,truename' )->where( 'is_partner=1' )->select();

		foreach ( $partnerList as $key => $item ) {
			$partnerList[ $key ]['is_exclude'] = 0;
			if ( in_array( $item['id'], $bonusExcludePartner ) ) {
				$partnerList[ $key ]['is_exclude'] = 1;
			}
		}
		$this->assign( 'partnerList', $partnerList );

		$bonusExcludeCompanies = explode( ',', M( 'Settings' )->where( "settings_code='bonus_exclude_company'" )->getField( 'settings_value' ) );
		$companyList           = M( 'member' )->field( 'id,loginname,nickname,truename' )->where( 'role in (4)' )->select();
		foreach ( $companyList as $key => $item ) {
			$companyList[ $key ]['is_exclude'] = 0;
			if ( in_array( $item['id'], $bonusExcludeCompanies ) ) {
				$companyList[ $key ]['is_exclude'] = 1;
			}
		}
		$this->assign( 'companyList', $companyList );

		$this->display();
	}

	public function specialSave() {
		$data = $this->post;
		if ( $data['action'] == 'save_bonus_exclude_partner' ) {
			$item = M( 'Settings' )->where( "settings_code='bonus_exclude_partner'" )->find();
			if ( ! $item ) {
				$item = [
					'settings_title'  => '不参与分红的合伙人',
					'settings_type'   => 'text',
					'settings_status' => '1',
					'settings_order'  => '99',
				];
			}
			$item['settings_value']  = implode( ',', $data['bonus_exclude_partner'] );
			$item['settings_uptime'] = time();

			if ( M( 'Settings' )->save( $item ) === false ) {
				$this->error( '保存失败' );
			} else {
				$this->success( '保存成功', '', false, "成功编辑配置项[{$item[settings_title]}][ID:{$item[settings_id]}]" );
			}
		} else if ( $data['action'] == 'save_bonus_exclude_company' ) {
			$item = M( 'Settings' )->where( "settings_code='bonus_exclude_company'" )->find();
			if ( ! $item ) {
				$item = [
					'settings_title'  => '不参与分红的省级合伙人',
					'settings_type'   => 'text',
					'settings_status' => '1',
					'settings_order'  => '96',
				];
			}
			$item['settings_value']  = implode( ',', $data['bonus_exclude_company'] );
			$item['settings_uptime'] = time();

			if ( M( 'Settings' )->save( $item ) === false ) {
				$this->error( '保存失败' );
			} else {
				$this->success( '保存成功', '', false, "成功编辑配置项[{$item[settings_title]}][ID:{$item[settings_id]}]" );
			}
		}
	}

	/**
	 * 保存配置参数
	 */
	public function parameterSave() {
		$data = $this->post;
		unset( $data['submit'] );
		unset( $data['__hash__'] );

		$sql = '';
		foreach ( $data as $k => $v ) {
			$tag = empty( $sql ) ? '' : ',';
			$sql .= " {$tag} ('{$k}', '{$v}') ";
		}
		$sql = " INSERT INTO __SETTINGS__ (settings_code,settings_value) VALUES {$sql} ON DUPLICATE KEY UPDATE settings_value=values(settings_value) ";

		if ( M()->execute( $sql ) === false ) {
			$this->error( '保存失败' );
		} else {
			$this->success( '操作成功', '', false, '成功修改了平台参数设置' );
		}
	}

	/**
	 * 新增配置组 [AJAX]
	 */
	public function addSettingsGroup() {
		$data = [
			'group_name'   => $this->post['group_name'],
			'group_status' => 1,
			'group_uptime' => time(),
		];

		if ( empty( $data['group_name'] ) ) {
			$this->error( '配置组名称不能为空' );
		}

		$info = M( 'SettingsGroup' )->where( "group_name='{$data[group_name]}'" )->find();
		if ( $info ) {
			$this->error( '该配置组名称已存在' );
		}

		if ( ! M( 'SettingsGroup' )->add( $data ) ) {
			$this->error( '创建失败' );
		} else {
			$this->success( '创建成功', '', false, "成功创建配置组[{$data[group_name]}]" );
		}
	}

	/**
	 * 新增配置项 [AJAX]
	 */
	public function addSettings() {
		$data = $this->post;

		$data['settings_status'] = 1;
		$data['settings_uptime'] = time();

		if ( ! validateExtend( $data['group_id'], 'NUMBER' ) ) {
			$this->error( '所属配置组参数格式有误' );
		}
		if ( empty( $data['settings_title'] ) ) {
			$this->error( '配置项标题不能为空' );
		}
		if ( ! validateExtend( $data['settings_code'], '/^[a-zA-Z]{1}[0-9a-zA-Z_-]+$/', true ) ) {
			$this->error( '配置项标识格式有误，需为英文数字组合' );
		}

		$id = M( 'Settings' )->add( $data );

		if ( ! $id ) {
			$this->error( '创建失败' );
		} else {
			$this->success( '创建成功', '', false, "成功创建配置项[{$data['settings_title']}][ID:{$id}]" );
		}
	}

	/**
	 * 保存配置项 [AJAX]
	 */
	public function saveSettings() {
		$data = $this->post;

		$data['settings_uptime'] = time();

		if ( ! validateExtend( $data['group_id'], 'NUMBER' ) ) {
			$this->error( '所属配置组参数格式有误' );
		}
		if ( empty( $data['settings_title'] ) ) {
			$this->error( '配置项标题不能为空' );
		}
		/*
		if (!validateExtend($data['settings_code'], '/^[a-zA-Z]{1}[0-9a-zA-Z_-]+$/', true)) {
			$this->error('配置项标识格式有误，需为英文数字组合');
		}
		*/

		//判断标题,代码是否已经存在
		$map_exists['settings_title'] = array( 'eq', $data['settings_title'] );
		$map_exists['settings_code']  = array( 'eq', $data['settings_code'] );
		$map_exists['settings_id']    = array( 'neq', $data['settings_id'] );
		$exists_info                  = M( 'Settings' )->where( $map_exists )->find();
		if ( $exists_info ) {
			$this->error( '配置标题或配置代码已经存在' );
		}

		if ( M( 'Settings' )->save( $data ) === false ) {
			$this->error( '保存失败' );
		} else {
			$this->success( '保存成功', '', false, "成功编辑配置项[{$data[settings_title]}][ID:{$data[settings_id]}]" );
		}
	}

	/**
	 * 保存配置组 [AJAX]
	 */
	public function saveSettingsGroup() {
		$data = $this->post;

		$data['group_uptime'] = time();

		if ( empty( $data['group_name'] ) ) {
			$this->error( '配置组名称不能为空' );
		}

		//判断配置组名称是否已经存在
		$map_exists['group_name'] = array( 'eq', $data['group_name'] );
		$map_exists['group_id']   = array( 'neq', $data['group_id'] );
		$exists_info              = M( 'SettingsGroup' )->where( $map_exists )->find();
		if ( $exists_info ) {
			$this->error( '配置组名称已经存在' );
		}

		if ( M( 'SettingsGroup' )->save( $data ) === false ) {
			$this->error( '保存失败' );
		} else {
			$this->success( '保存成功', '', false, "成功编辑配置组[{$data[group_name]}][ID:{$data[group_id]}]" );
		}
	}

	/**
	 * 系统维护
	 */
	public function siteStatus() {
		$site_switch       = M( 'Settings' )->where( "settings_code='site_switch'" )->find();
		$site_switch_intro = M( 'Settings' )->where( "settings_code='site_switch_intro'" )->find();
		if ( ! $site_switch || ! $site_switch_intro ) {
			$this->error( '系统维护配置参数出现异常，请联系技术人员' );
		}
		$this->assign( 'site_switch', $site_switch );
		$this->assign( 'site_switch_intro', $site_switch_intro );

		$this->display();
	}

}

?>