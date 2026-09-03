<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_language_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();

            $table->foreignUuid('target_language_id')->constrained('languages')->cascadeOnDelete();
            $table->foreignUuid('native_language_id')->constrained('languages')->cascadeOnDelete();

            $table->string('level', 2)->default('A2')->comment('CEFR level of target language');

            $table->unsignedInteger('daily_goal')->default(10)->comment('Words per day');

            $table->boolean('is_active')->default(true);

            $table->timestampsTz();

            $table->unique(['user_id', 'target_language_id']);

            $table->comment('One learning profile per user per target language');
        });

        Schema::create('user_language_stats', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('profile_id')->constrained('user_language_profiles')->cascadeOnDelete()->unique();

            $table->unsignedInteger('words_learned')->default(0);
            $table->unsignedInteger('streak_days')->default(0);
            $table->unsignedInteger('best_streak')->default(0);
            $table->unsignedBigInteger('xp')->default(0);
            $table->float('accuracy')->default(0)->comment('Share of correct answers, 0..1');

            $table->timestampTz('last_activity_at')->nullable();

            $table->timestampsTz();

            $table->comment('Aggregated statistics per language profile');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_language_stats');
        Schema::dropIfExists('user_language_profiles');
    }
};
