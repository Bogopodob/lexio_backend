<?php

namespace App\Modules\User\Application\UseCases\Profile\UpsertUserProfile;

final readonly class UpsertUserProfileCommand
{
    /**
     * @param  list<string>|null  $tags
     * @param  array<string, list<string>>|null  $reminderSchedule
     */
    public function __construct(
        public string $userId,
        public ?string $name,
        public ?string $lastname,
        public ?string $surname,
        public ?string $avatar,
        public ?string $city = null,
        public ?string $birthDate = null,
        public ?array $tags = null,
        public ?array $reminderSchedule = null,
    ) {}
}
