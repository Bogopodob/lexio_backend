<?php

namespace App\Modules\Library\Infrastructure\Http\Controllers;

use App\Modules\Library\Infrastructure\Http\Requests\SaveUserEntryRequest;
use App\Modules\Library\Infrastructure\Http\Requests\SaveUserPhraseRequest;
use App\Modules\Library\Infrastructure\Http\Requests\ShareCategoryRequest;
use App\Modules\Library\Infrastructure\Http\Requests\SpeakTextRequest;
use App\Modules\Library\Infrastructure\Http\Requests\UploadMediaRequest;
use App\Modules\Library\Infrastructure\Http\Takes\ListUserEntriesTake;
use App\Modules\Library\Infrastructure\Http\Takes\ListUserPhrasesTake;
use App\Modules\Library\Infrastructure\Http\Takes\ReadSharedTake;
use App\Modules\Library\Infrastructure\Http\Takes\SaveUserEntryTake;
use App\Modules\Library\Infrastructure\Http\Takes\SaveUserPhraseTake;
use App\Modules\Library\Infrastructure\Http\Takes\ShareCategoryTake;
use App\Modules\Library\Infrastructure\Http\Takes\SpeakTextTake;
use App\Modules\Library\Infrastructure\Http\Takes\StreamMediaTake;
use App\Modules\Library\Infrastructure\Http\Takes\UploadMediaTake;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class LibraryController extends Controller
{
    public function __construct(
        private readonly SaveUserEntryTake $saveUserEntryTake,
        private readonly ListUserEntriesTake $listUserEntriesTake,
        private readonly SaveUserPhraseTake $saveUserPhraseTake,
        private readonly ListUserPhrasesTake $listUserPhrasesTake,
        private readonly UploadMediaTake $uploadMediaTake,
        private readonly StreamMediaTake $streamMediaTake,
        private readonly SpeakTextTake $speakTextTake,
        private readonly ShareCategoryTake $shareCategoryTake,
        private readonly ReadSharedTake $readSharedTake,
    ) {}

    public function storeEntry(string $userId, SaveUserEntryRequest $request): JsonResponse
    {
        return $this->saveUserEntryTake->handle($userId, $request);
    }

    public function indexEntries(string $userId, Request $request): JsonResponse
    {
        return $this->listUserEntriesTake->handle($userId, $request);
    }

    public function showEntry(string $userId, string $entryId): JsonResponse
    {
        return $this->saveUserEntryTake->show($userId, $entryId);
    }

    public function updateEntry(string $userId, string $entryId, SaveUserEntryRequest $request): JsonResponse
    {
        return $this->saveUserEntryTake->update($userId, $entryId, $request);
    }

    public function destroyEntry(string $userId, string $entryId): JsonResponse
    {
        return $this->saveUserEntryTake->destroy($userId, $entryId);
    }

    public function storePhrase(string $userId, SaveUserPhraseRequest $request): JsonResponse
    {
        return $this->saveUserPhraseTake->handle($userId, $request);
    }

    public function indexPhrases(string $userId, Request $request): JsonResponse
    {
        return $this->listUserPhrasesTake->handle($userId, $request);
    }

    public function showPhrase(string $userId, string $phraseId): JsonResponse
    {
        return $this->saveUserPhraseTake->show($userId, $phraseId);
    }

    public function updatePhrase(string $userId, string $phraseId, SaveUserPhraseRequest $request): JsonResponse
    {
        return $this->saveUserPhraseTake->update($userId, $phraseId, $request);
    }

    public function destroyPhrase(string $userId, string $phraseId): JsonResponse
    {
        return $this->saveUserPhraseTake->destroy($userId, $phraseId);
    }

    public function uploadMedia(string $userId, UploadMediaRequest $request): JsonResponse
    {
        return $this->uploadMediaTake->handle($userId, $request);
    }

    public function streamMedia(string $userId, string $mediaId): BinaryFileResponse|JsonResponse
    {
        return $this->streamMediaTake->handle($userId, $mediaId);
    }

    public function speak(string $userId, SpeakTextRequest $request): JsonResponse
    {
        return $this->speakTextTake->speak($userId, $request);
    }

    public function transcribe(SpeakTextRequest $request): JsonResponse
    {
        return $this->speakTextTake->transcribe($request);
    }

    public function grantShare(string $userId, ShareCategoryRequest $request): JsonResponse
    {
        return $this->shareCategoryTake->grant($userId, $request);
    }

    public function listShares(string $userId, Request $request): JsonResponse
    {
        return $this->shareCategoryTake->index($userId, $request);
    }

    public function revokeShare(string $userId, string $shareId): JsonResponse
    {
        return $this->shareCategoryTake->destroy($userId, $shareId);
    }

    public function sharedWithMe(string $userId, Request $request): JsonResponse
    {
        return $this->shareCategoryTake->shared($userId, $request);
    }

    public function sharedEntries(string $userId, Request $request): JsonResponse
    {
        return $this->readSharedTake->entries($userId, $request);
    }

    public function sharedEntry(string $userId, string $entryId, Request $request): JsonResponse
    {
        return $this->readSharedTake->entry($userId, $entryId, $request);
    }

    public function sharedPhrases(string $userId, Request $request): JsonResponse
    {
        return $this->readSharedTake->phrases($userId, $request);
    }

    public function sharedPhrase(string $userId, string $phraseId, Request $request): JsonResponse
    {
        return $this->readSharedTake->phrase($userId, $phraseId, $request);
    }
}
