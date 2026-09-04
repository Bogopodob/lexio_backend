<?php

namespace App\Modules\Learning\Application\UseCases\FinishSession;

final readonly class FinishSessionCommand
{
    public function __construct(
        public string $sessionId,
        public string $userId,
    ) {}
}
