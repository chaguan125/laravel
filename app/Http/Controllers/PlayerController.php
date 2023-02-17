<?php
namespace App\Http\Controllers;

//use App\Http\Controllers\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\MdArticle;
use App\Models\MdArticleDetail;
use App\Models\MdPlayer;
use App\Models\MdPrize;
use App\Models\MdWxUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Mockery\Exception;

class PlayerController extends Controller{

    //  选手信息查看
    public function list(Request $request)
    {

        $openid = $request->get('openid');
//        $this->getUserInfo($request);
//        $openid =  $this->user->id;

        $row = MdPlayer::where(['wx_openid'=>$openid])->get()->toArray();

        if(!$row)
        {
            return [
                'code'=>1,
                'msg'=>'账号未注册',
            ];
        }

        return ['code'=>0,'data'=>$row[0]];
    }

    //  选手信息注册
    public function add(Request $request)
    {
        $openid = $request->get('openid');
        $wx_name = $request->get('wx_name','');
        $wx_avatar = $request->get('wx_avatar','');
        $name = $request->get('name','');
        $phone = $request->get('phone');
        $province = $request->get('province','');
        $city = $request->get('city','');
        $area = $request->get('area','');
        $address = $request->get('address','');

//        $this->getUserInfo($request);
//        $openid =  $this->user->id;

        if(!preg_match("/^1[3456789]{1}\d{9}$/",$phone)){
            return [
                'code'=>2,
                'msg'=>'请输入正确的手机号',
            ];
        }

        $row = MdPlayer::where(['phone'=>$phone])->get()->toArray();
        if($row)
        {
            return [
                'code'=>1,
                'msg'=>'账号已经注册过',
            ];
        }
        $model = new MdPlayer();
        $model->wx_openid = $openid;
        $model->wx_name = $wx_name;
        $model->wx_avatar = $wx_avatar;
        $model->name = $name;
        $model->phone = $phone;
        $model->province = $province;
        $model->city = $city;
        $model->area = $area;
        $model->address = $address;
        $model->save();


        return [
            'code'=>0,
            'msg'=>'success',
            'id'=>$model->id,
            'openid'=>$openid
        ];
    }

    //  作品增加
    public function articleAdd(Request $request)
    {
        $openid = $request->get('openid');
        $wish = $request->get('wish');
        $image = $request->get('img');
        $image = str_replace('data:image/png;base64,', '', $image);
        $image = str_replace(' ', '+', $image);

//        $this->getUserInfo($request);
//        $openid =  $this->user->id;

        $KeyName = "article-add-".$openid;
        if (Cache::has($KeyName)) {
            return [
                'code'=>2,
                'msg'=>'请勿重复请求!',
            ];
        }
        cache::set($KeyName, $openid, 1);
        $imgUrl = "images/".date("Ymd").rand(1111,9999).'.png';
        Storage::disk('uploads')->put($imgUrl, base64_decode($image));  //  上传图片

        $rows = MdPlayer::where(['wx_openid'=>$openid])->get()->toArray();
        if(!$rows)
        {
            return [
                'code'=>1,
                'msg'=>'账号未注册',
            ];
        }

//        1. 图片上传  2. 作品入库  3.防并发 redis

        $model = new MdArticle();
        $model ->wish = $wish;
        $model ->img = $imgUrl;
        $model ->player_id = $rows[0]['id'];
        $model ->number_votes = 0;
        $model ->save();
        if($model->id)
        {
            return [
                'code'=>0,
                'msg'=>'success',
                'article_id'=>$model->id,
            ];
        }else{
            return [
                'code'=>1,
                'msg'=>'fail',
            ];
        }


    }

    //  奖品列表
    public function prizeList(Request $request)
    {
        $rows = MdPrize::get()->toArray();
        return $rows;
    }

    //  作品列表
    public function articleList(Request $request)
    {
        $serial_number = $request->get('serial_number');
        $page = $request->get('page',1);
        $pageNum =20;
        $offset = ($page-1) * $pageNum ;

        $condition =[
            ['state', '=', MdArticle::$stateMap['已发布']]
        ];
        if(!empty($serial_number))
        {
            $condition[]=['serial_number', 'like', '%'.$serial_number.'%'];
        }

        $all_page = MdArticle::where($condition)->count();

        $rows = MdArticle::where($condition)
            ->offset($offset)
            ->limit($pageNum)
            ->get()->toArray();
        return [
            'code'=>0,
            'data'=>$rows,
            'current_page'=>$page,
            'all_page'=>$all_page,
        ];
    }

    //  作品点赞
    public function articleFabulous(Request $request)
    {
        $article_id = $request->get('article_id');
        $number = $request->get('number',1);
        $openid = $request->get('openid','');
        $wx_name = $request->get('wx_name','');
        $wx_avatar = $request->get('wx_avatar','');

//        $this->getUserInfo($request);
//        $openid =  $this->user->id;
        if(!$article_id)
        {
            return[
                'code' => 1,
                'msg' => '请选择作品',
            ];
        }

        //  当前时间是否已经超出活动结束时间
        $endTime = 1674835200;  //2023-01-28 00:00:00
        if(time() > $endTime)
        {
            return[
                'code' => 4,
                'msg' => '活动已结束',
            ];
        }

        $canUsed = 10;  //  剩余可使用点赞数量
        $day = date("Y-m-d");
        $userModel = MdWxUser::where(['openid'=>$openid , 'day'=> $day])->get()->toArray();
        if(!$userModel)
        {
            $UsedNum = 0;
            $userModel = new MdWxUser();
            $userModel ->openid = $openid;
            $userModel ->name = $wx_name;
            $userModel ->avatar = $wx_avatar;
            $userModel ->all_num = MdWxUser::$AllNum;
            $userModel ->used_num = $UsedNum;
            $userModel ->day = $day;
            $userModel ->save();
            $wxUserId = $userModel->id;
        }else{
            $wxUserId = $userModel[0]['id'];
            $UsedNum = $userModel[0]['used_num'];
            $canUsed = $canUsed - $UsedNum;
        }

        if($canUsed <$number)
        {
            return[
                'code' => 2,
                'msg' => '您的剩余点赞数量不足',
            ];
        }
        $re = $this->detailAdd($wxUserId , $article_id , $number);
        if($re)
        {
            return[
                'code' => 0,
                'msg' => 'success',
            ];
        }else{
            return[
                'code' => 3,
                'msg' => '点赞失败',
            ];
        }
    }


    public function detailAdd($wxUserId , $article_id , $number)
    {
        $userModel = MdWxUser::find($wxUserId);
        if(!$userModel)
        {
            return false;
        }

        $articleModel = MdArticle::find($article_id)->where( ['state', '=', MdArticle::$stateMap['已发布']]);
        if(!$articleModel)
        {
            return false;
        }

        DB::beginTransaction(); //  事务开启
        try{

            $userModel ->used_num = $userModel ->used_num + $number;
            $userModel ->save();

            $articleModel ->number_votes = $articleModel ->number_votes +$number;
            $articleModel ->save();

            $model = new MdArticleDetail();
            $model->article_id = $article_id;
            $model->num =$number;
            $model->wx_user_date_id = $wxUserId;
            $model->save();

            DB::commit(); //  事务提交
        }catch (Exception $e){
            DB::rollBack(); //  事务回滚
            dd($e->getMessage());
        }
        return true;

    }


    //  查看点赞明细
    public function showArticleDetail(Request $request)
    {
        $article_id = $request->get('article_id');
        $page = $request->get('page',1);
        $pageNum =20;

        $offset = ($page-1) * $pageNum ;

        $all_page = DB::table('md_article_detail')
            ->join('md_wx_user', 'md_article_detail.wx_user_date_id', '=', 'md_wx_user.id')
            ->select('md_article_detail.id','num', 'name', 'avatar')
            ->where(['md_article_detail.article_id'=>$article_id])
            ->whereNull('md_article_detail.deleted_at')
            ->count();

        $rows = DB::table('md_article_detail')
            ->join('md_wx_user', 'md_article_detail.wx_user_date_id', '=', 'md_wx_user.id')
            ->select('md_article_detail.id','num', 'name', 'avatar')
            ->where(['md_article_detail.article_id'=>$article_id])
            ->whereNull('md_article_detail.deleted_at')
            ->orderBy('md_article_detail.created_at')
            ->offset($offset)
            ->limit($pageNum)
            ->get()->toArray();
        return [
            'code'=>0,
            'data'=>$rows,
            'current_page'=>$page,
            'all_page'=>$all_page,
        ];

    }


    public function uploadBaseImg($img){
        $curl='data:image/jpg/png/gif;base64,'. $img;
        preg_match('/^(data:\s*image\/(\w+);base64,)/',$img,$res);
        if (strstr($curl,",")){
            $image = explode(',',$curl);
            $image = $image[1];
        }
        $imageName = date("Ymd").rand(1111,9999).'.'.$res[2];

        $filepath = 'upload/Picture/' . date('Ymd') . '/';
        if (!file_exists($filepath)) {
            @mkdir($filepath);
        }
        $imageSrc= 'upload/Picture/' . date('Ymd') ."/". $imageName;
        $r = file_put_contents($imageSrc, base64_decode(str_replace($res[1],'',$img)));
        if (!$r) {
            return false;
        }else{
            $path = '/Picture/' . date('Ymd') . '/';
            $savepath['image'] = $path . $imageName;
            return $this ->apijson('ok','上传成功',$savepath);

        }

    }

    public function showArticleInfo(Request $request)
    {

        $article_id = $request->get('article_id',0);
        $openid = $request->get('openid','');

        $this->getUserInfo($request);
        $openid =  $this->user->id;
        if($article_id == 0 || empty($article_id))
        {

            $player = MdPlayer::where(['wx_openid'=>$openid])->get()->toArray();
            if(empty($player))
            {
                return [
                    'code'=>1,
                    'msg'=>'选手不存在',
                ];
            }
            $condition =[
                ['player_id' ,'=' , $player[0]['id']]
            ];
        }else{
            $condition =[
                ['id' ,'=' , $article_id]
            ];
        }
        $rows = MdArticle::where($condition)->get()->toArray();
        if(empty($rows))
        {
            return[
                'code'=>1,
                'msg'=>'请确认作品存在',
            ];
        }else{
            return[
                'code'=>0,
                'data'=>$rows[0],
            ];
        }


    }

}
