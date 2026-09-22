<?php

return [
    'demo_enabled' => (bool) env('HEALTHCARE_DEMO_ENABLED', false),

    // Domain suffix used to resolve a tenant (municipio/empresa) from the
    // request subdomain, e.g. "queimados" in queimados.saudemaxi.com.br.
    'tenant_base_domain' => env('TENANT_BASE_DOMAIN', 'saudemaxi.test'),
];
