<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achievements', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('code', 50)->unique();
            $table->string('title');
            $table->string('desc')->nullable()->comment('Short label shown on the card');
            $table->text('condition')->comment('Human-readable unlock condition');

            $table->string('rarity', 10)->default('common');
            $table->string('color', 15)->nullable();
            $table->unsignedInteger('reward_xp')->default(0);

            $table->string('rule_type', 30)->comment('streak_days, words_learned, xp_total, reviews_total, reviews_of_type, goals_completed, accuracy');
            $table->unsignedInteger('rule_target')->default(1);
            $table->json('rule_extra')->nullable()->comment('E.g. {"learnable_type":"phrase","min_reviews":20}');

            $table->unsignedSmallInteger('sort')->default(500);

            $table->timestampsTz();
        });

        Schema::create('user_achievements', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('profile_id')->constrained('user_language_profiles')->cascadeOnDelete();
            $table->foreignUuid('achievement_id')->constrained('achievements')->cascadeOnDelete();

            $table->unsignedInteger('progress')->default(0);
            $table->boolean('unlocked')->default(false);
            $table->timestampTz('unlocked_at')->nullable();

            $table->timestampsTz();

            $table->unique(['user_id', 'profile_id', 'achievement_id']);
            $table->index(['profile_id', 'unlocked']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_achievements');
        Schema::dropIfExists('achievements');
    }
};
