<?php

return [
    'default' => env('GEO_PROVIDER', 'brasilapi'),

    'providers' => [
        'brasilapi' => [
            'base_url' => rtrim(env('BRASILAPI_BASE_URL', 'https://brasilapi.com.br/api/cep/v2'), '/'),
            'timeout' => (int) env('BRASILAPI_TIMEOUT', 10),
        ],
    ],
];
