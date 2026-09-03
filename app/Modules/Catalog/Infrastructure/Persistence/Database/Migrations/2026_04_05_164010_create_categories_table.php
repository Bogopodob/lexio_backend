<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('parent_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();

            $table->uuid('user_id')->nullable();

            $table->string('slug');

            $table->boolean('is_system')->default(false);

            $table->string('type')->default('theme');

            $table->string('color', 15)->nullable();
            $table->string('icon')->nullable();
            $table->smallInteger('sort')->default(500);

            $table->timestampsTz();

            $table->unique(['slug', 'user_id'], 'unique_slug_user');

            $table->index(['type', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
