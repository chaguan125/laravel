<?php
namespace App\Console\Commands;

use Psy\Command\Command;

class test1 extends Command
{

    public function __construct(string $name = null)
    {
        parent::__construct($name);
    }

    public function runs($info)
    {
        echo $info."执行test1";
    }
}
