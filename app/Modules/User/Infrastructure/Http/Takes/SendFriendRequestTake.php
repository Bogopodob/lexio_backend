<?php

namespace App\Modules\User\Infrastructure\Http\Takes;

use App\Modules\User\Application\UseCases\Friends\SendFriendRequest\SendFriendRequestCommand;
use App\Modules\User\Application\UseCases\Friends\SendFriendRequest\SendFriendRequestUseCase;
use App\Modules\User\Infrastructure\Http\Requests\SendFriendRequestRequest;
use App\Modules\User\Infrastructure\Http\Resources\FriendResponseResource;
use Illuminate\Http\JsonResponse;

final readonly class SendFriendRequestTake
{
    public function __construct(
        private SendFriendRequestUseCase $useCase,
    ) {}

    public function handle(string $userId, SendFriendRequestRequest $request): JsonResponse
    {
        $result = $this->useCase->handle(
            new SendFriendRequestCommand(
                userId: $userId,
                addresseeId: $request->validated('user_id'),
                email: $request->validated('email'),
            )
        );

        if (! $result) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        return FriendResponseResource::make([
            'status' => $result['status'],
            'request_id' => $result['friendship']->id,
        ])->response()->setStatusCode(201);
    }
}
