<?php

namespace App\Librarys;

use App\Models\Tenant;
use Illuminate\Support\Facades\Log;

class CareyShopCouponGive
{
    /**
     * 通过openid 及租户信息发放优惠券
     * @param $openid 用户openid
     * @param $couponId mixed 优惠券分类id或优惠券id数组
     * @param int $tenantId 租户id
     * @return array
     * @throws \Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedById
     */
    public static function sendShopCoupon($openid, $couponId, $tenantId = 2, $token = '',$orderId='')
    {
        tenancy()->initialize($tenantId); //设置租户信息
        $currentTenant = Tenant::query()->find($tenantId);
        $careyShopClient = new CareyShopClient($currentTenant->url, 'utc/coupon_give/method/send.user.coupon.api/');
        $params = [
            'token' => '',
            'openid' => $openid,
            'coupon_id' => $couponId,
            'order_id' => $orderId,
        ];
        $result =  $careyShopClient->query($params, true);
        // info($result);
        if (!empty($result['data']) && $result['status'] == 200) {
            $return =  ['status' => true, 'message' => '优惠券发放成功', 'coupon_info' => $result['data']];
        } else {
            Log::info('优惠券发放库优惠券发放失败：', ['result' => $result , 'openid' => $openid, 'couponId' => $couponId, 'tenantId' => $tenantId, 'orderId' => $orderId]);
            $return = ['status' => false,  'message' => $result['message'] ?? '发放优惠券失败'];

        }
        return $return;
    }
}
