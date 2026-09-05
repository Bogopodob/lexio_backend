<?php

namespace App\Modules\Catalog\Application\UseCases\QuizRound;

use App\Modules\Catalog\Domain\Entities\QuizRound;
use App\Modules\Catalog\Domain\Ports\CatalogRepositoryInterface;

final readonly class QuizRoundUseCase
{
    public function __construct(
        private CatalogRepositoryInterface $catalog,
    ) {}

    public function handle(QuizRoundCommand $command): ?QuizRound
    {
        $count = max(2, min(6, $command->count));

        $ids = $this->catalog->randomEntryIds($command->enLanguageId, $command->ruLanguageId, $count);

        if (count($ids) < 2) {
            return null;
        }

        $question = null;
        $correct = null;
        $distractors = [];

        foreach ($ids as $id) {
            $details = $this->catalog->getEntryDetails($id);

            if (! $details) {
                continue;
            }

            $en = null;
            $ru = null;

            foreach ($details->translations as $t) {
                if ($en === null && $t->languageId === $command->enLanguageId && trim($t->text) !== '') {
                    $en = $t;
                }

                if ($ru === null && $t->languageId === $command->ruLanguageId && trim($t->text) !== '') {
                    $ru = $t;
                }
            }

            if (! $en || ! $ru) {
                continue;
            }

            if ($question === null) {
                $question = $en;
                $correct = $ru->text;

                continue;
            }

            if (mb_strtolower(trim($ru->text)) !== mb_strtolower(trim($correct ?? ''))) {
                $distractors[] = $ru->text;
            }
        }

        if (! $question || $correct === null || $distractors === []) {
            return null;
        }

        $options = array_values(array_unique([$correct, ...array_slice($distractors, 0, $count - 1)]));
        shuffle($options);

        $index = array_search($correct, $options, true);

        if ($index === false || count($options) < 2) {
            return null;
        }

        return new QuizRound(
            word: $question->text,
            transcription: $question->transcription,
            options: $options,
            correctIndex: $index,
        );
    }
}
