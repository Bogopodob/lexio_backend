<?php

namespace App\Modules\Learning\Application\UseCases\UpdateProfile;

final readonly class UpdateProfileCommand
{
    public function __construct(
        public string $profileId,
        public string $userId,
        public ?string $level = null,
        public ?int $dailyGoal = null,
        public ?bool $isActive = null,
    ) {}
}
