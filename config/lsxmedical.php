<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Login via API lsxmedical
    |--------------------------------------------------------------------------
    |
    | Quando habilitado, a autenticacao do login e feita atraves da API
    | externa da lsxmedical. Quando desabilitado (padrao), a autenticacao
    | continua usando o banco de dados local do projeto (Auth::attempt).
    |
    */

    'login_enabled' => (bool) env('LSXMEDICAL_LOGIN_ENABLED', false),

    'base_url' => env('LSXMEDICAL_BASE_URL', 'https://api.lsxmedical.com.br'),

    'service_token' => env('LSXMEDICAL_SERVICE_TOKEN'),

    'login_endpoint' => env('LSXMEDICAL_LOGIN_ENDPOINT', '/api/clinic/patients/authenticate'),

    'timeout' => (int) env('LSXMEDICAL_TIMEOUT', 10),

];
