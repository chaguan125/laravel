<?php

namespace App\Admin\Repositories;

use App\Models\AlipayCoupon as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class AlipayCoupon extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;

}
