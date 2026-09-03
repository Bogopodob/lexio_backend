<?php

namespace App\Modules\User\Infrastructure\Http\Takes;

use App\Modules\User\Application\UseCases\Profile\GetUserProfile\GetUserProfileCommand;
use App\Modules\User\Application\UseCases\Profile\GetUserProfile\GetUserProfileUseCase;
use App\Modules\User\Infrastructure\Http\Resources\UserProfileResponseResource;
use Illuminate\Http\JsonResponse;

final readonly class GetUserProfileTake
{
    public function __construct(
        private GetUserProfileUseCase $useCase,
    ) {}

    public function handle(string $userId): JsonResponse
    {
        $result = $this->useCase->handle(new GetUserProfileCommand($userId));

        return UserProfileResponseResource::make($result)->response();
    }
}
