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
        Schema::create('entry_phrase', function (Blueprint $table) {
            $table->foreignUuid('entry_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('phrase_id')->constrained()->cascadeOnDelete();

            $table->foreignUuid('meaning_id')->nullable()->constrained('entry_meanings')->nullOnDelete();

            $table->primary(['entry_id', 'phrase_id']);
            $table->index(['meaning_id']);

            $table->comment('Example phrases linked to a word, optionally to a specific meaning');
        });

        Schema::create('entry_media', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('entry_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('entry_translation_id')->nullable()->constrained('entry_translations')->cascadeOnDelete();

            $table->string('type', 10)->comment('image, audio, video');
            $table->string('path')->comment('Storage path or external URL');

            $table->string('source', 10)->default('own')->comment('own, movie, music');
            $table->string('source_title')->nullable()->comment('Movie/song title where the word occurs');

            $table->smallInteger('sort')->default(500);

            $table->timestampsTz();

            $table->index(['entry_id']);
            $table->index(['entry_translation_id']);

            $table->comment('Pictures, audio and video for words; at least one of entry_id / entry_translation_id must be set (enforced in application layer)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entry_media');
        Schema::dropIfExists('entry_phrase');
    }
};
