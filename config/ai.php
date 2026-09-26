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

    // MAX assistant: deterministic rules always run; the providers above are
    // only used when this flag is on (Jev classifies, OpenAI writes).
    'max' => [
        'enabled' => (bool) env('MAX_ASSISTANT_AI_ENABLED', false),
        'openai_model' => env('OPENAI_MAX_MODEL', env('OPENAI_TRIAGE_MODEL')),
        'openai_timeout' => (int) env('OPENAI_MAX_TIMEOUT', 15),
        'min_confidence' => (float) env('MAX_ASSISTANT_MIN_CONFIDENCE', 0.70),
        'emergency_probability' => (float) env('MAX_ASSISTANT_EMERGENCY_PROBABILITY', 0.30),
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
