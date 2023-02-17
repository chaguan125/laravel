<?php

namespace App\Admin\Repositories;

use App\Models\MdPlayer as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class MdPlayer extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
