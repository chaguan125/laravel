<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DB;
use Illuminate\Http\Request;
use App\Models;
use App\Librarys\Ali\Coupon;
use App\Librarys\Ali\CouponFacade;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;

class CouponsController extends Controller{

    public function post(Request $request)
    {
        $brand_name = $request->get('brand_name');
        $tenant_id = $request->get('tenant_id');
        $goods_cover_image_id = $request->get('goods_cover_image_id'); //  封面图片id
//        $goods_cover_image_id = "A*ERGnQL9dpwoAAAAAAAAAAAAAARwnAQ";
        $goods_name = $request->get('goods_name');
        $goods_info = $request->get('goods_info');
        $goods_id = $request->get('goods_id');
        $floor_amount = $request->get('floor_amount');
        $amount = $request->get('amount');
        $nofity_uri = $request->get('nofity_uri');
        $duration = $request->get('duration')??0;  //  有效时间
        $publish_start_time = $request->get('publish_start_time',"2023-01-11 00:00:00");  //  发放开始时间
        $publish_end_time = $request->get('publish_end_time',"2023-02-01 23:59:59") ;  //  发放结束时间
        $out_biz_no = $request->get('out_biz_no');
        $voucher_quantity = $request->get('voucher_quantity');
        $voucher_description = $request->get('voucher_description');
        $voucher_discount_limit = $request->get('voucher_discount_limit');
        $user_give_max = $request->get('user_give_max');
        $pre_day_give_max = $request->get('pre_day_give_max');
        $availableTimeBegin = $request->get('available_time_begin',"00:00:00");  //  可用开始时间
        $availableTimeEnd = $request->get('available_time_end',"23:00:00");  //  可用截止时间

        $availableTimedayRule = $request->get('voucher_available_time','');   // 使用星期限制 目前默认为不限制
        $availableTimedayRule=explode(",",$availableTimedayRule);   // 使用星期限制


        //  先查询业务单号是否已存在
        $row = \App\Models\AlipayCoupons::where(['out_biz_no'=>$out_biz_no])->get()->toArray();
        if(count($row)>0)
        {
            return[
                'code'=>false,
                'msg'=>'业务单号已存在',
            ];
        }

        //  调用支付宝创建优惠券接口
        $result = CouponFacade::getInstance()->coupon()->cashlessFixVoucherTemplateCreate($brand_name,$goods_name,$goods_info,$goods_id,$floor_amount,
        $goods_cover_image_id,$voucher_description,$publish_start_time,$publish_end_time,$duration,$availableTimedayRule,$availableTimeBegin,$availableTimeEnd,
            $amount,$voucher_quantity,$out_biz_no,$nofity_uri ,"" ,[] ,[]);

        $code = (int)$result->code;
        if($code != 10000)
        {
            return[
                'code'=>false,
                'msg'=>'创建无资金单品券失败',
            ];
        }
        $template_id = $result->templateId;  //  模板id
        $voucherDiscountLimit = $result->voucherDiscountLimit; //  使用一张单品券用户可以获得的最大优惠。
        $renew_user_id = Auth::id()??0; //  操作用户
        //  新插入数据
        $DataTime = date("Y-m-d H:i:s");

        $model = new \App\Models\AlipayCoupons();
        $model->brand_name = $brand_name;
        $model->tenant_id = $tenant_id;
        $model->goods_name = $goods_name;
        $model->goods_info = $goods_info;
        $model->goods_id = $goods_id;
        $model->floor_amount = $floor_amount;
        $model->amount = $amount;
        $model->nofity_uri = $nofity_uri;
        $model->out_biz_no = $out_biz_no;
        $model->voucher_quantity = $voucher_quantity;
        $model->voucher_description = $voucher_description;
        $model->voucher_discount_limit = $voucher_discount_limit;
        $model->template_id = $template_id;
        $model->user_give_max = $user_give_max;
        $model->pre_day_give_max = $pre_day_give_max;
        $model->created_at = $DataTime;
        $model->updated_at = $DataTime;
        $model->goods_cover_image_id = $goods_cover_image_id;
        $model->duration = $duration;
        $model->publish_start_time = $publish_start_time;
        $model->publish_end_time = $publish_end_time;
        $model->renew_user_id = $renew_user_id;
        $model->save();
        $id = $model->id;
        if($id)
        {
            return[
                'code'=>10000,
                'msg'=>'无资金单品券创建成功',
            ];
        }else{
            return[
                'code'=>false,
                'msg'=>'无资金单品券入库失败',
            ];
        }

    }

    /**
     * 上传营销图片
     */
    public function file(Request $request)
    {
        $file=$request->file('file');
        $realpath=$file->getRealPath();  // 图片临时目录
        $fileextension=$file->getClientOriginalExtension();  //  后缀
        $filesize=$file->getSize();  //  文件大小
        //  检查图片格式是否合规
        $checkRe = $this->checkImg($filesize , $fileextension ,$realpath);
        if(!$checkRe['code'])
        {
            return $checkRe;
        }

        $path =base_path()."/uploads";
        if(!is_dir($path))
        {
            mkdir($path,0777);
        }
        $newName = date('YmdHis').mt_rand(100,999).".".$fileextension;
        $path=$file->move($path,$newName);
        if($path)
        {
            return $this->imageUpload($path->getPathname());
        }else{
            return[
                "code"=>false,
                "msg"=>"图片上传失败",
            ];
        }

    }

    /**
     * 上传营销图片到ali
     *
     * @param string $path 图片地址
     */
    public function imageUpload($path)
    {
        $result = CouponFacade::getInstance()->coupon()->upload($path);

        //  删除保存到临时文件夹的图片
        $status=unlink($path);
        $code = (int)$result->code;

        if($code == 10000)
        {
            return[
                "code"=>$result->code,
                "resourceId"=>$result->resourceId,
                "resourceUrl"=>$result->resourceUrl
            ];
        }else{
            return[
                "code"=>false,
                "msg"=>"上传阿里失败",
            ];
        }

    }

    /**
     * 复制优惠券
     */
    public function copy(Request $request)
    {
        $coupons_id = $request->get('id');
        $rows = \App\Models\AlipayCoupons::where(['id'=>$coupons_id])->get();
        if(count($rows)<=0)
        {
            return[
                'code'=>false,
                'msg'=>'数据不存在',
            ];
        }

        $row = $rows[0];
        $brand_name = $row->brand_name;
        $tenant_id = $row->tenant_id;
        $goods_name = $row->goods_name;
        $goods_info = $row->goods_info;
        $goods_id = $row->goods_id;
        $floor_amount = $row->floor_amount;
        $amount = $row->amount;
        $nofity_uri = $row->nofity_uri;
        $out_biz_no = $row->out_biz_no."-1";  // 先区分外部业务单号
        $voucher_quantity = $row->voucher_quantity;
        $voucher_description = $row->voucher_description;
        $voucher_discount_limit = $row->voucher_discount_limit;
        $user_give_max = $row->user_give_max;
        $pre_day_give_max = $row->pre_day_give_max;
        $goods_cover_image_id = $row->goods_cover_image_id;
        $duration = $row->duration;
        $publish_start_time = $row->publish_start_time;
        $publish_end_time = $row->publish_end_time;

        $availableTimeBegin = "00:00:00";  //  可用开始时间
        $availableTimeEnd = "23:59:59";  //  可用截止时间
        $availableTimedayRule=[];   // 使用星期限制 目前默认为不限制

        $renew_user_id = Auth::id()??0; //  操作用户
        //  调用支付宝创建优惠券接口
        $result = CouponFacade::getInstance()->coupon()->cashlessFixVoucherTemplateCreate($brand_name,$goods_name,$goods_info,$goods_id,$floor_amount,
            $goods_cover_image_id,$voucher_description,$publish_start_time,$publish_end_time,$duration,$availableTimedayRule,$availableTimeBegin,$availableTimeEnd,
            $amount,$voucher_quantity,$out_biz_no,$nofity_uri ,"" ,[] ,[]);

        $code = (int)$result->code;
        if($code != 10000)
        {
            return[
                'code'=>false,
                'msg'=>'创建无资金单品券失败',
            ];
        }
        $template_id = $result->templateId;  //  模板id
        $voucherDiscountLimit = $result->voucherDiscountLimit; //  使用一张单品券用户可以获得的最大优惠。

        $DataTime = date("Y-m-d H:i:s");
        $model = new \App\Models\AlipayCoupons();
        $model->brand_name = $brand_name;
        $model->tenant_id = $tenant_id;
        $model->goods_name = $goods_name;
        $model->goods_info = $goods_info;
        $model->goods_id = $goods_id;
        $model->floor_amount = $floor_amount;
        $model->amount = $amount;
        $model->nofity_uri = $nofity_uri;
        $model->out_biz_no = $out_biz_no;
        $model->voucher_quantity = $voucher_quantity;
        $model->voucher_description = $voucher_description;
        $model->voucher_discount_limit = $voucher_discount_limit;
        $model->template_id = $template_id;
        $model->user_give_max = $user_give_max;
        $model->pre_day_give_max = $pre_day_give_max;
        $model->created_at = $DataTime;
        $model->updated_at = $DataTime;
        $model->goods_cover_image_id = $goods_cover_image_id;
        $model->duration = $duration;
        $model->publish_start_time = $publish_start_time;
        $model->publish_end_time = $publish_end_time;
        $model->renew_user_id = $renew_user_id;
        $model->save();
        $id = $model->id;

        if($id)
        {
            return[
                'code'=>10000,
                'msg'=>'无资金单品券复制成功',
            ];
        }else{
            return[
                'code'=>false,
                'msg'=>'无资金单品券入库失败',
            ];
        }

    }

    /**
     * 检查图片是否合格
     *
     * @param int $filesize 图片大小
     * @param string $fileextension 图片后缀
     * @param string $path 图片地址
     */
    protected function checkImg($filesize,$fileextension,$path)
    {
        if($filesize > 2097152)
        {
            return[
                "code"=>false,
                "msg"=>"图片大小超过2M",
            ];
        }
        if(!in_array($fileextension,[ 'png', 'gif', 'jpg', 'jpeg', 'bmp']))
        {
            return[
                "code"=>false,
                "msg"=>"图片后缀只能是'png', 'gif', 'jpg', 'jpeg', 'bmp' 格式 ",
            ];
        }

        $img_info = getimagesize($path);

        if($img_info[0] !=800)
        {
            return[
                "code"=>false,
                "msg"=>"请上传800 X 600的图片",
            ];
        }

        if($img_info[1] !=600)
        {
            return[
                "code"=>false,
                "msg"=>"请上传800 X 600的图片",
            ];
        }

        return[
            "code"=>true,
        ];
    }

}
