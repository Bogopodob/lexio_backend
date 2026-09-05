<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('word_hints', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('profile_id');
            $table->string('learnable_type', 20);
            $table->uuid('learnable_id');
            $table->string('hint', 280);
            $table->timestamps();

            $table->foreign('profile_id')
                ->references('id')
                ->on('user_language_profiles')
                ->cascadeOnDelete();

            $table->unique(['profile_id', 'learnable_type', 'learnable_id'], 'word_hints_profile_learnable_unique');
            $table->index(['profile_id', 'learnable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('word_hints');
    }
};
