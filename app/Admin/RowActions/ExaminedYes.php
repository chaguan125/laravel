<?php

namespace App\Admin\RowActions;

use App\Models\MdArticle;
use Dcat\Admin\Grid\RowAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExaminedYes extends RowAction
{
    /**
     * 标题
     *
     * @return string
     */
    public function title()
    {
        return '审核通过';
    }

    /**
     * 设置确认弹窗信息，如果返回空值，则不会弹出弹窗
     *
     * 允许返回字符串或数组类型
     *
     * @return array|string|void
     */
    public function confirm()
    {
        return [
            "您确定要审核通过这条作品吗？"
        ];
    }

    /**
     * 处理请求
     *
     * @param Request $request
     *
     * @return \Dcat\Admin\Actions\Response
     */
    public function handle(Request $request)
    {
        $id = $this->getKey();
        $model = MdArticle::find($id);
        if(!$model)
        {
            return $this->response()->warning('请检查作品是否存在!')->refresh();
        }
        if( $model->examined_time)
        {
            return $this->response()->warning('该作品已经审核过了，请核实!')->refresh();
        }
        $serial_number =  MdArticle::where('release_time','<=' , date('Y-m-d H:i:s'))->count();

        $model->serial_number =  100000+$serial_number;
        $model->state =  MdArticle::$stateMap['已发布'];
        $model->examined_time = date('Y-m-d H:i:s');
        $model->examined_user = Auth::id()??0;
        $model->release_time = date('Y-m-d H:i:s');
        $model->save();
        $id = $model->id;
        if($id)
        {
            return $this->response()->success('审核成功')->refresh();
        }else{
            return $this->response()->warning('审核更新失败')->refresh();
        }
    }

    /**
     * 设置要POST到接口的数据
     *
     * @return array
     */
    public function parameters()
    {
        return [];
    }
}

