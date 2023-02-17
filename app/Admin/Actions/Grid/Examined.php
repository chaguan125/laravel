<?php

namespace App\Admin\Actions\Grid;

use App\Admin\Forms\Examined as ExaminedForm;
use Dcat\Admin\Widgets\Modal;
use Dcat\Admin\Grid\RowAction;

class Examined extends RowAction
{
    protected $title = '驳回';

    public function render()
    {
        // 实例化表单类并传递自定义参数
        $row = $this->getRow()->toArray();
        $form = ExaminedForm::make()->payload(['id' => $this->getKey(),'wish' => $row['wish'] , 'img' => $row['img'] , 'refute_reason'=> $row['refute_reason']]);

        return Modal::make()
            ->lg()
            ->title($this->title)
            ->body($form)
            ->button($this->title);
    }
}
