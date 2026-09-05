<?php

namespace App\Modules\User\Infrastructure\Http\Takes;

use App\Modules\User\Application\UseCases\Friends\AnswerFriendRequest\AnswerFriendRequestCommand;
use App\Modules\User\Application\UseCases\Friends\AnswerFriendRequest\AnswerFriendRequestUseCase;
use App\Modules\User\Infrastructure\Http\Resources\FriendResponseResource;
use Illuminate\Http\JsonResponse;

final readonly class AnswerFriendRequestTake
{
    public function __construct(
        private AnswerFriendRequestUseCase $useCase,
    ) {}

    public function handle(string $userId, string $requestId, bool $accept): JsonResponse
    {
        $result = $this->useCase->handle(new AnswerFriendRequestCommand($userId, $requestId, $accept));

        if (! $result) {
            return response()->json(['success' => false, 'message' => __('api.request.not_found')], 404);
        }

        return FriendResponseResource::make(['status' => $result->status])->response();
    }
}
