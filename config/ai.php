<?php

return [
    'openai' => [
        'enabled' => (bool) env('OPENAI_TRIAGE_ENABLED', false),
        'base_url' => env('OPENAI_API_URL', 'https://api.openai.com/v1'),
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_TRIAGE_MODEL'),
        'connect_timeout' => (int) env('OPENAI_CONNECT_TIMEOUT', 5),
        'timeout' => (int) env('OPENAI_TRIAGE_TIMEOUT', 30),
    ],

    'jev' => [
        'enabled' => (bool) env('JEV_ENABLED', false),
        'api_url' => env('JEV_API_URL', 'https://api.typesafe.ai/v1/systemone'),
        'api_key' => env('JEV_API_KEY'),
        'model' => env('JEV_MODEL', 'jev-latest'),
        'connect_timeout' => (int) env('JEV_CONNECT_TIMEOUT', 5),
        'timeout' => (int) env('JEV_TIMEOUT', 15),
    ],
];
