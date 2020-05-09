<?php

// +----------------------------------------------------------------------
// | BSYX [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) BSYX  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | APP模型继承类
// +----------------------------------------------------------------------

namespace Common\Controller;

use Think\Controller;
use Sms\Request\V20160927 as Sms;

class ApiController extends PushController {

	protected $app_common_data; //APP公共信息
	protected $return; //通用返回信息数组
	protected $post; //通用接收的POST数组
	protected $get; //通用接收的GET数组
	protected $values; //接口传输参数,主要用于签名使用
	protected $cache_name; //缓存名规范
	protected $myapiprint_no_result = 'ABCDEFG-ZCSH-GFEDCBA'; //myApiPrint不返回result参数的标记
	protected $CFG;

	public function __construct( $request = '' ) {
		parent::__construct();

		//1.加载配置
		$settings  = M( 'settings' )->where( 'settings_status=1' )->getField( 'settings_code, settings_value' );
		$this->CFG = $settings;

		$this->post = empty( $request ) ? I( 'post.' ) : $request['post'];
		$this->get  = empty( $request ) ? I( 'get.' ) : $request['get'];

		$this->values = $this->post;

		$this->cache_name = MODULE_NAME . '@' . CONTROLLER_NAME . '@' . ACTION_NAME . '@' . md5( implode( '_', $this->post ) );

		//获取公共头数据
		$app_headers_data = getallheaders();
		//添加终端系统版本号信息
		if ( isset( $app_headers_data['User-Agent'] ) ) {
			$this->app_common_data['platform_version'] = $app_headers_data['User-Agent'];
		}
		if ( isset( $app_headers_data['app_common_data'] ) ) { //兼容接收APP传输的数据
			$app_headers_data = $app_headers_data['app_common_data'];
			$app_headers_data = json_decode( $app_headers_data, true );
		} elseif (isset($app_headers_data['App-Common-Data'])) { //新增的标准公共头
			$app_headers_data = $app_headers_data['App-Common-Data'];
			$app_headers_data = json_decode( $app_headers_data, true );
		} elseif ( isset( $this->post['app_common_data'] ) ) { //兼容接收非APP传输的数据
			$app_headers_data = $this->post['app_common_data'];
			$app_headers_data = json_decode( html_entity_decode( html_entity_decode( $app_headers_data ) ), true );
		} else {
			$app_headers_data = array();
		}
		foreach ( C( 'APP_COMMON_DATA' ) as $data ) {
			$this->app_common_data[ $data ] = isset( $app_headers_data[ $data ] ) ? $app_headers_data[ $data ] : '';
		}
		$this->return = array( 'code' => '', 'msg' => '', 'result' => '' );
		layout( true );

		//权限验证
		$this->purviewCheck();

		if ( $this->app_common_data['platform'] == 'ios' ) {
			if ( $this->app_common_data['uid'] != 30 && ! empty( $this->app_common_data['uid'] ) ) {
				//$this->myApiPrint('苹果系统正在升级中，敬请期待！', 300);
			}
		}

		//所有接口创建数据都取消token验证
		C( 'TOKEN_ON', false );

		//APP版本检测
		$map_apk_manage = false;
		if ( $this->app_common_data['platform'] == 'android' ) {
			//暂停安卓版本验证,使用安卓端自身验证功能
			//$map_apk_manage['platform'] = array('eq', 1);
		} elseif ( $this->app_common_data['platform'] == 'ios' ) {
			$map_apk_manage['platform'] = array( 'eq', 2 );
		}
		if ( $map_apk_manage ) {
			$apk_manage_last = M( 'ApkManage' )->where( $map_apk_manage )->field( 'version_num,is_need,content' )->order( 'id desc' )->find();
			if ( $apk_manage_last && $apk_manage_last['is_need'] == '1' ) {
				if ( $this->app_common_data['version'] < $apk_manage_last['version_num'] ) {
					//$this->myApiPrint($apk_manage_last['version_num'], 700, $apk_manage_last['content']);
					$this->myApiPrint( '新版上线,如无法安装，请卸载' . C( 'APP_TITLE' ) . '重新下载', 700, c( 'APP_IOS_URL' ) );
				}
			}

		}
		if ( $this->app_common_data['registration_id'] != '' && intval( $this->app_common_data['uid'] ) > 0 ) {
			$up8['registration_id'] = $this->app_common_data['registration_id'];
			$up8['last_updated']    = time();
			$up8['version']         = $this->app_common_data['version'];
			M( 'login' )->where( 'uid=' . $this->app_common_data['uid'] . ' and sessionid=\'' . $this->app_common_data['sessionid'] . '\'' )->save( $up8 );
		}

		//自动缓存读取
		$this->cacheAutoRead();


	}

	/**
	 * 统一权限验证,避免非权限操作接口
	 */
	protected function purviewCheck() {

		//匹配无需权限验证配置
		$is_must_check       = true;
		$api_no_purviewcheck = C( 'API_NO_PURVIEWCHECK' );
		foreach ( $api_no_purviewcheck as $k => $v ) {
			if ( preg_match( '/' . addcslashes( $v, '/' ) . '/', $_SERVER['REQUEST_URI'] ) ) {
				$is_must_check = false;
				break;
			}
		}

		if ( $is_must_check ) {
			/*------------过滤不需要签名的操作------------*/
			$filters  = array(
				'Company/company',
				'Hack/service_center',
				'Shop/shop_apply',
				'Shop/myStoreSet_save',
				'Store/modify_header',
				'Member/modify_header',
				'Shop/shake_save',
				'Shop/product_save',
				'Shop/product_update',
				'UploadCom/upload',
				'Hack/vip_apply',
				'Member/save_member_weixin',
				'Member/authentication',
				'Im/getUserInfo',
				'Im/checkWord',
				'Im/pushAlertToTarget',
				'Im/msgTransf',
				'Im/getRobotResponseMsg',
				'Im/getRobotResponseMsgDetail',
			);
			$usetoken = true;
			if ( in_array( CONTROLLER_NAME . '/' . ACTION_NAME, $filters ) ) {
				$usetoken = false;
			}

			//通用api_token验证
			$api_token_status = C( 'API_TOKEN' );
			if ( $api_token_status && $usetoken ) {

				// 签名验证
				// 签名认证只对，需要API_TOKEN验证的接口有效 
				$this->verifyAppSign();

			}

			//检测当前站点是否处于已关闭处于维护模式,若是则停止一切APP功能
			$Parameter = M( 'Parameter', 'g_' );
			$is_close  = $Parameter->field( 'is_close,close_msg' )->where( 'id=1' )->find();
			if ( $is_close && $is_close['is_close'] == 1 ) {
				if ( $this->app_common_data['uid'] != 2322 ) {
					//产品详情页面接口直接显示提示信息
					if (MODULE_NAME == 'APP' && CONTROLLER_NAME == 'Product' && ACTION_NAME == 'showDetail') {
						die($is_close['close_msg']);
					} else {
						$this->myApiPrint( $is_close['close_msg'], 600, $this->myapiprint_no_result );
					}
				}
			}

			//账户状态验证：用户信息和其登录信息
			if ( ! empty( $this->app_common_data['uid'] ) ) {
				$user_info  = M( 'Member' )->where( 'id=' . $this->app_common_data['uid'] )->field( 'is_lock' )->find();
				$login_info = M( 'Login' )->where( 'uid=' . $this->app_common_data['uid'] )->field( 'id' )->find();

				if ( ! $login_info ) {
					$this->myApiPrint( "当前账户出现异常,请重新登录", 500, $this->myapiprint_no_result );
				}
				if ( ! $user_info ) {
					$this->myApiPrint( "当前账户已不存在,请重新登录", 500, $this->myapiprint_no_result );
				}
				if ( $user_info['is_lock'] == 1 ) {
					$this->myApiPrint( '您的账号已被锁定，请联系您的营运部', 600, $this->myapiprint_no_result );
				}
			}

			//单点登录验证
			$app_single_login = C( 'APP_SINGLE_LOGIN' );
			if ( $app_single_login ) {
				//特殊帐号处理
				//if ($app_single_login || $this->app_common_data['uid']=='3996') {
				$map_login['uid'] = array( 'eq', $this->app_common_data['uid'] );
				$login_info       = M( 'Login' )->where( $map_login )->field( 'sessionid' )->order( 'last_updated desc,id desc' )->find();
				if ( ! $login_info ) {
					$this->appLoginout();
					$this->myApiPrint( '登录异常,请重新登录', 500 );
				}
				if ( $login_info['sessionid'] != $this->app_common_data['sessionid'] ) {
					$this->appLoginout();
					$this->myApiPrint( '该帐号已于异地登录,请重新登录', 500 );
				}
			}
		}
	}

	/**
	 * 设置通用返回信息数组
	 */
	public function setReturn( $code = '', $msg = '', $result = '' ) {
		! empty( $code ) && $this->return['code'] = $code;
		! empty( $msg ) && $this->return['msg'] = $msg;
		! empty( $result ) && $this->return['result'] = $result;

		$this->returnJSON( $this->return );
	}

	/**
	 * 通用返回信息JSON封装
	 *
	 * @param array $return 待返回数组信息
	 *
	 * @return json
	 */
	public function returnJSON( $return ) {
		echo json_encode( $return );
		exit;
	}

	/**
	 * 短信接口
	 *
	 * @param int $telphone
	 * @param string $sms_name 选用的短信接口名称
	 * @param string $content 短信内容
	 * @param string $template 短信模板(非通用,视需调用)
	 */
	protected function sms( $telphone, $sms_name = '', $content = '', $template = 'yanzhengma' ) {
		$sms_config = C( 'SMS_CONFIG' );

		//判断是否有默认使用短信类型
		if ( empty( $sms_name ) ) {
			if ( C( 'SMS_USE_DEFAULT' ) ) {
				$sms_name = C( 'SMS_USE_DEFAULT' );
			} else {
				$sms_name = 'CHUANGXIN';
			}
		}

		//对单个或多个手机号进行正则验证
		$telphone_arr = strpos( $telphone, ',' ) ? explode( ',', $telphone ) : array( $telphone );
		foreach ( $telphone_arr as $tel ) {
			if ( ! validateExtend( $tel, 'MOBILE' ) ) {
				return getReturn( '手机号码格式有误' );
			}
		}
		if ( ! isset( $sms_config[ $sms_name ] ) ) {
			return getReturn( '无对应短信接口' );
		}

		//短信通用开头标识
		$content_prefix = "【{$sms_config[$sms_name]['sign']}】";

		//生成唯一六位数数字短信验证码
		$unique_id = getMd5( $telphone );
		$unique_id = substr( $unique_id, 0, 6 );
		$unique_id = preg_replace( '/[a-zA-Z]{1}/', rand( 1, 9 ), $unique_id );

		//组装短信内容
		$unique_id               = empty( $content ) ? $unique_id : $content;
		$sms_content             = empty( $content ) ? $content_prefix . '验证码：' . $unique_id : $content_prefix . $content;
		$_SESSION['contentcode'] = $unique_id;
		switch ( $sms_name ) {
			case 'CHUANGXIN':
				$sms_var  = $sms_config[ $sms_name ];
				$sms_push = $sms_var['url'];
				unset( $sms_var['url'] );
				$sms_var['mobile']  = $telphone;
				$sms_var['content'] = $sms_content;
				$this->curl( $sms_push, 'post', $sms_var );

				return getReturn( '', $unique_id );
				break;
			case 'ALI':
				$res = $this->aliSms2( $telphone, $unique_id, $template );
				if ( $res->Code != 'OK' ) {
					$this->myApiPrint( '短信发送失败:' . $res->Message );
					exit;
				} else {
					$res->Message = '';
				}

				return getReturn( $res->Message, $unique_id );
				break;
		}
	}

	/**
	 * 阿里短信接口【老接口，适合ZCSH】
	 *
	 * @param $telphone string 手机号码
	 * @param $unique_id 验证码
	 * @param $template 短信模板
	 */
	private function aliSms( $telphone, $unique_id, $template ) {
		Vendor( "AliSms.aliyun-php-sdk-core.Config" );

		$config = C( 'SMS_CONFIG.ALI' );

		if ( ! isset( $config['template'][ $template ] ) ) {
			return getReturn( '未识别的短信模板类型' );
		}
		$template = $config['template'][ $template ];

		$iClientProfile = \DefaultProfile::getProfile( "cn-hangzhou", $config['key'], $config['secret'] );
		$client         = new \DefaultAcsClient( $iClientProfile );
		$request        = new Sms\SingleSendSmsRequest();
		$request->setSignName( $config['sign'] );
		$request->setTemplateCode( $template );
		$request->setRecNum( $telphone );

		$data            = array();
		$data['code']    = $unique_id;
		$data['product'] = $config['sign'];
		$request->setParamString( json_encode( $data ) );
		try {
			$response = $client->getAcsResponse( $request );
			//return getReturn('', $unique_id);
		} catch ( \ClientException $e ) {
			//return getReturn('['.$e->getErrorCode().']:'.$e->getErrorMessage());
		} catch ( \ServerException $e ) {
			//return getReturn('['.$e->getErrorCode().']:'.$e->getErrorMessage());
		}
	}

	/**
	 * 阿里短信接口【新接口，适合新申请的，如FRB】
	 *
	 * @param $telphone string 手机号码
	 * @param $unique_id 验证码
	 * @param $template 短信模板
	 */
	private function aliSms2( $telphone, $unique_id, $template ) {
		Vendor( "Aliyun.init" );

		$config = C( 'SMS_CONFIG.ALI' );

		if ( ! isset( $config['template'][ $template ] ) ) {
			return getReturn( '未识别的短信模板类型' );
		}

		$data         = array();
		$data['code'] = $unique_id;

		//验证码不能传product参数
		$vcode_template = [ 'yanzhengma', 'login_submit', 'login_warning' ];
		if ( ! in_array( $template, $vcode_template ) ) {
			$data['product'] = $config['sign'];
		}

		$template = $config['template'][ $template ];

		$Sms = new \AliyunSms( $config['key'], $config['secret'] );

		return $Sms->sendSms( $config['sign'], $template, $telphone, $data );
	}

	//v1 function

	/**
	 * 公共返回函数
	 *
	 * @param $msg 需要打印的错误信息
	 * @param $code 默认打印300信息(300:fial,400:success)
	 */
	public function myApiPrint( $msg = '', $code = 300, $data = '' ) {
		if ( $this->app_common_data['uid'] != '' ) {
			$unique_sessionkey = CONTROLLER_NAME . '_' . ACTION_NAME . '_' . $this->app_common_data['uid'];
			session( $unique_sessionkey, null );
		}

		$result = array();

		//对data中的附件地址进行统一自动添加附件头域名(便于实现附件分离)
		if ( C( 'ATTACH_DOMAIN_ENABLE' ) ) {
			if ( ! empty( $data ) && is_array( $data ) ) {
				//随机获取一个附件头域名
				$attach_domain_key = array_rand( C( 'DEVICE_CONFIG' )[ C( 'DEVICE_CONFIG_DEFAULT' ) ]['attach_domain'], 1 );
				$attach_domain     = C( 'DEVICE_CONFIG' )[ C( 'DEVICE_CONFIG_DEFAULT' ) ]['attach_domain'][ $attach_domain_key ];

				//对任一接口返回的含有Uploads字符串的并且不带http头的内容进行附加头域名
				$data = json_encode( $data, JSON_UNESCAPED_SLASHES );
				$data = preg_replace( '/(\"){1}(\.)?(\/)?Uploads\//', '"' . $attach_domain . '/Uploads/', $data );
				$data = preg_replace( '/(\,){1}(\.)?(\/)?Uploads\//', ',' . $attach_domain . '/Uploads/', $data );

				//对任一接口返回的含有Uploads字符串的并且带http头但http域名部分与附件分离的头域名不同的内容进行附加头域名
				$data = preg_replace( '/http:\/\/([A-Za-z0-9.:]+)\/Uploads\//', $attach_domain . '/Uploads/', $data );
				
				$data = json_decode( $data, true );
			}
		}
		
		//转译
		$accept_language = getCurrentLang();
		if ($accept_language != 'zh-cn') {
			$lang_package = include ($_SERVER['DOCUMENT_ROOT'].'/Appcenter/APP/Lang/'.$accept_language.'.php');
			$data = json_encode( $data, JSON_UNESCAPED_SLASHES );
			$data = decodeUnicode($data);
			foreach ($lang_package as $k=>$v) {
				$data = preg_replace("/{$k}/", $v, $data);
				$msg = preg_replace("/{$k}/", $v, $msg);
			}
			$data = json_decode( $data, true );
		}

		if ( $data != $this->myapiprint_no_result ) {
			if ( $data == '' ) {
				$result['result'] = (object) array();
			} else {
				$result['result'] = $data;
			}
		}

		$result['code'] = $code;
		$result['msg']  = $msg;

		//自动缓存写入
		if ( $result['code'] == '400' && ! empty( $result['result'] ) ) {
			$this->cacheAutoWrite( json_encode( $result ) );
		}

		$this->ajaxReturn( $result );
		exit;
	}

	/**
	 * 生成APP签名
	 * 签名生成规则：
	 * 1. 将所有POST发送参数集合内非空参数值的参数按照参数名ASCII码从小到大排序（字典序），使用URL键值对的格式（即key1=value1&key2=value2…）拼接成字符串stringA
	 * 2. 在stringA最后拼接上key=(APP_CONFIG.SECRET_KEY)得到stringSignTemp字符串，并对stringSignTemp进行MD5运算，再将得到的字符串所有字符转换为大写，得到sign值signValue
	 * 重要规则：
	 *    1>. 参数名ASCII码从小到大排序（字典序）
	 *    2>. 如果参数的值为空不参与签名
	 *    3>. 参数名区分大小写
	 *    4>. 传送的app_common_data参数不参与签名
	 */
	public function makeAppSign() {
		$params = array_filter( $this->values );
		unset( $params['app_common_data'] );
		ksort( $params );
		$str = "";
		foreach ( $params as $k => $v ) {
			$str .= $k . "=" . $v . "&";
		}
		
		$str = empty($str) ? '&' : $str; //兼容APP端生成签名方式
		
		$str       .= "key=" . C( 'APP_CONFIG.SECRET_KEY' );
		$signValue = strtoupper( MD5( $str ) );

		return $signValue;
	}

	/**
	 * 测试签名专用
	 * @return string
	 */
	public function testMakeAppSign() {
		$params = array_filter( $this->values );
		unset( $params['app_common_data'] );
		ksort( $params );
		$str = "";
		foreach ( $params as $k => $v ) {
			$str .= $k . "=" . $v . "&";
		}
		$str .= "key=" . C( 'APP_CONFIG.SECRET_KEY' );

		return $str;
	}

	/**
	 * 验证APP签名
	 */
	public function verifyAppSign() {

		if ( ! IS_POST ) {
			return true;
		}
		$sign = $this->app_common_data['sign'];
		if ( ! $sign || $sign == '' ) {
			$this->myApiPrint( '未发送签名信息', 300 );
		}
		if ( $this->makeAppSign() != $sign ) {
//            echo $this->makeAppSign();
			$test = array();
			//$test['paramstr'] = $this->testMakeAppSign();
			//$test['sign'] = $this->makeAppSign();
			$this->myApiPrint( '无操作权限:签名异常', 300, $test );
		}

		return true;
	}

	/**
	 * 用户退出登录
	 */
	public function appLoginout() {
		if ( ! empty( $this->app_common_data['uid'] ) && ! empty( $this->app_common_data['sessionid'] ) ) {
			$map_login['uid']       = array( 'eq', $this->app_common_data['uid'] );
			$map_login['sessionid'] = array( 'eq', $this->app_common_data['sessionid'] );
			$login_info             = M( 'Login' )->where( $map_login )->field( 'id' )->find();
			if ( $login_info ) {
				if ( M( 'Login' )->where( 'id=' . $login_info['id'] )->delete() === false ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * 计算买家应得积分、商家应得积分、平台应得毛利润的封装
	 *
	 * @param $money 兑换产生的毛利润金额
	 */
	public function getPointsToMM( $money = 0 ) {
		$Parameter = M( 'Parameter', 'g_' );

		$data = array( 'merchant' => 0, 'member' => 0, 'profits' => $money );

		if ( empty( $money ) ) {
			return $data;
		}
		if ( ! validateExtend( $money, 'MONEY' ) ) {
			return $data;
		}

		//拉取商家和买家应得积分的配置参数
		$parameter_info = $Parameter->where( 'id=1' )->field( 'points_merchant,points_member' )->find();

		$data['merchant'] = $money * $parameter_info['points_merchant'];
		$data['member']   = $money * $parameter_info['points_member'];

		return $data;
	}

	/**
	 * record日志记录封装
	 *
	 * @param $log_folder string 存放日志的文件夹名(相对于record目录)
	 * @param $log_content string 日志内容(以追加的形式存入)
	 */
	public function recordLogWrite( $log_folder, $log_content ) {
		$time_str = date( 'Y' ) . '_' . date( 'm' ) . '_' . date( 'd' );
		$log_file = $_SERVER['DOCUMENT_ROOT'] . '/record/' . $log_folder . '/' . $time_str . '.log.php';

		//第一次生成文件时,执行特殊的写入
		if ( ! file_exists( $log_file ) ) {
			$content = "<?php\n exit; \n ?> \n" . $log_content;
		} else {
			$content = $log_content;
		}

		//写入日志
		$dir = dirname( $log_file );
		if ( ! is_dir( $dir ) ) {
			mkdir( $dir, 0777, true );
		}
		file_put_contents( $log_file, $content, FILE_APPEND );
	}

	/**
	 * 自动缓存读取
	 */
	public function cacheAutoRead() {
		$db_cache_on        = C( 'DATA_CACHE_ON' );
		$db_cache_auto_path = C( 'DATA_CACHE_AUTO_PATH' );

		if ( $db_cache_on && ! empty( $db_cache_auto_path ) ) {
			foreach ( $db_cache_auto_path as $k => $v ) {
				if ( preg_match( '/' . addcslashes( $v, '/' ) . '/', $_SERVER['REQUEST_URI'] ) ) {
					if ( S( $this->cache_name ) ) {
						if ( is_array( S( $this->cache_name ) ) ) {
							S( $this->cache_name, null );
						} else {
							$cache_content = json_decode( S( $this->cache_name ), true );
							$this->ajaxReturn( $cache_content );
							exit;
						}
					}
				}
			}
		}
	}

	/**
	 * 自动缓存写入
	 *
	 * @param $value 缓存值
	 */
	public function cacheAutoWrite( $value ) {
		$db_cache_on        = C( 'DATA_CACHE_ON' );
		$db_cache_auto_path = C( 'DATA_CACHE_AUTO_PATH' );

		if ( $db_cache_on && ! empty( $db_cache_auto_path ) ) {

			foreach ( $db_cache_auto_path as $k => $v ) {
				if ( preg_match( '/' . addcslashes( $v, '/' ) . '/', $_SERVER['REQUEST_URI'] ) ) {
					S( $this->cache_name, $value );
				}
			}
		}
	}

	/**
	 * 针对ios特殊处理商品显示
	 *
	 * @param unknown $coin
	 *
	 * @return string|unknown
	 */
	public function iosexp( $coin ) {
		if ( $this->app_common_data['platform'] == 'ios' ) {
			if ( str_replace( '.', '', $this->app_common_data['version'] ) * 1 < 502 ) {
				return '';
			} else {
				return $coin;
			}
		}

		return $coin;
	}

}

?>