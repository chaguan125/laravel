<?php

namespace App\Librarys;

class YoushuApi
{
    public function __construct($config = null)
    {
        if ($config === null) $config = config('youshu');
        $this->config = $config ?: config('youshu');
        if (empty($this->config)) {
            throw new \Exception('缺少配置');
        }
        $this->http = new \GuzzleHttp\Client([
            'base_uri' => $this->config['test'] ? 'https://test.zhls.qq.com' : 'https://zhls.qq.com',
            'timeout' => 10,
        ]);
    }

    public function add_wxapp_visit_page($data)
    {
        return $this->request('POST', 'data-api/v1/analysis/add_wxapp_visit_page', $data);
    }

    public function add_wxapp_visit_distribution($data)
    {
        return $this->request('POST', 'data-api/v1/analysis/add_wxapp_visit_distribution', $data);
    }

    /**
     * 添加数据仓库
     *
     * @param integer $dataSourceType
     * @return array
     */
    public function add_data_source($dataSourceType)
    {
        return $this->request('POST', 'data-api/v1/data_source/add', [
            'merchantId' => $this->config['merchant_id'],
            'dataSourceType' => $dataSourceType
        ]);
    }

    /**
     * 获取数据仓库
     *
     * @param integer $dataSourceType
     * @return array
     */
    public function get_data_source($dataSourceType)
    {
        return $this->request('POST', 'data-api/v1/data_source/add', [
            'merchantId' => $this->config['merchant_id'],
            'dataSourceType' => $dataSourceType
        ]);
    }

    /**
     * 获取签名配置
     *
     * @return array
     */
    public function signature()
    {
        $app_id = $this->config['app_id'];
        $nonce = md5(uniqid() . mt_rand(0, 99999));
        $sign = 'sha256';
        $timestamp = time();
        $str = 'app_id=' . $app_id . '&nonce=' . $nonce . '&sign=' . $sign . '&timestamp=' . $timestamp;
        $signature = hash_hmac($sign, $str, $this->config['app_secret'], false);
        return compact('app_id', 'nonce', 'sign', 'timestamp', 'signature');
    }

    public function request($method, $url, $params)
    {
        if ($method == 'GET') {
            $res = $this->http->request('GET', $url, [
                'query' => $params + $this->signature()
            ]);
        } elseif ($method == 'POST') {
            $res = $this->http->request('POST', $url, [
                'query' => $this->signature(),
                'json' => $params
            ]);
        }

        if ($res->getStatusCode() != 200) {
            throw new \Exception('网络通讯错误', -1);
        }
        $data = json_decode($res->getBody()->getContents(), true);
        if ($data['retcode'] != 0) {
            throw new \Exception($data['errmsg'], $data['retcode']);
        }
        return $data['data'];
    }
}
