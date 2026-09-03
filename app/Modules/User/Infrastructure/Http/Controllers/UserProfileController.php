<?php

namespace App\Modules\User\Infrastructure\Http\Controllers;

use App\Modules\User\Infrastructure\Http\Requests\UpsertUserProfileRequest;
use App\Modules\User\Infrastructure\Http\Takes\GetUserProfileTake;
use App\Modules\User\Infrastructure\Http\Takes\UpsertUserProfileTake;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class UserProfileController extends Controller
{
    public function __construct(
        private readonly GetUserProfileTake $getUserProfileTake,
        private readonly UpsertUserProfileTake $upsertUserProfileTake,
    ) {}

    public function show(string $userId): JsonResponse
    {
        return $this->getUserProfileTake->handle($userId);
    }

    public function upsert(string $userId, UpsertUserProfileRequest $request): JsonResponse
    {
        return $this->upsertUserProfileTake->handle($userId, $request);
    }
}
