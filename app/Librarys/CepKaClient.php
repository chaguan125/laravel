<?php

namespace App\Librarys;
use App\Models\ConfigKeyValue;
use Illuminate\Support\Facades\Log;

class CepKaClient{
    private $config = null;
    private $try_time = 0;

    public function __construct($config = null)
    {
        if (empty($config)) $config = config('cepka');
        $this->config = $config;
        // dd($config);
        $this->http = new \GuzzleHttp\Client([
            'base_uri' => $config['base_url'],
            'timeout'  => $config['timeout'] ?? 10,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * @descritpion '获取或刷新token'
     *
     * @param integer $try
     * @return array
     */
    public function getToken($try=2)
    {
        if($this->try_time >= $try){
            return ['code' => 1,'msg' => '获取token失败'];
        }
        $url = $this->config['prefix'].'backend/open-api/v1/tokens/bearer';

        $data = [];
        $cacheToken = ConfigKeyValue::getByCache('cep_ka_token',true);
        if(empty($cacheToken)){ //不存在时更新token
            try{
                $data = $this->request('POST', $url, [
                    'username' => $this->config['username'],
                    'password' => $this->config['password'],
                ]);
            }catch(\Exception $e){
                Log::info('cepka 获取token1',['config' => $this->config,'message' => $e->getMessage()]);
            }
            if(!empty($data['data']['token'])){
                $setData['expires_at'] = time() + 2*60*59;
                $setData['token'] = $data['data']['token'];
                ConfigKeyValue::setByCache('cep_ka_token',$setData,true);
                $cacheToken = $setData;
            }else{ // 进行重试
                $this->try_time += 1;
                $this->getToken();
            }
        }else{
            // $cacheToken['expires_at'] =  time() + $cacheToken['expires_in'];
            if(empty($cacheToken['expires_at']) || $cacheToken['expires_at'] <= time()){ //过期时更新token
                try{
                    $dataNew = $this->request('POST', $url, [
                        'username' => $this->config['username'],
                        'password' => $this->config['password'],
                    ]);
                }catch(\Exception $e){
                    Log::info('cepka 获取token2',['config' => $this->config,'message' => $e->getMessage()]);
                }

                if(!empty($dataNew['data']['token'])){
                    $setData['expires_at'] = time() + 2*60*60;
                    $setData['token'] = $dataNew['data']['token'];
                    ConfigKeyValue::setByCache('cep_ka_token',$setData,true);
                    $cacheToken = $dataNew;
                }else{
                    // 进行重试
                    $this->try_time += 1;
                    $this->getToken();
                }
            }
        }

        $cacheToken = ConfigKeyValue::getByCache('cep_ka_token',true);

        return $cacheToken;
    }


    public function setCodeCouponState($data){
        $result = [];

        $methodURl =  $this->config['prefix'].'cre/open-api/v2/admin/coupons/ka/callback';
        $secret = $this->config['secret'];
        $domain = $this->config['base_url'];
        $tokenInfo = $this->getToken();

        if(empty($tokenInfo['token'])){
            Log::info('cepka 请求卡券状态通知获取token接口',['message' => '获取token失败','params' => $data]);
            return ['code' => 1,'msg' => '获取token失败'];
        }

        $sign = $this->getSign(['domain' => $domain, 'url' => $methodURl,'body' => $data,'secret' => $secret]);

        $body =  $data;
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => $tokenInfo['token'],
            'X-TW-SIGNATURE' => $sign,
        ];

        info('可口可乐吧回调接口',$data);

        try{
            $result = $this->request('POST', $methodURl,$body,$headers);
        }catch(\Exception $e){
            Log::info('cepka 请求卡券状态通知接口',['message' => $e->getMessage(),'params' => $data,'headers' => $headers]);
        }
        // dd($result);
        return $result;
    }

    private function request($method, $url, $params,$headers =[])
    {
        if ($method == 'GET') {
            if(!empty($headers)){
                $request = [
                    'query' => $params,
                    'headers' => $headers,
                ];
            }else{
                $request = [
                    'query' => $params,
                ];
            }
            $res = $this->http->request($method, $url, $request);
        } elseif ($method == 'POST') {
            if(!empty($headers)){
                $request = [
                    'json' => $params,
                    'headers' => $headers,
                ];
            }else{
                $request = [
                    'json' => $params,
                ];
            }
            $res = $this->http->request($method, $url, $request);
        }

        if ($res->getStatusCode() != 200) {
            throw new \Exception('网络通讯错误', -1);
        }
        $contents = $res->getBody()->getContents();
        $data = json_decode($contents, true);
        return $data;
    }

    /**
     * 获取sign
     * @param array $data
     * @return String
     */
    public function getSign($data = []){
        $sign = '';
        $partSign = '';
        $partSign = str_replace($data['domain'], '', $data['url']);
        $partSign .= json_encode($data['body']);
        $partSign .= $data['secret'];
        $sign = hash('sha256',$partSign);
        // dd($sign,$partSign);
        return $sign;
    }

}
