<?php

class ZhongWYRsa
{
    private $privateKey = '';
    private $publicKey = '';

    public function __construct()
    {
        $this->publicKey = openssl_pkey_get_public(ZhongWYConfig::RSAPUBLIKEY);
    }

    /**
     * 公钥加密
     * @param $data
     * @param $publicKey
     * @return mixed
     */
    public function publicEncrypt($data)
    {
        openssl_public_encrypt($data, $encrypted, $this->publicKey);//公钥加密
        $encrypted = base64_encode($encrypted);
        return $encrypted;
    }

    public function publicDecrypt($data)
    {
        openssl_public_decrypt(base64_decode($data), $decrypted, $this->publicKey);
        return $decrypted;
    }

//    public function privateEncrypt($data, $privateKey)
//    {
//        openssl_private_encrypt($data, $encrypted, $privateKey);
//        return $encrypted;
//    }
//
//    public function privateDecrypt($data, $privateKey)
//    {
//        openssl_private_decrypt($data, $decrypted, $privateKey);
//        return $decrypted;
//    }
}
