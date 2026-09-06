<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_entry_translations', function (Blueprint $table) {
            $table->string('audio_path', 2048)->nullable()->after('transcription');
        });

        Schema::table('user_phrase_translations', function (Blueprint $table) {
            $table->string('audio_path', 2048)->nullable()->after('transcription');
        });

        Schema::table('user_phrases', function (Blueprint $table) {
            $table->string('image_path', 2048)->nullable()->after('category_id');
        });

        Schema::create('library_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('kind', 10);
            $table->string('path', 2048);
            $table->string('mime', 100);
            $table->unsignedBigInteger('bytes')->default(0);
            $table->timestampsTz();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['user_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_media');

        Schema::table('user_phrases', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });

        Schema::table('user_phrase_translations', function (Blueprint $table) {
            $table->dropColumn('audio_path');
        });

        Schema::table('user_entry_translations', function (Blueprint $table) {
            $table->dropColumn('audio_path');
        });
    }
};
