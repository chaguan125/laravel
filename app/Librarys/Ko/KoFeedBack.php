<?php

namespace App\Librarys\Ko;


class KoFeedBack
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
     * 发送短信验证码
     * @param string $phone 需要发送短信验证码的手机号
     * @param string $winId 抽奖结果标示
     * @param string $channel 渠道
     * @param string $membershipId 会员ID
     * @param string $campaignMark 活动标识
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getCaptcha($phone, $winId, $channel, $membershipId, $campaignMark)
    {
        $params = [
            "phone" => $phone,
            "winId" => $winId,
            "channel" => $channel,
            "membershipId" => $membershipId,
            "campaignMark" => $campaignMark,
        ];
        return $this->httpClient->http_post("/cre/open-api/v1/admin/utc/messages/captcha", $params, true);
    }

    /**
     * 问题留资
     * @param string $contactPhone
     * @param string $captcha
     * @param string $contactName
     * @param string $content
     * @param string $latitude
     * @param string $longitude
     * @param string $winId
     * @param string $channel
     * @param string $membershipId
     * @param string $campaignMark
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function feedback($contactPhone, $captcha, $contactName, $content, $latitude, $longitude, $winId, $channel, $membershipId, $campaignMark)
    {
        $params = [
            "contactPhone" => $contactPhone,
            "captcha" => $captcha,
            "contactName" => $contactName,
            "content" => $content,
            "latitude" => $latitude,
            "longitude" => $longitude,
            "winId" => $winId,
            "channel" => $channel ?: 'SWIRE_GT',
            "membershipId" => $membershipId,
            "campaignMark" => $campaignMark,
        ];
        // dump($params);
        return $this->httpClient->http_post("/cre/open-api/v1/admin/utc/lotteries/feedback", $params, true);
    }

}
