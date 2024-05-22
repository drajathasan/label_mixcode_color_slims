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
    protected array $headers = [];

    public function addHeader() {
        if (func_num_args()) {
            $this->headers = func_get_args();
            $this->addRow($this->headers, [
                'class' => 'dataListHeader',
                'style' => 'font-weight: bold; cursor: pointer;'
            ]);
        }

        return $this;
    }

    public function addRow(array $columns, array $options = [])
    {
        if (count($columns) === count($this->headers)) {
            foreach ($columns as $seq => $column) {
                if (!$column instanceof Base) {
                    $columns[$seq] = (new Td)->setSlot($column);
                }
            }

            $this->setSlot(
                createComponent('tr', $options)
                    ->setSlot(implode('', $columns))
            );
        }

        return $this;
    }
}