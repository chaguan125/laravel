<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MdPlayer extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //选手
        Schema::create('md_player', function (Blueprint $table) {
            $table->id();
            $table->string('wx_openid')->comment('微信openid');
            $table->text('wx_name')->nullable()->comment('微信昵称');
            $table->string('wx_avatar',200)->default('')->nullable()->comment('头像');
            $table->string('name',40)->default('')->comment('姓名');
            $table->string('phone',20)->default('')->comment('手机');
            $table->string('province',30)->default('')->comment('省');
            $table->string('city',30)->default('')->comment('市');
            $table->string('area',30)->default('')->comment('区/县');
            $table->string('address',150)->default('')->comment('详细地址');
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
        Schema::dropIfExists('md_player');
    }
}
