<?php

namespace App\Modules\Catalog\Infrastructure\Http\Takes;

use App\Modules\Catalog\Application\UseCases\QuizRound\QuizRoundCommand;
use App\Modules\Catalog\Application\UseCases\QuizRound\QuizRoundUseCase;
use App\Modules\Catalog\Domain\Ports\CatalogRepositoryInterface;
use App\Modules\Catalog\Infrastructure\Http\Resources\CatalogResponseResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class QuizRoundTake
{
    public function __construct(
        private QuizRoundUseCase $useCase,
        private CatalogRepositoryInterface $catalog,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $enId = null;
        $ruId = null;

        foreach ($this->catalog->listLanguages(false) as $lang) {
            if ($lang->code === 'en') {
                $enId = $lang->id;
            } elseif ($lang->code === 'ru') {
                $ruId = $lang->id;
            }
        }

        if (! $enId || ! $ruId) {
            return response()->json(['success' => false, 'message' => __('api.words.empty')], 404);
        }

        $round = $this->useCase->handle(new QuizRoundCommand(
            enLanguageId: $enId,
            ruLanguageId: $ruId,
            count: max(2, min(6, (int) $request->query('count', 4))),
        ));

        if (! $round) {
            return response()->json(['success' => false, 'message' => __('api.words.empty')], 404);
        }

        return CatalogResponseResource::make([
            'question' => [
                'word' => $round->word,
                'transcription' => $round->transcription,
            ],
            'options' => $round->options,
            'correct_index' => $round->correctIndex,
        ])->response();
    }
}
