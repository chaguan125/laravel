<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\AlipayCoupon;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class AlipayCouponController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new AlipayCoupon(), function (Grid $grid) {

            //  处理过滤器
            $grid->filter(function($filter){
//                $filter->panel();  //  修改过滤器布局 默认左边

//                $filter->expand();   // 展开过滤器
                $filter->equal('tenant_id', '厂房');   //  等于
                $filter->like('brand_name', '品牌名称');  //  相似
//                $filter->notEqual('goods_name', '货物名称');  //  不等于
                $filter->gt('user_give_max', '用户领取数量最少');  // 大于    ngt 大于等于
                $filter->lt('pre_day_give_max', '每日发放数量最多');  // 小于  nlt  小于等于
                $filter->between('voucher_discount_limit', '最大优惠'); //  between

//                ->datetime() 设置查询类型


            });

            //  冻结某一列
//            $grid->tenant_id->filter(
//                Grid\Column\Filter\Equal::make()
//            );

//            //  筛选某一行
//            $grid->column('tenant_id', '厂房')->filter(
//                Grid\Column\Filter\In::make([
//                    0 => '未知',
//                    1 => '1厂房',
//                    2 => '2厂房',
//                ])
//            );
            //  二维码显示
//            $grid->tenant_id->qrcode(function () {
//                return $this->tenant_id;
//            }, 200, 200);

            $grid->quickSearch('goods_name')->placeholder('搜索商品...');
//            $grid->tenant_id()->switch();

            $grid->column('序号')->display(function () {
                return $this->_index + 1;
            });

//            $grid->column('id')->sortable();

            $grid->column('tenant_id')->display(function ($tenant_id) {
                return $tenant_id ."厂房";
            });

//            $grid->setActionClass(Grid\Displayers\Actions::class);  //  设置按钮显示样式
            //   添加自定义按钮
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                // append一个操作
                $actions->append('<a href=""><i class="fa fa-eye">自定义</i></a>');

            });

            $grid->column('brand_name');
            $grid->column('goods_cover_image_id')->limit(15);
            $grid->column('goods_name')->editable();
            $grid->column('goods_info');
            $grid->column('goods_id');
            $grid->column('floor_amount');
            $grid->column('amount');
            $grid->column('nofity_uri');
            $grid->column('duration');
            $grid->column('publish_start_time');
            $grid->column('publish_end_time');
            $grid->column('out_biz_no');
            $grid->column('voucher_quantity');
            $grid->column('voucher_description');
            $grid->column('voucher_discount_limit');
            $grid->column('template_id');
            $grid->column('user_give_max');
            $grid->column('pre_day_give_max');
            $grid->column('renew_user_id');
//            $grid->column('created_at');
            $grid->column('updated_at')->sortable();

//            $grid->filter(function (Grid\Filter $filter) {
//                $filter->equal('id');
//
//            });
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
        return Show::make($id, new AlipayCoupon(), function (Show $show) {
            $show->field('id');
            $show->field('tenant_id');
            $show->field('brand_name');
            $show->field('goods_cover_image_id');
            $show->field('goods_name');
            $show->field('goods_info');
            $show->field('goods_id');
            $show->field('floor_amount');
            $show->field('amount');
            $show->field('nofity_uri');
            $show->field('duration');
            $show->field('publish_start_time');
            $show->field('publish_end_time');
            $show->field('out_biz_no');
            $show->field('voucher_quantity');
            $show->field('voucher_description');
            $show->field('voucher_discount_limit');
            $show->field('template_id');
            $show->field('user_give_max');
            $show->field('pre_day_give_max');
            $show->field('renew_user_id');
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

        return Form::make(new AlipayCoupon(), function (Form $form) {

//            $form->switch('tenant_id')
//                ->customFormat(function ($v) {
//                    return $v == '1厂房' ? 1 : 2;
//                })
//                ->saving(function ($v) {
//                    return $v ? '1' : '2';
//                });

            $form->display('id');
            $form->text('tenant_id');
            $form->text('brand_name');
            $form->text('goods_cover_image_id');
            $form->text('goods_name');
            $form->text('goods_info');
            $form->text('goods_id');
            $form->text('floor_amount');
            $form->text('amount');
            $form->text('nofity_uri');
            $form->text('duration');
            $form->text('publish_start_time');
            $form->text('publish_end_time');
            $form->text('out_biz_no');
            $form->text('voucher_quantity');
            $form->text('voucher_description');
            $form->text('voucher_discount_limit');
            $form->text('template_id');
            $form->text('user_give_max');
            $form->text('pre_day_give_max');
            $form->text('renew_user_id');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
