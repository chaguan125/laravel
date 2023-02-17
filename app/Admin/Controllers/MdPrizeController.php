<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\MdPrize;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class MdPrizeController extends AdminController
{

    protected $level =[1 => "一等奖", 2 => "二等奖", 3 => "三等奖", 4 => "幸福奖"];

    protected $category =['实物' => '实物', '虚拟' => '虚拟'];


    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {

        return Grid::make(new MdPrize(), function (Grid $grid) {
//            $grid->column('id')->sortable();
            $grid->column('name');
            $grid->column('level')->display(function ($level_id) {
                $level_arr =[1 => "一等奖", 2 => "二等奖", 3 => "三等奖", 4 => "幸福奖"];
                return $level_arr[$level_id];
            })->label();
            $grid->column('quantity');
            $grid->column('amount');
            $grid->column('describe')->limit(15);
            $grid->column('category')->label();
            $grid->column('img')->image();
            $grid->column('created_at');
            $grid->column('updated_at')->sortable();

            $grid->quickSearch('name')->placeholder('搜索奖品...');

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('name');
                $filter->equal('level');
                $filter->between('amount', '金额'); //  between

            });
        });
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     *
     * @return Show
     */
    protected function detail($id)
    {
        return Show::make($id, new MdPrize(), function (Show $show) {
            $show->field('id');
            $show->field('level');
            $show->field('name');
            $show->field('quantity');
            $show->field('amount');
            $show->field('describe');
            $show->field('category');
            $show->field('img');
            $show->field('created_at');
            $show->field('updated_at');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new MdPrize(), function (Form $form) {
            $form->display('id');
            $form->select('level', '奖品等级')->options($this->level)->required();
            $form->text('name')->required();
            $form->number('quantity','数量')->required();
            $form->number('amount','金额')->required();
            $form->textarea('describe', '奖品描述');
            $form->radio('category','类别')->options($this->category)->default('实物');
            $form->image('img')->uniqueName()->autoUpload();

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
