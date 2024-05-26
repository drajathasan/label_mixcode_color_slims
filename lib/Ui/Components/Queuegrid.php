<?php
namespace Mixcode\Ui\Components;

use Closure;

class Queuegrid extends Datagrid
{
    public function __construct(string $name = 'queuegrid', string $action = '', string $method = 'POST', string $target = 'submitExec')
    {
        parent::__construct(...func_get_args());

        // Add some options
        $this->properties['queueable'] = true;
        $this->properties['bar'] = [
            'question' => __('Add to print queue?'),
            'class' => 's-btn btn btn-success',
            'value' => __('Add To Print Queue'),
            'name' => 'queue'
        ];
        $this->properties['event']['on_queue'] = null;
        $this->properties['custom_event_to_call'] = ['queue'];
    }
}