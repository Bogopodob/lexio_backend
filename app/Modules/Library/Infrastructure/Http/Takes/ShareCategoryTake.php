<?php

namespace App\Modules\Library\Infrastructure\Http\Takes;

use App\Modules\Library\Application\UseCases\ListShares\ListSharesCommand;
use App\Modules\Library\Application\UseCases\ListShares\ListSharesUseCase;
use App\Modules\Library\Application\UseCases\RevokeShare\RevokeShareCommand;
use App\Modules\Library\Application\UseCases\RevokeShare\RevokeShareUseCase;
use App\Modules\Library\Application\UseCases\ShareCategory\ShareCategoryCommand;
use App\Modules\Library\Application\UseCases\ShareCategory\ShareCategoryUseCase;
use App\Modules\Library\Application\UseCases\SharedWithMe\SharedWithMeCommand;
use App\Modules\Library\Application\UseCases\SharedWithMe\SharedWithMeUseCase;
use App\Modules\Library\Infrastructure\Http\Requests\ShareCategoryRequest;
use App\Modules\Library\Infrastructure\Http\Resources\LibraryResponseResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class ShareCategoryTake
{
    public function __construct(
        private ShareCategoryUseCase $share,
        private ListSharesUseCase $list,
        private RevokeShareUseCase $revoke,
        private SharedWithMeUseCase $sharedWithMe,
    ) {}

    public function grant(string $userId, ShareCategoryRequest $request): JsonResponse
    {
        $share = $this->share->handle(new ShareCategoryCommand(
            userId: $userId,
            categoryId: (string) $request->validated('category_id'),
            friendId: (string) $request->validated('friend_user_id'),
        ));

        if (! $share) {
            return response()->json(['success' => false, 'message' => __('api.share.forbidden')], 403);
        }

        return LibraryResponseResource::make([
            'id' => $share->id,
            'category_id' => $share->categoryId,
            'friend_user_id' => $share->friendUserId,
            'friend_name' => $share->friendName,
        ])->response()->setStatusCode(201);
    }

    public function index(string $userId, Request $request): JsonResponse
    {
        $categoryId = (string) $request->query('category_id', '');

        if ($categoryId === '') {
            return response()->json(['success' => false, 'message' => __('api.share.forbidden')], 422);
        }

        $shares = $this->list->handle(new ListSharesCommand($userId, $categoryId));

        return LibraryResponseResource::make(array_map(fn ($s) => [
            'id' => $s->id,
            'friend_user_id' => $s->friendUserId,
            'friend_name' => $s->friendName,
        ], $shares))->response();
    }

    public function destroy(string $userId, string $shareId): JsonResponse
    {
        $deleted = $this->revoke->handle(new RevokeShareCommand($userId, $shareId));

        if (! $deleted) {
            return response()->json(['success' => false, 'message' => __('api.share.forbidden')], 404);
        }

        return LibraryResponseResource::make(['deleted' => true])->response();
    }

    public function shared(string $userId, Request $request): JsonResponse
    {
        $locale = (string) $request->query('locale', 'ru');

        $result = $this->sharedWithMe->handle(new SharedWithMeCommand(
            userId: $userId,
            locale: in_array($locale, ['ru', 'en'], true) ? $locale : 'ru',
        ));

        return LibraryResponseResource::make($result)->response();
    }
}
