<?php

namespace App\Modules\Auth\Domain\Ports;

use App\Modules\Auth\Domain\Entities\VerifiedAccessToken;

interface AccessTokenVerifierInterface
{
    public function verify(string $token): ?VerifiedAccessToken;
}
