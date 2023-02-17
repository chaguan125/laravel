<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class MdArticle extends Model
{
	use HasDateTimeFormatter;
    use SoftDeletes;

    protected $table = 'md_article';

    static $stateMap=[
        '待审核'=>0,
        '已审核' =>1 ,
        '已驳回'=> 2 ,
        '已发布' => 3,
    ];

}
