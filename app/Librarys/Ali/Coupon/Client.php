<?php
namespace App\Librarys\Ali\Coupon;

use AlibabaCloud\Tea\Tea;
use AlibabaCloud\Tea\Request;
use AlibabaCloud\Tea\Exception\TeaError;
use \Exception;
use AlibabaCloud\Tea\Exception\TeaUnableRetryError;
use App\Librarys\Ali\Coupon\Models\AlipayMarketingMaterialImageUploadResponse;
use App\Librarys\Ali\Coupon\Models\AlipayUserCashlessFixVoucherTemplateCreateResponse;
use App\Librarys\Ali\Coupon\Models\AlipayUserCouponSendResponse;
use App\Librarys\Ali\Coupon\Models\AlipayUserTemplateCreateResponse;

class Client {
    protected $_kernel;

    public function __construct($kernel){
        $this->_kernel = $kernel;
    }

    /**
     * 发放卡券
     * @param string $subject
     * @param string $outTradeNo
     * @param string $totalAmount
     * @param string $buyerId
     * @return AlipayUserCouponSendResponse
     * @throws TeaError
     * @throws Exception
     * @throws TeaUnableRetryError
     */
    public function send($templateId, $userId, $outBizNo, $amount = 0.00, $memo = '', $extendInfo = []){
        $_runtime = [
            "ignoreSSL" => $this->_kernel->getConfig("ignoreSSL"),
            "httpProxy" => $this->_kernel->getConfig("httpProxy"),
            "connectTimeout" => 15000,
            "readTimeout" => 15000,
            "retry" => [
                "maxAttempts" => 0
            ]
        ];
        $_lastRequest = null;
        $_lastException = null;
        $_now = time();
        $_retryTimes = 0;
        while (Tea::allowRetry(@$_runtime["retry"], $_retryTimes, $_now)) {
            if ($_retryTimes > 0) {
                $_backoffTime = Tea::getBackoffTime(@$_runtime["backoff"], $_retryTimes);
                if ($_backoffTime > 0) {
                    Tea::sleep($_backoffTime);
                }
            }
            $_retryTimes = $_retryTimes + 1;
            try {
                $_request = new Request();
                $systemParams = [
                    "method" => "alipay.marketing.voucher.send",
                    "app_id" => $this->_kernel->getConfig("appId"),
                    "timestamp" => $this->_kernel->getTimestamp(),
                    "format" => "json",
                    "version" => "1.0",
                    "alipay_sdk" => $this->_kernel->getSdkVersion(),
                    "charset" => "UTF-8",
                    "sign_type" => $this->_kernel->getConfig("signType"),
                    "app_cert_sn" => $this->_kernel->getMerchantCertSN(),
                    "alipay_root_cert_sn" => $this->_kernel->getAlipayRootCertSN()
                ];
                $bizParams = [
                    "template_id" => $templateId,
                    "user_id" => $userId,
                    "out_biz_no" => $outBizNo,
                    "amount" => $amount,
                    "memo" => $memo
                ];
                if($bizParams['amount'] == 0.00){
                    unset($bizParams['amount']);
                }
                if($extendInfo){
                    $bizParams['extend_info'] = json_encode($extendInfo, JSON_UNESCAPED_UNICODE);
                }

                $textParams = [];
                $_request->protocol = $this->_kernel->getConfig("protocol");
                $_request->method = "POST";
                $_request->pathname = "/gateway.do";
                $_request->headers = [
                    "host" => $this->_kernel->getConfig("gatewayHost"),
                    "content-type" => "application/x-www-form-urlencoded;charset=utf-8"
                ];
                $_request->query = $this->_kernel->sortMap(Tea::merge([
                    "sign" => $this->_kernel->sign($systemParams, $bizParams, $textParams, $this->_kernel->getConfig("merchantPrivateKey"))
                ], $systemParams, $textParams));
                $_request->body = $this->_kernel->toUrlEncodedRequestBody($bizParams);
                $_lastRequest = $_request;
                $_response= Tea::send($_request, $_runtime);
                $respMap = $this->_kernel->readAsJson($_response, "alipay.marketing.voucher.send");
                if ($this->_kernel->isCertMode()) {
                    if ($this->_kernel->verify($respMap, $this->_kernel->extractAlipayPublicKey($this->_kernel->getAlipayCertSN($respMap)))) {
                        return AlipayUserCouponSendResponse::fromMap($this->_kernel->toRespModel($respMap));
                    }
                }
                else {
                    if ($this->_kernel->verify($respMap, $this->_kernel->getConfig("alipayPublicKey"))) {
                        return AlipayUserCouponSendResponse::fromMap($this->_kernel->toRespModel($respMap));
                    }
                }
                throw new TeaError([
                    "message" => "验签失败，请检查支付宝公钥设置是否正确。"
                ]);
            }
            catch (Exception $e) {
                if (!($e instanceof TeaError)) {
                    $e = new TeaError([], $e->getMessage(), $e->getCode(), $e);
                }
                if (Tea::isRetryable($e)) {
                    $_lastException = $e;
                    continue;
                }
                throw $e;
            }
        }
        throw new TeaUnableRetryError($_lastRequest, $_lastException);
    }


    /**
     * 无资金券模板创建
     * @param string $brandName 优惠券名称
     * @param string $publishStartTime 发放开始时间
     * @param string $publishEndTime 发放结束时间
     * @param integer $validPeriodDuration 领取后几天内有效
     * @param array $availableTimedayRule 使用星期限制 [1,2,3,4,5,6,7] 表示周一至周日都可用
     * @param string $availableTimeBegin 可用开始时间 H:i:s
     * @param string $availableTimeEnd 可用结束时间 H:i:s
     * @param string $outBizNo 外部业务单号 H:i:s
     * @param string $voucherDescription 券使用说明
     * @param integer $voucherQuantity 发行数量
     * @param float $amount 优惠券面额
     * @param float $floorAmount 使用门槛
     * @param array $product_no 产品编码
     * @param string $nofityUrl 通知地址
     * @return AlipayUserTemplateCreateResponse
     * @throws TeaError
     * @throws Exception
     * @throws TeaUnableRetryError
     */
    public function templateCreate($brandName, $publishStartTime, $publishEndTime, $validPeriodDuration,
        $availableTimedayRule, $availableTimeBegin, $availableTimeEnd, $outBizNo, $voucherDescription, $voucherQuantity,
        $amount, $floorAmount, $product_no = [], $nofityUrl = ''){
            $_runtime = [
                "ignoreSSL" => $this->_kernel->getConfig("ignoreSSL"),
                "httpProxy" => $this->_kernel->getConfig("httpProxy"),
                "connectTimeout" => 15000,
                "readTimeout" => 15000,
                "retry" => [
                    "maxAttempts" => 0
                ]
            ];
            $_lastRequest = null;
            $_lastException = null;
            $_now = time();
            $_retryTimes = 0;
            while (Tea::allowRetry(@$_runtime["retry"], $_retryTimes, $_now)) {
                if ($_retryTimes > 0) {
                    $_backoffTime = Tea::getBackoffTime(@$_runtime["backoff"], $_retryTimes);
                    if ($_backoffTime > 0) {
                        Tea::sleep($_backoffTime);
                    }
                }
                $_retryTimes = $_retryTimes + 1;
                try {
                    $_request = new Request();
                    $systemParams = [
                        "method" => "alipay.marketing.cashlessvoucher.template.create",
                        "app_id" => $this->_kernel->getConfig("appId"),
                        "timestamp" => $this->_kernel->getTimestamp(),
                        "format" => "json",
                        "version" => "1.0",
                        "alipay_sdk" => $this->_kernel->getSdkVersion(),
                        "charset" => "UTF-8",
                        "sign_type" => $this->_kernel->getConfig("signType"),
                        "app_cert_sn" => $this->_kernel->getMerchantCertSN(),
                        "alipay_root_cert_sn" => $this->_kernel->getAlipayRootCertSN()
                    ];


                    $bizParams = [
                        "voucher_type" => 'CASHLESS_FIX_VOUCHER', //先仅支持定额券
                        "brand_name" => $brandName,
                        "publish_start_time" => $publishStartTime,
                        'publish_end_time' => $publishEndTime,
                        'voucher_valid_period' => json_encode([ //暂时只支持领取后几天有效
                            'type' => 'RELATIVE',
                            'duration' => $validPeriodDuration,
                            'unit' => 'DAY'
                        ], JSON_UNESCAPED_UNICODE),
                        'voucher_available_time' => json_encode([[
                            'day_rule' => implode(",", $availableTimedayRule),
                            'time_begin' => $availableTimeBegin,
                            'time_end' => $availableTimeEnd,
                        ]]),
                        'out_biz_no' => $outBizNo,
                        'voucher_description' => json_encode(explode('\n', $voucherDescription), JSON_UNESCAPED_UNICODE),
                        'voucher_quantity' => $voucherQuantity,
                        'amount' => $amount,
                        'floor_amount' => $floorAmount,
                        'notify_url' => $nofityUrl
                    ];

                    if(empty($bizParams['notify_url'])){
                        unset($bizParams['notify_url']);
                    }

                    if(!empty($product_no)){
                        $bizParams['rule_conf'] = json_encode([
                            'BIZ_PRODUCT' => implode(",", $product_no),
                            'PID' => config("ali.pid")
                        ]);
                    }else{
                        $bizParams['rule_conf'] = json_encode([
                            'PID' => config("ali.pid")
                        ]);
                    }
                    $textParams = [];
                    $_request->protocol = $this->_kernel->getConfig("protocol");
                    $_request->method = "POST";
                    $_request->pathname = "/gateway.do";
                    $_request->headers = [
                        "host" => $this->_kernel->getConfig("gatewayHost"),
                        "content-type" => "application/x-www-form-urlencoded;charset=utf-8"
                    ];
                    $_request->query = $this->_kernel->sortMap(Tea::merge([
                        "sign" => $this->_kernel->sign($systemParams, $bizParams, $textParams, $this->_kernel->getConfig("merchantPrivateKey"))
                    ], $systemParams, $textParams));
                    $_request->body = $this->_kernel->toUrlEncodedRequestBody($bizParams);
                    $_lastRequest = $_request;
                    $_response= Tea::send($_request, $_runtime);
                    $respMap = $this->_kernel->readAsJson($_response, "alipay.marketing.cashlessvoucher.template.create");
                    if ($this->_kernel->isCertMode()) {
                        if ($this->_kernel->verify($respMap, $this->_kernel->extractAlipayPublicKey($this->_kernel->getAlipayCertSN($respMap)))) {
                            return AlipayUserTemplateCreateResponse::fromMap($this->_kernel->toRespModel($respMap));
                        }
                    }
                    else {
                        if ($this->_kernel->verify($respMap, $this->_kernel->getConfig("alipayPublicKey"))) {
                            return AlipayUserTemplateCreateResponse::fromMap($this->_kernel->toRespModel($respMap));
                        }
                    }
                    throw new TeaError([
                        "message" => "验签失败，请检查支付宝公钥设置是否正确。"
                    ]);
                }
                catch (Exception $e) {
                    if (!($e instanceof TeaError)) {
                        $e = new TeaError([], $e->getMessage(), $e->getCode(), $e);
                    }
                    if (Tea::isRetryable($e)) {
                        $_lastException = $e;
                        continue;
                    }
                    throw $e;
                }
            }
            throw new TeaUnableRetryError($_lastRequest, $_lastException);
    }

    /**
     * 创建单品模板
     *
     * @param string $brandName 卡券名称
     * @param string $goodsName 商品名称
     * @param string $goodsInfo 商品信息
     * @param string $goodsIds 商品id，以逗号分隔
     * @param float $floorAmount 最低使用门槛
     * @param string $goodsCoverImageId 商品封图
     * @param string $voucherDescription 券使用说明
     * @param string $publishStartTime 发券开始时间
     * @param string $publishEndTime 发券结束时间
     * @param integer $validPeriodDuration 券领取后多少天有效
     * @param array $availableTimedayRule 使用星期限制 [1,2,3,4,5,6,7] 表示周一至周日都可用
     * @param string $availableTimeBegin  可用开始时间 H:i:s
     * @param string $availableTimeEnd  可用结束时间 H:i:s
     * @param string $goods_detail_image_ids 单品券详情图片
     * @param float $amount 代金券面额
     * @param integer $voucherQuantity 券发放数量
     * @param integer $outBizNo 外部业务单号
     * @param array $pids 商户id
     * @param array $storeIds 店铺id
     * @param string $notifyUri 券变动异步通知地址
     * @return AlipayUserCashlessFixVoucherTemplateCreateResponse
     */
    public function cashlessFixVoucherTemplateCreate(string $brandName, string $goodsName, string $goodsInfo, string $goodsIds,
        float $floorAmount, string $goodsCoverImageId, string $voucherDescription, string $publishStartTime, string $publishEndTime,
        int $validPeriodDuration = 0, array $availableTimedayRule, string $availableTimeBegin, string $availableTimeEnd,
        float $amount, string $voucherQuantity, string $outBizNo, string $notifyUri = '', string $goods_detail_image_ids = '',
         array $pids = [], array $storeIds = []){
        $_runtime = [
            "ignoreSSL" => $this->_kernel->getConfig("ignoreSSL"),
            "httpProxy" => $this->_kernel->getConfig("httpProxy"),
            "connectTimeout" => 15000,
            "readTimeout" => 15000,
            "retry" => [
                "maxAttempts" => 0
            ]
        ];
        $_lastRequest = null;
        $_lastException = null;
        $_now = time();
        $_retryTimes = 0;
        while (Tea::allowRetry(@$_runtime["retry"], $_retryTimes, $_now)) {
            if ($_retryTimes > 0) {
                $_backoffTime = Tea::getBackoffTime(@$_runtime["backoff"], $_retryTimes);
                if ($_backoffTime > 0) {
                    Tea::sleep($_backoffTime);
                }
            }
            $_retryTimes = $_retryTimes + 1;
            try {
                $_request = new Request();
                $systemParams = [
                    "method" => "alipay.marketing.cashlessitemvoucher.template.create",
                    "app_id" => $this->_kernel->getConfig("appId"),
                    "timestamp" => $this->_kernel->getTimestamp(),
                    "format" => "json",
                    "version" => "1.0",
                    "alipay_sdk" => $this->_kernel->getSdkVersion(),
                    "charset" => "UTF-8",
                    "sign_type" => $this->_kernel->getConfig("signType"),
                    "app_cert_sn" => $this->_kernel->getMerchantCertSN(),
                    "alipay_root_cert_sn" => $this->_kernel->getAlipayRootCertSN()
                ];

                $bizParams = [
                    "voucher_type" => 'ITEM_CASHLESS_FIX_VOUCHER',
                    "brand_name" => $brandName,
                    "goods_cover_image_id" => $goodsCoverImageId,
                    "goods_name" => $goodsName,
                    "goods_info" => $goodsInfo,
                    "goods_id" => $goodsIds,
                    'floor_amount' => $floorAmount,
                    'voucher_description' => json_encode(array_map(function($item){
                        return trim($item);
                    }, array_slice(explode("\n", $voucherDescription), 0, 10)), JSON_UNESCAPED_UNICODE),

                    "publish_start_time" => $publishStartTime,
                    'publish_end_time' => $publishEndTime,
                    'voucher_available_time' => json_encode([[
                        'day_rule' => implode(",", $availableTimedayRule),
                        'time_begin' => $availableTimeBegin,
                        'time_end' => $availableTimeEnd,
                    ]]),
                    'out_biz_no' => $outBizNo,
                    'voucher_quantity' => $voucherQuantity,
                    'amount' => $amount,
                ];

                //不为空时，是领取多少天后有效
                if(!empty($validPeriodDuration)){
                    $bizParams['voucher_valid_period'] = json_encode([
                        'type' => 'RELATIVE',
                        'duration' => $validPeriodDuration,
                        'unit' => 'DAY'
                    ], JSON_UNESCAPED_UNICODE);
                }else{
                    $bizParams['voucher_valid_period'] = json_encode([
                        'type' => 'ABSOLUTE',
                        'start' => $publishStartTime,
                        'end' => $publishEndTime,
                    ], JSON_UNESCAPED_UNICODE);
                }

                if($notifyUri){
                    $bizParams['notify_uri'] = $notifyUri;
                }
                if(!empty($pids)){
                    array_push($pids, config("ali.pid"));
                    $bizParams['rule_conf'] = json_encode([
                        'PID' => implode(",", $pids)
                    ]);
                }else{
                    $bizParams['rule_conf'] = json_encode([
                        'PID' => config("ali.pid")
                    ]);
                }

                if(!empty($storeIds)){
                    $bizParams['rule_conf'] = json_encode([
                        'STORE' => implode(",", $storeIds),
                    ]);
                }
                echo json_encode($bizParams,true);

                $textParams = [];
                $_request->protocol = $this->_kernel->getConfig("protocol");
                $_request->method = "POST";
                $_request->pathname = "/gateway.do";
                $_request->headers = [
                    "host" => $this->_kernel->getConfig("gatewayHost"),
                    "content-type" => "application/x-www-form-urlencoded;charset=utf-8"
                ];

                $_request->query = $this->_kernel->sortMap(Tea::merge([
                    "sign" => $this->_kernel->sign($systemParams, $bizParams, $textParams, $this->_kernel->getConfig("merchantPrivateKey"))
                ], $systemParams, $textParams));
                $_request->body = $this->_kernel->toUrlEncodedRequestBody($bizParams);
                $_lastRequest = $_request;
                $_response= Tea::send($_request, $_runtime);
                $respMap = $this->_kernel->readAsJson($_response, "alipay.marketing.cashlessitemvoucher.template.create");
                if ($this->_kernel->isCertMode()) {
                    if ($this->_kernel->verify($respMap, $this->_kernel->extractAlipayPublicKey($this->_kernel->getAlipayCertSN($respMap)))) {
                        return AlipayUserCashlessFixVoucherTemplateCreateResponse::fromMap($this->_kernel->toRespModel($respMap));
                    }
                }
                else {
                    if ($this->_kernel->verify($respMap, $this->_kernel->getConfig("alipayPublicKey"))) {
                        return AlipayUserCashlessFixVoucherTemplateCreateResponse::fromMap($this->_kernel->toRespModel($respMap));
                    }
                }
                throw new TeaError([
                    "message" => "验签失败，请检查支付宝公钥设置是否正确。"
                ]);
            }
            catch (Exception $e) {
                if (!($e instanceof TeaError)) {
                    $e = new TeaError([], $e->getMessage(), $e->getCode(), $e);
                }
                if (Tea::isRetryable($e)) {
                    $_lastException = $e;
                    continue;
                }
                throw $e;
            }
        }
        throw new TeaUnableRetryError($_lastRequest, $_lastException);
    }

     /**
     * @param string $imageFilePath
     * @return AlipayMarketingMaterialImageUploadResponse
     * @throws TeaError
     * @throws Exception
     * @throws TeaUnableRetryError
     */
    public function upload($imageFilePath){
        $_runtime = [
            "ignoreSSL" => $this->_kernel->getConfig("ignoreSSL"),
            "httpProxy" => $this->_kernel->getConfig("httpProxy"),
            "connectTimeout" => 100000,
            "readTimeout" => 100000,
            "retry" => [
                "maxAttempts" => 0
            ]
        ];
        $_lastRequest = null;
        $_lastException = null;
        $_now = time();
        $_retryTimes = 0;
        while (Tea::allowRetry(@$_runtime["retry"], $_retryTimes, $_now)) {
            if ($_retryTimes > 0) {
                $_backoffTime = Tea::getBackoffTime(@$_runtime["backoff"], $_retryTimes);
                if ($_backoffTime > 0) {
                    Tea::sleep($_backoffTime);
                }
            }
            $_retryTimes = $_retryTimes + 1;
            try {
                $_request = new Request();
                $systemParams = [
                    "method" => "alipay.marketing.material.image.upload",
                    "app_id" => $this->_kernel->getConfig("appId"),
                    "timestamp" => $this->_kernel->getTimestamp(),
                    "format" => "json",
                    "version" => "1.0",
                    "alipay_sdk" => $this->_kernel->getSdkVersion(),
                    "charset" => "UTF-8",
                    "sign_type" => $this->_kernel->getConfig("signType"),
                    "app_cert_sn" => $this->_kernel->getMerchantCertSN(),
                    "alipay_root_cert_sn" => $this->_kernel->getAlipayRootCertSN()
                ];
                $bizParams = [];
                $textParams = [];
                $fileParams = [
                    "file_content" => $imageFilePath
                ];
                $boundary = $this->_kernel->getRandomBoundary();
                $_request->protocol = $this->_kernel->getConfig("protocol");
                $_request->method = "POST";
                $_request->pathname = "/gateway.do";
                $_request->headers = [
                    "host" => $this->_kernel->getConfig("gatewayHost"),
                    "content-type" => $this->_kernel->concatStr("multipart/form-data;charset=utf-8;boundary=", $boundary)
                ];

                $_request->query = $this->_kernel->sortMap(Tea::merge([
                    "sign" => $this->_kernel->sign($systemParams, $bizParams, $textParams, $this->_kernel->getConfig("merchantPrivateKey"))
                ], $systemParams));
                $_request->body = $this->_kernel->toMultipartRequestBody($textParams, $fileParams, $boundary);
                $_lastRequest = $_request;
                $_response= Tea::send($_request, $_runtime);
                $respMap = $this->_kernel->readAsJson($_response, "alipay.marketing.material.image.upload");
                if ($this->_kernel->isCertMode()) {
                    if ($this->_kernel->verify($respMap, $this->_kernel->extractAlipayPublicKey($this->_kernel->getAlipayCertSN($respMap)))) {
                        return AlipayMarketingMaterialImageUploadResponse::fromMap($this->_kernel->toRespModel($respMap));
                    }
                }
                else {
                    if ($this->_kernel->verify($respMap, $this->_kernel->getConfig("alipayPublicKey"))) {
                        return AlipayMarketingMaterialImageUploadResponse::fromMap($this->_kernel->toRespModel($respMap));
                    }
                }
            }
            catch (Exception $e) {
                if (!($e instanceof TeaError)) {
                    $e = new TeaError([], $e->getMessage(), $e->getCode(), $e);
                }
                if (Tea::isRetryable($e)) {
                    $_lastException = $e;
                    continue;
                }
                throw $e;
            }
        }
        throw new TeaUnableRetryError($_lastRequest, $_lastException);
    }
}
