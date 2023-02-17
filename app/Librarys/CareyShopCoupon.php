<?php

namespace App\Librarys;

use Illuminate\Support\Facades\Redis;

class CareyShopCoupon
{
    //生产订单号
    public static function codeNo()
    {
        $now = date('YmdHis');
        $res = Redis::pipeline(function ($pipe) use ($now) {
            $pipe->INCR('cs_coupon_code' . $now);
            $pipe->EXPIRE('cs_coupon_code' . $now, 60);
        });
        return $now . str_pad($res[0], 4, '0', STR_PAD_LEFT);
    }

}
