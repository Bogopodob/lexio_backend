<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_goals', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('profile_id')->constrained('user_language_profiles')->cascadeOnDelete();

            $table->string('title');
            $table->string('desc')->nullable();
            $table->string('color', 15)->nullable();
            $table->unsignedSmallInteger('progress')->default(0)->comment('0..100');

            $table->unsignedSmallInteger('sort')->default(500);

            $table->timestampsTz();

            $table->index(['profile_id', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_goals');
    }
};
