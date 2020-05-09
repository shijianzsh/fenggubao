<?php
/**
 * 支付宝  配置账号信息
 *
 */

class AliPayConfig
{
	const AppId = ''; //APPID:正式:2018010901714068,沙箱:2016091200493576
	const PId = '';   //合作者身份id
	//RSA2
	const RsaPrivateKey_FileName = 'private.pem'; //开发者私钥去头去尾去回车,一行字符串
	const RsaPublickKey_FileName = 'public.pem'; //支付宝公钥,一行字符串
	
	//const ApiVersion = '';
	const SignType = 'RSA2';
	const PostCharset = 'utf-8';
	const Format = 'json';
	const gateWay = 'https://openapi.alipay.com/gateway.do'; //正式:https://openapi.alipay.com/gateway.do,沙箱:https://openapi.alipaydev.com/gateway.do
}
