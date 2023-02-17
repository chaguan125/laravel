<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlipayCoupons extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('alipay_coupons', function (Blueprint $table) {
            $table->id();
            $table->integer('tenant_id')->default(0)->index()->comment('厂房');
            $table->string('brand_name')->default('')->comment('品牌名称');
            $table->string('goods_cover_image_id', 32)->default('')->comment('封面图片');
            $table->string('goods_name')->default('')->comment('商品名称');
            $table->string('goods_info')->default('')->comment('商品描述');
            $table->json('goods_id')->nullable()->comment('商品id');
            $table->decimal('floor_amount', 10, 2)->default(0.00)->comment('消费门槛金额');
            $table->decimal('amount', 10, 2)->default(0.00)->comment('代金券面额');
            $table->string('nofity_uri')->default('')->comment('券变动异步通知地址');
            $table->integer('duration')->default(0)->comment('有效时间');
            $table->timestamp('publish_start_time')->nullable()->comment('发放开始时间');
            $table->timestamp('publish_end_time')->nullable()->comment('发放结束时间');
            $table->string('out_biz_no')->unique()->comment('业务单号');
            $table->integer('voucher_quantity')->default(0)->comment('发券数量');
            $table->text('voucher_description')->nullable()->comment('券使用说明');
            $table->decimal('voucher_discount_limit')->default(0.00)->comment('最大优惠');
            $table->string('template_id')->default('')->comment('券模板ID');
            $table->integer('user_give_max')->default(0)->comment('用户领取数量限制');
            $table->integer('pre_day_give_max')->default(0)->comment('每日发放数量');
            $table->integer('renew_user_id')->default(0)->comment('用户id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::dropIfExists('alipay_coupons');
    }
}
