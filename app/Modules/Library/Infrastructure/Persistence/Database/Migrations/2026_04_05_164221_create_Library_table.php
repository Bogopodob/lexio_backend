<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();

            $table->foreignUuid('category_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('image_path')->nullable();

            $table->timestampsTz();
        });

        Schema::create('user_entry_translations', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_entry_id')->constrained('user_entries')->cascadeOnDelete();

            $table->foreignUuid('language_id')->constrained('languages')->cascadeOnDelete();

            $table->string('text');
            $table->string('transcription')->nullable();
            $table->string('part_of_speech')->nullable();
            $table->text('notes')->nullable();

            $table->timestampsTz();

            $table->unique(['user_entry_id', 'language_id']);
        });

        Schema::create('user_phrases', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();

            $table->foreignUuid('category_id')->nullable()->constrained()->nullOnDelete();

            $table->string('phrase_type')->default('phrase');
            $table->timestampsTz();
        });

        Schema::create('user_phrase_translations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_phrase_id')->constrained('user_phrases')->cascadeOnDelete();

            $table->foreignUuid('language_id')->constrained('languages')->cascadeOnDelete();

            $table->text('text');
            $table->text('transcription')->nullable();
            $table->text('notes')->nullable();

            $table->timestampsTz();

            $table->unique(['user_phrase_id', 'language_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_phrase_translations');
        Schema::dropIfExists('user_phrases');
        Schema::dropIfExists('user_entry_translations');
        Schema::dropIfExists('user_entries');
    }
};
