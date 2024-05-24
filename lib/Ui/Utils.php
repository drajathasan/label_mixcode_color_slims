<?php
namespace Mixcode\Ui;

trait Utils
{
    public function xssClean(string|array $char):string|array
    {
        $formatter = function($chr) {
            if (substr($chr, 0,1) == '!') return trim($chr,'!');
            return str_replace(['\'', '"'], '', strip_tags($chr));
        };

        if (is_string($char)) return $formatter($char);

        foreach ($char as $key => $value) {
            if (is_string($value)) $char[$key] = $formatter($value);
            else if (is_array($value)) $char[$key] = $this->xssClean($value);
        }

        return $char;
    }

    public function isPlugin(string &$self = '')
    {
        return basename($self = $_SERVER['PHP_SELF']) === 'plugin_container.php';
    }

    public function getSelfUrl(array $additionalUrl = [])
    {
        $self = '';
        if ($this->isPlugin($self)) {
            unset($additionalUrl['id']);
            unset($additionalUrl['mod']);
        }

        $query = $_GET;
        foreach ($additionalUrl as $key => $value) {
            $query[$key] = $value;
        }

        return trim($self . ($query ? '?' . http_build_query($query) : ''));
    }
}