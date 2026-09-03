<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_progresses', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();

            $table->foreignUuid('profile_id')->constrained('user_language_profiles')->cascadeOnDelete()->comment('Language profile being trained');

            $table->string('learnable_type');
            $table->uuid('learnable_id');

            $table->float('easiness_factor')->default(2.5);
            $table->unsignedInteger('interval_days')->default(1);
            $table->unsignedInteger('repetition')->default(0);
            $table->unsignedSmallInteger('quality_last')->default(0);

            $table->timestampTz('next_review_at')->nullable();
            $table->timestampTz('last_reviewed_at')->nullable();

            $table->unique(['profile_id', 'learnable_type', 'learnable_id']);

            $table->index(['profile_id', 'next_review_at'], 'user_progress_review_idx');
            $table->index(['learnable_type', 'learnable_id'], 'user_progress_learnable_idx');

            $table->timestampsTz();
        });

        Schema::create('user_streaks', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();

            $table->foreignUuid('profile_id')->nullable()->constrained('user_language_profiles')->nullOnDelete()->comment('Null means global streak across languages');

            $table->dateTimeTz('date');
            $table->unsignedInteger('words_reviewed')->default(0);
            $table->unsignedInteger('words_new')->default(0);

            $table->unique(['user_id', 'profile_id', 'date']);

            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_streaks');
        Schema::dropIfExists('user_progresses');
    }
};
