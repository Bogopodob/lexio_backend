<?php

namespace App\Modules\Catalog\Infrastructure\Console\Commands;

use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Category;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Entry;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\EntryMeaning;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\EntryTranslation;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Language;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\WordForm;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Reader\Xls as XlsReader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Process\Process;

class ImportIrregularVerbsCommand extends Command
{
    protected $signature = 'catalog:import-irregular-verbs
        {--file=All_700_Irregular_Verbs.xls : path relative to the public disk}
        {--level=A1 : level assigned to newly created entries}
        {--limit= : max verbs to import}
        {--dry-run : parse the file without writing to database}
        {--no-transcribe : skip espeak-ng transcription generation}';

    protected $description = 'Import irregular verbs (base + past forms) into the catalog';

    /**
     * Bands in file order. Cumulative: each band contains all previous ones.
     *
     * @var list<array{sheet: string, slug: string, ru: string, en: string, level: string, sort: int}>
     */
    private const BANDS = [
        ['sheet' => '50=50%', 'slug' => 'irr-50', 'ru' => 'Ядро', 'en' => 'Core 50', 'level' => 'A1', 'sort' => 110],
        ['sheet' => '100=70%', 'slug' => 'irr-100', 'ru' => 'База', 'en' => 'Base 100', 'level' => 'A1', 'sort' => 120],
        ['sheet' => '150=90%', 'slug' => 'irr-150', 'ru' => 'Разговорный', 'en' => 'Speaking 150', 'level' => 'A2', 'sort' => 130],
        ['sheet' => '200=93%', 'slug' => 'irr-200', 'ru' => 'Уверенный', 'en' => 'Confident 200', 'level' => 'A2', 'sort' => 140],
        ['sheet' => '300=97%', 'slug' => 'irr-300', 'ru' => 'Продвинутый', 'en' => 'Advanced 300', 'level' => 'B1', 'sort' => 150],
        ['sheet' => '366=99%', 'slug' => 'irr-366', 'ru' => 'Почти все', 'en' => 'Almost All 366', 'level' => 'B1', 'sort' => 160],
        ['sheet' => '700=100%', 'slug' => 'irr-700', 'ru' => 'Все 700', 'en' => 'All 700', 'level' => 'B2', 'sort' => 170],
    ];

    private int $verbsSeen = 0;

    private int $entriesCreated = 0;

    private int $meaningsCreated = 0;

    private int $translationsCreated = 0;

    private int $formsCreated = 0;

    private int $transcribed = 0;

    private int $skipped = 0;

    /**
     * @var array<string, string>
     */
    private array $transcribeCache = [];

    private bool $espeakChecked = false;

    private bool $espeakAvailable = false;

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $file = (string) $this->option('file');

        if (! $disk->exists($file)) {
            $this->error("File not found on the public disk: {$file}");

            return self::FAILURE;
        }

        $en = Language::query()->where('code', 'en')->first();
        $ru = Language::query()->where('code', 'ru')->first();

        if (! $en || ! $ru) {
            $this->error('Languages en/ru must exist. Run language seeder first.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;

        $this->loadTranscribeCache($disk);

        $reader = new XlsReader;
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($disk->path($file));

        // Phase 1: parse everything (bands in file order, first appearance wins).
        $verbs = $this->parse($spreadsheet, $limit);

        if ($verbs === []) {
            $this->warn('No verbs parsed.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Parsed %d verbs.', count($verbs)));

        if ($dryRun) {
            $this->table(
                ['base', 'ru', 'v2', 'v3', 'bands', 'group'],
                array_map(fn ($v) => [
                    $v['base'],
                    mb_substr($v['ru'], 0, 40),
                    implode('/', array_column($v['past'], 'form')),
                    implode('/', array_column($v['participle'], 'form')),
                    implode(',', $v['bands']),
                    $v['group'] !== null ? "#{$v['group']}" : '-',
                ], array_slice($verbs, 0, 15)),
            );
            $this->info('Dry run — nothing written.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($verbs, $en, $ru) {
            $categoryIds = $this->ensureCategories($verbs);

            // New verbs go after existing ranks; file order = frequency order.
            $rank = (int) (Entry::query()->max('frequency_rank') ?? 0);

            foreach ($verbs as $verb) {
                $rank = $this->importVerb($verb, $categoryIds, (string) $en->id, (string) $ru->id, $rank);
            }
        });

        $this->saveTranscribeCache($disk);

        $this->info(sprintf(
            'Done. verbs: %d, entries: %d, meanings: %d, translations: %d, forms: %d, transcribed: %d, skipped: %d',
            $this->verbsSeen,
            $this->entriesCreated,
            $this->meaningsCreated,
            $this->translationsCreated,
            $this->formsCreated,
            $this->transcribed,
            $this->skipped,
        ));

        return self::SUCCESS;
    }

    /**
     * @return list<array{base: string, baseVariants: list<string>, ru: string, tr1: ?string, past: list<array{form: string, tr: ?string}>, participle: list<array{form: string, tr: ?string}>, pattern: ?string, group: ?int, bands: list<string>, level: string}>
     */
    private function parse(Spreadsheet $spreadsheet, ?int $limit): array
    {
        $verbs = [];
        $seen = [];

        foreach (self::BANDS as $band) {
            $sheet = $spreadsheet->getSheetByName($band['sheet']);

            if (! $sheet) {
                $this->warn("Sheet missing: {$band['sheet']}");

                continue;
            }

            $rows = $band['sheet'] === '700=100%'
                ? $this->parseRareSheet($sheet)
                : $this->parseBandSheet($sheet);

            foreach ($rows as $row) {
                $key = mb_strtolower($row['base']);

                if (isset($seen[$key])) {
                    if (! in_array($band['slug'], $verbs[$seen[$key]]['bands'], true)) {
                        $verbs[$seen[$key]]['bands'][] = $band['slug'];
                    }

                    continue;
                }

                $seen[$key] = count($verbs);
                $row['bands'] = [$band['slug']];
                $row['level'] = $band['level'];
                $verbs[] = $row;

                if ($limit !== null && count($verbs) >= $limit) {
                    return $verbs;
                }
            }
        }

        return $verbs;
    }

    /**
     * Band sheets: № | Перевод | v1 | tr | v2 | tr | v3 | tr | Группа | Созвучие | Частота.
     *
     * @return list<array>
     */
    private function parseBandSheet(Worksheet $sheet): array
    {
        $rows = [];
        $group = null;

        foreach ($sheet->toArray(null, true, true, false) as $cells) {
            $cells = array_map(fn ($v) => $this->clean((string) ($v ?? '')), array_values($cells ?? []));
            $first = $cells[0] ?? '';

            if (preg_match('/^Группа №(\d+)/u', $first, $m)) {
                $group = (int) $m[1];

                continue;
            }

            if ($first === '' || ! ctype_digit($first)) {
                continue;
            }

            $rows[] = [
                'base' => $this->firstVariant($cells[2] ?? ''),
                'baseVariants' => $this->splitVariants($cells[2] ?? ''),
                'ru' => $cells[1] ?? '',
                'tr1' => $this->cleanTranscription($cells[3] ?? ''),
                'past' => $this->splitForms($cells[4] ?? '', $cells[5] ?? ''),
                'participle' => $this->splitForms($cells[6] ?? '', $cells[7] ?? ''),
                'pattern' => $this->clean($cells[9] ?? '') !== '' ? $this->clean($cells[9]) : null,
                'group' => $group,
            ];
        }

        return $rows;
    }

    /**
     * Rare sheet: ПН | Перевод | v1 | v2 | v3 | рифма. No transcriptions.
     *
     * @return list<array>
     */
    private function parseRareSheet(Worksheet $sheet): array
    {
        $rows = [];

        foreach ($sheet->toArray(null, true, true, false) as $cells) {
            $cells = array_map(fn ($v) => $this->clean((string) ($v ?? '')), array_values($cells ?? []));
            $first = $cells[0] ?? '';

            if ($first === '' || ! ctype_digit($first)) {
                continue;
            }

            $forms = array_map('trim', explode('|', $cells[2] ?? ''));

            $rows[] = [
                'base' => $this->firstVariant($forms[0] ?? ''),
                'baseVariants' => $this->splitVariants($forms[0] ?? ''),
                'ru' => $cells[1] ?? '',
                'tr1' => null,
                'past' => $this->splitForms($forms[1] ?? '', null),
                'participle' => $this->splitForms($forms[2] ?? '', null),
                'pattern' => null,
                'group' => null,
            ];
        }

        return $rows;
    }

    /**
     * @return list<string>
     */
    private function splitVariants(string $cell): array
    {
        $out = [];

        foreach (explode('/', $cell) as $part) {
            $part = $this->clean($part);

            if ($part !== '' && ! in_array($part, $out, true)) {
                $out[] = $part;
            }
        }

        return $out;
    }

    private function firstVariant(string $cell): string
    {
        return $this->splitVariants($cell)[0] ?? '';
    }

    /**
     * @return list<array{form: string, tr: ?string}>
     */
    private function splitForms(string $cell, ?string $trCell): array
    {
        $tr = $this->cleanTranscription($trCell ?? '');
        $out = [];

        foreach ($this->splitVariants($cell) as $i => $form) {
            // One transcription cell per form column: keep it on the first variant.
            $out[] = ['form' => $form, 'tr' => $i === 0 ? $tr : null];
        }

        return $out;
    }

    private function clean(string $value): string
    {
        return trim(str_replace("\u{00A0}", '', $value));
    }

    private function cleanTranscription(?string $value): ?string
    {
        $value = $this->clean((string) ($value ?? ''));

        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, '[') && str_ends_with($value, ']')) {
            $value = trim(mb_substr($value, 1, mb_strlen($value) - 2));
        }

        return $value === '' ? null : $value;
    }

    /**
     * @param  list<array>  $verbs
     * @return array{parent: string, bands: array<string, string>, groups: array<int, string>}
     */
    private function ensureCategories(array $verbs): array
    {
        $parentId = $this->ensureCategory(
            'irregular-verbs', null, 'verbs',
            'Неправильные глаголы', 'Irregular Verbs',
            '#E8E4F8', '📚', 100,
        );

        $bands = [];

        foreach (self::BANDS as $band) {
            $bands[$band['slug']] = $this->ensureCategory(
                $band['slug'], $parentId, 'verbs',
                $band['ru'], $band['en'],
                '#E8E4F8', '📚', $band['sort'],
            );
        }

        // Rhyme groups in order of appearance; name = conjugation formula.
        $groupFormulas = [];

        foreach ($verbs as $verb) {
            if ($verb['group'] === null) {
                continue;
            }

            if ($verb['pattern'] !== null && ! isset($groupFormulas[$verb['group']])) {
                $groupFormulas[$verb['group']] = $verb['pattern'];
            }
        }

        $groups = [];
        $sort = 200;

        foreach ($groupFormulas as $number => $formula) {
            $groups[$number] = $this->ensureCategory(
                "irr-group-{$number}", $parentId, 'verbs',
                $formula, $formula,
                '#F1F1F1', '🎵', $sort++,
            );
        }

        return ['parent' => $parentId, 'bands' => $bands, 'groups' => $groups];
    }

    private function ensureCategory(
        string $slug,
        ?string $parentId,
        string $type,
        string $ru,
        string $en,
        ?string $color,
        ?string $icon,
        int $sort,
    ): string {
        $existing = Category::query()
            ->where('slug', $slug)
            ->whereNull('user_id')
            ->value('id');

        if ($existing) {
            return (string) $existing;
        }

        $id = Uuid::uuid4()->toString();

        Category::query()->insert([
            'id' => $id,
            'parent_id' => $parentId,
            'user_id' => null,
            'slug' => $slug,
            'is_system' => true,
            'type' => $type,
            'color' => $color,
            'icon' => $icon,
            'sort' => $sort,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (['ru' => $ru, 'en' => $en] as $locale => $value) {
            DB::table('translations')->insert([
                'id' => Uuid::uuid4()->toString(),
                'entity_type' => Category::class,
                'entity_id' => $id,
                'locale' => $locale,
                'field' => 'name',
                'value' => $value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $id;
    }

    /**
     * @param  array{parent: string, bands: array<string, string>, groups: array<int, string>}  $categoryIds
     */
    private function importVerb(array $verb, array $categoryIds, string $enId, string $ruId, int $rank): int
    {
        if ($verb['base'] === '' || $verb['ru'] === '') {
            $this->skipped++;

            return $rank;
        }

        $this->verbsSeen++;

        $entryId = $this->findEntryId($enId, mb_strtolower($verb['base']));

        if (! $entryId) {
            $entryId = Uuid::uuid4()->toString();

            Entry::query()->create([
                'id' => $entryId,
                'level' => $verb['level'],
                'frequency_rank' => $rank++,
            ]);
            $this->entriesCreated++;
        }

        $entry = Entry::query()->find($entryId);

        if ($entry && $entry->frequency_rank === null) {
            $entry->update(['frequency_rank' => $rank++]);
        }

        if ($entry && $entry->forms_pattern === null && $verb['pattern'] !== null) {
            $entry->update(['forms_pattern' => mb_substr($verb['pattern'], 0, 60)]);
        }

        $meaningId = $this->findMeaningId($entryId, mb_strtolower($verb['ru']));

        if (! $meaningId) {
            $meaningId = Uuid::uuid4()->toString();

            EntryMeaning::query()->create([
                'id' => $meaningId,
                'entry_id' => $entryId,
                'note' => mb_substr($verb['ru'], 0, 255),
            ]);
            $this->meaningsCreated++;
        }

        // Base variants share one meaning; the transcription sits on the first.
        foreach ($verb['baseVariants'] as $i => $variant) {
            $this->ensureTranslation(
                $entryId, $meaningId, $enId, $variant,
                $i === 0 ? $this->transcribe($variant, $verb['tr1']) : null,
                true,
            );
        }

        $this->ensureTranslation($entryId, $meaningId, $ruId, $verb['ru'], null, false);

        $this->importForms($entryId, $enId, $verb['past'], 'past');
        $this->importForms($entryId, $enId, $verb['participle'], 'past_participle');

        foreach ($verb['bands'] as $slug) {
            $this->attachCategory($entryId, $categoryIds['bands'][$slug]);
        }

        if ($verb['group'] !== null && isset($categoryIds['groups'][$verb['group']])) {
            $this->attachCategory($entryId, $categoryIds['groups'][$verb['group']]);
        }

        return $rank;
    }

    private function findEntryId(string $enId, string $lowerEn): ?string
    {
        $id = EntryTranslation::query()
            ->where('language_id', $enId)
            ->whereRaw('LOWER(text) = ?', [$lowerEn])
            ->value('entry_id');

        return $id ? (string) $id : null;
    }

    private function findMeaningId(string $entryId, string $lowerRu): ?string
    {
        $id = EntryMeaning::query()
            ->where('entry_id', $entryId)
            ->whereRaw('LOWER(note) = ?', [$lowerRu])
            ->value('id');

        return $id ? (string) $id : null;
    }

    private function ensureTranslation(
        string $entryId,
        string $meaningId,
        string $languageId,
        string $text,
        ?string $transcription,
        bool $lowercase,
    ): void {
        $exists = EntryTranslation::query()
            ->where('meaning_id', $meaningId)
            ->where('language_id', $languageId)
            ->whereRaw('LOWER(text) = ?', [mb_strtolower($text)])
            ->exists();

        if ($exists) {
            return;
        }

        EntryTranslation::query()->create([
            'id' => Uuid::uuid4()->toString(),
            'entry_id' => $entryId,
            'meaning_id' => $meaningId,
            'language_id' => $languageId,
            'text' => $lowercase ? mb_strtolower($text) : $text,
            'transcription' => $transcription,
            'part_of_speech' => 'verb',
        ]);
        $this->translationsCreated++;
    }

    /**
     * @param  list<array{form: string, tr: ?string}>  $forms
     */
    private function importForms(string $entryId, string $enId, array $forms, string $type): void
    {
        if ($forms === []) {
            return;
        }

        $anchorId = EntryTranslation::query()
            ->where('entry_id', $entryId)
            ->where('language_id', $enId)
            ->orderBy('created_at')
            ->value('id');

        if (! $anchorId) {
            return;
        }

        foreach ($forms as $item) {
            $exists = WordForm::query()
                ->where('entry_translation_id', $anchorId)
                ->where('form', $item['form'])
                ->where('form_type', $type)
                ->exists();

            if ($exists) {
                continue;
            }

            WordForm::query()->create([
                'id' => Uuid::uuid4()->toString(),
                'entry_translation_id' => $anchorId,
                'form' => $item['form'],
                'transcription' => $this->transcribe($item['form'], $item['tr']),
                'form_type' => $type,
            ]);
            $this->formsCreated++;
        }
    }

    private function attachCategory(string $entryId, string $categoryId): void
    {
        $exists = DB::table('entry_category')
            ->where('entry_id', $entryId)
            ->where('category_id', $categoryId)
            ->exists();

        if (! $exists) {
            DB::table('entry_category')->insert([
                'entry_id' => $entryId,
                'category_id' => $categoryId,
            ]);
        }
    }

    private function transcribe(string $word, ?string $known): ?string
    {
        if ($known !== null && $known !== '') {
            return $known;
        }

        $key = mb_strtolower(trim($word));

        if (isset($this->transcribeCache[$key])) {
            return $this->transcribeCache[$key] !== '' ? $this->transcribeCache[$key] : null;
        }

        if ($this->option('no-transcribe')) {
            return null;
        }

        if (! $this->espeakChecked) {
            $this->espeakChecked = true;
            $this->espeakAvailable = trim((string) shell_exec('command -v espeak-ng')) !== '';
            if (! $this->espeakAvailable) {
                $this->warn('espeak-ng not found — missing transcriptions stay empty.');
            }
        }

        if (! $this->espeakAvailable) {
            return null;
        }

        $process = new Process(['espeak-ng', '--ipa', '-v', 'en', $word]);
        $process->setTimeout(10);
        $process->run();

        $ipa = trim((string) $process->getOutput());

        if (! $process->isSuccessful() || $ipa === '') {
            $this->transcribeCache[$key] = '';

            return null;
        }

        // First pronunciation only; espeak may list alternatives.
        $ipa = trim(explode("\n", $ipa)[0]);
        $this->transcribeCache[$key] = $ipa;
        $this->transcribed++;

        return $ipa;
    }

    private function loadTranscribeCache($disk): void
    {
        if (! $disk->exists('words/irregular-transcriptions.csv')) {
            return;
        }

        foreach (explode("\n", (string) $disk->get('words/irregular-transcriptions.csv')) as $line) {
            $parts = str_getcsv(trim($line), ';');

            if (count($parts) === 2 && $parts[0] !== '') {
                $this->transcribeCache[$parts[0]] = $parts[1];
            }
        }
    }

    private function saveTranscribeCache($disk): void
    {
        if ($this->transcribeCache === [] || $this->option('dry-run')) {
            return;
        }

        $lines = [];

        foreach ($this->transcribeCache as $word => $ipa) {
            $lines[] = $word.';'.$ipa;
        }

        sort($lines);
        $disk->put('words/irregular-transcriptions.csv', implode("\n", $lines)."\n");
    }
}
