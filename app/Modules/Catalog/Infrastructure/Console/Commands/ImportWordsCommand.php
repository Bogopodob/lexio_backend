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
        {--map-file= : json file (relative to the words disk) mapping source file names to category slugs}
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

    /**
     * @var array{fragment: string, part: string, category: string}[]
     */
    private const FILENAME_HINTS = [
        ['fragment' => 'глагол', 'part' => 'verb', 'category' => 'verbs'],
        ['fragment' => 'существител', 'part' => 'noun', 'category' => 'nouns'],
        ['fragment' => 'прилагател', 'part' => 'adjective', 'category' => 'adjectives'],
        ['fragment' => 'нареч', 'part' => 'adverb', 'category' => 'adverbs'],
        ['fragment' => 'предлог', 'part' => 'preposition', 'category' => 'preposition'],
        ['fragment' => 'союз', 'part' => 'conjunction', 'category' => 'conjunction'],
    ];

    private int $entriesCreated = 0;

    private int $meaningsCreated = 0;

    private int $translationsCreated = 0;

    private int $skipped = 0;

    /**
     * @var array<string, ?string>
     */
    private array $categoryCache = [];

    /**
     * @var array<string, bool>
     */
    private array $unmatchedSources = [];

    /**
     * @var array<string, string>
     */
    private ?array $sourceMap = null;

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

        if ($this->unmatchedSources !== []) {
            $this->warn('No category matched for sources:');

            foreach (array_keys($this->unmatchedSources) as $source) {
                $this->line(' - '.($source === '' ? '(empty)' : $source));
            }
        }

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
            if (! preg_match('/\.(xlsx|csv)$/iu', $path)) {
                continue;
            }

            // The irregular-verbs transcription cache is not a dictionary.
            if (str_starts_with(basename($path), 'irregular-transcriptions')) {
                continue;
            }

            $files[] = $path;
        }

        sort($files);

        return $files;
    }

    private function importFile($disk, string $file, string $enId, string $ruId, bool $dryRun): void
    {
        $this->info(sprintf('Processing: %s (parsing...)', $file));

        $rows = $this->readRows($disk, $file);
        $total = count($rows);

        $this->line(sprintf('  parsed %d rows', $total));

        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;

        if ($limit !== null) {
            $rows = array_slice($rows, 0, $limit);
        }

        $folder = $this->folderOf($file);
        $withFrequency = (bool) $this->option('frequency');

        $created = 0;
        $rank = 0;

        foreach ($rows as $i => $row) {
            $rank++;

            if (($i + 1) % 1000 === 0) {
                $this->line(sprintf('  %s: %d/%d rows', $file, $i + 1, $total));
            }

            $enText = $this->clean($row['en'] ?? '');
            $ruText = $this->clean($row['ru'] ?? '');

            if ($enText === '' || $ruText === '') {
                $this->skipped++;

                continue;
            }

            $transcription = $this->cleanTranscription($row['transcription'] ?? '');
            $source = $row['source'] ?? basename($file);
            $rowPart = $this->resolvePart($row['part'] ?? null, $source, $folder);
            $categoryId = $this->resolveCategoryId($disk, $source, $folder);
            $rowRank = isset($row['number']) && $row['number'] !== null
                ? $row['number']
                : ($withFrequency ? $rank : null);

            if ($dryRun) {
                $created++;

                continue;
            }

            $this->importRow($enId, $ruId, $enText, $ruText, $transcription, $rowPart, $categoryId, $rowRank);
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
            $ruKey = mb_strtolower($ruText);

            $existing = $this->findTranslation($enId, $key);

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

            $meaning = $this->findMeaning($entryId, $ruKey);

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
                $exists = $this->findTranslationInMeaning($meaning->id, $languageId, $text);

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

    private function findTranslation(string $languageId, string $text): ?EntryTranslation
    {
        // English texts are ASCII, so SQL LOWER() is safe here.
        return EntryTranslation::query()
            ->where('language_id', $languageId)
            ->whereRaw('LOWER(text) = ?', [mb_strtolower($text)])
            ->first();
    }

    private function findMeaning(string $entryId, string $ruKey): ?EntryMeaning
    {
        return EntryMeaning::query()
            ->where('entry_id', $entryId)
            ->get()
            ->first(fn (EntryMeaning $m) => mb_strtolower((string) $m->note) === $ruKey);
    }

    private function findTranslationInMeaning(string $meaningId, string $languageId, string $text): ?EntryTranslation
    {
        $key = mb_strtolower($text);

        return EntryTranslation::query()
            ->where('meaning_id', $meaningId)
            ->where('language_id', $languageId)
            ->get()
            ->first(fn (EntryTranslation $t) => mb_strtolower($t->text) === $key);
    }

    private function resolvePart(?string $rowPart, string $source, ?string $folder): ?string
    {
        if ($this->option('part')) {
            return $this->option('part');
        }

        $cleaned = $this->clean($rowPart ?? '');

        if ($cleaned !== '' && isset(self::PART_BY_FOLDER[$cleaned])) {
            return self::PART_BY_FOLDER[$cleaned];
        }

        if ($cleaned !== '') {
            return $cleaned;
        }

        $lower = mb_strtolower($source);

        foreach (self::FILENAME_HINTS as $hint) {
            if (mb_strpos($lower, $hint['fragment']) !== false) {
                return $hint['part'];
            }
        }

        return $folder !== null ? (self::PART_BY_FOLDER[$folder] ?? null) : null;
    }

    private function resolveCategoryId($disk, string $source, ?string $folder): ?string
    {
        if ($this->option('category')) {
            return $this->slugToCategoryId((string) $this->option('category'));
        }

        $base = $this->sourceBase($source);

        if (isset($this->categoryCache[$base])) {
            return $this->categoryCache[$base];
        }

        $map = $this->sourceMap($disk);

        if (isset($map[$base])) {
            return $this->categoryCache[$base] = $this->slugToCategoryId($map[$base]);
        }

        $categoryId = $this->categoryIdByRuName($base);

        if ($categoryId === null && $folder !== null && isset(self::CATEGORY_BY_FOLDER[$folder])) {
            $categoryId = $this->slugToCategoryId(self::CATEGORY_BY_FOLDER[$folder]);
        }

        if ($categoryId === null) {
            $lower = mb_strtolower($base);

            foreach (self::FILENAME_HINTS as $hint) {
                if (mb_strpos($lower, $hint['fragment']) !== false) {
                    $categoryId = $this->slugToCategoryId($hint['category']);

                    break;
                }
            }
        }

        if ($categoryId === null && $base !== '') {
            $this->unmatchedSources[$base] = true;
        }

        return $this->categoryCache[$base] = $categoryId;
    }

    private function sourceBase(string $source): string
    {
        $base = trim(basename($source));

        return (string) preg_replace('/\.(xlsx|csv)$/iu', '', $base);
    }

    /**
     * @return array<string, string>
     */
    private function sourceMap($disk): array
    {
        if ($this->sourceMap !== null) {
            return $this->sourceMap;
        }

        $this->sourceMap = [];

        if (! $this->option('map-file')) {
            return $this->sourceMap;
        }

        $path = (string) $this->option('map-file');
        $full = $disk->exists($path) ? $disk->path($path) : $path;

        if (! is_file($full)) {
            $this->warn("Map file not found: {$path}");

            return $this->sourceMap;
        }

        $decoded = json_decode((string) file_get_contents($full), true);

        if (is_array($decoded)) {
            foreach ($decoded as $source => $slug) {
                $this->sourceMap[$this->sourceBase((string) $source)] = (string) $slug;
            }
        }

        return $this->sourceMap;
    }

    private function categoryIdByRuName(string $base): ?string
    {
        if ($base === '') {
            return null;
        }

        $id = DB::table('translations')
            ->whereIn('entity_type', [
                Category::class,
                'App\\Modules\\Catalog\\Infrastructure\\Persistence\\Eloquent\\Category',
            ])
            ->where('field', 'name')
            ->where('locale', 'ru')
            ->where('value', $base)
            ->value('entity_id');

        return $id ? (string) $id : null;
    }

    private function slugToCategoryId(string $slug): ?string
    {
        $id = Category::query()->where('slug', $slug)->value('id');

        return $id ? (string) $id : null;
    }

    /**
     * @return list<array{en: string, ru: string, transcription: ?string, part: ?string, source: ?string, number: ?int}>
     */
    private function readRows($disk, string $file): array
    {
        if (str_ends_with(mb_strtolower($file), '.csv')) {
            return $this->readCsv($disk->path($file));
        }

        return $this->readXlsx($disk->path($file));
    }

    /**
     * @return list<array{en: string, ru: string, transcription: ?string, part: ?string, source: ?string, number: ?int}>
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
                'source' => null,
                'number' => null,
            ];
        }

        return $result;
    }

    /**
     * @return list<array{en: string, ru: string, transcription: ?string, part: ?string, source: ?string, number: ?int}>
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

            $number = isset($data['number']) && is_numeric(trim((string) $data['number']))
                ? (int) trim((string) $data['number'])
                : null;

            $result[] = [
                'en' => (string) ($data['name'] ?? ''),
                'transcription' => (string) ($data['transcription'] ?? ''),
                'ru' => (string) ($data['translation'] ?? ''),
                'part' => isset($data['part']) ? (string) $data['part'] : null,
                'source' => isset($data['name_file']) ? (string) $data['name_file'] : null,
                'number' => $number,
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
