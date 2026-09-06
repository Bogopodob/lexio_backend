<?php

namespace App\Modules\Catalog\Infrastructure\Console\Commands;

use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Category;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Language;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Phrase;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\PhraseTranslation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Process\Process;

class ImportPhrasesCommand extends Command
{
    protected $signature = 'catalog:import-phrases
        {--file=worlds.md : path relative to the backend root}
        {--limit= : max phrases to import}
        {--dry-run : parse the file without writing to database}
        {--no-transcribe : skip espeak-ng transcription generation}';

    protected $description = 'Import system phrase blocks (worlds.md) into the catalog';

    /**
     * File section number => [slug, ru, en].
     *
     * @var array<string, array{slug: string, ru: string, en: string}>
     */
    private const BLOCKS = [
        '1.1' => ['slug' => 'phr-greetings', 'ru' => 'Приветствия', 'en' => 'Greetings'],
        '1.2' => ['slug' => 'phr-replies', 'ru' => 'Ответы', 'en' => 'Replies'],
        '1.3' => ['slug' => 'phr-thanks', 'ru' => 'Благодарность', 'en' => 'Thanks'],
        '1.4' => ['slug' => 'phr-sorry', 'ru' => 'Извинения', 'en' => 'Apologies'],
        '1.5' => ['slug' => 'phr-questions', 'ru' => 'Вопросы', 'en' => 'Questions'],
        '2.1' => ['slug' => 'phr-morning', 'ru' => 'Утро', 'en' => 'Morning'],
        '2.2' => ['slug' => 'phr-home', 'ru' => 'Быт', 'en' => 'Home life'],
        '4.1' => ['slug' => 'phr-shopping', 'ru' => 'Магазин', 'en' => 'Shopping'],
        '4.2' => ['slug' => 'phr-restaurant', 'ru' => 'Ресторан', 'en' => 'Restaurant'],
        '4.3' => ['slug' => 'phr-bank', 'ru' => 'Банк и услуги', 'en' => 'Bank & services'],
        '4.4' => ['slug' => 'phr-services', 'ru' => 'Разное', 'en' => 'Misc'],
    ];

    private const TABLE_HEADERS = ['№', 'Фраза на английском', 'Транскрипция', 'Перевод'];

    private int $phrasesCreated = 0;

    private int $translationsCreated = 0;

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
        $path = base_path((string) $this->option('file'));

        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $en = Language::query()->where('code', 'en')->first();
        $ru = Language::query()->where('code', 'ru')->first();

        if (! $en || ! $ru) {
            $this->error('Languages en/ru must exist. Run language seeder first.');

            return self::FAILURE;
        }

        $rows = $this->parse($path);
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;

        if ($limit !== null) {
            $rows = array_slice($rows, 0, $limit);
        }

        $this->info(sprintf('Parsed %d phrases.', count($rows)));

        if ((bool) $this->option('dry-run')) {
            $this->table(
                ['en', 'ru', 'tr', 'block'],
                array_map(fn ($r) => [
                    mb_substr($r['en'], 0, 32),
                    mb_substr($r['ru'], 0, 32),
                    $r['tr'] ?? '—',
                    $r['block'],
                ], array_slice($rows, 0, 12)),
            );
            $this->info('Dry run — nothing written.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($rows, $en, $ru) {
            $categoryIds = $this->ensureCategories();

            foreach ($rows as $row) {
                $this->importRow($row, $categoryIds, (string) $en->id, (string) $ru->id);
            }
        });

        $this->info(sprintf(
            'Done. phrases: %d, translations: %d, transcribed: %d, skipped: %d',
            $this->phrasesCreated,
            $this->translationsCreated,
            $this->transcribed,
            $this->skipped,
        ));

        return self::SUCCESS;
    }

    /**
     * Records are anchored at their № line: the next three meaningful
     * lines are EN, transcription, RU. Any stray line only discards
     * the current partial record instead of desyncing the whole file.
     *
     * @return list<array{en: string, ru: string, tr: ?string, block: string}>
     */
    private function parse(string $path): array
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES);

        if ($lines === false) {
            return [];
        }

        $rows = [];
        $block = null;
        $cells = [];

        foreach ($lines as $line) {
            $cell = trim($line);

            if ($cell === '' || in_array($cell, self::TABLE_HEADERS, true)) {
                continue;
            }

            // Chat markers like "[9/7/26 1:55 AM] Я: 4.1 В магазине".
            // The section header may hide inside the marker itself.
            if (preg_match('/^\[[^\]]*\]\s*\S+:/u', $cell)) {
                if (preg_match('/^.*?Я:\s*(\d+\.\d+)\s+\S/u', $cell, $m) && isset(self::BLOCKS[$m[1]])) {
                    $block = $m[1];
                }

                $cells = [];

                continue;
            }

            if (preg_match('/^(\d+\.\d+)\s+.+$/u', $cell, $m) && isset(self::BLOCKS[$m[1]])) {
                $block = $m[1];
                $cells = [];

                continue;
            }

            if (ctype_digit($cell)) {
                $cells = [];

                continue;
            }

            if ($block === null || count($cells) >= 3) {
                continue;
            }

            $cells[] = $cell;

            if (count($cells) === 3) {
                [$en, $tr, $ru] = $cells;

                if ($en !== '' && $ru !== '') {
                    $rows[] = [
                        'en' => $en,
                        'ru' => $ru,
                        'tr' => $this->cleanTranscription($tr),
                        'block' => $block,
                    ];
                }

                $cells = [];
            }
        }

        return $rows;
    }

    private function cleanTranscription(string $value): ?string
    {
        $value = trim(str_replace("\u{00A0}", '', $value));

        if ($value === '' || $value === '—' || $value === '-') {
            return null;
        }

        // Single "[...]" pair → store bare IPA; several "[a] / [b]"
        // variants stay as written.
        if (str_starts_with($value, '[') && str_ends_with($value, ']')
            && substr_count($value, '[') === 1
        ) {
            $value = trim(mb_substr($value, 1, mb_strlen($value) - 2));
        }

        return $value === '' ? null : $value;
    }

    /**
     * @return array{parent: string, bands: array<string, string>}
     */
    private function ensureCategories(): array
    {
        $parent = $this->ensureCategory(
            'phrases', null, 'phrase', 'Разговорные фразы', 'Spoken phrases', '#F08AB4', '💬', 120,
        );

        $bands = [];

        foreach (self::BLOCKS as $block) {
            $bands[$block['slug']] = $this->ensureCategory(
                $block['slug'], $parent, 'phrase', $block['ru'], $block['en'], '#F08AB4', '💬', 130,
            );
        }

        return ['parent' => $parent, 'bands' => $bands];
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
     * @param  array{parent: string, bands: array<string, string>}  $categoryIds
     */
    private function importRow(array $row, array $categoryIds, string $enId, string $ruId): void
    {
        $block = self::BLOCKS[$row['block']] ?? null;

        if (! $block) {
            $this->skipped++;

            return;
        }

        $existingId = PhraseTranslation::query()
            ->where('language_id', $enId)
            ->whereRaw('LOWER(text) = ?', [mb_strtolower($row['en'])])
            ->value('phrase_id');

        if ($existingId) {
            $this->attachCategory((string) $existingId, $categoryIds['bands'][$block['slug']]);
            $this->skipped++;

            return;
        }

        $phraseId = Uuid::uuid4()->toString();

        Phrase::query()->create(['id' => $phraseId, 'level' => 'A1', 'phrase_type' => 'phrase']);

        PhraseTranslation::query()->create([
            'id' => Uuid::uuid4()->toString(),
            'phrase_id' => $phraseId,
            'language_id' => $enId,
            'text' => $row['en'],
            'transcription' => $this->transcribe($row['en'], $row['tr']),
        ]);
        $this->translationsCreated++;

        PhraseTranslation::query()->create([
            'id' => Uuid::uuid4()->toString(),
            'phrase_id' => $phraseId,
            'language_id' => $ruId,
            'text' => $row['ru'],
        ]);
        $this->translationsCreated++;

        $this->attachCategory($phraseId, $categoryIds['bands'][$block['slug']]);
        $this->phrasesCreated++;
    }

    private function attachCategory(string $phraseId, string $categoryId): void
    {
        $exists = DB::table('phrases_categories')
            ->where('phrase_id', $phraseId)
            ->where('category_id', $categoryId)
            ->exists();

        if (! $exists) {
            DB::table('phrases_categories')->insert([
                'phrase_id' => $phraseId,
                'category_id' => $categoryId,
            ]);
        }
    }

    private function transcribe(string $text, ?string $known): ?string
    {
        if ($known !== null && $known !== '') {
            return $known;
        }

        if ($this->option('no-transcribe')) {
            return null;
        }

        $key = mb_strtolower(trim($text));

        if (isset($this->transcribeCache[$key])) {
            return $this->transcribeCache[$key] !== '' ? $this->transcribeCache[$key] : null;
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

        $process = new Process(['espeak-ng', '--ipa', '-v', 'en', $text]);
        $process->setTimeout(15);
        $process->run();

        $ipa = trim(explode("\n", (string) $process->getOutput())[0] ?? '');

        if (! $process->isSuccessful() || $ipa === '') {
            $this->transcribeCache[$key] = '';

            return null;
        }

        $this->transcribeCache[$key] = $ipa;
        $this->transcribed++;

        return $ipa;
    }
}
