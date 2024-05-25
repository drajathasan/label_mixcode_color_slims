<?php
namespace Mixcode\Ui\Components;

use Closure;

class Reportgrid extends Datagrid
{
    public function __construct(string $name = 'reportgrid', string $action = '', string $method = 'POST', string $target = 'submitExec')
    {
        parent::__construct(...func_get_args());

        // Add some options
        $this->properties['editable'] = false;
    }

    protected function setPrintAction()
    {
        $label = '<strong>'.$this->detail['total'].'</strong> '.__('record(s) found. Currently displaying page').' '.(int)($_GET['page']??1).' ('.$this->limit.' '.__('record each page').') ';
        $buttonPrint = (string)createComponent('a', [
            'class' => 's-btn btn btn-default printReport notAJAX',
            'href' => '#',
            'onclick' => 'window.print()'
        ])->setSlot(__('Print Current Page'));

        $pagiNation = '';
        if ($this->detail['total'] > $this->properties['limit']) {
            $pagiNation = (string)createComponent('div', ['class' => 'paging-area'])
                            ->setSlot((string)Pagination::create($this->setUrl(), $this->detail['total'], $this->properties['limit']));
        }                            

        return $pagiNation . (string)createComponent('div', [
            'class' => 's-print__page-info printPageInfo'
        ])->setSlot($label . $buttonPrint);
    }

    public function __toString()
    {
        $report = parent::__toString();
        $printArea = $this->setPrintAction();

        return $printArea . $report;

    }
}