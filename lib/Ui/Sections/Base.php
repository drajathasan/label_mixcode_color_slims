<?php
namespace Mixcode\Ui\Sections;

use Mixcode\Ui\Utils;

abstract class Base
{
    use Utils;
    protected array $properties = [];

    public function __call($method, $arguments)
    {
        $name = trim(str_replace('set', '', strtolower($method)));
        $this->$name = count($arguments) == 1 ? $arguments[0] : $arguments;
        return $this;
    }

    public function __get($key)
    {
        $value = $this->properties[$key]??null;

        if (is_array($value)) {
            return implode('', $value);
        }

        return $value;
    }

    public function __set($key, $value) {
        if (isset($this->properties[$key]) && is_array($this->properties[$key])) {
            $value = array_merge($this->properties[$key], $value);
        }
        $this->properties[$key] = $value;
    }
}