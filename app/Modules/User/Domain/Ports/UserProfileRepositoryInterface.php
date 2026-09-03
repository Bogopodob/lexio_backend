<?php

namespace App\Modules\User\Domain\Ports;

use App\Modules\User\Domain\Entities\UserProfile;

interface UserProfileRepositoryInterface
{
    public function findByUserId(string $userId): ?UserProfile;

    public function save(UserProfile $profile): UserProfile;
}
