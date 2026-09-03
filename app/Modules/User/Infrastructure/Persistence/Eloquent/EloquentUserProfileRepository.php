<?php

namespace App\Modules\User\Infrastructure\Persistence\Eloquent;

use App\Modules\User\Domain\Entities\UserProfile;
use App\Modules\User\Domain\Ports\UserProfileRepositoryInterface;
use App\Modules\User\Infrastructure\Persistence\Eloquent\Models\UserProfile as UserProfileModel;

final class EloquentUserProfileRepository implements UserProfileRepositoryInterface
{
    public function findByUserId(string $userId): ?UserProfile
    {
        $profile = UserProfileModel::query()->find($userId);

        return $profile ? $this->toDomain($profile) : null;
    }

    public function save(UserProfile $profile): UserProfile
    {
        $model = UserProfileModel::query()->updateOrCreate(
            ['user_id' => $profile->userId],
            [
                'name' => $this->nullableTrim($profile->name),
                'lastname' => $this->nullableTrim($profile->lastname),
                'surname' => $this->nullableTrim($profile->surname),
                'avatar' => $this->nullableTrim($profile->avatar),
                'city' => $this->nullableTrim($profile->city),
                'birth_date' => $profile->birthDate,
                'tags' => $profile->tags === null ? null : array_values($profile->tags),
            ],
        );

        return $this->toDomain($model);
    }

    private function toDomain(UserProfileModel $profile): UserProfile
    {
        return new UserProfile(
            userId: (string) $profile->user_id,
            name: $profile->name,
            lastname: $profile->lastname,
            surname: $profile->surname,
            avatar: $profile->avatar,
            city: $profile->city,
            birthDate: $profile->birth_date ? $profile->birth_date->format('Y-m-d') : null,
            tags: $profile->tags === null ? null : array_values((array) $profile->tags),
        );
    }

    private function nullableTrim(?string $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
