<?php

namespace App\Modules\User\Infrastructure\Http\Takes;

use App\Modules\User\Application\UseCases\Friends\ListLeaderboard\ListLeaderboardCommand;
use App\Modules\User\Application\UseCases\Friends\ListLeaderboard\ListLeaderboardUseCase;
use App\Modules\User\Infrastructure\Http\Resources\FriendResponseResource;
use App\Modules\User\Infrastructure\Http\Resources\LeaderboardResource;
use Illuminate\Http\JsonResponse;

final readonly class ListLeaderboardTake
{
    public function __construct(
        private ListLeaderboardUseCase $useCase,
    ) {}

    public function handle(string $userId): JsonResponse
    {
        $result = $this->useCase->handle(new ListLeaderboardCommand($userId));

        $data = array_map(fn ($r) => LeaderboardResource::make($r)->resolve(request()), $result);

        return FriendResponseResource::make($data)->response();
    }
}
