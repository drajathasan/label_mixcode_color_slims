<?php
use SLiMS\Config;
use SLiMS\Migration\Migration;

class MovingOldSetting extends Migration
{
    function camelToSnake(string $input)
    {
        if (preg_match_all('/[A-Z]/', $key, $match)) {
            return str_replace($match[0], array_map(function($item) {
                return '_' . strtolower($item);
            }, $match[0]), $input);
        }

        return $input;
    }

    function up()
    {
        $newConfig = require __DIR__ . '/../config/lbc.php';
        $oldConfig = config('lbc_settings');

        if ($oldConfig) {
            foreach ($oldConfig as $key => $value) {
                $newConfig['settings'][$this->ccamelToSnake($key)] = $value;
            } 
        }

        foreach (['right','left','both'] as $type) {
            $positionType = config('lbc_' . $type . 'Code');
            if ($positionType) {
                foreach ($positionType as $key => $value) {
                    $key = $this->camelToSnake($key);
                    $positionType[$key] = $value; 
                }
                $newConfig['saved_template_setting'][$type] = $positionType;
            }
        }

        if (config('lbc_color')) {
            $newConfig['colors'] = config('lbc_color'); 
        }

        Config::createOrUpdate('lbc', $newConfig);
    }

    function down()
    {

    }
}