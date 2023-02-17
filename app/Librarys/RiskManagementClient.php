<?php

namespace App\Librarys;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class RiskManagementClient
{
    private $client;

    private $config;

    /**
     * 构造函数
     *
     * @param array $config
     */
    public function __construct($config = [])
    {
        $this->config = $config;
        $this->client = new Client([
            'base_uri' => $config['base_url'] ?? '',
            'timeout' => 10.0,
            'http_errors' => false,
            'verify' => false
        ]);
    }

    /**
     * 再来一瓶风险评估
     *
     * @param string $unionid unionid
     * @param string $userIp 用户IP
     * @param integer $postTime 请求时间
     * @param string $goodsInfo 商品信息
     * @param string $phoneNumber 手机号码
     * @return array
     */
    public function judgeAgain($unionid, $userIp, $postTime, $goodsInfo, $phoneNumber)
    {
        $params = [
            # 账号信息
            'accountType' => 2,
            'uid' => $unionid,
            'phoneNumber' => $phoneNumber,

            # 物品信息
            'goodInfo' => $goodsInfo,

            # 行为信息
            'userIp' => $userIp,
            'postTime' => $postTime
        ];

        $params = collect($params)->filter(function ($value, $key) {
            return $value !== null;
        })->toArray();

        try {
            $result = $this->client->request('GET', 'index.php', ['query' => $this->formatParams($params)]);
            $result = json_decode($result->getBody()->getContents(), true);
            if (isset($result['code']) && $result['code'] != 0 && isset($result['message'])) {
                Log::error('风控接口请求异常, ' . $result['message']);
            }
            return $result;
        } catch (Exception $e) {
            Log::error('风控接口请求异常, ' . $e->getMessage());
            return ['code' => 500];
        }
    }

    private function formatParams($args, $region = 'ap-shanghai-1')
    {
        $secretId = $this->config['secretId'];
        $secretKey = $this->config['secretKey'];

        $args['Nonce'] = (string)rand(0, 0x7fffffff);
        $args['Action'] = 'IntelligentQRCode';
        $args['Region'] = $region;
        $args['SecretId'] = $secretId;
        $args['Timestamp'] = (string)time();

        ksort($args);
        $args['Signature'] = base64_encode(
            hash_hmac(
                'sha1', 'GET' . substr($this->config['base_url'], 8) . 'index.php?' . $this->makeQueryString($args, false),
                $secretKey, true
            )
        );
        return $args;
    }

    private function makeQueryString($args, $isURLEncoded)
    {
        $arr = array();
        foreach ($args as $key => $value) {
            if (!$isURLEncoded) {
                $arr[] = "$key=$value";
            } else {
                $arr[] = $key . '=' . urlencode($value);
            }
        }
        return implode('&', $arr);
    }
}
