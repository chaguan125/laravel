<?php
namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class MdWxUser extends Model
{
    use HasDateTimeFormatter;
    use SoftDeletes;

    protected $table = 'md_wx_user';

    static $AllNum = 10;

}
