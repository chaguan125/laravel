<?php

namespace App\Librarys;

class TencentMap
{
    private $key;
    public $http;

    public function __construct($config = null)
    {
        if ($config === null) $config = config('map.tencent');
        $this->key = $config['key'];
        $this->http = new \GuzzleHttp\Client([
            'base_uri' => 'https://apis.map.qq.com/',
            'timeout' => $config['timeout'] ?? 10,
        ]);
    }

    /**
     * 实现从其它地图供应商坐标系或标准GPS坐标系，批量转换到腾讯地图坐标系
     * @param string $locations 预转换的坐标，支持批量转换，格式：纬度前，经度后，纬度和经度之间用",“分隔，每组坐标之间使用”;"分隔；批量支持坐标个数以HTTP GET方法请求上限为准
     * @param int $type 输入的locations的坐标类型可选值为[1,6]之间的整数，每个数字代表的类型说明：1 GPS坐标 2 sogou 经纬度 3 baidu经纬度 4 mapbar经纬度 5 [默认]腾讯、google、高德坐标 6 sogou墨卡托
     * @return mixed
     * @throws \Exception
     */
    public function translate($locations, $type = 3)
    {
        $data = $this->request('GET', 'ws/coord/v1/translate', [
            'locations' => $locations,
            'type' => $type,
            'key' => $this->key,
            'output' => 'json'
        ]);
        return $data['locations'];
    }

    /**
     * 由经纬度到文字地址及相关位置信息的转换能力
     * @param $lat
     * @param $lng
     * @param int $get_poi 是否返回周边地点（POI）列表，可选值：0 不返回(默认) 1 返回
     * @param string $poi_options 周边POI列表控制参数：
     * @return mixed
     * @throws \Exception
     */
    public function locationToAddress($lat, $lng, $get_poi = 0, $poi_options = null)
    {
        $data = $this->request('GET', 'ws/geocoder/v1/', [
            'location' => $lat . ',' . $lng,
            'key' => $this->key,
            'output' => 'json',
            'get_poi' => $get_poi,
            'poi_options' => $poi_options,
        ]);
        $address_component = $data['result']['address_component'];
        $data['result']['address_details'] =  $data['result']['address'];
        $data['result']['address'] = $address_component['province'] . $address_component['city']. $address_component['district']. $address_component['street'];
        return $data['result'];
    }

    /**
     * 文字地址到经纬度的转换能力
     * @param string $address 地址（注：地址中请包含城市名称，否则会影响解析效果）
     * @param null $region 地址所在城市（若地址中包含城市名称侧可不传）
     * @return mixed
     * @throws \Exception
     */
    public function addressToLocation($address, $region = null)
    {
        $data = $this->request('GET', 'ws/geocoder/v1/', [
            'address' => $address,
            'region' => $region,
            'output' => 'json',
            'key' => $this->key,
        ]);
        return $data['result'];
    }

    private function request($method, $url, $params)
    {
        if ($method == 'GET') {
            $res = $this->http->request($method, $url, [
                'query' => $params
            ]);
        } else {
            $res = $this->http->request($method, $url, [
                'form_params' => $params
            ]);
        }

        if ($res->getStatusCode() != 200) {
            throw new \Exception('网络通讯错误', -1);
        }
        $contents = $res->getBody()->getContents();
        $data = json_decode($contents, true);
        if ($data['status'] != 0) {
            throw new \Exception('腾讯地图接口请求错误：' . $contents, $data['status']);
        }
        return $data;
    }
}
