<?php

namespace App\Modules\User\Infrastructure\Http\Resources;

use App\Modules\User\Domain\Entities\UserProfile;
use Illuminate\Http\Resources\Json\JsonResource;

final class UserProfileResource extends JsonResource
{
    public function __construct(private readonly UserProfile $profile)
    {
        parent::__construct($profile);
    }

    public function toArray($request): array
    {
        return [
            'user_id' => $this->profile->userId,
            'name' => $this->profile->name,
            'lastname' => $this->profile->lastname,
            'surname' => $this->profile->surname,
            'avatar' => $this->profile->avatar,
            'city' => $this->profile->city,
            'birth_date' => $this->profile->birthDate,
            'tags' => $this->profile->tags ?? [],
        ];
    }
}
