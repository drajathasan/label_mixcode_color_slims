<?php
use Mixcode\Ui\Components\Base;

if (!function_exists('createComponent')) {
    function createComponent(string $name, array $properties = [])
    {
        return new class($name, $properties) extends Base {
            public function __construct($name, $properties) {
                $this->tag = $name;
                foreach ($properties as $key => $value) {
                    $this->setAttribute($key, $value);
                }
            }
        };
    }
}