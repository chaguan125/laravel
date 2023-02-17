<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MdWxUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //用户日常点赞记录
        Schema::create('md_wx_user', function (Blueprint $table) {
            $table->id();
            $table->string('openid',150)->default('')->index()->comment('微信用户openid');
            $table->string('name',50)->default('')->nullable()->comment('昵称');
            $table->string('avatar',200)->default('')->nullable()->comment('头像');
            $table->integer('all_num')->default(0)->comment('总数量');
            $table->integer('used_num')->default(0)->comment('已使用数量');
            $table->date('day')->comment('日期');

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
        Schema::dropIfExists('md_wx_user');
    }
}
