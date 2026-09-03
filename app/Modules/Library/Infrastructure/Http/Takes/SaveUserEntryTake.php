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
        ), $request->validated('translations'));

        $result = $this->useCase->handle(
            new SaveUserEntryCommand(
                userId: $userId,
                categoryId: $request->validated('category_id'),
                imagePath: $request->validated('image_path'),
                translations: $translations,
            )
        );

        $data = UserEntryResource::make($result)->resolve(request());

        return LibraryResponseResource::make($data)->response()->setStatusCode(201);
    }
}
