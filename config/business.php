<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Business lines — one owner, two brands
    |--------------------------------------------------------------------------
    |
    | HappyCookingCo (HCC): cookware
    | H2S: hydrogen machines and health products
    |
    */

    'lines' => [
        'hcc' => [
            'name' => 'HappyCookingCo',
            'short' => 'HCC',
            'color' => 'orange',
        ],
        'h2s' => [
            'name' => 'H2S',
            'short' => 'H2S',
            'color' => 'cyan',
        ],
    ],

    'calendar_type_lines' => [
        'cooking-show' => 'hcc',
        'water-awareness-show' => 'h2s',
        'home-demo' => 'h2s',
        'product-demo' => 'h2s',
        'post-show-follow-up' => 'h2s',
    ],

    'landing_page_lines' => [
        'water-awareness-show' => 'h2s',
    ],

];
