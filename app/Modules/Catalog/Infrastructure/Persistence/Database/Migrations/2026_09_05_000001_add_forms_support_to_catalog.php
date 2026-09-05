<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('word_forms', function (Blueprint $table) {
            $table->string('transcription')->nullable()->after('form');
        });

        Schema::table('entries', function (Blueprint $table) {
            $table->string('forms_pattern', 60)->nullable()->after('frequency_rank');
        });
    }

    public function down(): void
    {
        Schema::table('entries', function (Blueprint $table) {
            $table->dropColumn('forms_pattern');
        });

        Schema::table('word_forms', function (Blueprint $table) {
            $table->dropColumn('transcription');
        });
    }
};
