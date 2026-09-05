<?php

namespace App\Modules\User\Infrastructure\Http\Controllers;

use App\Modules\User\Infrastructure\Http\Requests\AvatarUploadRequest;
use App\Modules\User\Infrastructure\Http\Requests\UpsertUserProfileRequest;
use App\Modules\User\Infrastructure\Http\Takes\GetUserProfileTake;
use App\Modules\User\Infrastructure\Http\Takes\StoreAvatarTake;
use App\Modules\User\Infrastructure\Http\Takes\StreamAvatarTake;
use App\Modules\User\Infrastructure\Http\Takes\UpsertUserProfileTake;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class UserProfileController extends Controller
{
    public function __construct(
        private readonly GetUserProfileTake $getUserProfileTake,
        private readonly UpsertUserProfileTake $upsertUserProfileTake,
        private readonly StoreAvatarTake $storeAvatarTake,
        private readonly StreamAvatarTake $streamAvatarTake,
    ) {}

    public function show(string $userId): JsonResponse
    {
        return $this->getUserProfileTake->handle($userId);
    }

    public function upsert(string $userId, UpsertUserProfileRequest $request): JsonResponse
    {
        return $this->upsertUserProfileTake->handle($userId, $request);
    }

    public function storeAvatar(string $userId, AvatarUploadRequest $request): JsonResponse
    {
        return $this->storeAvatarTake->handle($userId, $request);
    }

    public function avatar(string $userId): BinaryFileResponse|JsonResponse
    {
        return $this->streamAvatarTake->handle($userId);
    }
}
