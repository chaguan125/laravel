<?php

namespace App\Librarys;

use EasyWeChat\Kernel\AccessToken;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use App\Models\Tenant\Payment;
use App\Models\CentralPayment;
use Zzbzh\Wxpay\PaymentV3\Application;
use Illuminate\Support\Facades\Log;

class EasyWeChat
{
    const PATTERN = ['direct'=>'直连模式','service'=>'服务商模式'];

    private static $instance = [];

    /**
     * 获取公众号app
     *
     * @return \EasyWeChat\OfficialAccount\Application
     */
    public static function officialAccount($name = 'default')
    {
        $app = self::_make('OfficialAccount', 'wechat.official_account.' . $name);
        $app['market_code'] = function ($app) {
            return new MarketCodeClient($app);
        };
        //特殊处理央服公众号的access_token
        if (isset($app['config']['app_id']) && $app['config']['app_id'] == 'wx955be0205194649c') {
            $app['access_token'] = function ($app) {
                return new class ($app) extends AccessToken {
                    /**
                     * @var string
                     */
                    protected $endpointToGetToken = 'https://token.ficent.com/TokenValidate/api/getaccesstoken?appid=';

                    /**
                     * @return array
                     */
                    protected function getCredentials(): array
                    {
                        return [
                            'grant_type' => 'client_credential',
                            'appid' => $this->app['config']['app_id'],
                            'secret' => $this->app['config']['secret'],
                        ];
                    }

                    public function requestToken(array $credentials, $toArray = false)
                    {
                        $response = $this->app['http_client']->get($this->endpointToGetToken . $this->app['config']['app_id'], [
                            'headers' => [
                                'X-Access-Token' => '9d2f2aa4-db7a-11ea-b02d-94292f7b22c6',
                            ]
                        ]);
                        $res = $response->getBody()->getContents();
                        $result = json_decode($res, true);
                        $result['expires_in'] += 3;
                        $formatted = $this->castResponseToType($response, $this->app['config']->get('response_type'));
                        if (empty($result[$this->tokenKey])) {
                            throw new \EasyWeChat\Kernel\Exceptions\HttpException('Request access_token fail: ' . $res, $response, $formatted);
                        }
                        return $toArray ? $result : $formatted;
                    }
                };
            };
        }
        return $app;
    }

    /**
     * 获取小程序app
     *
     * @return \EasyWeChat\MiniProgram\Application
     */
    public static function miniProgram($name = 'default')
    {
        return self::_make('MiniProgram', 'wechat.mini_program.' . $name);
    }

    /**
     * 获取微信支付app
     *
     * @return \EasyWeChat\Payment\Application
     */
    public static function payment($name = 'default')
    {
        return self::_make('Payment', 'wechat.payment.' . $name);
    }

    /**
     * 获取企业微信app
     *
     * @return \EasyWeChat\Work\Application
     */
    public static function work($name = 'default')
    {
        return self::_make('Work', 'wechat.work.' . $name);
    }

    private static function _make($path, $config, $type = 'default')
    {
        if (!isset(self::$instance[$path.'_'.$type])) {
            $application = "\\EasyWeChat\\{$path}\\Application";
            switch ($path) {
                case 'Payment':
                    if ($type == 'default') {
                        $config = Payment::payV3Config();
                    } else {
                        $config = CentralPayment::payV3Config();
                    }
                    $config_res = $config + config('wechat.defaults');
                    break;
                case 'OfficialAccount':
                    if ($type == 'default') {
                        $config = Payment::officialConfig();
                    } else {
                        $config = CentralPayment::officialConfig();
                    }
                    $config_res = $config + config('wechat.defaults');
                    break;
                default:
                    $config_res = config($config) + config('wechat.defaults');
            }
            $app = new $application($config_res);

            $redis = Redis::connection('wechat')->client();
            $cache = new RedisAdapter($redis);
            $app->rebind('cache', $cache);

            $app->rebind('request', app('request'));
            self::$instance[$path.'_'.$type] = $app;
        }
        return self::$instance[$path.'_'.$type];
    }

    /**
     * 获取v3微信支付app default 子库 central 主库
     * @return Application
     */
    public static function v3Pay($type = 'default')
    {
        if (!isset(self::$instance['v3Pay'.'_'.$type])) {
            switch ($type) {
                case 'default':
                    $config_res = Payment::payV3Config();
                    break;
                case 'central':
                    $config_res = CentralPayment::payV3Config();
                    break;
                default:
                    $config_res = [];
            }
            $config_res = $config_res + config('wechat.defaults');
//            Log::info('博饼配置信息',['config'=>$config_res]);
            $app = new Application($config_res);
            $redis = Redis::connection('wechat')->client();
            $cache = new RedisAdapter($redis);
            $app->rebind('cache', $cache);
            $app->rebind('request', app('request'));
            self::$instance['v3Pay'.'_'.$type] = $app;
        }
        return self::$instance['v3Pay'.'_'.$type];
    }

    /**
     * 获取v3微信支付app default 子库 central 主库 不使用单例模式
     * @return Application
     */
    public static function v3PayNew($type = 'default')
    {
        switch ($type) {
            case 'default':
                $config_res = Payment::payV3Config();
                break;
            case 'central':
                $config_res = CentralPayment::payV3Config();
                break;
            default:
                $config_res = [];
        }
        $config_res = $config_res + config('wechat.defaults');
        $app = new Application($config_res);
        $redis = Redis::connection('wechat')->client();
        $cache = new RedisAdapter($redis);
        $app->rebind('cache', $cache);
        $app->rebind('request', app('request'));
        return $app;
    }


    //获取小程序发送红包的配置信息 default 子库 central 主库
    public static function miniRedPay($type = 'default')
    {
        if (!isset(self::$instance['miniRedPay'.'_'.$type])) {
            $application = "\\EasyWeChat\\Payment\\Application";
            switch ($type) {
                case 'default':
                    $config_res = Payment::redMiniConfig();
                    break;
                case 'central':
                    $config_res = CentralPayment::redMiniConfig();
                    break;
                default:
                    $config_res = [];
            }
            $config_res = $config_res + config('wechat.defaults');
            $app = new $application($config_res);
            $redis = Redis::connection('wechat')->client();
            $cache = new RedisAdapter($redis);
            $app->rebind('cache', $cache);
            $app->rebind('request', app('request'));
            self::$instance['miniRedPay'.'_'.$type] = $app;
        }
        return self::$instance['miniRedPay'.'_'.$type];
    }

    //队列使用的非单例模式 获取小程序发送红包的配置信息 default 子库 central 主库
    public static function miniRedPayNew($type = 'default')
    {
        $application = "\\EasyWeChat\\Payment\\Application";
        switch ($type) {
            case 'default':
                $config_res = Payment::redMiniConfig();
                break;
            case 'central':
                $config_res = CentralPayment::redMiniConfig();
                break;
            default:
                $config_res = [];
        }
        $config_res = $config_res + config('wechat.defaults');
        $app = new $application($config_res);
        $redis = Redis::connection('wechat')->client();
        $cache = new RedisAdapter($redis);
        $app->rebind('cache', $cache);
        $app->rebind('request', app('request'));
        return $app;
    }

    /**
     * 根据代金券商户号获取商户配置信息
     * @param $mchid
     * @param string $type
     * @return Application
     */
    public static function v3PayByMchid($mchid,$type = 'default'){
        if($mchid == config('wechat.service_provider.mch_id')){
            $config_res = config('wechat.service_provider');
        }else{
            switch ($type) {
                case 'default':
                    $config_res = Payment::payV3ConfigByMchid($mchid);
                    break;
                case 'central':
                    $config_res = CentralPayment::payV3ConfigMchid($mchid);
                    break;
                default:
                    $config_res = [];
            }
        }

        $config_res = $config_res + config('wechat.defaults');
        $app = new Application($config_res);
        $redis = Redis::connection('wechat')->client();
        $cache = new RedisAdapter($redis);
        $app->rebind('cache', $cache);
        $app->rebind('request', app('request'));
        return $app;
    }
}
