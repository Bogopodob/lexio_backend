<?php

namespace App\Modules\User\Infrastructure\Http\Takes;

use App\Modules\User\Application\UseCases\Friends\SearchUsers\SearchUsersCommand;
use App\Modules\User\Application\UseCases\Friends\SearchUsers\SearchUsersUseCase;
use App\Modules\User\Infrastructure\Http\Resources\FriendResponseResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class SearchUsersTake
{
    public function __construct(
        private SearchUsersUseCase $useCase,
    ) {}

    public function handle(string $userId, Request $request): JsonResponse
    {
        $result = $this->useCase->handle(
            new SearchUsersCommand(
                userId: $userId,
                query: (string) $request->query('query', ''),
                limit: max(1, min(20, (int) $request->query('limit', 10))),
            )
        );

        return FriendResponseResource::make($result)->response();
    }
}
