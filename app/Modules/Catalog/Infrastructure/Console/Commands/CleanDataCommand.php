<?php

namespace App\Modules\Catalog\Infrastructure\Console\Commands;

use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\EntryMeaning;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\EntryTranslation;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\WordForm;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanDataCommand extends Command
{
    protected $signature = 'catalog:clean-data
        {--fix : apply changes, otherwise print a dry-run report}
        {--rules= : comma-separated rules (typo-hardsign,subset-merge), default all}';

    protected $description = 'Audit and fix catalog data issues (typos, duplicate meanings)';

    private const RULE_TYPO = 'typo-hardsign';

    private const RULE_SUBSET = 'subset-merge';

    /**
     * @var list<array{rule: string, action: string, detail: string}>
     */
    private array $planned = [];

    private int $meaningsDeleted = 0;

    private int $translationsDeleted = 0;

    private int $translationsMoved = 0;

    private int $formsReanchored = 0;

    public function handle(): int
    {
        $rules = $this->selectedRules();
        $fix = (bool) $this->option('fix');

        if (! $fix) {
            $this->info('Dry run — nothing will be written. Pass --fix to apply.');
        }

        try {
            DB::transaction(function () use ($rules, $fix) {
                if (in_array(self::RULE_TYPO, $rules, true)) {
                    $this->ruleHardsignTypo();
                }

                if (in_array(self::RULE_SUBSET, $rules, true)) {
                    $this->ruleSubsetMerge();
                }

                if (! $fix) {
                    throw new DryRunRollback;
                }
            });
        } catch (DryRunRollback) {
            // Dry run: rolled back intentionally.
        }

        if ($this->planned === []) {
            $this->info('No issues found.');

            return self::SUCCESS;
        }

        $this->table(
            ['rule', 'action', 'detail'],
            array_map(fn ($p) => [$p['rule'], $p['action'], $p['detail']], $this->planned),
        );

        $this->info(sprintf(
            'Meanings deleted: %d, translations deleted: %d, moved: %d, forms re-anchored: %d%s',
            $this->meaningsDeleted,
            $this->translationsDeleted,
            $this->translationsMoved,
            $this->formsReanchored,
            $fix ? '' : ' (dry run)',
        ));

        if ($fix) {
            $this->warn('Applied. Restore from pg_dump backup if anything looks wrong.');
        }

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function selectedRules(): array
    {
        $raw = trim((string) ($this->option('rules') ?? ''));

        if ($raw === '') {
            return [self::RULE_TYPO, self::RULE_SUBSET];
        }

        $all = [self::RULE_TYPO, self::RULE_SUBSET];

        return array_values(array_intersect(
            array_map('trim', explode(',', $raw)),
            $all,
        ));
    }

    /**
     * Multibyte-safe trailing hard-sign strip (rtrim is byte-based).
     */
    private function stripHardsign(string $value): string
    {
        return (string) preg_replace('/ъ+$/u', '', trim($value));
    }

    /**
     * Russian words ending with ъ (modern orthography has none):
     * merge into the sibling meaning without it, or strip it.
     */
    private function ruleHardsignTypo(): void
    {
        $meanings = EntryMeaning::query()
            ->where('note', 'LIKE', '%ъ')
            ->get(['id', 'entry_id', 'note']);

        foreach ($meanings as $meaning) {
            $fixed = $this->stripHardsign($meaning->note);

            if ($fixed === '') {
                continue;
            }

            $sibling = EntryMeaning::query()
                ->where('entry_id', $meaning->entry_id)
                ->where('note', $fixed)
                ->first(['id']);

            if ($sibling) {
                $this->mergeMeanings($meaning->id, $sibling->id, self::RULE_TYPO, "«{$meaning->note}» → «{$fixed}»");
            } else {
                $this->plan(self::RULE_TYPO, 'rename', "«{$meaning->note}» → «{$fixed}»");
                $meaning->update(['note' => $fixed]);

                $texts = EntryTranslation::query()
                    ->where('meaning_id', $meaning->id)
                    ->where('text', 'LIKE', '%ъ')
                    ->get();

                foreach ($texts as $t) {
                    $newText = $this->stripHardsign($t->text);

                    if ($newText === '') {
                        continue;
                    }

                    $conflict = EntryTranslation::query()
                        ->where('meaning_id', $meaning->id)
                        ->where('language_id', $t->language_id)
                        ->where('id', '!=', $t->id)
                        ->whereRaw('LOWER(text) = ?', [mb_strtolower($newText)])
                        ->exists();

                    if ($conflict) {
                        $this->reanchorForms((string) $t->id);
                        $t->delete();
                        $this->translationsDeleted++;

                        continue;
                    }

                    $t->update(['text' => $newText]);
                }
            }
        }
    }

    /**
     * A meaning fully contained in another meaning's ;-list is a duplicate:
     * «мочь» ⊂ «мочь; уметь». Merge the smaller into the bigger.
     */
    private function ruleSubsetMerge(): void
    {
        $entryIds = EntryMeaning::query()->distinct()->pluck('entry_id');

        foreach ($entryIds as $entryId) {
            $meanings = EntryMeaning::query()
                ->where('entry_id', $entryId)
                ->get(['id', 'note']);

            if ($meanings->count() < 2) {
                continue;
            }

            $segments = [];

            foreach ($meanings as $m) {
                $segments[(string) $m->id] = array_map(
                    fn ($s) => mb_strtolower(trim($s)),
                    explode(';', (string) ($m->note ?? '')),
                );
            }

            foreach ($meanings as $small) {
                $smallKey = mb_strtolower(trim((string) ($small->note ?? '')));

                if ($smallKey === '') {
                    continue;
                }

                foreach ($meanings as $big) {
                    if ($big->id === $small->id) {
                        continue;
                    }

                    if (in_array($smallKey, $segments[(string) $big->id], true)) {
                        $this->mergeMeanings(
                            $small->id,
                            $big->id,
                            self::RULE_SUBSET,
                            "«{$small->note}» ⊂ «{$big->note}»",
                        );
                        break;
                    }
                }
            }
        }
    }

    /**
     * Move translations into the target meaning (skipping conflicts),
     * re-anchor word forms, delete the source meaning.
     */
    private function mergeMeanings(string $fromId, string $toId, string $rule, string $detail): void
    {
        $moving = EntryTranslation::query()->where('meaning_id', $fromId)->get();

        foreach ($moving as $t) {
            $conflict = EntryTranslation::query()
                ->where('meaning_id', $toId)
                ->where('language_id', $t->language_id)
                ->whereRaw('LOWER(text) = ?', [mb_strtolower($t->text)])
                ->exists();

            if ($conflict) {
                $this->reanchorForms((string) $t->id);
                $t->delete();
                $this->translationsDeleted++;

                continue;
            }

            $this->reanchorForms((string) $t->id, $toId);
            $t->update(['meaning_id' => $toId]);
            $this->translationsMoved++;
        }

        EntryMeaning::query()->where('id', $fromId)->delete();
        $this->meaningsDeleted++;
        $this->plan($rule, 'merge', $detail);
    }

    /**
     * Word forms cascade on translation delete — point them at a surviving
     * EN translation of the same entry first. When $keepMeaningId is given,
     * forms conservatively follow moved translations only within it.
     */
    private function reanchorForms(string $translationId, ?string $keepMeaningId = null): void
    {
        $forms = WordForm::query()->where('entry_translation_id', $translationId)->get();

        if ($forms->isEmpty()) {
            return;
        }

        $anchor = EntryTranslation::query()->where('id', $translationId)->first(['entry_id', 'language_id']);

        if (! $anchor) {
            return;
        }

        $target = EntryTranslation::query()
            ->where('entry_id', $anchor->entry_id)
            ->where('language_id', $anchor->language_id)
            ->where('id', '!=', $translationId)
            ->when($keepMeaningId !== null, fn ($q) => $q->where('meaning_id', $keepMeaningId))
            ->orderBy('created_at')
            ->value('id');

        if (! $target) {
            $target = EntryTranslation::query()
                ->where('entry_id', $anchor->entry_id)
                ->where('id', '!=', $translationId)
                ->orderBy('created_at')
                ->value('id');
        }

        if (! $target) {
            return;
        }

        WordForm::query()->where('entry_translation_id', $translationId)->update([
            'entry_translation_id' => $target,
        ]);
        $this->formsReanchored += $forms->count();
    }

    /**
     * @param  array{rule: string, action: string, detail: string}  $row
     */
    private function plan(string $rule, string $action, string $detail): void
    {
        $this->planned[] = ['rule' => $rule, 'action' => $action, 'detail' => $detail];
    }
}

final class DryRunRollback extends \RuntimeException {}
