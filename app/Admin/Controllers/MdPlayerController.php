<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\MdPlayer;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use Illuminate\Http\Request;

class MdPlayerController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new MdPlayer(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('wx_openid');
            $grid->column('wx_name');
            $grid->column('wx_avatar');
            $grid->column('name');
            $grid->column('phone');
            $grid->column('province');
            $grid->column('city');
            $grid->column('area');
            $grid->column('address');

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');

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
        return Show::make($id, new MdPlayer(), function (Show $show) {
            $show->field('id');
            $show->field('wx_openid');
            $show->field('wx_name');
            $show->field('wx_avatar');
            $show->field('name');
            $show->field('phone');
            $show->field('province');
            $show->field('city');
            $show->field('area');
            $show->field('address');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new MdPlayer(), function (Form $form) {
            $form->display('id');
            $form->text('wx_openid');
            $form->text('wx_name');
            $form->text('wx_avatar');
            $form->text('name');
            $form->text('phone');
            $form->text('province');
            $form->text('city');
            $form->text('area');
            $form->text('address');
        });
    }

}
