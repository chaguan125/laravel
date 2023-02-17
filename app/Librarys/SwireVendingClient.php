<?php

namespace App\Librarys;


class SwireVendingClient
{
	private $key = '085cc06df3274ea8b973d332c30bdf33';
	private $http;
	private $type;


	public function __construct()
	{
		$base = [
			'D' => 'https://colvqnr.app.swiretest.com', // 测试环境
			'P' => 'https://colv.app.swirebev.com'   // 正式环境
		];
		$this->type = config('ko_act.ko_env');
		$this->http = new \GuzzleHttp\Client([
			'base_uri' => $base[$this->type],
			'timeout'  => 10,
			'http_errors' => false
		]);
	}


	/**
	 * 3.3.核销状态通知
	 * @param array $params 
	 * @return void
	 */
	public function consumeNotify(array $params)
	{
		try {
			$params = $this->setSign($params);
			$res = $this->http->post('/api/utc/consumeNotify', [
				'headers' => [
					'Authorization' => 'application/json;charset=UTF-8',
					'Charset' => 'utf-8'
				],
				'json' => $params
			]);
			$statusCode = $res->getStatusCode();
			$contents = $res->getBody()->getContents();
			$result = json_decode($contents, 1);
			$resultSign = $this->sign($result);
			if ($statusCode == 200 && $result['code'] === 0 && $resultSign) {
				return ['status_code' => 200, 'message' => 'success', 'result' => $result];
			} else { // 其他异常响应：
				return ['status_code' => 400, 'message' => $resultSign ? $result['msg'] : '签名错误'];
			}
		} catch (\Exception $e) {  // 报错 按照没有数据处理
			info($e->getMessage());
			return ['status_code' => 500, 'message' => '服务器内部错误-2'];
		}
	}

	/**
	 * 3.5.查询出货状态
	 * @param array $params  customerNo =>客户号 orderId=>订单号
	 * @return void
	 */
	public function queryStatus(array $params)
	{
		// return ['status_code' => 400, 'message' => 'success', 'result' => [
		// 	"msg" => "success",
		// 	"code" => 0,
		// 	"sign" => "2eba97281bbbac5e0b6be170ce260a69",
		// 	"status" => 0,
		// 	"orderId" => "05100162121617860403576"
		// ]];

		try {
			$params['timestamp'] = time();
			$params = $this->setSign($params);
			$res = $this->http->post('/api/utc/queryStatus', [
				'headers' => [
					'Authorization' => 'application/json;charset=UTF-8',
					'Charset' => 'utf-8'
				],
				'json' => $params
			]);
			$statusCode = $res->getStatusCode();
			$contents = $res->getBody()->getContents();
			$result = json_decode($contents, 1);
			$resultSign = $this->sign($result);
			if ($statusCode == 200 && $result['code'] === 0 && $resultSign) {
				return ['status_code' => 200, 'message' => 'success', 'result' => $result];
			} else { // 其他异常响应：
				return ['status_code' => 400, 'message' => $resultSign ? $result['msg'] : '签名错误'];
			}
		} catch (\Exception $e) {  // 报错 按照没有数据处理
			info($e->getMessage());
			return ['status_code' => 500, 'message' => '服务器内部错误-2'];
		}
	}

	/**
	 * 验证签名
	 */
	public function sign($params): bool
	{
		if (empty($params['sign'])) {
			return false;
		}
		$sign = $params['sign'];
		unset($params['sign']);
		ksort($params);
		$type = ['array', 'object', 'NULL'];
		$stringToBeSigned = '';
		foreach ($params as $key => $val) {
			if (!in_array(gettype($val), $type)) {
				$stringToBeSigned .=  $key . '=' . $val .  '&';
			}
		}

		$stringToBeSigned .= $this->key;
		if (!hash_equals(md5($stringToBeSigned), $sign)) {
			return false;
		}
		return true;
	}

	/**
	 * 计算签名
	 */
	public function setSign($params)
	{
		ksort($params);
		$type = ['array', 'object', 'NULL'];
		$stringToBeSigned = '';
		foreach ($params as $key => $val) {
			if (!in_array(gettype($val), $type)) {
				$stringToBeSigned .=  $key . '=' . $val . '&';
			}
		}
		$stringToBeSigned .= $this->key;
		$params['sign'] = md5($stringToBeSigned);
		return $params;
	}
}
