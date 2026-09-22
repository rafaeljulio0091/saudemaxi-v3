<?php

return [
    // Base URL of the external telemedicine provider's clinic API.
    // Real contract documented under /api/clinic/, see AGENTS.md section 12.
    'base_url' => env('TELEMEDICINE_API_URL'),

    // Clinic service bearer token. Never exposed to the browser.
    'token' => env('TELEMEDICINE_API_TOKEN'),

    'timeout' => (int) env('TELEMEDICINE_API_TIMEOUT', 15),
];
