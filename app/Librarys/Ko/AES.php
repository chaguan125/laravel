<?php

namespace App\Librarys\Ko;

class AES
{
    private static $key;//加密解密秘钥
    private static $iv;//加密解密初始化向量

    public function __construct($type = 'P')
    {
        $key_info = config('ko_act')['user_info'][$type] ?? [];
        self::$key = $key_info['key'] ?? "";
        self::$iv = $key_info['iv'] ?? "'";
    }

    /**
     * AES 加密
     * @param string $str
     * @return string
     */
    public function str_encrypt($str)
    {
        return base64_encode(openssl_encrypt($str, 'AES-128-CBC', self::$key, OPENSSL_RAW_DATA, self::$iv));
    }

    /**
     * AES 解密
     * @param string $str
     * @return false|string
     */
    public function str_decrypt($str)
    {
        return openssl_decrypt(base64_decode($str), 'AES-128-CBC', self::$key, OPENSSL_RAW_DATA, self::$iv);
    }
}
