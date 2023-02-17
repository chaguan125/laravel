<?php

namespace App\Librarys;

use App\Librarys\EasyWeChat;
use App\Models\Tenant\ActCouponRecord;
use App\Models\Tenant\AppActInfo;
use App\Models\Tenant\AppIndexAct;
use App\Models\Tenant\Fj\Summer\Coupon;
use App\Models\Tenant\CodeCoupon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\CentralUserCouponRecord;
use App\Models\AppUserMiyaOpenid;
use App\Models\Tenant\Pt\PtGroupCouponRecord;
use Illuminate\Support\Facades\Redis;

class Coupons
{
    const SWIRE_PLATFORM_GQSH = 'swire_platform_gqsh_code_coupon';
    public $error = '';
    /**
     * 领取优惠券
     */
    public function receive($id, $openid, $user_id, $code = '', $tag = 'coupon_act')
    {
        $act = AppIndexAct::select('title', 'desc', 'after_desc', 'data', 'begin', 'end', 'type', 'img','label_id')
            ->where('id', $id)
            // ->where('state', 1) // 活动开启才可以领奖
            ->whereIn('type', [2, 3])
            ->first();
        if (empty($act)) {
            return $this->error('未找到活动信息');
        }
        $now = time();
        if ($act->begin && $act->begin > $now) {
            return $this->error('活动未开始');
        } else if ($act->end && $act->end < $now) {
            return $this->error('活动已结束');
        }
        $info = AppActInfo::select('type', 'coupon_stock_id', 'stock', 'maximum', 'explain', 'stock_creator_mchid', 'miya_status', 'createMchId')
            ->where('id', $id)->where('type', $act->type)->first();
        if (empty($info)) {
            return $this->error('未找到活动详情');
        }
        if ($info->stock <= 0) {
            return $this->error('奖品已被抢完啦！试试看其活动吧！');
        }
        $appUserGiftModel = tenant('id') ? '\App\Models\Tenant\AppUserGift' : '\App\Models\CentralAppUserGift';
        // $user_id = $request->token_data['user_id'];
        if ($info->maximum > 0 && $appUserGiftModel::where('act_id', $id)->where('user_id', $user_id)->count() >= $info->maximum) {
            return $this->error('该奖品每人最多可领取' . $info->maximum . '次');
        }
        $success = AppActInfo::where('id', $id)->where('stock', '>', 0)
            ->update([
                'stock' => DB::raw('stock - 1'),
                'send_out' => DB::raw('send_out + 1')
            ]);
        if (!$success) {
            return $this->error('奖品库存不足');
        }
        $code =  $code ?: date('ymdhis') . substr(explode(' ', microtime())[0], 2, 6) . mt_rand(100, 999); //不存在code时创建code
        $gift_info = [
            'title' => $act->title,
            'desc' => $act->after_desc == '' ? $act->desc : $act->after_desc
        ];
        $gift_data = [];
        $giftState = 0; //0未领取 1已领取 2已使用 3已过期
        switch ($info->type) {
            case 2:  // 微信卡券
                if ($info->miya_status == 1) { //米雅发券
                    $miya = new \App\Librarys\MeiYaClient();
                    $miyaInfo = AppUserMiyaOpenid::where('user_id', $user_id)->first();
                    if (empty($miyaInfo)) {
                        return $this->error('缺少米雅openid');
                    }
                    $createMchId = $info->createMchId ?: '1520952751';
                    $sendMchId = $info->stock_creator_mchid ?: '1242800802';
                    $res = $miya->commonSendNew($miyaInfo->miya_openid, $info->coupon_stock_id, $code, $createMchId, $sendMchId);
                    if ($res['status_code'] == 500) {
                        AppActInfo::where('id', $id)
                            ->update([
                                'stock' => DB::raw('stock + 1'),
                                'send_out' => DB::raw('send_out - 1')
                            ]);
                        return $this->error($res['message'],$res['status_code']);
                    }
                    $result = [
                        'data' => ['coupon_id' => $res['result']['data']]
                    ];
                } else {
                    $app = EasyWeChat::v3PayByMchid($info->stock_creator_mchid);

                    $params = [
                        'openid' => $openid,
                        'stock_id' => $info->coupon_stock_id,
                        'out_request_no' => $code,
                    ];

                    if (!empty($info->stock_creator_mchid)) {
                        $params['stock_creator_mchid'] = $info->stock_creator_mchid; //第三方和自己的券都可以进行发放
                    }

                    try {
                        $result = $app->coupon->send($params);
                    } catch (\Exception $e) {
                        Log::error('支付配置错误', ['message' => $e->getMessage()]);
                        $result = [];
                    }
                    if (!isset($result['status_code'])) {
                        return $this->error('领取失败');
                    }
                    if ($result['status_code'] !== 200) {
                        AppActInfo::where('id', $id)
                            ->update([
                                'stock' => DB::raw('stock + 1'),
                                'send_out' => DB::raw('send_out - 1')
                            ]);
                        switch ($result['data']['code']) {
                            case 'USER_ACCOUNT_ABNORMAL': //帐号行为异常
                                return $this->error('您的微信号被风险管控识阻止，无法领券，请更换其他微信号参与活动。',$result['data']['code']);
                            case 'NOT_ENOUGH': //预算上限
                                return $this->error('很遗憾，今天的卡券已全部发放完毕。',$result['data']['code']);
                            case 'RULE_LIMIT': //已领取过该代金券
                                return $this->error('很遗憾，该优惠券已达领取上限。',$result['data']['code']);
                            case 'FREQUENCY_LIMIT_EXCEED': //超过发放频率限制
                                return $this->error('网络繁忙，请稍后重试。',$result['data']['code']);
                            default:
                                $result['stock_id'] = $info->coupon_stock_id;
                                $result['tenant_id'] = tenant('id');
                                info($params);
                                Log::error($result);
                                if (trim($result['data']['message']) == '活动未开始或已结束') { //活动结束下线领券活动
                                    AppIndexAct::query()->where('id', $id)->update(['state' => 0]);
                                }
                                return $this->error('优惠券发放失败。',$result['data']['code']);
                        }
                    }
                }


                ActCouponRecord::create([
                    'tag' => $tag,
                    'openid' => $openid,
                    'coupon_stock_id' => $info->coupon_stock_id,
                    'coupon_id' => $result['data']['coupon_id'],
                    'partner_trade_no' => $code
                ]);
                $gift_info['money'] = $act->data;
                $gift_data['stock_id'] = $info->coupon_stock_id;
                $gift_data['rule'] = $info->explain;
                $code = $result['data']['coupon_id'];
                break;
            case 3:
                $gift_info['icon'] = $act->img;
                $gift_info['money'] = $act->data;
                $gift_data['gift'] = $act->data;
                $gift_data['rule'] = $act->rule;

                //同一个用户重复领取时间间隔验证
                $attemp = $this->validAttempt($user_id);
                if(!$attemp){
                    return $this->error($this->error);
                }

                //处理用户领取券码
                $result = null;
                try{
                    DB::transaction(function () use ($user_id, $id,&$result,$info,&$code,&$giftState) {
                        $codeCoupon = CodeCoupon::query()
                            ->where('user_id',0)
                            ->where('state',0)
                            ->where('active_id',$id)
                            ->orderBy('id','DESC')
                            ->lockForUpdate()
                            ->first();
                        if($codeCoupon){
                            $codeCoupon->user_id = $user_id;
                            $codeCoupon->active_id = $id;
                            $codeCoupon->state = 1;
                            $giftState = 1;
                            $codeCoupon->save();

                            //处理还有多少待领取
                            $info->decrement('stock');
                            $info->increment('send_out');
                            $code = $codeCoupon->code;
                            $result = $codeCoupon;
                        }else{
                            throw new \Exception('券码已被领完');
                        }
                    });
                }catch (\Exception $e){
                    Log::error('锅圈券码已被领完', ['message' => $e->getMessage()]);
                    return $this->error('券码已被领完');
                }
//                $code = md5($code . $user_id);
                //从数据库中取出最新一条code记录
//                $codeInfo = CodeCoupon::query()->where('active_id',$act->id)->where('state',0)->orderBy('id','desc')->first();
//                if($codeInfo){
//                    $code = $codeInfo->code;
//                    $codeInfo->state = 1;
//                    $codeInfo->save();
//                }
                break;
            default:
                $code = '';
                break;
        }
        $appUserGiftModel::create([
            'user_id' => $user_id,
            'type' => $info->miya_status == 1 ? 4 : $info->type,
            'act_id' => $id,
            'code' => $code,
            'data' => $gift_data,
            'info' => $gift_info,
            'tenant_id' => tenant('id') ?? 0,
            'state' => $giftState,
            'stock_send_mchid' => ($info->type == 2 && $info->miya_status == 0) ?  $app->coupon->getMchid() : 0,
            'label_id' => $act->label_id ?? 0,
        ]);
        return $this->success([
            'coupon_id' => $code,
            'coupon_name' => $gift_info['title'],
            'activity_id' => $id,
        ]);
    }

    /**
     * 验证团购是否开启
     */
    public function validAttempt($userId){
        $lock = Redis::SETNX(self::SWIRE_PLATFORM_GQSH.':'.$userId,1);
        if($lock){
            Redis::EXPIRE(self::SWIRE_PLATFORM_GQSH.':'.$userId,5);
        }else{
            $this->error = '操作频繁，请稍后再试！';
            return false;
        }
        return true;
    }


    public function check($id, $openid)
    {
        $gift = CentralUserCouponRecord::find($id);
        if (empty($gift)) {
            return $this->error('未找到卡券信息');
        }
        if(!is_array($gift->data)){
            $giftData = json_decode($gift->data, true);
        }else{
            $giftData = $gift->data;
        }

        if(!is_array($gift->info)){
            $giftInfo = json_decode($gift->info, true);
        }else{
            $giftInfo = $gift->info;
        }

        $data['rule'] = $giftData['rule'] ?? '';
        $data['info'] = $giftInfo;
        $data['state'] = $gift->state;
        $data['created_at'] = $gift->created_at;
        if($gift->type == 3){
            $data['code'] = $gift->code;
            $data['type'] = $gift->type;
            $end = AppIndexAct::where('id', $gift->act_id)->value('end');
            $data['end'] = date('Y-m-d H:i:s', $end);
        }
        if ($gift->state > 0 || $gift->type == 4) { // 已使用/米雅卡券
            return $this->success($data);
        }

        //查询的时候取到正确的支付信息
        if ($gift->tenant_id) {
            tenancy()->initialize($gift->tenant_id);
        }

        if ($gift->type == 3) { // type = 3 暂时未用到
            $end = AppIndexAct::where('id', $gift->act_id)->value('end');
            if ($end && $end < time()) {
                $gift->state = 4;
                $gift->save(); //券码暂时不处理过期
            } else {
                $data['code'] = $gift->code;
            }
            $data['state'] = $gift->state;
            $data['end'] = date('Y-m-d H:i:s', $end);
            return $this->success($data);
        }

        if($gift->stock_send_mchid){
            $payment = EasyWeChat::v3PayByMchid($gift->stock_send_mchid);
        }else{
            $payment = EasyWeChat::v3Pay();
        }

        $res = $payment->coupon->info([
            'coupon_id' => $gift->code,
            'openid' => $openid,
        ]);

        if ($res['status_code'] === 200) {
            switch ($res['data']['status']) {
                case 'SENDED':
                    $data['state'] = 0;
                    return $this->success($data);
                case 'USED':
                    $data['state'] = 1;
                    break;
                case 'EXPIRED':
                    $data['state'] = 2;
                    break;
                default:
                    return $this->error('查询失败，请稍后再试。');
            }
            $gift->state = $data['state'];
            $gift->save();
            //更新拼团活动的优惠券使用状态
            PtGroupCouponRecord::query()->where('coupon_id', $gift->code)->update(['is_used'=> $data['state'],'updated_at'=>date('Y-m-d H:i:s')]);
             if($gift->tenant_id==3){ //更改夏日活动券的状态
                 Coupon::query()->where('coupon_id', $gift->code)->update(['is_used'=> $data['state'],'updated_at'=>date('Y-m-d H:i:s')]);
             }
            return $this->success($data);
        } else {
            Log::error('查询券状态出错', $res);
            return $this->error($res['data']['message'] ?? '网络错误');
        }
    }

    private function error($msg,$err_code='')
    {
        return ['code' => 400, 'msg' => $msg,'err_code'=>$err_code];
    }

    private function success($data)
    {
        return ['code' => 200, 'msg' => 'success', 'data' => $data];
    }
}
