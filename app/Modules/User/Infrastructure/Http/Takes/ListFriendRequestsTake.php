<?php

namespace App\Modules\User\Infrastructure\Http\Takes;

use App\Modules\User\Application\UseCases\Friends\ListFriendRequests\ListFriendRequestsCommand;
use App\Modules\User\Application\UseCases\Friends\ListFriendRequests\ListFriendRequestsUseCase;
use App\Modules\User\Infrastructure\Http\Resources\FriendResponseResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class ListFriendRequestsTake
{
    public function __construct(
        private ListFriendRequestsUseCase $useCase,
    ) {}

    public function handle(string $userId, Request $request): JsonResponse
    {
        $direction = $request->query('direction', 'incoming');

        $result = $this->useCase->handle(
            new ListFriendRequestsCommand($userId, $direction === 'outgoing' ? 'outgoing' : 'incoming')
        );

        return FriendResponseResource::make($result)->response();
    }
}
