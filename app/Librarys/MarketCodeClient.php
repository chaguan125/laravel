<?php

namespace App\Librarys;

use EasyWeChat\Kernel\BaseClient;
use Illuminate\Support\Facades\Storage;

/**
 * 一物一码
 */
class MarketCodeClient extends BaseClient
{
    protected $baseUri = 'https://api.weixin.qq.com/intp/marketcode/';

    /**
     * 申请二维码
     *
     * @param int $code_count
     * @param string $isv_application_id
     * @return \Psr\Http\Message\ResponseInterface|\EasyWeChat\Kernel\Support\Collection|array|object|string
     */
    public function applyCode($code_count, $isv_application_id)
    {
        $res = $this->httpPostJson('applycode', [
            'code_count' => $code_count,
            'isv_application_id' => $isv_application_id
        ]);
        return $res;
    }

    /**
     * 查询二维码申请单
     *
     * @param int $code_count
     * @param string $isv_application_id
     * @return \Psr\Http\Message\ResponseInterface|\EasyWeChat\Kernel\Support\Collection|array|object|string
     */
    public function applyCodeQuery($isv_application_id = '', $application_id = '')
    {
        $params = $application_id && $isv_application_id ? [
            'application_id' => $application_id,
            'isv_application_id' => $isv_application_id
        ] : ($application_id ? ['application_id' => $application_id] : ['isv_application_id' => $isv_application_id]);
        $res = $this->httpPostJson('applycodequery', $params);
        return $res;
    }

    /**
     * 下载二维码包
     *
     * @param int $application_id
     * @param int $code_start 起始坐标
     * @param int $code_end 结束坐标
     * @return array [lattice,code,index,url]关联数组
     */
    public function applyCodeDownload($application_id, $code_start, $code_end)
    {
        $key = $this->app->config->get('market_code_key');
        $res = $this->httpPostJson('applycodedownload', [
            'application_id' => $application_id,
            'code_start' => $code_start,
            'code_end' => $code_end
        ]);
        $buffer = base64_decode($res['buffer']);
        $res = openssl_decrypt($buffer, 'AES-128-CBC', $key, OPENSSL_CIPHER_AES_128_CBC, $key);
        if ($res === false) {
            throw new \Exception('解密失败', 500);
        }
        $res = explode("\n", trim($res));
        return array_map(function ($row) {
            $row = explode("\t", $row);
            return [
                'lattice' => $row[0],
                'code' => $row[1],
                'index' => $row[2],
                'url' => $row[3],
            ];
        }, $res);
    }

    /**
     * 从文件载入二维码
     *
     * @param string $path 文件路径
     * @return array [lattice,code,index,url]关联数组
     */
    public static function applyCodePath($path){
        $res = Storage::disk('local')->get($path);
        if (empty($res)) {
            throw new \Exception('文件不存在', 500);
        }

        $res = explode("\n", trim($res));
        return array_map(function ($row) {
            $row = explode("\t", $row);
            return [
                'lattice' => $row[0],
                'code' => $row[1],
                'index' => $row[2],
                'url' => $row[3],
            ];
        }, $res);
    }

    /**
     * 激活二维码
     *
     * @param int $application_id 申请单号
     * @param string $activity_name 活动名称
     * @param string $product_brand 商品品牌
     * @param string $product_title 商品标题
     * @param string $product_code 商品条码
     * @param string $wxa_appid
     * @param string $wxa_path
     * @param int $code_start
     * @param int $code_end
     * @param int $wxa_type 0正式版，开发版为1，体验版为2
     * @return bool
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function codeActive($application_id, string $activity_name, string $product_brand, string $product_title, $product_code, $wxa_appid, $wxa_path, int $code_start, int $code_end, int $wxa_type)
    {
        $res = $this->httpPostJson('codeactive', compact('application_id', 'activity_name', 'product_brand', 'product_title', 'product_code', 'wxa_appid', 'wxa_path', 'code_start', 'code_end', 'wxa_type'));
        if (!isset($res['errcode']) || $res['errcode'] != 0) {
            throw new \Exception($res['errmsg'] ?? '网络错误', $res['errcode'] ?? 500);
        }
        return true;
    }

    /**
     * code_ticket换code
     *
     * @param string $openid
     * @param string $code_ticket
     * @return \Psr\Http\Message\ResponseInterface|\EasyWeChat\Kernel\Support\Collection|array|object|string
     */
    public function ticketToCode($openid, $code_ticket)
    {
        return $this->httpPostJson('tickettocode', [
            'openid' => $openid,
            'code_ticket' => $code_ticket
        ]);
    }

    /**
     * 查询二维码激活状态接口
     *
     * @param array $params
     *
     * @return \Psr\Http\Message\ResponseInterface|\EasyWeChat\Kernel\Support\Collection|array|object|string
     */
    public function codeActiveQuery($params = [])
    {
        $res = $this->httpPostJson('codeactivequery', $params);
        if (!isset($res['errcode']) || $res['errcode'] != 0) {
            throw new \Exception($res['errmsg'] ?? '网络错误', $res['errcode'] ?? 500);
        }
        return $res;
    }
}
