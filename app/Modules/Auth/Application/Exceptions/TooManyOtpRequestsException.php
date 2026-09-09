<?php

namespace App\Modules\Auth\Application\Exceptions;

use Exception;

final class TooManyOtpRequestsException extends Exception
{
    public function __construct(
        string $message = 'Too many code requests',
        public readonly int $retryAfter = 60,
    ) {
        parent::__construct($message);
    }
}
