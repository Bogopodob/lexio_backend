<?php

namespace App\Modules\Library\Infrastructure\Http\Controllers;

use App\Modules\Library\Infrastructure\Http\Requests\SaveUserEntryRequest;
use App\Modules\Library\Infrastructure\Http\Requests\SaveUserPhraseRequest;
use App\Modules\Library\Infrastructure\Http\Takes\ListUserEntriesTake;
use App\Modules\Library\Infrastructure\Http\Takes\ListUserPhrasesTake;
use App\Modules\Library\Infrastructure\Http\Takes\SaveUserEntryTake;
use App\Modules\Library\Infrastructure\Http\Takes\SaveUserPhraseTake;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class LibraryController extends Controller
{
    public function __construct(
        private readonly SaveUserEntryTake $saveUserEntryTake,
        private readonly ListUserEntriesTake $listUserEntriesTake,
        private readonly SaveUserPhraseTake $saveUserPhraseTake,
        private readonly ListUserPhrasesTake $listUserPhrasesTake,
    ) {}

    public function storeEntry(string $userId, SaveUserEntryRequest $request): JsonResponse
    {
        return $this->saveUserEntryTake->handle($userId, $request);
    }

    public function indexEntries(string $userId, Request $request): JsonResponse
    {
        return $this->listUserEntriesTake->handle($userId, $request);
    }

    public function storePhrase(string $userId, SaveUserPhraseRequest $request): JsonResponse
    {
        return $this->saveUserPhraseTake->handle($userId, $request);
    }

    public function indexPhrases(string $userId, Request $request): JsonResponse
    {
        return $this->listUserPhrasesTake->handle($userId, $request);
    }
}
