<?php

namespace App\Librarys\Ko;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class Http
{
    private $type;

    private $force = false;

    private $username;

    private $password;

    private $secret_key;

    protected $re_try_time = 1;//重试次数

    public $httpClient;

    /**
     * Http constructor.
     * @param string $type
     */
    public function __construct($type = 'P')
    {
        $user = config('ko_act')['user_info'][$type] ?? [];
        $this->username = $user['username'] ?? '';
        $this->password = $user['password'] ?? '';
        $this->secret_key = $user['secret_key'] ?? '';
        $this->type = $type;
        $handlerStack = HandlerStack::create(new CurlHandler());
        $handlerStack->push(Middleware::retry($this->retryDecider(), $this->retryDelay()));
        $this->httpClient = new Client([
            'base_uri' => $user['url'] ?? '',
            'timeout' => 10.0,
            'http_errors' => false,
            'handler' => $handlerStack
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
            if ($response) {
                $res = json_decode($response->getBody(), true);
                $response->getBody()->rewind();//将流指针倒回开始位置
                //if ($this->type == 'D') {
                    //测试环境才记录日志
                    $uri = $request->getUri()->getPath();
                    if (in_array($uri, ["/cre/open-api/v1/admin/coupons/channel/receive", "/cre/open-api/v1/admin/coupons/refresh"])) {
                        $params = json_decode($request->getBody(), true);
                        //Log::info('ko+请求记录', [$request->getUri()->getQuery(), $params, $res]);
                    }
                //}
                if ($response->getStatusCode() == 401) {
                    $error_code_normal = $res["error"]["code"] ?? "";
                    $error_code_abnormal = $res["code"] ?? "";
                    if ($error_code_normal == "UNAUTHORIZED" || $error_code_abnormal == "UNAUTHORIZED") {
                        $this->force = true;
                        return true;
                    }
                }
            }
            return false;
        };
    }

    public function retryDelay()
    {
        return function ($numberOfRetries) {
            return 500;//返回下次重试的时间（毫秒）
//            return 1000 * $numberOfRetries;
        };
    }

    /**
     * 获取token
     * @param bool $force
     * @return mixed|string
     * @throws \Exception
     */
    public function getToken($force = false)
    {
        $token = Cache::get('ko_coupon_token' . $this->type);
        if ($this->force || $force || empty($token)) {
            $params = [
                'username' => $this->username,
                'password' => $this->password
            ];
            $res = $this->http_post('/backend/open-api/v1/tokens/bearer', $params, false);
            $result = $res['result'] ?? [];
            if (!isset($res['status_code']) || $res['status_code'] != 200 || !isset($result['data']['token']) || empty($result['data']['token'])) {
                $error_msg = "token:" . ($result["error"]["code"] ?? "获取ko_token失败");
                throw new \Exception($error_msg, 401);
            }
            $token = $result['data']['token'];
            $this->setToken($token);
        }
        return $token;
    }

    public function setToken($token)
    {
        Cache::put('ko_coupon_token' . $this->type, $token, 7200 - 30);//有效期是两个小时
    }

    /**
     * @param string $action
     * @param array $params
     * @param bool $auth
     * @param string $method
     * @return array
     * @throws \Exception
     */
    public function headers($action, $params = [], $auth = true, $method = 'POST')
    {
        if (!$auth) {
            return [
                'Content-Type' => 'application/json',//内容类型
            ];
        }
        return [
            'Content-Type' => 'application/json',//内容类型
            'Authorization' => $this->getToken(),//Bearer Token
            'X-TW-SIGNATURE' => $this->getSign($action, $params, $method)//签名
        ];
    }

    /**
     * @param string $action 请求地址
     * @param array $params 参数
     * @param bool $auth 是否需要token
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function http_get($action = '', $params = [], $auth = true)
    {
        $response = $this->httpClient->request('GET', $action, [
            'query' => $params,
            'headers' => $this->headers($action, $params, $auth, 'GET')
        ]);
        $res = json_decode($response->getBody(), true);
        if ($auth && $response->getStatusCode() == 200) {
            $token = Cache::get('ko_coupon_token' . $this->type);
            $this->setToken($token);
        }
        return ['status_code' => $response->getStatusCode(), 'result' => $res];
    }

    /**
     * @param string $action 请求地址
     * @param array $params 参数
     * @param bool $auth 是否需要token
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function http_post($action = '', $params = [], $auth = true)
    {
        $response = $this->httpClient->request('POST', $action, [
            'json' => $params,
            'headers' => $this->headers($action, $params, $auth, 'POST')
        ]);
        $res = json_decode($response->getBody(), true);
        if ($auth && $response->getStatusCode() == 200) {
            $token = Cache::get('ko_coupon_token' . $this->type);
            $this->setToken($token);
        }
        return ['status_code' => $response->getStatusCode(), 'result' => $res];
    }

    /**
     * 获取签名
     * @param string $action
     * @param $params
     * @param string $method
     * @return string
     */
    public function getSign($action, $params, $method = 'POST')
    {
        if ($params && strtolower($method) == 'get') {
            $action .= "?" . http_build_query($params);
        }
        $str = $action;
        if ($params && strtolower($method) == 'post') {
            $str .= json_encode($params);
        }
        $str .= $this->secret_key;
        return hash('sha256', $str);
    }
}
