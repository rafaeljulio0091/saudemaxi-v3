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

    'consultation_history_endpoint' => env('LSXMEDICAL_CONSULTATION_HISTORY_ENDPOINT', '/api/clinic/consultation-history/'),

    'scheduling' => [
        'specialties_endpoint' => env('LSXMEDICAL_SPECIALTIES_ENDPOINT', '/api/clinic/scheduling/specialties/'),
        'business_days_endpoint' => env('LSXMEDICAL_BUSINESS_DAYS_ENDPOINT', '/api/clinic/scheduling/business-days/'),
        'available_times_endpoint' => env('LSXMEDICAL_AVAILABLE_TIMES_ENDPOINT', '/api/clinic/scheduling/available-times/'),
        'doctors_endpoint' => env('LSXMEDICAL_DOCTORS_ENDPOINT', '/api/clinic/scheduling/doctors/'),
        'create_consultation_endpoint' => env('LSXMEDICAL_CREATE_CONSULTATION_ENDPOINT', '/api/clinic/scheduling/create-consultation/'),
    ],

    'timeout' => (int) env('LSXMEDICAL_TIMEOUT', 10),

];
