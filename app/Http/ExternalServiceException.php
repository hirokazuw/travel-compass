<?php

declare(strict_types=1);

namespace App\Http;

final class ExternalServiceException extends \RuntimeException
{
    public function __construct(string $message, public readonly string $service, public readonly ?int $httpStatus)
    {
        parent::__construct($message);
    }
}
