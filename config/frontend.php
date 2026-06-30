<?php

$environment = env('APP_ENV', 'production');

$defaults = [
    'local' => [
        'url' => 'http://localhost:8000',
        'label' => 'Local',
    ],
    'staging' => [
        'url' => 'https://staging.molecularh2water.com',
        'label' => 'Staging',
    ],
    'production' => [
        'url' => 'https://www.molecularh2water.com',
        'label' => 'Production',
    ],
];

$selected = $defaults[$environment] ?? $defaults['production'];

return [

    /*
    |--------------------------------------------------------------------------
    | Public Frontend URL
    |--------------------------------------------------------------------------
    |
    | Base URL for the customer-facing MolecularH2Water site. Used for warranty
    | QR codes and admin links. Override with FRONTEND_URL per deployment.
    |
    */

    'url' => env('FRONTEND_URL', $selected['url']),

    'environment_label' => env('FRONTEND_ENV_LABEL', $selected['label']),

    'warranty_path' => '/warranty',

];
