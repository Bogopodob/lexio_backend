<?php

namespace Tests\Feature;

use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Category;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Entry;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\EntryTranslation;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Language;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CatalogImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        Language::query()->create(['code' => 'en', 'name' => 'English', 'native_name' => 'English']);
        Language::query()->create(['code' => 'ru', 'name' => 'Russian', 'native_name' => 'Русский']);

        Category::query()->create(['slug' => 'verbs', 'type' => 'grammar']);

        Storage::disk('public')->put(
            'words/test.csv',
            "\xEF\xBB\xBFname;transcription;translation;part\nrun;[rʌn];бегать;verb\nrun;[rʌn];запускать;verb\n"
        );
    }

    public function test_import_creates_entries_grouped_into_meanings(): void
    {
        $this->artisan('catalog:import-words', ['--file' => 'words/test.csv', '--category' => 'verbs'])
            ->assertSuccessful();

        $this->assertSame(1, Entry::query()->count());
        $this->assertSame(2, DB::table('entry_meanings')->count());
        $this->assertSame(4, EntryTranslation::query()->count());

        $en = EntryTranslation::query()->where('text', 'run')->first();

        $this->assertNotNull($en);
        $this->assertSame('rʌn', $en->transcription);
        $this->assertSame('verb', $en->part_of_speech);
        $this->assertSame(1, DB::table('entry_category')->count());
    }

    public function test_import_is_idempotent(): void
    {
        $options = ['--file' => 'words/test.csv'];

        $this->artisan('catalog:import-words', $options)->assertSuccessful();
        $this->artisan('catalog:import-words', $options)->assertSuccessful();

        $this->assertSame(1, Entry::query()->count());
        $this->assertSame(4, EntryTranslation::query()->count());
    }

    public function test_dry_run_writes_nothing(): void
    {
        $this->artisan('catalog:import-words', ['--file' => 'words/test.csv', '--dry-run' => true])
            ->assertSuccessful();

        $this->assertSame(0, Entry::query()->count());
        $this->assertSame(0, EntryTranslation::query()->count());
    }
}
