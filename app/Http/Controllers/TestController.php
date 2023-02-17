<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Mockery\Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class TestController extends Controller
{
    //  缓存信息测试使用
    public function redis(Request $request)
    {

        $message = "信息输出";
        Log::emergency($message);
        Log::alert($message);
        Log::critical($message);
        Log::error($message);
        Log::warning($message);
        Log::notice($message);
        Log::info($message);
        Log::debug($message);


        echo date('Y-m-d H:i:s');
        //  响应返回文件下载
//        $pathToFile = "D:\phpstudy_pro\laravel\uploads\iiii.txt";
//        return response()->file($pathToFile);


        //  判断缓存是否存在
//        if (!Cache::has('name')) {
//            //
//            echo "缓存不存在";
//        }

        //  cache 需要先指明使用那个缓存  .evn已配置使用redis
//        cache::set('name', '更新cache缓存信息', 600);
        $value = Cache::get('name');

//        cache::store('redis')->set('bar', 'baz', 600);
//        $value = Cache::store('redis')->get('bar');

//        Redis::set('name', '男');
//        $data = Redis::get('name');
        return[$value];
    }

    //  检测用户是否登录
    public function check()
    {
        if (Auth::check()) {
            return "1";
        }else{
            return "2";
        }
    }

    //  事务提交回滚
    public function trsubmit()
    {
        DB::beginTransaction(); //  事务开启
        try{
            $model = new \App\Models\AlipayCoupons();
            $model->brand_name = "品牌";
            $model->out_biz_no ="8223225";
            $model->renew_user_id = 2;
            $model->save();

            $model = new \App\Models\AlipayCoupons();
            $model->brand_name = "品牌";
            $model->out_biz_no ="8223225";
            $model->renew_user_id = 2;
            $model->save();
            DB::commit(); //  事务提交
        }catch (Exception $e){
            DB::rollBack(); //  事务回滚
            dd($e->getMessage());
        }

        return [
            'code' => 10000,
            'msg' => '事务执行成功',
        ];
    }

    //  集合使用
    public function collect()
    {
//        $average = collect([1, 8, 2, 4])->avg();  //  求集合内的平均数

        //  拆分数组
//        $collection = collect([1, 2, 3, 4, 5, 6, 7]);
//        $chunks = $collection->chunk(4);
//        $re = $chunks->all();


        Collection::macro('toUpper', function () {
            return $this->map(function ($value) {
                return $value."--22";
            });
        });

        $collection = collect(['first', 'second']);

        $upper = $collection->toUpper();

        var_dump($upper);
    }


    //  后端表单验证
    public function store(Request $request)
    {
//        $pwd = Hash::make($request->newPassword);  //  哈希加密

        //  验证没有通过的话 返回上一次请求的视图
        $validated = $request->validate([
            'title' => 'required|max:255',
            'body' => 'required',
        ]);

//        $title = $request->old('title');  //获取上次请求的数据 当验证失败后 可以默认填充

        return ['验证通过'];
    }


    //  数据表操作
    public function dbDate()
    {

//        $rows = DB::table('alipay_coupons')->get()->toArray();
        //  查询指定单列
//        $rows = DB::table('alipay_coupons')->pluck('brand_name')->toArray();

        //  查询指定列  out_biz_no => brand_name
//        $rows = DB::table('alipay_coupons')->pluck('brand_name' , 'out_biz_no')->toArray();

        //  查询多列
//        $rows = DB::table('alipay_coupons')->select('brand_name', 'out_biz_no' ,'goods_id')->get()->toArray();

//        //  查询条件
//        $rows = \App\Models\AlipayCoupons::where('brand_name', '=', '123')
//            ->where(function ($query) {
//                $query->where('brand_name', '>', 1)
//                    ->orWhere('voucher_quantity', '=', 12);
//            })
//            ->get()->toArray();


//        foreach ($rows as $row)
//        {
//            echo $row['brand_name']."<br>";
//        }

        //  给指定列 自增  返回修改行数
//        $rows = DB::table('alipay_coupons')->increment('tenant_id');

//        $rows = \App\Models\AlipayCoupons::get()->toArray();

        //  分页   ?page=2 代表查询第二页的数据
        $rows = \App\Models\AlipayCoupons::paginate(3)->toArray();

        echo "<pre>";
        print_r($rows);

    }



}
