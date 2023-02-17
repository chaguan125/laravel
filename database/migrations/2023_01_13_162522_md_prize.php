<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MdPrize extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //奖品表
        Schema::create('md_prize', function (Blueprint $table) {
            $table->id();
            $table->integer('level')->default(1)->index()->comment('奖品等级');
            $table->string('name',50)->default('')->comment('奖品名称');
            $table->integer('quantity')->default(0)->comment('数量');
            $table->decimal('amount',10, 2)->default(0.00)->comment('金额');
            $table->string('describe')->default('')->comment('奖品描述');
            $table->string('category',10)->default('实物')->nullable()->comment('类别');
            $table->string('img')->default('')->nullable()->comment('图片');

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
        //删除表格
        Schema::dropIfExists('md_prize');
    }
}
