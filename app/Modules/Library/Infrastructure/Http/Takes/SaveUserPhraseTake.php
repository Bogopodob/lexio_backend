<?php

namespace App\Modules\Library\Infrastructure\Http\Takes;

use App\Modules\Library\Application\UseCases\SaveUserPhrase\SaveUserPhraseCommand;
use App\Modules\Library\Application\UseCases\SaveUserPhrase\SaveUserPhraseUseCase;
use App\Modules\Library\Domain\Entities\UserPhraseTranslation;
use App\Modules\Library\Infrastructure\Http\Requests\SaveUserPhraseRequest;
use App\Modules\Library\Infrastructure\Http\Resources\LibraryResponseResource;
use App\Modules\Library\Infrastructure\Http\Resources\UserPhraseResource;
use Illuminate\Http\JsonResponse;

final readonly class SaveUserPhraseTake
{
    public function __construct(
        private SaveUserPhraseUseCase $useCase,
    ) {}

    public function handle(string $userId, SaveUserPhraseRequest $request): JsonResponse
    {
        $result = $this->useCase->handle($this->command($userId, $request));

        if ($result === null) {
            return response()->json(['success' => false, 'message' => __('api.entry.not_found')], 404);
        }

        $data = UserPhraseResource::make($result)->resolve(request());

        return LibraryResponseResource::make($data)->response()->setStatusCode(201);
    }

    public function show(string $userId, string $phraseId): JsonResponse
    {
        $result = $this->useCase->show($userId, $phraseId);

        if ($result === null) {
            return response()->json(['success' => false, 'message' => __('api.entry.not_found')], 404);
        }

        return LibraryResponseResource::make(
            UserPhraseResource::make($result)->resolve(request())
        )->response();
    }

    public function update(string $userId, string $phraseId, SaveUserPhraseRequest $request): JsonResponse
    {
        $result = $this->useCase->handle($this->command($userId, $request, $phraseId));

        if ($result === null) {
            return response()->json(['success' => false, 'message' => __('api.entry.not_found')], 404);
        }

        return LibraryResponseResource::make(
            UserPhraseResource::make($result)->resolve(request())
        )->response();
    }

    public function destroy(string $userId, string $phraseId): JsonResponse
    {
        $deleted = $this->useCase->destroy($userId, $phraseId);

        if (! $deleted) {
            return response()->json(['success' => false, 'message' => __('api.entry.not_found')], 404);
        }

        return LibraryResponseResource::make(['deleted' => true])->response();
    }

    private function command(string $userId, SaveUserPhraseRequest $request, ?string $phraseId = null): SaveUserPhraseCommand
    {
        $translations = array_map(fn (array $t) => new UserPhraseTranslation(
            id: '',
            languageId: $t['language_id'],
            text: $t['text'],
            transcription: $t['transcription'] ?? null,
            notes: $t['notes'] ?? null,
            audioPath: $t['audio_path'] ?? null,
        ), $request->validated('translations'));

        return new SaveUserPhraseCommand(
            userId: $userId,
            categoryId: $request->validated('category_id'),
            phraseType: $request->validated('phrase_type') ?? 'phrase',
            translations: $translations,
            imagePath: $request->validated('image_path'),
            phraseId: $phraseId,
        );
    }
}
