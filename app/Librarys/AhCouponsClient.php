<?php

namespace App\Librarys;

use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;

class AhCouponsClient
{
    private $secret_key;
    private $url;
    private $http;
    private $type;
    protected $re_try_time = 1; //重试次数
    public $username;
    public $password;

    public function __construct($type = 'P')
    {
        $user = config('ko_act')['user_info'][$type] ?? [];
        $this->username = $user['username'] ?? '';
        $this->password = $user['password'] ?? '';
        $this->secret_key = $user['secret_key'] ?? '';
        $this->type = $type;
        $handlerStack = HandlerStack::create(new CurlHandler());
        $handlerStack->push(Middleware::retry($this->retryDecider(), $this->retryDelay()));
        $this->http = new \GuzzleHttp\Client([
            'base_uri' => $user['url'] ?? '',
            'timeout' => 10,
            'http_errors' => false,
            'handler' => $handlerStack
            // 'debug' => fopen(storage_path('guzzleHttp.log'), 'a+')
        ]);
    }

    public function retryDecider()
    {

        return function (
            $retries,
            \GuzzleHttp\Psr7\Request $request,
            Response $response = null,
            $exception = null
        ) {
            if ($retries >= ($this->re_try_time)) {
                return false;
            }
            if ($exception instanceof ConnectException) {
                return true;
            }
            if ($exception instanceof RequestException) {
                return true;
            }
            if ($response) { // 记录日志
                $res = json_decode($response->getBody(), true);
                $response->getBody()->rewind(); //将流指针倒回开始位置
                $uri = $request->getUri()->getPath();
                $params = json_decode($request->getBody(), true);
                Log::info('ko+请求记录', [$uri, $params, $res]);
            }
            return false;
        };
    }

    public function retryDelay()
    {
        return function ($numberOfRetries) {
            return 500; //返回下次重试的时间（毫秒）
            //            return 1000 * $numberOfRetries;
        };
    }

    /**
     * 获取token
     */
    public function getToken($force = false)
    {
        $token = Cache::get('ko_coupon_token' . $this->type);
        if ($force || empty($token)) {
            $res = $this->http->post('/backend/open-api/v1/tokens/bearer', [
                'json' => [
                    'username' => $this->username,
                    'password' => $this->password
                ]
            ]);
            $contents = $res->getBody()->getContents();
            if ($res->getStatusCode() === 200) {
                $data = json_decode($contents, true);
                $token = $data['data']['token'];
                Cache::set('ko_coupon_token' . $this->type, $token, 60 * 115); // 缓存 115分钟
            } else {
                throw new \Exception($contents);
            }
        }
        return $token;
    }

    /**
     * 核销
     * @param string $couponWithSalt 券号
     * @param string $channel 核销渠道
     * @param string $transactionId 事务id，建议使用uuid生成，相同的事务id核 销同一张卡券能获得相同的结果
     * @return void
     */
    public function getCouponsRedeem(string $couponWithSalt, string $transactionId = NULL, string $channel = 'SWIRE_MT_FM',  $retry = false)
    {

        try {
            $this->url = '/coupon/open-api/v1/admin/coupons/redeem';
            $parame = [
                'couponWithSalt' => $couponWithSalt,
                'channel' => $channel,
                'transactionId' => $transactionId,
            ];
            //info('核销请求参数记录-' . json_encode($parame));
            $res = $this->http->post($this->url, [
                'headers' => [
                    'Authorization' => $this->getToken($retry),
                    'X-TW-SIGNATURE' => $this->getSing($parame)
                ],
                'json' => $parame
            ]);

            $statusCode = $res->getStatusCode();
            $contents = $res->getBody()->getContents();
            if ($statusCode == 200) {
                return ['status_code' => 200, 'message' => 'success'];
            } elseif ($statusCode == 401) { // 未授权
                $this->getCouponsRedeem($couponWithSalt, $channel, $transactionId, true);
            } else { // 其他异常响应：
                return ['status_code' => $statusCode, 'message' => $contents];
            }
        } catch (\Exception $e) {  // 报错 按照没有数据处理
            return ['status_code' => 500, 'message' => $e->getMessage()];
        }
    }

    /**
     * 批量核销
     *
     * @param array $couponWithSalt
     * @param array $transactionIds
     * @param string $channel
     * @param boolean $retry
     * @return void
     */
    public function CouponsRedeemBatch(array $couponWithSalts, array $transactionIds, $channel, $retry)
    {
        try {
            $this->url = '/coupon/open-api/v1/admin/coupons/redeem-batch';
            $parame = [
                'couponWithSalts' => $couponWithSalts,
                'channel' => $channel,
                'transactionId' => $transactionIds,
            ];
            info('核销请求参数记录-' . json_encode($parame));
            $res = $this->http->post($this->url, [
                'headers' => [
                    'Authorization' => $this->getToken($retry),
                    'X-TW-SIGNATURE' => $this->getSing($parame)
                ],
                'json' => $parame
            ]);

            $statusCode = $res->getStatusCode();
            $contents = $res->getBody()->getContents();
            if ($statusCode == 200) {
                return ['status_code' => 200, 'message' => 'success'];
            } elseif ($statusCode == 401) { // 未授权
                $this->CouponsRedeemBatch($couponWithSalts, $transactionIds, $channel, true);
            } else { // 其他异常响应：
                return ['status_code' => $statusCode, 'message' => $contents];
            }
        } catch (\Exception $e) {  // 报错 按照没有数据处理
            return ['status_code' => 500, 'message' => $e->getMessage()];
        }
    }

    /**
     * 商家卡券事件通知
     * @param string $couponWithSalt 券号
     * @param string $channel 核销渠道
     * @param string $type 类型 REDEEM
     * @param string $time 事件发生时间
     * @return void
     */
    public function CouponsEvent(string $couponWithSalt, string $time, string $channel = 'SWIRE_MT_FM', string $type = 'REDEEM',  $retry = false)
    {

        try {
            $this->url = '/coupon/open-api/v1/admin/coupons/event';
            $parame = [
                'couponWithSalt' => $couponWithSalt,
                'channel' => $channel,
                'type' => $type,
                'time' => $time,
            ];
            $res = $this->http->post($this->url, [
                'headers' => [
                    'Authorization' => $this->getToken($retry),
                    'X-TW-SIGNATURE' => $this->getSing($parame)
                ],
                'json' => $parame
            ]);

            $statusCode = $res->getStatusCode();
            $contents = $res->getBody()->getContents();
            if ($statusCode == 200) {
                return ['status_code' => 200, 'message' => 'success'];
            } elseif ($statusCode == 401) { // 未授权
                $this->getCouponsRedeem($couponWithSalt, $time, $channel, $type, true);
            } else { // 其他异常响应：
                return ['status_code' => $statusCode, 'message' => $contents];
            }
        } catch (\Exception $e) {  // 报错 按照没有数据处理
            return ['status_code' => 500, 'message' => $e->getMessage()];
        }
    }

    /**
     *  计算签名
     */
    private function getSing($parames = [])
    {
        $str = $this->url;
        if ($parames) {
            $str .= json_encode($parames);
        }
        $str .= $this->secret_key;
        return hash("sha256", $str);
    }
}
