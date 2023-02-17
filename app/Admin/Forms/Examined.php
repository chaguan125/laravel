<?php

namespace App\Admin\Forms;

use App\Models\MdArticle;
use Dcat\Admin\Traits\LazyWidget;
use Dcat\Admin\Widgets\Form;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Examined extends Form
{
    use LazyWidget;

    public function handle(array $input)
    {
        $id = $this->payload['id'];

        $model = MdArticle::find($id);
        if(!$model)
        {
            return $this->response()->warning('请检查作品是否存在!')->refresh();
        }
        if( $model->examined_time)
        {
            return $this->response()->warning('该作品已经审核过了，请核实!')->refresh();
        }
        $model->state =  MdArticle::$stateMap['已驳回'];
        $model->examined_time = date('Y-m-d H:i:s');
        $model->examined_user = Auth::id()??0;
        $model->refute_reason = $input['refute_reason']??"";
        $model->save();
        $id = $model->id;
        if($id)
        {
            return $this->response()->success('驳回成功')->refresh();
        }else{
            return $this->response()->warning('更新失败')->refresh();
        }


    }

    public function form()
    {
        $this->textarea('refute_reason', trans('驳回原因'));
    }


    // 返回表单数据，如不需要可以删除此方法
    public function default()
    {
        return [
            'refute_reason'         => $this->payload['refute_reason'],
        ];
    }


}
