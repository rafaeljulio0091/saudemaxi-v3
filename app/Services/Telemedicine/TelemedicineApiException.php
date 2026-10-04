<?php

namespace App\Services\Telemedicine;

use RuntimeException;

class TelemedicineApiException extends RuntimeException
{
    /**
     * @param  'timeout'|'unavailable'|'unauthorized'|'invalid'|'invalid_response'|'not_found'|'business_rejection'|'local_persistence'  $reason
     * @param  array<string, mixed>  $errors
     */
    public function __construct(
        string $message,
        public readonly string $reason,
        public readonly ?int $status = null,
        public readonly array $errors = [],
    ) {
        parent::__construct($message);
    }
}
