<?php

namespace App\AI\Exceptions;

use RuntimeException;
use Throwable;

class AiProviderException extends RuntimeException
{
    public function __construct(
        public readonly string $provider,
        public readonly string $model,
        public readonly string $errorCode,
        public readonly ?int $durationMs = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct('O provedor de inteligência artificial não concluiu a operação.', 0, $previous);
    }
}
