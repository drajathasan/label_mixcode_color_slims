<?php
/**
 * Plugin Name: Label Mixcode Color Slims
 * Plugin URI: https://github.com/drajathasan/label_mixcode_color_slims
 * Description: Plugin pengganti label_barcode_color_slims dengan fitur baru dan support SLiMS 9 terbaru
 * Version: 1.0.0
 * Author: Drajat Hasan
 * Author URI: https://github.com/drajathasan/
 */

// get plugin instance
$plugin = \SLiMS\Plugins::getInstance();

// registering menus
$plugin->registerMenu('bibliography', 'Label Mixcode Color', __DIR__ . '/index.php');
