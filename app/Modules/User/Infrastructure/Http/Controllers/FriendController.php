<?php

namespace App\Modules\User\Infrastructure\Http\Controllers;

use App\Modules\User\Infrastructure\Http\Requests\SendFriendRequestRequest;
use App\Modules\User\Infrastructure\Http\Takes\AnswerFriendRequestTake;
use App\Modules\User\Infrastructure\Http\Takes\ListFriendRequestsTake;
use App\Modules\User\Infrastructure\Http\Takes\ListFriendsTake;
use App\Modules\User\Infrastructure\Http\Takes\RemoveFriendTake;
use App\Modules\User\Infrastructure\Http\Takes\SearchUsersTake;
use App\Modules\User\Infrastructure\Http\Takes\SendFriendRequestTake;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class FriendController extends Controller
{
    public function __construct(
        private readonly ListFriendsTake $listFriendsTake,
        private readonly SendFriendRequestTake $sendFriendRequestTake,
        private readonly ListFriendRequestsTake $listFriendRequestsTake,
        private readonly AnswerFriendRequestTake $answerFriendRequestTake,
        private readonly RemoveFriendTake $removeFriendTake,
        private readonly SearchUsersTake $searchUsersTake,
    ) {}

    public function index(string $userId): JsonResponse
    {
        return $this->listFriendsTake->handle($userId);
    }

    public function store(string $userId, SendFriendRequestRequest $request): JsonResponse
    {
        return $this->sendFriendRequestTake->handle($userId, $request);
    }

    public function requests(string $userId, Request $request): JsonResponse
    {
        return $this->listFriendRequestsTake->handle($userId, $request);
    }

    public function accept(string $userId, string $requestId): JsonResponse
    {
        return $this->answerFriendRequestTake->handle($userId, $requestId, true);
    }

    public function decline(string $userId, string $requestId): JsonResponse
    {
        return $this->answerFriendRequestTake->handle($userId, $requestId, false);
    }

    public function destroy(string $userId, string $friendshipId): JsonResponse
    {
        return $this->removeFriendTake->handle($userId, $friendshipId);
    }

    public function search(string $userId, Request $request): JsonResponse
    {
        return $this->searchUsersTake->handle($userId, $request);
    }
}
