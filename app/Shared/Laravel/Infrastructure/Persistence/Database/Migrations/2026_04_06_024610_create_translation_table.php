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
        Schema::create('translations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('entity_type');
            $table->uuid('entity_id');
            $table->string('locale');
            $table->string('field');
            $table->string('value');

            $table->timestampsTz();
            $table->softDeletes();

            $table->index(['locale', 'field', 'value']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
