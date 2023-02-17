<?php

namespace App\Librarys\Ko;


class KoMember
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
     * 获取会员信息
     * @param $membershipId
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getMember($membershipId)
    {
        $params = [
            'membershipId' => $membershipId
        ];
        return $this->httpClient->http_post("/membership/koplus/open-api/v1/admin/members", $params, true);
    }

    /**
     * 解密手机号
     * @param string $str
     * @return false|string
     */
    public function decrypt_mobile($str)
    {
        $aes = new AES($this->type);
        return $aes->str_decrypt($str);
    }
}
