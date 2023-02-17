<?php

namespace App\Librarys;

use Exception;

class BaiduMap
{
    private $ak;

    public function __construct($config = null)
    {
        if ($config === null) $config = config('map.baidu');
        $this->ak = $config['ak'];
        $this->http = new \GuzzleHttp\Client([
            'base_uri' => 'https://api.map.baidu.com/',
            'timeout'  => $config['timeout'] ?? 10,
        ]);
    }

    /**
     * 根据IP返回具体位置
     *
     * @param string $ip
     * @return string
     * @throws \Exception
     */
    public function locationIp($ip)
    {
        $data = $this->request('GET', 'location/ip', [
            'ak' => $this->ak,
            'ip' => $ip,
            'coor' => 'bd09ll'
        ]);

        return $data['content']['address'] ?? '';
    }

    /**
     * 坐标转换
     *
     * @param string $coords
     * @return array
     */
    public function coords($coords, $from = 1, $to = 5)
    {
        $data = $this->request('GET', 'geocoding/v3/', [
            'ak' => $this->ak,
            'output' => 'json',
            'coords' => $coords,
            'from' => $from,
            'to' => $to
        ]);
        return $data['result']['location'];
    }

    /**
     * 地理编码
     *
     * @param string $address
     * @return array
     */
    public function geocoding($address, $ret_coordtype = 'gcj02ll')
    {
        $data = $this->request('GET', 'geocoding/v3/', [
            'ak' => $this->ak,
            'output' => 'json',
            'address' => $address,
            'ret_coordtype' => $ret_coordtype
        ]);
        return $data['result']['location'];
    }

    /**
     * 逆地理编码
     *
     * @param float $lat
     * @param float $lng
     * @return string
     */
    public function reverseGeocoding($lat, $lng, $coordtype = 'gcj02ll')
    {
        $data = $this->request('GET', 'reverse_geocoding/v3/', [
            'ak' => $this->ak,
            'output' => 'json',
            'coordtype' => $coordtype,
            'location' => $lat . ',' . $lng
        ]);

        return $data['result'];
    }

    /**
     * 逆地理编码返回格式化信息
     *
     * @param float $lat
     * @param float $lng
     * @return Array
     */
    public function reverseGeocodingSet($lat, $lng, $coordtype = 'gcj02ll', $retry = false)
    {
        try{
            $data = $this->request('GET', 'reverse_geocoding/v3/', [
                'ak' => $this->ak,
                'output' => 'json',
                'coordtype' => $coordtype,
                'location' => $lat . ',' . $lng
            ]);
        }catch(Exception $e){
            if(!$retry){
                return $this->reverseGeocodingSet($lat, $lng, $coordtype, true);
            }
            throw new Exception($e->getMessage());
        }
        return $data;
    }

    private function request($method, $url, $params)
    {
        if ($method == 'GET') {
            $res = $this->http->request($method, $url, [
                'query' => $params
            ]);
        } elseif ($method == 'POST') {
            $res = $this->http->request($method, $url, [
                'form_params' => $params
            ]);
        }

        if ($res->getStatusCode() != 200) {
            throw new \Exception('网络通讯错误', -1);
        }
        $contents = $res->getBody()->getContents();
        $data = json_decode($contents, true);
        if ($data['status']) {
            throw new \Exception('接口请求错误：'.$contents, $data['status']);
        }
        return $data;
    }
}
