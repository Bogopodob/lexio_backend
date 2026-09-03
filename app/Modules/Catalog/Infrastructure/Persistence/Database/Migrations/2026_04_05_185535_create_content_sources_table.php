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
        Schema::create('content_sources', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('type', 10)->comment('movie, music, text');
            $table->string('title');
            $table->foreignUuid('language_id')->nullable()->constrained('languages')->nullOnDelete();
            $table->string('level', 2)->nullable();
            $table->string('external_id')->nullable()->comment('External catalog reference');

            $table->timestampsTz();

            $table->index(['type', 'language_id']);

            $table->comment('Movies, songs and texts where words occur');
        });

        Schema::table('entry_media', function (Blueprint $table) {
            $table->foreignUuid('content_source_id')->nullable()->constrained('content_sources')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entry_media', function (Blueprint $table) {
            $table->dropConstrainedForeignId('content_source_id');
        });

        Schema::dropIfExists('content_sources');
    }
};
