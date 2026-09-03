<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('entries', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('image_path')->nullable()->comment('Графика для слова');
            $table->string('level', 2)->default('A1')->comment('Уровень сложности слово');

            $table->unsignedInteger('frequency_rank')->nullable()->comment('Частота слова');

            $table->timestampsTz();

            $table->index(['level', 'frequency_rank']);

            $table->comment('Абстрактные слова без привязки к языку. Пример: EN - run, RU - бегать, FR - courir');
        });

        Schema::create('entry_meanings', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('entry_id')->constrained()->cascadeOnDelete();

            $table->string('note')->nullable()->comment('Gloss: what distinguishes this meaning, e.g. "peace" vs "world"');

            $table->timestampsTz();

            $table->comment('Meanings (senses) of a concept. Example: "мир" -> "peace" and "world"');
        });

        Schema::create('entry_translations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('entry_id')->constrained()->cascadeOnDelete();

            $table->foreignUuid('meaning_id')->constrained('entry_meanings')->cascadeOnDelete();

            $table->foreignUuid('language_id')->constrained('languages')->cascadeOnDelete();

            $table->string('text');
            $table->string('transcription')->nullable();
            $table->string('audio_path')->nullable();

            $table->string('part_of_speech')->nullable()->comment('Часть речи: verb, noun');

            $table->timestampsTz();

            $table->unique(['meaning_id', 'language_id', 'text']);
            $table->index(['language_id', 'text']);

            $table->comment('Translations grouped by meaning: several translations per language allowed');
        });

        Schema::create('word_forms', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('entry_translation_id')->constrained('entry_translations')->cascadeOnDelete();

            $table->string('form')->comment('Например: ran, running, runner');

            $table->string('form_type')->comment('Глаголы, существительные и так далее');

            $table->timestampsTz();

            $table->index(['entry_translation_id']);

            $table->comment('Формы слова привязаны к конкретному языку');
        });

        Schema::create('entry_category', function (Blueprint $table) {
            $table->foreignUuid('entry_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('category_id')->constrained()->cascadeOnDelete();

            $table->primary(['entry_id', 'category_id']);

            $table->comment('Одна концепция может быть в нескольких категориях');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entry_category');
        Schema::dropIfExists('word_forms');
        Schema::dropIfExists('entry_translations');
        Schema::dropIfExists('entry_meanings');
        Schema::dropIfExists('entries');
    }
};
