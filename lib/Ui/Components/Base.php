<?php
namespace Mixcode\Ui\Components;

use Closure;
use Mixcode\Ui\Utils;
use SLiMS\Plugins;

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
            $newAttribute[$argument[0]] = is_callable($argument[1]) ? $argument[1]($this) : $argument[1];
            $attribute = $newAttribute;
        } else {
            $attribute = $argument[0]??[];
        }

        $this->attributes = array_merge($this->attributes, $attribute);

        return $this;
    }

    public function generateAttribute():string
    {
        $attributes = $this->attributes;
        return implode(' ', array_map(function($attribute) use($attributes) {
            $isByPassedValue = false;
            if (strpos($attribute, '!') !== false) {
                $isByPassedValue = true;
                $attribute = trim($attribute, '!');
            }
            return $this->xssClean($attribute) . '="' . ($isByPassedValue ? $attributes[$attribute] : $this->xssClean($attributes[$attribute])) . '"';
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

    public function setHiddenInput(string $name, string $value)
    {
        if (!isset($this->properties['hidden_input'])) $this->properties['hidden_input'] = [];
        $this->properties['hidden_input'][$name] = $value;
        $this->setSlot((string)createComponent('input', ['name' => $name, 'type' => 'hidden', 'value' => $value])->setSlot(''));
        return $this;
    }

    public function registerEvent(string $eventName, Closure $callback, string $evenType = '')
    {
        $argument = !empty($eventType) ? [$callback, $eventType] : [$callback];
        $this->properties['event']['on_' . strtolower($eventName)] = $argument;
        $this->properties['custom_event_to_call'][] = strtolower($eventName);
        return $this;
    }

    public function callEvent(string|array $eventNameOrNames)
    {
        if (is_string($eventNameOrNames)) $eventNameOrNames = [$eventNameOrNames];

        if ($this->properties['custom_event_to_call']) {
            $eventNameOrNames = array_merge($this->properties['custom_event_to_call'], $eventNameOrNames);
        }

        $className = strtolower($class = (new \ReflectionClass($this))->getShortName());

        foreach ($eventNameOrNames as $eventName) {
            if (isset($this->properties['event']['on_' . ($eventType = strtolower($eventName))])) {
                $event = $this->properties['event']['on_' . $eventType];

                if (isset($event[1])) {
                    list($event, $eventType) = $event;
                } else { $event = $event[0]; }

                // validated some request
                if (!isset($_REQUEST[$eventType])) continue;

                $bypassDefaultEvent = false;
                Plugins::run($className . '_on_' . $eventType, [$this, &$bypassDefaultEvent]);

                if (!$bypassDefaultEvent) call_user_func_array($event, [$this]);
            }
        }
    }

    public function __call($method, $argument)
    {
        if (substr($method, 0,2) === 'on') {
            $eventName = strtolower(substr_replace($method, 'on_', 0,2));
            $this->properties['event'][$eventName] = $argument;
        }
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
            $html = in_array($tag, $this->voidElements) ? $html . '/>' : $html . '</' . $tag . '>';
        }

        return $html;
    }
}