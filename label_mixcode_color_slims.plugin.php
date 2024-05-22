<?php
/**
 * Plugin Name: Label Mixcode Color Slims
 * Plugin URI: https://github.com/drajathasan/label_mixcode_color_slims
 * Description: Plugin pengganti label_barcode_color_slims dengan fitur baru dan support SLiMS 9 terbaru
 * Version: 2.0.0
 * Author: Drajat Hasan
 * Author URI: https://t.me/drajathasan/
 */
 use SLiMS\Plugins;

// require autoload
require __DIR__ . '/vendor/autoload.php';

// registering menus
Plugins::getInstance()
    ->registerMenu(
        module_name: 'bibliography', 
        label: 'Label Mixcode Color', 
        path: __DIR__ . '/pages/print.php'
    );
