<?php
namespace App\Librarys\Ali;

use Alipay\EasySDK\Kernel\Config;

class CouponFacade {
    
    private static $instance;

    private function __construct()
    {

    }

    private function __clone()
    {
    }

    /**
     * 返回卡券代理类
     *
     * @return \App\Librarys\Ali\Coupon
     */
    public static function getInstance()
    {
        if (empty(self::$instance)) {
            $factory = Factory::setOptions(self::getOptions()); 
            self::$instance = $factory->coupon();
        }
        return self::$instance;
    }

    /**
     * 返回配置信息
     *
     * @return void
     */
    private static function getOptions()
    {
        $options = new Config();
        $options->protocol = 'https';
        $options->gatewayHost = 'openapi.alipay.com';
        $options->signType = 'RSA2';
        $options->appId = config('ali.appid');
        $options->merchantPrivateKey =  config('ali.private_key');
        $options->alipayPublicKey =  config('ali.public_key');
        $options->notifyUrl = config('ali.notify_url');
        $options->encryptKey = config('ali.encrypt_key');
        return $options;
    }
}