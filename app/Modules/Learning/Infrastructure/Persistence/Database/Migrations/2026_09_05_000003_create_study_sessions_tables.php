<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('profile_id')->constrained('user_language_profiles')->cascadeOnDelete();

            $table->string('source', 20)->default('mixed')->comment('due, new, mixed');
            $table->string('status', 10)->default('active')->comment('active, finished, abandoned');

            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('answered')->default(0);
            $table->unsignedInteger('correct')->default(0);
            $table->unsignedInteger('xp_earned')->default(0);

            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('finished_at')->nullable();

            $table->timestampsTz();

            $table->index(['profile_id', 'status']);
        });

        Schema::create('study_session_items', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('session_id')->constrained('study_sessions')->cascadeOnDelete();

            $table->string('learnable_type', 20);
            $table->uuid('learnable_id');

            $table->unsignedInteger('position')->default(0);

            $table->string('status', 10)->default('pending')->comment('pending, correct, wrong, skipped');
            $table->unsignedSmallInteger('quality')->nullable();

            $table->timestampsTz();

            $table->unique(['session_id', 'position']);
            $table->index(['session_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_session_items');
        Schema::dropIfExists('study_sessions');
    }
};
