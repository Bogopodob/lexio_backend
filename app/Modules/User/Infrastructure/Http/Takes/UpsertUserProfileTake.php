<?php

namespace App\Modules\User\Infrastructure\Http\Takes;

use App\Modules\User\Application\UseCases\Profile\UpsertUserProfile\UpsertUserProfileCommand;
use App\Modules\User\Application\UseCases\Profile\UpsertUserProfile\UpsertUserProfileUseCase;
use App\Modules\User\Infrastructure\Http\Requests\UpsertUserProfileRequest;
use App\Modules\User\Infrastructure\Http\Resources\UserProfileResponseResource;
use Illuminate\Http\JsonResponse;

final readonly class UpsertUserProfileTake
{
    public function __construct(
        private UpsertUserProfileUseCase $useCase,
    ) {}

    public function handle(string $userId, UpsertUserProfileRequest $request): JsonResponse
    {
        $result = $this->useCase->handle(
            new UpsertUserProfileCommand(
                userId: $userId,
                name: $request->validated('name'),
                lastname: $request->validated('lastname'),
                surname: $request->validated('surname'),
                avatar: $request->validated('avatar'),
                city: $request->validated('city'),
                birthDate: $request->validated('birth_date'),
                tags: $request->validated('tags'),
                reminderSchedule: $request->validated('reminder_schedule'),
            )
        );

        return UserProfileResponseResource::make($result)->response();
    }
}
