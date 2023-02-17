<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MdArticleDetail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //奖品表
        Schema::create('md_article_detail', function (Blueprint $table) {
            $table->id();
            $table->integer('article_id')->default(0)->index()->comment('作品id');
            $table->integer('num')->default(0)->comment('点赞数量');$table->decimal('amount',10, 2)->default(0.00)->comment('金额');
//            $table->string('wx_user_openid',150)->default('')->comment('点赞用户openid');
            $table->string('wx_user_date_id',150)->default('')->nullable()->comment('用户日常点赞id');

            $table->timestamps();
            $table->softDeletes('deleted_at',0);  //  软删除
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
        Schema::dropIfExists('md_article_detail');
    }
}
