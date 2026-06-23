<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'broadcasting/auth'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter(explode(',', env('CORS_ALLOWED_ORIGINS',
        'http://localhost:3006,http://localhost:3000,http://127.0.0.1:3006,http://localhost:5173,http://127.0.0.1:5173,http://localhost:8100'
    ))),

    'allowed_origins_patterns' => array_filter(explode(',', env('CORS_ALLOWED_ORIGIN_PATTERNS',
        '#^https?://localhost(:\d+)?$#,#^https?://127\.0\.0\.1(:\d+)?$#,#^capacitor://localhost$#,#^ionic://localhost$#,#^https?://192\.168\.\d{1,3}\.\d{1,3}(:\d+)?$#,#^https?://10\.\d{1,3}\.\d{1,3}\.\d{1,3}(:\d+)?$#'
    ))),

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
