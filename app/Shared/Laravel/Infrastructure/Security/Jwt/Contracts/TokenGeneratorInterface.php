<?php

namespace App\Shared\Laravel\Infrastructure\Security\Jwt\Contracts;

use App\Shared\Laravel\Infrastructure\Security\Jwt\DTO\TokenPayloadDTO;
use App\Shared\Laravel\Infrastructure\Security\Jwt\DTO\TokenResultDTO;

interface TokenGeneratorInterface
{
    public function generate(TokenPayloadDTO $payload): TokenResultDTO;

    public function verify(string $token): ?TokenPayloadDTO;

    public function revoke(string $token): void;
}
