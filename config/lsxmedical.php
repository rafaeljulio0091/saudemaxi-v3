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

    'base_url' => env('LSXMEDICAL_BASE_URL', 'https://homolog2.lsxmedical.com'),

    // Token de servico da clinica, usado em todos os endpoints /api/clinic/*.
    'service_token' => env('TELEMEDICINE_API_TOKEN'),

    'login_endpoint' => env('LSXMEDICAL_LOGIN_ENDPOINT', '/api/clinic/patients/authenticate'),

    'filter_patients_endpoint' => env('LSXMEDICAL_FILTER_PATIENTS_ENDPOINT', '/api/clinic/filter-patients/'),

    'create_patient_endpoint' => env('LSXMEDICAL_CREATE_PATIENT_ENDPOINT', '/api/clinic/create-patient/'),

    'timeout' => (int) env('LSXMEDICAL_TIMEOUT', 10),

];
