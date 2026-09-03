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
        Schema::create('phrases', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('level', 2)->default('A1');

            $table->string('phrase_type')->default('phrase');

            $table->string('image_path')->nullable();

            $table->timestampsTz();

            $table->index(['phrase_type', 'level']);
        });

        Schema::create('phrase_translations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('phrase_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('language_id')->constrained('languages')->cascadeOnDelete();

            $table->text('text');
            $table->text('transcription')->nullable();
            $table->string('audio_path')->nullable();

            $table->timestampsTz();

            $table->unique(['phrase_id', 'language_id']);
        });

        Schema::create('phrases_categories', function (Blueprint $table) {
            $table->foreignUuid('phrase_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('category_id')->constrained()->cascadeOnDelete();

            $table->primary(['phrase_id', 'category_id']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phrases_categories');
        Schema::dropIfExists('phrase_translations');
        Schema::dropIfExists('phrases');
    }
};
