<?php

namespace App\Librarys;

use Exception;
use Illuminate\Support\Str;


class MeiYaClient
{
    private $http;
    private $type;
    private $appId = '1603699621019';
    private $appSecret = 'b4461387de204f6286c71f43562cf09c';
    private $createMchId = '1520952751';
    private $sendMchId = '1242800802';
    // private $stockId = '15661142';
    // 创建批次商户号1520952751  发券商户号1242800802  微信批次号15661142

    public function __construct()
    {
        $this->type = config('ko_act.ko_env');
        // $this->type = 'D';
        if ($this->type == 'P') {
            $this->appId = '1603699621009';
            $this->appSecret = 'e44883c2f9604d9f84cdc8f2d4f63909';
            $this->createMchId = '1520952751';
            $this->sendMchId = '1242800802';
            // $this->stockId = '15661142';
        }
        $base = [
            'D' => 'http://dev.promotion.miyapay.com', // 测试环境
            // 'D' => 'https://promotion.miyapay.com', // 测试环境
            'P' => 'https://promotion.miyapay.com'   // 正式环境
        ];
        $this->http = new \GuzzleHttp\Client([
            'base_uri' => $base[$this->type],
            'timeout'  => 10,
            'http_errors' => false,
            'verify' => false,
            // 'debug' => fopen(storage_path('guzzleHttp.log'), 'a+'),
            'headers' => $this->headers()
        ]);
    }

    //返回当前的毫秒时间戳
    private function getMsectime()
    {
        list($msec, $sec) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
    }

    private function headers()
    {
        $params = [
            'appId' => $this->appId,
            'nonce' =>  (string) Str::uuid(),
            'timestamp' => $this->getMsectime(),
            'version' => '1.0.0',
            'appSecret' => $this->appSecret,
        ];
        $str = '';
        foreach ($params as $k => $v) {
            $str .= $k . '=' . $v . '&';
        }
        $str = rtrim($str, '&');
        $params['sign'] = strtoupper(md5($str));
        unset($params['appSecret']);
        // info($params);
        return $params;
    }



    /**
     * 统一微信发券接口
     * @param string $openid
     * @return void
     */
    public function commonSend(string $openid, string $stockId)
    {
        try {
            $res = $this->http->post('/promotion/wx/coupon/commonSend', [
                'json' => [
                    'createMchId' => $this->createMchId,
                    'sendMchId' => $this->sendMchId,
                    'stockId' => $stockId,
                    'openId' => $openid,
                ]
            ]);
            $statusCode = $res->getStatusCode();
            $contents = $res->getBody()->getContents();
            if ($statusCode == 200) {
                $data = json_decode($contents, true);
                if ($data['code'] == 200) {
                    return ['status_code' => 200, 'message' => 'success', 'result' => $data];
                } else {
                    return ['status_code' => 500, 'message' => $data['msg'], 'result' => $data];
                }
            } else {
                return ['status_code' => $statusCode, 'message' => '服务器内部错误 -1'];
            }
        } catch (Exception $e) {  // 报错 按照没有数据处理
            return ['status_code' => 500, 'message' => $e->getMessage()];
        }
    }


    /**
     * 统一微信发券接口
     * @param
     * @return void
     */
    public function commonSendNew(string $openid, string $stockId, string $outBizNo, string $createMchId = '', string $sendMchId = '')
    {
        try {
            $res = $this->http->post('/promotion/wx/coupon/commonSend', [
                'json' => [
                    'createMchId' => $createMchId,
                    'sendMchId' => $sendMchId,
                    'stockId' => $stockId,
                    'openId' => $openid,
                    'outBizNo' => $outBizNo
                ]
            ]);
            $statusCode = $res->getStatusCode();
            $contents = $res->getBody()->getContents();
            if ($statusCode == 200) {
                $data = json_decode($contents, true);
                if ($data['code'] == 200) {
                    return ['status_code' => 200, 'message' => 'success', 'result' => $data];
                } else {
                    return ['status_code' => 500, 'message' => $data['msg'], 'result' => $data];
                }
            } else {
                return ['status_code' => $statusCode, 'message' => '服务器内部错误 -1'];
            }
        } catch (Exception $e) {  // 报错 按照没有数据处理
            return ['status_code' => 500, 'message' => $e->getMessage()];
        }
    }


    /**
     * 通知配置接口，用于接收核销回调 - 未测试
     * @param string $openid
     * @return void
     */
    public function setWxNotifyUrl(string $notifyUrl, string $stockId)
    {
        try {
            $res = $this->http->post('/promotion/wx/coupon/setWxNotifyUrl', [
                'json' => [
                    'notifyUrl' => $notifyUrl,
                    'stockId' => $stockId,
                ]
            ]);
            $statusCode = $res->getStatusCode();
            $contents = $res->getBody()->getContents();
            if ($statusCode == 200) {
                $data = json_decode($contents, true);
                if ($data['code'] == 200) {
                    return ['status_code' => 200, 'message' => 'success', 'result' => $data];
                } else {
                    return ['status_code' => 500, 'message' => $data['msg']];
                }
            } else {
                return ['status_code' => $statusCode, 'message' => '服务器内部错误 -1'];
            }
        } catch (Exception $e) {  // 报错 按照没有数据处理
            return ['status_code' => 500, 'message' => $e->getMessage()];
        }
    }

    /**
     * 红包发放接口
     * /rpc/wechat/coupon/sendRedPacketForUser
     */
    public function sendRedPacketForUser($openId, $amount, $outBizNo, $id = '')
    {
        // info($openId . '-' . $amount . '-' . $outBizNo . '-' . $id);
        // return [
        //     'status_code' => 200,
        //     'message' => 'success',
        //     'result' => [
        //         "code" => 200,
        //         "msg" => null,
        //         "tracerId" => "fe6ff00354667bd4",
        //         "data" => "10100104597982112312059610569265"
        //     ]
        // ];
        try {
            $res = $this->http->post('/promotion/rpc/wechat/coupon/sendRedPacketForUser', [
                'json' => [
                    'openId' => $openId,
                    'amount' => $amount,
                    'outBizNo' => $outBizNo,
                    'id' => $id
                ]
            ]);
            $statusCode = $res->getStatusCode();
            $contents = $res->getBody()->getContents();
            // info($contents);
            if ($statusCode == 200) {
                $data = json_decode($contents, true);
                if ($data['code'] == 200) {
                    return ['status_code' => 200, 'message' => 'success', 'result' => $data];
                } else {
                    return ['status_code' => 500, 'message' => $data['msg'], 'result' => $data];
                }
            } else {
                return ['status_code' => $statusCode, 'message' => '服务器内部错误 -1'];
            }
        } catch (Exception $e) {  // 报错 按照没有数据处理
            return ['status_code' => 500, 'message' => substr($e->getMessage(), 0, 150)];
        }
    }
}
