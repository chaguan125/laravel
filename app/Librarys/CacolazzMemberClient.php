<?php

namespace App\Librarys;

use Exception;
use Illuminate\Support\Facades\Log;

class CacolazzMemberClient
{
	private $http;
	public $sid ;
	public $key ;
    public $outh_config = [];

	public function __construct($type = 'P')
	{
		$base = [
			'D' => 'https://colvqnrmember.app.swiretest.com/',  //测试
//			'P' => 'https://colvmember.app.swirecocacola.com/'  //正式
			'P' => 'https://fsvmemberapi.app.swirecocacola.com/'  //正式
		];
		if ($type != 'P') {
			$this->sid = '001';
			$this->key = '7d42af6a99984e7c9df9e4f3654e9990';
		}else{
            $this->sid = '102';
			$this->key = '582eba6f1d024b1aab610cd1c478b42d';
        }
        $this->oauth_config = [
            'sid' => '102',
            'key' => '582eba6f1d024b1aab610cd1c478b42d'
        ];
		$this->type = $type;
		$this->http = new \GuzzleHttp\Client([
			'base_uri' => $base[$type],
			'timeout'  => 10,
            'headers'  => [
                'Content-Type' => 'application/json',//内容类
            ]
        ]);
	}

    /**
     * @param $phone
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * 判断是否是会员
     */
	public function isMemeber($phone){
          $param1 = [
              'sid'  => $this->sid,
              'phoneNo' => $phone,
          ];
        $param['json'] = [
            'sid'  => $this->sid,
            'phoneNo' => $phone,
        ];
        $param['json']['sign'] =  $this->getSign('/api/v1/isMember',$param1,$this->key);
//          $param['sign'] = $this->getSign('/api/v1/isMember',$param,$this->key);
          $res =$this->http->post('api/v1/isMember', $param);
        $data = json_decode($res->getBody()->getContents(), true);
//        Log::info('查询是否是会员结果',['data'=>$data]);
        return $data;
    }

    /**
     * @param mixed
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * 根据手机号或者unionid判断是否是会员
     */
    public function isMemeberNew($params){
        $param1 = [
            'sid'  => $this->sid,
        ];
        $param['json'] = [
            'sid'  => $this->sid,
        ];
        if(!empty($params['unionId'])){
            $param1['unionId'] = $params['unionId'];
            $param['json']['unionId'] = $params['unionId'];
        }
        if(!empty($params['phone']) && empty($params['unionId'])){ //只填写手机号时才会按手机号查询，手机号和unionid都填写只按unionid查询
            $param1['phoneNo'] = $params['phone'];
            $param['json']['phoneNo'] = $params['phone'];
        }
        $param['json']['sign'] =  $this->getSign('/api/v1/isMember',$param1,$this->key);
//          $param['sign'] = $this->getSign('/api/v1/isMember',$param,$this->key);
        $res =$this->http->post('api/v1/isMember', $param);
        $statusCode = $res->getStatusCode();
        if($statusCode == 200){
            $data = json_decode($res->getBody()->getContents(), true);
            // Log::info('查询是否是会员结果',['data'=>$data]);
            return $data;
        }else{
            throw new Exception($statusCode);
        }

    }


    /**
     * @param $phone
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * 获取会员积分
     */
    public function getPoint($phone)
    {
        try {
            $param1 = [
                'sid'  => $this->sid,
                'phoneNo' => $phone,
            ];
            $param['json'] = $param1;
            $param['json']['sign'] = $this->getSign('/api/v1/queryScore',$param1,$this->key);
            $res =$this->http->post('api/v1/queryScore', $param);
            $statusCode = $res->getStatusCode();
            $contents = $res->getBody()->getContents();
            if ($statusCode == 200) {
                $data = json_decode($contents, true);
                if ($data['code'] == 0) {
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
     * @param $param
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * 注册会员
     */
    public function registMember($param){

        $param['sid'] = $this->sid;
        $param1['json'] = $param;
        $param1['json']['sign'] = $this->getSign('/api/v1/register',$param,$this->key);
        $res =$this->http->post('api/v1/register', $param1);
        $data = json_decode($res->getBody()->getContents(), true);
//        Log::info('会员注册结果',['data'=>$data]);
        return $data;
    }

     /**
     * @param $param
     * @return mixed
     * 增加消耗积分
     */
    public function addScore($param)
    {
        try {
            $param['sid'] = $this->sid;
            $param1['json'] = $param;
            $param1['json']['sign'] = $this->getSign('/api/v1/addScore',$param,$this->key);
            $res =$this->http->post('api/v1/addScore', $param1);

            $statusCode = $res->getStatusCode();
            $contents = $res->getBody()->getContents();
            if ($statusCode == 200) {
                $data = json_decode($contents, true);
                if ($data['code'] == 0) {
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
     * @param $url
     * @param $params
     * @param $key
     * @return string
     * 签名
     */
    public function getSign($url, $params, $key = '')
    {
        $key = empty($key) ? $this->key : $key;
        $str = $url;
        if ($params) {
            ksort($params);
            foreach($params as $k=>$val){
                if(isset($val)){
                    $str.='&'.$k.'='.$val;
                }
            }
        }
        $str .= '&'.$key;
        return  hash('sha256', $str);
    }

}
