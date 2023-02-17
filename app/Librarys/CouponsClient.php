<?php

namespace App\Librarys;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use App\Models\Ko\KoActCoupon;
use App\Models\Ko\KoActRecord;
use App\Librarys\Ko\KoCoupon;
use Exception;
use App\Jobs\KoCouponsRedeem;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;

class CouponsClient
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
                //Log::info('ko+请求记录', [$uri, $params, $res]);
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
     * 查询
     * @param string $coupon
     * @return void
     */
    public function getCouponsQuery(string $coupon, $retry = false)
    {
        try {
            $this->url = '/cre/open-api/v1/admin/coupons/' . $coupon;
            $res = $this->http->get($this->url, [
                'headers' => [
                    'Authorization' => $this->getToken($retry),
                    'X-TW-SIGNATURE' => $this->getSing()
                ],
            ]);
            $statusCode = $res->getStatusCode();
            $contents = $res->getBody()->getContents();
            if ($statusCode == 200) {
                return ['status_code' => 200, 'message' => 'success', 'result' => json_decode($contents, true)];
            } elseif ($statusCode == 401) { // 未授权
                $this->getCouponsQuery($coupon, true);
            } else {
                return ['status_code' => $statusCode, 'message' => $contents];
            }
        } catch (\Exception $e) {  // 报错 按照没有数据处理
            return ['status_code' => 500, 'message' => $e->getMessage()];
        }
    }


    /**
     * 核销
     * @param string $coupon 卷号
     * @param string $storeCity 门店所在城市名称
     * @param string $storeId 门店 ID
     * @param string $channel 核销渠道
     * @param string $longitude 经度
     * @param string $latitude 纬度
     * @return void
     */
    public function getCouponsRedeem(string $coupon, string $storeCity, string $storeId, string $channel, $longitude = '', $latitude = '', $retry = false)
    {

        try {
            $coupon = $this->refreshCode($coupon, $channel);
            $this->url = '/cre/open-api/v1/admin/coupons/redeem';
            $parame = [
                'coupon' => $coupon,
                'storeCity' => $storeCity,
                'storeId' => $storeId,
                'channel' => $channel,
                'longitude' => $longitude,
                'latitude' => $latitude,
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
                KoCouponsRedeem::dispatch($coupon, $storeCity, $storeId, $channel, 'redeem')->onConnection('redis')->onQueue('KoCouponsRedeem');
                if($channel == 'SWIRE_VENDING'){ //自贩机
                    KoCouponsRedeem::dispatch($coupon, $storeCity, $storeId, $channel, 'reversal')->onConnection('redis')->onQueue('KoCouponsRedeem')->delay(now()->addMinutes(60 * 12)); // 24 小时判断是否回收
                }
                return ['status_code' => 200, 'message' => 'success', 'coupon' => $coupon];
            } elseif ($statusCode == 401) { // 未授权
                $this->getCouponsRedeem($coupon, $storeCity, $storeId, $channel, $longitude, $latitude, true);
            } else { // 其他异常响应：
                return ['status_code' => $statusCode, 'message' => $contents];
            }
        } catch (\Exception $e) {  // 报错 按照没有数据处理
            return ['status_code' => 500, 'message' => $e->getMessage()];
        }
    }


    /**
     * 冲正
     * @param string $coupon 卷号
     * @param string $storeCity 门店所在城市名称
     * @param string $storeId 门店 ID
     * @param string $channel 核销渠道
     * @param string $longitude 经度
     * @param string $latitude 纬度
     * @return void
     */
    public function getCouponsReversal(string $coupon, string $storeCity, string $storeId, string $channel, $longitude = '', $latitude = '', $retry = false)
    {
        try {
            // $coupon = $this->refreshCode($coupon, $channel);
            $this->url = '/cre/open-api/v1/admin/coupons/reversal';
            $parame = [
                'coupon' => $coupon,
                'storeCity' => $storeCity,
                'storeId' => $storeId,
                'channel' => $channel,
                'longitude' => $longitude,
                'latitude' => $latitude,
            ];
            info('冲正请求参数记录-' . json_encode($parame));
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
                return ['status_code' => 200, 'message' => 'success', 'coupon' => $coupon];
            } elseif ($statusCode == 401) { // 未授权
                $this->getCouponsReversal($coupon, $storeCity, $storeId, $channel, $longitude, $latitude, true);
            } else { // 其他异常响应：
                return ['status_code' => $statusCode, 'message' => $contents];
            }
        } catch (\Exception $e) {  // 报错 按照没有数据处理
            return ['status_code' => 500, 'message' => $e->getMessage()];
        }
    }

    /**
     * 判断couponCode 是否过期
     */
    private function refreshCode($code, $channel = '')
    {
        if (in_array($channel, ['SWIRE_GT','SWIRE_MT_MYJ'])) {
            return $code;
        }
        if (!Redis::EXISTS('ko_' . $code)) {
            $koActCoupon = KoActCoupon::where('couponCode', $code)->first();
            if (empty($koActCoupon)) {
                throw new Exception('未找到 couponCode');
            }
            $koActRecord = KoActRecord::where(['couponId' => $koActCoupon->couponId, 'campaignMark' => substr($code, 0, 3)])->first();
            if (empty($koActRecord)) {
                throw new Exception('未找到 couponId');
            }
            $ko = new KoCoupon($this->type);
            $res = $ko->couponRefresh($koActRecord->membershipId, $koActRecord->couponId, ($channel ?: $koActRecord->channel));

            $result = $res['result'];
            if (isset($res['status_code']) && $res["status_code"] == 200) {
                KoActCoupon::query()->create(['couponId' => $koActRecord->couponId, 'couponCode' => $result["data"]["couponCode"]]);
                return $result["data"]["couponCode"];
            } else {
                throw new Exception(json_encode($result));
            }
        }
        return $code;
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
