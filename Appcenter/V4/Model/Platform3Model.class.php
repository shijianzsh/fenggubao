<?php
namespace V4\Model;


/**
 * 给第三方平台对接的
 * Enter description here ...
 * @author Administrator
 *
 */
class Platform3Model{
    
    /**
     * 签名加密的key
     * Enter description here ...
     * @var unknown_type
     */
    const KEY = 'D36F737629C8A32249CDB81C069FE9FC';
    
    
    /**
     * 公益平台接口地址前缀
     * Enter description here ...
     * @var unknown_type
     */
    //const ZCGYURL = 'http://cs.zcsh333.com/Account/recevied';  //*测试
    //const ZCGYURL = 'http://192.151.231.14/Account/recevied';    //正式
    
    //const ZCGY_IP = 'cs.zcsh333.com'; //*测试
    //const ZCGY_IP = '192.151.231.14';   //正式
    
    private $values = array();
    
    /**
     * 设置参数
     * Enter description here ...
     * @param array $params 数组
     */
    public function setValues($params){
        $this->values = $params;
    }
    
    /**
     * 获取参数数组
     * Enter description here ...
     */
    public function getValues(){
        return $this->values;
    }
    
    /**
     * 格式化参数格式化成url参数
     */
    private function ToUrlParams()
    {
        $buff = "";
        foreach ($this->values as $k => $v)
        {
            if($k != "sign" && $v != "" && !is_array($v)){
                $buff .= $k . "=" . $v . "&";
            }
        }
        
        $buff = trim($buff, "&");
        return $buff;
    }
    
    /**
    * 设置签名，详见签名生成算法
    * @param string $value 
    **/
    public function SetSign()
    {
        $sign = $this->MakeSign();
        $this->values['sign'] = $sign;
        return $sign;
    }
    
    /**
     * 生成签名
     * @return 签名，本函数不覆盖sign成员变量，如要设置签名需要调用SetSign方法赋值
     */
    public function MakeSign()
    {
        //签名步骤一：按字典序排序参数
        ksort($this->values);
        $string = $this->ToUrlParams();
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=".Platform3Model::KEY;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }
    
    /**
     * 获取执行post的 参数格式
     */
    public function getUrlParams()
    {
        $buff = "";
        foreach ($this->values as $k => $v)
        {
            if($v != "" && !is_array($v)){
                $buff .= $k . "=" . $v . "&";
            }
        }
        
        $buff = trim($buff, "&");
        return $buff;
    }
    
    
}