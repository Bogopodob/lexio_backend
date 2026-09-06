<?php

namespace App\Modules\Library\Application\UseCases\RevokeShare;

final readonly class RevokeShareCommand
{
    public function __construct(
        public string $userId,
        public string $shareId,
    ) {}
}
