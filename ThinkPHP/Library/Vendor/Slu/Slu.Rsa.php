<?php

class SluRsa
{
    private $privateKey = '';
    private $publicKey = '';

    public function __construct()
    {
        $this->publicKey = openssl_pkey_get_public(SluConfig::RSAPUBLIKEY);
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

}
