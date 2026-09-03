<?php

namespace App\Modules\Catalog\Infrastructure\Console\Commands;

use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Category;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Entry;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\EntryMeaning;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\EntryTranslation;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Language;
use avadim\FastExcelReader\Excel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Ramsey\Uuid\Uuid;

class ImportWordsCommand extends Command
{
    protected $signature = 'catalog:import-words
        {--dir= : subdirectory under the words disk (default: all subdirectories)}
        {--file= : single file path relative to the words disk}
        {--part= : part of speech override (verb, noun, adjective, adverb, preposition, conjunction)}
        {--category= : category slug to attach entries to}
        {--level=A1 : level assigned to newly created entries}
        {--frequency : assign frequency_rank by row order (for frequency dictionaries)}
        {--limit= : max rows per file}
        {--dry-run : parse files without writing to database}';

    protected $description = 'Import dictionary words (xlsx/csv) into the catalog';

    private const PART_BY_FOLDER = [
        'Глаголы' => 'verb',
        'Существительные' => 'noun',
        'Прилагательные' => 'adjective',
        'Наречия' => 'adverb',
        'Предлоги' => 'preposition',
        'Союзы' => 'conjunction',
    ];

    private const CATEGORY_BY_FOLDER = [
        'Глаголы' => 'verbs',
        'Существительные' => 'nouns',
        'Прилагательные' => 'adjectives',
        'Наречия' => 'adverbs',
        'Предлоги' => 'preposition',
        'Союзы' => 'conjunction',
    ];

    private int $entriesCreated = 0;

    private int $meaningsCreated = 0;

    private int $translationsCreated = 0;

    private int $skipped = 0;

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $files = $this->resolveFiles($disk);

        if ($files === []) {
            $this->warn('No dictionary files found.');

            return self::SUCCESS;
        }

        $en = Language::query()->where('code', 'en')->first();
        $ru = Language::query()->where('code', 'ru')->first();

        if (! $en || ! $ru) {
            $this->error('Languages en/ru must exist. Run language seeder first.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        foreach ($files as $file) {
            $this->importFile($disk, $file, $en->id, $ru->id, $dryRun);
        }

        $this->info(sprintf(
            'Done. entries: %d, meanings: %d, translations: %d, skipped: %d%s',
            $this->entriesCreated,
            $this->meaningsCreated,
            $this->translationsCreated,
            $this->skipped,
            $dryRun ? ' (dry run)' : '',
        ));

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function resolveFiles($disk): array
    {
        if ($this->option('file')) {
            return [$this->option('file')];
        }

        $dir = trim((string) ($this->option('dir') ?? ''), '/');
        $base = $dir === '' ? 'words' : 'words/'.$dir;

        $files = [];

        foreach ($disk->allFiles($base) as $path) {
            if (preg_match('/\.(xlsx|csv)$/iu', $path)) {
                $files[] = $path;
            }
        }

        sort($files);

        return $files;
    }

    private function importFile($disk, string $file, string $enId, string $ruId, bool $dryRun): void
    {
        $rows = $this->readRows($disk, $file);
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;

        if ($limit !== null) {
            $rows = array_slice($rows, 0, $limit);
        }

        $folder = $this->folderOf($file);
        $part = $this->option('part') ?: (self::PART_BY_FOLDER[$folder] ?? null);
        $categorySlug = $this->option('category') ?: (self::CATEGORY_BY_FOLDER[$folder] ?? null);
        $categoryId = $categorySlug ? Category::query()->where('slug', $categorySlug)->value('id') : null;
        $withFrequency = (bool) $this->option('frequency');

        $created = 0;
        $rank = 0;

        foreach ($rows as $row) {
            $rank++;

            $enText = $this->clean($row['en'] ?? '');
            $ruText = $this->clean($row['ru'] ?? '');

            if ($enText === '' || $ruText === '') {
                $this->skipped++;

                continue;
            }

            $transcription = $this->cleanTranscription($row['transcription'] ?? '');
            $rowPart = $part ?? ($this->clean($row['part'] ?? '') ?: null);

            if ($dryRun) {
                $created++;

                continue;
            }

            $this->importRow($enId, $ruId, $enText, $ruText, $transcription, $rowPart, $categoryId, $withFrequency ? $rank : null);
            $created++;
        }

        $this->line(sprintf('%s: %d rows', $file, $created));
    }

    private function importRow(
        string $enId,
        string $ruId,
        string $enText,
        string $ruText,
        ?string $transcription,
        ?string $part,
        ?string $categoryId,
        ?int $rank,
    ): void {
        DB::transaction(function () use ($enId, $ruId, $enText, $ruText, $transcription, $part, $categoryId, $rank) {
            $key = mb_strtolower($enText);

            $existing = EntryTranslation::query()
                ->where('language_id', $enId)
                ->where('text', $key)
                ->first();

            if ($existing) {
                $entryId = (string) $existing->entry_id;
                $entry = Entry::query()->find($entryId);

                if ($rank !== null && $entry && $entry->frequency_rank === null) {
                    $entry->frequency_rank = $rank;
                    $entry->save();
                }
            } else {
                $entry = Entry::query()->create([
                    'id' => Uuid::uuid4()->toString(),
                    'level' => $this->option('level') ?? 'A1',
                    'frequency_rank' => $rank,
                ]);
                $entryId = (string) $entry->id;
                $this->entriesCreated++;
            }

            $meaning = EntryMeaning::query()
                ->where('entry_id', $entryId)
                ->where('note', mb_substr($ruText, 0, 255))
                ->first();

            if (! $meaning) {
                $meaning = EntryMeaning::query()->create([
                    'id' => Uuid::uuid4()->toString(),
                    'entry_id' => $entryId,
                    'note' => mb_substr($ruText, 0, 255),
                ]);
                $this->meaningsCreated++;
            }

            foreach ([
                [$enId, $key, $transcription],
                [$ruId, $ruText, null],
            ] as [$languageId, $text, $tr]) {
                $exists = EntryTranslation::query()
                    ->where('meaning_id', $meaning->id)
                    ->where('language_id', $languageId)
                    ->where('text', $text)
                    ->exists();

                if ($exists) {
                    $this->skipped++;

                    continue;
                }

                EntryTranslation::query()->create([
                    'id' => Uuid::uuid4()->toString(),
                    'entry_id' => $entryId,
                    'meaning_id' => $meaning->id,
                    'language_id' => $languageId,
                    'text' => $text,
                    'transcription' => $tr,
                    'part_of_speech' => $part,
                ]);
                $this->translationsCreated++;
            }

            if ($categoryId && ! DB::table('entry_category')->where('entry_id', $entryId)->where('category_id', $categoryId)->exists()) {
                DB::table('entry_category')->insert([
                    'entry_id' => $entryId,
                    'category_id' => $categoryId,
                ]);
            }
        });
    }

    /**
     * @return list<array{en: string, ru: string, transcription: ?string, part: ?string}>
     */
    private function readRows($disk, string $file): array
    {
        if (str_ends_with(mb_strtolower($file), '.csv')) {
            return $this->readCsv($disk->path($file));
        }

        return $this->readXlsx($disk->path($file));
    }

    /**
     * @return list<array{en: string, ru: string, transcription: ?string, part: ?string}>
     */
    private function readXlsx(string $path): array
    {
        $cells = Excel::open($path)->getSheet(0)->readCells();

        $rows = [];

        foreach (array_keys($cells) as $cell) {
            if (preg_match('/\d+$/', $cell, $m)) {
                $rows[(int) $m[0]] = true;
            }
        }

        ksort($rows);

        $result = [];

        foreach (array_keys($rows) as $row) {
            $result[] = [
                'en' => (string) ($cells["A{$row}"] ?? ''),
                'transcription' => (string) ($cells["B{$row}"] ?? ''),
                'ru' => (string) ($cells["C{$row}"] ?? ''),
                'part' => null,
            ];
        }

        return $result;
    }

    /**
     * @return list<array{en: string, ru: string, transcription: ?string, part: ?string}>
     */
    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');

        if (! $handle) {
            return [];
        }

        $bom = fread($handle, 3);

        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $headers = fgetcsv($handle, 0, ';');

        if (! $headers) {
            fclose($handle);

            return [];
        }

        $headers = array_map(fn ($h) => mb_strtolower(trim((string) $h)), $headers);
        $result = [];

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if (count($row) !== count($headers)) {
                continue;
            }

            $data = array_combine($headers, $row);

            $result[] = [
                'en' => (string) ($data['name'] ?? ''),
                'transcription' => (string) ($data['transcription'] ?? ''),
                'ru' => (string) ($data['translation'] ?? ''),
                'part' => isset($data['part']) ? (string) $data['part'] : null,
            ];
        }

        fclose($handle);

        return $result;
    }

    private function folderOf(string $file): ?string
    {
        $parts = explode('/', $file);

        return count($parts) >= 3 ? $parts[count($parts) - 2] : null;
    }

    private function clean(string $value): string
    {
        $value = str_replace(["\r", "\n", "\t", "\xc2\xa0"], ' ', $value);
        $value = (string) preg_replace('/\s+/u', ' ', $value);

        return trim($value);
    }

    private function cleanTranscription(string $value): ?string
    {
        $value = $this->clean($value);

        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, '[') && str_ends_with($value, ']')) {
            $value = trim(mb_substr($value, 1, -1));
        }

        return $value === '' ? null : $value;
    }
}
