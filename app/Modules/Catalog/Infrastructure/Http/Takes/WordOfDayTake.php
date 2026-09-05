<?php

namespace App\Modules\Catalog\Infrastructure\Http\Takes;

use App\Modules\Catalog\Application\UseCases\WordOfDay\WordOfDayCommand;
use App\Modules\Catalog\Application\UseCases\WordOfDay\WordOfDayUseCase;
use App\Modules\Catalog\Domain\Ports\CatalogRepositoryInterface;
use App\Modules\Catalog\Infrastructure\Http\Resources\CatalogResponseResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class WordOfDayTake
{
    public function __construct(
        private WordOfDayUseCase $useCase,
        private CatalogRepositoryInterface $catalog,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $date = (string) $request->query('date', date('Y-m-d'));

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return response()->json(['success' => false, 'message' => 'Invalid date'], 422);
        }

        $details = $this->useCase->handle(new WordOfDayCommand($date));

        if (! $details) {
            return response()->json(['success' => false, 'message' => 'No words yet'], 404);
        }

        $enId = null;
        $ruId = null;

        foreach ($this->catalog->listLanguages(false) as $lang) {
            if ($lang->code === 'en') {
                $enId = $lang->id;
            } elseif ($lang->code === 'ru') {
                $ruId = $lang->id;
            }
        }

        $word = null;
        $translation = null;

        foreach ($details->translations as $t) {
            if ($word === null && $t->languageId === $enId) {
                $word = $t;
            }

            if ($translation === null && $t->languageId === $ruId) {
                $translation = $t;
            }
        }

        if (! $word || ! $translation) {
            return response()->json(['success' => false, 'message' => 'No words yet'], 404);
        }

        $enIds = [];

        foreach ($details->translations as $t) {
            if ($t->languageId === $enId) {
                $enIds[$t->id] = true;
            }
        }

        $forms = [];

        foreach ($details->forms as $f) {
            if (isset($enIds[$f->translationId]) && ! in_array($f->form, $forms, true)) {
                $forms[] = $f->form;
            }
        }

        $example = null;

        foreach ($details->examples as $e) {
            foreach ($e->translations as $t) {
                if ($t->languageId === $ruId && trim($t->text) !== '') {
                    $example = $t->text;
                    break 2;
                }
            }
        }

        return CatalogResponseResource::make([
            'date' => $date,
            'word' => $word->text,
            'transcription' => $word->transcription,
            'translation' => $translation->text,
            'part_of_speech' => $word->partOfSpeech,
            'level' => $details->entry->level,
            'forms' => array_slice($forms, 0, 4),
            'example' => $example,
        ])->response();
    }
}
