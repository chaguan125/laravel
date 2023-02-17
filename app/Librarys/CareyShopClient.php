<?php

namespace App\Librarys;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Exception;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;
use phpDocumentor\Reflection\Utils;

class CareyShopClient
{
    private $appKey; //carey shop app key
    private $appSecret; // carey shop app secret
    private $format = 'json'; //default return result format
    private $sign = ''; //params secret value
    private $timestamp = 0;
    private $token = '';
    private $baseUrl;
    private $url;
    private $http;
    private $type;
    protected $re_try_time = 1; //重试次数

    public function __construct($baseUrl, $url, $type = 'P')
    {
        $this->appKey = config('carey_shop.app_key');
        $this->appSecret = config('carey_shop.app_secret');
        $this->type = $type;
        $this->baseUrl = $baseUrl;
        $this->url = $url;
        $this->timestamp = time();
        $handlerStack = HandlerStack::create(new CurlHandler());
        $handlerStack->push(Middleware::retry($this->retryDecider(), $this->retryDelay()));
        $this->http = new \GuzzleHttp\Client([
            'base_uri' => $baseUrl,
            'timeout' => 10,
            'http_errors' => false,
            'handler' => $handlerStack,
            'verify' => false,
            //            'debug' => fopen(storage_path('guzzleHttp.log'), 'a+')
        ]);
    }

    /**
     * 查询
     * @param string $coupon
     * @return void
     */
    public function query($params, $retry = false)
    {
        $this->token = empty($params['token']) ? '' : $params['token']; //token created by careyshop , utc-platform fetched it and transport back to careyshop by pass-through
        $params['appkey'] = $this->appKey;
        $params['format'] = $this->format;
        $params['sign'] = $this->getSign();
        $params['timestamp'] = $this->timestamp;
        $statusCode = '';
        $contents = '';

        try {
            $res = $this->http->get($this->url, [
                'query' => $params,
            ]);
            $statusCode = $res->getStatusCode();
            $contents = $res->getBody()->getContents();
            $arrContents = json_decode($contents, true);
            // {"status":500,"message":"优惠劵领取日期已结束","data":null}
            if ($statusCode == 200) {
                return $arrContents;
                //                return ['status_code' => 200, 'message' => 'success', 'result' => json_decode($contents, true)];
            } else {
                return $arrContents;
                // return [];
                //                return ['status_code' => $statusCode, 'message' => $contents];
            }
        } catch (\Exception $e) {  // 报错 按照没有数据处理
            // info($statusCode);
            // info($contents);
            Log::error($e->getMessage());
            return ['sttus' => 500, 'message' => $e->getMessage()];
            //            return ['status_code' => 500, 'message' => $e->getMessage()];
        }
    }

    /**
     * 查询
     * @param string $coupon
     * @return void
     */
    public function sendPost($params, $retry = false)
    {
        $this->token = empty($params['token']) ? '' : $params['token']; //token created by careyshop , utc-platform fetched it and transport back to careyshop by pass-through
        $params['appkey'] = $this->appKey;
        $params['format'] = $this->format;
        $params['sign'] = $this->getSign();
        $params['timestamp'] = $this->timestamp;
        try {
            $res = $this->http->post($this->url, [
                'form_params' => $params,
            ]);
            $statusCode = $res->getStatusCode();
            $contents = $res->getBody()->getContents();
            //            dd($contents);
            if ($statusCode == 200) {
                return json_decode($contents, true);
                //                return ['status_code' => 200, 'message' => 'success', 'result' => json_decode($contents, true)];
            } else {
                return [];
                //                return ['status_code' => $statusCode, 'message' => $contents];
            }
        } catch (\Exception $e) {  // 报错 按照没有数据处理
            return [];
            //            return ['status_code' => 500, 'message' => $e->getMessage()];
        }
    }

    /**
     * 查询
     * @param string $coupon
     * @return void
     */
    public function sendPost2($params, $retry = false)
    {
        $this->token = empty($params['token']) ? '' : $params['token']; //token created by careyshop , utc-platform fetched it and transport back to careyshop by pass-through
        $params['appkey'] = $this->appKey;
        $params['format'] = $this->format;
        $params['sign'] = $this->getSign();
        $params['timestamp'] = $this->timestamp;
        //        info(json_encode($params,256));
        try {
            $res = $this->http->post($this->url, [
                'form_params' => $params,
            ]);
            $statusCode = $res->getStatusCode();
            $contents = $res->getBody()->getContents();
            if ($statusCode == 200) {
                return json_decode($contents, true);
                //                return ['status_code' => 200, 'message' => 'success', 'result' => json_decode($contents, true)];
            } else {
                return [];
                //                return ['status_code' => $statusCode, 'message' => $contents];
            }
        } catch (\Exception $e) {  // 报错 按照没有数据处理
            return [];
            //            return ['status_code' => 500, 'message' => $e->getMessage()];
        }
    }


    /**
     *  计算签名
     */
    private function getSign($params = [])
    {
        $sign = '';
        ksort($params);
        $stringToBeSigned = $this->appSecret;
        $type = ['array', 'object', 'NULL'];
        foreach ($params as $key => $val) {
            if ($key != '' && !in_array(gettype($val), $type)) {
                $stringToBeSigned .= $key .  (is_bool($val) === true ?  ($val ? 1 : 0)  : $val);
            }
        }
        unset($key, $val);
        $stringToBeSigned .= $this->appSecret;
        $sign = md5($stringToBeSigned);
        $this->sign = $sign;
        return $sign;
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
                //                $res = json_decode($response->getBody(), true);
                //                $response->getBody()->rewind(); //将流指针倒回开始位置
                //                $uri = $request->getUri()->getPath();
                //                $params = json_decode($request->getBody(), true);
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
}
