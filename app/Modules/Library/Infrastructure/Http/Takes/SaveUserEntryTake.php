<?php

namespace App\Modules\Library\Infrastructure\Http\Takes;

use App\Modules\Library\Application\UseCases\SaveUserEntry\SaveUserEntryCommand;
use App\Modules\Library\Application\UseCases\SaveUserEntry\SaveUserEntryUseCase;
use App\Modules\Library\Domain\Entities\UserEntryTranslation;
use App\Modules\Library\Infrastructure\Http\Requests\SaveUserEntryRequest;
use App\Modules\Library\Infrastructure\Http\Resources\LibraryResponseResource;
use App\Modules\Library\Infrastructure\Http\Resources\UserEntryResource;
use Illuminate\Http\JsonResponse;

final readonly class SaveUserEntryTake
{
    public function __construct(
        private SaveUserEntryUseCase $useCase,
    ) {}

    public function handle(string $userId, SaveUserEntryRequest $request): JsonResponse
    {
        $translations = array_map(fn (array $t) => new UserEntryTranslation(
            id: '',
            languageId: $t['language_id'],
            text: $t['text'],
            transcription: $t['transcription'] ?? null,
            partOfSpeech: $t['part_of_speech'] ?? null,
            notes: $t['notes'] ?? null,
            audioPath: $t['audio_path'] ?? null,
        ), $request->validated('translations'));

        $result = $this->useCase->handle(
            new SaveUserEntryCommand(
                userId: $userId,
                categoryId: $request->validated('category_id'),
                imagePath: $request->validated('image_path'),
                translations: $translations,
            )
        );

        if ($result === null) {
            return response()->json(['success' => false, 'message' => __('api.entry.not_found')], 404);
        }

        $data = UserEntryResource::make($result)->resolve(request());

        return LibraryResponseResource::make($data)->response()->setStatusCode(201);
    }

    public function show(string $userId, string $entryId): JsonResponse
    {
        $result = $this->useCase->show($userId, $entryId);

        if ($result === null) {
            return response()->json(['success' => false, 'message' => __('api.entry.not_found')], 404);
        }

        return LibraryResponseResource::make(
            UserEntryResource::make($result)->resolve(request())
        )->response();
    }

    public function update(string $userId, string $entryId, SaveUserEntryRequest $request): JsonResponse
    {
        $translations = array_map(fn (array $t) => new UserEntryTranslation(
            id: '',
            languageId: $t['language_id'],
            text: $t['text'],
            transcription: $t['transcription'] ?? null,
            partOfSpeech: $t['part_of_speech'] ?? null,
            notes: $t['notes'] ?? null,
            audioPath: $t['audio_path'] ?? null,
        ), $request->validated('translations'));

        $result = $this->useCase->handle(
            new SaveUserEntryCommand(
                userId: $userId,
                categoryId: $request->validated('category_id'),
                imagePath: $request->validated('image_path'),
                translations: $translations,
                entryId: $entryId,
            )
        );

        if ($result === null) {
            return response()->json(['success' => false, 'message' => __('api.entry.not_found')], 404);
        }

        return LibraryResponseResource::make(
            UserEntryResource::make($result)->resolve(request())
        )->response();
    }

    public function destroy(string $userId, string $entryId): JsonResponse
    {
        $deleted = $this->useCase->destroy($userId, $entryId);

        if (! $deleted) {
            return response()->json(['success' => false, 'message' => __('api.entry.not_found')], 404);
        }

        return LibraryResponseResource::make(['deleted' => true])->response();
    }
}
