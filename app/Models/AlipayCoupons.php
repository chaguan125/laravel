<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlipayCoupons extends Model
{
    use HasFactory;

    protected $table = "alipay_coupons";

    public $timestamps = false;

}
