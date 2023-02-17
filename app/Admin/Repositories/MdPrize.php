<?php

namespace App\Admin\Repositories;

use App\Models\MdPrize as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class MdPrize extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
