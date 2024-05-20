<?php
namespace Mixcode\Ui\Components;

class Search extends Form
{
    protected array $attributes = [
        'id' => 'search',
        'class' => 'form-inline',
        'method' => 'get'
    ];

    public function __construct()
    {
        $this->setSlot(function() {
            return __('Search') . 
            createComponent('input', [
                'name' => 'keywords',
                'type' => 'text',
                'class' => 'form-control col-md-3'
            ]) . 
            createComponent('input', [
                'name' => 'keywords',
                'type' => 'submit',
                'value' => __('Search'),
                'class' => 's-btn btn btn-default'
            ]);
        });

       $this->setAttribute('action', function($component) {
            $self = '';
            
            if ($component->isPlugin($self)) {
                $self = $self . '?' . $_SERVER['QUERY_STRING'];
            }

            return $self;
       });
    }
}