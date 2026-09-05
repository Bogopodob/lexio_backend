<?php

namespace App\Modules\User\Infrastructure\Http\Takes;

use App\Modules\User\Application\UseCases\Friends\RemoveFriend\RemoveFriendCommand;
use App\Modules\User\Application\UseCases\Friends\RemoveFriend\RemoveFriendUseCase;
use App\Modules\User\Infrastructure\Http\Resources\FriendResponseResource;
use Illuminate\Http\JsonResponse;

final readonly class RemoveFriendTake
{
    public function __construct(
        private RemoveFriendUseCase $useCase,
    ) {}

    public function handle(string $userId, string $friendshipId): JsonResponse
    {
        $deleted = $this->useCase->handle(new RemoveFriendCommand($userId, $friendshipId));

        if (! $deleted) {
            return response()->json(['success' => false, 'message' => __('api.friendship.not_found')], 404);
        }

        return FriendResponseResource::make(['deleted' => true])->response();
    }
}
