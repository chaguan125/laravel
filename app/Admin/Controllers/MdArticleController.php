<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Grid\Examined;
use App\Admin\Extensions\Tools\ExaminedBatch;
use App\Admin\Repositories\MdArticle;
use App\Admin\RowActions\ExaminedYes;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Widgets\Modal;
use Illuminate\Http\Request;

class MdArticleController extends AdminController
{
    public $state=[0 => '待审核', 1 => "已审核", 2 => "已驳回", 3 => "已发布"];

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new MdArticle(), function (Grid $grid) {

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableDelete();  //  禁止删除
                $actions->disableEdit();    //  禁止编辑
                $actions->append(new ExaminedYes());
                $actions->append(new Examined());

            });

            $grid->disableBatchDelete();
            $grid->batchActions([
                new ExaminedBatch('审核通过', 1),
                new ExaminedBatch('驳回作品', 2),
            ]);

            $grid->quickSearch('serial_number')->placeholder('搜索编号...');

            $grid->column('序号')->display(function () {
                return $this->_index + 1;
            });

//            $grid->column('id')->sortable();
             $grid->column('serial_number')->sortable();;
            $grid->column('wish')->limit(20);
            $grid->column('img')->image();
            $grid->column('player_id');
            $grid->column('number_votes')->sortable();
//            $grid->column('rank');
            $grid->column('state')->display(function ($state) {
                $state_arr =[0 => '待审核', 1 => "已审核", 2 => "已驳回", 3 => "已发布"];
                return $state_arr[$state];
            })->label();

            $grid->column('examined_time');
            $grid->column('examined_user');
            $grid->column('refute_reason')->limit(20);
            $grid->column('release_time');
            $grid->column('created_at');
            $grid->column('updated_at')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->like('serial_number');
                $filter->equal('player_id');
                $filter->equal('state')->select($this->state);
                $filter->between('release_time', '发布时间')->datetime();
                $filter->between('examined_time', '审核时间')->datetime();
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
        return Show::make($id, new MdArticle(), function (Show $show) {
            $show->field('id');
            $show->field('wish');
            $show->field('serial_number');
            $show->field('img');
            $show->field('player_id');
            $show->field('number_votes');
            $show->field('rank');
            $show->field('state');
            $show->field('examined_time');
            $show->field('examined_user');
            $show->field('refute_reason');
            $show->field('release_time');
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
        return Form::make(new MdArticle(), function (Form $form) {
            $form->display('id');
            $form->textarea('wish');
            $form->textarea('serial_number');
            $form->image('img')->uniqueName()->autoUpload();
            $form->text('player_id');
            $form->text('number_votes');
            $form->text('rank');
            $form->text('state');
            $form->text('examined_time');
            $form->text('examined_user');
            $form->text('refute_reason');
            $form->text('release_time');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }


}
