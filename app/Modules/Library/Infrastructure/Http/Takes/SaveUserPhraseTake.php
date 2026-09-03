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
        $translations = array_map(fn (array $t) => new UserPhraseTranslation(
            id: '',
            languageId: $t['language_id'],
            text: $t['text'],
            transcription: $t['transcription'] ?? null,
            notes: $t['notes'] ?? null,
        ), $request->validated('translations'));

        $result = $this->useCase->handle(
            new SaveUserPhraseCommand(
                userId: $userId,
                categoryId: $request->validated('category_id'),
                phraseType: $request->validated('phrase_type') ?? 'phrase',
                translations: $translations,
            )
        );

        $data = UserPhraseResource::make($result)->resolve(request());

        return LibraryResponseResource::make($data)->response()->setStatusCode(201);
    }
}
