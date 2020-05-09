<?php
require_once "CgbPay.Exception.php";
require_once "CgbPay.Config.php";
require_once "CgbPay.Core.php";

/**
 * 接口访问类
 */
class CgbPayApi
{
	private $cgbPayCore;
	private $postUrl; //发送数据至接口的地址
	
	public function __construct() {
		$this->cgbPayCore = new CgbPayCore();
		
		$this->postUrl = 'http://'.CgbPayConfig::apiIp.'/CGBClient/BankAction';
	}
	
	/**
	 * 获取封装好的交易xml数据
	 * @param array $data (必须包含交易码tranCode字段)
	 * @return xml
	 * @throws CgbPayException
	 */
	public function getXmlData($data=false) {
		if (!$data || !is_array($data)) {
			throw new CgbPayException("参数有误");
		}
		
		//检测交易代码是否合法,并设置公共头交易码
		if (!isset($data['tranCode']) || !in_array($data['tranCode'], $this->cgbPayCore->tranCodeAllow)) {
			throw new CgbPayException("交易码异常");
		} else {
			$this->cgbPayCore->SetHeaderData('tranCode', $data['tranCode']);
			unset($data['tranCode']);
		}
		
		//交易时公共头中必须有entSeqNo(企业财务系统流水号)
		if (!isset($data['entSeqNo'])) {
			throw new CgbPayException("企业交易上报公共头中必须含有企业财务系统流水号entSeqNo");
		} else {
			$this->cgbPayCore->SetHeaderData('entSeqNo', $data['entSeqNo']);
			unset($data['entSeqNo']);
		}
		
		$this->cgbPayCore->FromArray($data);
		
		return $this->cgbPayCore->ToXml();
	}
	
	/**
	 * 获取封装好的由返回xml数据转换的数组数据
	 * @param xml $xml
	 * @return array
	 */
	public function getArrayData($xml=false) {
		if (!$xml) {
			throw new CgbPayException("xml数据异常");
		}
		
		return $this->cgbPayCore->FromXml($xml);
	}
	
	/**
	 * 以post方式提交xml到对应的接口url
	 * @param string $xml  需要post的xml数据
	 * @param string $url  url
	 * @param bool $useCert 是否需要证书，默认不需要
	 * @param int $second   url执行超时时间，默认30s
	 * @throws CgbPayException
	 */
	public function postXmlCurl($xml, $url='', $useCert = false, $second = 30)
	{
		$url = $this->postUrl;
		
		$ch = curl_init();
		
		curl_setopt($ch,CURLOPT_URL, $url);
	
		//设置超时
		curl_setopt($ch, CURLOPT_TIMEOUT, $second);
	
		//设置header
		curl_setopt($ch, CURLOPT_HEADER, false);
	
		//要求结果为字符串且输出到屏幕上
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	
		//post提交方式
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	
		//运行curl
		$data = curl_exec($ch);
	
		//返回结果
		if ($data) {
			curl_close($ch);
			return $data;
		} else {
			$error = curl_errno($ch);
			curl_close($ch);
			throw new CgbPayException("curl出错，错误码:$error");
		}
	}
	
}

