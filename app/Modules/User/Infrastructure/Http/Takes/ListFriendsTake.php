<?php

namespace App\Modules\User\Infrastructure\Http\Takes;

use App\Modules\User\Application\UseCases\Friends\ListFriends\ListFriendsCommand;
use App\Modules\User\Application\UseCases\Friends\ListFriends\ListFriendsUseCase;
use App\Modules\User\Infrastructure\Http\Resources\FriendResource;
use App\Modules\User\Infrastructure\Http\Resources\FriendResponseResource;
use Illuminate\Http\JsonResponse;

final readonly class ListFriendsTake
{
    public function __construct(
        private ListFriendsUseCase $useCase,
    ) {}

    public function handle(string $userId): JsonResponse
    {
        $result = $this->useCase->handle(new ListFriendsCommand($userId));

        $data = array_map(fn ($f) => FriendResource::make($f)->resolve(request()), $result);

        return FriendResponseResource::make($data)->response();
    }
}
