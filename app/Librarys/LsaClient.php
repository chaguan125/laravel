<?php

namespace App\Librarys;

use App\Models\CentralMarketCodeActive;
use App\Models\CentralMarketCodeInfo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Models\Tenant\MarketCodeInfo;
use App\Models\Tenant\ActConfig;
use App\Models\Tenant\MarketCodeActive;
use App\Models\MarketCodeConfig;
use Illuminate\Support\Facades\Redis;

class LsaClient
{
    private $mchkey = '13c46b209610f7857c95c174d40bf990';
    private $http;
    private $type;


    public function __construct()
    {
        $this->type = config('ko_act.ko_env');
        $this->type = 'P';
        if ($this->type == 'P') {
            $this->mchkey = '7ace08e79594a83cba1fb56c0a335c33';
        }
        $base = [
            'D' => 'https://dev.lsa0.cn', // 测试环境
            'P' => 'https://utcyouma.app.swirecocacola.com'   // 正式环境
        ];
        $this->http = new \GuzzleHttp\Client([
            'base_uri' => $base[$this->type],
            'timeout'  => 10,
            'http_errors' => false,
            'verify' => false
            // 'debug' => fopen(storage_path('guzzleHttp.log'), 'a+')
        ]);
    }

    /**
     * 获取token
     */
    public function getToken($force = false)
    {
        try {
            $token = Cache::get('lsa_token' . $this->type);
            if ($force || empty($token)) {
                $res = $this->http->post('/api/thirdApi/getToken', [
                    'form_params' => [
                        'mchkey' => $this->mchkey
                    ]
                ]);
                $contents = $res->getBody()->getContents();
                if ($res->getStatusCode() === 200) {
                    $data = json_decode($contents, true);
                    if ($data['code'] == 0) {
                        $token = $data['data']['token'];
                        Cache::set('lsa_token' . $this->type, $token, 60 * 115); // 缓存 115分钟
                    } else {
                        throw new Exception($data['message']);
                    }
                } else {
                    throw new Exception($contents);
                }
            }
        } catch (Exception $e) {
            app('log')->error($e->getMessage());
        }
        return $token;
    }


    /**
     * 查询
     * @param string $code
     * @return void
     */
    public function queryCodeInfo(string $code, $retry = false)
    {
        try {
            $wxa_path = Redis::HGET('MarketCodeConfigController_bak:' . (tenant('id') ?? 0) . date('m'), $code);
            if ($wxa_path) { //存在直接返回
                return ['status_code' => 200, 'message' => 'success', 'result' => ['wxa_path' => $wxa_path]];
            }
            $res = $this->http->post('/api/thirdApi/codeinfo', [
                'headers' => [
                    'Token' => $this->getToken($retry),
                    'X-Requested-With' => 'XMLHttpRequest'
                ],
                'form_params' => [
                    'code' => $code,
                ]
            ]);

            $statusCode = $res->getStatusCode();
            $contents = $res->getBody()->getContents();
            if ($statusCode == 200) {
                $data = json_decode($contents, true);
                if ($data['code'] == 0) {
                    $data = $data['data'];

                    if($data['status'] != 1){
                        return ['status_code' => 400, 'message' => '二维码状态异常 -3'];
                    }

                    $marketCodeConfig = MarketCodeConfig::where([
                        'batch_no' => $data['batch_no'],
                        'batch_name' => $data['batch_name'],
                        'product_code' => $data['product_code'],
                        'product_name' => $data['product_name'],
                    ])->first();
                    // dd($marketCodeConfig);
                    if ($marketCodeConfig) {
                        if ($marketCodeConfig->tenant_id) {
                            tenancy()->initialize($marketCodeConfig->tenant_id);
                        }
                        $actConfig = ActConfig::find($marketCodeConfig->act_id);

                        if ($marketCodeConfig->act_type == 'red_packet') {
                            if($actConfig->is_merchant){
                                if ($actConfig->miya_status) {
                                    $wxa_path = $marketCodeConfig->tenant_id ? '/red_envelope_merchant_miya/pages/index/index?factory_id=' . $marketCodeConfig->tenant_id . '&act_id=' . $marketCodeConfig->act_id : '/red_envelope_miya/pages/index/index?act_id=' . $marketCodeConfig->act_id;
                                } else {
                                    $wxa_path = $marketCodeConfig->tenant_id ? '/red_envelope_merchant/pages/index/index?factory_id=' . $marketCodeConfig->tenant_id . '&act_id=' . $marketCodeConfig->act_id : '/red_envelope/pages/index/index?act_id=' . $marketCodeConfig->act_id;
                                }
                            }else{
                                if ($actConfig->miya_status) {
                                    $wxa_path = $marketCodeConfig->tenant_id ? '/red_envelope_miya/pages/index/index?factory_id=' . $marketCodeConfig->tenant_id . '&act_id=' . $marketCodeConfig->act_id : '/red_envelope_miya/pages/index/index?act_id=' . $marketCodeConfig->act_id;
                                } else {
                                    $wxa_path = $marketCodeConfig->tenant_id ? '/red_envelope/pages/index/index?factory_id=' . $marketCodeConfig->tenant_id . '&act_id=' . $marketCodeConfig->act_id : '/red_envelope/pages/index/index?act_id=' . $marketCodeConfig->act_id;
                                }
                            }
                        } else if ($marketCodeConfig->act_type == 'again') {
                            $wxa_path = $marketCodeConfig->tenant_id ? '/againbottleNoshare/pages/index/index?tenant=' . $marketCodeConfig->tenant_id . '&id=' . $marketCodeConfig->act_id : '/againbottleNoshare/pages/index/index?id=' . $marketCodeConfig->act_id;
                        } else if($marketCodeConfig->act_type == "full_reduct"){
                            //上海申美精准营销
                            $special_path = '/full_reduct/poster/poster?act_id='.$marketCodeConfig->act_id;
                        } else if($marketCodeConfig->act_type == "precision_marketing"){
                            //通用精准营销
                            $special_path = '/precision_marketing/poster/poster?act_id='.$marketCodeConfig->act_id.'&tenant='.$marketCodeConfig->tenant_id.'&code_ticket='.$code;
                        }else if($marketCodeConfig->act_type == 'centralism_precision'){
                            //多厂房公用码包的精准营销，进入入口后需根据用户定位，厂房活动来决定最终跳转路径
                            $special_path = '/centralism_precision/load/load?config_id='.$marketCodeConfig->id;
                        }else {
                            $wxa_path = $marketCodeConfig->tenant_id ? '/againbottle/pages/index/index?tenant=' . $marketCodeConfig->tenant_id . '&id=' . $marketCodeConfig->act_id : '/againbottle/pages/index/index?id=' . $marketCodeConfig->act_id;
                        }
                        if(in_array($marketCodeConfig->act_type,['full_reduct','precision_marketing', 'centralism_precision'])){ //其他活动特殊处理
                            $wxa_path = $special_path;
                        }else{
                            if(empty($actConfig)){ //兼容活动被删除
                                return ['status_code' => 400, 'message' => '活动不存在'];
                            }
                            $wxa_path = $actConfig->app_url ?: $wxa_path;
                        }

                        DB::transaction(function () use ($data, $marketCodeConfig, $wxa_path) {
                            $used_to = '';
                            if(in_array($marketCodeConfig->act_type,['full_reduct','precision_marketing'])){
                                $used_to = $marketCodeConfig->act_type;
                            }elseif($marketCodeConfig->act_type == 'centralism_precision'){
                                //多厂房公用码包的活动，记录下码包id，通过码包id查询group_id，继而查厂房是否有此码包
                                $used_to =  $marketCodeConfig->act_type . '_' . $marketCodeConfig->id;
                            }elseif($marketCodeConfig->tenant_id){
                                $used_to = $marketCodeConfig->act_type . '_' . $marketCodeConfig->act_id;
                            }else{
                                $used_to = 'central_' . $marketCodeConfig->act_type . '_' . $marketCodeConfig->act_id;
                            }
                            if($marketCodeConfig->tenant_id == 0){
                                CentralMarketCodeInfo::firstOrCreate(['code' => $data['code']], ['application_id' => $marketCodeConfig->batch_no, 'index' => 0]);
                                CentralMarketCodeActive::firstOrCreate(['application_id' => $marketCodeConfig->batch_no], [
                                    'code_start' => 0,
                                    'code_end' => 1,
                                    'wxa_type' => $this->type == "Q" ? 2 : 0,
                                    'used_to' => $used_to,
                                    'activity_name' => $data['batch_name'] ?? '',
                                    'product_code' => $data['product_code'] ?? '',
                                    'product_title' => $data['product_name'] ?? '',
                                    'wxa_appid' => '',
                                    'wxa_path' => $wxa_path,
                                ]);
                            }else{
                                MarketCodeInfo::firstOrCreate(['code' => $data['code']], ['application_id' => $marketCodeConfig->batch_no, 'index' => 0]);
                                MarketCodeActive::firstOrCreate(['application_id' => $marketCodeConfig->batch_no], [
                                    'code_start' => 0,
                                    'code_end' => 1,
                                    'wxa_type' => $this->type == "Q" ? 2 : 0,
                                    'used_to' => $used_to,
                                    'activity_name' => $data['batch_name'] ?? '',
                                    'product_code' => $data['product_code'] ?? '',
                                    'product_title' => $data['product_name'] ?? '',
                                    'wxa_appid' => '',
                                    'wxa_path' => $wxa_path,
                                ]);
                            }

                        });
                        Redis::HSET('MarketCodeConfigController_bak:' . (tenant('id') ?? 0) . date('m'), $code, $wxa_path);
                        Redis::EXPIRE('MarketCodeConfigController_bak:' . (tenant('id') ?? 0) . date('m'), 24 * 60 * 60); // 保存一个月
                        return ['status_code' => 200, 'message' => 'success', 'result' => ['wxa_path' => $wxa_path]];
                    } else {
                        return ['status_code' => 400, 'message' => '未找到二维码批次 -2'];
                    }
                } else {
                    return ['status_code' => 500, 'message' => $data['message']];
                }
            } elseif ($statusCode == 401) { // 未授权
                return $this->queryCodeInfo($code, true);
            } else {
                return ['status_code' => $statusCode, 'message' => '服务器内部错误 -1'];
            }
        } catch (Exception $e) {  // 报错 按照没有数据处理
            app('log')->error($e->getMessage());
            return ['status_code' => 500, 'message' => '服务器内部错误 -2'];
        }
    }


    /**
     * 查询
     * @param string $code
     * @return void
     */
    // public function getCodeInfo(string $code, string $appid, string $openid, string $used_to, $retry = false)
    // {
    // 	// try {
    // 	$res = $this->http->post('/api/thirdApi/codeinfo', [
    // 		'headers' => [
    // 			'Token' => $this->getToken($retry),
    // 			'X-Requested-With' => 'XMLHttpRequest'
    // 		],
    // 		'form_params' => [
    // 			'code' => $code,
    // 			'appid' => $appid,
    // 			'openid' => $openid
    // 		]
    // 	]);

    // 	$statusCode = $res->getStatusCode();
    // 	$contents = $res->getBody()->getContents();
    // 	if ($statusCode == 200) {
    // 		$data = json_decode($contents, true);
    // 		dd($data);
    // 		if ($data['code'] == 0) {
    // 			DB::transaction(function () use ($code, $data) {
    // 				MarketCodeInfo::firstOrCreate(['code' => $code], ['application_id' => $data['batch_no'], 'index' => 0]);
    // 				MarketCodeActive::firstOrCreate(['application_id' => $data['batch_no']], ['code_start' => 0, 'code_end' => 1, 'wxa_type' => $this->type == "Q" ? 2 : 0, 'used_to' => $used_to]);
    // 			});
    // 			return ['status_code' => 200, 'message' => 'success', 'result' => $data];
    // 		} else {
    // 			return ['status_code' => 500, 'message' => $data['message']];
    // 		}
    // 	} elseif ($statusCode == 401) { // 未授权
    // 		// $this->getCodeInfo($code, true);
    // 	} else {
    // 		return ['status_code' => $statusCode, 'message' => '服务器内部错误 -1'];
    // 	}
    // 	// } catch (Exception $e) {  // 报错 按照没有数据处理
    // 	// 	dd($e->getMessage());
    // 	// 	return ['status_code' => 500, 'message' => '服务器内部错误 -2'];
    // 	// }
    // }
}
