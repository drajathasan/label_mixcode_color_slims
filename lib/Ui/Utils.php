<?php
namespace Mixcode\Ui;

trait Utils
{
    public function xssClean(string|array $char):string|array
    {
        $formatter = fn($chr) => str_replace(['\'', '"'], '', strip_tags($chr));

        if (is_string($char)) return $formatter($char);

        foreach ($char as $key => $value) {
            if (is_string($value)) $char[$key] = $formatter($value);
            else if (is_array($value)) $char[$key] = $this->xssClean($value);
        }

        return $char;
    }
}