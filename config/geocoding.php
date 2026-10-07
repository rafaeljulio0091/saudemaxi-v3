<?php

return [
    'enabled' => (bool) env('GEOCODING_ENABLED', false),
    'provider' => env('GEOCODING_PROVIDER', 'nominatim'),
    'base_url' => env('GEOCODING_BASE_URL', 'https://nominatim.openstreetmap.org'),
    'user_agent' => env('GEOCODING_USER_AGENT'),
    'contact_email' => env('GEOCODING_CONTACT_EMAIL'),
    'connect_timeout' => (int) env('GEOCODING_CONNECT_TIMEOUT', 5),
    'timeout' => (int) env('GEOCODING_TIMEOUT', 10),
    'request_interval_ms' => (int) env('GEOCODING_REQUEST_INTERVAL_MS', 1000),
    'attribution' => [
        'label' => '© OpenStreetMap contributors',
        'url' => 'https://www.openstreetmap.org/copyright',
    ],
];
