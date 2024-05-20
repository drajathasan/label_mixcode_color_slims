<?php
namespace Mixcode\Ui\Components;

class Table extends Base
{
    protected string $tag = 'table';
    protected array $attributes = [
        'class' => 's-table table',
        'id' => 'dataList'
    ];
    protected array $rows = [];
    protected array $columns = [];

    public function __toString()
    {
        $this->setSlot(createComponent('tr', [
            'class' => 'dataListHeader',
            'style' => 'font-weight: bold; cursor: pointer;'
        ])->setSlot('<td>Hai</td>'));

        return parent::__toString();
    }
}