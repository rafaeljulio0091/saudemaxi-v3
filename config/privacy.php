<?php

return [
    /*
    | A stable, server-side key used only for deterministic lookup indexes.
    | Define a dedicated value in production before the first patient record.
    */
    'identifier_hash_key' => env('PRIVACY_IDENTIFIER_HASH_KEY') ?: env('APP_KEY'),
];
