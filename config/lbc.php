<?php
return [
    'settings' => [
        'chunk' => 2,
        'template' => 'right',
        'code_type' => 'barcode',
        'margin_page' => [
            'top' => '5mm',
            'right' => '5mm',
            'bottom' => '5mm',
            'left' => '5mm'
        ],
        'page_break_at' => 6,
        'auto_print' => true
    ],
    'template_sample' => [
        'item_code' => 'B00017', 
        'call_number_font_size' => 'text-sm', 
        'call_number' => '7965.555 919 Har n', 
        'width_box' => 20, 
        'height_box' => 10, 
        'width_barcode' => 8,
        'height_barcode' => 4, 
        'top_barcode' => 3.5, 
        'left_barcode' => -5
    ],
    'saved_template_setting' => [],
    'colors' => [
        '0XX' => '#ffffff', 
        '1XX' => '#ffffff', 
        '2XX' => '#ffffff', 
        '3XX' => '#ffffff', 
        '4XX' => '#ffffff', 
        '5XX' => '#ffffff',
        '6XX' => '#ffffff', 
        '7XX' => '#ffffff',
        '8XX' => '#ffffff',
        '9XX' => '#ffffff'
    ]
];