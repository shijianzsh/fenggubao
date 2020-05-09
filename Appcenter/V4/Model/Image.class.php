<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/4
 * Time: 17:07
 */

namespace V4\Model;


/**
 * 货币类型
 * Class Currency
 * @package V4\Model
 */
class Image {
	public static function url( $path, $localoss='local' ) {

		if ( strtolower( substr( $path, 0, 4 ) ) == 'http' ) {
			return $path;
		}

		$path = preg_replace( '/^\//', '', $path );
		
		//oss域名头配置参数
		$attach_domain_key = array_rand( C( 'DEVICE_CONFIG' )[ C( 'DEVICE_CONFIG_DEFAULT' ) ]['attach_domain'], 1 );
		$attach_domain     = C( 'DEVICE_CONFIG' )[ C( 'DEVICE_CONFIG_DEFAULT' ) ]['attach_domain'][ $attach_domain_key ];
		
		switch ( $localoss ) {
			case 'local':
				$path = C( 'LOCAL_HOST' ) . $path;
				break;
			case 'oss':
				$path = $attach_domain. $path;
				break;
		}

		return $path;
	}

	/**
	 * 格式化数据指定键名的键值URL格式
	 * 
	 * @param array $item 待处理数据
	 * @param mixed $keys 指定的键名(字符串或数组格式)
	 * @param string $localoss 处理类型(处理为本地域名头或者oss域名头)[默认:local]
	 * 
	 * @return array
	 */
	public static function formatItem( $item, $keys = [], $localoss='local' ) {
		if ( ! is_array( $keys ) ) {
			$keys = [ $keys ];
		}
		if ( $item ) {
			foreach ( $keys as $key ) {
				$item[ $key ] = self::url( $item[ $key ], $localoss );
			}
		}

		return $item;
	}

	public static function formatList( $list, $keys = [], $localoss='local' ) {
		foreach ( $list as $index => $item ) {
			$list[ $index ] = self::formatItem( $item, $keys, $localoss );
		}

		return $list;
	}
}