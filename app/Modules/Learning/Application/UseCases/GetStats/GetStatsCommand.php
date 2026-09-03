<?php

namespace App\Modules\Learning\Application\UseCases\GetStats;

final readonly class GetStatsCommand
{
    public function __construct(
        public string $profileId,
        public string $userId,
    ) {}
}
