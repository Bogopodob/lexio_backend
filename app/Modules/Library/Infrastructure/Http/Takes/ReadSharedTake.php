<?php

namespace App\Modules\Library\Infrastructure\Http\Takes;

use App\Modules\Library\Application\UseCases\ReadShared\ReadSharedUseCase;
use App\Modules\Library\Infrastructure\Http\Resources\LibraryResponseResource;
use App\Modules\Library\Infrastructure\Http\Resources\UserEntryResource;
use App\Modules\Library\Infrastructure\Http\Resources\UserPhraseResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class ReadSharedTake
{
    public function __construct(
        private ReadSharedUseCase $useCase,
    ) {}

    /**
     * @return ?string valid category id or null
     */
    private function categoryId(Request $request): ?string
    {
        $id = (string) $request->query('category_id', '');

        return preg_match('/^[0-9a-fA-F-]{36}$/', $id) ? $id : null;
    }

    private function forbidden(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => __('api.share.forbidden')], 404);
    }

    public function entries(string $userId, Request $request): JsonResponse
    {
        $categoryId = $this->categoryId($request);

        if ($categoryId === null) {
            return $this->forbidden();
        }

        $result = $this->useCase->entries($userId, $categoryId);

        if ($result === null) {
            return $this->forbidden();
        }

        return LibraryResponseResource::make(
            array_map(fn ($e) => UserEntryResource::make($e)->resolve($request), $result)
        )->response();
    }

    public function entry(string $userId, string $entryId, Request $request): JsonResponse
    {
        $categoryId = $this->categoryId($request);

        if ($categoryId === null) {
            return $this->forbidden();
        }

        $result = $this->useCase->entry($userId, $categoryId, $entryId);

        if ($result === null) {
            return $this->forbidden();
        }

        return LibraryResponseResource::make(
            UserEntryResource::make($result)->resolve($request)
        )->response();
    }

    public function phrases(string $userId, Request $request): JsonResponse
    {
        $categoryId = $this->categoryId($request);

        if ($categoryId === null) {
            return $this->forbidden();
        }

        $result = $this->useCase->phrases($userId, $categoryId);

        if ($result === null) {
            return $this->forbidden();
        }

        return LibraryResponseResource::make(
            array_map(fn ($p) => UserPhraseResource::make($p)->resolve($request), $result)
        )->response();
    }

    public function phrase(string $userId, string $phraseId, Request $request): JsonResponse
    {
        $categoryId = $this->categoryId($request);

        if ($categoryId === null) {
            return $this->forbidden();
        }

        $result = $this->useCase->phrase($userId, $categoryId, $phraseId);

        if ($result === null) {
            return $this->forbidden();
        }

        return LibraryResponseResource::make(
            UserPhraseResource::make($result)->resolve($request)
        )->response();
    }
}
