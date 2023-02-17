<?php

namespace App\Librarys\Ko;


use Illuminate\Support\Facades\Redis;

class KoCoupon
{
    private $type;//P 正式环境 D 测试环境

    public $httpClient;

    /**
     * KoCoupon constructor.
     * @param string $type
     */
    public function __construct($type = 'P')
    {
        $this->type = $type;
        $this->httpClient = new Http($type);
    }

    /**
     * 【商家】获取卡券详情
     * @param string $coupon 券码
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function couponQuery($coupon)
    {
        //返回参数status说明 INACTIVE 未激活 ACTIVE 已激活 RECEIVED 已领取 REDEEMED 已核销 FORBIDDEN 已禁用
        return $this->httpClient->http_get("/cre/open-api/v1/admin/coupons/{$coupon}", [], true);
    }

    /**
     * 【商家】核销卡券
     * @param string $coupon 券号
     * @param string $storeCity 门店所在城市名称
     * @param string $storeId 门店ID
     * @param string $channel 核销渠道
     * @param string $longitude 经度
     * @param string $latitude 纬度
     * @param string $transactionId
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function couponRedeem($coupon, $storeCity, $storeId, $channel, $longitude = '', $latitude = '', $transactionId = '')
    {
        $params = [
            'coupon' => $coupon,
            'storeCity' => $storeCity,
            'storeId' => $storeId,
            'channel' => $channel,
        ];
        if ($longitude) {
            $params['longitude'] = $longitude;
        }
        if ($latitude) {
            $params['latitude'] = $latitude;
        }
        if ($transactionId) {
            $params['transactionId'] = $transactionId;
        }
        return $this->httpClient->http_post("/cre/open-api/v1/admin/coupons/redeem", $params, true);
    }

    /**
     * 【商家】卡券冲正
     * @param string $coupon 券号
     * @param string $storeCity 门店所在城市名称
     * @param string $storeId 门店ID
     * @param string $channel 核销渠道
     * @param string $longitude 经度
     * @param string $latitude 纬度
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function couponReversal($coupon, $storeCity, $storeId, $channel, $longitude = '', $latitude = '')
    {
        $params = [
            'coupon' => $coupon,
            'storeCity' => $storeCity,
            'storeId' => $storeId,
            'channel' => $channel,
        ];
        if ($longitude) {
            $params['longitude'] = $longitude;
        }
        if ($latitude) {
            $params['latitude'] = $latitude;
        }
        return $this->httpClient->http_post("/cre/open-api/v1/admin/coupons/reversal", $params, true);
    }

    /**
     * 【C 端】获取用户在指定活动获取的卡券
     * @param string $membershipId 会员 ID
     * @param string $campaignMark 活动标识
     * @param string $channel 渠道
     * @param string $channelValidation 渠道匹配模式
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function userCoupons($membershipId, $campaignMark, $channel, $channelValidation = 'TYPE_AND_CODE')
    {
        //channelValidation参数说明
        // NONE 不匹配 channel 即取全部数据
        // CODE 仅对 SUBCHANNEL 匹配，例如传入 SWIRE_MT，则会取出 SWIRE_MT 和 COFCO_MT 的数据
        // TYPE 仅对 SWIRE/COFCO 匹配，取 channel = SWIRE_{},COFCO_{}的数据
        // TYPE_AND_CODE channel 全匹配即当传入的 channel 与 在 平台 保存 channel 完全一致时才会返回
        $params = [
            'membershipId' => $membershipId,
            'campaignMark' => $campaignMark,
            'channel' => $channel,
            'channelValidation' => $channelValidation
        ];
        //返回参数status说明 REDEEMABLE 可兑换 REDEEM_EXPIRED 过期未兑换 REDEEMED 已兑换
        return $this->httpClient->http_get("/cre/open-api/v1/admin/coupons", $params, true);
    }

    /**
     * 【C 端】获取卡券详情
     * @param string $membershipId 会员 ID
     * @param string $couponId 券 ID
     * @param string $channel 领取渠道
     * @param string $campaignMark 活动标识
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function couponDetail($membershipId, $couponId, $channel, $campaignMark)
    {
        $params = [
            'membershipId' => $membershipId,
            'couponId' => $couponId,
            'campaignMark' => $campaignMark,
            'channel' => $channel,
        ];
        //返回参数status说明 REDEEMABLE 可兑换 REDEEM_EXPIRED 过期未兑换 REDEEMED 已兑换
        return $this->httpClient->http_post("/cre/open-api/v1/admin/coupons/details", $params, true);
    }

    /**
     * 【C 端】刷新卡券
     * @param string $membershipId 会员 ID
     * @param string $couponId 券 ID
     * @param string $channel 领取渠道
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function couponRefresh($membershipId, $couponId, $channel)
    {
        $params = [
            'membershipId' => $membershipId,
            'couponId' => $couponId,
            'channel' => $channel,
        ];

        $res = $this->httpClient->http_get("/cre/open-api/v1/admin/coupons/refresh", $params, true);
        if (isset($res['status_code']) && $res["status_code"] == 200) {
            $result = $res['result'] ?? [];
            $my_res = $result["data"] ?? [];
            $data = [
                "couponCode" => $my_res["couponCode"] ?? "",//【String】券号（不带域名）
            ];
            if ($data['couponCode']) {
                $this->syncInfo($data['couponCode'], $couponId);//写到redis方便核销接口取数据
            }
        }
        
        return $res;
    }

    /**
     * 【C 端】领取卡券
     * @param string $membershipId 会员 ID
     * @param string $winId 中奖 ID
     * @param string $channel 领取渠道
     * @param string $campaignMark 活动标识
     * @param string $longitude 经度
     * @param string $latitude 纬度
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function couponReceive($membershipId, $winId, $channel, $campaignMark, $longitude = '', $latitude = '')
    {
        $params = [
            'membershipId' => $membershipId,
            'winId' => $winId,
            'channel' => $channel,
            'campaignMark' => $campaignMark
        ];
        if ($longitude) {
            $params['longitude'] = $longitude;
        }
        if ($latitude) {
            $params['latitude'] = $latitude;
        }
        $res = $this->httpClient->http_post("/cre/open-api/v1/admin/coupons/channel/receive", $params, true);
        if (isset($res['status_code']) && $res["status_code"] == 200) {
            $result = $res['result'] ?? [];
            $my_res = $result["data"] ?? [];
            $data = [
                "couponId" => $my_res["couponId"] ?? "",//【String】券 ID
                "couponCode" => $my_res["couponCode"] ?? "",//【String】券号（不带域名）
            ];
            if ($data['couponId'] && $data['couponCode']) {
                $this->syncInfo($data['couponCode'], $data['couponId']);//写到redis方便核销接口取数据
            }
        }
        return $res;
    }

    /**
     * 当前可分享奖品数量
     * @param string $membershipId
     * @param string $channel
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function shareableCount($membershipId, $channel)
    {
        $params = [
            'membershipId' => $membershipId,
            'channel' => $channel,
        ];
        return $this->httpClient->http_get("/cre-bff/c/admin/lotteries/results/shareable-count", $params, true);
    }

    private function syncInfo($couponCode, $couponId)
    {
        $str = 'ko_' . $couponCode;
        Redis::pipeline(function ($pipe) use ($couponId, $str) {
            $pipe->SET($str, $couponId);
            $pipe->EXPIRE($str, 170);//有效期三分钟
        });
    }

    //格式化错误信息
    public static function format_msg($result)
    {
        if(config('ko_act')['ko_env'] == "D"){
            return json_encode($result["error"]);
        }
        $code = $result["error"]["code"] ?? "";
        switch ($code) {
            case 'UNAUTHORIZED':
                $str = '未授权';
                break;
            case 'PERMISSION_DENIED':
                $str = '无权获取信息';
                break;
            case 'COUPON_NOT_FOUND':
                $str = '未找到对应券信息';
                break;
            case 'COUPON_INVALID':
                $str = '非法券';
                break;
            case 'COUPON_EXPIRED':
                $str = '卡券二维码过期，请刷新后重试';
                break;
            case 'SIGNATURE_PARAM_NOT_MATCHED':
                $str = '签名不匹配';
                break;
            case 'SYSTEM_INTERNAL_ERROR':
                $str = '系统内部错误';
                break;
            case 'REDEEM_CITY_NOT_Error':
            case 'REDEEM_CITY_NOT_ERROR':
                $str = '对应城市不允许核销';
                break;
            case 'COUPON_ALREADY_REDEEMED':
                $str = '券已经核销';
                break;
            case 'COUPON_INACTIVE':
                $str = '券未激活';
                break;
            case 'COUPON_FORBIDDEN':
                $str = '券被禁用';
                break;
            case 'OUT_OF_REDEEM_TIME_RANGE':
                $str = '当前时段不允许核销';
                break;
            case 'CHANNEL_NOT_MATCH':
                $str = '渠道不匹配';
                break;
            case 'NO_MEMBERSHIP_ID':
                $str = '会员ID不存在';
                break;
            case 'RESULT_REDEEMED':
            case 'SHARE_REDEEMED':
                $str = '券已领取或奖品已经兑换';
                break;
            case 'RESULT_NOT_FOUND':
                $str = '中奖记录不存在';
                break;
            case 'VALIDATION_FAILED':
                $str = '参数校验异常';
                break;
            case 'MEMBERSHIP_NOT_FOUND':
                $str = '会员不存在';
                break;
            case 'MAX_REQUEST_COUNT_EXCEEDED':
                $str = '发送次数超过限制';
                break;
            case 'RESULT_NO_PRIZE':
                $str = '未中奖';
                break;
            case 'FEEDBACK_EXISTS':
                $str = '该中奖结果已留资';
                break;
            case 'WEDO_ERROR':
                $str = 'wedo接口异常';
                break;
            case 'SHARED_NOT_FOUND':
                $str = '未找到对应的分享';
                break;
            case 'INVALID_VERIFY_CODE':
                $str = '验证码验证失败';
                break;
            default:
                $str = $code . '接口异常';
        }
        return $str;
    }
}
