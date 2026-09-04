<?php

namespace App\Modules\Learning\Application\UseCases\GetNextCard;

final readonly class GetNextCardCommand
{
    public function __construct(
        public string $sessionId,
        public string $userId,
    ) {}
}
