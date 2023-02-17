<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MdArticle extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //作品表
        Schema::create('md_article', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number')->nullable()->comment('编号');
            $table->text('wish')->nullable()->comment('祝福/心愿');
            $table->string('img',150)->default('')->nullable()->comment('图片');
            $table->integer('player_id')->default(0)->index()->comment('选手id');
            $table->integer('number_votes')->default(0)->comment('票数');
            $table->integer('rank')->default(0)->comment('排名');
            $table->integer('state')->default(0)->comment('状态');
            $table->timestamp('examined_time')->nullable()->comment('审核时间');
            $table->integer('examined_user')->nullable()->comment('审核人');
            $table->string('refute_reason',200)->nullable()->comment('驳回原因');
            $table->timestamp('release_time')->nullable()->comment('发布时间');
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
        Schema::dropIfExists('md_article');
    }
}
