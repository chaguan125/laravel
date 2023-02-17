<?php

namespace App\Http\Controllers;

use App\Libraries\BocWechatFacades;
use App\Models\VoteUser;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Redis;

class Controller extends BaseController
{
    /**
     * 授权用户
     * @var array
     */
    public $user;

    /**
     * 接入公众号
     * @var string
     */
    protected $appid;

    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * desc: NULL转empty
     * author: wf
     * Date: 2022/4/7
     * Time: 9:59
     * @param $data
     * @return \Illuminate\Support\Collection|mixed|string
     */
    public function nullToEmpty($data){
        if($data !== null){
            if(is_array($data)) {
                if (!empty($data)) {
                    foreach ($data as $key => $value) {
                        if ($value === null) {
                            $data[$key] = '';
                        } else {
                            $data[$key] = $this->nullToEmpty($value);      //递归再去执行
                        }
                    }
                } else {
                    $data = '';
                }
            } elseif (is_object($data)) {
                if (!empty($data)) {
                    $data = collect($data)->map(function ($value, $key) {
                        if ($value === null) {
                            return '';
                        } else {
                            return $this->nullToEmpty($value);      //递归再去执行
                        }
                    });
                } else {
                    $data = '';
                }
            }else{
                if($data === null){ $data = ''; }         //注意三个等号
            }
        }else{ $data = ''; }
        return $data;
    }

    /**
     * 失败
     * @param $msg
     * @param array $arr
     * @return \Illuminate\Http\JsonResponse
     */
    public function fail($msg, ...$arr)
    {
        return $this->ajaxReturn(1, $msg, ...$arr);
    }

    /**
     * 成功
     * @param $msg
     * @param mixed ...$arr
     * @return \Illuminate\Http\JsonResponse
     */
    public function success($msg = 'SUCCESS', ...$arr)
    {
        return $this->ajaxReturn(0, $msg, ...$arr);
    }

    /**
     * ajax请求
     * @param int $code
     * @param string $msg
     * @param array $data
     * @param mixed ...$arr
     * @return \Illuminate\Http\JsonResponse
     */
    public function ajaxReturn($code = 0, $msg = '操作完成', ...$arr)
    {
        $return = ['code' => $code, 'msg' => $msg];
        foreach ($arr as $item) {
            if (is_array($item)) {
                $return = array_merge($return, $item);
            } else {
                array_push($return, $item);
            }
        }
        return response()->json($return, 200, ['Author' => 'wf'], 256);
    }

    /**
     * desc: 需要首先调用，获取授权用户信息
     * author: wf
     * Date: 2022/4/7
     * Time: 9:59
     * @param Request $request
     */
    protected function getUserInfo(Request $request)
    {
        if(config('debug.wechat_debug',false)){
            $sessionKey = 'openplatform.oauth_user.test';
            $this->user = $request->session()->get($sessionKey);
            return;
        }
        $appId = $this->getAppid($request);
        $sessionKey = \sprintf('openplatform.oauth_user.%s', $appId);
        $this->user = $request->session()->get($sessionKey);
    }


    /**
     * desc: 获取or更新粉丝信息
     * author: wf
     * Date: 2022/4/7
     * Time: 9:59
     * @param $model
     * @return mixed
     */
    protected function self($model = null) {
        $user = $this->user;
        $model = $model ?? VoteUser::class;
        $update = [
            'name' => $this->user['name'] ?? '',
            'avatar' => $this->user['avatar'] ?? '',
            'sex' => $this->user['original']['sex'] ?? 0,
            'province' => $this->user['original']['province'] ?? '',
            'city' => $this->user['original']['city'] ?? ''
        ];
        $model::query()->updateOrCreate(['openid' => $user['id']], $update);
        $userInfo = $model::query()->where('openid', $user['id'])->first();

        return $userInfo;
    }

    /**
     * desc: 获取授权公众号APPID
     * author: wf
     * Date: 2022/4/7
     * Time: 10:00
     * @param Request $request
     * @return array|mixed|string
     */
    protected function getAppid(Request $request)
    {
        return $this->appid = $request->header('Appid') ? $request->header('Appid') : ($request->input('appid') ?? config('openplatform.default_office_account_id'));
    }

    /**
     * desc: 获取jssdk
     * author: wf
     * Date: 2022/4/7
     * Time: 10:03
     * @return string
     */
    public function jssdk($url = '')
    {
        if (config('debug.wechat_debug',false)) {
            return '';
        }
        $officialAccount = BocWechatFacades::officialAccount();
        $url = $url ?: $officialAccount->jssdk->getUrl();
        return $officialAccount->jssdk->setUrl(str_replace('443', '', $url))->buildConfig(array('showMenuItems', 'hideOptionMenu', 'onMenuShareAppMessage', 'onMenuShareTimeline', 'getLocation'), false, false, false, array('wx-open-launch-weapp'));
    }

    /**
     * 创建订单号
     *
     * @return string
     */
    protected function createOrderNo ()
    {
        $str = date('ymdHis');
        $res = Redis::pipeline(function ($pipe) use ($str) {
            $pipe->INCR('order_' . $str);
            $pipe->EXPIRE('order_' . $str, 60);
        });
        return $str . str_pad($res[0], 4, '0', STR_PAD_LEFT);
    }

    /**
     * desc: 把网络图片转成base64
     * author: wf
     * Date: 2022/3/31
     * Time: 15:39
     * @param string $img
     * @return string
     */
    protected function imgtobase64($img='')
    {
        $imageInfo = getimagesize($img);
        return 'data:' . $imageInfo['mime'] . ';base64,' . chunk_split(base64_encode(file_get_contents($img)));
    }

    /**
     * desc: 获取本周的开始和结束时间
     * author: wf
     * Date: 2022/5/30
     * Time: 14:15
     * @return array
     */
    protected function getWeekDate()
    {
        $date = [];

        //当前日期
        $sdefaultDate = date("Y-m-d");

        //$first =1 表示每周星期一为开始日期 0表示每周日为开始日期
        $first = 1;

        //获取当前周的第几天 周日是 0 周一到周六是 1 - 6
        $w = date('w', strtotime($sdefaultDate));

        //获取本周开始日期，如果$w是0，则表示周日，减去 6 天
        $week_start = date('Y-m-d', strtotime("$sdefaultDate -" . ($w ? $w - $first : 6) . ' days'));

        //本周结束日期
        $week_end = date('Y-m-d', strtotime("$week_start +6 days"));

        $date['start'] = $week_start;
        $date['end']   = $week_end;

        return $date;
    }
}
