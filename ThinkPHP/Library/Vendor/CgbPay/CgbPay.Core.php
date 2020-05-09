<?php
/**
 * 广发银企直联 核心库
 */

require_once "CgbPay.Config.php";
require_once "CgbPay.Exception.php";

/**
 * 数据对象基础类，该类中定义数据类最基本的行为，包括：
 * 计算/设置/获取签名、输出xml格式的参数、从xml读取数据对象等
 */
class CgbPayCore {
	
	protected $values = array();
	protected $header = array();
	public $tranCodeAllow = array();
	
	public function __construct() {
		//初始化公共头数据
		$this->header = array(
			'tranCode' => '',                         //交易码
			'cifMaster' => CgbPayConfig::cifMaster,   //客户号
			'entSeqNo' => '',                         //企业财务系统流水号(交易时必填)
			'tranDate' => date('Ymd'),                //日期
			'tranTime' => date('His'),                //时间
			'retCode' => '000',                       //返回码
			'entUserId' => CgbPayConfig::entUserId,   //操作员
			'password' => CgbPayConfig::password,     //操作密码
		);
		
		//配置允许的交易码
		$this->tranCodeAllow = strpos(CgbPayConfig::tranCode,',') ? explode(',', CgbPayConfig::tranCode) : array(CgbPayConfig::tranCode);
	}
	
	/**
	 * 设置公共头参数
	 * @param string $key
	 * @param string $value
	 */
	public function SetHeaderData($key, $value) {
		$this->header[$key] = $value;
	}
	
	/**
	 * 使用数组初始化
	 * @param array $array
	 */
	public function FromArray($array) {
		$this->values = $array;
	}
	
	/**
	 * 设置参数
	 * @param string $key
	 * @param string $value
	 */
	public function SetData($key, $value) {
		$this->values[$key] = $value;
	}
	
	/**
	 * 输出xml字符
	 * @throws CgbPayException
	 */
	public function ToXml() {
		if (!is_array($this->values) || count($this->values) <= 0) {
    		throw new CgbPayException("数组数据异常！");
    	}
    	
    	//检测entSeqNo是否为空,为空则默认为非交易上报,并移除entSeqNo
    	if (empty($this->header['entSeqNo'])) {
    		unset($this->header['entSeqNo']);
    	}
    	
    	$xml = "<BEDC><Message>";
    	
    	//公共头拼装
    	$xml .= "<commHead>";
    	foreach ($this->header as $key=>$val) {
    		//$xml .= "<{$key}><![CDATA[{$val}]]></{$key}>";
    		$xml .= "<{$key}>{$val}</{$key}>";
    	}
    	$xml .= "</commHead>";
    	
    	//数据拼装
    	$xml .= "<Body>";
    	foreach ($this->values as $key=>$val) {
    		//$xml .= "<{$key}><![CDATA[{$val}]]></{$key}>";
    		$xml .= "<{$key}>{$val}</{$key}>";
        }
        $xml .= "</Body>";
        
        $xml .= "</Message></BEDC>";
        
        //封装为xml格式
        $xml = <<<XML
    			<?xml version="1.0" encoding="GBK"?>
        		{$xml}  
XML;
    	
    	/***此种方式xml标记之间有空格
    	$xml = new DOMDocument('1.0', 'GBK');
    	$xml->formatOutput = true;
    	
    	$bedc = $xml->createElement('BEDC');
    	$xml->appendChild($bedc);
    	$message = $xml->createElement('Message');
    	$bedc->appendChild($message);
    	
    	$commhead = $xml->createElement('commHead');
    	$message->appendChild($commhead);
    	foreach ($this->header as $key=>$val) {
    		$current = $xml->createElement($key, $val);
    		$commhead->appendChild($current);
    	}
    	
    	$body = $xml->createElement('Body');
    	$message->appendChild($body);
    	foreach ($this->values as $key=>$val) {
    		$current = $xml->createElement($key, $val);
    		$body->appendChild($current);
    	}
    	
    	$xml = $xml->saveXML();
    	***/
        		
        $xml = "cgb_data=".$xml;
        //$xml = iconv('utf-8', 'gb2312', $xml);
        $xml = mb_convert_encoding($xml, 'gbk', 'utf-8');
        
        return $xml;
	}
	
    /**
     * 将xml转为array
     * @param string $xml
     * @throws CgbPayException
     */
	public function FromXml($xml) {	
		if (!$xml) {
			throw new CgbPayException("xml数据异常！");
		}
		
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        
        //重新封装数组数据
        $data_head = array(
        	'retCode' => $data['Message']['commHead']['retCode'],
        );
        $this->values = array_merge($data_head, $data['Message']['Body']);
        
		return $this->values;
	}
	
	/**
	 * 获取设置的值
	 */
	public function GetValues() {
		return $this->values;
	}
	
}