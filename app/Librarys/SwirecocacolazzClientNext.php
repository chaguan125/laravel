<?php

namespace App\Librarys;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class SwirecocacolazzClientNext
{
	private $http;
	private $type;
	public $appId = '12584488-81AB-4A9D-9DF2-498A216E2375';
	public $appSecret = '795A44E4-F03D-4D5D-95A7-D7BE11FB6E57';

	public function __construct($type = 'P')
	{
		$base = [
			'D' => 'https://scczzdnr.app.swiretest.com/',
			'Q' => 'https://myscczzqnr.app.swiretest.com/',
			'P' => 'https://www.swirecocacolazz.com/',
			'P2' => 'https://utcapiqnr.app.swiretest.com'

		];
		if (!($type == 'P' || $type == 'P2')) {
			$this->appId = '9B5AF24C-D1C4-4854-A94F-F6E43FC0DD2C';
			$this->appSecret = '5F85B95A-4DA5-402E-B408-BBF066313F70';
		}
		$this->type = $type;
		$this->http = new \GuzzleHttp\Client([
			'base_uri' => $base[$type],
			'timeout'  => 10,
		]);
	}

	/**
	 * 查询
	 *
	 * @param array $outlet_no 渠道编码
	 * @param string $campaign_code 活动编号
	 * @param boolean $retry 二次请求标识
	 * @return void
	 */
	public function getCustomerInfoByNo($outlet_no, $campaign_code, $retry = false)
	{
		$res = $this->http->get('UTC/customer/getCustomerInfoByNo/' . implode(',', $outlet_no) . '/3008/' . $campaign_code, [
			'headers' => [
				'x-access-token' => $this->getAccessToken($retry)
			]
		]);
		$data = json_decode($res->getBody()->getContents(), true);
		if ($data['status'] == 200) {
			$result = [];
			foreach ($data['customerInfoList'] as $item) {
				$phone = [];
				if (!empty($item['phone1'])) {
					$phone[] = $item['phone1'];
				}
				if (!empty($item['phone2']) && $item['phone2'] != $item['phone1']) {
					$phone[] = $item['phone2'];
				}
				$result[$item['outletNo']] = [
					'name' => $item['outletName'],
					'phone' => $phone,
					'address' => $item['outletAddress']
				];
			}
			return $result;
		} else if (!$retry) {
			return $this->getCustomerInfoByNo($outlet_no, $campaign_code, true);
		}
		throw new \Exception($data['message'], $data['status']);
	}

	public function getAccessToken($force = false)
	{
		$token = Cache::get('swirecocacolazz_accesstoken' . $this->type);
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
			Cache::set('swirecocacolazz_accesstoken' . $this->type, $token, Carbon::createFromTimestamp(min($expiresOn, time() + 7200)));
		}
		return $token;
	}

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
				$result[$item['outletNo']] = [
					'location' => $item['outletLocationX'] . ',' . $item['outletLocationY'],
					'name' => $item['outletName'],
					'phone' => [],
					'address' => $item['outletAddress']
				];
			}
			return $result;
		} elseif ($data['status'] == 202) {
			return [];
		} else if (!$retry) {
			return $this->getCustomerInfoByNoV2($lat, $lng, $distance, $campaignCode, true);
		}
		throw new \Exception($data['message'], $data['status']);
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
		$result = Cache::get('getCacheCustomerInfoByNoV2' . $lat . $lng . $distance . $campaignCode);
		if ($retry || empty($result)) {
			$result = $this->getCustomerInfoByNoV2($lat, $lng, $distance, $campaignCode, $retry);
			if ($result) {
				Cache::put('getCacheCustomerInfoByNoV2' . $lat . $lng . $distance . $campaignCode, $result, now()->addMinutes(10)); // 缓存 10 分钟
			}
		}
		return $result;
	}
}
