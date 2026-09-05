<?php

namespace App\Modules\User\Infrastructure\Http\Takes;

use App\Modules\User\Application\UseCases\Profile\StoreAvatar\StoreAvatarCommand;
use App\Modules\User\Application\UseCases\Profile\StoreAvatar\StoreAvatarUseCase;
use App\Modules\User\Infrastructure\Http\Requests\AvatarUploadRequest;
use App\Modules\User\Infrastructure\Security\InvalidImageException;
use Illuminate\Http\JsonResponse;

final readonly class StoreAvatarTake
{
    public function __construct(
        private StoreAvatarUseCase $useCase,
    ) {}

    public function handle(string $userId, AvatarUploadRequest $request): JsonResponse
    {
        $file = $request->file('avatar');

        if (! $file || ! $file->isValid()) {
            return response()->json(['success' => false, 'message' => __('api.avatar.upload_failed')], 422);
        }

        try {
            $result = $this->useCase->handle(new StoreAvatarCommand($userId), $file);
        } catch (InvalidImageException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'data' => $result]);
    }
}
