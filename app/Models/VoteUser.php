<?php

namespace App\Models;

use Encore\Admin\Traits\DefaultDatetimeFormat;

use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class VoteUser extends Model
{
	use DefaultDatetimeFormat;

    protected $guarded = [];
    protected $table = 'vote_users';

    public function checkMobilePlace($mobilephone){
        $url = "";
        $client = new \GuzzleHttp\Client([
            'base_uri' => $url,
            'timeout'  => 10,
        ]);
        try {
            $response = $client->request('get', "https://api04.aliyun.venuscn.com/mobile?mobile=".$mobilephone, [
                'headers' => [
                    'Authorization' => 'APPCODE a83ecaaa19e2446d891fd0b61a1a5db9'
                ],
            ]);
            $data = $response->getBody()->getContents();//获取响应内容
            $data = json_decode($data, true);
            if(!empty($data['data'])){
                return $data['data'];
            }
        }catch (\Exception $e){
                return [];
        }
        return [];
    }

    public function ipAddress(){
        $ip = request()->ip();
        $regionName = Cache::remember('rem_user_ip:'.$ip,1800,function () use ($ip){
            $client = new Client([
                'base_uri' => url('http://ip-api.com/json/'.$ip),
                'timeout' => 10.0
            ]);
            try{
                $response = $client->request('GET', '?lang=zh-CN');
                $res = $response->getBody()->getContents();//获取响应内容
                $dataArray = json_decode($res,true);
                if($dataArray && isset($dataArray['regionName']) && $dataArray['regionName']){
                    $regionName = $dataArray['regionName'];
                    return $regionName;
                }
            }catch (\Exception $e){
            }
            return null;
        });
        return $regionName;
    }

}
