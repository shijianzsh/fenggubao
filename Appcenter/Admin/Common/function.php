<?php
/**
 * Created by PhpStorm.
 * User: jay
 * Date: 2018/9/23
 * Time: 15:33
 */

function dated( $gs, $time ) {
	if ( intval( $time ) == 0 ) {
		return '';
	} else {
		return date( $gs, $time );
	}
}

function getLevelName( $level, $star = 0 ) {
	$star = $star ? $star . '星' : '';
	switch ( $level ) {
		case 1:
			return $star . '体验会员';
		case 2:
			return $star . '个人代理';
		case 5:
			return $star . '爱心创客';
		case 99:
			return $star . '管理员';
		default :
			return '未知';
	}
}

function getRoleName( $role ) {
	switch ( $role ) {
		case 3:
			return '区域合伙人';
		case 4:
			return '省级合伙人';
	}
}

function getPartnerName( $is_partner ) {
	if ( $is_partner ) {
		return '合伙人';
	}
}