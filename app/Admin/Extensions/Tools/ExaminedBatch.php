<?php

namespace App\Admin\Extensions\Tools;

use App\Models\MdArticle;
use Dcat\Admin\Grid\BatchAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExaminedBatch extends BatchAction
{
    protected $action;

    // 注意action的构造方法参数一定要给默认值
    public function __construct($title = null, $action = 1)
    {
        $this->title = $title;
        $this->action = $action;
    }

    // 确认弹窗信息
    public function confirm()
    {
        return '您确定要审核已选中的作品吗？';
    }

    // 处理请求
    public function handle(Request $request)
    {
        // 获取选中的文章ID数组
        $keys = $this->getKey();

        $action = $request->get('action');
        foreach (MdArticle::find($keys) as $model) {
            if($action == 1)
            {
                $model->state = MdArticle::$stateMap['已发布'];

                $serial_number =  MdArticle::where('release_time','<=' , date('Y-m-d H:i:s'))->count();
                $model->serial_number =  100000+$serial_number;

            }else{
                $model->state = MdArticle::$stateMap['已驳回'];
            }

            $model->examined_time = date('Y-m-d H:i:s');
            $model->examined_user = Auth::id()??0;
            $model->release_time = date('Y-m-d H:i:s');
            $model->save();
        }
        $message = $action==1 ? '审核成功' : '驳回作品';

        return $this->response()->success($message)->refresh();
    }

    // 设置请求参数
    public function parameters()
    {
        return [
            'action' => $this->action,
        ];
    }
}
