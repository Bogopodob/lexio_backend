<?php

namespace App\Modules\User\Infrastructure\Http\Resources;

use App\Modules\User\Domain\Entities\UserProfile;
use Illuminate\Http\Resources\Json\JsonResource;

final class UserProfileResponseResource extends JsonResource
{
    public function __construct(private readonly UserProfile $profile)
    {
        parent::__construct($profile);
    }

    public function toArray($request): array
    {
        return [
            'success' => true,
            'data' => UserProfileResource::make($this->profile)->resolve($request),
        ];
    }
}
