<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVoteUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vote_users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('openid')->default('')->comment('openid');
            $table->string('name')->default('')->comment('姓名');
            $table->string('avatar')->default('')->comment('头像');
            $table->string('phone')->default('')->comment('手机号码');
            $table->unsignedTinyInteger('sex')->default('0')->comment('性别');
            $table->string('province')->default('')->comment('省份');
            $table->string('city')->default('')->comment('城市');
            $table->string('mobile')->default('')->comment('电话');
            $table->string('qrcode')->nullable()->comment('二维码');
            $table->string('ticket')->nullable()->comment('获取的二维码ticket，凭借此ticket可以在有效时间内换取二维码');
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
        Schema::dropIfExists('vote_users');
    }
}
