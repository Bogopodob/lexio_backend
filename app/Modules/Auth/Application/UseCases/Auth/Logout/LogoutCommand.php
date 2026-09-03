<?php

namespace App\Modules\Auth\Application\UseCases\Auth\Logout;

final readonly class LogoutCommand
{
    public function __construct(
        public string $token,
    ) {}
}
