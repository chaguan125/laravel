<?php

namespace App\Librarys;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class SwirecocacolazzClient
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
			'P' => 'https://www.swirecocacolazz.com/'
		];
		if ($type != 'P') {
			$this->appId = '9B5AF24C-D1C4-4854-A94F-F6E43FC0DD2C';
			$this->appSecret = '5F85B95A-4DA5-402E-B408-BBF066313F70';
		}
		$this->type = $type;
		$this->http = new \GuzzleHttp\Client([
			'base_uri' => $base[$type],
			'timeout'  => 10,
		]);
	}

	public function getCustomerInfoByNo($outlet_no)
	{
		$res = $this->http->get('UTC/customer/getCustomerInfoByNo/' . implode(',', $outlet_no), [
			'headers' => [
				'x-access-token' => $this->getAccessToken()
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
		}
		throw new \Exception($data['message'], $data['status']);
	}

	public function getAccessToken()
	{
		$token = Cache::get('swirecocacolazz_accesstoken' . $this->type);
		if (empty($token)) {
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
}
