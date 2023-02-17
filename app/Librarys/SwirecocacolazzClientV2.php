<?php

namespace App\Librarys;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SwirecocacolazzClientV2
{
	private $http;
	private $type;
	public $appId = '9B5AF24C-D1C4-4854-A94F-F6E43FC0DD2C';
	public $appSecret = '5F85B95A-4DA5-402E-B408-BBF066313F70';

	public function __construct($type = 'P')
	{
		$base = [
			'Q' => 'https://utcapiqnr.app.swiretest.com',
			'P' => 'https://utcapigateway.app.swirecocacola.com'

		];
		if ($type == 'P') {
			$this->appId = '966E605B-06A9-4FAF-BE71-E8E3FA79DE97';
			$this->appSecret = '16478407-E438-4E7F-A47F-CEA7E7BC1BA8';
		}
		$this->type = $type;
		$this->http = new \GuzzleHttp\Client([
			'base_uri' => $base[$type],
			'timeout'  => 10,
		]);
	}

	/**
	 * 获取token
	 */
	public function getAccessTokenV2($force = false)
	{
		$token = Cache::get('swirecocacolazz_accesstoken_v2' . $this->type);
		if ($force || empty($token)) {
			$res = $this->http->get('app/getAccessTokenByApp', [
				'query' => [
					'appId' => $this->appId,
					'appSecret' => $this->appSecret
				]
			]);
			$data = json_decode($res->getBody()->getContents(), true);
			$token = $data['accessToken'];
			$expiresOn = strtotime($data['expiresOn']);
			Cache::set('swirecocacolazz_accesstoken_v2' . $this->type, $token, Carbon::createFromTimestamp(min($expiresOn, time() + 7200)));
		}
		return $token;
	}

	/**
	 * 查询
	 *
	 * @param string $lat
	 * @param string $lng
	 * @param string $distance 距离
	 * @param string $campaignCode 二次请求标识
	 * @return void
	 */
	public function getCustomerInfoByNoV2($lat, $lng, $distance, $campaignCode, $retry = false)
	{
		try {
			$res = $this->http->post('UTC/customer/getCustomerInfoByLocation', [
				'headers' => [
					'x-access-token' => $this->getAccessTokenV2($retry)
				],
				'json' => [
					'locationX' => (string)$lng,
					'locationY' => (string)$lat,
					'distance' => (string)$distance,
					'campaignCode' => (string)$campaignCode,

				]
			]);
			$data = json_decode($res->getBody()->getContents(), true);
			if ($data['status'] == 200) {
				$result = [];
				foreach ($data['customerInfoList'] as $item) {
					if (isset($item['outletLocationX']) && isset($item['outletLocationY']) && $item['outletLocationX'] && $item['outletLocationY']) {
                        $nodeType = $item['nodeType'] ?? '';
					    $result[$item['outletNo']] = [
							'location' => $item['outletLocationX'] . ',' . $item['outletLocationY'],
							'name' => $item['outletName'],
							'phone' => [],
							'address' => $item['outletAddress'],
                            'distance' => (int)$item['distance'] ?? 0,
							'nodeType' => strtolower($nodeType)
						];
					}
				}
				return $result;
			} elseif ($data['status'] == 202) {
				return [];
			} else if (!$retry) {
				return $this->getCustomerInfoByNoV2($lat, $lng, $distance, $campaignCode, true);
			}
		} catch (\Exception $e) {  // 报错 按照没有数据处理
			// info($e);
			return [];
		}
		// throw new \Exception($data['message'], $data['status']);
	}

	/**
	 * 查询缓存
	 *
	 * @param string $lat
	 * @param string $lng
	 * @param string $distance 距离
	 * @param string $campaignCode 二次请求标识
	 * @return void
	 */
	public function getCacheCustomerInfoByNoV2($lat, $lng, $distance, $campaignCode, $retry = false)
	{
		$result = Cache::get('getCacheCustomerInfoByNoV2' . $this->type . $lat . $lng . $distance . $campaignCode);
		if ($retry || empty($result)) {
			$result = $this->getCustomerInfoByNoV2($lat, $lng, $distance, $campaignCode, $retry);
			if ($result) {
				Cache::put('getCacheCustomerInfoByNoV2' . $this->type . $lat . $lng . $distance . $campaignCode, $result, now()->addMinutes(10)); // 缓存 10 分钟
			}
		}
		return $result;
	}

	/**
	 *
	 * 获取店铺经纬度信息
	 * @param string $outletNo
	 * @param boolean $retry
	 * @return void
	 */
	public function getCustomerLocation($outletNo, $retry = false){
		try {
			$res = $this->http->post('/UTC/customer/getLocationByOutletNo', [
				'headers' => [
					'x-access-token' => $this->getAccessTokenV2($retry)
				],
				'json' => [
					'outletNo' => (string)$outletNo
				]
			]);
			$data = json_decode($res->getBody()->getContents(), true);
			if ($data['status'] == 200) {
				return [
					'lat' => $data['outletLocationY'],
					'lng' => $data['outletLocationX']
				];
			} elseif ($data['status'] == 202) {
				return true;
			} else if (!$retry) {
				return $this->getCustomerLocation($outletNo, true);
			}
		}catch (\Exception $e) {  // 报错 按照没有数据处理
			Log::error('请求店铺经纬度进行异常'.$e->getMessage());
			return false;
		}

		return false;
	}

	/**
	 *
	 * 获取店铺经纬度信息
	 * @param string $outletNo
	 * @param boolean $retry
	 * @return void
	 */
	public function getCustomerLocationCacheable($outletNo, $retry = false){
		$result = Cache::get('getCustomerLocationV2' . $this->type . $outletNo);
		if ($retry || empty($result)) {
			$result = $this->getCustomerLocation($outletNo, $retry);
			if ($result) {
				Cache::put('getCustomerLocationV2' . $this->type . $outletNo, $result, now()->addMinutes(10)); // 缓存 10 分钟
			}
		}
		return $result;
	}
}
