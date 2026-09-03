<?php

namespace App\Modules\Auth\Domain\Ports;

use App\Modules\Auth\Domain\Entities\AuthUser;
use DateTimeImmutable;

interface TokenIssuerInterface
{
    /**
     * @return array{token: string, expires_at: DateTimeImmutable, expires_in: int}
     */
    public function issue(AuthUser $user): array;
}
