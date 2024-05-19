<?php
namespace Mixcode\Ui\Components;

use Closure;
use Mixcode\Ui\Utils;

abstract class Base
{
    use Utils;

    protected string $tag = '';
    protected array $voidElements = [
        'area','base','br','col',
        'embed','hr','img','input',
        'link','meta','param','source',
        'track','wbr'
    ];
    protected array $slots = [];
    protected array $attributes = [];

    public function setSlot(string|Closure $slot):self
    {
        if ($slot instanceof Closure) $slot = $slot($this);
        $this->slots[] = $slot;

        return $this;
    }

    public function setClass(string|Closure $class):self
    {
        if ($class instanceof Closure) $class = $class($this);
        $this->setAttribute('class', $class);
        return $this;
    }

    public function setAttribute():self
    {
        $attribute = ($argument = func_get_args());
        if (func_num_args() == 2) {
            $newAttribute = [];
            $newAttribute[$argument[0]] = $argument[1];
            $attribute = $newAttribute;
        }
        $this->attributes = array_merge($this->attributes, $attribute);

        return $this;
    }

    public function generateAttribute():string
    {
        $attributes = $this->attributes;
        return implode(' ', array_map(function($attribute) use($attributes) {
            return $this->xssClean($attribute) . '="' . $this->xssClean($attributes[$attribute]) . '"';
        }, array_keys($attributes)));
    }

    public function generateSlot()
    {
        return implode('', array_map(function($slot) {
            if ($slot instanceof Base) return (string)$slot;
            return $slot;
        }, $this->slots));
    }

    public function notAjax(bool &$status = false)
    {
        $class = $this->attributes['class'];
        if (strpos($class, 'notAJAX') === false) {
            $this->attributes['class'] = $class . ' notAJAX';
            $status = true;
        }
        
        return $this;
    }

    public function openPopUp(string $title = 'Untitle Pop Up', bool &$status = false)
    {
        $class = $this->attributes['class'];

        if (strpos($class, 'openPopUp') === false) {
            $withNotAjaxClass = false;
            $this->notAjax($withNotAjaxClass);
            
            if ($withNotAjaxClass) $class = $this->attributes['class'];
            $this->attributes['class'] = $class . ' openPopUp';
            
            if (!isset($this->attributes['width'])) {
                $this->attributes['width'] = 780;
            }

            if (!isset($this->attributes['height'])) {
                $this->attributes['height'] = 500;
            }

            $this->attributes['title'] = $title;
        }
        return $this;
    }

    public function __toString()
    {
        $tag = trim($this->xssClean($this->tag));
        $html = '<' . $tag . ' ';
        if ($this->attributes) {
            $html .= $this->generateAttribute();
        }
        if ($this->slots) {
            $html .= '>';
            $html .= $this->generateSlot();
            $html .= '</' . $tag . '>';
        } else {
            $html = in_array($tag, $this->voidElements) ? $html . '/>' : '</' . $tag . '>';
        }

        return $html;
    }
}