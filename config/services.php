<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Serviços de Terceiros
    |--------------------------------------------------------------------------
    |
    | Este arquivo armazena configurações e credenciais de serviços de
    | terceiros, como Resend, Postmark, AWS e outros. Ele fornece o local
    | convencional para que os pacotes encontrem essas informações.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'product_source' => [
        'base_url' => env('PRODUCT_SOURCE_BASE_URL', 'http://host.docker.internal:8001/api'),
        'timeout' => env('PRODUCT_SOURCE_TIMEOUT', 5),
    ],

];
