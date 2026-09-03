<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * All domain tables must exist after migrating.
     *
     * @return array<string>
     */
    public static function tables(): array
    {
        return [
            'users',
            'user_profiles',
            'languages',
            'categories',
            'entries',
            'entry_meanings',
            'entry_translations',
            'word_forms',
            'entry_category',
            'phrases',
            'phrase_translations',
            'phrases_categories',
            'entry_phrase',
            'entry_media',
            'content_sources',
            'translations',
            'user_language_profiles',
            'user_language_stats',
            'user_progresses',
            'user_streaks',
            'user_entries',
            'user_entry_translations',
            'user_phrases',
            'user_phrase_translations',
        ];
    }

    public function test_all_domain_tables_exist(): void
    {
        foreach (self::tables() as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }
    }

    public function test_user_profiles_has_extended_columns(): void
    {
        foreach (['city', 'birth_date', 'tags'] as $column) {
            $this->assertTrue(Schema::hasColumn('user_profiles', $column), "Missing column: user_profiles.{$column}");
        }
    }

    public function test_entry_translations_supports_several_translations_per_language(): void
    {
        $this->assertTrue(Schema::hasColumn('entry_translations', 'meaning_id'));
        $this->assertFalse(Schema::hasIndex('entry_translations', ['entry_id', 'language_id']));
    }
}
