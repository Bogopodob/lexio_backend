<?php

namespace App\Modules\Learning\Application\UseCases\GetAvailability;

final readonly class GetAvailabilityCommand
{
    public function __construct(
        public string $profileId,
        public string $userId,
        public ?string $categoryId = null,
        public ?string $level = null,
    ) {}
}
